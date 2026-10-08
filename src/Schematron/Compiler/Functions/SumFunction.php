<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class SumFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'sum';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        if (count($arguments) !== 1){
            throw new RuntimeException(sprintf(
                '%s expects exactly one argument',
                self::class,
            ));
        }

        $argument = $arguments[0];

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$nodes = {$argument};
            \$sum = 0.0;

            if (\$nodes instanceof \DOMNodeList) {
                foreach (\$nodes as \$node) {
                    \$sum += (float) \$node->textContent;
                }
            } elseif (is_array(\$nodes)) {
                foreach (\$nodes as \$value) {
                    \$sum += (float) \$value;
                }
            } elseif (is_scalar(\$nodes)) {
                \$sum = (float) \$nodes;
            }

            return \$sum;
        })()
        PHP;
    }
}
