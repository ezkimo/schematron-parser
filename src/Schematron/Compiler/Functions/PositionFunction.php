<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class PositionFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'position';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return sprintf("\$xpath->evaluate('position()', %s)", $context);
    }
}