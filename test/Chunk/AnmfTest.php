<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\Chunk\Exception\AnimationFrameWithoutBitstream;
use Woltlab\WebpExif\Chunk\Exception\EmptyAnimationFrame;
use Woltlab\WebpExif\ChunkType;
use Woltlab\WebpExif\Exception\UnexpectedChunk;

final class AnmfTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $frameData = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
        ];
        $chunk = Anmf::fromBuffer($this->generateAnmf($frameData));
        self::assertSame(
            ChunkType::ANMF,
            ChunkType::fromFourCC($chunk->getFourCC()),
        );
    }

    public function testReportsCorrectOffset(): void
    {
        // This is a bogus offset that cannot naturally occur because all chunks
        // in a RIFF contain must be of even length. We do not validate the
        // offset so this ensures we're not dealing with hardcoded values.
        $offset = 7;

        $frameData = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
        ];
        $buffer = $this->generateAnmf($frameData);
        $buffer->setPosition(0);
        $buffer->insertString(str_repeat("\x00", $offset));

        $chunk = Anmf::fromBuffer($buffer);
        self::assertSame(
            $offset,
            $chunk->getOffset(),
        );
    }

    public function testAlphPermittedAtFirstPosition(): void
    {
        $frameData = [
            "ALPH\x00\x00\x00\x00",
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
        ];
        $buffer = $this->generateAnmf($frameData);

        $chunk = Anmf::fromBuffer($buffer);

        self::assertEquals(
            count($chunk->getDataChunks()),
            2
        );
    }

    public function testPermitsUnknownChunksAtEnd(): void
    {
        $frameData = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            "####\x00\x00\x00\x00",
        ];
        $buffer = $this->generateAnmf($frameData);

        $chunk = Anmf::fromBuffer($buffer);

        self::assertEquals(
            count($chunk->getDataChunks()),
            2
        );
    }

    public function testReportsCorrectDataChunks(): void
    {
        $frameData = [
            "ALPH\x00\x00\x00\x00",
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            "####\x00\x00\x00\x00",
        ];
        $buffer = $this->generateAnmf($frameData);

        $chunk = Anmf::fromBuffer($buffer);
        $dataChunks = $chunk->getDataChunks();

        self::assertEquals(count($dataChunks), 3);
        self::assertEquals([
            $dataChunks[0]->getFourCC(),
            $dataChunks[0]->getOffset(),
        ], [
            "ALPH",
            20
        ]);
        self::assertEquals([
            $dataChunks[1]->getFourCC(),
            $dataChunks[1]->getOffset()
        ], [
            "VP8L",
            28
        ]);
        self::assertEquals([
            $dataChunks[2]->getFourCC(),
            $dataChunks[2]->getOffset(),
        ], [
            "####",
            42,
        ]);
    }

    public function testRejectsKnownChunkAfterImageData(): void
    {
        $this->expectExceptionObject(new UnexpectedChunk("ALPH", 0x22));

        $frameData = [
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
            "ALPH\x00\x00\x00\x00",
        ];
        $buffer = $this->generateAnmf($frameData);

        Anmf::fromBuffer($buffer);
    }

    public function testRejectsUnknownChunkBeforeImageData(): void
    {
        $this->expectExceptionObject(new AnimationFrameWithoutBitstream(0));

        $frameData = [
            "####\x00\x00\x00\x00",
            "VP8L\x06\x00\x00\x00\x2F\x41\x6C\x6F\x00\x6B",
        ];
        $buffer = $this->generateAnmf($frameData);

        Anmf::fromBuffer($buffer);
    }

    public function testRejectsMissingBitstream(): void
    {
        $this->expectExceptionObject(new AnimationFrameWithoutBitstream(0));

        $frameData = [
            "ALPH\x00\x00\x00\x00",
        ];
        $buffer = $this->generateAnmf($frameData);

        Anmf::fromBuffer($buffer);
    }

    public function testRejectsEmptyFrame(): void
    {
        $this->expectExceptionObject(new EmptyAnimationFrame(0));

        $frameData = [];
        $buffer = $this->generateAnmf($frameData);

        Anmf::fromBuffer($buffer);
    }

    /**
     * @param list<string> $frameData
     */
    private function generateAnmf(array $frameData = []): Buffer
    {
        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        // The uint32 length will be inserted here in the last step.

        $buffer->insertString(str_repeat("\x00", 16));

        foreach ($frameData as $chunk) {
            $buffer->insertString($chunk);
            if (strlen($chunk) % 2 === 1) {
                $buffer->insertByte(0);
            }
        }

        $buffer->setPosition(0)->insertInt($buffer->size());

        return $buffer->setPosition(0);
    }
}
