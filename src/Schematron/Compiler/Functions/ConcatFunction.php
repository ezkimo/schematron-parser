<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class ConcatFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'concat';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        
        if ($argumentCount < 2) {
            throw new RuntimeException(sprintf(
                'concat() expects at least two arguments, received: %s', 
                $argumentCount)
            );
        }

        return "(" . implode(' . ', $arguments) . ")";
    }
}