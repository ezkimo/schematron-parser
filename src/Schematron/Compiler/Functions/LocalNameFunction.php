<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class LocalNameFunction implements NodeSetAwareFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'local-name';
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
                    return \$node->localName;
                }
                return '';
            }

            if (is_array(\$nodes)) {
                foreach (\$nodes as \$node) {
                    if (\$node instanceof \DOMNode) {
                        return \$node->localName;
                    }
                    return (string) \$node;
                }
                return '';
            }

            if (\$nodes instanceof \DOMNode) {
                return \$nodes->localName;
            }

            return \$nodes === null ? '' : (string) \$nodes;
        })()
        PHP;
    }
}
