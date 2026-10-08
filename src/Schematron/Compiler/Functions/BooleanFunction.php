<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class BooleanFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'boolean';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(
                'boolean() expects exactly one argument, received: %s',
                $argumentCount
            );
        }

        return sprintf(
            "( 
                (is_array(%s) && count(%s) > 0) 
                || %s instanceof \DOMNode 
                || (
                    ! is_array(%s) 
                    && %s !== null 
                    && %s !== '' 
                    && ! ((is_int(%s) || is_float(%s)) && (%s == 0 || %s === NAN))
                )
            )",
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0],
            $arguments[0]
        );
    }
}