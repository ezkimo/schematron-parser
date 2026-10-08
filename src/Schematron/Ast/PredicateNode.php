<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class PredicateNode extends AbstractNode
{
    public function __construct(
        public AbstractNode $path,
        public AbstractNode $predicate,
    ) {}
}