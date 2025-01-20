<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Nelexa\Buffer\Buffer;
use RuntimeException;

final class Vp8l extends Chunk
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        string $data,
    ) {
        parent::__construct("VP8L", $data);
    }

    public static function fromBuffer(Buffer $buffer): self
    {
        $length = $buffer->getUnsignedInt();
        $startOfData = $buffer->position();

        $signature = $buffer->getUnsignedByte();
        if ($signature !== 0x2F) {
            throw new RuntimeException("TODO: invalid signature for lossless");
        }

        $header = $buffer->getUnsignedInt();

        // The header contains the following data:
        // 0-13: width - 1
        // 14-27: height - 1
        // 28: alpha_is_used
        // 29-31: version (must be 0)
        $version = $header >> 29;
        if ($version !== 0) {
            throw new RuntimeException("Expected the version to be 0, found {$version} instead");
        }

        $width = ($header & 0x3FFF) + 1;
        $height = (($header >> 14) & 0x3FFF) + 1;

        return new Vp8l(
            $width,
            $height,
            $buffer->setPosition($startOfData)->getString($length)
        );
    }
}
