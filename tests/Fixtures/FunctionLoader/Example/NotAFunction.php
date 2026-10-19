<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Fixtures\FunctionLoader\Example;

/**
 * Matches the *Function.php file name filter but does not implement
 * XPathFunctionInterface, so FunctionLoader must not pick it up.
 */
final class NotAFunction
{
}
