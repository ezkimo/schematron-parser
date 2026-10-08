<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\NormalizeSpaceFunction;
use MMNewmedia\Schematron\Compiler\Functions\UpperCaseFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileNonEvaluablePredicateTest extends TestCase
{
    public function testUpperCasePredicateCompilesToPhpPipeline(): void
    {
        $expression = "ram:SpecifiedLineTradeSettlement/ram:ApplicableTradeTax[upper-case(ram:TypeCode) = 'VAT']/ram:CategoryCode";

        $compiler = new XPathAstCompiler([UpperCaseFunction::class]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('mb_strtoupper', $code);
        $this->assertStringContainsString('XPathValue::stringValue', $code);
        $this->assertStringContainsString('$xpath->evaluate("ram:CategoryCode", $node)', $code);
        $this->assertStringNotContainsString('upper-case(ram:TypeCode)', $code);
        $this->assertStringNotContainsString('string(ram:SpecifiedLineTradeSettlement', $code);
    }

    public function testSelfAxisPredicateCompilesToPhpPipeline(): void
    {
        $expression = ".[upper-case(ram:TypeCode) = 'VAT']/ram:CategoryCode";

        $compiler = new XPathAstCompiler([UpperCaseFunction::class]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('mb_strtoupper', $code);
        $this->assertStringContainsString('XPathValue::stringValue', $code);
        $this->assertStringContainsString('$xpath->evaluate(".", $context)', $code);
        $this->assertStringNotContainsString('upper-case(', $code);
    }

    public function testNestedNormalizeSpaceUpperCasePredicateCompilesToPhpPipeline(): void
    {
        $expression = ".[normalize-space(upper-case(ram:TypeCode)) = 'VAT']/ram:CategoryCode";

        $compiler = new XPathAstCompiler([
            NormalizeSpaceFunction::class,
            UpperCaseFunction::class,
        ]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('mb_strtoupper', $code);
        $this->assertStringContainsString('XPathValue::stringValue', $code);
        $this->assertStringNotContainsString('upper-case(', $code);
        $this->assertStringNotContainsString('string(.[', $code);
    }

    public function testPathStepFunctionArgumentResolvesAgainstStepNodes(): void
    {
        $expression = "cac:TaxCategory[cac:TaxScheme/normalize-space(upper-case(cbc:ID)) = 'VAT']/cbc:ID";

        $compiler = new XPathAstCompiler([
            NormalizeSpaceFunction::class,
            UpperCaseFunction::class,
        ]);
        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('$xpath->evaluate("cac:TaxCategory", $context)', $code);
        $this->assertStringContainsString('$xpath->evaluate("cac:TaxScheme", $node)', $code);
        $this->assertStringContainsString('mb_strtoupper((string) $xpath->evaluate("string(cbc:ID)", $node))', $code);
        $this->assertStringNotContainsString('string(cbc:ID)", $context', $code);
    }
}
