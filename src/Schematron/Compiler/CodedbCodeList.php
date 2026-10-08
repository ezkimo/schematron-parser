<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Compiler;

use DOMDocument;
use DOMXPath;

/**
 * Resolves the Factur-X `document('FILE')/codedb/cl[@id=N]/enumeration[@value=V]`
 * code-list membership checks used by the compiled Schematron rule classes.
 *
 * The codedb XML files are loaded once and cached per process.
 */
final class CodedbCodeList
{
    /** @var array<string, DOMDocument> */
    private static array $documents = [];

    /** @var array<string, string|false> */
    private static array $resolvedPaths = [];

    /** @var list<string>|null */
    private static ?array $baseDirectories = null;

    public static function has(string $filename, int $clId, string $value): bool
    {
        $document = self::document($filename);
        $xpath = new DOMXPath($document);

        $value = addslashes($value);
        $query = sprintf(
            "//cl[@id='%d']/enumeration[@value='%s']",
            $clId,
            $value
        );

        $nodes = @$xpath->query($query);

        return $nodes !== false && $nodes->length > 0;
    }

    public static function document(string $filename): DOMDocument
    {
        if (!isset(self::$documents[$filename])) {
            $path = self::locate($filename);

            $document = new DOMDocument();
            $loaded = $document->load($path);
            if ($loaded === false) {
                throw new \RuntimeException(sprintf(
                    'Failed to load Codedb document: %s',
                    $path
                ));
            }

            self::$documents[$filename] = $document;
        }

        return self::$documents[$filename];
    }

    private static function locate(string $filename): string
    {
        if (isset(self::$resolvedPaths[$filename])) {
            $resolved = self::$resolvedPaths[$filename];
            if ($resolved === false) {
                throw new \RuntimeException(sprintf('Codedb file not found: %s', $filename));
            }

            return $resolved;
        }

        foreach (self::baseDirectories() as $base) {
            $candidate = $base . '/' . $filename;
            if (is_file($candidate)) {
                return self::$resolvedPaths[$filename] = $candidate;
            }
        }

        self::$resolvedPaths[$filename] = false;

        throw new \RuntimeException(sprintf('Codedb file not found: %s', $filename));
    }

    /**
     * Candidate directories searched for a codedb file, ordered by priority.
     *
     * @return list<string>
     */
    private static function baseDirectories(): array
    {
        if (self::$baseDirectories !== null) {
            return self::$baseDirectories;
        }

        $projectRoot = dirname(__DIR__, 3);

        $candidates = [
            $projectRoot . '/data/zugferd/_generated',
            $projectRoot . '/data/zugferd/_preprocessed',
            $projectRoot . '/data/zugferd/_XSLT',
        ];

        return self::$baseDirectories = array_values(array_unique(array_filter($candidates, 'is_dir')));
    }

    /**
     * Overrides the base directories searched by locate() (mainly for tests).
     *
     * @param list<string> $directories
     */
    public static function setBaseDirectories(array $directories): void
    {
        self::$baseDirectories = $directories;
        self::$resolvedPaths = [];
        self::$documents = [];
    }
}
