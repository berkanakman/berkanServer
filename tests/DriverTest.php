<?php

namespace Berkan\Tests;

use Berkan\Drivers\BasicBerkanDriver;
use Berkan\Drivers\BasicWithPublicBerkanDriver;
use Berkan\Drivers\BerkanDriver;
use Berkan\Drivers\LaravelBerkanDriver;
use Berkan\Drivers\Specific\WordPressBerkanDriver;
use Berkan\Drivers\Specific\SymfonyBerkanDriver;
use Berkan\Drivers\Specific\DrupalBerkanDriver;
use Berkan\Drivers\Specific\CraftBerkanDriver;
use Berkan\Drivers\Specific\JoomlaBerkanDriver;

class DriverTest extends TestCase
{
    // === BasicBerkanDriver Tests ===

    public function test_basic_driver_always_serves(): void
    {
        $driver = new BasicBerkanDriver();
        $this->assertTrue($driver->serves('/any/path', 'site', '/'));
    }

    public function test_basic_driver_is_static_file_when_exists(): void
    {
        $driver = new BasicBerkanDriver();
        $sitePath = $this->tempDir . '/basic-site';
        mkdir($sitePath);
        file_put_contents($sitePath . '/style.css', 'body{}');

        $result = $driver->isStaticFile($sitePath, 'basic-site', '/style.css');
        $this->assertEquals($sitePath . '/style.css', $result);
    }

    public function test_basic_driver_is_static_file_returns_false_when_missing(): void
    {
        $driver = new BasicBerkanDriver();

        $result = $driver->isStaticFile($this->tempDir, 'site', '/nonexistent.css');
        $this->assertFalse($result);
    }

    public function test_basic_driver_front_controller_with_index_php(): void
    {
        $driver = new BasicBerkanDriver();
        $sitePath = $this->tempDir . '/basic-site';
        mkdir($sitePath);
        file_put_contents($sitePath . '/index.php', '<?php');

        $result = $driver->frontControllerPath($sitePath, 'basic-site', '/');
        $this->assertEquals($sitePath . '/index.php', $result);
    }

    public function test_basic_driver_front_controller_with_index_html(): void
    {
        $driver = new BasicBerkanDriver();
        $sitePath = $this->tempDir . '/html-site';
        mkdir($sitePath);
        file_put_contents($sitePath . '/index.html', '<html></html>');

        $result = $driver->frontControllerPath($sitePath, 'html-site', '/');
        $this->assertEquals($sitePath . '/index.html', $result);
    }

    public function test_basic_driver_front_controller_fallback(): void
    {
        $driver = new BasicBerkanDriver();
        $sitePath = $this->tempDir . '/empty-site';
        mkdir($sitePath);

        $result = $driver->frontControllerPath($sitePath, 'empty-site', '/');
        $this->assertEquals($sitePath . '/index.php', $result);
    }

    // === BasicWithPublicBerkanDriver Tests ===

    public function test_basic_with_public_driver_serves_when_public_dir_exists(): void
    {
        $driver = new BasicWithPublicBerkanDriver();
        $sitePath = $this->tempDir . '/public-site';
        mkdir($sitePath . '/public', 0755, true);

        $this->assertTrue($driver->serves($sitePath, 'site', '/'));
    }

    public function test_basic_with_public_driver_does_not_serve_without_public(): void
    {
        $driver = new BasicWithPublicBerkanDriver();
        $sitePath = $this->tempDir . '/no-public';
        mkdir($sitePath);

        $this->assertFalse($driver->serves($sitePath, 'site', '/'));
    }

    public function test_basic_with_public_driver_static_file(): void
    {
        $driver = new BasicWithPublicBerkanDriver();
        $sitePath = $this->tempDir . '/public-site';
        mkdir($sitePath . '/public', 0755, true);
        file_put_contents($sitePath . '/public/app.js', 'console.log()');

        $result = $driver->isStaticFile($sitePath, 'site', '/app.js');
        $this->assertEquals($sitePath . '/public/app.js', $result);
    }

    public function test_basic_with_public_driver_static_file_returns_false(): void
    {
        $driver = new BasicWithPublicBerkanDriver();
        $sitePath = $this->tempDir . '/public-site';
        mkdir($sitePath . '/public', 0755, true);

        $result = $driver->isStaticFile($sitePath, 'site', '/nonexistent.js');
        $this->assertFalse($result);
    }

    public function test_basic_with_public_driver_front_controller(): void
    {
        $driver = new BasicWithPublicBerkanDriver();
        $sitePath = $this->tempDir . '/public-site';
        mkdir($sitePath . '/public', 0755, true);

        $result = $driver->frontControllerPath($sitePath, 'site', '/');
        $this->assertEquals($sitePath . '/public/index.php', $result);
    }

    // === LaravelBerkanDriver Tests ===

    public function test_laravel_driver_serves_with_artisan(): void
    {
        $driver = new LaravelBerkanDriver();
        $sitePath = $this->tempDir . '/laravel-app';
        mkdir($sitePath . '/public', 0755, true);
        file_put_contents($sitePath . '/public/index.php', '<?php');
        file_put_contents($sitePath . '/artisan', '#!/usr/bin/env php');

        $this->assertTrue($driver->serves($sitePath, 'laravel', '/'));
    }

    public function test_laravel_driver_serves_with_bootstrap_app(): void
    {
        $driver = new LaravelBerkanDriver();
        $sitePath = $this->tempDir . '/laravel-app2';
        mkdir($sitePath . '/public', 0755, true);
        mkdir($sitePath . '/bootstrap', 0755, true);
        file_put_contents($sitePath . '/public/index.php', '<?php');
        file_put_contents($sitePath . '/bootstrap/app.php', '<?php');

        $this->assertTrue($driver->serves($sitePath, 'laravel', '/'));
    }

    public function test_laravel_driver_does_not_serve_random_site(): void
    {
        $driver = new LaravelBerkanDriver();
        $sitePath = $this->tempDir . '/not-laravel';
        mkdir($sitePath);

        $this->assertFalse($driver->serves($sitePath, 'not-laravel', '/'));
    }

    public function test_laravel_driver_static_file(): void
    {
        $driver = new LaravelBerkanDriver();
        $sitePath = $this->tempDir . '/laravel-app';
        mkdir($sitePath . '/public', 0755, true);
        file_put_contents($sitePath . '/public/robots.txt', 'User-agent: *');

        $result = $driver->isStaticFile($sitePath, 'laravel', '/robots.txt');
        $this->assertEquals($sitePath . '/public/robots.txt', $result);
    }

    public function test_laravel_driver_front_controller(): void
    {
        $driver = new LaravelBerkanDriver();
        $sitePath = $this->tempDir . '/laravel-app';
        mkdir($sitePath . '/public', 0755, true);

        $result = $driver->frontControllerPath($sitePath, 'laravel', '/');
        $this->assertEquals($sitePath . '/public/index.php', $result);
    }

    // === WordPressBerkanDriver Tests ===

    public function test_wordpress_driver_serves_with_wp_config(): void
    {
        $driver = new WordPressBerkanDriver();
        $sitePath = $this->tempDir . '/wp-site';
        mkdir($sitePath . '/wp-admin', 0755, true);
        file_put_contents($sitePath . '/wp-config.php', '<?php');

        $this->assertTrue($driver->serves($sitePath, 'wp', '/'));
    }

    public function test_wordpress_driver_serves_with_wp_config_sample(): void
    {
        $driver = new WordPressBerkanDriver();
        $sitePath = $this->tempDir . '/wp-site2';
        mkdir($sitePath . '/wp-admin', 0755, true);
        file_put_contents($sitePath . '/wp-config-sample.php', '<?php');

        $this->assertTrue($driver->serves($sitePath, 'wp', '/'));
    }

    public function test_wordpress_driver_does_not_serve_without_wp_admin(): void
    {
        $driver = new WordPressBerkanDriver();
        $sitePath = $this->tempDir . '/not-wp';
        mkdir($sitePath);
        file_put_contents($sitePath . '/wp-config.php', '<?php');

        $this->assertFalse($driver->serves($sitePath, 'not-wp', '/'));
    }

    public function test_wordpress_driver_static_file(): void
    {
        $driver = new WordPressBerkanDriver();
        $sitePath = $this->tempDir . '/wp-site';
        mkdir($sitePath . '/wp-content', 0755, true);
        file_put_contents($sitePath . '/wp-content/style.css', 'body{}');

        $result = $driver->isStaticFile($sitePath, 'wp', '/wp-content/style.css');
        $this->assertEquals($sitePath . '/wp-content/style.css', $result);
    }

    public function test_wordpress_driver_front_controller(): void
    {
        $driver = new WordPressBerkanDriver();
        $sitePath = $this->tempDir . '/wp-site';
        mkdir($sitePath);

        $result = $driver->frontControllerPath($sitePath, 'wp', '/');
        $this->assertEquals($sitePath . '/index.php', $result);
    }

    // === BerkanDriver Base Tests ===

    public function test_mutate_uri_returns_unchanged(): void
    {
        $driver = new BasicBerkanDriver();
        $this->assertEquals('/some/path', $driver->mutateUri('/some/path'));
    }

    public function test_drivers_in_returns_empty_for_nonexistent_dir(): void
    {
        $result = BerkanDriver::driversIn('/nonexistent/directory');
        $this->assertEquals([], $result);
    }

    public function test_default_drivers_returns_two_drivers(): void
    {
        $defaults = BerkanDriver::defaultDrivers();
        $this->assertCount(2, $defaults);
        $this->assertContains(BasicWithPublicBerkanDriver::class, $defaults);
        $this->assertContains(BasicBerkanDriver::class, $defaults);
    }

    // === SymfonyBerkanDriver Tests ===

    public function test_symfony_driver_serves_with_bin_console(): void
    {
        $driver = new SymfonyBerkanDriver();
        $sitePath = $this->tempDir . '/symfony-app';
        mkdir($sitePath . '/bin', 0755, true);
        mkdir($sitePath . '/public', 0755, true);
        file_put_contents($sitePath . '/bin/console', '#!/usr/bin/env php');
        file_put_contents($sitePath . '/public/index.php', '<?php');

        $this->assertTrue($driver->serves($sitePath, 'symfony', '/'));
    }

    public function test_symfony_driver_does_not_serve_random_site(): void
    {
        $driver = new SymfonyBerkanDriver();
        $sitePath = $this->tempDir . '/not-symfony';
        mkdir($sitePath);

        $this->assertFalse($driver->serves($sitePath, 'not-symfony', '/'));
    }
}
