# mmnewmedia/schematron-parser

XPath expression parser and compiler for Schematron rule files in PHP.

The library tokenizes and parses XPath 1.0 expressions into an AST, compiles them into executable
PHP code, and can generate one PHP rule class per `sch:assert` / `sch:report` from a `.sch`
file. It powers the pure-PHP EN 16931 / Factur-X / ZUGFeRD Schematron validation
(no Java required at runtime).

## Requirements

- PHP ^8.4
- `ext-dom`, `ext-mbstring`

## Installation

```bash
composer require mmnewmedia/schematron-parser
```

## Overview

```
MMNewmedia\Schematron\Ast\         XPath tokenizer and AST value objects
MMNewmedia\Schematron\Parser\      XPathAstParser (token stream -> AST)
MMNewmedia\Schematron\Compiler\    ExpressionCompiler, XPathAstCompiler, XPath function library
MMNewmedia\Schematron\             SchematronCompilerService (.sch -> PHP rule classes)
```

### Parsing and compiling XPath expressions

```php
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;

$functionLoader = new FunctionLoader(
    dirname(__DIR__) . '/vendor/mmnewmedia/schematron-parser/src/Schematron/Compiler/Functions',
    'MMNewmedia\Schematron\Compiler\Functions',
);

$compiler = new ExpressionCompiler(
    new XPathAstParser(new XPathTokenizer()),
    new XPathAstCompiler($functionLoader->load()),
    new ExpressionNormalizer(),
);

// XPath -> PHP expression evaluating against a context node ($context by default)
$php = $compiler->compile('substring-before(ram:ID, "-")', '$node');

// XPath -> PHP expression evaluating to a node-set (DOMNodeList)
$nodeSet = $compiler->compileNodeSet('.//ram:SpecifiedLineTradeTransaction', '$node');
```

`XPathAstCompiler` also accepts the AST directly (`$parser->parse(...)` + `$compiler->compile(...)`),
and individual XPath functions can be compiled with a plain `new XPathAstCompiler([])` if no
function library is needed.

The compiler ships implementations for the XPath 1.0 core functions plus the extensions used by
EN 16931 schematron (`upper-case`, `lower-case`, `matches`, `tokenize`, `distinct-values`,
`starts-with`, `ends-with`, `exists`, `empty`, `xs:date`, `xs:decimal`, …) under
`Compiler/Functions/`. Custom functions implement `XPathFunctionInterface` (or
`NodeSetAwareFunctionInterface`) and are picked up by `FunctionLoader`.

The discovered class list is cached per directory/namespace, so repeated `load()` calls do not
rescan the filesystem. Call `FunctionLoader::clearCache()` if the functions directory changes
at runtime.

### Generating rule classes from a .sch file

```php
use MMNewmedia\Schematron\SchematronCompilerService;

$service = new SchematronCompilerService($compiler, '/path/to/output/Rules/Cii');
$service->compile('/path/to/EN16931-CII-validation-preprocessed.sch', 'MyProject\Schematron\Rules\Cii');
```

Each `sch:assert` / `sch:report` becomes a final class with:

```php
public static function validate(DOMXPath $xpath, DOMNode $context): bool;
public static function message(): string;
public static function context(DOMXPath $xpath, DOMNode $context): iterable; // only when needed
```

### CLI

```bash
php bin/compile-schematron.php <source.sch> <outputDir> <namespace>
```

Compiles a single Schematron file: every `sch:assert` / `sch:report` is written as a PHP rule
class into `<outputDir>`, using `<namespace>` as the generated class namespace.

### Codedb lookups

Some Factur-X rules resolve codelist entries at runtime via `CodedbCodeList::has()`, which scans
codedb XML files. Point it at your files before evaluating generated rules:

```php
use MMNewmedia\Schematron\Compiler\CodedbCodeList;

CodedbCodeList::setBaseDirectories(['/path/to/zugferd/_generated', '/path/to/zugferd/_preprocessed']);
```

Without configuration it looks in `<base>/data/zugferd/{_generated,_preprocessed,_XSLT}`.

## Tests

```bash
composer install
composer test
```

## Static analysis

```bash
composer psalm
```

Psalm runs with `errorLevel="5"` (mirroring the original project configuration).

## License

This project is licensed under the BSD 3-Clause License.
See the [LICENSE](./LICENSE) file for the full text.
