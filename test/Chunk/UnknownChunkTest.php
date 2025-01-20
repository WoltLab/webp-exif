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

        UnknownChunk::forBytes("ALPH", "");
    }

    public function testFourCCIsPreserved(): void
    {
        $fourCC = "####";
        $chunk = UnknownChunk::forBytes($fourCC, "");

        $this->assertEquals($fourCC, $chunk->getFourCC());
    }
}
