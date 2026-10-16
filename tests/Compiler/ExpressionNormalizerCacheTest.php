<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Normalization is deterministic, so identical raw expressions must be
 * normalized only once. A repeated raw expression must reuse the cached
 * result instead of running the normalization passes again.
 */
class ExpressionNormalizerCacheTest extends TestCase
{
    private function normalizer(): ExpressionNormalizer
    {
        return new ExpressionNormalizer();
    }

    /**
     * Creates a normalizer that counts how often the normalization steps run.
     */
    private function countingNormalizer(): CountingExpressionNormalizer
    {
        return new CountingExpressionNormalizer();
    }

    public function testRepeatedExpressionsAreNormalizedOnlyOnce(): void
    {
        $normalizer = $this->countingNormalizer();

        $first = $normalizer->normalize('a =  b and  c');

        $this->assertSame(1, $normalizer->whitespaceCalls());

        // Re-normalizing the same expression is served from the cache
        $second = $normalizer->normalize('a =  b and  c');

        $this->assertSame(1, $normalizer->whitespaceCalls());
        $this->assertSame($first, $second);
    }

    public function testDifferentExpressionsAreNormalizedSeparately(): void
    {
        $normalizer = $this->countingNormalizer();

        $normalizer->normalize('a =  b');
        $normalizer->normalize('c =  d');

        $this->assertSame(2, $normalizer->whitespaceCalls());
    }

    public function testEquivalentExpressionsAreNormalizedIdentically(): void
    {
        $normalizer = $this->countingNormalizer();

        // Beide Ausdrücke werden normalisiert, liefern aber identische Ergebnisse
        $this->assertSame(
            $normalizer->normalize('a = b'),
            $normalizer->normalize('a=b'),
        );

        // Beide Ausdrücke werden normalisiert, da sie unterschiedlich geschrieben sind
        $this->assertSame(2, $normalizer->whitespaceCalls());
    }

    public function testClearCacheForcesRepetitionOfNormalization(): void
    {
        $normalizer = $this->countingNormalizer();

        $normalizer->normalize('a = b');
        $normalizer->clearCache();

        // After clearing the cache, the expression is normalized again
        $normalizer->normalize('a = b');

        $this->assertSame(2, $normalizer->whitespaceCalls());
    }
}

/**
 * Normalizer that counts how often the normalization steps run.
 */
final class CountingExpressionNormalizer extends ExpressionNormalizer
{
    private int $whitespaceCalls = 0;

    public function whitespaceCalls(): int
    {
        return $this->whitespaceCalls;
    }

    #[\Override]
    protected function normalizeWhitespace(string $expression): string
    {
        $this->whitespaceCalls++;

        return parent::normalizeWhitespace($expression);
    }
}
