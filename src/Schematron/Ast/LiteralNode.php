<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class LiteralNode extends AbstractNode
{
    public function __construct(
        public string $value
    ) {}
}