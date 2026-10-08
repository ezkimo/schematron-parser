<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\Functions\AvgFunction;
use MMNewmedia\Schematron\Compiler\Functions\CountFunction;
use MMNewmedia\Schematron\Compiler\Functions\DistinctValuesFunction;
use MMNewmedia\Schematron\Compiler\Functions\MaxFunction;
use MMNewmedia\Schematron\Compiler\Functions\MinFunction;
use MMNewmedia\Schematron\Compiler\Functions\SumFunction;
use MMNewmedia\Schematron\Compiler\Functions\XsDecimalFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CompileNodeSetAwareFunctionsTest extends TestCase
{
    public static function purePathExpressions(): array
    {
        return [
            'sum' => ['sum(ram:Amount)', SumFunction::class],
            'count' => ['count(ram:Amount)', CountFunction::class],
            'avg' => ['avg(ram:Amount)', AvgFunction::class],
            'min' => ['min(ram:Amount)', MinFunction::class],
            'max' => ['max(ram:Amount)', MaxFunction::class],
            'distinct-values' => ['distinct-values(ram:Amount)', DistinctValuesFunction::class],
        ];
    }

    /**
     * @dataProvider purePathExpressions
     */
    #[DataProvider('purePathExpressions')]
    public function testPurePathArgumentCompilesToNodeSetEvaluation(string $expression, string $functionClass): void
    {
        $compiler = new XPathAstCompiler([$functionClass]);

        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('$xpath->evaluate("ram:Amount", $context)', $code);
        $this->assertStringNotContainsString('string(ram:Amount)', $code);
        $this->assertStringContainsString('\DOMNodeList', $code);
        $this->assertStringContainsString('use ($context, $xpath)', $code);
    }

    public static function predicateExpressions(): array
    {
        return [
            'operator predicate' => [
                'count(//ram:SpecifiedLineTradeSettlement/ram:ApplicableTradeTax[ram:CategoryCode = \'S\'])',
                CountFunction::class,
                '//ram:SpecifiedLineTradeSettlement/ram:ApplicableTradeTax[(ram:CategoryCode) = (\'S\')]',
            ],
            'normalize-space predicate' => [
                'count(ram:AdditionalReferencedDocument[normalize-space(ram:TypeCode) = \'130\'])',
                CountFunction::class,
                'ram:AdditionalReferencedDocument[(normalize-space(ram:TypeCode)) = (130)]',
            ],
            'false() predicate' => [
                'count(//ram:ApplicableTradeTax[(ram:ChargeIndicator/udt:Indicator) = (false())])',
                CountFunction::class,
                '//ram:ApplicableTradeTax[(ram:ChargeIndicator/udt:Indicator) = (false())]',
            ],
        ];
    }

    /**
     * @dataProvider predicateExpressions
     */
    #[DataProvider('predicateExpressions')]
    public function testPredicateArgumentCompilesToNodeSetEvaluation(string $expression, string $functionClass, string $expectedPath): void
    {
        $compiler = new XPathAstCompiler([$functionClass]);

        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context'
        );

        $this->assertStringContainsString('$xpath->evaluate("' . $expectedPath . '", $context)', $code);
        $this->assertStringNotContainsString('string(' . $expectedPath, $code);
        $this->assertStringContainsString('\DOMNodeList', $code);
    }

    public function testTerminalXsDecimalStepFallsBackToScalarEvaluation(): void
    {
        $compiler = new XPathAstCompiler([
            SumFunction::class,
            XsDecimalFunction::class,
        ]);

        $code = $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse('sum(ram:TaxTotal/xs:decimal(ram:Amount))'),
            '$context'
        );

        $this->assertStringContainsString('$xpath->evaluate("ram:TaxTotal", $context)', $code);
        $this->assertStringContainsString('$xpath->evaluate("ram:Amount", $node)', $code);
        $this->assertStringNotContainsString('string(ram:TaxTotal/ram:Amount)', $code);
    }
}
