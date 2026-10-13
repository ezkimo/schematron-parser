<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class TokenizeFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'tokenize';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount < 2 || $argumentCount > 3) {
            throw new RuntimeException(sprintf(
                'tokenize() expects two or three arguments, received: %s',
                $argumentCount
            ));
        }

        [ $subject, $pattern ] = $arguments;

        return <<<PHP
        (function() use ({$context}, \$xpath) {
            \$subject = (string) {$subject};
            \$pattern = (string) {$pattern};

            if (\$subject === '') {
                return [];
            }

            if (\$pattern === '') {
                return preg_split('//u', \$subject, -1, PREG_SPLIT_NO_EMPTY);
            }

            \$result = preg_split('/' . \$pattern . '/u', \$subject);

            if (\$result === false) {
                return [];
            }

            return \$result;
        })()
        PHP;
    }
}