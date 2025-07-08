<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 *
 * @internal
 */
final class ExpectedKeyFrame extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Expected a keyframe to be the first frame");
    }
}
