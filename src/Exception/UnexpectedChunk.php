<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class UnexpectedChunk extends RuntimeException
{
    public function __construct(string $fourCC, int $offset)
    {
        $offset = \dechex($offset);
        parent::__construct("Found the unexpected chunk `{$fourCC}` at offset 0x{$offset}");
    }
}
