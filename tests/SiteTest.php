<?php

namespace Berkan\Tests;

use Berkan\CommandLine;
use Berkan\Configuration;
use Berkan\Contracts\WebServer;
use Berkan\Filesystem;
use Berkan\Site;
use PHPUnit\Framework\MockObject\MockObject;

class SiteTest extends TestCase
{
    protected Site $site;
    protected Configuration $config;
    protected CommandLine|MockObject $cli;
    protected Filesystem $files;
    protected WebServer|MockObject $webServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->cli = $this->createMock(CommandLine::class);
        $this->config = new Configuration($this->files);
        $this->webServer = $this->createMock(WebServer::class);

        // Create config directory and write base config
        $basePath = $this->config->path();
        mkdir($basePath, 0755, true);
        $this->config->writeBaseConfiguration();

        $this->site = new Site($this->config, $this->cli, $this->files);
        $this->site->setWebServer($this->webServer);
    }

    // === Basic Path Tests ===

    public function test_sites_path(): void
    {
        $expected = $this->config->homePath() . '/Sites';
        $this->assertEquals($expected, $this->site->sitesPath());
    }

    public function test_certificates_path(): void
    {
        $expected = $this->config->homePath() . '/Certificates';
        $this->assertEquals($expected, $this->site->certificatesPath());
    }

    // === Link Tests ===

    public function test_link_creates_symlink(): void
    {
        $target = $this->tempDir . '/my-project';
        mkdir($target);

        $result = $this->site->link($target, 'my-project');

        $this->assertEquals('my-project', $result);
        $this->assertTrue(is_link($this->site->sitesPath() . '/my-project'));
    }

    public function test_unlink_removes_symlink(): void
    {
        $target = $this->tempDir . '/my-project';
        mkdir($target);

        $this->site->link($target, 'my-project');
        $this->assertTrue(file_exists($this->site->sitesPath() . '/my-project'));

        $this->webServer->expects($this->once())
            ->method('removeSite')
            ->with('my-project');

        $this->site->unlink('my-project');
        $this->assertFalse(file_exists($this->site->sitesPath() . '/my-project'));
    }

    public function test_unlink_nonexistent_does_not_fail(): void
    {
        $this->site->unlink('nonexistent');
        $this->assertTrue(true);
    }

    public function test_links_returns_linked_sites(): void
    {
        $target1 = $this->tempDir . '/project1';
        $target2 = $this->tempDir . '/project2';
        mkdir($target1);
        mkdir($target2);

        $this->site->link($target1, 'project1');
        $this->site->link($target2, 'project2');

        $links = $this->site->links();

        $this->assertCount(2, $links);
        $this->assertEquals($target1, $links['project1']);
        $this->assertEquals($target2, $links['project2']);
    }

    public function test_links_returns_empty_collection_initially(): void
    {
        $links = $this->site->links();
        $this->assertCount(0, $links);
    }

    // === Parked Sites Tests ===

    public function test_parked_returns_directories_from_parked_paths(): void
    {
        $parkPath = $this->tempDir . '/Sites';
        mkdir($parkPath);
        mkdir($parkPath . '/project-a');
        mkdir($parkPath . '/project-b');
        file_put_contents($parkPath . '/not-a-dir.txt', 'file');

        $this->config->addPath($parkPath);

        $parked = $this->site->parked();

        $this->assertTrue($parked->has('project-a'));
        $this->assertTrue($parked->has('project-b'));
        $this->assertFalse($parked->has('not-a-dir.txt'));
    }

    public function test_parked_skips_hidden_directories(): void
    {
        $parkPath = $this->tempDir . '/Sites';
        mkdir($parkPath);
        mkdir($parkPath . '/project-a');
        mkdir($parkPath . '/.git');
        mkdir($parkPath . '/.idea');

        $this->config->addPath($parkPath);

        $parked = $this->site->parked();

        $this->assertTrue($parked->has('project-a'));
        $this->assertFalse($parked->has('.git'));
        $this->assertFalse($parked->has('.idea'));
    }

    public function test_parked_skips_nonexistent_paths(): void
    {
        $this->config->addPath('/nonexistent/path');

        $parked = $this->site->parked();
        $this->assertCount(0, $parked);
    }

    public function test_all_sites_merges_parked_and_linked(): void
    {
        $parkPath = $this->tempDir . '/Sites';
        mkdir($parkPath);
        mkdir($parkPath . '/parked-project');

        $linkTarget = $this->tempDir . '/linked-project';
        mkdir($linkTarget);

        $this->config->addPath($parkPath);
        $this->site->link($linkTarget, 'linked-project');

        $all = $this->site->allSites();

        $this->assertTrue($all->has('parked-project'));
        $this->assertTrue($all->has('linked-project'));
    }

    // === SSL/Secured Tests ===

    public function test_secured_returns_empty_when_no_certs(): void
    {
        $result = $this->site->secured();
        $this->assertCount(0, $result);
    }

    public function test_secured_returns_certificate_urls(): void
    {
        $certsPath = $this->site->certificatesPath();
        mkdir($certsPath, 0755, true);

        file_put_contents($certsPath . '/mysite.test.crt', 'cert');
        file_put_contents($certsPath . '/mysite.test.key', 'key');
        file_put_contents($certsPath . '/another.test.crt', 'cert');

        $secured = $this->site->secured();

        $this->assertContains('mysite.test', $secured->toArray());
        $this->assertContains('another.test', $secured->toArray());
    }

    public function test_secured_ignores_non_crt_files(): void
    {
        $certsPath = $this->site->certificatesPath();
        mkdir($certsPath, 0755, true);

        file_put_contents($certsPath . '/mysite.test.crt', 'cert');
        file_put_contents($certsPath . '/mysite.test.key', 'key');
        file_put_contents($certsPath . '/mysite.test.conf', 'conf');

        $secured = $this->site->secured();

        $this->assertCount(1, $secured);
    }

    // === GetSitePath Tests ===

    public function test_get_site_path_from_link(): void
    {
        $target = $this->tempDir . '/my-project';
        mkdir($target);

        $this->site->link($target, 'my-project');

        $path = $this->site->getSitePath('my-project');
        $this->assertEquals($target, $path);
    }

    public function test_get_site_path_from_parked(): void
    {
        $parkPath = $this->tempDir . '/Sites';
        mkdir($parkPath . '/my-project', 0755, true);

        $this->config->addPath($parkPath);

        $path = $this->site->getSitePath('my-project');
        $this->assertEquals($parkPath . '/my-project', $path);
    }

    public function test_get_site_path_returns_empty_for_unknown(): void
    {
        $path = $this->site->getSitePath('nonexistent');
        $this->assertEquals('', $path);
    }

    public function test_get_site_path_prefers_linked_over_parked(): void
    {
        $linkTarget = $this->tempDir . '/linked-target';
        mkdir($linkTarget);

        $parkPath = $this->tempDir . '/Sites';
        mkdir($parkPath . '/myapp', 0755, true);

        $this->config->addPath($parkPath);
        $this->site->link($linkTarget, 'myapp');

        $path = $this->site->getSitePath('myapp');
        $this->assertEquals($linkTarget, $path);
    }

    // === Proxy Tests ===

    public function test_proxies_returns_empty_when_no_proxies(): void
    {
        $proxies = $this->site->proxies();
        $this->assertCount(0, $proxies);
    }

    public function test_proxies_returns_proxy_entries(): void
    {
        $sitesPath = $this->site->sitesPath();
        mkdir($sitesPath, 0755, true);

        file_put_contents($sitesPath . '/myapp.proxy', 'http://localhost:3000');

        $proxies = $this->site->proxies();
        $this->assertEquals('http://localhost:3000', $proxies['myapp']);
    }

    public function test_proxies_ignores_non_proxy_files(): void
    {
        $sitesPath = $this->site->sitesPath();
        mkdir($sitesPath, 0755, true);

        file_put_contents($sitesPath . '/myapp.proxy', 'http://localhost:3000');
        file_put_contents($sitesPath . '/other.txt', 'text');

        $proxies = $this->site->proxies();
        $this->assertCount(1, $proxies);
    }

    // === Isolation Tests ===

    public function test_isolate_saves_php_version(): void
    {
        $this->site->isolate('mysite', '8.3');

        $config = $this->config->read();
        $this->assertEquals('8.3', $config['isolated_versions']['mysite']);
    }

    public function test_php_version_returns_isolated_version(): void
    {
        $this->site->isolate('mysite', '8.2');

        $this->assertEquals('8.2', $this->site->phpVersion('mysite'));
    }

    public function test_php_version_returns_null_for_non_isolated(): void
    {
        $this->assertNull($this->site->phpVersion('nonexistent'));
    }

    public function test_remove_isolation(): void
    {
        $this->site->isolate('mysite', '8.3');
        $this->site->removeIsolation('mysite');

        $this->assertNull($this->site->phpVersion('mysite'));
    }

    public function test_remove_isolation_on_non_isolated_site(): void
    {
        $this->site->removeIsolation('nonexistent');
        $this->assertNull($this->site->phpVersion('nonexistent'));
    }

    public function test_isolated_returns_all_isolated_sites(): void
    {
        $this->site->isolate('site1', '8.2');
        $this->site->isolate('site2', '8.3');

        $isolated = $this->site->isolated();

        $this->assertEquals('8.2', $isolated['site1']);
        $this->assertEquals('8.3', $isolated['site2']);
    }

    public function test_isolated_returns_empty_array_when_no_isolations(): void
    {
        $isolated = $this->site->isolated();
        $this->assertEquals([], $isolated);
    }

    public function test_isolate_creates_isolated_versions_key(): void
    {
        // Ensure config has no isolated_versions key
        $config = $this->config->read();
        $this->assertArrayNotHasKey('isolated_versions', $config);

        $this->site->isolate('mysite', 'php@7.4');

        $config = $this->config->read();
        $this->assertArrayHasKey('isolated_versions', $config);
        $this->assertEquals('php@7.4', $config['isolated_versions']['mysite']);
    }

    // === Scan Projects Tests ===

    public function test_scan_projects_returns_project_list(): void
    {
        $path = $this->tempDir . '/Sites';
        mkdir($path . '/project1', 0755, true);
        mkdir($path . '/project2', 0755, true);
        file_put_contents($path . '/not-a-dir.txt', 'file');

        $projects = $this->site->scanProjects($path);

        $this->assertCount(2, $projects);

        $names = array_column($projects, 'name');
        $this->assertContains('project1', $names);
        $this->assertContains('project2', $names);
    }

    public function test_scan_projects_includes_framework_detection(): void
    {
        $path = $this->tempDir . '/Sites';
        mkdir($path . '/laravel-app', 0755, true);
        file_put_contents($path . '/laravel-app/artisan', '#!/usr/bin/env php');

        $projects = $this->site->scanProjects($path);

        $this->assertCount(1, $projects);
        $this->assertEquals('Laravel', $projects[0]['framework']);
    }

    public function test_scan_projects_returns_empty_for_empty_dir(): void
    {
        $path = $this->tempDir . '/empty';
        mkdir($path, 0755, true);

        $projects = $this->site->scanProjects($path);
        $this->assertCount(0, $projects);
    }

    public function test_scan_projects_skips_hidden_directories(): void
    {
        $path = $this->tempDir . '/Sites';
        mkdir($path . '/project1', 0755, true);
        mkdir($path . '/.git', 0755, true);
        mkdir($path . '/.hidden', 0755, true);

        $projects = $this->site->scanProjects($path);

        $this->assertCount(1, $projects);
        $names = array_column($projects, 'name');
        $this->assertContains('project1', $names);
        $this->assertNotContains('.git', $names);
        $this->assertNotContains('.hidden', $names);
    }

    public function test_scan_projects_includes_full_path(): void
    {
        $path = $this->tempDir . '/Sites';
        mkdir($path . '/myapp', 0755, true);

        $projects = $this->site->scanProjects($path);

        $this->assertEquals($path . '/myapp', $projects[0]['path']);
    }

    // === Framework Detection Tests ===

    public function test_detect_framework_laravel(): void
    {
        $path = $this->tempDir . '/laravel';
        mkdir($path);
        file_put_contents($path . '/artisan', '#!/usr/bin/env php');

        $this->assertEquals('Laravel', $this->site->detectFramework($path));
    }

    public function test_detect_framework_symfony(): void
    {
        $path = $this->tempDir . '/symfony';
        mkdir($path . '/bin', 0755, true);
        mkdir($path . '/public', 0755, true);
        file_put_contents($path . '/bin/console', '#!/usr/bin/env php');
        file_put_contents($path . '/public/index.php', '<?php');

        $this->assertEquals('Symfony', $this->site->detectFramework($path));
    }

    public function test_detect_framework_wordpress(): void
    {
        $path = $this->tempDir . '/wordpress';
        mkdir($path);
        file_put_contents($path . '/wp-config.php', '<?php');

        $this->assertEquals('WordPress', $this->site->detectFramework($path));
    }

    public function test_detect_framework_drupal(): void
    {
        $path = $this->tempDir . '/drupal';
        mkdir($path . '/core/lib', 0755, true);
        file_put_contents($path . '/core/lib/Drupal.php', '<?php');

        $this->assertEquals('Drupal', $this->site->detectFramework($path));
    }

    public function test_detect_framework_magento2(): void
    {
        $path = $this->tempDir . '/magento';
        mkdir($path . '/bin', 0755, true);
        file_put_contents($path . '/bin/magento', '#!/usr/bin/env php');

        $this->assertEquals('Magento 2', $this->site->detectFramework($path));
    }

    public function test_detect_framework_craft_cms(): void
    {
        $path = $this->tempDir . '/craft';
        mkdir($path);
        file_put_contents($path . '/craft', '#!/usr/bin/env php');

        $this->assertEquals('Craft CMS', $this->site->detectFramework($path));
    }

    public function test_detect_framework_joomla(): void
    {
        $path = $this->tempDir . '/joomla';
        mkdir($path . '/administrator', 0755, true);
        file_put_contents($path . '/configuration.php', '<?php');

        $this->assertEquals('Joomla', $this->site->detectFramework($path));
    }

    public function test_detect_framework_composer_project(): void
    {
        $path = $this->tempDir . '/composer-project';
        mkdir($path);
        file_put_contents($path . '/composer.json', '{}');

        $this->assertEquals('PHP (Composer)', $this->site->detectFramework($path));
    }

    public function test_detect_framework_plain_php(): void
    {
        $path = $this->tempDir . '/plain';
        mkdir($path);
        file_put_contents($path . '/index.php', '<?php echo "hello";');

        $this->assertEquals('Plain PHP', $this->site->detectFramework($path));
    }

    public function test_detect_framework_static_unknown(): void
    {
        $path = $this->tempDir . '/static';
        mkdir($path);
        file_put_contents($path . '/index.html', '<html></html>');

        $this->assertEquals('Static/Unknown', $this->site->detectFramework($path));
    }

    public function test_detect_framework_empty_directory(): void
    {
        $path = $this->tempDir . '/empty';
        mkdir($path);

        $this->assertEquals('Static/Unknown', $this->site->detectFramework($path));
    }

    public function test_detect_framework_priority_laravel_over_composer(): void
    {
        // Laravel has both artisan and composer.json
        $path = $this->tempDir . '/laravel-full';
        mkdir($path);
        file_put_contents($path . '/artisan', '#!/usr/bin/env php');
        file_put_contents($path . '/composer.json', '{}');

        $this->assertEquals('Laravel', $this->site->detectFramework($path));
    }

    // === Build Server Tests (Port Placeholders) ===

    public function test_build_server_replaces_all_placeholders(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_HTTPS_PORT', $result);
        $this->assertStringNotContainsString('VALET_SITE_PATH', $result);
        $this->assertStringNotContainsString('VALET_HOME_PATH', $result);
        $this->assertStringNotContainsString('VALET_SERVER_PATH', $result);
        $this->assertStringNotContainsString('VALET_PHP_SOCKET', $result);
        $this->assertStringContainsString('myapp', $result);
        $this->assertStringContainsString('test', $result);
    }

    public function test_build_server_uses_default_ports(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        $this->assertStringContainsString(':80', $result);
    }

    public function test_build_server_uses_custom_ports(): void
    {
        $this->config->updateKey('http_port', '8080');
        $this->config->updateKey('https_port', '8443');

        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        $this->assertStringContainsString(':8080', $result);
    }

    public function test_build_server_uses_default_socket(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        $this->assertStringContainsString('berkan.sock', $result);
    }

    public function test_build_server_uses_isolated_socket(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');
        $this->site->isolate('myapp', 'php@7.4');

        $result = $this->site->buildServer('myapp');

        $this->assertStringContainsString('berkan-7.4.sock', $result);
    }

    // === Build Secure Server Tests ===

    public function test_build_secure_server_replaces_all_placeholders(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        // Create a fake cert and key
        $certsPath = $this->site->certificatesPath();
        mkdir($certsPath, 0755, true);
        file_put_contents($certsPath . '/myapp.test.crt', 'cert');
        file_put_contents($certsPath . '/myapp.test.key', 'key');

        $result = $this->site->buildSecureServer('myapp');

        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_HTTPS_PORT', $result);
        $this->assertStringNotContainsString('VALET_SITE', $result);
        $this->assertStringNotContainsString('VALET_TLD', $result);
        $this->assertStringNotContainsString('VALET_CERT', $result);
        $this->assertStringNotContainsString('VALET_KEY', $result);
        $this->assertStringNotContainsString('VALET_PHP_SOCKET', $result);
    }

    public function test_build_secure_server_uses_custom_ports(): void
    {
        $this->config->updateKey('http_port', '8080');
        $this->config->updateKey('https_port', '8443');

        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildSecureServer('myapp');

        $this->assertStringContainsString(':8080', $result);
        $this->assertStringContainsString(':8443', $result);
    }

    public function test_build_secure_server_uses_isolated_socket(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');
        $this->site->isolate('myapp', 'php@8.2');

        $result = $this->site->buildSecureServer('myapp');

        $this->assertStringContainsString('berkan-8.2.sock', $result);
    }

    // === Build Proxy Server Tests ===

    public function test_build_proxy_server_replaces_all_placeholders(): void
    {
        $result = $this->site->buildProxyServer('myapi', 'http://localhost:3000');

        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_SITE', $result);
        $this->assertStringNotContainsString('VALET_TLD', $result);
        $this->assertStringNotContainsString('VALET_PROXY_HOST', $result);
        $this->assertStringContainsString('myapi', $result);
        $this->assertStringContainsString('http://localhost:3000', $result);
    }

    public function test_build_proxy_server_uses_custom_port(): void
    {
        $this->config->updateKey('http_port', '8080');

        $result = $this->site->buildProxyServer('myapi', 'http://localhost:3000');

        $this->assertStringContainsString(':8080', $result);
    }

    public function test_build_proxy_server_extracts_ws_host(): void
    {
        $result = $this->site->buildProxyServer('myapi', 'http://localhost:3000');

        $this->assertStringContainsString('localhost:3000', $result);
    }

    // === Build Secure Proxy Server Tests ===

    public function test_build_secure_proxy_server_replaces_all_placeholders(): void
    {
        $certsPath = $this->site->certificatesPath();
        mkdir($certsPath, 0755, true);

        $result = $this->site->buildSecureProxyServer('myapi', 'http://localhost:3000');

        $this->assertStringNotContainsString('VALET_LOOPBACK', $result);
        $this->assertStringNotContainsString('VALET_HTTP_PORT', $result);
        $this->assertStringNotContainsString('VALET_HTTPS_PORT', $result);
        $this->assertStringNotContainsString('VALET_SITE', $result);
        $this->assertStringNotContainsString('VALET_TLD', $result);
        $this->assertStringNotContainsString('VALET_CERT', $result);
        $this->assertStringNotContainsString('VALET_KEY', $result);
        $this->assertStringNotContainsString('VALET_PROXY_HOST', $result);
    }

    public function test_build_secure_proxy_server_uses_custom_ports(): void
    {
        $this->config->updateKey('http_port', '8080');
        $this->config->updateKey('https_port', '8443');

        $result = $this->site->buildSecureProxyServer('myapi', 'http://localhost:3000');

        $this->assertStringContainsString(':8080', $result);
        $this->assertStringContainsString(':8443', $result);
    }

    // === Stub Prefix Tests ===

    public function test_build_server_uses_apache_stubs_by_default(): void
    {
        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        // Apache stubs contain VirtualHost
        $this->assertStringContainsString('VirtualHost', $result);
    }

    public function test_build_server_uses_nginx_stubs_when_configured(): void
    {
        $this->config->updateKey('web_server', 'nginx');

        $linkTarget = $this->tempDir . '/myapp-dir';
        mkdir($linkTarget);
        $this->site->link($linkTarget, 'myapp');

        $result = $this->site->buildServer('myapp');

        // Nginx stubs contain server {
        $this->assertStringContainsString('server', $result);
    }
}
