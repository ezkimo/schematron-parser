<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class SubstringBeforeFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'substring-before';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 2) {
            throw new RuntimeException(sprintf(
                'substring-before() expects exactly two arguments, received: %s',
                $argumentCount
            ));
        }

        [ $haystack, $needle ] = $arguments;

        return <<<PHP
        (function() use (\$xpath, {$context}) {
            \$pos = strpos({$haystack}, {$needle});
            return \$pos === false ? '' : substr({$haystack}, 0, \$pos);
        })()
        PHP;
    }
}