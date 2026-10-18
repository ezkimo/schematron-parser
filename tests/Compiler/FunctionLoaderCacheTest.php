<?php

declare(strict_types=1);

namespace MMNewmedia\Tests\Compiler;

use DirectoryIterator;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\Functions\CountFunction;
use MMNewmedia\Tests\Fixtures\FunctionLoader\Example\AlphaFunction;
use MMNewmedia\Tests\Fixtures\FunctionLoader\Example\BetaFunction;
use PHPUnit\Framework\TestCase;

/**
 * The discovery result of FunctionLoader is deterministic for a given
 * directory and namespace, so repeated load() calls must reuse the cached
 * class list instead of scanning the filesystem and checking classes again.
 */
class FunctionLoaderCacheTest extends TestCase
{
    private const EXAMPLE_DIRECTORY = __DIR__ . '/../Fixtures/FunctionLoader/Example';
    private const EXAMPLE_NAMESPACE = 'MMNewmedia\\Tests\\Fixtures\\FunctionLoader\\Example';

    /** @var list<string> */
    private array $temporaryDirectories = [];

    #[\Override]
    protected function setUp(): void
    {
        // Der Cache ist statisch, daher vor jedem Test leeren, um Einträge
        // aus anderen Testklassen auszuschließen
        FunctionLoader::clearCache();
    }

    #[\Override]
    protected function tearDown(): void
    {
        FunctionLoader::clearCache();

        foreach ($this->temporaryDirectories as $directory) {
            foreach (new DirectoryIterator($directory) as $file) {
                if ($file->isDot() === false && $file->isFile()) {
                    unlink($file->getPathname());
                }
            }

            rmdir($directory);
        }

        $this->temporaryDirectories = [];
    }

    public function testRepeatedLoadOnSameLoaderIsServedFromCache(): void
    {
        $loader = $this->countingLoader();

        $first = $loader->load();
        $second = $loader->load();

        $this->assertSame(1, $loader->discoveryCalls());
        $this->assertSame($first, $second);
    }

    public function testSeparateLoadersWithSameConfigurationShareTheCache(): void
    {
        $first = $this->countingLoader();
        $first->load();

        $second = $this->countingLoader();
        $second->load();

        // Derselbe directory/namespace-Schlüssel: der zweite Loader scannt nicht mehr
        $this->assertSame(0, $second->discoveryCalls());
    }

    public function testDifferentDirectoryIsDiscoveredSeparately(): void
    {
        $loader = $this->countingLoader();
        $loader->load();

        $other = $this->countingLoader($this->temporaryDirectory());
        $other->load();

        $this->assertSame(1, $other->discoveryCalls());
    }

    public function testDifferentNamespaceIsDiscoveredSeparately(): void
    {
        $loader = $this->countingLoader();
        $loader->load();

        $other = $this->countingLoader(self::EXAMPLE_DIRECTORY, self::EXAMPLE_NAMESPACE . '\\Other');
        $other->load();

        $this->assertSame(1, $other->discoveryCalls());
    }

    public function testClearCacheForcesRediscovery(): void
    {
        $loader = $this->countingLoader();
        $loader->load();

        FunctionLoader::clearCache();

        $loader->load();

        $this->assertSame(2, $loader->discoveryCalls());
    }

    /**
     * Black-Box-Nachweis: Neue Dateien im Verzeichnis bleiben bis zum
     * clearCache() unsichtbar, danach wird das Verzeichnis neu gescannt.
     */
    public function testDirectoryChangesBecomeVisibleAfterClearCache(): void
    {
        $directory = $this->temporaryDirectory();
        $loader = new FunctionLoader($directory, self::EXAMPLE_NAMESPACE);

        $this->assertSame([], $loader->load());

        copy(
            self::EXAMPLE_DIRECTORY . '/AlphaFunction.php',
            $directory . '/AlphaFunction.php',
        );

        // Ergebnis kommt weiterhin aus dem Cache
        $this->assertSame([], $loader->load());

        FunctionLoader::clearCache();

        $this->assertSame([AlphaFunction::class], $loader->load());
    }

    /**
     * Nur Klassen, die als *Function.php enden und XPathFunctionInterface
     * implementieren, werden entdeckt.
     */
    public function testFixtureDirectoryIsDiscovered(): void
    {
        $loader = new FunctionLoader(self::EXAMPLE_DIRECTORY, self::EXAMPLE_NAMESPACE);

        $this->assertEqualsCanonicalizing(
            [AlphaFunction::class, BetaFunction::class],
            $loader->load(),
        );
    }

    public function testRealFunctionDirectoryIsDiscovered(): void
    {
        $loader = new FunctionLoader(
            dirname(__DIR__, 2) . '/src/Schematron/Compiler/Functions',
            'MMNewmedia\\Schematron\\Compiler\\Functions',
        );

        $this->assertContains(CountFunction::class, $loader->load());
    }

    private function countingLoader(
        ?string $directory = null,
        ?string $namespace = null,
    ): CountingFunctionLoader {
        return new CountingFunctionLoader(
            $directory ?? self::EXAMPLE_DIRECTORY,
            $namespace ?? self::EXAMPLE_NAMESPACE,
        );
    }

    /**
     * Erstellt den Namen eines temporären Verzeichnisses, das entfernt wird,
     * wenn der Test beendet ist.
     */
    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/schematron-function-loader-' . uniqid();

        mkdir($directory);

        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}

/**
 * Loader, der zählt, wie oft das Dateisystem-Scanning tatsächlich läuft.
 */
final class CountingFunctionLoader extends FunctionLoader
{
    private int $discoveryCalls = 0;

    public function discoveryCalls(): int
    {
        return $this->discoveryCalls;
    }

    #[\Override]
    protected function discover(): array
    {
        $this->discoveryCalls++;

        return parent::discover();
    }
}
