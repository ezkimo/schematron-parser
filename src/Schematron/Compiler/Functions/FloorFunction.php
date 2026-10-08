<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class FloorFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'floor';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'floor() expects exactly one argument, received: %s',
                $argumentCount
            ));
        }

        return sprintf(
            'floor((float) %s)',
            $arguments[0]
        );
    }
}