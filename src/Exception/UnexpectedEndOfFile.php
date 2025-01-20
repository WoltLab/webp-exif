<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class UnexpectedEndOfFile extends RuntimeException
{
    public function __construct(int $offset, int $remainingBytes)
    {
        parent::__construct("Expected more data after offset {$offset} ({$remainingBytes} bytes remaining)");
    }
}
