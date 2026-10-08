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
     * Kompiliert einen XPath Ausdruck zu ausführbarem PHP Code.
     * 
     * @param string $expression Der zu kompilierende XPath Ausdruck
     * @param string $context    PHP Ausdruck für den Kontextknoten (z.B. '$node')
     * @return string
     */
    public function compile(string $expression, string $context): string
    {
        // Normalisierung (z.B. Entfernen von Leerzeichen, Vereinheitlichung von Schreibweisen)
        $normalizedExpression = $this->normalizer->normalize($expression);

        // Stabiler Cache-Key (Hash des normalisierten Ausdrucks)
        $key = $this->buildCacheKey($normalizedExpression);

        // PHP-Code Cache prüfen
        if (isset($this->phpCache[$key])) {
            return $this->phpCache[$key];
        }

        // AST Cache - Parser wird nur einmalig ausgeführt, um den Ausdruck in einen AST zu übersetzen
        if (! isset($this->astCache[$key])) {
            $this->astCache[$key] = $this->parser->parse($normalizedExpression);
        }

        // AST wird in ausführbaren PHP Code kompiliert
        $php = $this->compiler->compile($this->astCache[$key], $context);
        
        // Ergebnis cachen und zurückgeben
        return $this->phpCache[$key] = $php;
    }

    /**
     * Kompiliert einen XPath Ausdruck zu einem PHP Ausdruck, der eine Node-Set
     * (DOMNodeList oder DOMNode[]) für den gegebenen Kontextknoten liefert.
     *
     * @param string $expression Der zu kompilierende XPath Ausdruck
     * @param string $context    PHP Ausdruck für den Kontextknoten (z.B. '$node')
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
     * Cache leeren (z.B. nach Änderungen an der Schematron-Datei oder bei Versionswechseln)
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->astCache = [];
        $this->phpCache = [];
    }

    /**
     * Erzeugt einen stabilen Cache-Key für den gegebenen normalisierten Ausdruck
     * 
     * @param string $normalizedExpression
     * @return string
     */
    protected function buildCacheKey(string $normalizedExpression): string
    {
        return hash('xxh3', $normalizedExpression);
    }
}