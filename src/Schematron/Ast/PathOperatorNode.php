<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class PathOperatorNode extends AbstractNode
{
    public function __construct(
        public string $operator, // '/' oder '//'
        public AbstractNode $left,
        public AbstractNode $right
    ) {}
}