<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

abstract class Chunk
{
    protected function __construct(
        private readonly string $fourCC,
        private readonly int $offset,
        private readonly string $data,
    ) {}

    public function getFourCC(): string
    {
        return $this->fourCC;
    }

    public function getLength(): int
    {
        return \strlen($this->data);
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getRawBytes(): string
    {
        return $this->data;
    }
}
