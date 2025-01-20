<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use TypeError;
use Woltlab\WebpExif\Chunk\Chunk as IChunk;

final class WebP
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        /** @var list<Chunk|IChunk> */
        private array $chunks,
    ) {}

    /**
     * @return list<Chunk|IChunk>
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * Returns the length of all chunks including an additional 8 bytes per
     * chunk for the chunk header.
     */
    public function getByteLength(): int
    {
        return \array_reduce(
            $this->chunks,
            static function (int $length, Chunk|IChunk $chunk) {
                $chunkLength = $chunk instanceof IChunk ? $chunk->getLength() : $chunk->length;

                return $length + $chunkLength + 8;
            },
            0
        );
    }

    /**
     * @return array{'length': int, 'width': int, 'height': int, 'chunks': list<string>}
     */
    public function debugInfo(): array
    {
        $chunkInfo = \array_map(
            static function (Chunk|IChunk $chunk) {
                if ($chunk instanceof IChunk) {
                    return \sprintf(
                        "Chunk %s (length %d)",
                        $chunk->getFourCC(),
                        $chunk->getLength(),
                    );
                }

                return \sprintf(
                    "Chunk %s (length %d)",
                    $chunk->fourCC,
                    $chunk->length,
                );
            },
            $this->chunks,
        );

        return [
            'length' => $this->getByteLength(),
            'width' => $this->width,
            'height' => $this->height,
            'chunks' => $chunkInfo,
        ];
    }

    /**
     * @param list<Chunk|IChunk> $chunks
     */
    public static function fromChunks(int $width, int $height, array $chunks): self
    {
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof Chunk) && !($chunk instanceof IChunk)) {
                throw new TypeError(
                    \sprintf(
                        "Expected a list of %s, received %s instead",
                        Chunk::class,
                        \gettype($chunk),
                    ),
                );
            }
        }

        return new self($width, $height, $chunks);
    }
}
