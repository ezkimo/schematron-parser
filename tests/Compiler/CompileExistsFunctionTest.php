<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\EmptyFunction;
use MMNewmedia\Schematron\Compiler\Functions\ExistsFunction;
use MMNewmedia\Schematron\Compiler\Functions\UpperCaseFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileExistsFunctionTest extends TestCase
{
    public function testExistsWithNonEvaluablePredicateReceivesNodeSet(): void
    {
        $expression = "exists(cac:TaxCategory[upper-case(cbc:ID) = 'VAT']/cbc:ID)";

        $compiler = new XPathAstCompiler([
            ExistsFunction::class,
            UpperCaseFunction::class,
        ]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('count(', $code);
        $this->assertStringContainsString('\DOMNodeList', $code);
        $this->assertStringNotContainsString('XPathValue::stringValue', $code);
        $this->assertStringNotContainsString('string(cac:TaxCategory', $code);
    }

    public function testExistsWithEvaluablePathChecksNodeListLength(): void
    {
        $expression = 'exists(cac:TaxCategory/cbc:ID)';

        $compiler = new XPathAstCompiler([ExistsFunction::class]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('->length > 0', $code);
        $this->assertStringContainsString('cac:TaxCategory/cbc:ID', $code);
        $this->assertStringNotContainsString('XPathValue::stringValue', $code);
    }

    public function testEmptyWithEvaluablePathChecksNodeListLength(): void
    {
        $expression = 'empty(cac:TaxCategory/cbc:ID)';

        $compiler = new XPathAstCompiler([EmptyFunction::class]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('->length > 0', $code);
        $this->assertStringNotContainsString('XPathValue::stringValue', $code);
    }
}
