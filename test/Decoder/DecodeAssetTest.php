<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Decoder;

final class DecodeAssetTest extends TestCase
{
    #[DataProvider('pathnameProvider')]
    public function testDecodeAsset(string $pathname): void
    {
        $decoder = new Decoder();
        $webp = $decoder->fromBinary(file_get_contents($pathname));

        $this->assertIsObject($webp);
    }

    public static function pathnameProvider(): Generator
    {
        foreach (glob("./test/TestAsset/*.webp") as $file) {
            yield [$file];
        }
    }
}
