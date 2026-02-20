<p align="center">
  <b>BERKAN</b><br>
  PHP Development Environment for macOS<br>
  <i>macOS için PHP Geliştirme Ortamı</i>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-5.6--8.4-8892BF?style=flat-square" alt="PHP 5.6-8.4">
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
  - [Error Display Control](#error-display-control)
  - [Short Open Tag](#short-open-tag)
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
  - [Hata Görüntüleme Kontrolü](#hata-görüntüleme-kontrolü)
  - [Short Open Tag](#short-open-tag-1)
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
- **Interactive park** - `berkan park` scans projects, detects frameworks, and lets you assign PHP versions per project
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

- **Full PHP version range**: PHP 5.6, 7.0–7.4, 8.0–8.4 supported via `shivammathur/php` tap
- **Global PHP version switching** between any installed PHP version
- **Install/remove PHP versions** at any time with `berkan php:install` / `berkan php:remove`
- **List installed PHP versions** with `berkan php:list`
- **Per-site PHP isolation** - different sites can run different PHP versions simultaneously
- **Per-project PHP selection via `berkan park`** - interactive project scanning with automatic framework detection and PHP version assignment
- **Isolated PHP-FPM pools** - each isolated version gets its own socket (`berkan-7.4.sock`, `berkan-8.1.sock`, etc.)
- **Automatic framework detection** during park: Laravel, Symfony, WordPress, Drupal, Magento 2, Craft CMS, Joomla, Composer projects, Plain PHP
- `berkan php` - run PHP CLI using Berkan's managed version
- `berkan composer` - run Composer with the correct PHP binary
- Automatic **PHP-FPM pool configuration** with Unix socket (`berkan.sock`)
- Memory limit, upload size, and execution time tuned for development (512 MB, no timeout)

### Error Display Control

- **Toggle PHP error display** with `berkan error hide` / `berkan error show`
- Uses PHP-FPM `auto_prepend_file` for reliable per-request error control
- Works consistently across all sites, including those with `.htaccess` rewrites and PHP version isolation
- Settings persist in `config.json` and take effect immediately without restart

### Short Open Tag

- **Toggle PHP `short_open_tag`** with `berkan shorttag on` / `berkan shorttag off`
- Enables or disables the `<?` short tag syntax across all installed PHP versions
- Uses a dedicated `.ini` file (`berkan-short-open-tag.ini`) in each PHP version's `conf.d` directory
- Settings persist in `config.json` and are applied via PHP-FPM restart

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
- **Homebrew** and **Composer** (installed in the steps below)
- Port 80/443 are used by default, but **custom ports** can be selected during installation if these are occupied

## Installation

### Step 1: Install Homebrew

If you don't have Homebrew installed, run:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### Step 2: Install PHP and Composer

```bash
brew install php composer
```

### Step 3: Install Berkan

```bash
composer global require berkan/server
```

> **Note:** Make sure Composer's global vendor bin directory is in your `PATH`. Add one of the following to your shell profile (`~/.zshrc` or `~/.bashrc`):
> ```bash
> export PATH="$HOME/.composer/vendor/bin:$PATH"
> # or on newer Composer versions:
> export PATH="$HOME/.config/composer/vendor/bin:$PATH"
> ```
> Then restart your terminal or run `source ~/.zshrc`.

### Step 4: Install Berkan Services

```bash
sudo berkan install
```

This is an interactive installer that will guide you through the setup. It automatically checks for Homebrew, PHP, and Composer — installing any that are missing.

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
  [4] 8.0
  [5] 7.4
  [6] 7.3
  [7] 7.2
  [8] 7.1
  [9] 7.0
  [10] 5.6
> 0,1,5
```
Select one or more PHP versions. The latest version you select will be set as the active version. Older versions (5.6–8.0) are installed via the `shivammathur/php` Homebrew tap (added automatically).

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

**4. Port Configuration (automatic):**

If ports 80 or 443 are already in use by another process, Berkan will detect the conflict and offer alternative ports:
```
⚠ Port 80 is currently in use by another process.
⚠ Port 443 is currently in use by another process.

Which ports would you like to use?
  [0] Default (80/443) - stop conflicting services manually
  [1] 8080/8443
  [2] 8888/8843
  [3] Custom
> 1
```
If no conflict is detected, Berkan silently uses the default ports 80/443. Custom ports are saved in `config.json` and all server configurations are automatically updated.

### Step 5: Trust Berkan (Recommended)

This step adds Berkan to sudoers so you don't need to type your password for common operations like `start`, `stop`, `restart`, `secure`, etc.:

```bash
sudo berkan trust
```

### Step 6: Verify Installation

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

### Step 7: Park Your Projects Directory

```bash
mkdir -p ~/Sites
cd ~/Sites
berkan park
```

Berkan will scan the directory, detect each project's framework, and interactively ask which PHP version to use for each project:

```
Found 3 projects in [/Users/you/Sites]:

  blog [WordPress] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
    [2] 7.4
  > 2

  myapp [Laravel] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
  > 0

  legacy-api [PHP (Composer)] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
    [2] 7.4
  > 2
```

Projects assigned a specific PHP version will be isolated with their own PHP-FPM pool and socket. Projects set to "Default" will use the globally linked PHP version.

Now any folder you create inside `~/Sites/` will automatically be accessible as `http://folder-name.test` in your browser.

### What `berkan install` Does (Step by Step)

1. **Checks for Homebrew** — if not installed, runs the official Homebrew installer automatically
2. **Checks for Composer** — if not installed, installs it via Homebrew
3. Asks you to choose a web server (Apache or Nginx), PHP versions, and databases
4. **Detects port conflicts** on 80/443 and lets you choose alternative ports if needed
5. Creates the configuration directory at `~/.config/berkan/`
6. Creates subdirectories: `Apache/` or `Nginx/`, `Certificates/`, `Drivers/`, `Sites/`, `Log/`, `dnsmasq.d/`
7. Saves your choices (including port configuration) to `~/.config/berkan/config.json`
8. Installs the chosen web server via Homebrew (if not already installed)
9. Writes the main server configuration (`httpd.conf` for Apache or `nginx.conf` for Nginx) with configured ports
10. Creates the default catch-all server configuration (routes all `.test` requests to `server.php`)
11. Configures PHP-FPM with a `berkan` pool using Unix socket (`~/.config/berkan/berkan.sock`)
12. Installs additional PHP versions you selected via Homebrew (adds `shivammathur/php` tap for older versions)
13. Installs and configures DnsMasq to resolve all `*.test` domains to `127.0.0.1`
14. Creates macOS resolver file at `/etc/resolver/test`
15. Installs and starts selected databases via Homebrew
16. Starts all services (`brew services start`)
17. Symlinks the `berkan` executable to `/usr/local/bin/berkan`
18. Copies a sample custom driver to `~/.config/berkan/Drivers/`

### Updating Berkan

```bash
composer global update berkan/server
sudo berkan restart
```

### Alternative: Manual Installation

If you prefer to install from source:

```bash
git clone https://github.com/berkanakman/berkanServer.git
cd berkanServer
composer install
sudo berkan install
```

To update a manual installation:

```bash
cd /path/to/berkanServer
git pull origin main
composer install
sudo berkan restart
```

## Quick Start

```bash
# 1. Install Homebrew, PHP, and Composer (skip if already installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
brew install php composer

# 2. Install Berkan
composer global require berkan/server

# 3. Install services (interactive — choose web server, PHP versions, databases)
sudo berkan install

# 4. Park your projects directory (interactive — scans projects, asks PHP version per project)
cd ~/Sites
berkan park

# 5. Create a Laravel project
composer create-project laravel/laravel myapp

# 6. Visit in browser
# http://myapp.test

# 7. Secure with HTTPS
sudo berkan secure myapp

# 8. Visit with HTTPS
# https://myapp.test

# 9. Install an older PHP version for a legacy project
sudo berkan php:install 7.4

# 10. Isolate a legacy site to PHP 7.4
cd ~/Sites/legacy-app
berkan isolate 7.4
```

That's it. Every folder inside `~/Sites/` is now automatically available as `http://folder-name.test`. Legacy projects can run older PHP versions side by side with modern ones.

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
| `berkan park [path]` | Register directory as a parked path with interactive project scanning and per-project PHP version selection |
| `berkan parked` | List all parked directories |
| `berkan forget [path]` | Remove a parked path |
| `berkan link [name]` | Create a symbolic link for the current directory |
| `berkan links` | List all linked sites with SSL status, URLs, and PHP version |
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
| `berkan shorttag on` | Enable PHP `short_open_tag` (`<?` syntax) on all PHP versions |
| `berkan shorttag off` | Disable PHP `short_open_tag` on all PHP versions |
| `berkan shorttag` | Display current `short_open_tag` status |
| `berkan error hide` | Hide PHP errors (notices, warnings, deprecations) on all sites |
| `berkan error show` | Show PHP errors on all sites |
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

The `park` command registers a directory and interactively scans it for projects. Every subdirectory within a parked directory becomes automatically accessible as a `.test` site.

```bash
cd ~/Sites
berkan park
```

Berkan will scan the directory, detect each project's framework (Laravel, Symfony, WordPress, Drupal, Magento 2, Craft CMS, Joomla, Composer, Plain PHP, etc.), and ask which PHP version to assign:

```
Found 3 projects in [/Users/you/Sites]:

  blog [WordPress] — PHP version?
  > 7.4

  myapp [Laravel] — PHP version?
  > Default (global PHP)

  api [Symfony] — PHP version?
  > 8.3
```

Each project assigned a specific PHP version gets an isolated PHP-FPM pool with its own socket (e.g., `berkan-7.4.sock`). This means different projects can run entirely different PHP versions simultaneously.

```
# Result:
# ~/Sites/blog       -> http://blog.test       (PHP 7.4)
# ~/Sites/myapp      -> http://myapp.test      (global PHP)
# ~/Sites/api        -> http://api.test        (PHP 8.3)
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
# +--------+-----+---------------------+-------------------------------+------+
# | Site   | SSL | URL                 | Path                          | PHP  |
# +--------+-----+---------------------+-------------------------------+------+
# | mysite |     | http://mysite.test  | /Users/you/some/deep/nested.. | 8.4  |
# | blog   | X   | https://blog.test   | /Users/you/Sites/blog         | 7.4  |
# +--------+-----+---------------------+-------------------------------+------+
```

```bash
# Remove a link
berkan unlink mysite
```

### How Request Routing Works

**Apache:**
1. A request for `myapp.test` arrives at Apache
2. The site's `.htaccess` rewrite rules run first (if any)
3. If no `.htaccess` rule matched, Berkan's fallback rule routes unmatched requests to `server.php` (via `RewriteOptions InheritDown`)
4. `server.php` extracts the site name from the hostname
5. The driver system auto-detects the framework (Laravel, WordPress, etc.)
6. The appropriate front controller is loaded and executed

**Nginx:**
1. A request for `myapp.test` arrives at Nginx
2. The server block routes it to `server.php`
3. `server.php` extracts the site name from the hostname
4. It looks for a matching linked site or parked directory
5. The driver system auto-detects the framework
6. The appropriate front controller is loaded and executed

> **Note:** Apache uses `RewriteOptions InheritDown` so that your project's `.htaccess` rules always take priority over Berkan's fallback routing. This means traditional PHP sites with custom rewrite rules (e.g., multilingual routing like `/tr/subeler.php`) work natively without modification.

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

Berkan supports PHP versions from **5.6 to 8.4**. Versions 8.1–8.4 are available from the standard Homebrew formula. Older versions (5.6–8.0) are provided by the `shivammathur/php` tap, which Berkan adds automatically when needed.

### Supported PHP Versions

| Version | Source | Formula |
|---------|--------|---------|
| PHP 8.4 | Homebrew | `php` or `php@8.4` |
| PHP 8.3 | Homebrew | `php@8.3` |
| PHP 8.2 | Homebrew | `php@8.2` |
| PHP 8.1 | Homebrew | `php@8.1` |
| PHP 8.0 | shivammathur/php | `php@8.0` |
| PHP 7.4 | shivammathur/php | `php@7.4` |
| PHP 7.3 | shivammathur/php | `php@7.3` |
| PHP 7.2 | shivammathur/php | `php@7.2` |
| PHP 7.1 | shivammathur/php | `php@7.1` |
| PHP 7.0 | shivammathur/php | `php@7.0` |
| PHP 5.6 | shivammathur/php | `php@5.6` |

### Global PHP Switching

```bash
# Switch to PHP 8.3
sudo berkan use 8.3

# Switch to PHP 7.4
sudo berkan use 7.4

# Check current version
berkan which-php
# Output: php@8.3
```

### Installing and Removing PHP Versions

```bash
# Install a modern PHP version
sudo berkan php:install 8.2

# Install a legacy PHP version (shivammathur/php tap is added automatically)
sudo berkan php:install 7.4
sudo berkan php:install 5.6

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
# | php@7.4     | Running |        |
# | php@5.6     | Running |        |
# +-------------+---------+--------+
```

### Per-Site PHP Isolation

You can isolate individual sites to use different PHP versions. Each isolated site gets its own PHP-FPM pool and socket:

```bash
cd ~/Sites/legacy-app
berkan isolate 7.4
# Creates berkan-7.4.sock and isolated PHP-FPM pool

cd ~/Sites/modern-app
berkan isolate 8.4
# Creates berkan-8.4.sock and isolated PHP-FPM pool

# List isolated sites
berkan isolated
# +------------+-------------+
# | Site       | PHP Version |
# +------------+-------------+
# | legacy-app | php@7.4     |
# | modern-app | php@8.4     |
# +------------+-------------+

# Remove isolation
cd ~/Sites/legacy-app
berkan unisolate
```

### Per-Project PHP via `berkan park`

When you run `berkan park`, Berkan scans the directory and lets you assign PHP versions per project interactively. See [The `park` Command](#the-park-command) for details.

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
    /**
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
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

> **Note:** The `isStaticFile()` method omits the return type declaration for compatibility with PHP 7.x isolated sites. Use a `@return` docblock instead.

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
    "http_port": "80",
    "https_port": "443",
    "paths": [
        "/Users/you/Sites",
        "/Users/you/Projects"
    ],
    "web_server": "apache",
    "php_versions": ["8.4", "8.3", "7.4"],
    "databases": ["mysql", "redis"],
    "isolated_versions": {
        "legacy-app": "php@7.4",
        "old-wordpress": "php@5.6"
    },
    "hide_errors": false,
    "short_open_tag": false
}
```

| Key | Description | Default |
|-----|-------------|---------|
| `tld` | Top-level domain for sites | `test` |
| `loopback` | Loopback IP address | `127.0.0.1` |
| `http_port` | HTTP port for the web server | `80` |
| `https_port` | HTTPS port for the web server | `443` |
| `paths` | Parked directories | `[]` |
| `web_server` | Active web server (`apache` or `nginx`) | `apache` |
| `php_versions` | Installed PHP versions | `["8.4"]` |
| `databases` | Installed databases | `[]` |
| `isolated_versions` | Per-site PHP version assignments | `{}` |
| `hide_errors` | Hide PHP errors on all sites (`berkan error hide/show`) | `false` |
| `short_open_tag` | Enable PHP `short_open_tag` (`berkan shorttag on/off`) | `false` |

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
| `~/.config/berkan/berkan.sock` | PHP-FPM Unix socket (global/default) |
| `~/.config/berkan/berkan-7.4.sock` | Isolated PHP-FPM socket (example: PHP 7.4) |
| `~/.config/berkan/berkan_prepend.php` | PHP auto-prepend file for error display control |

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

## Error Display Control

Berkan can globally hide or show PHP errors (notices, warnings, deprecations, etc.) across all your sites:

```bash
# Hide PHP errors on all sites
berkan error hide

# Show PHP errors again
berkan error show
```

This uses PHP-FPM's `auto_prepend_file` mechanism, which injects a small script (`berkan_prepend.php`) before every PHP request. The script reads the `hide_errors` setting from `config.json` and suppresses error display accordingly.

This approach works reliably across all scenarios:
- Sites using `.htaccess` rewrite rules
- Sites isolated to different PHP versions (PHP 7.x, 8.x)
- Sites served via `server.php` fallback router

The setting takes effect immediately for new requests without requiring a service restart.

## Short Open Tag

Berkan can globally enable or disable PHP's `short_open_tag` directive, which controls whether the `<?` short tag syntax is allowed (in addition to the standard `<?php`):

```bash
# Enable short open tag (<? syntax)
berkan shorttag on

# Disable short open tag (only <?php works)
berkan shorttag off

# Check current status
berkan shorttag
```

Since `short_open_tag` is a `PHP_INI_PERDIR` directive, it cannot be changed at runtime with `ini_set()`. Berkan handles this by writing a dedicated `.ini` file (`berkan-short-open-tag.ini`) to each installed PHP version's `conf.d` directory and restarting PHP-FPM.

The setting is applied to **all installed PHP versions** simultaneously.

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
   .htaccess rules             (Apache only) Site's own rewrite rules run first
        |
        v
   server.php                  Fallback request router (unmatched requests)
        |
        v
   Driver System               Auto-detects framework
        |
        v
   PHP-FPM                     Executes PHP via Unix socket
        |                      (auto_prepend_file for error control)
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
│   ├── app.php               # 48 command definitions
│   ├── includes/
│   │   └── helpers.php       # Helper functions
│   ├── stubs/                # Configuration templates
│   │   ├── httpd.conf        # Apache main config
│   │   ├── berkan.conf       # Apache default catch-all VirtualHost
│   │   ├── site.berkan.conf  # Apache per-site VirtualHost (InheritDown)
│   │   ├── secure.berkan.conf # Apache HTTPS VirtualHost (InheritDown)
│   │   ├── proxy.berkan.conf  # Apache proxy VirtualHost
│   │   ├── secure.proxy.berkan.conf # Apache secure proxy
│   │   ├── nginx.conf         # Nginx main config
│   │   ├── nginx-berkan.conf  # Nginx default catch-all server
│   │   ├── nginx-site.berkan.conf  # Nginx per-site server
│   │   ├── nginx-secure.berkan.conf # Nginx HTTPS server
│   │   ├── nginx-proxy.berkan.conf  # Nginx proxy server
│   │   ├── nginx-secure.proxy.berkan.conf # Nginx secure proxy
│   │   ├── etc-phpfpm-berkan.conf  # PHP-FPM pool template
│   │   ├── etc-phpfpm-berkan-isolated.conf # Isolated PHP-FPM pool template
│   │   ├── berkan_prepend.php # PHP auto-prepend for error display control
│   │   └── ...               # More templates
│   ├── templates/
│   │   └── 404.html          # Custom 404 page
│   └── Berkan/               # PHP classes (PSR-4: Berkan\)
│       ├── Contracts/
│       │   └── WebServer.php  # WebServer interface
│       ├── Apache.php         # Apache management (implements WebServer)
│       ├── Nginx.php          # Nginx management (implements WebServer)
│       ├── Berkan.php         # Main class
│       ├── Brew.php           # Homebrew integration
│       ├── CommandLine.php    # Shell command execution
│       ├── Composer.php       # Composer package management
│       ├── Configuration.php  # Config management
│       ├── Database.php       # Database management
│       ├── Diagnose.php       # Diagnostics
│       ├── DnsMasq.php        # DNS management
│       ├── Filesystem.php     # File operations
│       ├── PhpFpm.php         # PHP-FPM management
│       ├── Server.php         # Request routing
│       ├── Site.php           # Site & SSL management
│       ├── Status.php         # Health checks
│       ├── Ngrok.php          # Ngrok tunnel sharing
│       ├── Expose.php         # Expose tunnel sharing
│       ├── Cloudflared.php    # Cloudflare tunnel sharing
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

### Port conflicts

If ports 80/443 are occupied by another service, you have two options:

1. **Use custom ports** - Run `berkan install` again and select alternative ports (e.g., 8080/8443). The ports are stored in `config.json` and all server configurations are updated automatically.

2. **Free the default ports** - Stop the conflicting service:
   ```bash
   # Find what's using port 80
   sudo lsof -i :80

   # Stop macOS built-in Apache if it's running
   sudo apachectl stop
   sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
   ```

When using custom ports, access your sites with the port number: `http://myapp.test:8080` or `https://myapp.test:8443`.

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
# Check if default socket exists
ls -la ~/.config/berkan/berkan.sock

# Check isolated sockets (e.g., PHP 7.4)
ls -la ~/.config/berkan/berkan-7.4.sock

# Restart PHP-FPM
sudo brew services restart php

# Restart a specific isolated PHP-FPM
sudo brew services restart php@7.4

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
- **İnteraktif park** - `berkan park` projeleri tarar, framework algılar ve proje bazında PHP versiyonu seçmenizi sağlar
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

- **Geniş PHP versiyon desteği**: PHP 5.6, 7.0–7.4, 8.0–8.4 — `shivammathur/php` tap ile eski versiyonlar desteklenir
- Kurulu herhangi bir PHP versiyonu arasında **global PHP versiyon değiştirme**
- `berkan php:install` / `berkan php:remove` ile istediğiniz zaman **PHP versiyonu kur/kaldır**
- `berkan php:list` ile **kurulu PHP versiyonlarını listele**
- **Site bazlı PHP izolasyonu** - farklı siteler arasında farklı PHP versiyonları çalıştırabilir
- **`berkan park` ile proje bazlı PHP seçimi** - interaktif proje tarama, otomatik framework algılama ve PHP versiyon atama
- **İzole PHP-FPM havuzları** - her izole versiyon kendi soketini alır (`berkan-7.4.sock`, `berkan-8.1.sock`, vb.)
- **Park sırasında otomatik framework algılama**: Laravel, Symfony, WordPress, Drupal, Magento 2, Craft CMS, Joomla, Composer projeleri, Plain PHP
- `berkan php` - Berkan'ın yönettiği versiyonla PHP CLI çalıştırma
- `berkan composer` - doğru PHP binary'si ile Composer çalıştırma
- Unix soket (`berkan.sock`) ile otomatik **PHP-FPM havuz yapılandırması**
- Geliştirme için ayarlanmış bellek limiti, yükleme boyutu ve çalışma süresi (512 MB, zaman aşımı yok)

### Hata Görüntüleme Kontrolü

- `berkan error hide` / `berkan error show` ile **PHP hata görüntülemesini açıp kapatın**
- Güvenilir istek bazlı hata kontrolü için PHP-FPM `auto_prepend_file` kullanır
- `.htaccess` rewrite kuralları ve PHP versiyon izolasyonu olan siteler dahil tüm sitelerde tutarlı çalışır
- Ayarlar `config.json`'da saklanır ve yeniden başlatma gerekmeden anında etki eder

### Short Open Tag

- `berkan shorttag on` / `berkan shorttag off` ile **PHP `short_open_tag` ayarını açıp kapatın**
- Tüm kurulu PHP versiyonlarında `<?` kısa tag sözdizimini etkinleştirir veya devre dışı bırakır
- Her PHP versiyonunun `conf.d` dizininde özel bir `.ini` dosyası (`berkan-short-open-tag.ini`) kullanır
- Ayarlar `config.json`'da saklanır ve PHP-FPM yeniden başlatılarak uygulanır

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
- **Homebrew** ve **Composer** (aşağıdaki adımlarda kurulur)
- Port 80/443 varsayılan olarak kullanılır, ancak bu portlar doluysa kurulum sırasında **özel portlar** seçilebilir

## Kurulum

### Adım 1: Homebrew Kurulumu

Homebrew kurulu değilse:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### Adım 2: PHP ve Composer Kurulumu

```bash
brew install php composer
```

### Adım 3: Berkan Kurulumu

```bash
composer global require berkan/server
```

> **Not:** Composer'ın global vendor bin dizininin `PATH`'inizde olduğundan emin olun. Shell profilinize (`~/.zshrc` veya `~/.bashrc`) aşağıdakilerden birini ekleyin:
> ```bash
> export PATH="$HOME/.composer/vendor/bin:$PATH"
> # veya yeni Composer versiyonlarında:
> export PATH="$HOME/.config/composer/vendor/bin:$PATH"
> ```
> Ardından terminalinizi yeniden başlatın veya `source ~/.zshrc` çalıştırın.

### Adım 4: Berkan Servislerini Kurun

```bash
sudo berkan install
```

Bu, sizi kurulum boyunca yönlendirecek interaktif bir kurucu başlatır. Homebrew, PHP ve Composer'ı otomatik kontrol eder — eksik olanları kurar.

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
  [4] 8.0
  [5] 7.4
  [6] 7.3
  [7] 7.2
  [8] 7.1
  [9] 7.0
  [10] 5.6
> 0,1,5
```
Bir veya daha fazla PHP versiyonu seçin. Seçtiğiniz en son versiyon aktif versiyon olarak ayarlanacaktır. Eski versiyonlar (5.6–8.0) `shivammathur/php` Homebrew tap'ı ile kurulur (otomatik eklenir).

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

**4. Port Yapılandırması (otomatik):**

Port 80 veya 443 başka bir işlem tarafından kullanılıyorsa Berkan çakışmayı algılar ve alternatif portlar sunar:
```
⚠ Port 80 is currently in use by another process.
⚠ Port 443 is currently in use by another process.

Which ports would you like to use?
  [0] Default (80/443) - stop conflicting services manually
  [1] 8080/8443
  [2] 8888/8843
  [3] Custom
> 1
```
Çakışma yoksa Berkan sessizce varsayılan 80/443 portlarını kullanır. Özel portlar `config.json`'a kaydedilir ve tüm sunucu konfigürasyonları otomatik güncellenir.

### Adım 5: Berkan'ı Güvenilir Yapın (Önerilen)

Bu adım Berkan'ı sudoers'a ekler, böylece `start`, `stop`, `restart`, `secure` gibi yaygın komutlarda şifre girmeniz gerekmez:

```bash
sudo berkan trust
```

### Adım 6: Kurulumu Doğrulayın

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

### Adım 7: Projeler Dizininizi Park Edin

```bash
mkdir -p ~/Sites
cd ~/Sites
berkan park
```

Berkan dizini tarayacak, her projenin framework'ünü algılayacak ve her proje için hangi PHP versiyonunun kullanılacağını interaktif olarak soracaktır:

```
Found 3 projects in [/Users/siz/Sites]:

  blog [WordPress] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
    [2] 7.4
  > 2

  myapp [Laravel] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
  > 0

  eski-api [PHP (Composer)] — PHP version?
    [0] Default (global PHP)
    [1] 8.4
    [2] 7.4
  > 2
```

Belirli bir PHP versiyonu atanan projeler kendi PHP-FPM havuzu ve soketi ile izole edilir. "Default" seçilen projeler global PHP versiyonunu kullanır.

Artık `~/Sites/` içinde oluşturduğunuz her klasör tarayıcınızda otomatik olarak `http://klasör-adı.test` olarak erişilebilir olacaktır.

### `berkan install` Ne Yapar? (Adım Adım)

1. **Homebrew kontrolü** — kurulu değilse resmi Homebrew kurucusunu otomatik çalıştırır
2. **Composer kontrolü** — kurulu değilse Homebrew ile kurar
3. Web sunucu (Apache veya Nginx), PHP versiyonları ve veritabanları seçmenizi ister
4. **Port çakışmalarını algılar** (80/443) ve gerekirse alternatif port seçmenizi sağlar
5. `~/.config/berkan/` konfigürasyon dizinini oluşturur
6. Alt dizinleri oluşturur: `Apache/` veya `Nginx/`, `Certificates/`, `Drivers/`, `Sites/`, `Log/`, `dnsmasq.d/`
7. Seçimlerinizi (port yapılandırması dahil) `~/.config/berkan/config.json` dosyasına kaydeder
8. Seçilen web sunucuyu Homebrew ile kurar (kurulu değilse)
9. Ana sunucu konfigürasyonunu yapılandırılmış portlarla yazar (Apache için `httpd.conf` veya Nginx için `nginx.conf`)
10. Varsayılan catch-all sunucu konfigürasyonunu oluşturur (tüm `.test` isteklerini `server.php`'ye yönlendirir)
11. PHP-FPM'i Unix socket kullanan bir `berkan` havuzuyla yapılandırır (`~/.config/berkan/berkan.sock`)
12. Seçtiğiniz ek PHP versiyonlarını Homebrew ile kurar (eski versiyonlar için `shivammathur/php` tap eklenir)
13. DnsMasq'i tüm `*.test` alan adlarını `127.0.0.1`'e çözümlemek için kurar ve yapılandırır
14. `/etc/resolver/test` macOS resolver dosyasını oluşturur
15. Seçilen veritabanlarını Homebrew ile kurar ve başlatır
16. Tüm servisleri başlatır (`brew services start`)
17. `berkan` çalıştırılabilir dosyasını `/usr/local/bin/berkan` olarak symlink'ler
18. Örnek bir özel driver'ı `~/.config/berkan/Drivers/` dizinine kopyalar

### Berkan'ı Güncelleme

```bash
composer global update berkan/server
sudo berkan restart
```

### Alternatif: Manuel Kurulum

Kaynak koddan kurmayı tercih ederseniz:

```bash
git clone https://github.com/berkanakman/berkanServer.git
cd berkanServer
composer install
sudo berkan install
```

Manuel kurulumu güncellemek için:

```bash
cd /path/to/berkanServer
git pull origin main
composer install
sudo berkan restart
```

## Hızlı Başlangıç

```bash
# 1. Homebrew, PHP ve Composer kurun (zaten kuruluysa atlayın)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
brew install php composer

# 2. Berkan'ı kurun
composer global require berkan/server

# 3. Servisleri kurun (interaktif — web sunucu, PHP versiyonları, veritabanları seçin)
sudo berkan install

# 4. Projeler dizininizi park edin (interaktif — projeleri tarar, proje başına PHP versiyonu sorar)
cd ~/Sites
berkan park

# 5. Bir Laravel projesi oluşturun
composer create-project laravel/laravel myapp

# 6. Tarayıcıda ziyaret edin
# http://myapp.test

# 7. HTTPS ile güvenli hale getirin
sudo berkan secure myapp

# 8. HTTPS ile ziyaret edin
# https://myapp.test

# 9. Eski bir proje için eski PHP versiyonu kurun
sudo berkan php:install 7.4

# 10. Eski siteyi PHP 7.4'e izole edin
cd ~/Sites/eski-uygulama
berkan isolate 7.4
```

Bu kadar! `~/Sites/` içindeki her klasör artık otomatik olarak `http://klasor-adi.test` adresinde erişime açıktır. Eski projeler modern projelerle yan yana farklı PHP versiyonları çalıştırabilir.

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
| `berkan park [yol]` | Dizini interaktif proje tarama ve proje başına PHP versiyon seçimiyle park et |
| `berkan parked` | Tüm park edilmiş dizinleri listele |
| `berkan forget [yol]` | Park edilmiş yolu kaldır |
| `berkan link [isim]` | Mevcut dizin için sembolik bağlantı oluştur |
| `berkan links` | Tüm bağlantılı siteleri SSL durumu, URL'leri ve PHP versiyonlarıyla listele |
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
| `berkan shorttag on` | Tüm PHP versiyonlarında `short_open_tag` etkinleştir (`<?` sözdizimi) |
| `berkan shorttag off` | Tüm PHP versiyonlarında `short_open_tag` devre dışı bırak |
| `berkan shorttag` | Mevcut `short_open_tag` durumunu göster |
| `berkan error hide` | Tüm sitelerde PHP hatalarını gizle (notice, warning, deprecation) |
| `berkan error show` | Tüm sitelerde PHP hatalarını göster |
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

`park` komutu bir dizini kaydeder ve interaktif olarak projeleri tarar. Park edilmiş dizin içindeki her alt klasör otomatik olarak `.test` sitesi olarak erişilebilir hale gelir.

```bash
cd ~/Sites
berkan park
```

Berkan dizini tarayacak, her projenin framework'ünü algılayacak (Laravel, Symfony, WordPress, Drupal, Magento 2, Craft CMS, Joomla, Composer, Plain PHP, vb.) ve hangi PHP versiyonunun atanacağını soracaktır:

```
Found 3 projects in [/Users/siz/Sites]:

  blog [WordPress] — PHP version?
  > 7.4

  myapp [Laravel] — PHP version?
  > Default (global PHP)

  api [Symfony] — PHP version?
  > 8.3
```

Belirli bir PHP versiyonu atanan her proje, kendi soketi ile izole bir PHP-FPM havuzu alır (ör. `berkan-7.4.sock`). Bu sayede farklı projeler tamamen farklı PHP versiyonlarını aynı anda çalıştırabilir.

```
# Sonuç:
# ~/Sites/blog       -> http://blog.test       (PHP 7.4)
# ~/Sites/myapp      -> http://myapp.test      (global PHP)
# ~/Sites/api        -> http://api.test        (PHP 8.3)
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
# +-----------+-----+------------------------+-------------------------------+------+
# | Site      | SSL | URL                    | Path                          | PHP  |
# +-----------+-----+------------------------+-------------------------------+------+
# | sitelerim |     | http://sitelerim.test  | /Users/siz/çok/derin/iç/iç... | 8.4  |
# | blog      | X   | https://blog.test      | /Users/siz/Sites/blog         | 7.4  |
# +-----------+-----+------------------------+-------------------------------+------+
```

```bash
# Bağlantıyı kaldır
berkan unlink sitelerim
```

### İstek Yönlendirme Nasıl Çalışır?

**Apache:**
1. `myapp.test` için bir istek Apache'ye ulaşır
2. Sitenin `.htaccess` rewrite kuralları önce çalışır (varsa)
3. Hiçbir `.htaccess` kuralı eşleşmediyse, Berkan'ın yedek kuralı eşleşmeyen istekleri `server.php`'ye yönlendirir (`RewriteOptions InheritDown` ile)
4. `server.php` ana bilgisayar adından site adını çıkarır
5. Driver sistemi framework'ü otomatik algılar (Laravel, WordPress, vb.)
6. Uygun ön denetleyici (front controller) yüklenir ve çalıştırılır

**Nginx:**
1. `myapp.test` için bir istek Nginx'e ulaşır
2. Server bloğu isteği `server.php`'ye yönlendirir
3. `server.php` ana bilgisayar adından site adını çıkarır
4. Eşleşen bağlantılı site veya park edilmiş dizini arar
5. Driver sistemi framework'ü otomatik algılar
6. Uygun ön denetleyici yüklenir ve çalıştırılır

> **Not:** Apache, projenizin `.htaccess` kurallarının Berkan'ın yedek yönlendirmesinden her zaman öncelikli olmasını sağlamak için `RewriteOptions InheritDown` kullanır. Bu sayede özel rewrite kuralları olan geleneksel PHP siteleri (ör. çokdilli yönlendirme `/tr/subeler.php`) herhangi bir değişiklik yapılmadan doğal olarak çalışır.

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

Berkan, **PHP 5.6'dan 8.4'e** kadar tüm versiyonları destekler. 8.1–8.4 versiyonları standart Homebrew formülünden gelir. Eski versiyonlar (5.6–8.0) `shivammathur/php` tap'ı tarafından sağlanır ve gerektiğinde Berkan otomatik olarak ekler.

### Desteklenen PHP Versiyonları

| Versiyon | Kaynak | Formül |
|----------|--------|--------|
| PHP 8.4 | Homebrew | `php` veya `php@8.4` |
| PHP 8.3 | Homebrew | `php@8.3` |
| PHP 8.2 | Homebrew | `php@8.2` |
| PHP 8.1 | Homebrew | `php@8.1` |
| PHP 8.0 | shivammathur/php | `php@8.0` |
| PHP 7.4 | shivammathur/php | `php@7.4` |
| PHP 7.3 | shivammathur/php | `php@7.3` |
| PHP 7.2 | shivammathur/php | `php@7.2` |
| PHP 7.1 | shivammathur/php | `php@7.1` |
| PHP 7.0 | shivammathur/php | `php@7.0` |
| PHP 5.6 | shivammathur/php | `php@5.6` |

### Global PHP Değiştirme

```bash
# PHP 8.3'e geç
sudo berkan use 8.3

# PHP 7.4'e geç
sudo berkan use 7.4

# Mevcut versiyonu kontrol et
berkan which-php
# Çıktı: php@8.3
```

### PHP Versiyonlarını Kurma ve Kaldırma

```bash
# Modern PHP versiyonu kur
sudo berkan php:install 8.2

# Eski PHP versiyonu kur (shivammathur/php tap'ı otomatik eklenir)
sudo berkan php:install 7.4
sudo berkan php:install 5.6

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
# | php@7.4     | Running |        |
# | php@5.6     | Running |        |
# +-------------+---------+--------+
```

### Site Bazında PHP İzolasyonu

Tek tek siteleri farklı PHP versiyonları kullanacak şekilde izole edebilirsiniz. Her izole site kendi PHP-FPM havuzu ve soketini alır:

```bash
cd ~/Sites/eski-uygulama
berkan isolate 7.4
# berkan-7.4.sock ve izole PHP-FPM havuzu oluşturulur

cd ~/Sites/modern-uygulama
berkan isolate 8.4
# berkan-8.4.sock ve izole PHP-FPM havuzu oluşturulur

# İzole edilmiş siteleri listele
berkan isolated
# +------------------+-------------+
# | Site             | PHP Version |
# +------------------+-------------+
# | eski-uygulama    | php@7.4     |
# | modern-uygulama  | php@8.4     |
# +------------------+-------------+

# İzolasyonu kaldır
cd ~/Sites/eski-uygulama
berkan unisolate
```

### `berkan park` ile Proje Bazında PHP

`berkan park` çalıştırdığınızda, Berkan dizini tarar ve proje başına PHP versiyonunu interaktif olarak atamanızı sağlar. Detaylar için [`park` Komutu](#park-komutu) bölümüne bakın.

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
    /**
     * @return string|false
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
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

> **Not:** `isStaticFile()` metodu PHP 7.x izole sitelerle uyumluluk için return type bildirimi içermez. Bunun yerine `@return` docblock kullanın.

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
    "http_port": "80",
    "https_port": "443",
    "paths": [
        "/Users/siz/Sites",
        "/Users/siz/Projects"
    ],
    "web_server": "apache",
    "php_versions": ["8.4", "8.3", "7.4"],
    "databases": ["mysql", "redis"],
    "isolated_versions": {
        "eski-proje": "php@7.4",
        "eski-wordpress": "php@5.6"
    },
    "hide_errors": false,
    "short_open_tag": false
}
```

| Anahtar | Açıklama | Varsayılan |
|---------|----------|------------|
| `tld` | Siteler için üst düzey alan adı | `test` |
| `loopback` | Loopback IP adresi | `127.0.0.1` |
| `http_port` | Web sunucu HTTP portu | `80` |
| `https_port` | Web sunucu HTTPS portu | `443` |
| `paths` | Park edilmiş dizinler | `[]` |
| `web_server` | Aktif web sunucu (`apache` veya `nginx`) | `apache` |
| `php_versions` | Kurulu PHP versiyonları | `["8.4"]` |
| `databases` | Kurulu veritabanları | `[]` |
| `isolated_versions` | Site bazlı PHP versiyon atamaları | `{}` |
| `hide_errors` | Tüm sitelerde PHP hatalarını gizle (`berkan error hide/show`) | `false` |
| `short_open_tag` | PHP `short_open_tag` etkinleştir (`berkan shorttag on/off`) | `false` |

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
| `~/.config/berkan/berkan.sock` | PHP-FPM Unix soketi (global/varsayılan) |
| `~/.config/berkan/berkan-7.4.sock` | İzole PHP-FPM soketi (örnek: PHP 7.4) |
| `~/.config/berkan/berkan_prepend.php` | Hata görüntüleme kontrolü için PHP auto-prepend dosyası |

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

## Hata Görüntüleme Kontrolü

Berkan, tüm sitelerinizde PHP hatalarını (notice, warning, deprecation, vb.) toplu olarak gizleyip gösterebilir:

```bash
# Tüm sitelerde PHP hatalarını gizle
berkan error hide

# PHP hatalarını tekrar göster
berkan error show
```

Bu özellik PHP-FPM'in `auto_prepend_file` mekanizmasını kullanır. Her PHP isteğinden önce küçük bir script (`berkan_prepend.php`) enjekte edilir. Script, `config.json`'daki `hide_errors` ayarını okur ve hata görüntülemeyi buna göre bastırır.

Bu yaklaşım tüm senaryolarda güvenilir şekilde çalışır:
- `.htaccess` rewrite kuralları kullanan siteler
- Farklı PHP versiyonlarına izole edilmiş siteler (PHP 7.x, 8.x)
- `server.php` yedek yönlendirici üzerinden sunulan siteler

Ayar, servis yeniden başlatma gerektirmeden yeni istekler için anında etki eder.

## Short Open Tag

Berkan, PHP'nin `short_open_tag` direktifini toplu olarak etkinleştirip devre dışı bırakabilir. Bu direktif, standart `<?php` etiketine ek olarak `<?` kısa tag sözdiziminin kullanılıp kullanılamayacağını kontrol eder:

```bash
# Short open tag'i etkinleştir (<? sözdizimi)
berkan shorttag on

# Short open tag'i devre dışı bırak (sadece <?php çalışır)
berkan shorttag off

# Mevcut durumu kontrol et
berkan shorttag
```

`short_open_tag` bir `PHP_INI_PERDIR` direktifi olduğu için `ini_set()` ile runtime'da değiştirilemez. Berkan bunu, her kurulu PHP versiyonunun `conf.d` dizinine özel bir `.ini` dosyası (`berkan-short-open-tag.ini`) yazarak ve PHP-FPM'i yeniden başlatarak halleder.

Ayar, **tüm kurulu PHP versiyonlarına** aynı anda uygulanır.

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
   .htaccess kuralları         (Yalnızca Apache) Sitenin kendi rewrite kuralları önce çalışır
        |
        v
   server.php                  Yedek istek yönlendirici (eşleşmeyen istekler)
        |
        v
   Driver Sistemi              Framework'ü otomatik algılar
        |
        v
   PHP-FPM                     Unix soketi üzerinden PHP çalıştırır
        |                      (hata kontrolü için auto_prepend_file)
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
│   ├── app.php               # 48 komut tanımı
│   ├── includes/
│   │   └── helpers.php       # Yardımcı fonksiyonlar
│   ├── stubs/                # Konfigürasyon şablonları
│   │   ├── httpd.conf        # Apache ana konfig
│   │   ├── berkan.conf       # Apache varsayılan catch-all VirtualHost
│   │   ├── site.berkan.conf  # Apache site bazlı VirtualHost (InheritDown)
│   │   ├── secure.berkan.conf # Apache HTTPS VirtualHost (InheritDown)
│   │   ├── proxy.berkan.conf  # Apache proxy VirtualHost
│   │   ├── secure.proxy.berkan.conf # Apache güvenli proxy
│   │   ├── nginx.conf         # Nginx ana konfig
│   │   ├── nginx-berkan.conf  # Nginx varsayılan catch-all sunucu
│   │   ├── nginx-site.berkan.conf  # Nginx site bazlı sunucu
│   │   ├── nginx-secure.berkan.conf # Nginx HTTPS sunucu
│   │   ├── nginx-proxy.berkan.conf  # Nginx proxy sunucu
│   │   ├── nginx-secure.proxy.berkan.conf # Nginx güvenli proxy
│   │   ├── etc-phpfpm-berkan.conf  # PHP-FPM havuz şablonu
│   │   ├── etc-phpfpm-berkan-isolated.conf # İzole PHP-FPM havuz şablonu
│   │   ├── berkan_prepend.php # Hata görüntüleme kontrolü için PHP auto-prepend
│   │   └── ...               # Daha fazla şablon
│   ├── templates/
│   │   └── 404.html          # Özel 404 sayfası
│   └── Berkan/               # PHP sınıfları (PSR-4: Berkan\)
│       ├── Contracts/
│       │   └── WebServer.php  # WebServer arayüzü (interface)
│       ├── Apache.php         # Apache yönetimi (WebServer implement eder)
│       ├── Nginx.php          # Nginx yönetimi (WebServer implement eder)
│       ├── Berkan.php         # Ana sınıf
│       ├── Brew.php           # Homebrew entegrasyonu
│       ├── CommandLine.php    # Kabuk komut çalıştırma
│       ├── Composer.php       # Composer paket yönetimi
│       ├── Configuration.php  # Konfigürasyon yönetimi
│       ├── Database.php       # Veritabanı yönetimi
│       ├── Diagnose.php       # Tanı
│       ├── DnsMasq.php        # DNS yönetimi
│       ├── Filesystem.php     # Dosya işlemleri
│       ├── PhpFpm.php         # PHP-FPM yönetimi
│       ├── Server.php         # İstek yönlendirme
│       ├── Site.php           # Site ve SSL yönetimi
│       ├── Status.php         # Sağlık kontrolleri
│       ├── Ngrok.php          # Ngrok tünel paylaşımı
│       ├── Expose.php         # Expose tünel paylaşımı
│       ├── Cloudflared.php    # Cloudflare tünel paylaşımı
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

### Port çakışmaları

Port 80/443 başka bir servis tarafından kullanılıyorsa iki seçeneğiniz var:

1. **Özel portlar kullanın** - `berkan install` komutunu tekrar çalıştırın ve alternatif portlar seçin (ör. 8080/8443). Portlar `config.json`'a kaydedilir ve tüm sunucu konfigürasyonları otomatik güncellenir.

2. **Varsayılan portları boşaltın** - Çakışan servisi durdurun:
   ```bash
   # Port 80'i ne kullanıyor?
   sudo lsof -i :80

   # macOS dahili Apache'si çalışıyorsa durdurun
   sudo apachectl stop
   sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
   ```

Özel portlar kullanırken sitelerinize port numarasıyla erişin: `http://myapp.test:8080` veya `https://myapp.test:8443`.

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
# Varsayılan soketin var olup olmadığını kontrol et
ls -la ~/.config/berkan/berkan.sock

# İzole soketleri kontrol et (ör. PHP 7.4)
ls -la ~/.config/berkan/berkan-7.4.sock

# PHP-FPM'i yeniden başlat
sudo brew services restart php

# Belirli bir izole PHP-FPM'i yeniden başlat
sudo brew services restart php@7.4

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
