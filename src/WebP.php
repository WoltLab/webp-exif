<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use InvalidArgumentException;

final class WebP
{
    private function __construct(
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
            static fn(int $length, Chunk $chunk) => $length += $chunk->length + 8,
            0
        );
    }

    /**
     * @return array{'length': int, 'chunks': list<string>}
     */
    public function debugInfo(): array
    {
        $chunkInfo = \array_map(
            static function (Chunk $chunk) {
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
            'chunks' => $chunkInfo,
        ];
    }

    /**
     * @param list<Chunk> $chunks
     */
    public static function fromChunks(array $chunks): self
    {
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof Chunk)) {
                throw new InvalidArgumentException("TODO: not a webp chunk");
            }
        }

        return new self($chunks);
    }
}
