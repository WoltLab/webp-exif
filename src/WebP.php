<?php

declare(strict_types=1);

namespace Woltlab\WebpExif;

use BadMethodCallException;
use Woltlab\WebpExif\Chunk\Alph;
use Woltlab\WebpExif\Chunk\Anim;
use Woltlab\WebpExif\Chunk\Anmf;
use Woltlab\WebpExif\Chunk\Chunk;
use Woltlab\WebpExif\Chunk\Exif;
use Woltlab\WebpExif\Chunk\Iccp;
use Woltlab\WebpExif\Chunk\UnknownChunk;
use Woltlab\WebpExif\Chunk\Vp8;
use Woltlab\WebpExif\Chunk\Vp8l;
use Woltlab\WebpExif\Chunk\Vp8x;
use Woltlab\WebpExif\Chunk\Xmp;
use Woltlab\WebpExif\Exception\ExtraChunksInSimpleFormat;
use Woltlab\WebpExif\Exception\MissingChunks;
use Woltlab\WebpExif\Exception\UnexpectedChunk;

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
     * WebP can be encoded using the simple format that only contains a single
     * VP8 or VP8L chunk and is a bit smaller. Animations or any sort of extra
     * information is only supported in the extended file format.
     */
    public function containsOnlyBitstream(): bool
    {
        if (\count($this->chunks) > 1) {
            return false;
        }

        // The simple format can only contain a single VP8/VP8L chunk.
        return \array_all($this->chunks, static function ($chunk) {
            return match ($chunk::class) {
                Vp8::class, Vp8l::class => true,
                default => false,
            };
        });
    }

    public function getIccProfile(): ?Iccp
    {
        return \array_find($this->chunks, static fn($chunk) => $chunk instanceof Iccp);
    }

    public function getAlpha(): ?Alph
    {
        return \array_find($this->chunks, static fn($chunk) => $chunk instanceof Alph);
    }

    public function getExif(): ?Exif
    {
        return \array_find($this->chunks, static fn($chunk) => $chunk instanceof Exif);
    }

    public function getXmp(): ?Xmp
    {
        return \array_find($this->chunks, static fn($chunk) => $chunk instanceof Xmp);
    }

    public function getAnimation(): ?Anim
    {
        return \array_find($this->chunks, static fn($chunk) => $chunk instanceof Anim);
    }

    /**
     * @return list<Anmf>
     */
    public function getAnimationFrames(): array
    {
        return \array_values(
            \array_filter(
                $this->chunks,
                static fn($chunk) => $chunk instanceof Anmf
            )
        );
    }

    public function getBitstream(): Vp8|Vp8l|null
    {
        if ($this->getAnimation() !== null) {
            return null;
        }

        return \array_find(
            $this->chunks,
            static fn($chunk) => $chunk instanceof Vp8 || $chunk instanceof Vp8l
        );
    }

    /**
     * @return list<UnknownChunk>
     */
    public function getUnknownChunks(): array
    {
        return \array_values(
            \array_filter(
                $this->chunks,
                static fn($chunk) => $chunk instanceof UnknownChunk
            )
        );
    }

    public function withExif(?Exif $exif): self
    {
        $chunks = \array_values(
            \array_filter(
                $this->chunks,
                static fn($chunk) => !($chunk instanceof Exif)
            )
        );

        if ($exif !== null) {
            $chunks[] = $exif;
        }

        $webp = clone $this;
        $webp->chunks = $chunks;

        return $webp;
    }

    public function withIccp(?Iccp $iccp): self
    {
        $chunks = \array_values(
            \array_filter(
                $this->chunks,
                static fn($chunk) => !($chunk instanceof Iccp)
            )
        );

        if ($iccp !== null) {
            $chunks[] = $iccp;
        }

        $webp = clone $this;
        $webp->chunks = $chunks;

        return $webp;
    }

    public function withXmp(?Xmp $xmp): self
    {
        $chunks = \array_values(
            \array_filter(
                $this->chunks,
                static fn($chunk) => !($chunk instanceof Xmp)
            )
        );

        if ($xmp !== null) {
            $chunks[] = $xmp;
        }

        $webp = clone $this;
        $webp->chunks = $chunks;

        return $webp;
    }

    /**
     * @param list<UnknownChunk> $chunks
     */
    public function withUnknownChunks(array $chunks): self
    {
        $newChunks = $this->chunks;
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof UnknownChunk)) {
                throw new BadMethodCallException(
                    \sprintf(
                        "Expected a list of %s, received %s instead",
                        UnknownChunk::class,
                        \get_debug_type($chunk),
                    ),
                );
            }

            $newChunks[] = $chunk;
        }

        $webp = clone $this;
        $webp->chunks = $newChunks;

        return $webp;
    }

    /**
     * @param list<Chunk> $chunks
     */
    public static function fromChunks(array $chunks): self
    {
        foreach ($chunks as $chunk) {
            if (!($chunk instanceof Chunk)) {
                throw new BadMethodCallException(
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
        $chunks = $vp8x->filterChunks($chunks);

        return new WebP($vp8x->width, $vp8x->height, $chunks);
    }
}
