<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use WoltLab\WebpExif\Chunk\Anmf;
use WoltLab\WebpExif\Chunk\Exception\DimensionsExceedInt32;
use WoltLab\WebpExif\Chunk\Vp8x;
use WoltLab\WebpExif\ChunkType;
use WoltLab\WebpExif\Exception\UnexpectedChunk;
use WoltLab\WebpExif\Exception\Vp8xAbsentChunk;
use WoltLab\WebpExif\Exception\Vp8xHeaderLengthMismatch;
use WoltLabTest\WebpExif\Helper\ChunkGenerator;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 */
final class Vp8xTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $pseudoValidVp8x = $this->getBufferFor("\x0A\x00\x00\x00" . str_repeat("\x00", 10));
        $chunk = Vp8x::fromBuffer($pseudoValidVp8x);
        self::assertSame(
            ChunkType::VP8X,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testLengthMismatch(): void
    {
        $this->expectExceptionObject(new Vp8xHeaderLengthMismatch(10, 12));

        $pseudoValidVp8x = $this->getBufferFor("\x0C\x00\x00\x00" . str_repeat("\x00", 12));
        Vp8x::fromBuffer($pseudoValidVp8x);
    }

    public function testExcessiveWidth(): void
    {
        $width = 16_777_215; // max uint24
        $height = 1200;

        $this->expectExceptionObject(new DimensionsExceedInt32($width, $height));

        Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));
    }

    public function testExcessiveHeight(): void
    {
        $width = 1200;
        $height = 16_777_215; // max uint24

        $this->expectExceptionObject(new DimensionsExceedInt32($width, $height));

        Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));
    }

    public function testDecodeDimensionsMatchRawValues(): void
    {
        $width = 33;
        $height = 66;
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(width: $width, height: $height));

        self::assertEquals($vp8x->width, $width);
        self::assertEquals($vp8x->height, $height);
    }

    public function testReportsNoSetFlags(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x());
        $this->validateFlags($vp8x);
    }

    public function testReportsIccpProfileFlag(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(iccProfile: true));
        $this->validateFlags($vp8x, iccProfile: true);
    }

    public function testReportsAlphaFlag(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(alpha: true));
        $this->validateFlags($vp8x, alpha: true);
    }

    public function testReportsExifFlag(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(exif: true));
        $this->validateFlags($vp8x, exif: true);
    }

    public function testReportsXmpFlag(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(xmp: true));
        $this->validateFlags($vp8x, xmp: true);
    }

    public function testReportsAnimationFlag(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(animation: true));
        $this->validateFlags($vp8x, animation: true);
    }

    public function testReportsAllFlags(): void
    {
        $vp8x = Vp8x::fromBuffer($this->generateVp8x(iccProfile: true, alpha: true, exif: true, xmp: true, animation: true));
        $this->validateFlags($vp8x, iccProfile: true, alpha: true, exif: true, xmp: true, animation: true);
    }

    public function testReportsMissingIccProfile(): void
    {
        $this->expectExceptionObject(new Vp8xAbsentChunk('ICCP'));

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(iccProfile: true));

        $chunkGenerator = new ChunkGenerator();
        $vp8x->filterChunks([$chunkGenerator->exif()]);
    }

    public function testReportsMissingAlpha(): void
    {
        $this->expectExceptionObject(new Vp8xAbsentChunk('ALPH'));

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(alpha: true));

        $chunkGenerator = new ChunkGenerator();
        $vp8x->filterChunks([$chunkGenerator->exif()]);
    }

    public function testReportsMissingExif(): void
    {
        $this->expectExceptionObject(new Vp8xAbsentChunk('EXIF'));

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(exif: true));

        $chunkGenerator = new ChunkGenerator();
        $vp8x->filterChunks([$chunkGenerator->xmp()]);
    }

    public function testReportsMissingXmp(): void
    {
        $this->expectExceptionObject(new Vp8xAbsentChunk('XMP '));

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(xmp: true));

        $chunkGenerator = new ChunkGenerator();
        $vp8x->filterChunks([$chunkGenerator->exif()]);
    }

    public function testRejectsAnimationMixedWithBitstreamChunk(): void
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

        $chunkGenerator = new ChunkGenerator();
        $anim = $chunkGenerator->anim();
        $vp8l = $chunkGenerator->vp8l();

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(animation: true));

        $this->expectExceptionObject(new UnexpectedChunk("VP8L", -4));

        $vp8x->filterChunks([$anim, $anmf, $anmf, $vp8l]);
    }

    public function testRejectsBitstreamChunkMixedWithAnimationFrame(): void
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

        $chunkGenerator = new ChunkGenerator();
        $vp8l = $chunkGenerator->vp8l();

        $vp8x = Vp8x::fromBuffer($this->generateVp8x());

        $this->expectExceptionObject(new UnexpectedChunk("ANMF", 0));

        $vp8x->filterChunks([$anmf, $vp8l]);
    }

    public function testRemovalOfDuplicateChunks(): void
    {
        $chunkGenerator = new ChunkGenerator();
        $iccp = $chunkGenerator->iccp();
        $vp8l = $chunkGenerator->vp8l();

        $vp8x = Vp8x::fromBuffer($this->generateVp8x(iccProfile: true));
        $chunks = $vp8x->filterChunks([$iccp, $iccp, $vp8l]);

        $filteredChunksAsFourCC = \array_map(
            static fn($chunk) => $chunk->getFourCC(),
            $chunks,
        );

        self::assertEquals(["ICCP", "VP8L"], $filteredChunksAsFourCC);
    }

    private function validateFlags(
        Vp8x $vp8x,
        bool $iccProfile = false,
        bool $alpha = false,
        bool $exif = false,
        bool $xmp = false,
        bool $animation = false,
    ): void {
        self::assertEquals([
            $iccProfile,
            $alpha,
            $exif,
            $xmp,
            $animation,
        ], [
            $vp8x->iccProfile,
            $vp8x->alpha,
            $vp8x->exif,
            $vp8x->xmp,
            $vp8x->animation,
        ]);
    }

    private function generateVp8x(
        int $headerLength = 10,
        int $width = 1_234,
        int $height = 2_345,
        bool $iccProfile = false,
        bool $alpha = false,
        bool $exif = false,
        bool $xmp = false,
        bool $animation = false,
    ): Buffer {
        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        $buffer->insertInt($headerLength);

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

        return $this->getBufferFor(
            $buffer->toString()
        );
    }

    private function getBufferFor(string $bytes): Buffer
    {
        $buffer = new StringBuffer($bytes);
        $buffer->setReadOnly(true);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
