<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class FunctionNode extends AbstractNode
{
    public function __construct(
        public string $name,
        /** @var AbstractNode[] */
        public array $arguments
    ) {}
}