<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Nelexa\Buffer\Buffer;
use Woltlab\WebpExif\Chunk\Exception\DimensionsExceedInt32;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;

final class Vp8x extends Chunk
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        int $offset,
        public readonly bool $iccProfile,
        public readonly bool $alpha,
        public readonly bool $exif,
        public readonly bool $xmp,
        public readonly bool $animation,
    ) {
        parent::__construct(
            "VP8X",
            $offset,
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

        $startOfData = $buffer->position();

        // The next byte contains a bit field for the features of this image,
        // the first two bits and the last bit are reserved and MUST be ignored.
        $bitField = $buffer->getByte();
        $iccProfile = ($bitField & 0b00100000) === 32;
        $alpha      = ($bitField & 0b00010000) === 16;
        $exif       = ($bitField & 0b00001000) ===  8;
        $xmp        = ($bitField & 0b00000100) ===  4;
        $animation  = ($bitField & 0b00000010) ===  2;

        // The next 24 bits are reserved.
        $buffer->skip(3);

        // The width of the canvas is represented as a uint24LE but minus one,
        // therefore we have to add 1 when decoding the value.
        $width = self::decodeDimension($buffer);

        // The height follows the same rules as the width.
        $height = self::decodeDimension($buffer);

        // The product of `width` and `height` must not exceed 2^31 - 1, the
        // maximum value of int32. We cannot assume that PHP is a 64 bit build
        // therefore we calculate the largest possible value for `$height` that
        // would not exceed the maximum value. This approach avoids hitting an
        // integer overflow on 32 bit builds when calculating the product.
        $maxInt32 = 2_147_483_647;
        $maximumHeight = $maxInt32 / $width;
        if ($maximumHeight < $height) {
            throw new DimensionsExceedInt32($width, $height);
        }

        return new Vp8x(
            $width,
            $height,
            $startOfData - 8,
            $iccProfile,
            $alpha,
            $exif,
            $xmp,
            $animation
        );
    }

    private static function decodeDimension(Buffer $buffer): int
    {
        $a = $buffer->getUnsignedByte();
        $b = $buffer->getUnsignedByte();
        $c = $buffer->getUnsignedByte();

        return ($a | ($b << 8) | ($c << 16)) + 1;
    }
}
