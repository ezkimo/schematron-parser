<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class NameFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'name';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argument = $arguments[0] ?? $context;

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$nodes = {$argument};

            if (\$nodes instanceof \DOMNodeList) {
                foreach (\$nodes as \$node) {
                    return \$node->nodeName;
                }
                return '';
            }

            if (is_array(\$nodes)) {
                foreach (\$nodes as \$node) {
                    if (\$node instanceof \DOMNode) {
                        return \$node->nodeName;
                    }
                    return (string) \$node;
                }
                return '';
            }

            if (\$nodes instanceof \DOMNode) {
                return \$nodes->nodeName;
            }

            return \$nodes === null ? '' : (string) \$nodes;
        })()
        PHP;
    }
}
