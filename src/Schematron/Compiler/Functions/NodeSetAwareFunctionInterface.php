<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler\Functions;

/**
 * Marker interface for functions whose path arguments are node-sets
 * (e.g. sum(), count()) instead of scalar string values.
 *
 * XPathAstCompiler passes path arguments to these functions as
 * `$xpath->evaluate("path", $context)` results (DOMNodeList) so that
 * every matching node can be processed, not just the first one.
 */
interface NodeSetAwareFunctionInterface extends XPathFunctionInterface
{
}
