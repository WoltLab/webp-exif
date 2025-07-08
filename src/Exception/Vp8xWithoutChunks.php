<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class Vp8xWithoutChunks extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("The file uses the extended WebP format but does not provide any other chunks");
    }
}
