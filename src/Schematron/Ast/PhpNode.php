<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class PhpNode extends AbstractNode
{
    public function __construct(
        public string $code
    ) {}
}