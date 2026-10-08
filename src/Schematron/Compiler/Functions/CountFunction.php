<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class CountFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'count';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 1) {
            throw new RuntimeException(sprintf(
                'count() expects exactly one argument. Received: %s', 
                $argumentCount
            ));
        }

        $argument = $arguments[0];

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$nodes = {$argument};

            if (\$nodes instanceof \DOMNodeList) {
                return \$nodes->length;
            }

            return is_array(\$nodes) ? count(\$nodes) : 0;
        })()
        PHP;
    }
}
