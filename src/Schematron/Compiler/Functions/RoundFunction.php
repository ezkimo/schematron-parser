<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class RoundFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'round';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1 && $argumentCount !== 2) {
            throw new RuntimeException(sprintf(
                'round() expects one or two arguments; received: %s', 
                $argumentCount
            ));
        }

        if ($argumentCount === 1) {
            return sprintf('round((float) %s)', $arguments[0]);
        }

        return sprintf('round((float) %s, (int) (%s))', $arguments[0], $arguments[1]);
    }
}