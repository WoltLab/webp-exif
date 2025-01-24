<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Exception\UnknownChunkWithKnownFourCC;
use Woltlab\WebpExif\Chunk\UnknownChunk;

final class UnknownChunkTest extends TestCase
{
    public function testRejectsKnownFourCC(): void
    {
        $this->expectException(UnknownChunkWithKnownFourCC::class);

        UnknownChunk::forBytes("ALPH", 0, "");
    }

    public function testFourCCIsPreserved(): void
    {
        $fourCC = "####";
        $chunk = UnknownChunk::forBytes($fourCC, 0, "");

        $this->assertEquals($fourCC, $chunk->getFourCC());
    }

    public function testReportsCorrectOffset(): void
    {
        // This is a bogus offset that cannot naturally occur because all chunks
        // in a RIFF contain must be of even length. We do not validate the
        // offset so this ensures we're not dealing with hardcoded values.
        $offset = 7;

        $chunk = UnknownChunk::forBytes("####", $offset, "");
        $this->assertSame(
            $offset,
            $chunk->getOffset(),
        );
    }
}
