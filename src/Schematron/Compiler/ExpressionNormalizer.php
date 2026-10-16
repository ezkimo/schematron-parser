<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

class ExpressionNormalizer
{
    /**
     * Cache for normalized expressions, keyed by the raw expression
     *
     * @var array<string, string>
     */
    protected array $normalizationCache = [];

    public function normalize(string $expression): string
    {
        if (isset($this->normalizationCache[$expression])) {
            return $this->normalizationCache[$expression];
        }

        $normalized = trim($expression);

        $normalized = $this->normalizeWhitespace($normalized);
        $normalized = $this->normalizeOperators($normalized);
        $normalized = $this->normalizeIfThenElse($normalized);
        $normalized = $this->normalizeParantheses($normalized);

        // Mehrfache Normalisierung identischer Ausdrücke über den Cache bedienen
        return $this->normalizationCache[$expression] = $normalized;
    }

    /**
     * Clear the cache (e.g. after changes to the Schematron file or on version upgrade)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->normalizationCache = [];
    }

    protected function normalizeWhitespace(string $expression): string
    {
        // Replace multiple whitespace with a single space
        $expression = preg_replace('/\s+/', ' ', $expression);

        // No whitespace before or after parentheses (only after parentheses, when
        // no alphanumeric characters follow)
        $expression = preg_replace('/\s*\(\s*/', '(', $expression);
        $expression = preg_replace('/\)\s+(?=[\)\s]|$)/', ')', $expression);

        return trim($expression);
    }

    protected function normalizeOperators(string $expression): string
    {
        // Comparison operators with spaces
        $expression = preg_replace('/(?<![<>!])\s*=\s*(?![=])/', '=', $expression);
        $expression = preg_replace('/!\s*=(?=[^=])/', '!=', $expression);
        $expression = preg_replace('/<\s*=(?=[^=])/', '<=', $expression);
        $expression = preg_replace('/>\s*=(?=[^=])/', '>=', $expression);
        $expression = preg_replace('/<\s*(?=[^=])/', '< ', $expression);
        $expression = preg_replace('/>\s*(?=[^=])/', '> ', $expression);

        // Logical operators
        $expression = preg_replace('/\s+and\s+/i', ' and ', $expression);
        $expression = preg_replace('/\s+or\s+/i', ' or ', $expression);
        $expression = preg_replace('/\s+mod\s+/i', ' mod ', $expression);
        $expression = preg_replace('/\s+div\s+/i', ' div ', $expression);

        return trim($expression);
    }

    protected function normalizeIfThenElse(string $expression): string
    {
        if (
            stripos($expression, 'if ') !== false &&
            stripos($expression, ' then ') !== false &&
            stripos($expression, ' else ') !== false
        ) {

            $expression = preg_replace('/\s*if\s+/i', 'if ', $expression);
            $expression = preg_replace('/\s+then\s+/i', ' then ', $expression);
            $expression = preg_replace('/\s+else\s+/i', ' else ', $expression);
        }

        return trim($expression);
    }

    protected function normalizeParantheses(string $expression): string
    {
        // Simple parentheses normalization, removing doubled spaces around parentheses
        $expression = preg_replace('/\(\s+/', '(', $expression);
        $expression = preg_replace('/\s+\)/', ')', $expression);

        return trim($expression);
    }
}