<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class AbsFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'abs';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'abs() expects exactly one argument, recevied: %s', 
                $argumentCount
            ));
        }

        return sprintf(
            'abs((float) %s)',
            $arguments[0]
        );
    }
}