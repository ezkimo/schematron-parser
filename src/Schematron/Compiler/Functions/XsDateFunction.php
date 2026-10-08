<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class XsDateFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'xs:date';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return sprintf("strtotime((string) %s)", $arguments[0]);
    }
}
