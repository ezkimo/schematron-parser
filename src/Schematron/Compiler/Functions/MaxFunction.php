<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class MaxFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'max';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'max() expects exactly one argument, received: %s',
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
                    \$values[] = (float) \$node->textContent;
                }
            } elseif (is_array(\$nodes)) {
                \$values = array_map('floatval', \$nodes);
            } elseif (is_scalar(\$nodes)) {
                \$values = [(float) \$nodes];
            }

            return count(\$values) > 0 ? max(\$values) : 0.0;
        })()
        PHP;
    }
}
