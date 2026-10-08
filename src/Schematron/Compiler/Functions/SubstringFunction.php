<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

class SubstringFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'substring';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);

        if ($argumentCount < 2 || $argumentCount > 3) {
            throw new RuntimeException(sprintf(
                'substring() expects 2 or 3 arguments, but received: %s', 
                $argumentCount
            ));
        }

        $arguments = array_pad($arguments, 3, null);
        [$string, $offset, $length] = $arguments;

        return $length === null
            ? sprintf('substr(%s, (int) %s) - 1', $string, $offset)
            : sprintf('substr(%s, (int) %s - 1, (int) %s)', $string, $offset, $length);
    }
}