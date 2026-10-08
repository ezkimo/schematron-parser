<?php

declare(strict_types=1);

/**
 * Regeneriert die kompilierten Schematron Regelklassen aus den vorverarbeiteten
 * .sch Dateien (CII, UBL und die funf Factur-X / ZUGFeRD Profile).
 *
 * Usage:
 *   php bin/compile-schematron-rules.php [outputRoot] [dataDir]
 *
 * outputRoot  Projektwurzel, unter der src/Schematron/Rules/<subdir> geschrieben
 *             wird (default: Paketwurzel).
 * dataDir     Wurzel der vorverarbeiteten .sch Dateien im x-rechnung Layout
 *             (cii/schematron/preprocessed/, ubl/schematron/preprocessed/,
 *             zugferd/_preprocessed/), default: <paket>/data.
 *
 * Das Zielverzeichnis wird pro Profil um dessen Unterordner ergänzt, die Namespaces
 * bleiben dabei unverändert. Optional wird src/Schematron/Rules/rules-config.php
 * geschrieben, wenn direkt in den Quellbaum generiert wird.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use MMNewmedia\Schematron\SchematronCompilerService;

$packageRoot = dirname(__DIR__);
$baseOutput = $argv[1] ?? $packageRoot;
$dataDir = $argv[2] ?? $packageRoot . '/data';

$sources = [
    'cii' => [
        'file' => $dataDir . '/cii/schematron/preprocessed/EN16931-CII-validation-preprocessed.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\Cii',
        'subdir' => 'Cii',
    ],
    'ubl' => [
        'file' => $dataDir . '/ubl/schematron/preprocessed/EN16931-UBL-validation-preprocessed.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\Ubl',
        'subdir' => 'Ubl',
    ],
    'facturx-minimum' => [
        'file' => $dataDir . '/zugferd/_preprocessed/FACTUR-X_MINIMUM.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\FacturX\\Minimum',
        'subdir' => 'FacturX/Minimum',
    ],
    'facturx-basic-wl' => [
        'file' => $dataDir . '/zugferd/_preprocessed/FACTUR-X_BASIC-WL.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\FacturX\\BasicWL',
        'subdir' => 'FacturX/BasicWL',
    ],
    'facturx-basic' => [
        'file' => $dataDir . '/zugferd/_preprocessed/FACTUR-X_BASIC.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\FacturX\\Basic',
        'subdir' => 'FacturX/Basic',
    ],
    'facturx-en16931' => [
        'file' => $dataDir . '/zugferd/_preprocessed/FACTUR-X_EN16931.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\FacturX\\EN16931',
        'subdir' => 'FacturX/EN16931',
    ],
    'facturx-extended' => [
        'file' => $dataDir . '/zugferd/_preprocessed/FACTUR-X_EXTENDED.sch',
        'namespace' => 'MMNewmedia\\Schematron\\Rules\\FacturX\\Extended',
        'subdir' => 'FacturX/Extended',
    ],
];

$functionLoader = new FunctionLoader(
    $packageRoot . '/src/Schematron/Compiler/Functions',
    'MMNewmedia\\Schematron\\Compiler\\Functions',
);

$expressionCompiler = new ExpressionCompiler(
    new XPathAstParser(new XPathTokenizer()),
    new XPathAstCompiler($functionLoader->load()),
    new ExpressionNormalizer(),
);

$configEntries = [];
$ruleIndex = 0;

foreach ($sources as $profile => $config) {
    $outputDirectory = rtrim($baseOutput, '/') . '/src/Schematron/Rules/' . $config['subdir'];

    echo "Compiling {$profile}...\n";
    $service = new SchematronCompilerService($expressionCompiler, $outputDirectory);
    $service->compile($config['file'], $config['namespace']);

    $configEntries = array_merge(
        $configEntries,
        collectSmokeConfig($config['file'], $config['namespace'], $ruleIndex),
    );

    $ruleIndex = 0;
}

if (rtrim($baseOutput, '/') === $packageRoot) {
    $rulesConfigPath = $packageRoot . '/src/Schematron/Rules/rules-config.php';
    file_put_contents($rulesConfigPath, renderRulesConfig($configEntries));
    echo "Wrote " . count($configEntries) . " entries to {$rulesConfigPath}\n";
}

echo "Done.\n";

/**
 * Sammelt pro assert ein Smoke-Eintrag {className, code, context} aus einer .sch Datei.
 * Die Namensauflösung (id oder globaler Zähler "Rule-{i}") muss SchematronCompilerService
 * exakt nachbilden, da Klasse und Konfiguration damit paarweise erzeugt werden.
 *
 * @param int $ruleIndex Globaler, dateiübergreifender Zähler (by reference)
 * @return array<int, array{className: string, code: string, context: string}>
 */
function collectSmokeConfig(string $schematronFile, string $namespace, int &$ruleIndex): array
{
    $dom = new DOMDocument();
    $dom->load($schematronFile);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('sch', 'http://purl.oclc.org/dsdl/schematron');

    $entries = [];

    foreach ($xpath->query('//sch:rule') as $rule) {
        /** @var DOMElement $rule */
        $context = html_entity_decode($rule->getAttribute('context'), ENT_QUOTES | ENT_XML1);

        foreach ($xpath->query('sch:assert | sch:report', $rule) as $testNode) {
            /** @var DOMElement $testNode */
            $id = $testNode->getAttribute('id') ?: 'Rule-' . $ruleIndex;
            $className = normalizeRuleClassName($id);
            $ruleIndex++;

            $entries[] = [
                'className' => $namespace . '\\' . $className,
                'code' => $id,
                'context' => $context,
            ];
        }
    }

    return $entries;
}

function normalizeRuleClassName(string $id): string
{
    return preg_replace_callback('/-(.?)/', static function (array $matches): string {
        return ucfirst($matches[1]);
    }, $id) . 'Rule';
}

/**
 * @param array<int, array{className: string, code: string, context: string}> $entries
 */
function renderRulesConfig(array $entries): string
{
    $lines = [];
    foreach ($entries as $entry) {
        $lines[] = sprintf(
            "    [\n        'className' => %s,\n        'code' => %s,\n        'context' => %s,\n    ]",
            var_export($entry['className'], true),
            var_export($entry['code'], true),
            var_export($entry['context'], true),
        );
    }

    return "<?php\n\ndeclare(strict_types=1);\n\n" .
        "// Generiert von bin/compile-schematron-rules.php - nicht manuell bearbeiten.\n" .
        "return [\n" . implode(",\n", $lines) . "\n];\n";
}