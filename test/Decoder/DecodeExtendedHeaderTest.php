<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\Vp8xHeaderLengthMismatch;

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
            $buffer->insertString($chunk);
        }

        // Insert the length of the WebP chunk.
        $buffer->setPosition(4);
        $buffer->insertInt($buffer->size() - 4);

        return $buffer->toString();
    }
}
