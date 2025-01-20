<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class DataAfterLastChunk extends RuntimeException
{
    public function __construct(int $remainingBytes)
    {
        parent::__construct("The file contains {$remainingBytes} extra bytes after the last chunk");
    }
}
