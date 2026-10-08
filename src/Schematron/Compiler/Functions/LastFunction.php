<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class LastFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'last';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {

        return sprintf('count(%s)', $context);
    }
}