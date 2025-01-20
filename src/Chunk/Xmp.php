<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Xmp extends Chunk
{
    private function __construct(string $data)
    {
        parent::__construct("XMP ", $data);
    }

    public static function forBytes(string $bytes): self
    {
        return new Xmp($bytes);
    }
}
