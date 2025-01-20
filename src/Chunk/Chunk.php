<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

interface Chunk
{
    public function getFourCC(): string;
    public function getLength(): int;
}
