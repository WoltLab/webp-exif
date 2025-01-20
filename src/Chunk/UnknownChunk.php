<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use RuntimeException;
use Woltlab\WebpExif\ChunkType;

final class UnknownChunk implements Chunk
{
    private function __construct(
        private readonly string $fourCC,
        private readonly string $data
    ) {}

    public function getFourCC(): string
    {
        return $this->fourCC;
    }

    public function getLength(): int
    {
        return \strlen($this->data);
    }

    public static function forBytes(string $fourCC, string $bytes): self
    {
        if (ChunkType::fromFourCC($fourCC) !== ChunkType::UnknownChunk) {
            throw new RuntimeException("TODO: Cannot create an unknown chunk from a known value, {$fourCC} is well-defined");
        }

        return new UnknownChunk($fourCC, $bytes);
    }
}
