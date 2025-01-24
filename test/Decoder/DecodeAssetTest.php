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
        $binary = file_get_contents($pathname);
        assert($binary !== false);

        $decoder = new Decoder();
        $webp = $decoder->fromBinary($binary);

        // Placeholder assertion that makes both phpunit and phpstan happy
        // because we test those files to validate that the decoder works.
        // see https://github.com/sebastianbergmann/phpunit/issues/3016
        $this->assertTrue($webp->getByteLength() > 0);
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
