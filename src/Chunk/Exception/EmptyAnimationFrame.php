<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

/** @internal */
final class EmptyAnimationFrame extends RuntimeException
{
    public function __construct(int $offset)
    {
        $offset = \dechex($offset);
        parent::__construct("The ANMF frame at offset 0x{$offset} contains no chunks");
    }
}
