<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Exception;

use OutOfBoundsException;

/** @internal */
final class Vp8xMissingImageData extends OutOfBoundsException
{
    public function __construct(bool $stillImage)
    {
        if ($stillImage) {
            $message = "The file did not contain any VP8 or VP8L chunks";
        } else {
            $message = "The file did not contain multiple ANMF chunks";
        }

        parent::__construct($message);
    }
}
