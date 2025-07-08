<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Chunk\Exception;

use RuntimeException;

/** @internal */
final class AnimationFrameWithoutBitstream extends RuntimeException
{
    public function __construct(int $offset)
    {
        $offset = \dechex($offset);
        parent::__construct("The ANMF frame at offset 0x{$offset} does not contain a bitstream chunk");
    }
}
