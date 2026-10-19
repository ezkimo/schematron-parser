<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Fixtures\FunctionLoader\Example;

use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;
use Override;

final class BetaFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'beta';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return 'beta()';
    }
}
