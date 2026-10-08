<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class NotFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'not';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $arguments = implode(', ', $arguments);
        return "!({$arguments})";
    }
}