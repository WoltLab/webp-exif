<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WoltLab\WebpExif\Chunk\Vp8;
use WoltLab\WebpExif\Decoder;
use WoltLab\WebpExif\Encoder;
use WoltLab\WebpExif\WebP;
use WoltLabTest\WebpExif\Helper\ChunkGenerator;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 */
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
            "RIFF\x14\x00\x00\x00WEBPVP8 \x08\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF",
            $bytes,
        );
    }

    public function testEncodeSimpleVp8WithUnevenBitstreamLength(): void
    {
        $buffer = new StringBuffer("\x09\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF\x00");
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);
        $buffer->setReadOnly(true);

        $vp8 = Vp8::fromBuffer($buffer);
        $webp = WebP::fromChunks([$vp8]);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        self::assertEquals(
            "RIFF\x15\x00\x00\x00WEBPVP8 \x09\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\x00",
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
            "RIFF\x12\x00\x00\x00WEBPVP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
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

        $header = "RIFF\x30\x00\x00\x00WEBP";
        $vp8x = "VP8X\x0A\x00\x00\x00\x08\x00\x00\x00\x41\x2C\x00\xBD\x01\x00";
        $bitstream = "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B";
        $exifChunk = "EXIF\x04\x00\x00\x00" . $exif->getRawBytes();

        self::assertEquals(
            $header . $vp8x . $bitstream . $exifChunk,
            $bytes,
        );
    }

    public function testEncodeWithXmp(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        $xmp = $generator->xmp(bytes: "\xDE\xAD\xBE\xEF");
        $webp = $webp->withXmp($xmp);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        $header = "RIFF\x30\x00\x00\x00WEBP";
        $vp8x = "VP8X\x0A\x00\x00\x00\x04\x00\x00\x00\x41\x2C\x00\xBD\x01\x00";
        $bitstream = "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B";
        $xmpChunk = "XMP \x04\x00\x00\x00" . $xmp->getRawBytes();

        self::assertEquals(
            $header . $vp8x . $bitstream . $xmpChunk,
            $bytes,
        );
    }

    public function testEncodeWithIccp(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        $iccp = $generator->iccp(bytes: "\xDE\xAD\xBE\xEF");
        $webp = $webp->withIccp($iccp);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        $header = "RIFF\x30\x00\x00\x00WEBP";
        $vp8x = "VP8X\x0A\x00\x00\x00\x20\x00\x00\x00\x41\x2C\x00\xBD\x01\x00";
        $bitstream = "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B";
        $iccpChunk = "ICCP\x04\x00\x00\x00" . $iccp->getRawBytes();

        self::assertEquals(
            $header . $vp8x . $iccpChunk . $bitstream,
            $bytes,
        );
    }

    public function testEncodeWithUnknownChunk(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        $void = $generator->unknownChunk("VOID");
        $webp = $webp->withUnknownChunks([$void]);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        $header = "RIFF\x30\x00\x00\x00WEBP";
        $vp8x = "VP8X\x0A\x00\x00\x00\x00\x00\x00\x00\x41\x2C\x00\xBD\x01\x00";
        $bitstream = "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B";
        $voidChunk = "VOID\x04\x00\x00\x00" . $void->getRawBytes();

        self::assertEquals(
            $header . $vp8x . $bitstream . $voidChunk,
            $bytes,
        );
    }

    #[DataProvider('pathnameProvider')]
    public function testEncodedResultMatchesAsset(string $pathname): void
    {
        $binary = file_get_contents($pathname);
        assert($binary !== false);

        $decoder = new Decoder();
        $webp = $decoder->fromBinary($binary);

        $encoder = new Encoder();
        $bytes = $encoder->fromWebP($webp);

        self::assertEquals($binary, $bytes);
    }

    public static function pathnameProvider(): Generator
    {
        $files = glob("./test/TestAsset/*.webp");
        assert($files !== false);

        foreach ($files as $file) {
            yield [$file];
        }
    }
}
