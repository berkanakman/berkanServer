<?php

use Illuminate\Container\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

if (! function_exists('resolve')) {
    /**
     * Resolve the given class from the container.
     */
    function resolve(string $class): mixed
    {
        return Container::getInstance()->make($class);
    }
}

if (! function_exists('swap')) {
    /**
     * Swap the given class implementation in the container.
     */
    function swap(string $class, mixed $instance): void
    {
        Container::getInstance()->instance($class, $instance);
    }
}

if (! function_exists('info')) {
    /**
     * Display the given information message.
     */
    function info(string $text): void
    {
        output('<info>' . $text . '</info>');
    }
}

if (! function_exists('warning')) {
    /**
     * Display the given warning message.
     */
    function warning(string $text): void
    {
        output('<fg=red>' . $text . '</>');
    }
}

if (! function_exists('output')) {
    /**
     * Display the given output.
     */
    function output(string $text = ''): void
    {
        writer()->writeln($text);
    }
}

if (! function_exists('table')) {
    /**
     * Display a table in the console.
     */
    function table(array $headers = [], array $data = []): void
    {
        $table = new Table(writer());
        $table->setHeaders($headers)->setRows($data);
        $table->render();
    }
}

if (! function_exists('writer')) {
    /**
     * Get the console writer.
     */
    function writer(): ConsoleOutput
    {
        return resolve(ConsoleOutput::class);
    }
}

if (! function_exists('user')) {
    /**
     * Get the user running the command.
     */
    function user(): string
    {
        // BERKAN_SUDO_USER is set by the berkan bash script to preserve
        // the original user when auto-elevating with sudo
        if (! empty($_SERVER['BERKAN_SUDO_USER'])) {
            return $_SERVER['BERKAN_SUDO_USER'];
        }

        if (! empty($_SERVER['SUDO_USER'])) {
            return $_SERVER['SUDO_USER'];
        }

        return $_SERVER['USER'];
    }
}

if (! function_exists('should_be_sudo')) {
    /**
     * Verify that the script is currently running as "sudo".
     */
    function should_be_sudo(): void
    {
        if (! isset($_SERVER['SUDO_USER'])) {
            throw new RuntimeException('This command must be run with sudo.');
        }
    }
}

if (! function_exists('retry')) {
    /**
     * Retry the given callback the given number of times.
     */
    function retry(int $times, callable $callback, int $sleep = 0): mixed
    {
        beginning:
        try {
            return $callback();
        } catch (Exception $e) {
            if (! $times) {
                throw $e;
            }

            $times--;

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     */
    function tap(mixed $value, callable $callback): mixed
    {
        $callback($value);

        return $value;
    }
}

if (! function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     */
    function starts_with(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     */
    function ends_with(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
