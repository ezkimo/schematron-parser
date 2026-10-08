<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class SubstringAfterFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'substring-after';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 2) {
            throw new RuntimeException(sprintf(
                'substring-after() expects exactly two arguments, received: %s',
                $argumentCount
            ));
        }
        
        [ $string, $needle ] = $arguments;

        return <<<PHP
        (function() use (\$xpath, {$context}) {
            \$pos = strpos({$string}, {$needle});
            return \$pos === false ? '' : substr({$string}, \$pos + strlen({$needle}));
        })()
        PHP;
    }
}