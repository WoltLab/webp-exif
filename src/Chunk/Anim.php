<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Anim extends Chunk
{
    private function __construct(string $data)
    {
        parent::__construct("ANIM", $data);
    }

    public static function forBytes(string $bytes): self
    {
        return new Anim($bytes);
    }
}
