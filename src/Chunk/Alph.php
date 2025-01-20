<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Alph extends Chunk
{
    private function __construct(string $data)
    {
        parent::__construct("ALPH", $data);
    }

    public static function forBytes(string $bytes): self
    {
        return new Alph($bytes);
    }
}
