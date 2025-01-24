<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use TypeError;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\Chunk\Chunk;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Chunk\Vp8x;
use Woltlab\WebpExif\Exception\DataAfterLastChunk;
use Woltlab\WebpExif\Exception\ExtraChunksInSimpleFormat;
use Woltlab\WebpExif\Exception\MissingChunks;
use Woltlab\WebpExif\Exception\UnexpectedChunk;
use Woltlab\WebpExif\Exception\ExtraVp8xChunk;
use Woltlab\WebpExif\Exception\Vp8xMissingImageData;
use Woltlab\WebpExif\Exception\Vp8xWithoutChunks;

final class WebP
{
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        /** @var list<Chunk> */
        private array $chunks,
    ) {}

    /**
     * @return list<Chunk>
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * Returns the length of all chunks including an additional 8 bytes per
     * chunk for the chunk header.
     */
    public function getByteLength(): int
    {
        return \array_reduce(
            $this->chunks,
            static fn(int $length, Chunk $chunk) => $length + $chunk->getLength() + 8,
            0
        );
    }

    /**
     * @return array{'length': int, 'width': int, 'height': int, 'chunks': list<string>}
     */
    public function debugInfo(): array
    {
        $chunkInfo = \array_map(
            static function (Chunk $chunk) {
                return \sprintf(
                    "Chunk %s (offset %d [0x%X], length %d)",
                    $chunk->getFourCC(),
                    $chunk->getOffset(),
                    $chunk->getOffset(),
                    $chunk->getLength(),
                );
            },
            $this->chunks,
        );

        return [
            'length' => $this->getByteLength(),
            'width' => $this->width,
            'height' => $this->height,
            'chunks' => $chunkInfo,
        ];
    }

    /**
     * @param list<Chunk> $chunks
     */
    public static function fromChunks(array $chunks): self
    {
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof Chunk)) {
                throw new TypeError(
                    \sprintf(
                        "Expected a list of %s, received %s instead",
                        Chunk::class,
                        \get_debug_type($chunk),
                    ),
                );
            }
        }

        if ($chunks === []) {
            throw new MissingChunks();
        }

        // The first chunk must be one of VP8, VP8L or VP8X.
        $firstChunk = \array_shift($chunks);
        \assert($firstChunk !== null);

        return match ($firstChunk::class) {
            Vp8::class, Vp8l::class => self::fromSimpleFormat($firstChunk, $chunks),
            Vp8x::class => self::fromExtendedFormat($firstChunk, $chunks),
            default => throw new UnexpectedChunk($firstChunk->getFourCC(), 12)
        };
    }

    /**
     * @param list<Chunk> $chunks
     */
    private static function fromSimpleFormat(Vp8|Vp8l $imageData, array $chunks): self
    {
        if ($chunks !== []) {
            throw new ExtraChunksInSimpleFormat(
                $imageData->getFourCC(),
                \array_map(static fn(Chunk $chunk) => $chunk->getFourCC(), $chunks)
            );
        }

        return new WebP(
            $imageData->width,
            $imageData->height,
            [
                $imageData,
                ...$chunks,
            ]
        );
    }

    /**
     * @param list<Chunk> $chunks
     */
    private static function fromExtendedFormat(Vp8x $vp8x, array $chunks): self
    {
        if ($chunks === []) {
            throw new Vp8xWithoutChunks();
        }

        $nestedVp8x = \array_find($chunks, static fn($chunk) => $chunk instanceof Vp8x);
        if ($nestedVp8x !== null) {
            throw new ExtraVp8xChunk();
        }

        // The VP8X chunk most contain image data that can come in two flavors:
        //  1. Still images must contain either a VP8 or VP8L chunk.
        //  2. Animated images must contain multiple frames.
        $hasAnimation = \array_find($chunks, static fn($chunk) => $chunk instanceof Anim);
        if ($hasAnimation) {
            $frames = \array_filter(
                $chunks,
                static fn($chunk) => $chunk instanceof Anmf
            );

            if (\count($frames) < 2) {
                throw new Vp8xMissingImageData(stillImage: false);
            }
        } else {
            $hasBitstreamChunk = \array_find(
                $chunks,
                static fn($chunk) => ($chunk instanceof Vp8) || ($chunk instanceof Vp8l)
            );

            if (!$hasBitstreamChunk) {
                throw new Vp8xMissingImageData(stillImage: true);
            }
        }

        return new WebP($vp8x->width, $vp8x->height, $chunks);
    }
}
