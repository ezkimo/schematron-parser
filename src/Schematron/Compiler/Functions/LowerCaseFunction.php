<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class LowerCaseFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'lower-case';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'lower-case expects exactly one argument, received: %s',
                $argumentCount
            ));
        }

        return sprintf('mb_strtolower((string) %s)', $arguments[0]);
    }
}