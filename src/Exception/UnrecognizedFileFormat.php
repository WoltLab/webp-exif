<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class UnrecognizedFileFormat extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("The provided source appears not be a WebP image");
    }
}
