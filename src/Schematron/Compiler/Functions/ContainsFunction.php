<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class ContainsFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'contains';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 2) {
            throw new RuntimeException(sprintf(
                'contains() expects exactly two arguments, received: %s',
                $argumentCount
            ));
        }

        [ $string, $needle ] = $arguments;

        return "str_contains((string) {$string}, (string) {$needle})";
    }
}