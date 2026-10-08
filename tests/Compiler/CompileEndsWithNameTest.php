<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use DOMDocument;
use DOMXPath;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\Functions\EndsWithFunction;
use MMNewmedia\Schematron\Compiler\Functions\NameFunction;
use MMNewmedia\Schematron\Compiler\Functions\NotFunction;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use PHPUnit\Framework\TestCase;

class CompileEndsWithNameTest extends TestCase
{
    private function compile(string $expression, string $context = '$context'): string
    {
        $compiler = new XPathAstCompiler([
            EndsWithFunction::class,
            NameFunction::class,
            NotFunction::class,
        ]);

        return $compiler->compile(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            $context,
        );
    }

    private function compileNodeSet(string $expression): string
    {
        $compiler = new XPathAstCompiler([
            EndsWithFunction::class,
            NameFunction::class,
            NotFunction::class,
        ]);

        return $compiler->compileNodeSet(
            (new XPathAstParser(new XPathTokenizer()))->parse($expression),
            '$context',
        );
    }

    public function testEndsWithNameCompilesToPhp(): void
    {
        $code = $this->compile("ends-with(name(), 'TradeTax')");

        $this->assertStringContainsString('nodeName', $code);
        $this->assertStringContainsString("-strlen((string) 'TradeTax'))", $code);
        $this->assertStringNotContainsString("name()", $code);
        $this->assertStringNotContainsString('ends-with(', $code);
    }

    public function testNamespacedWildcardContextCompilesToNodeSetPipeline(): void
    {
        $code = $this->compileNodeSet("//ram:*[ends-with(name(), 'ID')]");

        $this->assertStringContainsString('".//ram:*"', $code);
        $this->assertStringContainsString('nodeName', $code);
        $this->assertStringContainsString('-strlen((string) \'ID\'))', $code);
    }

    public function testPlainWildcardContextWithEndsWithName(): void
    {
        $code = $this->compileNodeSet("//*[ends-with(name(), 'DocumentContextParameter')]");

        $this->assertStringContainsString('".//*"', $code);
        $this->assertStringContainsString('nodeName', $code);
    }

    public function testCompiledNodeSetFiltersByNameSuffix(): void
    {
        $document = new DOMDocument();
        $document->loadXML(
            '<root xmlns:ram="urn:test">
                <ram:TradeTax/>
                <ram:TradeTax/>
                <ram:TaxTotalAmount/>
                <ram:Other/>
            </root>'
        );
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ram', 'urn:test');

        $code = $this->compileNodeSet("//ram:*[ends-with(name(), 'TradeTax')]");
        $evaluator = eval('return function(DOMXPath $xpath, DOMNode $context) { return (' . $code . '); };');
        $nodes = $evaluator($xpath, $document->documentElement);

        $this->assertCount(2, $nodes);
        foreach ($nodes as $node) {
            $this->assertSame('ram:TradeTax', $node->nodeName);
        }
    }

    public function testExpressionCompilerCompilesNodeSet(): void
    {
        $expressionCompiler = new ExpressionCompiler(
            new XPathAstParser(new XPathTokenizer()),
            new XPathAstCompiler([EndsWithFunction::class, NameFunction::class]),
            new ExpressionNormalizer(),
        );

        $code = $expressionCompiler->compileNodeSet(
            "//ram:*[ends-with(name(), 'Quantity')]",
            '$context',
        );

        $this->assertStringContainsString('".//ram:*"', $code);
        $this->assertStringContainsString('nodeName', $code);
    }
}
