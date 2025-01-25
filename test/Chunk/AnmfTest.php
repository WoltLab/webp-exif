<?php

declare(strict_types=1);

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\StringBuffer;
use PHPUnit\Framework\TestCase;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\ChunkType;

final class AnmfTest extends TestCase
{
    public function testReportsCorrectFourCC(): void
    {
        $chunk = Anmf::fromBuffer($this->generateAnmf());
        $this->assertSame(
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

        $buffer = $this->generateAnmf();
        $buffer->setPosition(0);
        $buffer->insertString(str_repeat("\x00", $offset));

        $chunk = Anmf::fromBuffer($buffer);
        $this->assertSame(
            $offset,
            $chunk->getOffset(),
        );
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
