<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WoltLab\WebpExif\Chunk\Alph;
use WoltLab\WebpExif\ChunkType;

/**
 * @author      Alexander Ebert
 * @copyright   2025 WoltLab GmbH
 * @license     The MIT License <https://opensource.org/license/mit>
 */
final class AlphTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Alph::forBytes(0, "");
        self::assertSame(
            ChunkType::ALPH,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testReportsCorrectOffset(): void
    {
        // This is a bogus offset that cannot naturally occur because all chunks
        // in a RIFF contain must be of even length. We do not validate the
        // offset so this ensures we're not dealing with hardcoded values.
        $offset = 7;

        $chunk = Alph::forBytes($offset, "");
        self::assertSame(
            $offset,
            $chunk->getOffset(),
        );
    }
}
