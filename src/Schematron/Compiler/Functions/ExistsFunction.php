<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

class ExistsFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'exists';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'exists() expects exactly one argument, received: %s',
                $argumentCount
            ));
        }

        $arg = $arguments[0];

        return sprintf(
            "((%1\$s instanceof \\DOMNodeList && %1\$s->length > 0)"
            . " || (is_array(%1\$s) && count(%1\$s) > 0)"
            . " || (%1\$s instanceof \\DOMNode)"
            . " || (!(%1\$s instanceof \\DOMNodeList) && !is_array(%1\$s) && %1\$s !== null && %1\$s !== ''))",
            $arg
        );
    }
}
