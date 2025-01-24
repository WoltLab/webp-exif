<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

final class Anmf extends Chunk
{
    private function __construct(int $offset, string $data)
    {
        parent::__construct("ANMF", $offset, $data);
    }

    public static function forBytes(int $offset, string $bytes): self
    {
        return new Anmf($offset, $bytes);
    }
}
