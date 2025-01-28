<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class FileSizeMismatch extends RuntimeException
{
    public function __construct(int $expected, int $found)
    {
        parent::__construct("The file reports a payload of {$expected} bytes, but actually contains {$found} bytes");
    }
}
