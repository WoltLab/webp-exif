<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Encoder;
use Woltlab\WebpExif\WebP;

final class EncoderTest extends TestCase
{
    public function testEncodeSimpleVp8l(): void {
        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);
        $buffer->insertString("\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B");
        $buffer->setReadOnly(true);
        $buffer->setPosition(0);

        $vp8l = Vp8l::fromBuffer($buffer);
        $webp = WebP::fromChunks([$vp8l]);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        self::assertEquals(
            "RIFF\x1A\x00\x00\x00WEBPVP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            $bytes,
        );
    }
}
