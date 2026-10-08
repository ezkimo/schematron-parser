<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use DOMDocument;
use DOMXPath;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

/**
 * Node-set equality (XPath 1.0) must be evaluated by the XPath engine, not by
 * comparing string() casts. Two empty node-sets are NOT equal in XPath 1.0 —
 * comparing them via string(...) in PHP produced '' == '' / true, which broke
 * rules like BR-17 ("payee differs from seller" fails when neither party has a
 * SpecifiedLegalOrganization).
 */
class CompileNodeSetEqualityTest extends TestCase
{
    private function compiler(): XPathAstCompiler
    {
        return new XPathAstCompiler([]);
    }

    private function parser(): XPathAstParser
    {
        return new XPathAstParser(new XPathTokenizer());
    }

    public function testNodeSetEqualityIsDelegatedToXPathEngine(): void
    {
        $php = $this->compiler()->compile(
            $this->parser()->parse('ram:ID = ../ram:SellerTradeParty/ram:ID'),
            '$context',
        );

        $this->assertSame('$xpath->evaluate("(ram:ID) = (../ram:SellerTradeParty/ram:ID)", $context)', $php);
    }

    public function testNodeSetInequalityIsDelegatedToXPathEngine(): void
    {
        $php = $this->compiler()->compile(
            $this->parser()->parse('ram:ID != ../ram:SellerTradeParty/ram:ID'),
            '$context',
        );

        $this->assertSame('$xpath->evaluate("(ram:ID) != (../ram:SellerTradeParty/ram:ID)", $context)', $php);
    }

    public function testMultiStepNodeSetEqualityIsDelegatedToXPathEngine(): void
    {
        $php = $this->compiler()->compile(
            $this->parser()->parse('ram:SpecifiedLegalOrganization/ram:ID = ../ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID'),
            '$context',
        );

        $this->assertSame(
            '$xpath->evaluate("(ram:SpecifiedLegalOrganization/ram:ID) = (../ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)", $context)',
            $php,
        );
    }

    public function testLiteralComparisonKeepsStringCompilation(): void
    {
        $php = $this->compiler()->compile(
            $this->parser()->parse("ram:TypeCode = '380'"),
            '$context',
        );

        $this->assertSame('($xpath->evaluate("string(ram:TypeCode)", $context)) == (380)', $php);
    }

    public function testNotOperatorDelegatesNestedNodeSetEquality(): void
    {
        $php = $this->fullCompiler()->compile(
            'not(ram:SpecifiedLegalOrganization/ram:ID = ../ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)',
            '$context',
        );

        $this->assertSame(
            '!($xpath->evaluate("(ram:SpecifiedLegalOrganization/ram:ID) = (../ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)", $context))',
            $php,
        );
    }

    public function testEmptyNodeSetsAreNotEqualWhenEvaluated(): void
    {
        [$xpath, $payee] = $this->facturXFixture();
        $context = $payee;
        $php = $this->fullCompiler()->compile(
            'not(ram:SpecifiedLegalOrganization/ram:ID = ../ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)',
            '$context',
        );

        $result = eval('return ' . $php . ';');

        $this->assertTrue($result);
    }

    public function testDifferentNamesAreNotEqualWhenEvaluated(): void
    {
        [$xpath, $payee] = $this->facturXFixture();
        $context = $payee;
        $php = $this->fullCompiler()->compile(
            'ram:Name = ../ram:SellerTradeParty/ram:Name',
            '$context',
        );

        $result = eval('return ' . $php . ';');

        $this->assertFalse($result);
    }

    private function fullCompiler(): ExpressionCompiler
    {
        $functions = (new FunctionLoader(
            dirname(__DIR__) . '/../src/Schematron/Compiler/Functions',
            'MMNewmedia\\Schematron\\Compiler\\Functions',
        ))->load();

        return new ExpressionCompiler(
            new XPathAstParser(new XPathTokenizer()),
            new XPathAstCompiler($functions),
            new ExpressionNormalizer(),
        );
    }

    /**
     * @return array{DOMXPath, \DOMNode}
     */
    private function facturXFixture(): array
    {
        $document = new DOMDocument();
        $document->load(dirname(__DIR__) . '/../data/example/factur-x.xml');

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace(
            'ram',
            'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100'
        );

        return [$xpath, $xpath->query('//ram:PayeeTradeParty')->item(0)];
    }
}