<?php

namespace Berkan\Tests;

use Berkan\Configuration;
use Berkan\Filesystem;
use Berkan\Server;

class ServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a config file for Server::loadConfig()
        $configDir = $this->tempDir . Configuration::BERKAN_HOME_PATH;
        mkdir($configDir, 0755, true);

        $config = [
            'tld' => 'test',
            'loopback' => '127.0.0.1',
            'paths' => [],
        ];

        file_put_contents(
            $configDir . '/config.json',
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    public function test_extract_site_name_removes_tld(): void
    {
        $result = Server::extractSiteName('myapp.test');
        $this->assertEquals('myapp', $result);
    }

    public function test_extract_site_name_removes_port(): void
    {
        $result = Server::extractSiteName('myapp.test:8080');
        $this->assertEquals('myapp', $result);
    }

    public function test_extract_site_name_with_subdomain(): void
    {
        $result = Server::extractSiteName('api.myapp.test');
        $this->assertEquals('api.myapp', $result);
    }

    public function test_load_config_returns_defaults_when_no_file(): void
    {
        // Remove the config file
        $configPath = $this->tempDir . Configuration::BERKAN_HOME_PATH . '/config.json';
        if (file_exists($configPath)) {
            unlink($configPath);
        }

        $config = Server::loadConfig();

        $this->assertEquals('test', $config['tld']);
        $this->assertEquals('127.0.0.1', $config['loopback']);
        $this->assertEquals([], $config['paths']);
    }

    public function test_load_config_reads_from_file(): void
    {
        $config = Server::loadConfig();

        $this->assertEquals('test', $config['tld']);
        $this->assertEquals('127.0.0.1', $config['loopback']);
    }

    public function test_load_config_with_custom_tld(): void
    {
        $configDir = $this->tempDir . Configuration::BERKAN_HOME_PATH;
        $config = ['tld' => 'local', 'loopback' => '127.0.0.1', 'paths' => []];
        file_put_contents($configDir . '/config.json', json_encode($config));

        $result = Server::loadConfig();
        $this->assertEquals('local', $result['tld']);
    }

    public function test_site_path_returns_empty_for_unknown_site(): void
    {
        $result = Server::sitePath('nonexistent');
        $this->assertEquals('', $result);
    }

    public function test_site_path_from_linked_site(): void
    {
        $sitesDir = $this->tempDir . Configuration::BERKAN_HOME_PATH . '/Sites';
        $target = $this->tempDir . '/my-project';
        mkdir($sitesDir, 0755, true);
        mkdir($target);
        symlink($target, $sitesDir . '/my-project');

        $result = Server::sitePath('my-project');
        $this->assertEquals($target, $result);
    }

    public function test_site_path_from_parked_path(): void
    {
        $parkedDir = $this->tempDir . '/Sites';
        mkdir($parkedDir . '/blog', 0755, true);

        $configDir = $this->tempDir . Configuration::BERKAN_HOME_PATH;
        $config = [
            'tld' => 'test',
            'loopback' => '127.0.0.1',
            'paths' => [$parkedDir],
        ];
        file_put_contents($configDir . '/config.json', json_encode($config));

        $result = Server::sitePath('blog');
        $this->assertEquals($parkedDir . '/blog', $result);
    }

    public function test_resolve_returns_array(): void
    {
        $result = Server::resolve('/some-uri', 'myapp.test');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('myapp', $result[1]);
        $this->assertEquals('/some-uri', $result[2]);
    }

    public function test_server_path_returns_valid_path(): void
    {
        $path = Server::serverPath();

        $this->assertIsString($path);
        $this->assertStringEndsWith('server.php', $path);
    }

    public function test_default_drivers(): void
    {
        $drivers = \Berkan\Drivers\BerkanDriver::defaultDrivers();

        $this->assertCount(2, $drivers);
        $this->assertContains(\Berkan\Drivers\BasicWithPublicBerkanDriver::class, $drivers);
        $this->assertContains(\Berkan\Drivers\BasicBerkanDriver::class, $drivers);
    }
}
