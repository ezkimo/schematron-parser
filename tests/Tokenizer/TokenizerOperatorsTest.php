<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Tokenizer;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use PHPUnit\Framework\TestCase;

class TokenizerOperatorsTest extends TestCase
{
    public function testRecognizesArithmeticOperators(): void
    {
        $expression = '1 + 2 - 3 * 4 div 5 mod 6';

        $tokenizer = new XPathTokenizer();
        $tokens = $tokenizer->tokenize($expression);

        $operators = array_column($tokens, 'value');

        $this->assertContains('+', $operators);
        $this->assertContains('-', $operators);
        $this->assertContains('*', $operators);
        $this->assertContains('div', $operators);
        $this->assertContains('mod', $operators);
    }
}