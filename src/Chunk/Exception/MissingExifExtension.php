<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

final class MissingExifExtension extends RuntimeException {
    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct("The `php_exif` extension is required to parse EXIF data");
    }
}
