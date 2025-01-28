<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\WebP;
use WoltlabTest\WebpExif\Helper\ChunkGenerator;

final class WebPTest extends TestCase
{
    public function testRemoveExif(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $exif = $generator->exif();
        $vp8x = $generator->vp8x(width: $vp8l->width, height: $vp8l->height, exif: true);
        $webp = WebP::fromChunks([$vp8x, $vp8l, $exif]);

        self::assertEquals($exif, $webp->getExif());

        $webp = $webp->withExif(null);

        self::assertEquals(null, $webp->getExif());
    }

    public function testReplaceExif(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $exif = $generator->exif();
        $vp8x = $generator->vp8x(width: $vp8l->width, height: $vp8l->height, exif: true);
        $webp = WebP::fromChunks([$vp8x, $vp8l, $exif]);

        self::assertEquals($exif, $webp->getExif());

        $bytes = "\xDE\xAD\xC0\xDE";
        $exif = $generator->exif(bytes: $bytes);
        $webp = $webp->withExif($exif);

        self::assertEquals($bytes, $webp->getExif()?->getRawBytes());
    }

    public function testAddExif(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        self::assertEquals(null, $webp->getExif());

        $exif = $generator->exif();
        $webp = $webp->withExif($exif);

        self::assertEquals($exif, $webp->getExif());
    }
}
