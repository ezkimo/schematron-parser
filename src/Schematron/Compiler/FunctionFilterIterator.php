<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use DirectoryIterator;
use FilterIterator;
use Override;

/** @extends FilterIterator<int, DirectoryIterator, DirectoryIterator> */
class FunctionFilterIterator extends FilterIterator
{
    #[Override]
    public function accept(): bool
    {
        return $this->current()->isFile() === true && 
            str_ends_with($this->current()->getFileName(), 'Function.php');
    }
}