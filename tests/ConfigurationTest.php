<?php

namespace Berkan\Tests;

use Berkan\Configuration;
use Berkan\Filesystem;

class ConfigurationTest extends TestCase
{
    protected Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = new Configuration(new Filesystem());
    }

    public function test_path_returns_home_config_path(): void
    {
        $expected = $this->tempDir . Configuration::BERKAN_HOME_PATH;
        $this->assertEquals($expected, $this->configuration->path());
    }

    public function test_home_path(): void
    {
        $expected = $this->tempDir . Configuration::BERKAN_HOME_PATH;
        $this->assertEquals($expected, $this->configuration->homePath());
    }

    public function test_install_creates_directories_and_config(): void
    {
        $this->configuration->install();

        $basePath = $this->configuration->path();

        $this->assertTrue(is_dir($basePath));
        $this->assertTrue(is_dir($basePath . '/Drivers'));
        $this->assertTrue(is_dir($basePath . '/Sites'));
        $this->assertTrue(is_dir($basePath . '/Certificates'));
        $this->assertTrue(is_dir($basePath . '/Log'));
        $this->assertTrue(is_dir($basePath . '/dnsmasq.d'));
        $this->assertTrue(file_exists($basePath . '/config.json'));
    }

    public function test_install_creates_sample_driver(): void
    {
        $this->configuration->install();

        $driverPath = $this->configuration->path() . '/Drivers/SampleBerkanDriver.php';
        $this->assertTrue(file_exists($driverPath));
    }

    public function test_install_does_not_overwrite_existing_drivers_directory(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath . '/Drivers', 0755, true);
        file_put_contents($basePath . '/Drivers/custom.php', 'custom');

        $this->configuration->install();

        $this->assertEquals('custom', file_get_contents($basePath . '/Drivers/custom.php'));
    }

    public function test_write_base_configuration(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->writeBaseConfiguration();

        $config = $this->configuration->read();

        $this->assertEquals('test', $config['tld']);
        $this->assertEquals(BERKAN_LOOPBACK, $config['loopback']);
        $this->assertEquals('80', $config['http_port']);
        $this->assertEquals('443', $config['https_port']);
        $this->assertIsArray($config['paths']);
        $this->assertEmpty($config['paths']);
        $this->assertEquals('apache', $config['web_server']);
        $this->assertEquals(['8.4'], $config['php_versions']);
        $this->assertIsArray($config['databases']);
        $this->assertEmpty($config['databases']);
    }

    public function test_write_base_configuration_includes_port_defaults(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->writeBaseConfiguration();

        $config = $this->configuration->read();

        $this->assertArrayHasKey('http_port', $config);
        $this->assertArrayHasKey('https_port', $config);
        $this->assertEquals('80', $config['http_port']);
        $this->assertEquals('443', $config['https_port']);
    }

    public function test_write_base_configuration_does_not_overwrite_existing(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->write(['tld' => 'local', 'loopback' => '127.0.0.1', 'paths' => []]);
        $this->configuration->writeBaseConfiguration();

        $config = $this->configuration->read();
        $this->assertEquals('local', $config['tld']);
    }

    public function test_read_returns_defaults_when_no_config_file(): void
    {
        $config = $this->configuration->read();

        $this->assertEquals('test', $config['tld']);
        $this->assertEquals(BERKAN_LOOPBACK, $config['loopback']);
        $this->assertEquals('80', $config['http_port']);
        $this->assertEquals('443', $config['https_port']);
        $this->assertEmpty($config['paths']);
        $this->assertEquals('apache', $config['web_server']);
        $this->assertEquals(['8.4'], $config['php_versions']);
        $this->assertEmpty($config['databases']);
    }

    public function test_write_and_read(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $data = ['tld' => 'dev', 'loopback' => '127.0.0.1', 'paths' => ['/my/path']];
        $this->configuration->write($data);

        $config = $this->configuration->read();
        $this->assertEquals('dev', $config['tld']);
        $this->assertEquals(['/my/path'], $config['paths']);
    }

    public function test_write_creates_json_with_pretty_print(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->write(['tld' => 'test', 'paths' => ['/some/path']]);

        $raw = file_get_contents($basePath . '/config.json');
        $this->assertStringContainsString("\n", $raw);
        $this->assertStringContainsString('/some/path', $raw);
        $this->assertStringNotContainsString('\/', $raw); // JSON_UNESCAPED_SLASHES
    }

    public function test_add_path(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/my/projects');

        $config = $this->configuration->read();
        $this->assertContains('/my/projects', $config['paths']);
    }

    public function test_add_path_does_not_duplicate(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/my/projects');
        $this->configuration->addPath('/my/projects');

        $config = $this->configuration->read();
        $this->assertCount(1, $config['paths']);
    }

    public function test_add_path_prepend(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/first');
        $this->configuration->addPath('/second', true);

        $config = $this->configuration->read();
        $this->assertEquals('/second', $config['paths'][0]);
        $this->assertEquals('/first', $config['paths'][1]);
    }

    public function test_add_multiple_paths(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/first');
        $this->configuration->addPath('/second');
        $this->configuration->addPath('/third');

        $config = $this->configuration->read();
        $this->assertCount(3, $config['paths']);
    }

    public function test_remove_path(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/my/projects');
        $this->configuration->addPath('/other');
        $this->configuration->removePath('/my/projects');

        $config = $this->configuration->read();
        $this->assertNotContains('/my/projects', $config['paths']);
        $this->assertContains('/other', $config['paths']);
    }

    public function test_remove_path_reindexes_array(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/a');
        $this->configuration->addPath('/b');
        $this->configuration->addPath('/c');
        $this->configuration->removePath('/b');

        $config = $this->configuration->read();
        $this->assertEquals(['/a', '/c'], $config['paths']);
    }

    public function test_remove_nonexistent_path_does_nothing(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->removePath('/nonexistent');

        $config = $this->configuration->read();
        $this->assertEmpty($config['paths']);
    }

    public function test_prune_removes_nonexistent_paths(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $existingDir = $this->tempDir . '/existing';
        mkdir($existingDir);

        $this->configuration->addPath($existingDir);
        $this->configuration->addPath('/nonexistent/path');

        $this->configuration->prune();

        $config = $this->configuration->read();
        $this->assertContains($existingDir, $config['paths']);
        $this->assertNotContains('/nonexistent/path', $config['paths']);
    }

    public function test_prune_with_no_paths_key(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->write(['tld' => 'test']);

        // Should not throw
        $this->configuration->prune();
        $this->assertTrue(true);
    }

    public function test_update_key(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $result = $this->configuration->updateKey('tld', 'local');

        $this->assertEquals('local', $result['tld']);

        $config = $this->configuration->read();
        $this->assertEquals('local', $config['tld']);
    }

    public function test_update_key_adds_new_key(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $result = $this->configuration->updateKey('custom_key', 'custom_value');

        $this->assertEquals('custom_value', $result['custom_key']);
        $config = $this->configuration->read();
        $this->assertEquals('custom_value', $config['custom_key']);
    }

    public function test_update_key_for_http_port(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->updateKey('http_port', '8080');
        $this->configuration->updateKey('https_port', '8443');

        $config = $this->configuration->read();
        $this->assertEquals('8080', $config['http_port']);
        $this->assertEquals('8443', $config['https_port']);
    }

    public function test_parked_paths(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->addPath('/my/sites');

        $paths = $this->configuration->parkedPaths();
        $this->assertEquals(['/my/sites'], $paths);
    }

    public function test_parked_paths_returns_empty_when_no_paths(): void
    {
        $paths = $this->configuration->parkedPaths();
        $this->assertEquals([], $paths);
    }

    public function test_uninstall_removes_config_directory(): void
    {
        $this->configuration->install();

        $basePath = $this->configuration->path();
        $this->assertTrue(is_dir($basePath));

        $this->configuration->uninstall();

        $this->assertFalse(is_dir($basePath));
    }

    public function test_create_apache_directory(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->createApacheDirectory();

        $this->assertTrue(is_dir($basePath . '/Apache'));
    }

    public function test_create_nginx_directory(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);

        $this->configuration->createNginxDirectory();

        $this->assertTrue(is_dir($basePath . '/Nginx'));
    }

    public function test_create_web_server_directory_apache_by_default(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->writeBaseConfiguration();

        $this->configuration->createWebServerDirectory();

        $this->assertTrue(is_dir($basePath . '/Apache'));
    }

    public function test_create_web_server_directory_nginx(): void
    {
        $basePath = $this->configuration->path();
        mkdir($basePath, 0755, true);
        $this->configuration->write([
            'tld' => 'test',
            'loopback' => BERKAN_LOOPBACK,
            'paths' => [],
            'web_server' => 'nginx',
        ]);

        $this->configuration->createWebServerDirectory();

        $this->assertTrue(is_dir($basePath . '/Nginx'));
    }

    public function test_berkan_home_path_constant(): void
    {
        $this->assertEquals('/.config/berkan', Configuration::BERKAN_HOME_PATH);
    }
}
