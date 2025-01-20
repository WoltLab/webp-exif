<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use OutOfBoundsException;

/** @internal */
final class LengthOutOfBounds extends OutOfBoundsException
{
    public function __construct(int $length, int $offset, int $remainingBytes)
    {
        parent::__construct("Found the length {$length} at offset {$offset} but there are only {$remainingBytes} bytes remaining");
    }
}
