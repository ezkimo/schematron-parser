<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use MMNewmedia\Schematron\Ast\AbstractNode;
use MMNewmedia\Schematron\Ast\AttributeNode;
use MMNewmedia\Schematron\Ast\AxisNode;
use MMNewmedia\Schematron\Ast\FunctionNode;
use MMNewmedia\Schematron\Ast\LiteralNode;
use MMNewmedia\Schematron\Ast\NodeTestNode;
use MMNewmedia\Schematron\Ast\OperatorNode;
use MMNewmedia\Schematron\Ast\PathNode;
use MMNewmedia\Schematron\Ast\PathOperatorNode;
use MMNewmedia\Schematron\Ast\PhpNode;
use MMNewmedia\Schematron\Ast\PredicateNode;
use MMNewmedia\Schematron\Ast\VariableNode;
use MMNewmedia\Schematron\Compiler\Functions\NodeSetAwareFunctionInterface;
use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;
use ReflectionClass;
use RuntimeException;

final class XPathAstCompiler
{
    /** XPath 1.0 core function names that DOMXPath can evaluate. */
    private const XPATH_1_0_FUNCTIONS = [
        'last',
        'position',
        'count',
        'id',
        'local-name',
        'namespace-uri',
        'name',
        'string',
        'concat',
        'starts-with',
        'contains',
        'substring-before',
        'substring-after',
        'substring',
        'string-length',
        'normalize-space',
        'translate',
        'boolean',
        'not',
        'true',
        'false',
        'lang',
        'number',
        'sum',
        'floor',
        'ceiling',
        'round',
    ];

    /** @var array<class-string<XPathFunctionInterface>, XPathFunctionInterface> */
    protected array $functionInstances = [];

    /** @param class-string<XPathFunctionInterface>[] $functions */
    public function __construct(
        protected readonly array $functions
    ) {}

    public function compile(AbstractNode $node, string $context): string
    {   
        if ($node instanceof PhpNode) {
            return $node->code;
        }

        $codedbCompiled = $this->tryCompileDocumentCodeList($node, $context);
        if ($codedbCompiled !== null) {
            return $codedbCompiled;
        }

        if ($node instanceof LiteralNode) {
            return is_numeric($node->value) ? $node->value : "'" . $node->value . "'";
        }

        if ($node instanceof OperatorNode) {
            if (
                in_array($node->operator, ['=', '!='], true)
                && $this->isNodesetEvaluableOperand($node->left)
                && $this->isNodesetEvaluableOperand($node->right)
            ) {
                $leftXPath = $this->compilePath($node->left, $context);
                $rightXPath = $this->compilePath($node->right, $context);

                return "\$xpath->evaluate(\"({$leftXPath}) {$node->operator} ({$rightXPath})\", {$context})";
            }

            $left = $this->compile($node->left, $context);
            $right = $this->compile($node->right, $context);

            $hasDecimal = $this->containsXsDecimal($node->left)
                || $this->containsXsDecimal($node->right);

            return match ($node->operator) {
                'and'   => "({$left}) && ({$right})",
                'or'    => "({$left}) || ({$right})",
                '='     => $hasDecimal
                    ? "round((float) ({$left}) * 100) / 100 == round((float) ({$right}) * 100) / 100"
                    : "({$left}) == ({$right})",
                '!='    => $hasDecimal
                    ? "round((float) ({$left}) * 100) / 100 != round((float) ({$right}) * 100) / 100"
                    : "({$left}) != ({$right})",
                '>'     => "({$left}) > ({$right})",
                '<'     => "({$left}) < ({$right})",
                '>='    => "({$left}) >= ({$right})",
                '<='    => "({$left}) <= ({$right})",
                '+'     => "(float) ({$left}) + (float) ({$right})",
                '-'     => "(float) ({$left}) - (float) ({$right})",
                '*'     => "(float) ({$left}) * (float) ({$right})",
                'div'   => "(float) ({$left}) / (float) ({$right})",
                'mod'   => "(float) ({$left}) % (float) ({$right})",
                default => throw new RuntimeException(sprintf(
                    "Unknown operator %s",
                    $node->operator
                )),
            };
        }

        if ($node instanceof FunctionNode) {
            return $this->compileFunction($node, $context);
        }

        if ($node instanceof VariableNode) {
            $phpName = '$' . $node->name;
            return "(is_array({$phpName}) ? \MMNewmedia\Schematron\Compiler\XPathValue::stringValue({$phpName}) : ({$phpName} ?? ''))";
        }

        if ($node instanceof PathOperatorNode) {
            return $this->compilePathOperator($node, $context);
        }

        if (
            $node instanceof PathNode ||
            $node instanceof PredicateNode ||
            $node instanceof AxisNode ||
            $node instanceof AttributeNode ||
            $node instanceof NodeTestNode
        ) {
            if (!$this->isNodeSetEvaluablePath($node)) {
                return "\MMNewmedia\Schematron\Compiler\XPathValue::stringValue({$this->compileNodeSet($node, $context)})";
            }

            $xpath = $this->compilePath($node, $context);
            return "\$xpath->evaluate(\"string({$xpath})\", {$context})";
        }

        throw new RuntimeException(sprintf(
            'Unknown node type: %s', 
            get_class($node)
        ));
    }

    /**
     * Recognizes the Factur-X code-list membership check
     *
     *   document('FILE_codedb.xml')/codedb/cl[@id=N]/enumeration[@value=$var]
     *
     * and compiles it to a runtime CodedbCodeList lookup.
     *
     * @return string|null compiled PHP, or null when the node is not this pattern
     */
    protected function tryCompileDocumentCodeList(AbstractNode $node, string $context): ?string
    {
        if (!$node instanceof PathOperatorNode) {
            return null;
        }

        $bottom = $node;
        while ($bottom instanceof PathOperatorNode) {
            $bottom = $bottom->left;
        }

        if (!$bottom instanceof FunctionNode
            || $bottom->name !== 'document'
            || count($bottom->arguments) !== 1
            || !$bottom->arguments[0] instanceof LiteralNode
        ) {
            return null;
        }

        // Chain = document('FILE') / codedb / cl[@id=N] / enumeration[@value=...]
        // Expected shapes of the three right-hand steps:
        //   1. PathNode('codedb')
        //   2. PredicateNode(PathNode('cl'), @id=N)
        //   3. PredicateNode(PathNode('enumeration'), @value=...)
        $steps = $this->collectDocumentSteps($node);
        if (count($steps) !== 3) {
            return null;
        }

        [$codedb, $cl, $enumeration] = $steps;

        if (!$codedb instanceof PathNode || $codedb->path !== 'codedb') {
            return null;
        }

        $clId = $this->matchAttributeLiteralPredicate($cl, 'cl', 'id');
        if ($clId === null) {
            return null;
        }

        $valueExpr = $this->matchValuePredicateNode($enumeration);
        if ($valueExpr === null) {
            return null;
        }

        $filename = $bottom->arguments[0]->value;
        $valuePhp = $this->compile($valueExpr, $context);

        return sprintf(
            '\\MMNewmedia\\Schematron\\Compiler\\CodedbCodeList::has(%s, %d, %s)',
            var_export($filename, true),
            $clId,
            $valuePhp
        );
    }

    /**
     * Unwraps the left-deep chain into the right-hand path steps,
     * returning [codedb, cl[@id=N], enumeration[@value=...]].
     *
     * @return array{0: AbstractNode, 1: AbstractNode, 2: AbstractNode}
     */
    protected function collectDocumentSteps(PathOperatorNode $node): array
    {
        $steps = [];
        $current = $node;

        while ($current instanceof PathOperatorNode) {
            array_unshift($steps, $current->right);
            $current = $current->left;
        }

        return $steps;
    }

    /**
     * Returns the numeric literal when $node is a
     * PredicateNode(PathNode($stepName), @$attr = NUMBER), else null.
     */
    protected function matchAttributeLiteralPredicate(AbstractNode $node, string $stepName, string $attr): ?int
    {
        if (!$node instanceof PredicateNode
            || !$node->path instanceof PathNode
            || $node->path->path !== $stepName
            || !$node->predicate instanceof OperatorNode
            || $node->predicate->operator !== '='
            || !$node->predicate->left instanceof AttributeNode
            || $node->predicate->left->name !== $attr
            || !$node->predicate->right instanceof LiteralNode
            || !is_numeric($node->predicate->right->value)
        ) {
            return null;
        }

        return (int) $node->predicate->right->value;
    }

    /**
     * Returns the predicate's right-hand expression when $node is
     * PredicateNode(PathNode('enumeration'), @value = <expr>), else null.
     */
    protected function matchValuePredicateNode(AbstractNode $node): ?AbstractNode
    {
        if (!$node instanceof PredicateNode
            || !$node->path instanceof PathNode
            || $node->path->path !== 'enumeration'
            || !$node->predicate instanceof OperatorNode
            || $node->predicate->operator !== '='
            || !$node->predicate->left instanceof AttributeNode
            || $node->predicate->left->name !== 'value'
        ) {
            return null;
        }

        return $node->predicate->right;
    }

    protected function compilePathOperator(PathOperatorNode $node, string $context): string
    {
        $leftXpath = $this->compilePath($node->left, $context);

        if ($node->right instanceof FunctionNode) {
            return $this->compilePathStepFunction($node->right, $node->left, $leftXpath, $node->operator, $context);
        }

        if (!$this->isNodeSetEvaluablePath($node)) {
            return "\MMNewmedia\Schematron\Compiler\XPathValue::stringValue({$this->compileNodeSet($node, $context)})";
        }

        $rightXpath = $this->compilePath($node->right, $context);
        $xpath = "{$leftXpath}{$node->operator}{$rightXpath}";

        return "\$xpath->evaluate(\"string({$xpath})\", {$context})";
    }

    protected function compilePathStepFunction(FunctionNode $node, AbstractNode $left, string $leftXpath, string $operator, string $context): string
    {
        foreach ($this->functions as $fqcn) {
            $function = $this->getFunctionInstance($fqcn);
            if ($function->supports($node->name)) {
                $phpArgs = [];
                foreach ($node->arguments as $argument) {
                    if (
                        $function instanceof NodeSetAwareFunctionInterface
                        && $this->isPathTypeNode($argument)
                    ) {
                        $phpArgs[] = $this->compileStepFunctionNodeSet($left, $argument, $operator, $context);
                    } else {
                        $phpArgs[] = $this->compileStepFunctionScalar($left, $argument, $operator, $context);
                    }
                }
                return $function->compile($phpArgs, $context);
            }
        }

        $rightXpath = $this->compilePath($node, $context);
        $xpath = "{$leftXpath}{$operator}{$rightXpath}";

        return "\$xpath->evaluate(\"string({$xpath})\", {$context})";
    }

    protected function compileFunction(FunctionNode $node, string $context): string
    {
        $name = $node->name;

        foreach ($this->functions as $fqcn) {
            $function = $this->getFunctionInstance($fqcn);
            if ($function->supports($name)) {
                $arguments = $this->compileFunctionArguments($node, $context, $function);
                return $function->compile($arguments, $context);
            }
        }

        throw new RuntimeException(sprintf(
            "No function handler for '%s'",
            $name
        ));
    }

    protected function compileFunctionArguments(FunctionNode $node, string $context, XPathFunctionInterface $function): array
    {
        $arguments = [];

        foreach ($node->arguments as $argumentNode) {
            if (
                $function instanceof NodeSetAwareFunctionInterface
                && $this->isPathTypeNode($argumentNode)
            ) {
                if ($this->isNodeSetEvaluablePath($argumentNode)) {
                    $xpath = $this->compilePath($argumentNode, $context);
                    $arguments[] = "\$xpath->evaluate(\"{$xpath}\", {$context})";
                } elseif ($this->containsNonOnePointZeroPredicate($argumentNode)) {
                    $arguments[] = $this->compileNodeSet($argumentNode, $context);
                } else {
                    $arguments[] = $this->compile($argumentNode, $context);
                }

                continue;
            }

            $arguments[] = $this->compile($argumentNode, $context);
        }

        return $arguments;
    }

    protected function isPathTypeNode(AbstractNode $node): bool
    {
        return $node instanceof PathNode
            || $node instanceof PathOperatorNode
            || $node instanceof PredicateNode
            || $node instanceof AxisNode
            || $node instanceof AttributeNode
            || $node instanceof NodeTestNode;
    }

    /**
     * True when the AST subtree contains at least one xs:decimal() function call,
     * indicating the expression operates on decimal values that need rounding to
     * avoid IEEE 754 floating-point precision drift in == / != comparisons.
     */
    private function containsXsDecimal(AbstractNode $node): bool
    {
        if ($node instanceof FunctionNode && $node->name === 'xs:decimal') {
            return true;
        }

        if ($node instanceof OperatorNode) {
            return $this->containsXsDecimal($node->left)
                || $this->containsXsDecimal($node->right);
        }

        if ($node instanceof PathOperatorNode) {
            return $this->containsXsDecimal($node->left)
                || $this->containsXsDecimal($node->right);
        }

        if ($node instanceof PredicateNode) {
            return $this->containsXsDecimal($node->path)
                || $this->containsXsDecimal($node->predicate);
        }

        return false;
    }

    /**
     * True when a comparison operand is a plain location path that DOMXPath can
     * evaluate itself. Comparisons between two such operands are delegated to the
     * XPath engine so node-set semantics (existential equality, empty set = false)
     * are preserved instead of comparing string() casts in PHP.
     */
    protected function isNodesetEvaluableOperand(AbstractNode $node): bool
    {
        if (!$this->isNodeSetEvaluablePath($node)) {
            return false;
        }

        if ($node instanceof PathNode && $node->path === '') {
            return false;
        }

        return $this->isPathTypeNode($node);
    }

    protected function isNodeSetEvaluablePath(AbstractNode $node): bool
    {
        if ($node instanceof FunctionNode) {
            return in_array($node->name, self::XPATH_1_0_FUNCTIONS, true)
                && $this->areNodesSetEvaluable($node->arguments);
        }

        if ($node instanceof PathOperatorNode) {
            if ($node->left instanceof FunctionNode || $node->right instanceof FunctionNode) {
                return false;
            }

            return $this->isNodeSetEvaluablePath($node->left)
                && $this->isNodeSetEvaluablePath($node->right);
        }

        if ($node instanceof OperatorNode) {
            return $this->isNodeSetEvaluablePath($node->left)
                && $this->isNodeSetEvaluablePath($node->right);
        }

        if ($node instanceof PredicateNode) {
            return $this->isNodeSetEvaluablePath($node->path)
                && $this->isNodeSetEvaluablePath($node->predicate);
        }

        if (
            $node instanceof LiteralNode
            || $node instanceof PathNode
            || $node instanceof AxisNode
            || $node instanceof AttributeNode
            || $node instanceof NodeTestNode
        ) {
            return true;
        }

        return false;
    }

    /** @param AbstractNode[] $nodes */
    protected function areNodesSetEvaluable(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (!$this->isNodeSetEvaluablePath($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when the path contains a predicate using a function that DOMXPath
     * (XPath 1.0) cannot evaluate, e.g. `[upper-case(...) = 'VAT']`.
     */
    protected function containsNonOnePointZeroPredicate(AbstractNode $node): bool
    {
        if ($node instanceof PredicateNode) {
            if (!$this->isNodeSetEvaluablePath($node->predicate)) {
                return true;
            }

            return $this->containsNonOnePointZeroPredicate($node->path);
        }

        if ($node instanceof PathOperatorNode || $node instanceof OperatorNode) {
            return $this->containsNonOnePointZeroPredicate($node->left)
                || $this->containsNonOnePointZeroPredicate($node->right);
        }

        if ($node instanceof FunctionNode) {
            foreach ($node->arguments as $argument) {
                if ($this->containsNonOnePointZeroPredicate($argument)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compiles a path expression into PHP code producing a node-set
     * (DOMNodeList or array of DOMNode). Falls back to a PHP pipeline when the
     * path uses XPath 2.0 functions that DOMXPath cannot evaluate.
     */
    public function compileNodeSet(AbstractNode $node, string $context): string
    {
        if ($node instanceof PathNode && $node->path === '') {
            return "[{$context}]";
        }

        if ($this->isNodeSetEvaluablePath($node)) {
            return "\$xpath->evaluate(\"{$this->compilePath($node, $context)}\", {$context})";
        }

        if ($node instanceof PredicateNode) {
            return $this->filterPredicate(
                $node->predicate,
                $this->compileNodeSet($node->path, $context)
            );
        }

        if ($node instanceof PathOperatorNode) {
            return $this->applySteps(
                $node->right,
                $node->operator,
                $this->compileNodeSet($node->left, $context)
            );
        }

        throw new RuntimeException(sprintf(
            'Cannot compile non-evaluable node-set for node type: %s',
            get_class($node)
        ));
    }

    /**
     * Applies a relative path step to every node of a node-set expression.
     *
     * @param string $nodesExpr PHP expression evaluating to an iterable of DOMNode
     */
    protected function applySteps(AbstractNode $node, string $operator, string $nodesExpr): string
    {
        if ($node instanceof PathNode && $node->path === '') {
            return $nodesExpr;
        }

        if ($this->isNodeSetEvaluablePath($node)) {
            $path = $this->compilePath($node, '');
            $stepPath = $operator === '//' ? ".//{$path}" : $path;

            return <<<PHP
            (function(DOMXPath \$xpath, iterable \$nodes) {
                \$result = [];
                foreach (\$nodes as \$node) {
                    \$values = \$xpath->evaluate("{$stepPath}", \$node);
                    if (\$values instanceof \DOMNodeList) {
                        foreach (\$values as \$value) {
                            \$result[] = \$value;
                        }
                    } elseif (is_array(\$values)) {
                        foreach (\$values as \$value) {
                            \$result[] = \$value;
                        }
                    } elseif (\$values instanceof \DOMNode) {
                        \$result[] = \$values;
                    }
                }
                return \$result;
            })(\$xpath, {$nodesExpr})
            PHP;
        }

        if ($node instanceof PredicateNode) {
            return $this->filterPredicate(
                $node->predicate,
                $this->applySteps($node->path, $operator, $nodesExpr)
            );
        }

        if ($node instanceof PathOperatorNode) {
            return $this->applySteps(
                $node->right,
                $node->operator,
                $this->applySteps($node->left, $operator, $nodesExpr)
            );
        }

        if ($node instanceof FunctionNode || $node instanceof OperatorNode) {
            $php = $this->compile($node, '$node');

            return <<<PHP
            (function(DOMXPath \$xpath, iterable \$nodes) {
                \$result = [];
                foreach (\$nodes as \$node) {
                    \$result[] = ({$php});
                }
                return \$result;
            })(\$xpath, {$nodesExpr})
            PHP;
        }

        throw new RuntimeException(sprintf(
            'Cannot apply non-evaluable path step of type: %s',
            get_class($node)
        ));
    }

    /**
     * Filters a node-set expression by a predicate compiled against each node.
     *
     * @param string $nodesExpr PHP expression evaluating to an iterable of DOMNode
     */
    protected function filterPredicate(AbstractNode $predicate, string $nodesExpr): string
    {
        $php = $this->compile($predicate, '$node');

        return <<<PHP
        (function(DOMXPath \$xpath, iterable \$nodes) {
            \$result = [];
            foreach (\$nodes as \$node) {
                if ({$php}) {
                    \$result[] = \$node;
                }
            }
            return \$result;
        })(\$xpath, {$nodesExpr})
        PHP;
    }

    protected function compileStepFunctionNodeSet(AbstractNode $left, AbstractNode $argument, string $operator, string $context): string
    {
        return $this->applySteps(
            $argument,
            $operator,
            $this->compileNodeSet($left, $context)
        );
    }

    protected function compileStepFunctionScalar(AbstractNode $left, AbstractNode $argument, string $operator, string $context): string
    {
        $leftNodes = $this->compileNodeSet($left, $context);

        if ($this->isPathTypeNode($argument)) {
            $argXpath = $this->compilePath($argument, $context);
            $stepPath = $operator === '//' ? ".//{$argXpath}" : $argXpath;
            $argPhp = "\$xpath->evaluate(\"{$stepPath}\", \$node)";
        } else {
            $argPhp = $this->compile($argument, '$node');
        }

        return <<<PHP
        (function(DOMXPath \$xpath, iterable \$nodes) {
            foreach (\$nodes as \$node) {
                \$value = {$argPhp};
                if (\$value instanceof \DOMNode) {
                    return \$value->textContent;
                }
                if (\$value instanceof \DOMNodeList) {
                    foreach (\$value as \$item) {
                        return \$item->textContent;
                    }
                    continue;
                }
                if (is_array(\$value)) {
                    foreach (\$value as \$item) {
                        return (string) \$item;
                    }
                    continue;
                }
                if (\$value !== null && \$value !== '') {
                    return (string) \$value;
                }
            }
            return '';
        })(\$xpath, {$leftNodes})
        PHP;
    }

    protected function compilePath(AbstractNode $node, string $context): string
    {
        if ($node instanceof PathNode) {
            return $node->path;
        }

        if ($node instanceof AxisNode) {
            if ($node->axis === 'self') {
                return '.';
            }

            if ($node->axis === 'parent') {
                return '..';
            }
            
            return $node->axis . '::' . $node->name;
        }

        if ($node instanceof AttributeNode) {
            return '@' . $node->name;
        }

        if ($node instanceof NodeTestNode) {
            return $node->name . '()';
        }

        if ($node instanceof LiteralNode) {
            return is_numeric($node->value) === true  
                ? $node->value 
                : "'{$node->value}'";
        }

        if ($node instanceof OperatorNode) {
            $left  = $this->compilePath($node->left, $context);
            $right = $this->compilePath($node->right, $context);

            return "({$left}) {$node->operator} ({$right})";
        }

        if ($node instanceof PredicateNode) {
            $base = $this->compilePath($node->path, $context);
            $pred = $this->compilePath($node->predicate, $context);
            return "{$base}[{$pred}]";
        }

        if ($node instanceof PathOperatorNode) {
            $left  = $this->compilePath($node->left, $context);
            $right = $this->compilePath($node->right, $context);
            return "{$left}{$node->operator}{$right}";
        }

        if ($node instanceof FunctionNode) {
            
            $arguments = [];
            foreach ($node->arguments as $argument) {
                $arguments[] = $this->compilePath($argument, $context);
            }

            return $node->name . '(' . implode(', ', $arguments) . ')';
        }

        throw new RuntimeException("Cannot convert node to XPath path: " . get_class($node));
    }

    protected function getFunctionInstance(string $class): XPathFunctionInterface
    {
        return $this->functionInstances[$class] 
            ??= new ReflectionClass($class)->newLazyGhost(function(XPathFunctionInterface $object) {});
    }
}