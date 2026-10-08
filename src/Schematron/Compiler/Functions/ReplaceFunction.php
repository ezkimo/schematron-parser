<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class ReplaceFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'replace';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 3) {
            throw new RuntimeException(sprintf(
                'replace() expects exactly three parameters, received: %s',
                $argumentCount
            ));
        }

        [ $input, $pattern, $replacement ] = $arguments;

        return sprintf(
            "preg_replace('/' . (string) %s . '/u', (string) %s, (string) %s)",
            $pattern,
            $replacement,
            $input
        );
    }
}