<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Nelexa\Buffer\Buffer;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;

final class Vp8x extends Chunk
{
    private function __construct(
        public readonly int $width,
        public readonly int $height
    ) {
        parent::__construct(
            "VP8X",
            // VP8X only contains the header because the actual payload are the
            // chunks that follow afterwards.
            ""
        );
    }

    public static function fromBuffer(Buffer $buffer): self
    {
        // The next 4 bytes represent the length of the VP8X header which must
        // be 10 bytes long.
        $expectedHeaderLength = 10;
        $length = $buffer->getUnsignedInt();
        if ($length !== $expectedHeaderLength) {
            throw new Vp8xHeaderLengthMismatch($expectedHeaderLength, $length);
        }

        // The following 4 bytes contain a single byte containing a bitmask for
        // the contained features (which we can safely ignore at this point),
        // followed by 24 reserved bits.
        $buffer->skip(4);

        // The width of the canvas is represented as a uint24LE but minus one,
        // therefore we have to add 1 when decoding the value.
        $width = self::decodeDimension($buffer);

        // The height follows the same rules as the width.
        $height = self::decodeDimension($buffer);

        return new Vp8x($width, $height);
    }

    private static function decodeDimension(Buffer $buffer): int
    {
        $a = $buffer->getUnsignedByte();
        $b = $buffer->getUnsignedByte();
        $c = $buffer->getUnsignedByte();

        return $a + ($b << 8) + ($c << 16) + 1;
    }
}
