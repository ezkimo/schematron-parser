<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class DistinctValuesFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'distinct-values';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'distinct-values() expects exactly one argument, received: %s',
                $argumentCount
            ));
        }

        $argument = $arguments[0];

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$nodes = {$argument};
            \$values = [];

            if (\$nodes instanceof \DOMNodeList) {
                foreach (\$nodes as \$node) {
                    \$values[] = \$node->textContent;
                }
            } elseif (is_array(\$nodes)) {
                \$values = array_values(\$nodes);
            } elseif (is_scalar(\$nodes)) {
                \$values = [\$nodes];
            }

            return array_values(array_unique(\$values));
        })()
        PHP;
    }
}
