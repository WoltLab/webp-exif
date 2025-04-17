<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk\Exception;

use RuntimeException;

final class MissingExifExtension extends RuntimeException {
    public function __construct()
    {
        parent::__construct("The `php_exif` extension is required to parse EXIF data");
    }
}
