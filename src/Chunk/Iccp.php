<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk;

final class Iccp extends Chunk
{
    private function __construct(int $offset, string $data)
    {
        parent::__construct("ICCP", $offset, $data);
    }

    public static function forBytes(int $offset, string $bytes): self
    {
        return new Iccp($offset, $bytes);
    }
}
