<?php

declare(strict_types=1);

namespace WoltLab\WebpExif\Exception;

use RuntimeException;

/** @internal */
final class ExtraChunksInSimpleFormat extends RuntimeException
{
    /** @param string[] $chunkNames */
    public function __construct(string $fourCC, array $chunkNames)
    {
        $names = \implode(', ', $chunkNames);
        parent::__construct("The file was recognized as simple {$fourCC} but contains extra chunks: {$names}");
    }
}
