<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * A method whose signature is incompatible with its parent raises a fatal error
 * when the class loads, not when the file is parsed, so `php -l` reports the file
 * as clean. Because routes/api.php constructs every controller at boot, one such
 * class previously took the whole API down, including login.
 */
class ClassIntegrityTest extends TestCase
{
    public function testEveryApplicationClassLoadsWithoutError(): void
    {
        $failures = [];
        $loaded = 0;

        foreach ($this->applicationClasses() as $class) {
            try {
                class_exists($class);
                $loaded++;
            } catch (Throwable $exception) {
                $failures[] = $class . ': ' . $exception->getMessage();
            }
        }

        $this->assertSame([], $failures, "Classes failed to load:\n" . implode("\n", $failures));
        $this->assertGreaterThan(80, $loaded, 'The scan should have found the whole application.');
    }

    public function testRouteFileBuildsWithoutConstructingABrokenController(): void
    {
        $router = require dirname(__DIR__, 2) . '/routes/api.php';

        $this->assertInstanceOf(
            \App\Helpers\Router::class,
            $router,
            'Building the route table must not fail, since every request depends on it.'
        );
    }

    /**
     * @return list<class-string>
     */
    private function applicationClasses(): array
    {
        $base = dirname(__DIR__, 2) . '/app';
        $classes = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($base) + 1);

            $classes[] = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
        }

        return $classes;
    }
}
