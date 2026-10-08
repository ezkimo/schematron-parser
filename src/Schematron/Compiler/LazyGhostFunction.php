<?php

declare(Strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;
use ReflectionClass;

class LazyGhostFunction
{
    protected ?XPathFunctionInterface $instance = null;

    public function __construct(
        protected string $class,
    ) {}

    protected function getInstance(): XPathFunctionInterface
    {
        if ($this->instance === null) {
            $reflector = new ReflectionClass($this->class);
            $this->instance = $reflector->newLazyGhost(function(XPathFunctionInterface $ghost) {

            });
        }

        return $this->instance;
    }

    public function supports(string $expression): bool
    {
        return $this->getInstance()->supports($expression);
    }

    public function compile(string $expression, string $context): string
    {
        return $this->getInstance()->compile($expression, $context);
    }
}