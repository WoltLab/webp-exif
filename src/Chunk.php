<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

final class Chunk
{
    public readonly int $length;
    public readonly ChunkType $type;

    public function __construct(
        public readonly string $fourCC,
        public readonly string $data,
    ) {
        $this->length = \strlen($data);
        $this->type = ChunkType::fromFourCC($fourCC);
    }
}
