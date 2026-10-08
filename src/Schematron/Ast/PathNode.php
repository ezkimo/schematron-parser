<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Ast;

readonly class PathNode extends AbstractNode
{
    public function __construct(
        public string $path
    ) {}
}