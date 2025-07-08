<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

/** @internal */
final class ExpectedKeyFrame extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Expected a keyframe to be the first frame");
    }
}
