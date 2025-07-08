<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

/** @internal */
final class MissingMagicByte extends RuntimeException
{
    public function __construct(string $fourCC)
    {
        parent::__construct("The data for `{$fourCC}` is missing the magic byte");
    }
}
