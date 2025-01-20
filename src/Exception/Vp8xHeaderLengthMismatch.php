<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use OutOfBoundsException;

/** @internal */
final class Vp8xHeaderLengthMismatch extends OutOfBoundsException
{
    public function __construct(int $expected, int $found)
    {
        parent::__construct("The length of the VP8X header was expected to be {$expected} but found {$found}");
    }
}
