<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Parser;

use MMNewmedia\Schematron\Ast\AbstractNode;
use MMNewmedia\Schematron\Ast\AttributeNode;
use MMNewmedia\Schematron\Ast\AxisNode;
use MMNewmedia\Schematron\Ast\FunctionNode;
use MMNewmedia\Schematron\Ast\LiteralNode;
use MMNewmedia\Schematron\Ast\NodeTestNode;
use MMNewmedia\Schematron\Ast\OperatorNode;
use MMNewmedia\Schematron\Ast\PathNode;
use MMNewmedia\Schematron\Ast\PathOperatorNode;
use MMNewmedia\Schematron\Ast\PredicateNode;
use MMNewmedia\Schematron\Ast\VariableNode;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use RuntimeException;

class XPathAstParser
{
    public function __construct(
        private XPathTokenizer $tokenizer
    ) {}

    public function parse(string $expression): AbstractNode
    {
        $tokens = $this->tokenizer->tokenize($expression);
        return $this->parseOr($tokens);
    }

    // ─────────────────────────────────────────────
    // Operator-Priorität: or → and → comparison → additive → multiplicative → primary
    // ─────────────────────────────────────────────

    protected function parseOr(array &$tokens): AbstractNode
    {
        $node = $this->parseAnd($tokens);

        while ($this->nextIsOp($tokens, 'or')) {
            array_shift($tokens); // 'or'
            $right = $this->parseAnd($tokens);
            $node = new OperatorNode('or', $node, $right);
        }

        return $node;
    }

    protected function parseAnd(array &$tokens): AbstractNode
    {
        $node = $this->parseComparison($tokens);

        while ($this->nextIsOp($tokens, 'and')) {
            array_shift($tokens); // 'and'
            $right = $this->parseComparison($tokens);
            $node = new OperatorNode('and', $node, $right);
        }

        return $node;
    }

    protected function parseComparison(array &$tokens): AbstractNode
    {
        $node = $this->parseAdditive($tokens);

        while ($this->nextIsComparisonOp($tokens)) {
            $op = array_shift($tokens); // =, !=, <, >, <=, >=
            $right = $this->parseAdditive($tokens);
            
            $node = new OperatorNode($op['value'], $node, $right);
        }
        return $node;
    }

    protected function parseAdditive(array &$tokens): AbstractNode
    {
        $node = $this->parseMultiplicative($tokens);

        while ($this->nextIsOp($tokens, '+') || $this->nextIsOp($tokens, '-')) {
            $op = array_shift($tokens);
            $right = $this->parseMultiplicative($tokens);
            $node = new OperatorNode($op['value'], $node, $right);
        }

        return $node;
    }

    protected function parseMultiplicative(array &$tokens): AbstractNode
    {
        $node = $this->parsePath($tokens);

        while (
            $this->nextIsOp($tokens, '*') ||
            $this->nextIsOp($tokens, 'div') ||
            $this->nextIsOp($tokens, 'mod')
        ) {
            $op = array_shift($tokens);
            $right = $this->parsePrimary($tokens);
            $node = new OperatorNode($op['value'], $node, $right);
        }

        return $node;
    }

    protected function parsePrimary(array &$tokens): AbstractNode
    {
        $token = array_shift($tokens);

        // String-Literal
        if ($token['type'] === 'STRING') {
            $node = new LiteralNode($token['value']);
            return $this->parsePredicates($node, $tokens);
        }

        // Zahlen
        elseif ($token['type'] === 'NUMBER') {
            $node = new LiteralNode($token['value']);
            return $this->parsePredicates($node, $tokens);
        }

        // Variable: $name
        elseif ($token['type'] === 'VARIABLE') {
            $node = new VariableNode($token['value']);
            return $this->parsePredicates($node, $tokens);
        }

        // Attribut: @foo
        elseif ($token['type'] === 'ATTR') {
            $next = array_shift($tokens);
            if ($next['type'] !== 'NAME') {
                throw new RuntimeException("Expected attribute name after @");
            }
            $node = new AttributeNode($next['value']);
            return $this->parsePredicates($node, $tokens);
        }

        // Gruppierter Ausdruck: (expression)
        elseif ($token['type'] === 'CHAR' && $token['value'] === '(') {
            $node = $this->parseOr($tokens);

            while (isset($tokens[0]) && !($tokens[0]['type'] === 'CHAR' && $tokens[0]['value'] === ')')) {
                array_shift($tokens);
            }

            if (!isset($tokens[0]) || $tokens[0]['type'] !== 'CHAR' || $tokens[0]['value'] !== ')') {
                throw new RuntimeException("Missing closing parenthesis");
            }

            array_shift($tokens); // ')'
            return $this->parsePredicates($node, $tokens);
        }

        // Parent Axis: .. / Self Axis: .
        elseif ($token['type'] === 'AXIS') {
            $node = null;
            if ($token['value'] === '.') {
                $node = new AxisNode('self');
            }

            if ($token['value'] === '..') {
                $node = new AxisNode('parent');
            }

            if ($node instanceof AxisNode) {
                if ($this->nextIsPathOperator($tokens) === true) {
                    $operator = array_shift($tokens)['value'];
                    $right = $this->parsePrimary($tokens);
                    $node = new PathOperatorNode($operator, $node, $right);
                }

                return $this->parsePredicates($node, $tokens);
            }
        }

        // Wildcard path step: *
        elseif ($token['type'] === 'OP' && $token['value'] === '*') {
            $node = new PathNode('*');
            return $this->parsePredicates($node, $tokens);
        }

        // Root Path: /
        if ($token['type'] === 'PATH' && $token['value'] === '/') {

            // Ein Root-Pfad ist ein leerer linker Kontext
            $left = new PathNode('');

            // rechter Teil ist ein normaler Pfad
            $right = null;

            if ($this->nextIsPathOperator($tokens) || $this->isPathStepToken($tokens)) {
                $right = $this->parsePrimary($tokens);
            }

            // Wenn kein rechter Teil existiert, ist es einfach "/"
            if ($right === null) {
                return new PathNode('/');
            }

            // ansonsten: /foo → PathOperatorNode('/', '', foo)
            $node = new PathOperatorNode('/', $left, $right);

            return $this->parsePredicates($node, $tokens);
        }


        // Descendant-or-self: //
        if ($token['type'] === 'PATH' && $token['value'] === '//') {

            // linker Teil ist IMMER leer
            $left = new PathNode('');

            // rechter Teil ist ein normaler Pfad
            $right = $this->parsePrimary($tokens);

            $node = new PathOperatorNode('//', $left, $right);

            return $this->parsePredicates($node, $tokens);
        }

        // NAME => Pfad, Funktion, Achse, Node-Test
        if ($token['type'] === 'NAME') {
            $node = $this->parsePathOrName($token, $tokens);
            return $this->parsePredicates($node, $tokens);
        }

        throw new RuntimeException(sprintf(
            "Unexpected token: %s",
            $token['type'],
        ));
    }

    protected function parsePath(array &$tokens): AbstractNode
    {
        $node = $this->parsePrimary($tokens);

        while ($this->nextIsPathOperator($tokens)) {
            $operator = array_shift($tokens)['value']; // '/' oder '//'
            $right = $this->parsePrimary($tokens);

            $node = new PathOperatorNode($operator, $node, $right);
        }

        return $node;
    }

    private function parsePathOrName(array $firstToken, array &$tokens): AbstractNode
    {
        $name = $firstToken['value'];

        // Axis: child::ram:ClassCode
        if ($this->nextIsAxisSeparator($tokens) === true) {
            array_shift($tokens); // '::'
            $nameToken = array_shift($tokens);
            if ($nameToken['type'] !== 'NAME') {
                throw new RuntimeException("Expected node name after axis::");
            }
            return new AxisNode($name, $nameToken['value']);
        }

        // Namespaced wildcard node test: ram:*
        // Only treat NAME + '*' as a wildcard when NAME is a bare namespace
        // prefix (e.g. "ram:"), not a fully qualified element name, so that
        // "ram:ChargeTotalAmount * 10" keeps the '*' as a multiplication operator.
        if (str_ends_with($name, ':') && $this->nextIsOp($tokens, '*') === true) {
            array_shift($tokens); // '*'
            return new PathNode($name . '*');
        }

        // Node-Test: text(), node(), comment() – optional, wenn du sie speziell behandeln willst
        if ($this->nextIsNodeTest($name, $tokens) === true) {
            array_shift($tokens); // '('
            array_shift($tokens); // ')'
            return new NodeTestNode($name);
        }

        // Funktionsaufruf: normalize-space(...)
        if ($this->nextIsOpenParen($tokens) === true) {
            return $this->parseFunctionCall($name, $tokens);
        }

        // Normaler Pfadname
        return new PathNode($name);
    }

    protected function parseFunctionCall(string $name, array &$tokens): AbstractNode
    {
        array_shift($tokens); // '('

        $args = [];
        while ($this->nextIsCloseParen($tokens) === false) {
            $args[] = $this->parseOr($tokens);

            if ($this->nextIsComma($tokens)) {
                array_shift($tokens); // ','
            }
        }

        array_shift($tokens); // ')'

        return new FunctionNode($name, $args);
    }

    protected function nextIsOp(array $tokens, string $value): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'OP'
            && $tokens[0]['value'] === $value;
    }

    protected function nextIsComparisonOp(array $tokens): bool
    {
        if (! isset($tokens[0]) || $tokens[0]['type'] !== 'OP') {
            return false;
        }

        return in_array($tokens[0]['value'], ['=', '!=', '<', '>', '<=', '>='], true);
    }

    protected function nextIsOpenParen(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'CHAR'
            && $tokens[0]['value'] === '(';
    }

    protected function nextIsCloseParen(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'CHAR'
            && $tokens[0]['value'] === ')';
    }

    protected function nextIsComma(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'CHAR'
            && $tokens[0]['value'] === ',';
    }

    protected function nextIsPredicateOpen(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'PRED_OPEN';
    }

    protected function nextIsPredicateClose(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'PRED_CLOSE';
    }

    protected function nextIsAxisSeparator(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'AXIS_SEP';
    }

    protected function nextIsNodeTest(string $name, array $tokens): bool
    {
        $nodeTests = ['text', 'node', 'comment', 'processing-instruction'];

        return in_array($name, $nodeTests, true)
            && isset($tokens[0])
            && $tokens[0]['type'] === 'CHAR'
            && $tokens[0]['value'] === '('
            && isset($tokens[1])
            && $tokens[1]['type'] === 'CHAR'
            && $tokens[1]['value'] === ')';
    }

    protected function nextIsPathOperator(array $tokens): bool
    {
        return isset($tokens[0])
            && $tokens[0]['type'] === 'PATH'
            && ($tokens[0]['value'] === '/' || $tokens[0]['value'] === '//');
    }

    protected function isPathStepToken(array $tokens): bool
    {
        if (!isset($tokens[0])) {
            return false;
        }

        return $tokens[0]['type'] === 'NAME'
            || ($tokens[0]['type'] === 'OP' && $tokens[0]['value'] === '*');
    }

    protected function parsePredicates(AbstractNode $node, array &$tokens): AbstractNode
    {
        while ($this->nextIsPredicateOpen($tokens)) {
            array_shift($tokens); // '['
            $predicateExpr = $this->parseOr($tokens);

            if (!$this->nextIsPredicateClose($tokens)) {
                throw new RuntimeException("Missing closing ] in predicate");
            }

            array_shift($tokens); // ']'

            $node = new PredicateNode($node, $predicateExpr);
        }

        return $node;
    }
}
