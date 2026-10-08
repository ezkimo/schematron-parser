<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class TrueFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'true';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return 'true';
    }
}