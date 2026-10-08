<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class XsDecimalFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'xs:decimal';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return sprintf("(float) %s", $arguments[0]);
    }
}