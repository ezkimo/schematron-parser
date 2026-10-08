<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

interface XPathFunctionInterface
{
    public function supports(string $name): bool;

    public function compile(array $arguments, string $context): string;
}