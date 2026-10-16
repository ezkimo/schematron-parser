<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Schematron;

use DirectoryIterator;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use MMNewmedia\Schematron\SchematronCompilerService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Die Generierung der Regel-Klassen schreibt eine Datei pro Regel in das
 * Ausgabe-Verzeichnis. Das Verzeichnis wird einmalig angelegt und die
 * generierten Dateien werden überschrieben, wenn sie bereits existieren.
 */
class SchematronCompilerServiceTest extends TestCase
{
    private const SCHEMATRON_FILE = __DIR__ . '/../data/schematron/rules.sch';

    /** @var array<string> */
    private array $temporaryDirectories = [];

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            if (is_dir($directory)) {
                self::deleteDirectory($directory);
            } elseif (is_file($directory)) {
                unlink($directory);
            }
        }

        $this->temporaryDirectories = [];
    }

    public function testCreatesOutputDirectoryWhenMissing(): void
    {
        $outputDirectory = $this->outputDirectory('creates-directory');

        $this->assertFalse(is_dir($outputDirectory));

        $this->schematronCompilerService($outputDirectory)->compile(self::SCHEMATRON_FILE, 'MyProject');

        $this->assertTrue(is_dir($outputDirectory));
    }

    public function testGeneratedRuleClassContainsExpectedCode(): void
    {
        $outputDirectory = $this->outputDirectory('generated-code');

        $this->schematronCompilerService($outputDirectory)->compile(self::SCHEMATRON_FILE, 'MyProject');

        $content = file_get_contents($outputDirectory . '/Rule0Rule.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString('final class Rule0Rule', $content);
        $this->assertStringContainsString('$nodeCount', $content);
        $this->assertStringContainsString('At least one node', $content);
        $this->assertStringContainsString('public static function context', $content);
    }

    public function testExistingRuleClassFileIsOverwritten(): void
    {
        $outputDirectory = $this->outputDirectory('overwrite');
        mkdir($outputDirectory);
        file_put_contents($outputDirectory . '/Rule0Rule.php', 'STALE CONTENT');

        $this->schematronCompilerService($outputDirectory)->compile(self::SCHEMATRON_FILE, 'MyProject');

        $content = file_get_contents($outputDirectory . '/Rule0Rule.php');

        $this->assertStringContainsString('final class Rule0Rule', $content);
        $this->assertStringNotContainsString('STALE CONTENT', $content);
    }

    public function testOutputDirectoryCreationFailureThrowsRuntimeException(): void
    {
        // Eine Datei existiert bereits an der Stelle, an der das Verzeichnis angelegt werden soll
        $parentDirectory = $this->temporaryDirectory('invalid-directory');
        mkdir($parentDirectory, 0777, true);
        $outputDirectory = $parentDirectory . '/not-a-directory';
        file_put_contents($outputDirectory, 'not a directory');

        $this->expectException(RuntimeException::class);

        $this->schematronCompilerService($outputDirectory)->compile(self::SCHEMATRON_FILE, 'MyProject');
    }

    /**
     * Baut den SchematronCompilerService mit einem frischen ExpressionCompiler.
     */
    private function schematronCompilerService(string $outputDirectory): SchematronCompilerService
    {
        $functions = (new FunctionLoader(
            dirname(__DIR__, 2) . '/src/Schematron/Compiler/Functions',
            'MMNewmedia\\Schematron\\Compiler\\Functions',
        ))->load();

        $expressionCompiler = new ExpressionCompiler(
            new XPathAstParser(new XPathTokenizer()),
            new XPathAstCompiler($functions),
            new ExpressionNormalizer(),
        );

        return new SchematronCompilerService($expressionCompiler, $outputDirectory);
    }

    /**
     * Erstellt den Namen eines temporären Ausgabe-Verzeichnisses.
     *
     * @return string
     */
    private function outputDirectory(string $identifier): string
    {
        $directory = sys_get_temp_dir() . '/schematron-compiler-service-' . $identifier . '-' . uniqid();

        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    /**
     * Erstellt den Namen einer temporären Eltern-Verzeichnis, die im Fehlerfall verwendet wird.
     *
     * @return string
     */
    private function temporaryDirectory(string $identifier): string
    {
        $directory = sys_get_temp_dir() . '/schematron-compiler-service-' . $identifier . '-' . uniqid();

        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    /**
     * Entfernt alle Dateien des angegebenen Verzeichnisses samt Unterverzeichnissen.
     *
     * @param string $directory
     */
    private static function deleteDirectory(string $directory): void
    {
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }

            $path = $file->getPathname();

            if ($file->isFile()) {
                unlink($path);
            } elseif ($file->isDir()) {
                self::deleteDirectory($path);
            }
        }

        rmdir($directory);
    }
}
