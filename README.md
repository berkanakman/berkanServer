<p align="center">
  <b>BERKAN</b><br>
  PHP Development Environment for macOS<br>
  <i>macOS için PHP Geliştirme Ortamı</i>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-8892BF?style=flat-square" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/macOS-Apple%20Silicon%20%26%20Intel-000000?style=flat-square" alt="macOS">
  <img src="https://img.shields.io/badge/Web%20Server-Apache%20%7C%20Nginx-D22128?style=flat-square" alt="Apache | Nginx">
  <img src="https://img.shields.io/badge/Database-MySQL%20%7C%20PostgreSQL%20%7C%20MongoDB%20%7C%20Redis-336791?style=flat-square" alt="Databases">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="MIT License">
  <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version 1.0.0">
</p>

---

# Table of Contents / İçindekiler

- [English](#english)
  - [What is Berkan?](#what-is-berkan)
  - [Features](#features)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Quick Start](#quick-start)
  - [Commands Reference](#commands-reference)
  - [Serving Sites](#serving-sites)
  - [HTTPS / SSL](#https--ssl)
  - [PHP Version Management](#php-version-management)
  - [Database Management](#database-management)
  - [Web Server Selection](#web-server-selection)
  - [Site Proxying](#site-proxying)
  - [Sharing Sites](#sharing-sites)
  - [Custom Drivers](#custom-drivers)
  - [Supported Frameworks](#supported-frameworks)
  - [Configuration](#configuration)
  - [Logs & Diagnostics](#logs--diagnostics)
  - [Architecture](#architecture)
  - [Troubleshooting](#troubleshooting)
  - [Uninstalling](#uninstalling)
- [Türkçe](#türkçe)
  - [Berkan Nedir?](#berkan-nedir)
  - [Özellikler](#özellikler)
  - [Gereksinimler](#gereksinimler)
  - [Kurulum](#kurulum)
  - [Hızlı Başlangıç](#hızlı-başlangıç)
  - [Komut Referansı](#komut-referansı)
  - [Site Sunma](#site-sunma)
  - [HTTPS / SSL Desteği](#https--ssl-desteği)
  - [PHP Versiyon Yönetimi](#php-versiyon-yönetimi)
  - [Veritabanı Yönetimi](#veritabanı-yönetimi)
  - [Web Sunucu Seçimi](#web-sunucu-seçimi)
  - [Site Proxy](#site-proxy)
  - [Site Paylaşımı](#site-paylaşımı)
  - [Özel Driver Yazma](#özel-driver-yazma)
  - [Desteklenen Framework'ler](#desteklenen-frameworkler)
  - [Konfigürasyon](#konfigürasyon)
  - [Log ve Tanı](#log-ve-tanı)
  - [Mimari](#mimari)
  - [Sorun Giderme](#sorun-giderme)
  - [Kaldırma](#kaldırma)

---

# English

## What is Berkan?

**Berkan** is a lightweight macOS PHP development environment that supports both **Apache (httpd)** and **Nginx** as web servers. Choose your preferred web server during installation, switch between them at any time, and manage PHP versions and databases from a single CLI.

Berkan configures your Mac to always run your chosen web server, PHP-FPM, and DnsMasq in the background when your machine starts. Then, using DnsMasq, Berkan proxies all requests on the `.test` TLD to point to sites installed on your local machine.

In other words, a blazing fast macOS PHP development environment that uses roughly **7 MB of RAM**. Berkan isn't a complete replacement for Vagrant or Docker, but it's a great alternative if you want a flexible, speed-focused baseline.

## Features

### Web Server (Apache or Nginx)

Berkan lets you choose between **Apache** and **Nginx** during installation, and switch at any time with `berkan server:switch`.

**Apache (httpd):**
- Native **`.htaccess` support** - your rewrite rules work exactly as they do on production
- **`mod_rewrite`** for URL routing and pretty URLs
- **`mod_ssl`** for TLS/HTTPS with self-signed trusted certificates
- **`mod_proxy` + `mod_proxy_fcgi`** for connecting Apache to PHP-FPM via Unix socket
- **`mod_proxy_http`** for reverse-proxying to other local services
- **`mod_headers`**, **`mod_deflate`**, **`mod_vhost_alias`**, **`mod_http2`**
- Automatic `apachectl configtest` validation before every restart

**Nginx:**
- High-performance event-driven architecture
- **`fastcgi_pass`** for connecting Nginx to PHP-FPM via Unix socket
- **SSL/TLS** with HTTP/2 support
- **Reverse proxy** with WebSocket support
- **Gzip compression** out of the box
- Automatic `nginx -t` validation before every restart

### Site Serving

- **Zero-configuration** - park a directory and every subfolder is instantly a `.test` site
- **Park** entire directories: `~/Sites/blog` becomes `http://blog.test`
- **Link** individual projects from anywhere on disk with custom names
- **Automatic framework detection** via the driver system (25+ built-in drivers)
- Custom **404 page** for sites that can't be found
- Configurable **directory listing** (on/off)

### HTTPS / SSL

- **One-command HTTPS**: `berkan secure myapp` generates a trusted TLS certificate
- **2048-bit RSA** private keys with **SAN (Subject Alternative Name)**
- Certificates are **automatically trusted** in the macOS System Keychain
- **HTTP-to-HTTPS redirect** is configured automatically
- **HTTP/2** enabled on all secured sites
- **HSTS header** added by default for secure sites
- Unsecure a site back to HTTP with a single command

### PHP Management

- **Global PHP version switching** between PHP 8.1, 8.2, 8.3, 8.4
- **Install/remove PHP versions** at any time with `berkan php:install` / `berkan php:remove`
- **List installed PHP versions** with `berkan php:list`
- **Per-site PHP isolation** - different sites can run different PHP versions simultaneously
- `berkan php` - run PHP CLI using Berkan's managed version
- `berkan composer` - run Composer with the correct PHP binary
- Automatic **PHP-FPM pool configuration** with Unix socket (`berkan.sock`)
- Memory limit, upload size, and execution time tuned for development (512 MB, no timeout)

### Database Management

- **Install and manage databases** directly from Berkan CLI
- Supported databases:
  - **MySQL** - `berkan db:install mysql`
  - **PostgreSQL** - `berkan db:install postgresql`
  - **MongoDB** - `berkan db:install mongodb`
  - **Redis** - `berkan db:install redis`
- **Start, stop, restart** individual or all databases
- **View database status** alongside other services with `berkan status` and `berkan db:list`
- Interactive database selection during `berkan install`

### DNS Resolution

- **DnsMasq** resolves all `*.test` (or any custom TLD) domains to your loopback address
- macOS **resolver file** (`/etc/resolver/test`) ensures seamless system-wide DNS
- **Custom TLD** support - change `.test` to `.dev`, `.local`, or anything else
- **Custom loopback address** - default `127.0.0.1`, configurable to any IP

### Proxy

- **Reverse proxy** any `.test` domain to another local service (Node.js, Go, Python, Docker, etc.)
- Automatic **WebSocket support**
- Proxy sites can optionally be **secured with HTTPS**

### Sharing

- Share local sites publicly via three tunneling tools:
  - **Ngrok** (default)
  - **Expose**
  - **Cloudflare Tunnel (cloudflared)**
- Switch between sharing tools with a single command
- Fetch the public tunnel URL programmatically

### Framework Drivers

- **25 built-in drivers** auto-detect your application framework
- Supported: Laravel, WordPress, Symfony, Drupal, CakePHP, Magento 2, Craft CMS, Statamic, Joomla, Kirby, TYPO3, Neos, Nette, Contao, Concrete5, Jigsaw, Sculpin, Katana, Bedrock, Radicle, and more
- **Custom driver API** - write your own driver for any PHP application by implementing three methods
- Driver priority: Custom > Specific > Default (BasicWithPublic, Basic)

### System Integration

- **Homebrew-native** - all services installed and managed via `brew services`
- **Sudoers integration** - `berkan trust` removes the need for repeated `sudo` passwords
- **Loopback plist** for persistent custom loopback addresses across reboots
- **Symlink to PATH** - `berkan` is accessible globally after install

### Diagnostics & Logging

- `berkan status` - see web server, PHP-FPM, DnsMasq, and database status at a glance
- `berkan diagnose` - comprehensive diagnostic report
- `berkan log` - tail web server, PHP, PHP-FPM, or access logs in real-time
- Separate log files per service in `~/.config/berkan/Log/`

## Requirements

- **macOS** (Apple Silicon or Intel)
- **[Homebrew](https://brew.sh)**
- **PHP 8.1+** (installed via Homebrew)
- No other web servers (Nginx, Apache) should be using port 80/443

## Installation

### Prerequisites

Before installing Berkan, make sure you have the following:

1. **macOS** (Apple Silicon or Intel)

2. **Homebrew** - If you don't have Homebrew installed, install it first:
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

3. **PHP 8.1+** - Install PHP via Homebrew if you don't already have it:
   ```bash
   brew install php
   ```

4. **Composer** - Install Composer (PHP dependency manager):
   ```bash
   brew install composer
   ```

5. **Port 80 and 443 must be free** - Make sure no other web server (Apache, Nginx, MAMP, XAMPP, etc.) is running on these ports:
   ```bash
   # Check if anything is using port 80
   sudo lsof -i :80

   # If macOS built-in Apache is running, stop it
   sudo apachectl stop
   sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
   ```

### Step 1: Clone the Repository

```bash
git clone https://github.com/berkanakman/berkanServer.git
cd berkanServer
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

This will install all required PHP packages (Illuminate Container, Symfony Console, Guzzle, etc.) into the `vendor/` directory.

### Step 3: Install Berkan Services

```bash
sudo berkan install
```

This is an interactive installer that will guide you through the setup:

**1. Web Server Selection:**
```
Which web server would you like to use?
  [0] Apache
  [1] Nginx
> 0
```
- **Apache** - Best if you use `.htaccess` files (Laravel, WordPress, etc.)
- **Nginx** - Best if you want high-performance event-driven architecture

**2. PHP Version Selection (multi-select):**
```
Which PHP versions would you like to install? (comma-separated, e.g. 0,1)
  [0] 8.4
  [1] 8.3
  [2] 8.2
  [3] 8.1
> 0,1
```
Select one or more PHP versions. The latest version you select will be set as the active version.

**3. Database Selection (multi-select):**
```
Which databases would you like to install? (comma-separated, or "none")
  [0] MySQL
  [1] PostgreSQL
  [2] MongoDB
  [3] Redis
  [4] None
> 0,3
```
Select the databases you need, or choose "None" to skip. You can always install databases later with `berkan db:install`.

### Step 4: Trust Berkan (Recommended)

This step adds Berkan to sudoers so you don't need to type your password for common operations like `start`, `stop`, `restart`, `secure`, etc.:

```bash
sudo berkan trust
```

### Step 5: Verify Installation

```bash
berkan status
```

You should see all services running:

```
+----------------+---------+
| Service        | Status  |
+----------------+---------+
| Apache (httpd) | Running |
| PHP-FPM        | Running |
| DnsMasq        | Running |
| MySQL          | Running |
| Redis          | Running |
+----------------+---------+
```

Run diagnostics to make sure everything is correctly configured:

```bash
berkan diagnose
```

### Step 6: Park Your Projects Directory

```bash
mkdir -p ~/Sites
cd ~/Sites
berkan park
```

Now any folder you create inside `~/Sites/` will automatically be accessible as `http://folder-name.test` in your browser.

### What `berkan install` Does (Step by Step)

1. Asks you to choose a web server (Apache or Nginx), PHP versions, and databases
2. Creates the configuration directory at `~/.config/berkan/`
3. Creates subdirectories: `Apache/` or `Nginx/`, `Certificates/`, `Drivers/`, `Sites/`, `Log/`, `dnsmasq.d/`
4. Saves your choices to `~/.config/berkan/config.json`
5. Installs the chosen web server via Homebrew (if not already installed)
6. Writes the main server configuration (`httpd.conf` for Apache or `nginx.conf` for Nginx)
7. Creates the default catch-all server configuration (routes all `.test` requests to `server.php`)
8. Configures PHP-FPM with a `berkan` pool using Unix socket (`~/.config/berkan/berkan.sock`)
9. Installs additional PHP versions you selected via Homebrew
10. Installs and configures DnsMasq to resolve all `*.test` domains to `127.0.0.1`
11. Creates macOS resolver file at `/etc/resolver/test`
12. Installs and starts selected databases via Homebrew
13. Starts all services (`brew services start`)
14. Symlinks the `berkan` executable to `/usr/local/bin/berkan`
15. Copies a sample custom driver to `~/.config/berkan/Drivers/`

### Updating Berkan

```bash
cd /path/to/berkanServer
git pull origin main
composer install
sudo berkan restart
```

## Quick Start

```bash
# 1. Install Berkan (interactive)
sudo berkan install

# 2. Park your projects directory
cd ~/Sites
berkan park

# 3. Create a Laravel project
composer create-project laravel/laravel myapp

# 4. Visit in browser
# http://myapp.test

# 5. Secure with HTTPS
sudo berkan secure myapp

# 6. Visit with HTTPS
# https://myapp.test
```

That's it. Every folder inside `~/Sites/` is now automatically available as `http://folder-name.test`.

## Commands Reference

### Service Management

| Command | Description |
|---------|-------------|
| `berkan install` | Install Berkan services (interactive web server, PHP, database selection) |
| `berkan uninstall` | Uninstall Berkan (asks for confirmation) |
| `berkan uninstall --force` | Force uninstall and remove all configuration |
| `berkan start` | Start all Berkan services |
| `berkan stop` | Stop all Berkan services |
| `berkan restart` | Restart all Berkan services |
| `berkan status` | Display service status table |
| `berkan trust` | Add Berkan to sudoers (no more password prompts) |

### Site Management

| Command | Description |
|---------|-------------|
| `berkan park [path]` | Register directory as a parked path (default: current dir) |
| `berkan parked` | List all parked directories |
| `berkan forget [path]` | Remove a parked path |
| `berkan link [name]` | Create a symbolic link for the current directory |
| `berkan links` | List all linked sites with SSL status and URLs |
| `berkan unlink [name]` | Remove a linked site |
| `berkan open [name]` | Open site in default browser |
| `berkan paths` | Display all registered paths |

### HTTPS / SSL

| Command | Description |
|---------|-------------|
| `berkan secure [name]` | Enable HTTPS with a self-signed trusted certificate |
| `berkan secured` | List all secured sites |
| `berkan unsecure [name]` | Remove HTTPS and revert to HTTP |

### PHP Management

| Command | Description |
|---------|-------------|
| `berkan use <version>` | Switch global PHP version (e.g., `berkan use 8.3`) |
| `berkan php:install <version>` | Install a new PHP version (e.g., `berkan php:install 8.2`) |
| `berkan php:remove <version>` | Remove a PHP version |
| `berkan php:list` | List all installed PHP versions with status |
| `berkan which-php` | Display the currently active PHP version |
| `berkan php [args...]` | Run PHP CLI with Berkan's version |
| `berkan composer [args...]` | Run Composer with Berkan's PHP version |
| `berkan isolate <version>` | Isolate current site to a specific PHP version |
| `berkan unisolate` | Remove PHP isolation from current site |
| `berkan isolated` | List all isolated sites and their PHP versions |

### Database Management

| Command | Description |
|---------|-------------|
| `berkan db:install <name>` | Install a database (`mysql`, `postgresql`, `mongodb`, `redis`) |
| `berkan db:uninstall <name>` | Uninstall a database |
| `berkan db:start [name]` | Start a database (or all installed databases) |
| `berkan db:stop [name]` | Stop a database (or all installed databases) |
| `berkan db:restart [name]` | Restart a database (or all installed databases) |
| `berkan db:list` | List all supported databases and their status |

### Web Server

| Command | Description |
|---------|-------------|
| `berkan server:switch` | Switch between Apache and Nginx |

### Proxy

| Command | Description |
|---------|-------------|
| `berkan proxy <name> <url>` | Create a proxy (e.g., `berkan proxy myapi http://localhost:3000`) |
| `berkan proxy <name> <url> --secure` | Create a proxy with HTTPS |
| `berkan proxies` | List all proxy sites |
| `berkan unproxy <name>` | Remove a proxy |

### Sharing

| Command | Description |
|---------|-------------|
| `berkan share [name]` | Share a site publicly via tunnel |
| `berkan share-tool [tool]` | Get or set the sharing tool (`ngrok`, `expose`, `cloudflared`) |
| `berkan fetch-share-url` | Fetch the current share URL |
| `berkan set-ngrok-token <token>` | Set Ngrok auth token |

### Configuration

| Command | Description |
|---------|-------------|
| `berkan tld [name]` | Get or set the TLD (default: `test`) |
| `berkan loopback [address]` | Get or set the loopback IP (default: `127.0.0.1`) |
| `berkan directory-listing [on/off]` | Toggle directory listing |
| `berkan on-latest-version` | Display current Berkan version |

### Diagnostics

| Command | Description |
|---------|-------------|
| `berkan diagnose` | Run full diagnostics and display results |
| `berkan log [service]` | View logs (`apache`/`nginx`, `php`, `php-fpm`, `access`) |
| `berkan log` | List all available log files |

## Serving Sites

Berkan supports two ways to serve your PHP projects:

### The `park` Command

The `park` command registers a directory. Every subdirectory within a parked directory becomes automatically accessible as a `.test` site.

```bash
cd ~/Sites
berkan park

# Now:
# ~/Sites/blog       -> http://blog.test
# ~/Sites/myapp      -> http://myapp.test
# ~/Sites/api        -> http://api.test
```

You can park multiple directories:

```bash
berkan park ~/Projects
berkan park ~/Work/clients

# See all parked paths
berkan parked
```

To stop serving a directory:

```bash
cd ~/Sites
berkan forget

# Or specify a path
berkan forget ~/Projects
```

### The `link` Command

The `link` command creates a symbolic link for a single site. This is useful when you want to serve a single directory from a non-parked location, or when you want the site name to differ from the folder name.

```bash
cd ~/some/deep/nested/project
berkan link mysite

# Now accessible at: http://mysite.test
```

```bash
# List all links
berkan links

# Output:
# +--------+-----+---------------------+-------------------------------+
# | Site   | SSL | URL                 | Path                          |
# +--------+-----+---------------------+-------------------------------+
# | mysite |     | http://mysite.test  | /Users/you/some/deep/nested.. |
# | blog   | X   | https://blog.test   | /Users/you/Sites/blog         |
# +--------+-----+---------------------+-------------------------------+
```

```bash
# Remove a link
berkan unlink mysite
```

### How Request Routing Works

1. A request for `myapp.test` arrives at your web server (Apache or Nginx)
2. The catch-all server configuration routes it to `server.php`
3. `server.php` extracts the site name from the hostname
4. It looks for a matching linked site or parked directory
5. The driver system auto-detects the framework (Laravel, WordPress, etc.)
6. The appropriate front controller is loaded and executed

## HTTPS / SSL

Berkan can secure any site with a trusted self-signed TLS certificate with a single command. This works identically whether you're using Apache or Nginx.

### Securing a Site

```bash
# Secure by name
sudo berkan secure myapp

# Or cd into the project and secure without name
cd ~/Sites/myapp
sudo berkan secure
```

This will:
1. Generate a 2048-bit RSA private key
2. Create a self-signed certificate with SAN (Subject Alternative Name)
3. Trust the certificate in the macOS System Keychain
4. Create the appropriate web server configuration (Apache VirtualHost or Nginx server block)
5. Set up HTTP-to-HTTPS redirect
6. Enable HTTP/2 support
7. Add HSTS header
8. Restart the web server

### Unsecuring a Site

```bash
sudo berkan unsecure myapp
```

This removes the certificate, untrusts it from Keychain, and removes the SSL configuration.

### Listing Secured Sites

```bash
berkan secured
```

## PHP Version Management

### Global PHP Switching

```bash
# Switch to PHP 8.3
sudo berkan use 8.3

# Switch to PHP 8.2
sudo berkan use 8.2

# Check current version
berkan which-php
# Output: php@8.3
```

### Installing and Removing PHP Versions

```bash
# Install a new PHP version
sudo berkan php:install 8.2

# Remove a PHP version (cannot remove the active version)
sudo berkan php:remove 8.1

# List all installed PHP versions
berkan php:list

# Output:
# +-------------+---------+--------+
# | PHP Version | Status  | Active |
# +-------------+---------+--------+
# | php@8.4     | Running | Yes    |
# | php@8.3     | Stopped |        |
# | php@8.2     | Stopped |        |
# +-------------+---------+--------+
```

### Per-Site PHP Isolation

You can isolate individual sites to use different PHP versions:

```bash
cd ~/Sites/legacy-app
berkan isolate 8.1

cd ~/Sites/modern-app
berkan isolate 8.4

# List isolated sites
berkan isolated
# +------------+-------------+
# | Site       | PHP Version |
# +------------+-------------+
# | legacy-app | 8.1         |
# | modern-app | 8.4         |
# +------------+-------------+

# Remove isolation
cd ~/Sites/legacy-app
berkan unisolate
```

### Using PHP CLI

```bash
# Run PHP with Berkan's version
berkan php -v
berkan php artisan migrate
berkan php script.php

# Run Composer with Berkan's PHP
berkan composer install
berkan composer require laravel/framework
```

## Database Management

Berkan can install and manage databases directly from the CLI.

### Supported Databases

| Database | Install Command | Formula |
|----------|----------------|---------|
| MySQL | `berkan db:install mysql` | `mysql` |
| PostgreSQL | `berkan db:install postgresql` | `postgresql@17` |
| MongoDB | `berkan db:install mongodb` | `mongodb-community` |
| Redis | `berkan db:install redis` | `redis` |

### Installing a Database

```bash
# Install MySQL
sudo berkan db:install mysql

# Install PostgreSQL
sudo berkan db:install postgresql

# Install Redis
sudo berkan db:install redis
```

MongoDB requires the `mongodb/brew` tap, which Berkan handles automatically.

### Managing Databases

```bash
# Start a specific database
sudo berkan db:start mysql

# Stop all databases
sudo berkan db:stop

# Restart PostgreSQL
sudo berkan db:restart postgresql

# Uninstall a database
sudo berkan db:uninstall mongodb
```

### Checking Database Status

```bash
berkan db:list

# Output:
# +------------+------------+-----------+---------------+
# | Database   | Label      | Installed | Status        |
# +------------+------------+-----------+---------------+
# | mysql      | MySQL      | Yes       | Running       |
# | postgresql | PostgreSQL | Yes       | Running       |
# | mongodb    | MongoDB    | No        | Not Installed |
# | redis      | Redis      | Yes       | Stopped       |
# +------------+------------+-----------+---------------+
```

Installed databases also appear in `berkan status`:

```
+----------------+---------+
| Service        | Status  |
+----------------+---------+
| Apache (httpd) | Running |
| PHP-FPM        | Running |
| DnsMasq        | Running |
| MySQL          | Running |
| PostgreSQL     | Running |
+----------------+---------+
```

## Web Server Selection

### Choosing During Installation

When you run `sudo berkan install`, Berkan asks which web server you want:

```
Which web server would you like to use?
  [0] Apache
  [1] Nginx
```

### Switching Web Servers

You can switch between Apache and Nginx at any time:

```bash
sudo berkan server:switch
```

This will:
1. Confirm the switch (e.g., "Switch from Apache to Nginx?")
2. Stop the current web server
3. Update the configuration
4. Install and configure the new web server
5. Update sudoers entries
6. Start the new web server

All your sites, certificates, and configurations continue to work seamlessly after switching.

### Differences Between Apache and Nginx

| Feature | Apache | Nginx |
|---------|--------|-------|
| `.htaccess` support | Native | Not supported |
| Config test command | `apachectl configtest` | `nginx -t` |
| Config directory | `~/.config/berkan/Apache/` | `~/.config/berkan/Nginx/` |
| Log prefix | `apache-error.log` | `nginx-error.log` |
| PHP connection | `mod_proxy_fcgi` (Unix socket) | `fastcgi_pass` (Unix socket) |
| Proxy support | `mod_proxy_http` | `proxy_pass` |

## Site Proxying

Berkan can proxy `.test` domains to other local services like Node.js, Go, Python, or Docker containers.

```bash
# Proxy myapi.test to a Node.js app on port 3000
sudo berkan proxy myapi http://localhost:3000

# Proxy with HTTPS
sudo berkan proxy myapi http://localhost:3000 --secure

# List all proxies
berkan proxies
# +-----------+------------------------+
# | Site      | Proxy URL              |
# +-----------+------------------------+
# | myapi.test| http://localhost:3000  |
# +-----------+------------------------+

# Remove a proxy
sudo berkan unproxy myapi
```

Proxy configurations include WebSocket support for both Apache and Nginx.

## Sharing Sites

Berkan supports three tunneling tools for sharing local sites publicly:

### Using Ngrok (Default)

```bash
# Set your Ngrok auth token (one-time)
berkan set-ngrok-token YOUR_TOKEN

# Share a site
berkan share myapp

# In another terminal, get the URL
berkan fetch-share-url
```

### Using Expose

```bash
berkan share-tool expose
berkan share myapp
```

### Using Cloudflare Tunnel

```bash
berkan share-tool cloudflared
berkan share myapp
```

### Checking Current Tool

```bash
berkan share-tool
# Output: Current share tool: ngrok
```

## Custom Drivers

If Berkan doesn't auto-detect your application, you can write a custom driver.

### Creating a Driver

A sample driver is placed at `~/.config/berkan/Drivers/SampleBerkanDriver.php` during installation. Create a new file in that directory:

```php
<?php

use Berkan\Drivers\BerkanDriver;

class MyCustomBerkanDriver extends BerkanDriver
{
    /**
     * Determine if the driver serves the request.
     * Return true if this driver should handle sites at $sitePath.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/my-app-config.php');
    }

    /**
     * Determine if the incoming request is for a static file.
     * Return the full file path, or false if not a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        $staticPath = $sitePath . '/public' . $uri;

        if (file_exists($staticPath) && !is_dir($staticPath)) {
            return $staticPath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/public/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/public';

        return $sitePath . '/public/index.php';
    }
}
```

### Driver Resolution Order

1. **Custom drivers** in `~/.config/berkan/Drivers/` (highest priority)
2. **Specific drivers** (WordPress, Laravel, Symfony, etc.)
3. **Default drivers** (`BasicWithPublicBerkanDriver`, `BasicBerkanDriver`)

The first driver whose `serves()` method returns `true` will handle the request.

## Supported Frameworks

Berkan includes built-in drivers for **25 frameworks and CMS platforms**:

| Framework | Detection |
|-----------|-----------|
| **Laravel** | `artisan` or `bootstrap/app.php` + `public/index.php` |
| **WordPress** | `wp-config.php` + `wp-admin/` |
| **WordPress Bedrock** | `web/wp/wp-settings.php` |
| **Symfony** | `bin/console` + `public/index.php` |
| **Drupal** | `core/lib/Drupal.php` |
| **Joomla** | `configuration.php` + `libraries/` |
| **CakePHP** | `config/app.php` + `webroot/index.php` |
| **Magento 2** | `bin/magento` + `pub/index.php` |
| **Craft CMS** | `craft` file |
| **Statamic v3+** | Laravel + `statamic/cms` in composer.json |
| **Statamic v2** | `statamic/` dir + `please` file |
| **Statamic v1** | `_app/core/statamic.php` |
| **Kirby CMS** | `kirby/` directory |
| **Contao** | `system/initialize.php` or `vendor/contao/` |
| **Concrete5** | `concrete/dispatcher.php` |
| **TYPO3** | `typo3/` dir + `typo3conf/` or `config/system/settings.php` |
| **Neos CMS** | `flow` file |
| **Nette** | `app/bootstrap.php` + `www/index.php` |
| **Jigsaw** | `build_local/` directory |
| **Sculpin** | `output_dev/` or `output_prod/` directory |
| **Katana** | `public/_katana/` directory |
| **Radicle** | `web/wp/wp-settings.php` + `config/application.php` |
| **Basic with /public** | Any project with a `public/` directory |
| **Basic** | Fallback for any PHP project |

## Configuration

### Configuration File

The main configuration file is at `~/.config/berkan/config.json`:

```json
{
    "tld": "test",
    "loopback": "127.0.0.1",
    "paths": [
        "/Users/you/Sites",
        "/Users/you/Projects"
    ],
    "web_server": "apache",
    "php_versions": ["8.4", "8.3"],
    "databases": ["mysql", "redis"]
}
```

| Key | Description | Default |
|-----|-------------|---------|
| `tld` | Top-level domain for sites | `test` |
| `loopback` | Loopback IP address | `127.0.0.1` |
| `paths` | Parked directories | `[]` |
| `web_server` | Active web server (`apache` or `nginx`) | `apache` |
| `php_versions` | Installed PHP versions | `["8.4"]` |
| `databases` | Installed databases | `[]` |

### Configuration Directories

| Directory | Purpose |
|-----------|---------|
| `~/.config/berkan/config.json` | Main configuration |
| `~/.config/berkan/Apache/` | Apache VirtualHost configurations (when using Apache) |
| `~/.config/berkan/Nginx/` | Nginx server configurations (when using Nginx) |
| `~/.config/berkan/Certificates/` | SSL certificates and keys |
| `~/.config/berkan/Drivers/` | Custom user drivers |
| `~/.config/berkan/Sites/` | Symbolic links for linked sites |
| `~/.config/berkan/Log/` | Web server, PHP, and PHP-FPM logs |
| `~/.config/berkan/dnsmasq.d/` | DnsMasq TLD configuration |
| `~/.config/berkan/berkan.sock` | PHP-FPM Unix socket |

### System Files

| File | Purpose |
|------|---------|
| `/etc/resolver/test` | macOS DNS resolver for `.test` TLD |
| `/etc/sudoers.d/berkan` | Sudoers entries (after `berkan trust`) |
| `$(brew --prefix)/etc/httpd/httpd.conf` | Apache main configuration (when using Apache) |
| `$(brew --prefix)/etc/nginx/nginx.conf` | Nginx main configuration (when using Nginx) |
| `$(brew --prefix)/etc/dnsmasq.conf` | DnsMasq main configuration |

### Changing the TLD

```bash
# Change from .test to .dev
sudo berkan tld dev

# Now sites are at *.dev instead of *.test
# http://myapp.dev

# Check current TLD
berkan tld
# Output: dev
```

### Changing the Loopback Address

```bash
sudo berkan loopback 10.200.10.1

# Check current loopback
berkan loopback
# Output: 10.200.10.1
```

## Logs & Diagnostics

### Viewing Logs

```bash
# List all log files
berkan log

# Tail web server error log in real-time
berkan log apache    # if using Apache
berkan log nginx     # if using Nginx

# Tail PHP error log
berkan log php

# Tail PHP-FPM log
berkan log php-fpm

# Tail access log
berkan log access
```

### Running Diagnostics

```bash
berkan diagnose
```

This displays a comprehensive diagnostic report:

```
Berkan Diagnostics
==================================================
+-------------------------+-----------------------------------+
| Check                   | Result                            |
+-------------------------+-----------------------------------+
| Berkan Version          | 1.0.0                             |
| PHP Version             | 8.4.18                            |
| PHP Binary              | /opt/homebrew/opt/php@8.4/bin/php |
| Operating System        | Darwin 24.6.0                     |
| Homebrew Prefix         | /opt/homebrew                     |
| TLD                     | test                              |
| Loopback                | 127.0.0.1                         |
| Web Server              | apache                            |
| Apache (httpd) Status   | Running                           |
| PHP-FPM Status          | Running                           |
| DnsMasq Status          | Running                           |
| Apache (httpd) Config   | Found                             |
| PHP-FPM Socket          | Found                             |
| DNS Resolver            | Found                             |
| Installed PHP           | php@8.4, php@8.3                  |
| Linked PHP              | php@8.4                           |
| Apache (httpd) Config T.| Syntax OK                         |
| Parked Paths            | /Users/you/Sites                  |
| DNS Test                | 127.0.0.1                         |
| Installed Databases     | mysql, redis                      |
+-------------------------+-----------------------------------+
```

## Architecture

### Technology Stack

```
                    Request Flow
                    ============

Browser (myapp.test)
        |
        v
   DnsMasq (DNS)              Resolves *.test -> 127.0.0.1
        |
        v
   Apache or Nginx             Listens on 127.0.0.1:80/443
        |
        v
   server.php                  Request router
        |
        v
   Driver System               Auto-detects framework
        |
        v
   PHP-FPM                     Executes PHP via Unix socket
        |
        v
   Your Application            Laravel, WordPress, etc.
```

### Project Structure

```
server/
├── berkan                    # Main executable (bash)
├── server.php                # HTTP request router
├── find-usable-php.php       # PHP 8.1+ binary finder
├── composer.json              # Dependencies
├── cli/
│   ├── berkan.php            # CLI entry point & container setup
│   ├── app.php               # 47+ command definitions
│   ├── includes/
│   │   └── helpers.php       # Helper functions
│   ├── stubs/                # Configuration templates
│   │   ├── httpd.conf        # Apache main config
│   │   ├── berkan.conf       # Apache default catch-all VirtualHost
│   │   ├── site.berkan.conf  # Apache per-site VirtualHost
│   │   ├── secure.berkan.conf # Apache HTTPS VirtualHost
│   │   ├── proxy.berkan.conf  # Apache proxy VirtualHost
│   │   ├── secure.proxy.berkan.conf # Apache secure proxy
│   │   ├── nginx.conf         # Nginx main config
│   │   ├── nginx-berkan.conf  # Nginx default catch-all server
│   │   ├── nginx-site.berkan.conf  # Nginx per-site server
│   │   ├── nginx-secure.berkan.conf # Nginx HTTPS server
│   │   ├── nginx-proxy.berkan.conf  # Nginx proxy server
│   │   ├── nginx-secure.proxy.berkan.conf # Nginx secure proxy
│   │   └── ...               # More templates
│   ├── templates/
│   │   └── 404.html          # Custom 404 page
│   └── Berkan/               # PHP classes (PSR-4: Berkan\)
│       ├── Contracts/
│       │   └── WebServer.php  # WebServer interface
│       ├── Apache.php         # Apache management (implements WebServer)
│       ├── Nginx.php          # Nginx management (implements WebServer)
│       ├── Database.php       # Database management
│       ├── Berkan.php         # Main class
│       ├── Brew.php           # Homebrew integration
│       ├── Configuration.php  # Config management
│       ├── DnsMasq.php        # DNS management
│       ├── PhpFpm.php         # PHP-FPM management
│       ├── Site.php           # Site & SSL management
│       ├── Server.php         # Request routing
│       ├── Status.php         # Health checks
│       ├── Diagnose.php       # Diagnostics
│       └── Drivers/           # Framework drivers
│           ├── BerkanDriver.php
│           ├── LaravelBerkanDriver.php
│           └── Specific/      # 21 framework-specific drivers
└── tests/
```

## Troubleshooting

### Web server won't start

**Apache:**
```bash
# Check Apache configuration syntax
sudo apachectl configtest

# Check if port 80 is in use
sudo lsof -i :80

# Check Apache error log
berkan log apache
```

**Nginx:**
```bash
# Check Nginx configuration syntax
sudo nginx -t

# Check if port 80 is in use
sudo lsof -i :80

# Check Nginx error log
berkan log nginx
```

### Sites not resolving

```bash
# Check DNS resolution
dig myapp.test @127.0.0.1

# Verify resolver file exists
cat /etc/resolver/test

# Restart DnsMasq
sudo brew services restart dnsmasq

# Flush macOS DNS cache
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
```

### PHP-FPM socket not found

```bash
# Check if socket exists
ls -la ~/.config/berkan/berkan.sock

# Restart PHP-FPM
sudo brew services restart php

# Check PHP-FPM log
berkan log php-fpm
```

### HTTPS certificate not trusted

```bash
# Re-secure the site
sudo berkan unsecure myapp
sudo berkan secure myapp

# Manually trust a certificate
sudo security add-trusted-cert -d -r trustRoot \
  -k /Library/Keychains/System.keychain \
  ~/.config/berkan/Certificates/myapp.test.crt
```

### Database won't start

```bash
# Check database status
berkan db:list

# Restart a specific database
sudo berkan db:restart mysql

# Check Homebrew service status
brew services list
```

### Complete reset

```bash
# Stop all services
sudo berkan stop

# Uninstall
sudo berkan uninstall --force

# Reinstall
sudo berkan install
```

## Uninstalling

### Soft uninstall (keeps configuration)

```bash
sudo berkan uninstall
```

### Full uninstall (removes everything)

```bash
sudo berkan uninstall --force
```

This removes:
- Web server configuration changes (Apache or Nginx)
- PHP-FPM berkan pool
- DnsMasq TLD configuration
- DNS resolver file
- Sudoers entry
- Loopback configuration
- Stops all installed databases
- All files in `~/.config/berkan/` (with `--force`)
- The `/usr/local/bin/berkan` symlink

---

# Türkçe

## Berkan Nedir?

**Berkan**, macOS üzerinde hem **Apache (httpd)** hem de **Nginx** web sunucusunu destekleyen hafif bir PHP geliştirme ortamıdır. Kurulum sırasında tercih ettiğiniz web sunucusunu seçin, istediğiniz zaman aralarında geçiş yapın, PHP versiyonlarını ve veritabanlarını tek bir CLI'dan yönetin.

Berkan, Mac'inizi her açıldığında arka planda seçtiğiniz web sunucusu, PHP-FPM ve DnsMasq'i çalıştıracak şekilde yapılandırır. Ardından DnsMasq kullanarak `.test` TLD'sindeki tüm istekleri yerel makinenizdeki sitelere yönlendirir.

Yani yaklaşık **7 MB RAM** kullanan, hızlı bir macOS PHP geliştirme ortamı. Berkan, Vagrant veya Docker'ın tam bir alternatifi değildir, ancak esnek ve hız odaklı bir çalışma ortamı istiyorsanız harika bir seçenektir.

## Özellikler

### Web Sunucu (Apache veya Nginx)

Berkan, kurulum sırasında **Apache** veya **Nginx** arasında seçim yapmanıza olanak tanır ve istediğiniz zaman `berkan server:switch` ile geçiş yapabilirsiniz.

**Apache (httpd):**
- Native **`.htaccess` desteği** - rewrite kuralları üretim ortamıyla birebir aynı çalışır
- **`mod_rewrite`** ile URL yönlendirme ve temiz URL'ler
- **`mod_ssl`** ile kendinden imzalı güvenilir sertifikalarla TLS/HTTPS
- **`mod_proxy` + `mod_proxy_fcgi`** ile Apache'den PHP-FPM'e Unix soket üzerinden bağlantı
- **`mod_proxy_http`** ile diğer yerel servislere ters proxy
- **`mod_headers`**, **`mod_deflate`**, **`mod_vhost_alias`**, **`mod_http2`**
- Her yeniden başlatmadan önce otomatik `apachectl configtest` doğrulaması

**Nginx:**
- Yüksek performanslı event-driven mimari
- **`fastcgi_pass`** ile Nginx'ten PHP-FPM'e Unix soket üzerinden bağlantı
- HTTP/2 destekli **SSL/TLS**
- WebSocket destekli **ters proxy**
- Kutudan çıkan **Gzip sıkıştırma**
- Her yeniden başlatmadan önce otomatik `nginx -t` doğrulaması

### Site Sunma

- **Sıfır konfigürasyon** - bir dizini park edin, her alt klasör anında `.test` sitesi olur
- Dizinleri toplu **park** edin: `~/Sites/blog` otomatik olarak `http://blog.test` olur
- Diskteki herhangi bir konumdan projeleri özel isimlerle **link**'leyin
- Driver sistemi ile **otomatik framework algılama** (25'ten fazla yerleşik driver)
- Bulunamayan siteler için özel **404 sayfası**
- Yapılandırılabilir **dizin listeleme** (aç/kapat)

### HTTPS / SSL

- **Tek komutla HTTPS**: `berkan secure myapp` güvenilir TLS sertifikası oluşturur
- **2048-bit RSA** özel anahtarlar ve **SAN (Subject Alternative Name)** desteği
- Sertifikalar macOS Sistem Anahtarlığı'nda **otomatik olarak güvenilir** yapılır
- **HTTP'den HTTPS'ye yönlendirme** otomatik yapılandırılır
- Tüm güvenli sitelerde **HTTP/2** etkin
- Güvenli siteler için varsayılan olarak **HSTS başlığı** eklenir
- Tek komutla siteyi HTTP'ye geri döndürün

### PHP Yönetimi

- PHP 8.1, 8.2, 8.3, 8.4 arasında **global PHP versiyon değiştirme**
- `berkan php:install` / `berkan php:remove` ile istediğiniz zaman **PHP versiyonu kur/kaldır**
- `berkan php:list` ile **kurulu PHP versiyonlarını listele**
- **Site bazlı PHP izolasyonu** - farklı siteler arasında farklı PHP versiyonları çalıştırabilir
- `berkan php` - Berkan'ın yönettiği versiyonla PHP CLI çalıştırma
- `berkan composer` - doğru PHP binary'si ile Composer çalıştırma
- Unix soket (`berkan.sock`) ile otomatik **PHP-FPM havuz yapılandırması**
- Geliştirme için ayarlanmış bellek limiti, yükleme boyutu ve çalışma süresi (512 MB, zaman aşımı yok)

### Veritabanı Yönetimi

- Berkan CLI'dan doğrudan **veritabanlarını kurun ve yönetin**
- Desteklenen veritabanları:
  - **MySQL** - `berkan db:install mysql`
  - **PostgreSQL** - `berkan db:install postgresql`
  - **MongoDB** - `berkan db:install mongodb`
  - **Redis** - `berkan db:install redis`
- Tek tek veya tüm veritabanlarını **başlat, durdur, yeniden başlat**
- `berkan status` ve `berkan db:list` ile **veritabanı durumunu görün**
- `berkan install` sırasında interaktif veritabanı seçimi

### DNS Çözümleme

- **DnsMasq** tüm `*.test` (veya özel TLD) alan adlarını loopback adresinize çözümler
- macOS **resolver dosyası** (`/etc/resolver/test`) ile sorunsuz sistem genelinde DNS
- **Özel TLD** desteği - `.test`'i `.dev`, `.local` veya başka herhangi bir şeye değiştirin
- **Özel loopback adresi** - varsayılan `127.0.0.1`, herhangi bir IP'ye yapılandırılabilir

### Proxy

- Herhangi bir `.test` alan adını başka bir yerel servise (Node.js, Go, Python, Docker, vb.) **ters proxy**
- Otomatik **WebSocket desteği**
- Proxy siteler isteğe bağlı olarak **HTTPS ile güvenli** hale getirilebilir

### Paylaşım

- Üç tünel aracı ile yerel siteleri herkese açık paylaşma:
  - **Ngrok** (varsayılan)
  - **Expose**
  - **Cloudflare Tunnel (cloudflared)**
- Tek komutla paylaşım araçları arasında geçiş
- Herkese açık tünel URL'sini programatik olarak alma

### Framework Driver'ları

- **25 yerleşik driver** uygulama framework'ünüzü otomatik algılar
- Desteklenen: Laravel, WordPress, Symfony, Drupal, CakePHP, Magento 2, Craft CMS, Statamic, Joomla, Kirby, TYPO3, Neos, Nette, Contao, Concrete5, Jigsaw, Sculpin, Katana, Bedrock, Radicle ve dahası
- **Özel driver API** - üç metot implement ederek herhangi bir PHP uygulaması için kendi driver'ınızı yazın
- Driver önceliği: Özel > Belirli > Varsayılan (BasicWithPublic, Basic)

### Sistem Entegrasyonu

- **Homebrew-native** - tüm servisler `brew services` ile kurulur ve yönetilir
- **Sudoers entegrasyonu** - `berkan trust` tekrarlanan `sudo` şifresi ihtiyacını ortadan kaldırır
- Yeniden başlatmalar arasında kalıcı özel loopback adresleri için **Loopback plist**
- **PATH'e symlink** - kurulumdan sonra `berkan` her yerden erişilebilir

### Tanı ve Loglama

- `berkan status` - web sunucu, PHP-FPM, DnsMasq ve veritabanı durumunu tek bakışta görün
- `berkan diagnose` - kapsamlı tanı raporu
- `berkan log` - web sunucu, PHP, PHP-FPM veya erişim loglarını canlı takip edin
- `~/.config/berkan/Log/` içinde servis başına ayrı log dosyaları

## Gereksinimler

- **macOS** (Apple Silicon veya Intel)
- **[Homebrew](https://brew.sh)** kurulu olmalıdır
- **PHP 8.1+** (Homebrew ile kurulu)
- Port 80/443 başka bir web sunucu tarafından kullanılmıyor olmalıdır

## Kurulum

### Ön Gereksinimler

Berkan'ı kurmadan önce aşağıdakilerin yüklü olduğundan emin olun:

1. **macOS** (Apple Silicon veya Intel)

2. **Homebrew** - Eğer Homebrew kurulu değilse önce kurun:
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

3. **PHP 8.1+** - Homebrew ile PHP kurun (kurulu değilse):
   ```bash
   brew install php
   ```

4. **Composer** - PHP bağımlılık yöneticisini kurun:
   ```bash
   brew install composer
   ```

5. **Port 80 ve 443 boş olmalıdır** - Başka bir web sunucunun (Apache, Nginx, MAMP, XAMPP, vb.) bu portları kullanmadığından emin olun:
   ```bash
   # Port 80'i kullanan bir şey var mı kontrol edin
   sudo lsof -i :80

   # macOS'un dahili Apache'si çalışıyorsa durdurun
   sudo apachectl stop
   sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
   ```

### Adım 1: Depoyu Klonlayın

```bash
git clone https://github.com/berkanakman/berkanServer.git
cd berkanServer
```

### Adım 2: PHP Bağımlılıklarını Kurun

```bash
composer install
```

Bu komut gerekli tüm PHP paketlerini (Illuminate Container, Symfony Console, Guzzle, vb.) `vendor/` dizinine kuracaktır.

### Adım 3: Berkan Servislerini Kurun

```bash
sudo berkan install
```

Bu, sizi kurulum boyunca yönlendirecek interaktif bir kurucu başlatır:

**1. Web Sunucu Seçimi:**
```
Which web server would you like to use?
  [0] Apache
  [1] Nginx
> 0
```
- **Apache** - `.htaccess` dosyaları kullanıyorsanız en iyi seçim (Laravel, WordPress, vb.)
- **Nginx** - Yüksek performanslı event-driven mimari istiyorsanız en iyi seçim

**2. PHP Versiyon Seçimi (çoklu seçim):**
```
Which PHP versions would you like to install? (comma-separated, e.g. 0,1)
  [0] 8.4
  [1] 8.3
  [2] 8.2
  [3] 8.1
> 0,1
```
Bir veya daha fazla PHP versiyonu seçin. Seçtiğiniz en son versiyon aktif versiyon olarak ayarlanacaktır.

**3. Veritabanı Seçimi (çoklu seçim):**
```
Which databases would you like to install? (comma-separated, or "none")
  [0] MySQL
  [1] PostgreSQL
  [2] MongoDB
  [3] Redis
  [4] None
> 0,3
```
İhtiyacınız olan veritabanlarını seçin veya atlamak için "None" seçin. Veritabanlarını daha sonra `berkan db:install` ile istediğiniz zaman kurabilirsiniz.

### Adım 4: Berkan'ı Güvenilir Yapın (Önerilen)

Bu adım Berkan'ı sudoers'a ekler, böylece `start`, `stop`, `restart`, `secure` gibi yaygın komutlarda şifre girmeniz gerekmez:

```bash
sudo berkan trust
```

### Adım 5: Kurulumu Doğrulayın

```bash
berkan status
```

Tüm servislerin çalıştığını görmelisiniz:

```
+----------------+---------+
| Service        | Status  |
+----------------+---------+
| Apache (httpd) | Running |
| PHP-FPM        | Running |
| DnsMasq        | Running |
| MySQL          | Running |
| Redis          | Running |
+----------------+---------+
```

Her şeyin doğru yapılandırıldığından emin olmak için tanı aracını çalıştırın:

```bash
berkan diagnose
```

### Adım 6: Projeler Dizininizi Park Edin

```bash
mkdir -p ~/Sites
cd ~/Sites
berkan park
```

Artık `~/Sites/` içinde oluşturduğunuz her klasör tarayıcınızda otomatik olarak `http://klasör-adı.test` olarak erişilebilir olacaktır.

### `berkan install` Ne Yapar? (Adım Adım)

1. Web sunucu (Apache veya Nginx), PHP versiyonları ve veritabanları seçmenizi ister
2. `~/.config/berkan/` konfigürasyon dizinini oluşturur
3. Alt dizinleri oluşturur: `Apache/` veya `Nginx/`, `Certificates/`, `Drivers/`, `Sites/`, `Log/`, `dnsmasq.d/`
4. Seçimlerinizi `~/.config/berkan/config.json` dosyasına kaydeder
5. Seçilen web sunucuyu Homebrew ile kurar (kurulu değilse)
6. Ana sunucu konfigürasyonunu yazar (Apache için `httpd.conf` veya Nginx için `nginx.conf`)
7. Varsayılan catch-all sunucu konfigürasyonunu oluşturur (tüm `.test` isteklerini `server.php`'ye yönlendirir)
8. PHP-FPM'i Unix socket kullanan bir `berkan` havuzuyla yapılandırır (`~/.config/berkan/berkan.sock`)
9. Seçtiğiniz ek PHP versiyonlarını Homebrew ile kurar
10. DnsMasq'i tüm `*.test` alan adlarını `127.0.0.1`'e çözümlemek için kurar ve yapılandırır
11. `/etc/resolver/test` macOS resolver dosyasını oluşturur
12. Seçilen veritabanlarını Homebrew ile kurar ve başlatır
13. Tüm servisleri başlatır (`brew services start`)
14. `berkan` çalıştırılabilir dosyasını `/usr/local/bin/berkan` olarak symlink'ler
15. Örnek bir özel driver'ı `~/.config/berkan/Drivers/` dizinine kopyalar

### Berkan'ı Güncelleme

```bash
cd /path/to/berkanServer
git pull origin main
composer install
sudo berkan restart
```

## Hızlı Başlangıç

```bash
# 1. Berkan'ı kurun (interaktif)
sudo berkan install

# 2. Projeler dizininizi park edin
cd ~/Sites
berkan park

# 3. Bir Laravel projesi oluşturun
composer create-project laravel/laravel myapp

# 4. Tarayıcıda ziyaret edin
# http://myapp.test

# 5. HTTPS ile güvenli hale getirin
sudo berkan secure myapp

# 6. HTTPS ile ziyaret edin
# https://myapp.test
```

Bu kadar! `~/Sites/` içindeki her klasör artık otomatik olarak `http://klasor-adi.test` adresinde erişime açıktır.

## Komut Referansı

### Servis Yönetimi

| Komut | Açıklama |
|-------|----------|
| `berkan install` | Berkan servislerini kur (interaktif web sunucu, PHP, veritabanı seçimi) |
| `berkan uninstall` | Berkan'ı kaldır (onay ister) |
| `berkan uninstall --force` | Zorla kaldır ve tüm konfigürasyonları sil |
| `berkan start` | Tüm Berkan servislerini başlat |
| `berkan stop` | Tüm Berkan servislerini durdur |
| `berkan restart` | Tüm Berkan servislerini yeniden başlat |
| `berkan status` | Servis durumu tablosunu göster |
| `berkan trust` | Berkan'ı sudoers'a ekle (artık şifre sorulmaz) |

### Site Yönetimi

| Komut | Açıklama |
|-------|----------|
| `berkan park [yol]` | Dizini park edilmiş yol olarak kaydet (varsayılan: mevcut dizin) |
| `berkan parked` | Tüm park edilmiş dizinleri listele |
| `berkan forget [yol]` | Park edilmiş yolu kaldır |
| `berkan link [isim]` | Mevcut dizin için sembolik bağlantı oluştur |
| `berkan links` | Tüm bağlantılı siteleri SSL durumu ve URL'leriyle listele |
| `berkan unlink [isim]` | Bağlantılı siteyi kaldır |
| `berkan open [isim]` | Siteyi varsayılan tarayıcıda aç |
| `berkan paths` | Tüm kayıtlı yolları göster |

### HTTPS / SSL

| Komut | Açıklama |
|-------|----------|
| `berkan secure [isim]` | Kendinden imzalı güvenilir sertifika ile HTTPS etkinleştir |
| `berkan secured` | Tüm güvenli siteleri listele |
| `berkan unsecure [isim]` | HTTPS'i kaldırıp HTTP'ye geri dön |

### PHP Yönetimi

| Komut | Açıklama |
|-------|----------|
| `berkan use <versiyon>` | Global PHP versiyonunu değiştir (ör. `berkan use 8.3`) |
| `berkan php:install <versiyon>` | Yeni PHP versiyonu kur (ör. `berkan php:install 8.2`) |
| `berkan php:remove <versiyon>` | PHP versiyonu kaldır |
| `berkan php:list` | Kurulu PHP versiyonlarını durumlarıyla listele |
| `berkan which-php` | Aktif PHP versiyonunu göster |
| `berkan php [argümanlar...]` | Berkan'ın PHP versiyonuyla PHP CLI çalıştır |
| `berkan composer [argümanlar...]` | Berkan'ın PHP versiyonuyla Composer çalıştır |
| `berkan isolate <versiyon>` | Mevcut siteyi belirli bir PHP versiyonuna izole et |
| `berkan unisolate` | Mevcut siteden PHP izolasyonunu kaldır |
| `berkan isolated` | Tüm izole edilmiş siteleri ve PHP versiyonlarını listele |

### Veritabanı Yönetimi

| Komut | Açıklama |
|-------|----------|
| `berkan db:install <isim>` | Veritabanı kur (`mysql`, `postgresql`, `mongodb`, `redis`) |
| `berkan db:uninstall <isim>` | Veritabanı kaldır |
| `berkan db:start [isim]` | Veritabanı başlat (veya tüm kurulu veritabanlarını) |
| `berkan db:stop [isim]` | Veritabanı durdur (veya tümünü) |
| `berkan db:restart [isim]` | Veritabanı yeniden başlat (veya tümünü) |
| `berkan db:list` | Tüm desteklenen veritabanlarını ve durumlarını listele |

### Web Sunucu

| Komut | Açıklama |
|-------|----------|
| `berkan server:switch` | Apache ve Nginx arasında geçiş yap |

### Proxy

| Komut | Açıklama |
|-------|----------|
| `berkan proxy <isim> <url>` | Proxy oluştur (ör. `berkan proxy myapi http://localhost:3000`) |
| `berkan proxy <isim> <url> --secure` | HTTPS ile proxy oluştur |
| `berkan proxies` | Tüm proxy siteleri listele |
| `berkan unproxy <isim>` | Proxy'i kaldır |

### Paylaşım

| Komut | Açıklama |
|-------|----------|
| `berkan share [isim]` | Siteyi tünel üzerinden herkese açık paylaş |
| `berkan share-tool [araç]` | Paylaşım aracını al veya ayarla (`ngrok`, `expose`, `cloudflared`) |
| `berkan fetch-share-url` | Mevcut paylaşım URL'sini al |
| `berkan set-ngrok-token <token>` | Ngrok kimlik doğrulama token'ını ayarla |

### Konfigürasyon

| Komut | Açıklama |
|-------|----------|
| `berkan tld [isim]` | TLD'yi al veya ayarla (varsayılan: `test`) |
| `berkan loopback [adres]` | Loopback IP'yi al veya ayarla (varsayılan: `127.0.0.1`) |
| `berkan directory-listing [on/off]` | Dizin listelemeyi aç/kapat |
| `berkan on-latest-version` | Mevcut Berkan versiyonunu göster |

### Tanı

| Komut | Açıklama |
|-------|----------|
| `berkan diagnose` | Tam tanı çalıştır ve sonuçları göster |
| `berkan log [servis]` | Logları görüntüle (`apache`/`nginx`, `php`, `php-fpm`, `access`) |
| `berkan log` | Tüm mevcut log dosyalarını listele |

## Site Sunma

Berkan, PHP projelerinizi sunmak için iki yol destekler:

### `park` Komutu

`park` komutu bir dizini kaydeder. Park edilmiş dizin içindeki her alt klasör otomatik olarak `.test` sitesi olarak erişilebilir hale gelir.

```bash
cd ~/Sites
berkan park

# Artık:
# ~/Sites/blog       -> http://blog.test
# ~/Sites/myapp      -> http://myapp.test
# ~/Sites/api        -> http://api.test
```

Birden fazla dizin park edebilirsiniz:

```bash
berkan park ~/Projects
berkan park ~/Work/clients

# Tüm park edilmiş yolları gör
berkan parked
```

Bir dizini sunmayı durdurmak için:

```bash
cd ~/Sites
berkan forget

# Veya yol belirtin
berkan forget ~/Projects
```

### `link` Komutu

`link` komutu tek bir site için sembolik bağlantı oluşturur. Bu, park edilmemiş bir konumdaki tek bir dizini sunmak istediğinizde veya site adının klasör adından farklı olması gerektiğinde kullanışlıdır.

```bash
cd ~/çok/derin/iç/içe/proje
berkan link sitelerim

# Artık erişilebilir: http://sitelerim.test
```

```bash
# Tüm bağlantıları listele
berkan links

# Çıktı:
# +-----------+-----+------------------------+-------------------------------+
# | Site      | SSL | URL                    | Path                          |
# +-----------+-----+------------------------+-------------------------------+
# | sitelerim |     | http://sitelerim.test  | /Users/siz/çok/derin/iç/iç... |
# | blog      | X   | https://blog.test      | /Users/siz/Sites/blog         |
# +-----------+-----+------------------------+-------------------------------+
```

```bash
# Bağlantıyı kaldır
berkan unlink sitelerim
```

### İstek Yönlendirme Nasıl Çalışır?

1. `myapp.test` için bir istek web sunucunuza ulaşır (Apache veya Nginx)
2. Catch-all sunucu konfigürasyonu isteği `server.php`'ye yönlendirir
3. `server.php` ana bilgisayar adından site adını çıkarır
4. Eşleşen bağlantılı site veya park edilmiş dizini arar
5. Driver sistemi framework'ü otomatik algılar (Laravel, WordPress, vb.)
6. Uygun ön denetleyici (front controller) yüklenir ve çalıştırılır

## HTTPS / SSL Desteği

Berkan, tek bir komutla herhangi bir siteyi güvenilir kendinden imzalı TLS sertifikası ile güvenli hale getirebilir. Apache veya Nginx kullanmanızdan bağımsız olarak aynı şekilde çalışır.

### Siteyi Güvenli Hale Getirme

```bash
# İsimle güvenli hale getir
sudo berkan secure myapp

# Veya projeye cd yapıp isim vermeden güvenli hale getir
cd ~/Sites/myapp
sudo berkan secure
```

Bu işlem şunları yapar:
1. 2048-bit RSA özel anahtarı oluşturur
2. SAN (Subject Alternative Name) ile kendinden imzalı sertifika oluşturur
3. Sertifikayı macOS Sistem Anahtarlığı'nda güvenilir hale getirir
4. Uygun web sunucu konfigürasyonunu oluşturur (Apache VirtualHost veya Nginx server bloğu)
5. HTTP'den HTTPS'ye yönlendirme ayarlar
6. HTTP/2 desteğini etkinleştirir
7. HSTS başlığını ekler
8. Web sunucuyu yeniden başlatır

### Güvenliği Kaldırma

```bash
sudo berkan unsecure myapp
```

Bu, sertifikayı siler, Anahtarlık'tan çıkarır ve SSL konfigürasyonunu kaldırır.

### Güvenli Siteleri Listeleme

```bash
berkan secured
```

## PHP Versiyon Yönetimi

### Global PHP Değiştirme

```bash
# PHP 8.3'e geç
sudo berkan use 8.3

# PHP 8.2'ye geç
sudo berkan use 8.2

# Mevcut versiyonu kontrol et
berkan which-php
# Çıktı: php@8.3
```

### PHP Versiyonlarını Kurma ve Kaldırma

```bash
# Yeni PHP versiyonu kur
sudo berkan php:install 8.2

# PHP versiyonu kaldır (aktif versiyon kaldırılamaz)
sudo berkan php:remove 8.1

# Kurulu PHP versiyonlarını listele
berkan php:list

# Çıktı:
# +-------------+---------+--------+
# | PHP Version | Status  | Active |
# +-------------+---------+--------+
# | php@8.4     | Running | Yes    |
# | php@8.3     | Stopped |        |
# | php@8.2     | Stopped |        |
# +-------------+---------+--------+
```

### Site Bazında PHP İzolasyonu

Tek tek siteleri farklı PHP versiyonları kullanacak şekilde izole edebilirsiniz:

```bash
cd ~/Sites/eski-uygulama
berkan isolate 8.1

cd ~/Sites/modern-uygulama
berkan isolate 8.4

# İzole edilmiş siteleri listele
berkan isolated
# +------------------+-------------+
# | Site             | PHP Version |
# +------------------+-------------+
# | eski-uygulama    | 8.1         |
# | modern-uygulama  | 8.4         |
# +------------------+-------------+

# İzolasyonu kaldır
cd ~/Sites/eski-uygulama
berkan unisolate
```

### PHP CLI Kullanımı

```bash
# Berkan'ın versiyonuyla PHP çalıştır
berkan php -v
berkan php artisan migrate
berkan php script.php

# Berkan'ın PHP'siyle Composer çalıştır
berkan composer install
berkan composer require laravel/framework
```

## Veritabanı Yönetimi

Berkan, CLI üzerinden doğrudan veritabanlarını kurabilir ve yönetebilir.

### Desteklenen Veritabanları

| Veritabanı | Kurulum Komutu | Formül |
|------------|----------------|---------|
| MySQL | `berkan db:install mysql` | `mysql` |
| PostgreSQL | `berkan db:install postgresql` | `postgresql@17` |
| MongoDB | `berkan db:install mongodb` | `mongodb-community` |
| Redis | `berkan db:install redis` | `redis` |

### Veritabanı Kurma

```bash
# MySQL kur
sudo berkan db:install mysql

# PostgreSQL kur
sudo berkan db:install postgresql

# Redis kur
sudo berkan db:install redis
```

MongoDB için `mongodb/brew` tap'ı gereklidir, bunu Berkan otomatik olarak halleder.

### Veritabanlarını Yönetme

```bash
# Belirli bir veritabanını başlat
sudo berkan db:start mysql

# Tüm veritabanlarını durdur
sudo berkan db:stop

# PostgreSQL'i yeniden başlat
sudo berkan db:restart postgresql

# Veritabanı kaldır
sudo berkan db:uninstall mongodb
```

### Veritabanı Durumunu Kontrol Etme

```bash
berkan db:list

# Çıktı:
# +------------+------------+-----------+---------------+
# | Database   | Label      | Installed | Status        |
# +------------+------------+-----------+---------------+
# | mysql      | MySQL      | Yes       | Running       |
# | postgresql | PostgreSQL | Yes       | Running       |
# | mongodb    | MongoDB    | No        | Not Installed |
# | redis      | Redis      | Yes       | Stopped       |
# +------------+------------+-----------+---------------+
```

Kurulu veritabanları `berkan status` içinde de görünür:

```
+----------------+---------+
| Service        | Status  |
+----------------+---------+
| Apache (httpd) | Running |
| PHP-FPM        | Running |
| DnsMasq        | Running |
| MySQL          | Running |
| PostgreSQL     | Running |
+----------------+---------+
```

## Web Sunucu Seçimi

### Kurulum Sırasında Seçim

`sudo berkan install` çalıştırdığınızda, Berkan hangi web sunucuyu istediğinizi sorar:

```
Which web server would you like to use?
  [0] Apache
  [1] Nginx
```

### Web Sunucuları Arasında Geçiş

Apache ve Nginx arasında istediğiniz zaman geçiş yapabilirsiniz:

```bash
sudo berkan server:switch
```

Bu işlem şunları yapar:
1. Geçişi onaylar (ör. "Apache'den Nginx'e geçilsin mi?")
2. Mevcut web sunucuyu durdurur
3. Konfigürasyonu günceller
4. Yeni web sunucuyu kurar ve yapılandırır
5. Sudoers kayıtlarını günceller
6. Yeni web sunucuyu başlatır

Geçiş sonrasında tüm siteleriniz, sertifikalarınız ve konfigürasyonlarınız sorunsuz çalışmaya devam eder.

### Apache ve Nginx Arasındaki Farklar

| Özellik | Apache | Nginx |
|---------|--------|-------|
| `.htaccess` desteği | Native | Desteklenmiyor |
| Konfig test komutu | `apachectl configtest` | `nginx -t` |
| Konfig dizini | `~/.config/berkan/Apache/` | `~/.config/berkan/Nginx/` |
| Log ön eki | `apache-error.log` | `nginx-error.log` |
| PHP bağlantısı | `mod_proxy_fcgi` (Unix soket) | `fastcgi_pass` (Unix soket) |
| Proxy desteği | `mod_proxy_http` | `proxy_pass` |

## Site Proxy

Berkan, `.test` alan adlarını Node.js, Go, Python veya Docker container'ları gibi diğer yerel servislere yönlendirebilir.

```bash
# myapi.test'i port 3000'deki bir Node.js uygulamasına yönlendir
sudo berkan proxy myapi http://localhost:3000

# HTTPS ile proxy
sudo berkan proxy myapi http://localhost:3000 --secure

# Tüm proxy'leri listele
berkan proxies
# +-------------+------------------------+
# | Site        | Proxy URL              |
# +-------------+------------------------+
# | myapi.test  | http://localhost:3000   |
# +-------------+------------------------+

# Proxy'i kaldır
sudo berkan unproxy myapi
```

Proxy konfigürasyonları hem Apache hem de Nginx için WebSocket desteği içerir.

## Site Paylaşımı

Berkan, yerel siteleri herkese açık paylaşmak için üç tünel aracı destekler:

### Ngrok Kullanımı (Varsayılan)

```bash
# Ngrok kimlik doğrulama token'ını ayarla (bir kez)
berkan set-ngrok-token TOKEN_INIZ

# Siteyi paylaş
berkan share myapp

# Başka bir terminalde URL'yi al
berkan fetch-share-url
```

### Expose Kullanımı

```bash
berkan share-tool expose
berkan share myapp
```

### Cloudflare Tunnel Kullanımı

```bash
berkan share-tool cloudflared
berkan share myapp
```

### Mevcut Aracı Kontrol Etme

```bash
berkan share-tool
# Çıktı: Current share tool: ngrok
```

## Özel Driver Yazma

Berkan uygulamanızı otomatik algılamıyorsa, özel bir driver yazabilirsiniz.

### Driver Oluşturma

Kurulum sırasında `~/.config/berkan/Drivers/SampleBerkanDriver.php` adresine örnek bir driver yerleştirilir. Bu dizinde yeni bir dosya oluşturun:

```php
<?php

use Berkan\Drivers\BerkanDriver;

class BenimOzelBerkanDriver extends BerkanDriver
{
    /**
     * Driver'ın isteği sunup sunmayacağını belirler.
     * Bu driver $sitePath'teki siteleri yönetmeli mi?
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . '/benim-config.php');
    }

    /**
     * Gelen isteğin statik bir dosya için olup olmadığını belirler.
     * Tam dosya yolunu veya statik değilse false döndürün.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        $staticPath = $sitePath . '/public' . $uri;

        if (file_exists($staticPath) && !is_dir($staticPath)) {
            return $staticPath;
        }

        return false;
    }

    /**
     * Uygulamanın ön denetleyicisine (front controller) tam çözümlenmiş yolu alır.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/public/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $sitePath . '/public';

        return $sitePath . '/public/index.php';
    }
}
```

### Driver Çözümleme Sırası

1. **Özel driver'lar** - `~/.config/berkan/Drivers/` dizininde (en yüksek öncelik)
2. **Belirli driver'lar** - WordPress, Laravel, Symfony, vb.
3. **Varsayılan driver'lar** - `BasicWithPublicBerkanDriver`, `BasicBerkanDriver`

`serves()` metodu `true` döndüren ilk driver isteği yönetir.

## Desteklenen Framework'ler

Berkan, **25 framework ve CMS platformu** için yerleşik driver içerir:

| Framework | Algılama |
|-----------|----------|
| **Laravel** | `artisan` veya `bootstrap/app.php` + `public/index.php` |
| **WordPress** | `wp-config.php` + `wp-admin/` |
| **WordPress Bedrock** | `web/wp/wp-settings.php` |
| **Symfony** | `bin/console` + `public/index.php` |
| **Drupal** | `core/lib/Drupal.php` |
| **Joomla** | `configuration.php` + `libraries/` |
| **CakePHP** | `config/app.php` + `webroot/index.php` |
| **Magento 2** | `bin/magento` + `pub/index.php` |
| **Craft CMS** | `craft` dosyası |
| **Statamic v3+** | Laravel + composer.json'da `statamic/cms` |
| **Statamic v2** | `statamic/` dizini + `please` dosyası |
| **Statamic v1** | `_app/core/statamic.php` |
| **Kirby CMS** | `kirby/` dizini |
| **Contao** | `system/initialize.php` veya `vendor/contao/` |
| **Concrete5** | `concrete/dispatcher.php` |
| **TYPO3** | `typo3/` dir + `typo3conf/` veya `config/system/settings.php` |
| **Neos CMS** | `flow` dosyası |
| **Nette** | `app/bootstrap.php` + `www/index.php` |
| **Jigsaw** | `build_local/` dizini |
| **Sculpin** | `output_dev/` veya `output_prod/` dizini |
| **Katana** | `public/_katana/` dizini |
| **Radicle** | `web/wp/wp-settings.php` + `config/application.php` |
| **Public dizinli temel** | `public/` dizini olan herhangi bir proje |
| **Temel** | Herhangi bir PHP projesi için yedek driver |

## Konfigürasyon

### Konfigürasyon Dosyası

Ana konfigürasyon dosyası `~/.config/berkan/config.json` adresindedir:

```json
{
    "tld": "test",
    "loopback": "127.0.0.1",
    "paths": [
        "/Users/siz/Sites",
        "/Users/siz/Projects"
    ],
    "web_server": "apache",
    "php_versions": ["8.4", "8.3"],
    "databases": ["mysql", "redis"]
}
```

| Anahtar | Açıklama | Varsayılan |
|---------|----------|------------|
| `tld` | Siteler için üst düzey alan adı | `test` |
| `loopback` | Loopback IP adresi | `127.0.0.1` |
| `paths` | Park edilmiş dizinler | `[]` |
| `web_server` | Aktif web sunucu (`apache` veya `nginx`) | `apache` |
| `php_versions` | Kurulu PHP versiyonları | `["8.4"]` |
| `databases` | Kurulu veritabanları | `[]` |

### Konfigürasyon Dizinleri

| Dizin | Amaç |
|-------|------|
| `~/.config/berkan/config.json` | Ana konfigürasyon |
| `~/.config/berkan/Apache/` | Apache VirtualHost konfigürasyonları (Apache kullanılırken) |
| `~/.config/berkan/Nginx/` | Nginx sunucu konfigürasyonları (Nginx kullanılırken) |
| `~/.config/berkan/Certificates/` | SSL sertifikaları ve anahtarları |
| `~/.config/berkan/Drivers/` | Özel kullanıcı driver'ları |
| `~/.config/berkan/Sites/` | Bağlantılı siteler için sembolik bağlantılar |
| `~/.config/berkan/Log/` | Web sunucu, PHP ve PHP-FPM log'ları |
| `~/.config/berkan/dnsmasq.d/` | DnsMasq TLD konfigürasyonu |
| `~/.config/berkan/berkan.sock` | PHP-FPM Unix soketi |

### Sistem Dosyaları

| Dosya | Amaç |
|-------|------|
| `/etc/resolver/test` | `.test` TLD için macOS DNS çözümleyici |
| `/etc/sudoers.d/berkan` | Sudoers kayıtları (`berkan trust` sonrası) |
| `$(brew --prefix)/etc/httpd/httpd.conf` | Apache ana konfigürasyonu (Apache kullanılırken) |
| `$(brew --prefix)/etc/nginx/nginx.conf` | Nginx ana konfigürasyonu (Nginx kullanılırken) |
| `$(brew --prefix)/etc/dnsmasq.conf` | DnsMasq ana konfigürasyonu |

### TLD Değiştirme

```bash
# .test'ten .dev'e değiştir
sudo berkan tld dev

# Artık siteler *.test yerine *.dev'de
# http://myapp.dev

# Mevcut TLD'yi kontrol et
berkan tld
# Çıktı: dev
```

### Loopback Adresi Değiştirme

```bash
sudo berkan loopback 10.200.10.1

# Mevcut loopback'i kontrol et
berkan loopback
# Çıktı: 10.200.10.1
```

## Log ve Tanı

### Logları Görüntüleme

```bash
# Tüm log dosyalarını listele
berkan log

# Web sunucu hata logunu canlı takip et
berkan log apache    # Apache kullanılırken
berkan log nginx     # Nginx kullanılırken

# PHP hata logunu görüntüle
berkan log php

# PHP-FPM logunu görüntüle
berkan log php-fpm

# Erişim logunu görüntüle
berkan log access
```

### Tanı Çalıştırma

```bash
berkan diagnose
```

Bu, kapsamlı bir tanı raporu görüntüler:

```
Berkan Diagnostics
==================================================
+-------------------------+-----------------------------------+
| Check                   | Result                            |
+-------------------------+-----------------------------------+
| Berkan Version          | 1.0.0                             |
| PHP Version             | 8.4.18                            |
| PHP Binary              | /opt/homebrew/opt/php@8.4/bin/php |
| Operating System        | Darwin 24.6.0                     |
| Homebrew Prefix         | /opt/homebrew                     |
| TLD                     | test                              |
| Loopback                | 127.0.0.1                         |
| Web Server              | apache                            |
| Apache (httpd) Status   | Running                           |
| PHP-FPM Status          | Running                           |
| DnsMasq Status          | Running                           |
| Apache (httpd) Config   | Found                             |
| PHP-FPM Socket          | Found                             |
| DNS Resolver            | Found                             |
| Installed PHP           | php@8.4, php@8.3                  |
| Linked PHP              | php@8.4                           |
| Apache (httpd) Config T.| Syntax OK                         |
| Parked Paths            | /Users/siz/Sites                  |
| DNS Test                | 127.0.0.1                         |
| Installed Databases     | mysql, redis                      |
+-------------------------+-----------------------------------+
```

## Mimari

### Teknoloji Yığını

```
                    İstek Akışı
                    ===========

Tarayıcı (myapp.test)
        |
        v
   DnsMasq (DNS)              *.test -> 127.0.0.1 çözümler
        |
        v
   Apache veya Nginx           127.0.0.1:80/443 dinler
        |
        v
   server.php                  İstek yönlendirici
        |
        v
   Driver Sistemi              Framework'ü otomatik algılar
        |
        v
   PHP-FPM                     Unix soketi üzerinden PHP çalıştırır
        |
        v
   Uygulamanız                 Laravel, WordPress, vb.
```

### Proje Yapısı

```
server/
├── berkan                    # Ana çalıştırılabilir dosya (bash)
├── server.php                # HTTP istek yönlendirici
├── find-usable-php.php       # PHP 8.1+ ikili bulucu
├── composer.json              # Bağımlılıklar
├── cli/
│   ├── berkan.php            # CLI giriş noktası ve konteyner kurulumu
│   ├── app.php               # 47'den fazla komut tanımı
│   ├── includes/
│   │   └── helpers.php       # Yardımcı fonksiyonlar
│   ├── stubs/                # Konfigürasyon şablonları
│   │   ├── httpd.conf        # Apache ana konfig
│   │   ├── berkan.conf       # Apache varsayılan catch-all VirtualHost
│   │   ├── site.berkan.conf  # Apache site bazlı VirtualHost
│   │   ├── secure.berkan.conf # Apache HTTPS VirtualHost
│   │   ├── proxy.berkan.conf  # Apache proxy VirtualHost
│   │   ├── secure.proxy.berkan.conf # Apache güvenli proxy
│   │   ├── nginx.conf         # Nginx ana konfig
│   │   ├── nginx-berkan.conf  # Nginx varsayılan catch-all sunucu
│   │   ├── nginx-site.berkan.conf  # Nginx site bazlı sunucu
│   │   ├── nginx-secure.berkan.conf # Nginx HTTPS sunucu
│   │   ├── nginx-proxy.berkan.conf  # Nginx proxy sunucu
│   │   ├── nginx-secure.proxy.berkan.conf # Nginx güvenli proxy
│   │   └── ...               # Daha fazla şablon
│   ├── templates/
│   │   └── 404.html          # Özel 404 sayfası
│   └── Berkan/               # PHP sınıfları (PSR-4: Berkan\)
│       ├── Contracts/
│       │   └── WebServer.php  # WebServer arayüzü (interface)
│       ├── Apache.php         # Apache yönetimi (WebServer implement eder)
│       ├── Nginx.php          # Nginx yönetimi (WebServer implement eder)
│       ├── Database.php       # Veritabanı yönetimi
│       ├── Berkan.php         # Ana sınıf
│       ├── Brew.php           # Homebrew entegrasyonu
│       ├── Configuration.php  # Konfigürasyon yönetimi
│       ├── DnsMasq.php        # DNS yönetimi
│       ├── PhpFpm.php         # PHP-FPM yönetimi
│       ├── Site.php           # Site ve SSL yönetimi
│       ├── Server.php         # İstek yönlendirme
│       ├── Status.php         # Sağlık kontrolleri
│       ├── Diagnose.php       # Tanı
│       └── Drivers/           # Framework driver'ları
│           ├── BerkanDriver.php
│           ├── LaravelBerkanDriver.php
│           └── Specific/      # 21 framework'e özel driver
└── tests/
```

## Sorun Giderme

### Web sunucu başlamıyor

**Apache:**
```bash
# Apache konfigürasyon sözdizimini kontrol et
sudo apachectl configtest

# Port 80 başkası tarafından kullanılıyor mu?
sudo lsof -i :80

# Apache hata logunu kontrol et
berkan log apache
```

**Nginx:**
```bash
# Nginx konfigürasyon sözdizimini kontrol et
sudo nginx -t

# Port 80 başkası tarafından kullanılıyor mu?
sudo lsof -i :80

# Nginx hata logunu kontrol et
berkan log nginx
```

### Siteler çözümlenmiyor

```bash
# DNS çözümlemesini kontrol et
dig myapp.test @127.0.0.1

# Resolver dosyasının varlığını doğrula
cat /etc/resolver/test

# DnsMasq'i yeniden başlat
sudo brew services restart dnsmasq

# macOS DNS önbelleğini temizle
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
```

### PHP-FPM soketi bulunamıyor

```bash
# Soketin var olup olmadığını kontrol et
ls -la ~/.config/berkan/berkan.sock

# PHP-FPM'i yeniden başlat
sudo brew services restart php

# PHP-FPM logunu kontrol et
berkan log php-fpm
```

### HTTPS sertifikası güvenilir değil

```bash
# Siteyi yeniden güvenli hale getir
sudo berkan unsecure myapp
sudo berkan secure myapp

# Bir sertifikayı elle güvenilir yap
sudo security add-trusted-cert -d -r trustRoot \
  -k /Library/Keychains/System.keychain \
  ~/.config/berkan/Certificates/myapp.test.crt
```

### Veritabanı başlamıyor

```bash
# Veritabanı durumunu kontrol et
berkan db:list

# Belirli bir veritabanını yeniden başlat
sudo berkan db:restart mysql

# Homebrew servis durumunu kontrol et
brew services list
```

### Komple sıfırlama

```bash
# Tüm servisleri durdur
sudo berkan stop

# Kaldırma
sudo berkan uninstall --force

# Yeniden kur
sudo berkan install
```

## Kaldırma

### Yumuşak kaldırma (konfigürasyonu saklar)

```bash
sudo berkan uninstall
```

### Tam kaldırma (her şeyi siler)

```bash
sudo berkan uninstall --force
```

Bu işlem şunları kaldırır:
- Web sunucu konfigürasyon değişiklikleri (Apache veya Nginx)
- PHP-FPM berkan havuzu
- DnsMasq TLD konfigürasyonu
- DNS resolver dosyası
- Sudoers kaydı
- Loopback konfigürasyonu
- Tüm kurulu veritabanlarını durdurur
- `~/.config/berkan/` içindeki tüm dosyalar (`--force` ile)
- `/usr/local/bin/berkan` sembolik bağlantısı

---

**Berkan** - Built by Berkan Akman | MIT License
