<?php

namespace Berkan\Tests;

use Berkan\Brew;
use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Database;
use Berkan\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseTest extends TestCase
{
    protected Database $database;
    protected Brew|MockObject $brew;
    protected CommandLine|MockObject $cli;
    protected Configuration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brew = $this->createMock(Brew::class);
        $this->cli = $this->createMock(CommandLine::class);
        $files = new Filesystem();
        $this->config = new Configuration($files);

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->database = new Database($this->brew, $this->cli, $files, $this->config);
    }

    public function test_supported_databases_constant(): void
    {
        $this->assertArrayHasKey('mysql', Database::SUPPORTED_DATABASES);
        $this->assertArrayHasKey('postgresql', Database::SUPPORTED_DATABASES);
        $this->assertArrayHasKey('mongodb', Database::SUPPORTED_DATABASES);
        $this->assertArrayHasKey('redis', Database::SUPPORTED_DATABASES);
    }

    public function test_install_mysql(): void
    {
        $this->brew->expects($this->once())
            ->method('ensureInstalled')
            ->with('mysql');

        $this->brew->expects($this->once())
            ->method('startService')
            ->with('mysql');

        $this->database->install('mysql');

        $config = $this->config->read();
        $this->assertContains('mysql', $config['databases']);
    }

    public function test_install_mongodb_taps_first(): void
    {
        $this->brew->expects($this->once())
            ->method('tap')
            ->with(['mongodb/brew']);

        $this->brew->expects($this->once())
            ->method('ensureInstalled')
            ->with('mongodb-community');

        $this->database->install('mongodb');
    }

    public function test_install_unsupported_database_does_nothing(): void
    {
        $this->brew->expects($this->never())
            ->method('ensureInstalled');

        $this->database->install('sqlite');

        $config = $this->config->read();
        $this->assertNotContains('sqlite', $config['databases'] ?? []);
    }

    public function test_install_does_not_duplicate_in_config(): void
    {
        $this->brew->method('ensureInstalled');
        $this->brew->method('startService');

        $this->database->install('mysql');
        $this->database->install('mysql');

        $config = $this->config->read();
        $count = array_count_values($config['databases']);
        $this->assertEquals(1, $count['mysql']);
    }

    public function test_uninstall(): void
    {
        // First install
        $this->brew->method('ensureInstalled');
        $this->brew->method('startService');
        $this->database->install('redis');

        // Now uninstall
        $this->brew->expects($this->once())
            ->method('stopService')
            ->with('redis');

        $this->cli->expects($this->once())
            ->method('runAsUser')
            ->with(BREW_PREFIX . '/bin/brew uninstall redis');

        $this->database->uninstall('redis');

        $config = $this->config->read();
        $this->assertNotContains('redis', $config['databases']);
    }

    public function test_uninstall_unsupported_does_nothing(): void
    {
        $this->brew->expects($this->never())
            ->method('stopService');

        $this->database->uninstall('sqlite');
    }

    public function test_start_specific_database(): void
    {
        $this->brew->expects($this->once())
            ->method('startService')
            ->with('mysql');

        $this->database->start('mysql');
    }

    public function test_stop_specific_database(): void
    {
        $this->brew->expects($this->once())
            ->method('stopService')
            ->with('mysql');

        $this->database->stop('mysql');
    }

    public function test_restart_specific_database(): void
    {
        $this->brew->expects($this->once())
            ->method('restartService')
            ->with('mysql');

        $this->database->restart('mysql');
    }

    public function test_is_running(): void
    {
        $this->brew->method('isStartedService')
            ->with('mysql')
            ->willReturn(true);

        $this->assertTrue($this->database->isRunning('mysql'));
    }

    public function test_is_running_unsupported_returns_false(): void
    {
        $this->assertFalse($this->database->isRunning('sqlite'));
    }

    public function test_status_running(): void
    {
        $this->brew->method('isStartedService')->willReturn(true);

        $this->assertEquals('Running', $this->database->status('mysql'));
    }

    public function test_status_stopped(): void
    {
        $this->brew->method('isStartedService')->willReturn(false);

        $this->assertEquals('Stopped', $this->database->status('mysql'));
    }

    public function test_installed_returns_databases_from_config(): void
    {
        $this->config->updateKey('databases', ['mysql', 'redis']);

        $this->assertEquals(['mysql', 'redis'], $this->database->installed());
    }

    public function test_installed_returns_empty_array_by_default(): void
    {
        $this->assertEquals([], $this->database->installed());
    }

    public function test_list_returns_all_supported_databases(): void
    {
        $this->brew->method('isStartedService')->willReturn(false);

        $list = $this->database->list();

        $this->assertCount(4, $list);

        $names = array_column($list, 'name');
        $this->assertContains('mysql', $names);
        $this->assertContains('postgresql', $names);
        $this->assertContains('mongodb', $names);
        $this->assertContains('redis', $names);
    }

    public function test_list_shows_installed_status(): void
    {
        $this->brew->method('isStartedService')->willReturn(true);
        $this->brew->method('ensureInstalled');
        $this->brew->method('startService');

        $this->database->install('mysql');

        $list = $this->database->list();

        $mysqlEntry = collect($list)->firstWhere('name', 'mysql');
        $this->assertTrue($mysqlEntry['installed']);

        $redisEntry = collect($list)->firstWhere('name', 'redis');
        $this->assertFalse($redisEntry['installed']);
        $this->assertEquals('Not Installed', $redisEntry['status']);
    }
}
