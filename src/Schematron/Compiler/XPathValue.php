<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use DOMNode;
use DOMNodeList;

final class XPathValue
{
    /**
     * Equivalent of XPath 1.0 string() for a node-set: string value of the
     * first node in document order, or '' for an empty node-set.
     *
     * @param DOMNodeList<int, DOMNode>|array<int, DOMNode|scalar>|scalar|null $nodes
     */
    public static function stringValue(mixed $nodes): string
    {
        if ($nodes instanceof DOMNodeList) {
            foreach ($nodes as $node) {
                return $node->textContent;
            }

            return '';
        }

        if (is_array($nodes)) {
            foreach ($nodes as $node) {
                if ($node instanceof DOMNode) {
                    return $node->textContent;
                }

                return (string) $node;
            }

            return '';
        }

        if ($nodes === null) {
            return '';
        }

        return (string) $nodes;
    }
}
