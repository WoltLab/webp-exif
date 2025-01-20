<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Exif implements Chunk
{
    private function __construct(private readonly string $data) {}

    public function getFourCC(): string
    {
        return "EXIF";
    }

    public function getLength(): int
    {
        return \strlen($this->data);
    }

    public static function forBytes(string $bytes): self
    {
        return new Exif($bytes);
    }
}
