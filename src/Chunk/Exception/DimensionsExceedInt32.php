<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk\Exception;

use OutOfRangeException;

/** @internal */
final class DimensionsExceedInt32 extends OutOfRangeException
{
    public function __construct(int $width, int $height)
    {
        parent::__construct("The product of {$width} and {$height} exceeds the boundary of 2^31 - 1");
    }
}
