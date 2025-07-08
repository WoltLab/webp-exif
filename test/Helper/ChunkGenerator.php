<?php

declare(strict_types=1);

namespace WoltLabTest\WebpExif\Helper;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use WoltLab\WebpExif\Chunk\Anim;
use WoltLab\WebpExif\Chunk\Exif;
use WoltLab\WebpExif\Chunk\Iccp;
use WoltLab\WebpExif\Chunk\UnknownChunk;
use WoltLab\WebpExif\Chunk\Vp8;
use WoltLab\WebpExif\Chunk\Vp8l;
use WoltLab\WebpExif\Chunk\Vp8x;
use WoltLab\WebpExif\Chunk\Xmp;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 */
final class ChunkGenerator
{
    public function anim(int $offset = 0, ?string $bytes = null): Anim
    {
        if ($bytes === null) {
            $bytes = "\xC0\xFF\xEE";
        }

        return Anim::forBytes($offset, $bytes);
    }

    public function exif(int $offset = 0, ?string $bytes = null): Exif
    {
        if ($bytes === null) {
            $bytes = "\xC0\xFF\xEE";
        }

        return Exif::forBytes($offset, $bytes);
    }

    public function iccp(int $offset = 0, ?string $bytes = null): Iccp
    {
        if ($bytes === null) {
            $bytes = "\xC0\xFF\xEE";
        }

        return Iccp::forBytes($offset, $bytes);
    }

    public function vp8(): Vp8
    {
        $buffer = $this->createBuffer("\x08\x00\x00\x00\x00\x00\x00\x9D\x01\x2A\xFF\xFF\xFF\xFF");
        $buffer->setReadOnly(true);

        return Vp8::fromBuffer($buffer);
    }

    public function vp8l(): Vp8l
    {
        $buffer = $this->createBuffer("\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B");
        $buffer->setReadOnly(true);

        return Vp8l::fromBuffer($buffer);
    }

    public function vp8x(
        int $offset = 0,
        int $width = 1_337,
        int $height = 2_664,
        bool $iccProfile = false,
        bool $alpha = false,
        bool $exif = false,
        bool $xmp = false,
        bool $animation = false,
    ): Vp8x {
        return Vp8x::fromParameters($offset, $width, $height, $iccProfile, $alpha, $exif, $xmp, $animation);
    }

    public function xmp(int $offset = 0, ?string $bytes = null): Xmp
    {
        if ($bytes === null) {
            $bytes = "\xDE\xAD\xBA\xBE";
        }

        return Xmp::forBytes($offset, $bytes);
    }

    public function unknownChunk(string $fourCC, int $offset = 0, ?string $bytes = null): UnknownChunk
    {
        if ($bytes === null) {
            $bytes = "\xDE\xAD\xBA\xBE";
        }

        return UnknownChunk::forBytes($fourCC, $offset, $bytes);
    }

    private function createBuffer(string $value = ''): Buffer
    {
        $buffer = new StringBuffer($value);
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        return $buffer;
    }
}
