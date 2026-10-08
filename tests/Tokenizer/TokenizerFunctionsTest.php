<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Tokenizer;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use PHPUnit\Framework\TestCase;

class TokenizerFunctionsTest extends TestCase
{
    public function testRecognizesFunctionNames(): void
    {
        $tokenizer = new XPathTokenizer();
        $tokens = $tokenizer->tokenize('sum(round(xs:decimal(100)))');

        $names = array_column($tokens, 'value');

        $this->assertContains('sum', $names);
        $this->assertContains('round', $names);
        $this->assertContains('xs:decimal', $names);
    }
}