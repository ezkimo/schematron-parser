<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class NodeTestNode extends AbstractNode
{
    public function __construct(
        public string $name // z.B. 'text', 'node', 'comment', 'processing-instruction'
    ) {}
}