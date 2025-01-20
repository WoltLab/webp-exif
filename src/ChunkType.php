<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

enum ChunkType
{
    case ALPH;
    case ANIM;
    case EXIF;
    case ICCP;
    case VP8;
    case VP8L;
    case VP8X;
    case XMP;
    case UnknownChunk;

    public static function fromFourCC(string $fourCC): self
    {
        return match ($fourCC) {
            "ALPH" => self::ALPH,
            "ANIM" => self::ANIM,
            "EXIF" => self::EXIF,
            "ICCP" => self::ICCP,
            "VP8 " => self::VP8,
            "VP8L" => self::VP8L,
            "VP8X" => self::VP8X,
            "XMP " => self::XMP,
            default => self::UnknownChunk,
        };
    }
}
