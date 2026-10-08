<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

class IfThenElseFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $expression): bool
    {
        $expression = trim($expression);
        return str_starts_with($expression, 'if ')
            && str_contains($expression, ' then ')
            && str_contains($expression, ' else ');
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 3) {
            throw new RuntimeException(sprintf(
                'if-then-else() expects exactly three arguments, received: %s',
                $argumentCount
            ));
        }

        [ $condition, $then, $else] = $arguments;

        return sprintf('(%s ? %s : %s)', $condition, $then, $else);
    }
}