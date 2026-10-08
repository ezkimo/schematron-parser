<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class FalseFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'false';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 0) {
            throw new RuntimeException(sprintf(
                'false() expects exactly one argument, received: %s',
                $argumentCount
            ));
        }
        
        return 'false';
    }
}