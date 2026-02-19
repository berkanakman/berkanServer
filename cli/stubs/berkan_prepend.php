<?php
// Berkan error display handler
// Reads hide_errors setting from config.json
$__berkan_config_path = ($_SERVER['HOME'] ?? posix_getpwuid(posix_geteuid())['dir']) . '/.config/berkan/config.json';
if (file_exists($__berkan_config_path)) {
    $__berkan_config = json_decode(file_get_contents($__berkan_config_path), true);
    if (!empty($__berkan_config['hide_errors'])) {
        error_reporting(0);
        ini_set('display_errors', '0');
    }
    unset($__berkan_config);
}
unset($__berkan_config_path);
