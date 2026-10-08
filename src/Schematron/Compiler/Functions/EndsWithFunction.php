<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;
use RuntimeException;

final class EndsWithFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'ends-with';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $argumentCount = count($arguments);
        if ($argumentCount !== 2) {
            throw new RuntimeException(sprintf(
                'ends-with() expects exactly 2 arguments, received: %s',
                $argumentCount
            ));
        }

        [ $string, $suffix ] = $arguments;


        return "(substr((string) {$string}, -strlen((string) {$suffix})) === (string) {$suffix})";
    }
}