<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class AxisNode extends AbstractNode
{
    public function __construct(
        public string $axis,   // z.B. 'child', 'descendant', 'attribute'
        public ?string $name = null    // z.B. 'ram:ClassCode'
    ) {}
}