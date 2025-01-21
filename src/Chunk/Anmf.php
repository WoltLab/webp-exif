<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Anmf extends Chunk
{
    private function __construct(string $data)
    {
        parent::__construct("ANMF", $data);
    }

    public static function forBytes(string $bytes): self
    {
        return new Anmf($bytes);
    }
}
