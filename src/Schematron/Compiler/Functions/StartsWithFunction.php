<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class StartsWithFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'starts-with';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 2) {
            throw new RuntimeException(
                'starts-with() expects exactly 2 arguments. Received: %s', 
                $argumentCount
            );
        }

        [ $string, $prefix ] = $arguments;

        return "(strncmp((string) $string, (string) $prefix, strlen((string) $prefix)) === 0)";
    }
}