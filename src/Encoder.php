<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use Nelexa\Buffer\Buffer;
use Nelexa\Buffer\BufferException;
use Nelexa\Buffer\StringBuffer;

final class Encoder
{
    /**
     * Encodes a `WebP` object into the simple or extended format depending on
     * the contained chunks.
     *
     * @return string raw bytes
     */
    public function fromWebP(WebP $webp): string
    {
        if ($webp->containsOnlyBitstream()) {
            return $this->toSimpleFormat($webp);
        }

        return $this->toExtendedFileFormat($webp);
    }

    private function toSimpleFormat(WebP $webp): string
    {
        $chunks = $webp->getChunks();
        \assert(\count($chunks) === 1);
        $bitstream = $chunks[0];

        $buffer = new StringBuffer();
        $buffer->setOrder(Buffer::LITTLE_ENDIAN);

        $buffer->insertString("RIFF");

        $riffHeaderLength = 12;
        $chunkHeader = 8;
        $buffer->insertInt($riffHeaderLength + $chunkHeader + $bitstream->getLength());

        $buffer->insertString("WEBP");

        $buffer->insertString($bitstream->getFourCC());
        $buffer->insertInt($bitstream->getLength());
        $buffer->insertString($bitstream->getRawBytes());

        return $buffer->toString();
    }

    private function toExtendedFileFormat(WebP $webp): string
    {
        \var_dump($webp->debugInfo());
        throw new \RuntimeException("NOT IMPLEMENTED");
    }
}
