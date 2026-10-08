<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class NumberFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'number';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $expression = $arguments[0] ?? $context;
        return sprintf("floatval(%s)", $expression);
    }
}