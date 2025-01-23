<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use OutOfBoundsException;

/** @internal */
final class ExtraVp8xChunk extends OutOfBoundsException
{
    public function __construct()
    {
        parent::__construct("An extended WebP image format may only contain a single VP8X chunk");
    }
}
