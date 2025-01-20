<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class NotEnoughData extends RuntimeException
{
    public function __construct(int $expected, int $found)
    {
        parent::__construct("The file size is expected to be at least {$expected} bytes but is only {$found} bytes long");
    }
}
