<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\ExpectedKeyFrame;
use Woltlab\WebpExif\Chunk\Exception\MissingMagicByte;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\ChunkType;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;

final class Vp8Test extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8 = $this->getBufferFor("\x0A\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        $chunk = Vp8::fromBuffer($pseudoValidVp8);
        self::assertSame(
            ChunkType::VP8,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testLengthOfPayloadExceedsEof(): void
    {
        $this->expectExceptionObject(new LengthOutOfBounds(11, 4, 10));

        $lengthExceedsEof = $this->getBufferFor("\x0B\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        Vp8::fromBuffer($lengthExceedsEof);
    }

    public function testUnexpectedFrame(): void
    {
        $this->expectException(ExpectedKeyFrame::class);

        $interframeFirst = $this->getBufferFor("\x0A\x00\x00\x00\x01\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        Vp8::fromBuffer($interframeFirst);
    }

    public function testMissingMagicByte(): void
    {
        $this->expectException(MissingMagicByte::class);

        $missingMagicByte = $this->getBufferFor("\x0A\x00\x00\x00\x00\x00\x00\x9D\xFF\x2A\xFF\xFF\xFF\xFF");
        Vp8::fromBuffer($missingMagicByte);
    }

    public function testReportCorrectDimensions(): void
    {
        $width = 38;
        $height = 10_000;

        $buffer = $this->getBufferFor(
            "\x0a\x00\x00\x00\x00\x00\x00\x9D\x01\x2A"
                . $this->encodeDimensions($width)
                . $this->encodeDimensions($height)
        );
        $vp8 = Vp8::fromBuffer($buffer);

        self::assertEquals($width, $vp8->width);
        self::assertEquals($height, $vp8->height);
    }

    private function encodeDimensions(int $dimension): string
    {
        // Technically we must encode this as a 14-bit integer but since we
        // don't validate the scale in the first 2 bits, we can simply be lazy
        // and just use an uint16 instead.
        return \pack('v', $dimension);
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
