<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk;

final class Xmp extends Chunk
{
    private function __construct(int $offset, string $data)
    {
        parent::__construct("XMP ", $offset, $data);
    }

    public static function forBytes(int $offset, string $bytes): self
    {
        return new Xmp($offset, $bytes);
    }
}
