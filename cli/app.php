<?php

use Silly\Application;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Create the Silly application.
 */
$app = new Application('Berkan', \Berkan\Berkan::VERSION);

/**
 * Install Berkan services.
 */
$app->command('install', function (InputInterface $input, OutputInterface $output) {
    should_be_sudo();

    info('Installing Berkan...');
    output('');

    $helper = $this->getHelperSet()->get('question');
    $cli = resolve(\Berkan\CommandLine::class);

    // ============================================================
    // 0. Check & install prerequisites (Homebrew, Composer)
    // ============================================================

    // Check Homebrew
    $brewPaths = [BREW_PREFIX . '/bin/brew', '/usr/local/bin/brew', '/opt/homebrew/bin/brew'];
    $brewBin = null;
    foreach ($brewPaths as $path) {
        if (file_exists($path)) {
            $brewBin = $path;
            break;
        }
    }

    if (! $brewBin) {
        info('Homebrew is not installed. Installing Homebrew...');
        $cli->runAsUser('/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"');

        // Verify installation
        foreach ($brewPaths as $path) {
            if (file_exists($path)) {
                $brewBin = $path;
                break;
            }
        }
        if (! $brewBin) {
            warning('Homebrew installation failed. Please install manually: https://brew.sh');
            return;
        }
        info('Homebrew installed successfully.');
    } else {
        info('Homebrew: OK');
    }

    // Check Composer
    $composerBin = null;
    $composerPaths = [BREW_PREFIX . '/bin/composer', '/usr/local/bin/composer', '/opt/homebrew/bin/composer'];
    foreach ($composerPaths as $path) {
        if (file_exists($path)) {
            $composerBin = $path;
            break;
        }
    }

    if (! $composerBin) {
        info('Composer is not installed. Installing via Homebrew...');
        $cli->runAsUser($brewBin . ' install composer');

        foreach ($composerPaths as $path) {
            if (file_exists($path)) {
                $composerBin = $path;
                break;
            }
        }
        if (! $composerBin) {
            warning('Composer installation failed. Please install manually: https://getcomposer.org');
            return;
        }
        info('Composer installed successfully.');
    } else {
        info('Composer: OK');
    }

    output('');

    // ============================================================
    // 1. Web server selection
    // ============================================================
    $webServerQuestion = new ChoiceQuestion(
        'Which web server would you like to use?',
        ['Apache', 'Nginx'],
        0
    );
    $webServerChoice = $helper->ask($input, $output, $webServerQuestion);
    $webServer = strtolower($webServerChoice) === 'nginx' ? 'nginx' : 'apache';

    // ============================================================
    // 2. PHP version selection
    // ============================================================
    $phpVersionQuestion = new ChoiceQuestion(
        'Which PHP versions would you like to install? (comma-separated numbers)',
        ['8.4', '8.3', '8.2', '8.1', '8.0', '7.4', '7.3', '7.2', '7.1', '7.0', '5.6'],
        '0'
    );
    $phpVersionQuestion->setMultiselect(true);
    $phpVersions = $helper->ask($input, $output, $phpVersionQuestion);

    // ============================================================
    // 3. Database selection
    // ============================================================
    $dbQuestion = new ChoiceQuestion(
        'Which databases would you like to install? (comma-separated numbers, or press Enter to skip)',
        ['MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'None'],
        '4'
    );
    $dbQuestion->setMultiselect(true);
    $dbChoices = $helper->ask($input, $output, $dbQuestion);

    $dbMap = [
        'MySQL' => 'mysql',
        'PostgreSQL' => 'postgresql',
        'MongoDB' => 'mongodb',
        'Redis' => 'redis',
    ];

    $databases = [];
    foreach ($dbChoices as $choice) {
        if (isset($dbMap[$choice])) {
            $databases[] = $dbMap[$choice];
        }
    }

    // ============================================================
    // 4. Port conflict detection
    // ============================================================
    $httpPort = '80';
    $httpsPort = '443';

    $port80InUse = trim($cli->run('lsof -ti :80 2>/dev/null'));
    $port443InUse = trim($cli->run('lsof -ti :443 2>/dev/null'));

    if (! empty($port80InUse) || ! empty($port443InUse)) {
        output('');
        warning('Port conflict detected!');

        if (! empty($port80InUse)) {
            warning("  Port 80 is in use (PID: {$port80InUse})");
        }
        if (! empty($port443InUse)) {
            warning("  Port 443 is in use (PID: {$port443InUse})");
        }

        output('');

        $portQuestion = new ChoiceQuestion(
            'How would you like to resolve the port conflict?',
            [
                'Use default ports anyway (80/443) — stop conflicting services manually',
                'Use alternative ports (8080/8443)',
                'Use alternative ports (8888/8843)',
                'Enter custom ports',
            ],
            0
        );
        $portChoice = $helper->ask($input, $output, $portQuestion);

        if ($portChoice === 'Use alternative ports (8080/8443)') {
            $httpPort = '8080';
            $httpsPort = '8443';
        } elseif ($portChoice === 'Use alternative ports (8888/8843)') {
            $httpPort = '8888';
            $httpsPort = '8843';
        } elseif ($portChoice === 'Enter custom ports') {
            $portValidator = function ($value) {
                $port = (int) $value;
                if (! ctype_digit((string) $value) || $port < 1 || $port > 65535) {
                    throw new \RuntimeException('Port must be a number between 1 and 65535.');
                }
                return (string) $port;
            };

            $httpPortQ = new \Symfony\Component\Console\Question\Question('  HTTP port [80]: ', '80');
            $httpPortQ->setValidator($portValidator);
            $httpPort = $helper->ask($input, $output, $httpPortQ);

            $httpsPortQ = new \Symfony\Component\Console\Question\Question('  HTTPS port [443]: ', '443');
            $httpsPortQ->setValidator($portValidator);
            $httpsPort = $helper->ask($input, $output, $httpsPortQ);
        }

        info("Using ports: HTTP={$httpPort}, HTTPS={$httpsPort}");
    }

    // ============================================================
    // 5. Install configuration
    // ============================================================
    $config = resolve(\Berkan\Configuration::class);
    $config->install();
    $config->updateKey('web_server', $webServer);
    $config->updateKey('php_versions', $phpVersions);
    $config->updateKey('databases', $databases);
    $config->updateKey('http_port', $httpPort);
    $config->updateKey('https_port', $httpsPort);

    // Install web server
    resolve(\Berkan\Contracts\WebServer::class)->install();

    // ============================================================
    // 6. Install PHP versions
    // ============================================================
    $phpFpm = resolve(\Berkan\PhpFpm::class);
    $phpFpm->install();

    $brew = resolve(\Berkan\Brew::class);
    foreach ($phpVersions as $version) {
        $formula = 'php@' . $version;

        if (! $brew->installed($formula) && ! ($version === '8.4' && $brew->installed('php'))) {
            if ($brew->requiresTap($formula)) {
                $brew->tap(['shivammathur/php']);
            }
            $brew->ensureInstalled($formula);
        }
    }

    // ============================================================
    // 7. Install DNS
    // ============================================================
    resolve(\Berkan\DnsMasq::class)->install($config->read()['tld'] ?? 'test');

    // ============================================================
    // 8. Install databases
    // ============================================================
    if (! empty($databases)) {
        $database = resolve(\Berkan\Database::class);
        foreach ($databases as $db) {
            $database->install($db);
        }
    }

    resolve(\Berkan\Berkan::class)->symlinkToUsersBin();

    output('');
    info('Berkan installed successfully!');
    info('Web Server: ' . ucfirst($webServer));
    info('PHP Versions: ' . implode(', ', $phpVersions));
    if ($httpPort !== '80' || $httpsPort !== '443') {
        info("Ports: HTTP={$httpPort}, HTTPS={$httpsPort}");
    }
    if (! empty($databases)) {
        info('Databases: ' . implode(', ', $databases));
    }
    info('Use "berkan park" to serve a directory.');
})->descriptions('Install the Berkan services');

/**
 * Uninstall Berkan services.
 */
$app->command('uninstall [--force]', function ($force, InputInterface $input, OutputInterface $output) {
    should_be_sudo();

    if (! $force) {
        $helper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion('Are you sure you want to uninstall Berkan? [y/N] ', false);

        if (! $helper->ask($input, $output, $question)) {
            warning('Uninstall cancelled.');
            return;
        }
    }

    info('Uninstalling Berkan...');

    resolve(\Berkan\Contracts\WebServer::class)->uninstall();
    resolve(\Berkan\PhpFpm::class)->stop();
    resolve(\Berkan\DnsMasq::class)->uninstall();

    // Stop databases
    resolve(\Berkan\Database::class)->stop();

    resolve(\Berkan\Berkan::class)->unlinkFromUsersBin();
    resolve(\Berkan\Berkan::class)->removeSudoers();
    resolve(\Berkan\Berkan::class)->removeLoopback();

    if ($force) {
        resolve(\Berkan\Configuration::class)->uninstall();
    }

    info('Berkan has been uninstalled.');
})->descriptions('Uninstall the Berkan services');

/**
 * Trust Berkan with sudo.
 */
$app->command('trust', function () {
    should_be_sudo();
    resolve(\Berkan\Berkan::class)->trust();
})->descriptions('Configure sudoers file for Berkan');

/**
 * Start Berkan services.
 */
$app->command('start', function () {
    should_be_sudo();

    resolve(\Berkan\PhpFpm::class)->start();
    resolve(\Berkan\Contracts\WebServer::class)->start();
    resolve(\Berkan\DnsMasq::class)->restart();

    info('Berkan services have been started.');
})->descriptions('Start the Berkan services');

/**
 * Stop Berkan services.
 */
$app->command('stop', function () {
    should_be_sudo();

    resolve(\Berkan\Contracts\WebServer::class)->stop();
    resolve(\Berkan\PhpFpm::class)->stop();
    resolve(\Berkan\DnsMasq::class)->stop();

    info('Berkan services have been stopped.');
})->descriptions('Stop the Berkan services');

/**
 * Restart Berkan services.
 */
$app->command('restart', function () {
    should_be_sudo();

    resolve(\Berkan\PhpFpm::class)->restart();
    resolve(\Berkan\Contracts\WebServer::class)->restart();
    resolve(\Berkan\DnsMasq::class)->restart();

    info('Berkan services have been restarted.');
})->descriptions('Restart the Berkan services');

/**
 * Display Berkan status.
 */
$app->command('status', function () {
    resolve(\Berkan\Status::class)->display();
})->descriptions('Display the status of Berkan services');

/**
 * Park a directory with interactive project scanning.
 */
$app->command('park [path]', function (InputInterface $input, OutputInterface $output, $path = null) {
    $path = $path ?: getcwd();
    $path = realpath($path);

    if (! $path || ! is_dir($path)) {
        warning('The specified path does not exist or is not a directory.');
        return;
    }

    $config = resolve(\Berkan\Configuration::class);
    $config->addPath($path);

    $site = resolve(\Berkan\Site::class);
    $projects = $site->scanProjects($path);

    if (empty($projects)) {
        info("Path [{$path}] parked. No projects found yet.");
        return;
    }

    $helper = $this->getHelperSet()->get('question');
    $brew = resolve(\Berkan\Brew::class);
    $installedPhp = $brew->supportedPhpVersions();

    // PHP choices list
    $phpChoices = ['Default (global PHP)'];
    foreach ($installedPhp as $v) {
        $versionLabel = str_replace(['php@', 'php'], '', $v) ?: 'latest';
        $phpChoices[] = $versionLabel;
    }

    info("Found " . count($projects) . " projects in [{$path}]:");
    output('');

    // Ask if user wants to set PHP version for all projects at once
    $bulkChoices = array_merge(['Ask individually'], $phpChoices);
    $bulkQuestion = new ChoiceQuestion(
        '  Set PHP version for all projects, or ask individually?',
        $bulkChoices,
        0
    );
    $bulkChoice = $helper->ask($input, $output, $bulkQuestion);
    output('');

    $phpVersionsToRestart = [];

    foreach ($projects as $project) {
        if ($bulkChoice === 'Ask individually') {
            $question = new ChoiceQuestion(
                "  {$project['name']} [{$project['framework']}] — PHP version?",
                $phpChoices,
                0
            );
            $choice = $helper->ask($input, $output, $question);
        } else {
            $choice = $bulkChoice;
        }

        if ($choice !== 'Default (global PHP)') {
            $phpVersion = ($choice === 'latest') ? 'php' : 'php@' . $choice;
            $isBulk = $bulkChoice !== 'Ask individually';
            $site->isolate($project['name'], $phpVersion, $isBulk);
            $phpVersionsToRestart[$phpVersion] = true;
        }
    }

    output('');
    $phpFpm = resolve(\Berkan\PhpFpm::class);
    foreach (array_keys($phpVersionsToRestart) as $phpVersion) {
        $phpFpm->isolateVersion($phpVersion);
    }

    resolve(\Berkan\Contracts\WebServer::class)->restart();
    info("Path [{$path}] has been parked with " . count($projects) . " projects.");
})->descriptions('Register a directory as a parked path for Berkan');

/**
 * Display parked paths.
 */
$app->command('parked', function () {
    $paths = resolve(\Berkan\Configuration::class)->parkedPaths();

    if (empty($paths)) {
        info('No paths are parked.');
        return;
    }

    table(['Path'], array_map(function ($path) {
        return [$path];
    }, $paths));
})->descriptions('Display all parked paths');

/**
 * Forget a parked path.
 */
$app->command('forget [path]', function ($path = null) {
    $path = $path ?: getcwd();
    $path = realpath($path) ?: $path;

    resolve(\Berkan\Configuration::class)->removePath($path);

    info("The [{$path}] directory has been removed from Berkan's paths.");
})->descriptions('Remove a parked path from Berkan');

/**
 * Link a site.
 */
$app->command('link [name]', function ($name = null) {
    $name = strtolower($name ?: basename(getcwd()));
    $path = getcwd();

    resolve(\Berkan\Site::class)->link($path, $name);

    $tld = resolve(\Berkan\Configuration::class)->read()['tld'];

    info("A [{$name}] symbolic link has been created in [{$path}].");
    info("Site available at: http://{$name}.{$tld}");
})->descriptions('Link the current working directory to Berkan');

/**
 * Display linked sites.
 */
$app->command('links', function () {
    $links = resolve(\Berkan\Site::class)->links();

    if ($links->isEmpty()) {
        info('No sites are linked.');
        return;
    }

    $config = resolve(\Berkan\Configuration::class)->read();
    $tld = $config['tld'];
    $site = resolve(\Berkan\Site::class);
    $secured = $site->secured();
    $defaultPhp = $config['php_versions'][0] ?? 'php';

    table(['Site', 'SSL', 'URL', 'Path', 'PHP'], $links->map(function ($path, $name) use ($tld, $site, $secured, $defaultPhp) {
        $ssl = $secured->contains($name . '.' . $tld) ? 'X' : '';
        $protocol = $ssl ? 'https' : 'http';
        $isolated = $site->phpVersion($name);
        $php = $isolated ? str_replace(['php@', 'php'], '', $isolated) : $defaultPhp;

        return [$name, $ssl, "{$protocol}://{$name}.{$tld}", $path, $php];
    })->values()->all());
})->descriptions('Display all linked sites');

/**
 * Unlink a site.
 */
$app->command('unlink [name]', function ($name = null) {
    $name = strtolower($name ?: basename(getcwd()));

    resolve(\Berkan\Site::class)->unlink($name);

    info("The [{$name}] site has been unlinked.");
})->descriptions('Remove a linked Berkan site');

/**
 * Open a site in the browser.
 */
$app->command('open [name]', function ($name = null) {
    $name = strtolower($name ?: basename(getcwd()));
    $tld = resolve(\Berkan\Configuration::class)->read()['tld'];
    $secured = resolve(\Berkan\Site::class)->secured();
    $protocol = $secured->contains($name . '.' . $tld) ? 'https' : 'http';

    $url = escapeshellarg("{$protocol}://{$name}.{$tld}");
    resolve(\Berkan\CommandLine::class)->runAsUser("open {$url}");
})->descriptions('Open the site in your browser');

/**
 * Secure a site with HTTPS.
 */
$app->command('secure [name]', function ($name = null) {
    should_be_sudo();

    $name = strtolower($name ?: basename(getcwd()));

    resolve(\Berkan\Site::class)->secure($name);
})->descriptions('Secure the given site with a trusted TLS certificate');

/**
 * Display secured sites.
 */
$app->command('secured', function () {
    $secured = resolve(\Berkan\Site::class)->secured();

    if ($secured->isEmpty()) {
        info('No sites are secured.');
        return;
    }

    table(['Site'], $secured->map(function ($site) {
        return [$site];
    })->all());
})->descriptions('Display all secured sites');

/**
 * Unsecure a site.
 */
$app->command('unsecure [name]', function ($name = null) {
    should_be_sudo();

    $name = strtolower($name ?: basename(getcwd()));

    resolve(\Berkan\Site::class)->unsecure($name);
})->descriptions('Remove TLS certificate from the given site');

/**
 * Use a specific PHP version.
 */
$app->command('use [version]', function ($version) {
    if (! $version) {
        warning('Please provide a PHP version. Example: berkan use 8.3');
        return;
    }

    should_be_sudo();

    resolve(\Berkan\PhpFpm::class)->useVersion($version);
    resolve(\Berkan\Contracts\WebServer::class)->restart();
})->descriptions('Change the PHP version used by Berkan');

/**
 * Isolate a site to use a specific PHP version.
 */
$app->command('isolate [phpv]', function ($phpv) {
    if (! $phpv) {
        warning('Please provide a PHP version. Example: berkan isolate 8.4');
        return;
    }

    should_be_sudo();

    $site = strtolower(basename(getcwd()));

    // Normalize version input to Homebrew formula format
    // e.g. "8.5.3" → "php@8.5", "8.5" → "php@8.5", "7.3" → "php@7.3", "latest" → "php"
    $phpv = str_replace(['php@', 'php'], '', $phpv);
    if ($phpv === '' || $phpv === 'latest') {
        $phpVersion = 'php';
    } else {
        // Strip patch version: "8.5.3" → "8.5"
        $parts = explode('.', $phpv);
        $majorMinor = $parts[0] . '.' . ($parts[1] ?? '0');
        $phpVersion = 'php@' . $majorMinor;
    }

    $siteManager = resolve(\Berkan\Site::class);
    $siteManager->isolate($site, $phpVersion);
    resolve(\Berkan\PhpFpm::class)->isolateVersion($phpVersion);

    // Regenerate vhost config with the isolated PHP-FPM socket
    $tld = resolve(\Berkan\Configuration::class)->read()['tld'];
    $secured = $siteManager->secured();

    if ($secured->contains($site . '.' . $tld)) {
        $siteConf = $siteManager->buildSecureServer($site);
    } else {
        $siteConf = $siteManager->buildServer($site);
    }

    resolve(\Berkan\Contracts\WebServer::class)->installSite($site, $siteConf);
    resolve(\Berkan\Contracts\WebServer::class)->restart();
})->descriptions('Isolate the current site to use a specific PHP version');

/**
 * Remove isolation.
 */
$app->command('unisolate', function () {
    should_be_sudo();

    $site = strtolower(basename(getcwd()));
    $siteManager = resolve(\Berkan\Site::class);
    $version = $siteManager->phpVersion($site);

    if (! $version) {
        info('This site is not isolated.');
        return;
    }

    $siteManager->removeIsolation($site);
    resolve(\Berkan\PhpFpm::class)->removeIsolation($version);

    // Regenerate vhost config with the default PHP-FPM socket
    $tld = resolve(\Berkan\Configuration::class)->read()['tld'];
    $secured = $siteManager->secured();

    if ($secured->contains($site . '.' . $tld)) {
        $siteConf = $siteManager->buildSecureServer($site);
    } else {
        $siteConf = $siteManager->buildServer($site);
    }

    resolve(\Berkan\Contracts\WebServer::class)->installSite($site, $siteConf);
    resolve(\Berkan\Contracts\WebServer::class)->restart();
})->descriptions('Remove PHP version isolation from the current site');

/**
 * Display isolated sites.
 */
$app->command('isolated', function () {
    $isolated = resolve(\Berkan\Site::class)->isolated();

    if (empty($isolated)) {
        info('No sites are isolated.');
        return;
    }

    table(['Site', 'PHP Version'], array_map(function ($version, $site) {
        $displayVersion = str_replace(['php@', 'php'], '', $version) ?: 'latest';
        return [$site, $displayVersion];
    }, $isolated, array_keys($isolated)));
})->descriptions('Display all isolated sites');

/**
 * Display which PHP version is currently active.
 */
$app->command('which-php', function () {
    $version = resolve(\Berkan\PhpFpm::class)->currentVersion();
    output($version ?: 'No PHP version linked');
})->descriptions('Display the currently active PHP version');

/**
 * Run a PHP command using Berkan's PHP.
 */
$app->command('php', function () {
    warning('This command is handled by the berkan shell script.');
})->descriptions('Run PHP using Berkan\'s version');

/**
 * Run Composer using Berkan's PHP.
 */
$app->command('composer', function () {
    warning('This command is handled by the berkan shell script.');
})->descriptions('Run Composer using Berkan\'s PHP version');

/**
 * Share a site.
 */
$app->command('share [name]', function ($name = null) {
    warning('This command is handled by the berkan shell script.');
})->descriptions('Share the current site via a public URL');

/**
 * Set the share tool.
 */
$app->command('share-tool [tool]', function ($tool = null) {
    $config = resolve(\Berkan\Configuration::class);

    if ($tool) {
        if (! in_array($tool, ['ngrok', 'expose', 'cloudflared'])) {
            warning("Invalid share tool [{$tool}]. Use: ngrok, expose, or cloudflared.");
            return;
        }

        $config->updateKey('share_tool', $tool);
        info("Share tool set to [{$tool}].");
    } else {
        $currentTool = $config->read()['share_tool'] ?? 'ngrok';
        output("Current share tool: {$currentTool}");
    }
})->descriptions('Get or set the share tool (ngrok, expose, cloudflared)');

/**
 * Fetch share URL.
 */
$app->command('fetch-share-url', function () {
    $config = resolve(\Berkan\Configuration::class)->read();
    $tool = $config['share_tool'] ?? 'ngrok';

    $url = null;

    switch ($tool) {
        case 'ngrok':
            $url = resolve(\Berkan\Ngrok::class)->currentTunnelUrl();
            break;
        case 'expose':
            $url = resolve(\Berkan\Expose::class)->currentTunnelUrl();
            break;
        case 'cloudflared':
            $url = resolve(\Berkan\Cloudflared::class)->currentTunnelUrl();
            break;
    }

    if ($url) {
        output($url);
    } else {
        warning('No active tunnel found.');
    }
})->descriptions('Fetch the current share URL');

/**
 * Set Ngrok token.
 */
$app->command('set-ngrok-token [token]', function ($token) {
    if (! $token) {
        warning('Please provide a token. Example: berkan set-ngrok-token YOUR_TOKEN');
        return;
    }

    resolve(\Berkan\Ngrok::class)->setToken($token);
})->descriptions('Set the Ngrok auth token');

/**
 * Create a proxy.
 */
$app->command('proxy name url [--secure]', function ($name, $url, $secure) {
    should_be_sudo();

    $name = strtolower($name);

    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        warning("Invalid proxy URL: {$url}. Provide a valid URL (e.g. http://127.0.0.1:3000).");
        return;
    }

    resolve(\Berkan\Site::class)->proxyCreate($name, $url, $secure);
})->descriptions('Create a proxy site');

/**
 * List proxies.
 */
$app->command('proxies', function () {
    $proxies = resolve(\Berkan\Site::class)->proxies();

    if ($proxies->isEmpty()) {
        info('No proxies configured.');
        return;
    }

    $tld = resolve(\Berkan\Configuration::class)->read()['tld'];

    table(['Site', 'Proxy URL'], $proxies->map(function ($host, $name) use ($tld) {
        return ["{$name}.{$tld}", $host];
    })->values()->all());
})->descriptions('Display all proxy sites');

/**
 * Remove a proxy.
 */
$app->command('unproxy name', function ($name) {
    should_be_sudo();

    $name = strtolower($name);
    resolve(\Berkan\Site::class)->proxyDelete($name);
})->descriptions('Remove a proxy site');

/**
 * Run diagnostics.
 */
$app->command('diagnose', function () {
    resolve(\Berkan\Diagnose::class)->display();
})->descriptions('Run Berkan diagnostics');

/**
 * View logs.
 */
$app->command('log [service]', function ($service = null) {
    $homePath = resolve(\Berkan\Configuration::class)->homePath();
    $webServer = resolve(\Berkan\Configuration::class)->read()['web_server'] ?? 'apache';

    $logs = [
        'php' => $homePath . '/Log/php-error.log',
        'php-fpm' => $homePath . '/Log/php-fpm.log',
    ];

    if ($webServer === 'nginx') {
        $logs['nginx'] = $homePath . '/Log/nginx-error.log';
        $logs['access'] = $homePath . '/Log/nginx-access.log';
    } else {
        $logs['apache'] = $homePath . '/Log/apache-error.log';
        $logs['access'] = $homePath . '/Log/apache-access.log';
    }

    if ($service && isset($logs[$service])) {
        $logFile = $logs[$service];

        if (! file_exists($logFile)) {
            warning("Log file not found: {$logFile}");
            return;
        }

        resolve(\Berkan\CommandLine::class)->passthru("tail -f \"{$logFile}\"");
    } elseif ($service) {
        warning("Unknown log service [{$service}]. Available: " . implode(', ', array_keys($logs)));
    } else {
        table(['Service', 'Log File'], array_map(function ($path, $name) {
            $exists = file_exists($path) ? 'exists' : 'not found';
            return [$name, "{$path} ({$exists})"];
        }, $logs, array_keys($logs)));
    }
})->descriptions('View Berkan log files');

/**
 * Set the TLD.
 */
$app->command('tld [name]', function ($name = null) {
    $config = resolve(\Berkan\Configuration::class);

    if ($name) {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $name)) {
            warning("Invalid TLD: {$name}. Use only letters and numbers, starting with a letter.");
            return;
        }

        should_be_sudo();

        $oldTld = $config->read()['tld'];
        $config->updateKey('tld', $name);

        resolve(\Berkan\DnsMasq::class)->updateTld($oldTld, $name);
        resolve(\Berkan\Site::class)->resecureForNewTld($oldTld, $name);
        resolve(\Berkan\Contracts\WebServer::class)->installConfiguration();
        resolve(\Berkan\Contracts\WebServer::class)->installServer();
        resolve(\Berkan\Contracts\WebServer::class)->restart();

        info("TLD has been changed to [{$name}].");
    } else {
        output($config->read()['tld'] ?? 'test');
    }
})->descriptions('Get or set the TLD used for Berkan sites');

/**
 * Toggle directory listing.
 */
$app->command('directory-listing [toggle]', function ($toggle = null) {
    $config = resolve(\Berkan\Configuration::class);

    if ($toggle !== null) {
        $enabled = in_array(strtolower($toggle), ['on', '1', 'true', 'yes']);
        $config->updateKey('directory_listing', $enabled);

        resolve(\Berkan\Contracts\WebServer::class)->restart();

        info('Directory listing has been ' . ($enabled ? 'enabled' : 'disabled') . '.');
    } else {
        $enabled = $config->read()['directory_listing'] ?? false;
        output('Directory listing is ' . ($enabled ? 'enabled' : 'disabled'));
    }
})->descriptions('Toggle directory listing on or off');

/**
 * Display registered paths.
 */
$app->command('paths', function () {
    $paths = resolve(\Berkan\Configuration::class)->parkedPaths();

    if (empty($paths)) {
        info('No paths registered.');
        return;
    }

    foreach ($paths as $path) {
        output($path);
    }
})->descriptions('Display all registered paths');

/**
 * Get or set loopback address.
 */
$app->command('loopback [address]', function ($address = null) {
    $config = resolve(\Berkan\Configuration::class);

    if ($address) {
        if (! filter_var($address, FILTER_VALIDATE_IP)) {
            warning("Invalid IP address: {$address}");
            return;
        }

        should_be_sudo();

        $config->updateKey('loopback', $address);
        resolve(\Berkan\Berkan::class)->installLoopback($address);
        resolve(\Berkan\DnsMasq::class)->install($config->read()['tld']);
        resolve(\Berkan\Contracts\WebServer::class)->installConfiguration();
        resolve(\Berkan\Contracts\WebServer::class)->installServer();
        resolve(\Berkan\Contracts\WebServer::class)->restart();

        info("Loopback address has been set to [{$address}].");
    } else {
        output($config->read()['loopback'] ?? BERKAN_LOOPBACK);
    }
})->descriptions('Get or set the loopback address');

/**
 * Check if on latest version.
 */
$app->command('on-latest-version', function () {
    output(\Berkan\Berkan::version());
})->descriptions('Check if Berkan is on the latest version');

// ============================================================
// PHP Version Management Commands
// ============================================================

/**
 * Install a PHP version.
 */
$app->command('php:install version', function ($version) {
    should_be_sudo();

    $formula = starts_with($version, 'php') ? $version : 'php@' . $version;
    $brew = resolve(\Berkan\Brew::class);

    if ($brew->installed($formula)) {
        info("PHP {$version} is already installed.");
        return;
    }

    if ($brew->requiresTap($formula)) {
        $brew->tap(['shivammathur/php']);
    }

    $brew->installOrFail($formula);
    resolve(\Berkan\PhpFpm::class)->installConfiguration($formula);

    // Update config
    $config = resolve(\Berkan\Configuration::class);
    $configData = $config->read();
    $phpVersions = $configData['php_versions'] ?? [];
    $cleanVersion = str_replace(['php@', 'php'], '', $formula) ?: $version;

    if (! in_array($cleanVersion, $phpVersions)) {
        $phpVersions[] = $cleanVersion;
        $config->updateKey('php_versions', $phpVersions);
    }

    info("PHP {$version} has been installed.");
})->descriptions('Install a PHP version');

/**
 * Remove a PHP version.
 */
$app->command('php:remove version', function ($version) {
    should_be_sudo();

    $formula = starts_with($version, 'php') ? $version : 'php@' . $version;
    $brew = resolve(\Berkan\Brew::class);

    if (! $brew->installed($formula)) {
        warning("PHP {$version} is not installed.");
        return;
    }

    // Check if it's the active version
    $currentVersion = resolve(\Berkan\PhpFpm::class)->currentVersion();
    if ($currentVersion === $formula) {
        warning("Cannot remove PHP {$version} because it is currently in use.");
        return;
    }

    $brew->stopService($formula);
    resolve(\Berkan\CommandLine::class)->runAsUser(BREW_PREFIX . '/bin/brew uninstall ' . $formula);

    // Update config
    $config = resolve(\Berkan\Configuration::class);
    $configData = $config->read();
    $phpVersions = $configData['php_versions'] ?? [];
    $cleanVersion = str_replace(['php@', 'php'], '', $formula) ?: $version;

    if (($key = array_search($cleanVersion, $phpVersions)) !== false) {
        unset($phpVersions[$key]);
        $config->updateKey('php_versions', array_values($phpVersions));
    }

    info("PHP {$version} has been removed.");
})->descriptions('Remove a PHP version');

/**
 * List installed PHP versions.
 */
$app->command('php:list', function () {
    $brew = resolve(\Berkan\Brew::class);
    $installed = $brew->supportedPhpVersions();

    if (empty($installed)) {
        info('No PHP versions installed.');
        return;
    }

    $current = resolve(\Berkan\PhpFpm::class)->currentVersion();

    table(['PHP Version', 'Status', 'Active'], array_map(function ($version) use ($brew, $current) {
        $isActive = ($version === $current) ? 'Yes' : '';

        return [
            $version,
            $brew->isStartedService($version) ? 'Running' : 'Stopped',
            $isActive,
        ];
    }, $installed));
})->descriptions('List installed PHP versions');

// ============================================================
// Database Management Commands
// ============================================================

/**
 * Install a database.
 */
$app->command('db:install name', function ($name) {
    should_be_sudo();

    $name = strtolower($name);

    if (! isset(\Berkan\Database::SUPPORTED_DATABASES[$name])) {
        warning("Unsupported database: {$name}");
        warning('Supported databases: ' . implode(', ', array_keys(\Berkan\Database::SUPPORTED_DATABASES)));
        return;
    }

    resolve(\Berkan\Database::class)->install($name);
})->descriptions('Install a database (mysql, postgresql, mongodb, redis)');

/**
 * Uninstall a database.
 */
$app->command('db:uninstall name', function ($name) {
    should_be_sudo();

    $name = strtolower($name);
    resolve(\Berkan\Database::class)->uninstall($name);
})->descriptions('Uninstall a database');

/**
 * Start a database.
 */
$app->command('db:start [name]', function ($name = null) {
    should_be_sudo();

    $name = $name ? strtolower($name) : null;
    resolve(\Berkan\Database::class)->start($name);

    info($name ? ucfirst($name) . ' has been started.' : 'All databases have been started.');
})->descriptions('Start a database (or all installed databases)');

/**
 * Stop a database.
 */
$app->command('db:stop [name]', function ($name = null) {
    should_be_sudo();

    $name = $name ? strtolower($name) : null;
    resolve(\Berkan\Database::class)->stop($name);

    info($name ? ucfirst($name) . ' has been stopped.' : 'All databases have been stopped.');
})->descriptions('Stop a database (or all installed databases)');

/**
 * Restart a database.
 */
$app->command('db:restart [name]', function ($name = null) {
    should_be_sudo();

    $name = $name ? strtolower($name) : null;
    resolve(\Berkan\Database::class)->restart($name);

    info($name ? ucfirst($name) . ' has been restarted.' : 'All databases have been restarted.');
})->descriptions('Restart a database (or all installed databases)');

/**
 * List databases.
 */
$app->command('db:list', function () {
    $databases = resolve(\Berkan\Database::class)->list();

    table(
        ['Database', 'Label', 'Installed', 'Status'],
        array_map(function ($db) {
            return [
                $db['name'],
                $db['label'],
                $db['installed'] ? 'Yes' : 'No',
                $db['status'],
            ];
        }, $databases)
    );
})->descriptions('List all supported databases and their status');

// ============================================================
// Server Switch Command
// ============================================================

/**
 * Switch web server.
 */
$app->command('server:switch', function (InputInterface $input, OutputInterface $output) {
    should_be_sudo();

    $config = resolve(\Berkan\Configuration::class);
    $currentServer = $config->read()['web_server'] ?? 'apache';
    $newServer = $currentServer === 'apache' ? 'nginx' : 'apache';

    $helper = $this->getHelperSet()->get('question');
    $question = new ConfirmationQuestion(
        "Switch from " . ucfirst($currentServer) . " to " . ucfirst($newServer) . "? [y/N] ",
        false
    );

    if (! $helper->ask($input, $output, $question)) {
        warning('Server switch cancelled.');
        return;
    }

    info("Switching from " . ucfirst($currentServer) . " to " . ucfirst($newServer) . "...");

    // Stop current server
    resolve(\Berkan\Contracts\WebServer::class)->stop();

    // Update config
    $config->updateKey('web_server', $newServer);

    // Re-resolve the container to get the new server type
    // Clear both WebServer and Site singletons so they get re-created
    $container = \Illuminate\Container\Container::getInstance();
    $container->forgetInstance(\Berkan\Contracts\WebServer::class);
    $container->forgetInstance(\Berkan\Site::class);

    // Install the new server
    $webServer = resolve(\Berkan\Contracts\WebServer::class);
    $webServer->install();

    // Update sudoers
    resolve(\Berkan\Berkan::class)->trust();

    info("Successfully switched to " . ucfirst($newServer) . "!");
})->descriptions('Switch between Apache and Nginx');

/**
 * Toggle error display.
 */
$app->command('error action', function ($action) {
    $config = resolve(\Berkan\Configuration::class);
    $phpFpm = resolve(\Berkan\PhpFpm::class);

    if ($action === 'hide' || $action === 'show') {
        $config->updateKey('hide_errors', $action === 'hide');

        // Ensure prepend file and FPM pool configs are up to date
        $phpFpm->installPrependFile();
        $phpFpm->ensurePrependInPools();

        info('PHP errors are now ' . ($action === 'hide' ? 'hidden' : 'visible') . '.');
    } else {
        warning('Usage: berkan error hide|show');
    }
})->descriptions('Toggle PHP error display (hide/show)');

return $app;
