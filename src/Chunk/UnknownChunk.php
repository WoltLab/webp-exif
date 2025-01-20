<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Woltlab\WebpExif\Chunk\Exception\UnknownChunkWithKnownFourCC;
use Woltlab\WebpExif\ChunkType;

final class UnknownChunk extends Chunk
{
    public static function forBytes(string $fourCC, string $bytes): self
    {
        if (ChunkType::fromFourCC($fourCC) !== ChunkType::UnknownChunk) {
            throw new UnknownChunkWithKnownFourCC($fourCC);
        }

        return new UnknownChunk($fourCC, $bytes);
    }
}
