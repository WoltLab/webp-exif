<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\UnexpectedEndOfFile;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;
use Woltlab\WebpExif\Exception\Vp8xMissingImageData;

final class DecodeExtendedHeaderTest extends TestCase
{
    public function testHeaderLengthMismatch(): void
    {
        $headerLength = 20;
        $this->expectExceptionObject(new Vp8xHeaderLengthMismatch(10, $headerLength));

        $decoder = new Decoder();
        $decoder->fromBinary($this->generateVp8x(headerLength: $headerLength));
    }

    public function testRejectNestedVp8x(): void
    {
        $this->expectExceptionObject(new UnexpectedChunk("VP8X", 34));

        $decoder = new Decoder();
        $chunks = [
            "VP8X\x0A\x00\x00\x00" . str_repeat("\x00", 10),
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    public function testRejectEmptyVp8x(): void
    {
        $this->expectExceptionObject(new UnexpectedEndOfFile(30, 0));

        $decoder = new Decoder();
        $decoder->fromBinary($this->generateVp8x());
    }

    public function testIncompleteChunkHeader(): void
    {
        $this->expectExceptionObject(new UnexpectedEndOfFile(30, 4));

        $decoder = new Decoder();
        $chunks = [
            "EXIF",
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    public function testBadChunkLength(): void
    {
        $this->expectExceptionObject(new LengthOutOfBounds(1, 34, 0));

        $decoder = new Decoder();
        $chunks = [
            "EXIF\x01\x00\x00\x00",
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    public function testRejectMissingImageDataForStillImage(): void
    {
        $this->expectExceptionObject(new Vp8xMissingImageData(stillImage: true));

        $decoder = new Decoder();
        $chunks = [
            "EXIF\x00\x00\x00\x00",
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    public function testRejectMissingImageDataForAnimatedImage(): void
    {
        $this->expectExceptionObject(new Vp8xMissingImageData(stillImage: false));

        $decoder = new Decoder();
        $chunks = [
            "ANIM\x00\x00\x00\x00",
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    /**
     * @param list<string> $chunks
     */
    private function generateVp8x(
        int $headerLength = 10,
        int $width = 1_337,
        int $height = 1_337,
        array $chunks = []
    ): string {
        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        $buffer->insertString("RIFF");

        // The uint32 for the byte length of the `WEBP` chunk will be inserted
        // here in the last step.

        $buffer->insertString("WEBP");
        $buffer->insertString("VP8X");
        $buffer->insertInt($headerLength);
        // We don't care for the flags.
        $buffer->insertInt(0);

        // Encode the width and height as a 3 byte value each.
        $width = ($width - 1) & 0x00FFFFFF;
        $buffer->insertByte($width >> 16);
        $buffer->insertByte(($width >> 8) & 0x00FF);
        $buffer->insertByte($width & 0xFF);

        $height = ($height - 1) & 0x00FFFFFF;
        $buffer->insertByte($height >> 16);
        $buffer->insertByte(($height >> 8) & 0x00FF);
        $buffer->insertByte($height & 0xFF);

        foreach ($chunks as $chunk) {
            $paddingByte = (strlen($chunk) % 2 === 1) ? "\x00" : "";
            $buffer->insertString($chunk . $paddingByte);
        }

        // Insert the length of the WebP chunk.
        $buffer->setPosition(4);
        $buffer->insertInt($buffer->size() - 4);

        return $buffer->toString();
    }
}
