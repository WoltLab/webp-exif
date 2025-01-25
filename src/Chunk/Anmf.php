<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use Override;
use Woltlab\WebpExif\Decoder;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\UnexpectedEndOfFile;

final class Anmf extends Chunk
{
    /** @param list<Chunk> $chunks */
    private function __construct(
        int $offset,
        string $data,
        private readonly array $chunks
    ) {
        parent::__construct("ANMF", $offset, $data);
    }

    #[Override]
    public function getLength(): int
    {
        return \array_reduce(
            $this->chunks,
            static fn($acc, $chunk) => $acc + $chunk->getLength() + 8,
            parent::getLength(),
        );
    }

    /**
     * @return list<Chunk>
     */
    public function getDataChunks(): array
    {
        return $this->chunks;
    }

    public static function fromBuffer(Buffer $buffer): self
    {
        $offset = $buffer->position();
        $length = $buffer->getUnsignedInt();
        if ($length > $buffer->remaining()) {
            throw new LengthOutOfBounds($length, $offset, $buffer->remaining());
        }

        // An animation frame contains at least 16 bytes for the header.
        if ($buffer->remaining() < 16) {
            throw new UnexpectedEndOfFile($buffer->position(), $buffer->remaining());
        }

        // The next 8 bytes contain the X and Y coordinates, as well as the
        // frame witdth and height. Afterwards there are 3 bytes for the frame
        // duration followed by 1 byte representing a bit field. (= 16 bytes)
        $frameHeader = $buffer->getString(16);

        $chunks = [];
        $decoder = new Decoder();
        while ($buffer->position() < $offset + 4 + $length) {
            $chunks[] = $decoder->decodeChunk($buffer);
            if ($chunk instanceof Alph) {
                // An ALPH chunk can only appear at the start of the frame data.
                if ($chunks !== []) {
                    throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
                }

                $chunks[] = $chunk;
            } else if ($chunk instanceof Vp8 || $chunk instanceof Vp8l) {
                switch (\count($chunks)) {
                    case 0:
                        $chunks[] = $chunk;
                        break;

                    case 1:
                        // A bitstream chunk can only appear at the first
                        // position or after an ALPH chunk.
                        if (!($chunk[0] instanceof Alph)) {
                            throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
                        }

                        $chunks[] = $chunk;
                        break;

                    default:
                        throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
                }
            } else if ($chunk instanceof UnknownChunk) {
                if ($chunks === []) {
                    // An unknown chunk cannot appear at the first position.
                    throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
                }

                $lastChunk = $chunks[\count($chunks) - 1];
                if (!($lastChunk instanceof Vp8 || $lastChunk instanceof Vp8l)) {
                    throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
                }

                $chunks[] = $chunk;
            } else {
                throw new UnexpectedChunk($chunk->getFourCC(), $chunk->getOffset());
            }
        }

        return new Anmf($offset, $frameHeader, $chunks);
    }
}
