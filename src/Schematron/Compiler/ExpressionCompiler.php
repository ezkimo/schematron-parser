<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use MMNewmedia\Schematron\Parser\XPathAstParser;

final class ExpressionCompiler
{
    /**
     * @var array<string, \MMNewmedia\Schematron\Ast\AbstractNode>
     */
    protected array $astCache = [];

    /**
     * @var array<string, string>
     */
    protected array $phpCache = [];

    public function __construct(
        protected XPathAstParser $parser,
        protected XPathAstCompiler $compiler,
        protected ExpressionNormalizer $normalizer,
    ) {}

    /**
     * Compiles an XPath expression into executable PHP code.
     *
     * @param string $expression The XPath expression to compile
     * @param string $context    PHP expression for the context node (e.g. '$node')
     * @return string
     */
    public function compile(string $expression, string $context): string
    {
        // Normalize (e.g. remove whitespace, unify spellings)
        $normalizedExpression = $this->normalizer->normalize($expression);

        // Stable cache key (hash of the normalized expression)
        $key = $this->buildCacheKey($normalizedExpression);

        // Check PHP code cache
        if (isset($this->phpCache[$key])) {
            return $this->phpCache[$key];
        }

        // AST cache - the parser only runs once to translate the expression into an AST
        if (! isset($this->astCache[$key])) {
            $this->astCache[$key] = $this->parser->parse($normalizedExpression);
        }

        // Compile the AST into executable PHP code
        $php = $this->compiler->compile($this->astCache[$key], $context);

        // Cache and return the result
        return $this->phpCache[$key] = $php;
    }

    /**
     * Compiles an XPath expression into a PHP expression that evaluates a node-set
     * (DOMNodeList or DOMNode[]) for the given context node.
     *
     * @param string $expression The XPath expression to compile
     * @param string $context    PHP expression for the context node (e.g. '$node')
     * @return string
     */
    public function compileNodeSet(string $expression, string $context): string
    {
        $normalizedExpression = $this->normalizer->normalize($expression);

        $key = $this->buildCacheKey($normalizedExpression . '|nodeset');

        if (isset($this->phpCache[$key])) {
            return $this->phpCache[$key];
        }

        if (! isset($this->astCache[$key])) {
            $this->astCache[$key] = $this->parser->parse($normalizedExpression);
        }

        $php = $this->compiler->compileNodeSet($this->astCache[$key], $context);

        return $this->phpCache[$key] = $php;
    }

    /**
     * Clear the cache (e.g. after changes to the Schematron file or on version upgrade)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->astCache = [];
        $this->phpCache = [];
        $this->normalizer->clearCache();
    }

    /**
     * Builds a stable cache key for the given normalized expression
     *
     * @param string $normalizedExpression
     * @return string
     */
    protected function buildCacheKey(string $normalizedExpression): string
    {
        return hash('xxh3', $normalizedExpression);
    }
}