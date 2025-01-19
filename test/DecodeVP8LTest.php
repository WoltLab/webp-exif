<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;

final class DecodeVP8LTest extends TestCase
{
    public function testCanDecodeVP8L(): void
    {
        $binary = hex2bin("5249464612000000574542505650384C060000002F416C6F006B");
        assert($binary !== false);

        $decoder = new Decoder();
        $webp = $decoder->fromBinary($binary);

        $this->assertSame([
            'length' => 14,
            'width' => 11330,
            'height' => 446,
            'chunks' => [
                'Chunk VP8L (length 6)'
            ],
        ], $webp->debugInfo());
    }
}
