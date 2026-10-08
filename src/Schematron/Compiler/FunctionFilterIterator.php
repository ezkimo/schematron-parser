<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use FilterIterator;

class FunctionFilterIterator extends FilterIterator
{
    public function accept(): bool
    {
        return $this->current()->isFile() === true && 
            str_ends_with($this->current()->getFileName(), 'Function.php');
    }
}