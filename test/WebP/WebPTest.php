<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use WoltLab\WebpExif\Chunk\Anmf;
use WoltLab\WebpExif\Exception\ExtraChunksInSimpleFormat;
use WoltLab\WebpExif\Exception\MissingChunks;
use WoltLab\WebpExif\WebP;
use WoltLabTest\WebpExif\Helper\ChunkGenerator;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 */
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

    public function testRemoveXmp(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $xmp = $generator->xmp();
        $vp8x = $generator->vp8x(width: $vp8l->width, height: $vp8l->height, xmp: true);
        $webp = WebP::fromChunks([$vp8x, $vp8l, $xmp]);

        self::assertEquals($xmp, $webp->getXmp());

        $webp = $webp->withXmp(null);

        self::assertEquals(null, $webp->getXmp());
    }

    public function testReplaceXmp(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $xmp = $generator->xmp();
        $vp8x = $generator->vp8x(width: $vp8l->width, height: $vp8l->height, xmp: true);
        $webp = WebP::fromChunks([$vp8x, $vp8l, $xmp]);

        self::assertEquals($xmp, $webp->getXmp());

        $bytes = "\xDE\xAD\xC0\xDE";
        $xmp = $generator->xmp(bytes: $bytes);
        $webp = $webp->withXmp($xmp);

        self::assertEquals($bytes, $webp->getXmp()?->getRawBytes());
    }

    public function testAddXmp(): void
    {
        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8l]);

        self::assertEquals(null, $webp->getXmp());

        $xmp = $generator->xmp();
        $webp = $webp->withXmp($xmp);

        self::assertEquals($xmp, $webp->getXmp());
    }

    public function testRejectsZeroChunks(): void
    {
        $this->expectException(MissingChunks::class);

        WebP::fromChunks([]);
    }

    public function testRejectsExtraChunksInVp8l(): void
    {
        $this->expectExceptionObject(new ExtraChunksInSimpleFormat("VP8L", ["EXIF"]));

        $generator = new ChunkGenerator();
        $vp8l = $generator->vp8l();
        $exif = $generator->exif();
        WebP::fromChunks([$vp8l, $exif]);
    }

    public function testRejectsCreationFromAnythingButChunks(): void
    {
        $this->expectExceptionObject(new BadMethodCallException("Expected a list of WoltLab\WebpExif\Chunk\Chunk, received string instead"));

        // @phpstan-ignore argument.type
        WebP::fromChunks(["hello", "world"]);
    }

    public function testRejectsArbitraryDataForUnknownChunks(): void
    {
        $generator = new ChunkGenerator();
        $vp8x = $generator->vp8x();
        $vp8l = $generator->vp8l();
        $webp = WebP::fromChunks([$vp8x, $vp8l]);

        $exif = $generator->exif();

        $this->expectExceptionObject(new BadMethodCallException("Expected a list of WoltLab\WebpExif\Chunk\UnknownChunk, received WoltLab\WebpExif\Chunk\Exif instead"));

        // @phpstan-ignore argument.type
        $webp->withUnknownChunks([$exif]);
    }

    public function testSkipsBitstreamWhenAnimationIsPresent(): void
    {
        $frameData = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
        ];

        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        // The uint32 length will be inserted here in the last step.

        $buffer->insertString(str_repeat("\x00", 16));

        foreach ($frameData as $anmf) {
            $buffer->insertString($anmf);
            if (strlen($anmf) % 2 === 1) {
                $buffer->insertByte(0);
            }
        }

        $buffer->setPosition(0)
            ->insertInt($buffer->size())
            ->setPosition(0);


        $anmf = Anmf::fromBuffer($buffer);

        $generator = new ChunkGenerator();
        $vp8x = $generator->vp8x(animation: true);
        $anim = $generator->anim();
        $webp = WebP::fromChunks([$vp8x, $anim, $anmf, $anmf]);

        self::assertNotNull($webp->getAnimation());
        self::assertNull($webp->getBitstream());
    }
}
