<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class MatchesFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'matches';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount < 2 || $argumentCount > 3) {
            throw new RuntimeException(sprintf(
                'mathces() expects 2 or 3 arguments. Received: %s',
                $argumentCount
            ));
        }
        
        $subject = $arguments[0];
        $pattern = $arguments[1];
        $flags = $arguments[2] ?? '';

        return $flags !== ''
            ? "preg_match('/' . {$pattern} . '/' . {$flags}, (string) {$subject}) === 1"
            : "preg_match('/' . {$pattern} . '/', (string) {$subject}) === 1";
    }
}