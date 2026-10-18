<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use DirectoryIterator;
use MMNewmedia\Schematron\Compiler\Functions\XPathFunctionInterface;

class FunctionLoader
{
    /**
     * Cache for discovered function classes, keyed by directory and namespace,
     * so repeated load() calls skip filesystem and class checks
     *
     * @var array<string, list<class-string<XPathFunctionInterface>>>
     */
    private static array $discoveredFunctions = [];

    public function __construct(
        protected readonly string $directory,
        protected readonly string $namespace,
    ) {}

    /**
     * Returns the function classes of the configured directory, served from
     * the discovery cache when the same directory/namespace was scanned before
     *
     * @return list<class-string<XPathFunctionInterface>>
     */
    public function load(): array
    {
        // "\0" cannot occur in directory paths or namespaces, so the key is unambiguous
        $cacheKey = $this->directory . "\0" . $this->namespace;

        if (isset(self::$discoveredFunctions[$cacheKey])) {
            return self::$discoveredFunctions[$cacheKey];
        }

        return self::$discoveredFunctions[$cacheKey] = $this->discover();
    }

    /**
     * Clear the discovery cache (e.g. in tests or when the functions
     * directory changes at runtime)
     */
    public static function clearCache(): void
    {
        self::$discoveredFunctions = [];
    }

    /**
     * Scans the functions directory for classes implementing XPathFunctionInterface
     *
     * @return list<class-string<XPathFunctionInterface>>
     */
    protected function discover(): array
    {
        $functions = [];

        $directoryIterator = new DirectoryIterator($this->directory);
        $filterIterator = new FunctionFilterIterator($directoryIterator);

        foreach ($filterIterator as $file) {
            $className = $this->namespace . '\\' . $file->getBasename('.php');
            if (is_subclass_of($className, XPathFunctionInterface::class)) {
                $functions[] = $className;
            }
        }

        return $functions;
    }
}