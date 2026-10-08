<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

class ExpressionNormalizer
{
    public function normalize(string $expression): string
    {
        $expression = trim($expression);

        $expression = $this->normalizeWhitespace($expression);
        $expression = $this->normalizeOperators($expression);
        $expression = $this->normalizeIfThenElse($expression);
        $expression = $this->normalizeParantheses($expression);

        return $expression;
    }

    protected function normalizeWhitespace(string $expression): string
    {
        // Mehrfache Whitespace durch ein einzelnes Leerzeichen ersetzen
        $expression = preg_replace('/\s+/', ' ', $expression);

        // Kein Whitespace vor oder nach Klammern (nach Klammern nur, wenn danach keine alphanumerischen Zeichen folgen)
        $expression = preg_replace('/\s*\(\s*/', '(', $expression);
        $expression = preg_replace('/\)\s+(?=[\)\s]|$)/', ')', $expression);

        return trim($expression);
    }

    protected function normalizeOperators(string $expression): string
    {
        // Vergleichsoperatoren mit Spaces
        $expression = preg_replace('/(?<![<>!])\s*=\s*(?![=])/', '=', $expression);
        $expression = preg_replace('/!\s*=(?=[^=])/', '!=', $expression);
        $expression = preg_replace('/<\s*=(?=[^=])/', '<=', $expression);
        $expression = preg_replace('/>\s*=(?=[^=])/', '>=', $expression);
        $expression = preg_replace('/<\s*(?=[^=])/', '< ', $expression);
        $expression = preg_replace('/>\s*(?=[^=])/', '> ', $expression);

        // Logische Operatoren
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
        // einfache Klammer-Normalisierung, doppelte Spaces um Klammern entfernen
        $expression = preg_replace('/\(\s+/', '(', $expression);
        $expression = preg_replace('/\s+\)/', ')', $expression);

        return trim($expression);
    }
}