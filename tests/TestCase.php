<?php

namespace Berkan\Tests;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class TestCase extends BaseTestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Define constants if not already defined (normally set in berkan.php)
        if (! defined('BERKAN_LOOPBACK')) {
            define('BERKAN_LOOPBACK', '127.0.0.1');
        }

        if (! defined('BREW_PREFIX')) {
            define('BREW_PREFIX', '/opt/homebrew');
        }

        if (! defined('BERKAN_HOME_PATH')) {
            define('BERKAN_HOME_PATH', sys_get_temp_dir() . '/.config/berkan');
        }

        // Set up container
        $container = Container::getInstance();
        $container->singleton(ConsoleOutput::class, function () {
            return new ConsoleOutput();
        });

        // Create temp directory for tests
        $this->tempDir = sys_get_temp_dir() . '/berkan-tests-' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Set HOME for Configuration class
        $_SERVER['HOME'] = $this->tempDir;
        $_SERVER['USER'] = get_current_user();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \FilesystemIterator($dir);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
