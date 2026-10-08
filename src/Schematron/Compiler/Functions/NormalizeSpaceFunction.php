<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

use Override;

final class NormalizeSpaceFunction implements XPathFunctionInterface
{
    #[Override]
    public function supports(string $name): bool
    {
        return $name === 'normalize-space';
    }

    #[Override]
    public function compile(array $arguments, string $context): string
    {
        $arguments = implode(', ', $arguments);
        return sprintf(
            "trim(preg_replace('/\s+/', ' ', %s))", 
            $arguments
        );
    }
}