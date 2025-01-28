<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\Encoder;
use Woltlab\WebpExif\WebP;
use WoltlabTest\WebpExif\Helper\ChunkGenerator;

final class EncoderTest extends TestCase
{
    public function testEncodeSimpleVp8(): void
    {
        $generator = new ChunkGenerator();
        $vp8 = $generator->vp8();
        $webp = WebP::fromChunks([$vp8]);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        self::assertEquals(
            "RIFF\x1C\x00\x00\x00WEBPVP8 \x08\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF",
            $bytes,
        );
    }

    public function testEncodeSimpleVp8l(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        self::assertEquals(
            "RIFF\x1A\x00\x00\x00WEBPVP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            $bytes,
        );
    }

    public function testEncodeWithExif(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        $exif = $generator->exif(bytes: "\xDE\xAD\xBE\xEF");
        $webp = $webp->withExif($exif);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        $header = "RIFF\x2C\x00\x00\x00WEBP";
        $vp8x = "VP8X\x0A\x00\x00\x00\x08\x00\x00\x00\x41\x2C\x00\xBD\x01\x00";
        $bitstream = "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B";
        $exifChunk = "EXIF\x04\x00\x00\x00" . $exif->getRawBytes();

        self::assertEquals(
            $header . $vp8x . $bitstream . $exifChunk,
            $bytes,
        );
    }
}
