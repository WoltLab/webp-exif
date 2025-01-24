<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Anim extends Chunk
{
    private function __construct(int $offset, string $data)
    {
        parent::__construct("ANIM", $offset, $data);
    }

    public static function forBytes(int $offset, string $bytes): self
    {
        return new Anim($offset, $bytes);
    }
}
