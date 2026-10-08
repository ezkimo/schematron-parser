<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class AvgFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'avg';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'avg() expects exactly one argument, received: %s',
                $argumentCount
            ));
        }

        $argument = $arguments[0];

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$nodes = {$argument};
            \$sum = 0.0;
            \$count = 0;

            if (\$nodes instanceof \DOMNodeList) {
                foreach (\$nodes as \$node) {
                    \$sum += (float) \$node->textContent;
                    \$count++;
                }
            } elseif (is_array(\$nodes)) {
                foreach (\$nodes as \$value) {
                    \$sum += (float) \$value;
                    \$count++;
                }
            } elseif (is_scalar(\$nodes)) {
                return (float) \$nodes;
            }

            return \$count > 0 ? \$sum / \$count : 0.0;
        })()
        PHP;
    }
}
