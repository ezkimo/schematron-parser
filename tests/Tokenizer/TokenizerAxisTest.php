<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Tokenizer;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use PHPUnit\Framework\TestCase;

class TokenizerAxisTest extends TestCase
{
    public function testRecognizesSelfAxis(): void
    {
        $tokenizer = new XPathTokenizer();
        $tokens = $tokenizer->tokenize('.');

        $this->assertCount(1, $tokens);
        $this->assertSame('.', $tokens[0]['value']);
    }

    public function testRecognizesParentAxis(): void
    {
        $tokenizer = new XPathTokenizer();
        $tokens = $tokenizer->tokenize('..');

        $this->assertCount(1, $tokens);
        $this->assertSame('..', $tokens[0]['value']);
    }
}