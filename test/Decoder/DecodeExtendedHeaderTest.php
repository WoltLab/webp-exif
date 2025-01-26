<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;
use Woltlab\WebpExif\Exception\ExtraVp8xChunk;
use Woltlab\WebpExif\Exception\LengthOutOfBounds;
use Woltlab\WebpExif\Exception\UnexpectedEndOfFile;
use Woltlab\WebpExif\Exception\Vp8xMissingImageData;
use Woltlab\WebpExif\Exception\Vp8xWithoutChunks;

final class DecodeExtendedHeaderTest extends TestCase
{
    public function testRejectNestedVp8x(): void
    {
        $this->expectException(ExtraVp8xChunk::class);

        $decoder = new Decoder();
        $chunks = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            "VP8X\x0A\x00\x00\x00" . str_repeat("\x00", 10),
        ];
        $decoder->fromBinary($this->generateVp8x(chunks: $chunks));
    }

    public function testRejectEmptyVp8x(): void
    {
        $this->expectExceptionObject(new Vp8xWithoutChunks());

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
        int $width = 1_234,
        int $height = 2_345,
        array $chunks = []
    ): string {
        $iccProfile = \array_find($chunks, static fn($chunk) => \str_starts_with($chunk, "ICCP")) !== null;
        $alpha      = \array_find($chunks, static fn($chunk) => \str_starts_with($chunk, "ALPH")) !== null;
        $exif       = \array_find($chunks, static fn($chunk) => \str_starts_with($chunk, "EXIF")) !== null;
        $xmp        = \array_find($chunks, static fn($chunk) => \str_starts_with($chunk, "XMP ")) !== null;
        $animation  = \array_find($chunks, static fn($chunk) => \str_starts_with($chunk, "ANIM")) !== null;

        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        $buffer->insertString("RIFF");

        // The uint32 for the byte length of the `WEBP` chunk will be inserted
        // here in the last step.

        $buffer->insertString("WEBP");
        $buffer->insertString("VP8X");
        $buffer->insertInt(10);

        // Feature flags, the first two bits and the last bit are reserved.
        $bitField = 0;
        $bitField |= (int)$iccProfile << 5;
        $bitField |= (int)$alpha      << 4;
        $bitField |= (int)$exif       << 3;
        $bitField |= (int)$xmp        << 2;
        $bitField |= (int)$animation  << 1;
        $buffer->insertInt($bitField);

        // Encode the width and height as a 3 byte value each.
        $width = ($width - 1) & 0x00FFFFFF;
        $buffer->insertByte(($width >>  0) & 0xFF);
        $buffer->insertByte(($width >>  8) & 0xFF);
        $buffer->insertByte(($width >> 16) & 0xFF);

        $height = ($height - 1) & 0x00FFFFFF;
        $buffer->insertByte(($height >>  0) & 0xFF);
        $buffer->insertByte(($height >>  8) & 0xFF);
        $buffer->insertByte(($height >> 16) & 0xFF);

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
