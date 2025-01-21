<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;
use Woltlab\WebpExif\Exception\DataAfterLastChunk;
use Woltlab\WebpExif\Exception\FileSizeMismatch;
use Woltlab\WebpExif\Exception\NotEnoughData;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\UnrecognizedFileFormat;

final class DecoderTest extends TestCase
{
    public function testFileSizeTooShort(): void
    {
        $this->expectException(NotEnoughData::class);

        $decoder = new Decoder();
        $decoder->fromBinary("\xFF\xFF");
    }

    public function testUnrecognizedFileFormat(): void
    {
        $this->expectException(UnrecognizedFileFormat::class);

        $decoder = new Decoder();
        $decoder->fromBinary(str_repeat("\x00", 30));
    }

    public function testFileSizeMismatch(): void
    {
        $this->expectExceptionObject(new FileSizeMismatch(16, 18));

        $decoder = new Decoder();
        $decoder->fromBinary("RIFF\x10\x00\x00\x00WEBPVP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B");
    }

    public function testDataAfterSimpleLossy(): void
    {
        $this->expectException(DataAfterLastChunk::class);

        $decoder = new Decoder();
        $decoder->fromBinary("RIFF\x16\x00\x00\x00WEBPVP8 \x06\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\x00\x00");
    }

    public function testDataAfterSimpleLossless(): void
    {
        $this->expectException(DataAfterLastChunk::class);

        $decoder = new Decoder();
        $decoder->fromBinary("RIFF\x14\x00\x00\x00WEBPVP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B\x00\x00");
    }

    public function testUnexpectedChunkAtFirstPosition(): void
    {
        $this->expectExceptionObject(new UnexpectedChunk("EXIF", 12));

        $decoder = new Decoder();
        $decoder->fromBinary("RIFF\x16\x00\x00\x00WEBPEXIF\x0A\x00\x00\x00" . str_repeat("\x00", 10));
    }
}
