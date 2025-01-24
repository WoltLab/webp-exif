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

        $jsonData = @file_get_contents("{$pathname}.json");
        if ($jsonData === false) {
            // TODO: Remove this once we have set up the JSON for all test files!
            return;
        }

        $json = json_decode($jsonData, true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($json) && isset($json['chunks']) && is_array($json['chunks']));

        /** @var list<array{fourCC: string, offset: int, length: int, width?: int, height?: int}> $expectedChunks */
        $expectedChunks = $json['chunks'];

        // We do not store the VP8X chunk itself, thus we need to skip it when
        // checking for the decoding result.
        if ($expectedChunks[0]['fourCC'] === 'VP8X') {
            array_shift($expectedChunks);
        }

        $chunks = $webp->getChunks();
        for ($i = 0, $length = count($chunks); $i < $length; $i++) {
            $expected = $expectedChunks[$i];
            $chunk = $chunks[$i];

            $debugInfo = sprintf(
                "Testing chunk %s at offset %d (0x%s) to match the chunk at offset %d (0x%s)",
                $i,
                $expected['offset'],
                dechex($expected['offset']),
                $chunk->getOffset(),
                dechex($chunk->getOffset()),
            );

            // The chunk length reported by `webpinfo` differs in that it counts
            // the padding byte plus the header. A chunk payload of 17 bytes
            // would be reported as 17 + 1 (padding) + 8 = 26
            $paddingByte = $chunk->getLength() % 2;
            $effectiveLength = $chunk->getLength() + $paddingByte + 8;

            $this->assertEquals(
                [
                    $expected['fourCC'],
                    $expected['offset'],
                    $expected['length'],
                ],
                [
                    $chunk->getFourCC(),
                    $chunk->getOffset(),
                    $effectiveLength,
                ],
                $debugInfo,
            );
        }
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
