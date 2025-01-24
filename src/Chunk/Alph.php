<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Alph extends Chunk
{
    private function __construct(int $offset, string $data)
    {
        parent::__construct("ALPH", $offset, $data);
    }

    public static function forBytes(int $offset, string $bytes): self
    {
        return new Alph($offset, $bytes);
    }
}
