<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class OperatorNode extends AbstractNode
{
    public function __construct(
        public string $operator,
        public AbstractNode $left,
        public AbstractNode $right
    ) {}
}