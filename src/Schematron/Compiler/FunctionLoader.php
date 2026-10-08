<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use DirectoryIterator;
use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;

final class FunctionLoader
{
    public function __construct(
        protected readonly string $directory,
        protected readonly string $namespace,
    ) {}

    public function load(): array
    {
        $functions = [];

        $directoryIterator = new DirectoryIterator($this->directory);
        $filterIterator = new FunctionFilterIterator($directoryIterator); 

        foreach ($filterIterator as $file) {
            $className = $this->namespace . '\\' . $file->getBasename('.php');
            if (is_subclass_of($className, XPathFunctionInterface::class)) {
                $functions[] = $className;
            }
        }

        return $functions;
    }
}