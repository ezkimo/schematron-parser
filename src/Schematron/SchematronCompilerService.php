<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron;

use DOMDocument;
use DOMElement;
use DOMXPath;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use RuntimeException;

final class SchematronCompilerService
{
    public function __construct(
        protected readonly ExpressionCompiler $compiler,
        protected readonly string $outputDirectory,
    ) {}

    public function compile(string $schematronFile, string $namespace): void
    {
        clearstatcache();
        if (is_dir($this->outputDirectory) === false) {
            if (mkdir($this->outputDirectory, 0777, true) === false) {
                throw new RuntimeException(sprintf(
                    'Failed to create output directory: %s',
                    $this->outputDirectory
                ));
            };
        }

        $dom = new DOMDocument();
        $dom->load($schematronFile);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('sch', 'http://purl.oclc.org/dsdl/schematron');

        $i = 0;
        foreach ($xpath->query('//sch:rule') as $rule) {
            /** @var DOMElement $rule */
            $context = html_entity_decode($rule->getAttribute('context'), ENT_QUOTES | ENT_XML1);
            $contextPhp = null;

            try {
                $contextPhp = $this->compiler->compileNodeSet($context, '$context');
            } catch (RuntimeException $exception) {
                $contextPhp = null;
            }

            $variableInitializers = [];
            foreach ($xpath->query('sch:let', $rule) as $letNode) {
                /** @var DOMElement $letNode */
                $name = $letNode->getAttribute('name');
                $value = html_entity_decode($letNode->getAttribute('value'), ENT_QUOTES | ENT_XML1);

                try {
                    $variableInitializers[$name] = $this->compiler->compile($value, '$context');
                } catch (RuntimeException $exception) {
                    $variableInitializers[$name] = sprintf("'' /* uncompilable let '%s' */", addslashes($value));
                }
            }

            foreach ($xpath->query('sch:assert | sch:report', $rule) as $testNode) {
                /** @var DOMElement $testNode */
                $test = html_entity_decode($testNode->getAttribute('test'), ENT_QUOTES | ENT_XML1);
                $message = trim($testNode->textContent);

                $compiled = $this->compiler->compile($test, '$context');
                $className = $this->normalizeClassName($testNode->getAttribute('id') ?: 'Rule-' . $i);

                $this->generateRuleClass($namespace, $className, $compiled, $message, $contextPhp, $variableInitializers);
                $i++;
            }
        }
    }

    protected function generateRuleClass(string $namespace, string $className, string $phpExpression, string $message, ?string $contextPhp, array $variableInitializers = []): void
    {
        $message = addslashes($message);

        $contextMethod = '';
        if ($contextPhp !== null) {
            $contextMethod = <<<PHP

    public static function context(DOMXPath \$xpath, DOMNode \$context): iterable
    {
        return ({$contextPhp});
    }

PHP;
        }

        $variableCode = '';
        foreach ($variableInitializers as $name => $expr) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) !== 1) {
                continue;
            }
            $variableCode .= "        \$" . $name . " = ({$expr});\n";
        }

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use DOMNode;
use DOMXPath;

final class {$className}
{
    public static function validate(DOMXPath \$xpath, DOMNode \$context): bool
    {
{$variableCode}        return ({$phpExpression}) !== false;
    }

    public static function message(): string
    {
        return "{$message}";
    }
{$contextMethod}}
PHP;

        if (file_put_contents($this->outputDirectory . '/' . $className . '.php', $code) === false) {
            throw new RuntimeException(sprintf(
                'Failed to write rule class: %s',
                $this->outputDirectory . '/' . $className . '.php'
            ));
        }
    }

    protected function normalizeClassName(string $message): string
    {
        return preg_replace_callback('/-(.?)/', function($matches) {
            return ucfirst($matches[1]);
        }, $message) . 'Rule';
    }
}