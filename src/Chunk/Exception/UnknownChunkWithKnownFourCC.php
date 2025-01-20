<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk\Exception;

use RuntimeException;
use Woltlab\WebpExif\Chunk\UnknownChunk;

/** @internal */
final class UnknownChunkWithKnownFourCC extends RuntimeException
{
    public function __construct(string $fourCC)
    {
        parent::__construct("The FourCC code `{$fourCC}` is well-known and must not be used with " . UnknownChunk::class);
    }
}
