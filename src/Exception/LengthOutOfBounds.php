<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use OutOfBoundsException;

/** @internal */
final class LengthOutOfBounds extends OutOfBoundsException
{
    public function __construct(int $length, int $offset, int $remainingBytes)
    {
        $offset = \dechex($offset);

        parent::__construct("Found the length {$length} at offset 0x{$offset} but there are only {$remainingBytes} bytes remaining");
    }
}
