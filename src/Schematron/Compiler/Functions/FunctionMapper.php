<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

class FunctionMapper
{
    /**
     * @param XPathFunctionInterface[] $functions
     */
    public function __construct(
        protected array $functions,
    ) {}

    public function map(string $expression, string $context): false|string
    {
        foreach ($this->functions as $fn) {
            if ($fn->supports($expression) === true) {
                return $fn->compile($expression, $context);
            }
        }

        return false;
    }
}