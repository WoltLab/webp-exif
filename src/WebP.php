<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use TypeError;
use Woltlab\WebpExif\Chunk\Chunk;

final class WebP
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        /** @var list<Chunk> */
        private array $chunks,
    ) {}

    /**
     * @return list<Chunk>
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
            static fn (int $length, Chunk $chunk) => $length + $chunk->getLength() + 8,
            0
        );
    }

    /**
     * @return array{'length': int, 'width': int, 'height': int, 'chunks': list<string>}
     */
    public function debugInfo(): array
    {
        $chunkInfo = \array_map(
            static function (Chunk $chunk) {
                return \sprintf(
                    "Chunk %s (length %d)",
                    $chunk->getFourCC(),
                    $chunk->getLength(),
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
     * @param list<Chunk> $chunks
     */
    public static function fromChunks(int $width, int $height, array $chunks): self
    {
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof Chunk)) {
                throw new TypeError(
                    \sprintf(
                        "Expected a list of %s, received %s instead",
                        Chunk::class,
                        \get_debug_type($chunk),
                    ),
                );
            }
        }

        return new self($width, $height, $chunks);
    }
}
