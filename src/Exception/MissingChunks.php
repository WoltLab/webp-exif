<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class MissingChunks extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("A WebP container must contain at least one data chunk");
    }
}
