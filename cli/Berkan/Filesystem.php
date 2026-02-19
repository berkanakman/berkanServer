<?php

namespace Berkan;

class Filesystem
{
    /**
     * Determine if the given path is a directory.
     */
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Create a directory.
     */
    public function mkdir(string $path, ?string $owner = null, int $mode = 0755): void
    {
        if (! $this->isDir($path)) {
            mkdir($path, $mode, true);
        }

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Ensure that the given directory exists.
     */
    public function ensureDirExists(string $path, ?string $owner = null, int $mode = 0755): void
    {
        $this->mkdir($path, $owner, $mode);
    }

    /**
     * Create a directory as the non-root user.
     */
    public function mkdirAsUser(string $path, int $mode = 0755): void
    {
        $this->mkdir($path, user(), $mode);
    }

    /**
     * Touch the given path.
     */
    public function touch(string $path, ?string $owner = null): string
    {
        touch($path);

        if ($owner) {
            $this->chown($path, $owner);
        }

        return $path;
    }

    /**
     * Touch the given path as the non-root user.
     */
    public function touchAsUser(string $path): void
    {
        $this->touch($path, user());
    }

    /**
     * Determine if the given file exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Read the contents of the given file.
     */
    public function get(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * Write to the given file.
     */
    public function put(string $path, string $contents, ?string $owner = null): void
    {
        file_put_contents($path, $contents);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Write to the given file as the non-root user.
     */
    public function putAsUser(string $path, string $contents): void
    {
        $this->put($path, $contents, user());
    }

    /**
     * Append the contents to the given file.
     */
    public function append(string $path, string $contents, ?string $owner = null): void
    {
        file_put_contents($path, $contents, FILE_APPEND);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    /**
     * Append the contents to the given file as the non-root user.
     */
    public function appendAsUser(string $path, string $contents): void
    {
        $this->append($path, $contents, user());
    }

    /**
     * Copy the given file to a new location.
     */
    public function copy(string $from, string $to): void
    {
        copy($from, $to);
    }

    /**
     * Copy the given directory to a new location.
     */
    public function copyDirectory(string $from, string $to): void
    {
        if (! $this->isDir($to)) {
            $this->mkdir($to);
        }

        $files = new \FilesystemIterator($from);

        foreach ($files as $file) {
            if ($file->isDir()) {
                $this->copyDirectory($file->getPathname(), $to . '/' . $file->getFilename());
            } else {
                $this->copy($file->getPathname(), $to . '/' . $file->getFilename());
            }
        }
    }

    /**
     * Create a symlink to the given target.
     */
    public function symlinkAsUser(string $target, string $link): void
    {
        if ($this->exists($link)) {
            $this->unlink($link);
        }

        symlink($target, $link);

        $this->chown($link, user());
    }

    /**
     * Create a symlink to the given target.
     */
    public function symlink(string $target, string $link): void
    {
        if ($this->exists($link)) {
            $this->unlink($link);
        }

        symlink($target, $link);
    }

    /**
     * Read the target of the given symlink.
     */
    public function readLink(string $path): string|false
    {
        return readlink($path);
    }

    /**
     * Determine if the given path is a symbolic link.
     */
    public function isLink(string $path): bool
    {
        return is_link($path);
    }

    /**
     * Resolve the given symbolic link.
     */
    public function realpath(string $path): string|false
    {
        return realpath($path);
    }

    /**
     * Remove the given path.
     */
    public function unlink(string $path): void
    {
        if (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }

    /**
     * Remove the given directory.
     */
    public function remove(string $path): void
    {
        if (is_dir($path)) {
            $files = new \FilesystemIterator($path);

            foreach ($files as $file) {
                if ($file->isDir() && ! $file->isLink()) {
                    $this->remove($file->getPathname());
                } else {
                    $this->unlink($file->getPathname());
                }
            }

            @rmdir($path);
        } elseif (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Change the owner of the given path.
     */
    public function chown(string $path, string $user): void
    {
        chown($path, $user);
    }

    /**
     * Change the group of the given path.
     */
    public function chgrp(string $path, string $group): void
    {
        chgrp($path, $group);
    }

    /**
     * Change the mode of the given path.
     */
    public function chmod(string $path, int $mode): void
    {
        chmod($path, $mode);
    }

    /**
     * Scan the given directory.
     */
    public function scandir(string $path): array
    {
        return collect(scandir($path))
            ->reject(function ($file) {
                return in_array($file, ['.', '..']);
            })->values()->all();
    }

    /**
     * Get all of the files from the given directory (recursive).
     */
    public function files(string $path): array
    {
        if (! $this->isDir($path)) {
            return [];
        }

        return collect(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        ))->map(function ($file) {
            return $file->getPathname();
        })->values()->all();
    }

    /**
     * Get the commented configuration value from the given file.
     */
    public function commentedOutPhpIniValue(string $path, string $key): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        $contents = $this->get($path);

        if (preg_match('/^;?\s*' . preg_quote($key, '/') . '\s*=\s*(.+)$/m', $contents, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Remove a line from the given file matching the given pattern.
     */
    public function removeLine(string $file, string $pattern): void
    {
        if (! $this->exists($file)) {
            return;
        }

        $contents = $this->get($file);
        $lines = explode("\n", $contents);

        $lines = array_filter($lines, function ($line) use ($pattern) {
            return ! preg_match($pattern, $line);
        });

        $this->put($file, implode("\n", $lines));
    }

    /**
     * Determine if the given file is empty.
     */
    public function isEmpty(string $path): bool
    {
        return trim($this->get($path)) === '';
    }
}
