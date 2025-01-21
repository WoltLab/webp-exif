<?php

declare(strict_types=1);

namespace Woltlab\WebpExif\Chunk;

use Nelexa\Buffer\Buffer;
use Woltlab\WebpExif\Chunk\Exception\ExpectedKeyFrame;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;

final class Vp8 extends Chunk
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        string $data,
    ) {
        parent::__construct("VP8 ", $data);
    }

    public static function fromBuffer(Buffer $buffer): self
    {
        $length = $buffer->getUnsignedInt();
        $startOfData = $buffer->position();
        if ($length > $buffer->remaining()) {
            throw new LengthOutOfBounds($length, $buffer->position(), $buffer->remaining());
        }

        $tag = $buffer->getUnsignedByte();

        // We expect the first frame to be a keyframe.
        $frameType = $tag & 1;
        if ($frameType !== 0) {
            throw new ExpectedKeyFrame();
        }

        // Skip the next two bytes, they are part of the header but do not
        // contain any information that is relevant to us.
        $buffer->skip(2);

        // Keyframes must start with 3 magic bytes.
        $marker = $buffer->getString(3);
        if ($marker !== "\x9D\x01\x2A") {
            throw new MissingMagicByte("VP8");
        }

        // The width and height are encoded using 2 bytes each. However, the
        // first two bits are the scale followed by 14 bits for the dimension.
        $width = $buffer->getUnsignedShort() & 0x3FFF;
        $height = $buffer->getUnsignedShort() & 0x3FFF;

        return new Vp8(
            $width,
            $height,
            $buffer->setPosition($startOfData)->getString($length)
        );
    }
}
