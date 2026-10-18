<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Fixtures\FunctionLoader\Example;

use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;
use Override;

/**
 * Implements XPathFunctionInterface but does not match the *Function.php
 * file name filter, so FunctionLoader must not pick it up.
 */
final class IgnoredHelper implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'ignored';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        return 'ignored()';
    }
}
