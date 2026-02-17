<?php

/**
 * Berkan PHP Finder
 *
 * This script finds a usable PHP 8.1+ binary on the system.
 * It checks Homebrew paths first, then falls back to system PHP.
 */

$brewPrefix = (PHP_OS_FAMILY === 'Darwin' && PHP_INT_SIZE === 8 && str_contains(php_uname('m'), 'arm'))
    ? '/opt/homebrew'
    : '/usr/local';

$candidates = [
    $brewPrefix . '/opt/php/bin/php',
    $brewPrefix . '/opt/php@8.4/bin/php',
    $brewPrefix . '/opt/php@8.3/bin/php',
    $brewPrefix . '/opt/php@8.2/bin/php',
    $brewPrefix . '/opt/php@8.1/bin/php',
    '/usr/bin/php',
];

foreach ($candidates as $candidate) {
    if (! file_exists($candidate)) {
        continue;
    }

    // Check if PHP version is 8.1+
    $version = trim(shell_exec($candidate . ' -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;" 2>/dev/null'));

    if (empty($version)) {
        continue;
    }

    $parts = explode('.', $version);
    $major = (int) $parts[0];
    $minor = (int) ($parts[1] ?? 0);

    if ($major > 8 || ($major === 8 && $minor >= 1)) {
        echo $candidate;
        exit(0);
    }
}

// Fallback: use the current PHP binary if it's 8.1+
if (PHP_MAJOR_VERSION > 8 || (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION >= 1)) {
    echo PHP_BINARY;
    exit(0);
}

exit(1);
