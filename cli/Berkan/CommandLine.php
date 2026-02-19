<?php

namespace Berkan;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Run the given command as the non-root user.
     */
    public function run(string $command, ?callable $onError = null): string
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $process->run();

        if ($process->getExitCode() > 0) {
            if ($onError) {
                $onError($process->getExitCode(), $process->getErrorOutput());
            }
        }

        return $process->getOutput();
    }

    /**
     * Run the given command.
     */
    public function runAsUser(string $command, ?callable $onError = null): string
    {
        if (posix_getuid() === 0) {
            $user = user();
            $userInfo = posix_getpwnam($user);

            if ($userInfo === false) {
                throw new \RuntimeException("User '{$user}' not found in system.");
            }

            $uid = (int) $userInfo['uid'];
            $gid = (int) $userInfo['gid'];
            $home = $userInfo['dir'];

            // Create a PHP helper that drops privileges then execs the command
            $helper = sys_get_temp_dir() . '/berkan_exec_' . getmypid() . '.php';
            $phpCode = '<?php' . "\n"
                . 'posix_setgid(' . $gid . ');' . "\n"
                . 'posix_initgroups(' . var_export($user, true) . ', ' . $gid . ');' . "\n"
                . 'posix_setuid(' . $uid . ');' . "\n"
                . 'putenv("HOME=' . $home . '");' . "\n"
                . 'putenv("USER=' . $user . '");' . "\n"
                . 'putenv("LOGNAME=' . $user . '");' . "\n"
                . 'putenv("PATH=' . BREW_PREFIX . '/bin:' . BREW_PREFIX . '/sbin:/usr/bin:/bin:/usr/sbin:/sbin");' . "\n"
                . '$r = proc_open(["bash", "-c", $argv[1]], [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $p);' . "\n"
                . '$o = stream_get_contents($p[1]); $e = stream_get_contents($p[2]);' . "\n"
                . 'fclose($p[1]); fclose($p[2]); $x = proc_close($r);' . "\n"
                . 'fwrite(STDOUT, $o); fwrite(STDERR, $e); exit($x);' . "\n";

            file_put_contents($helper, $phpCode);

            $process = new Process([PHP_BINARY, $helper, $command]);
            $process->setTimeout(null);
            $process->run();

            @unlink($helper);

            if ($process->getExitCode() > 0 && $onError) {
                $onError($process->getExitCode(), $process->getErrorOutput());
            }

            return $process->getOutput();
        }

        return $this->run($command, $onError);
    }

    /**
     * Run the given command and pass through output.
     */
    public function passthru(string $command): void
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $process->setTty(Process::isTtySupported());

        $process->run(function ($type, $line) {
            writer()->write($line);
        });
    }

    /**
     * Run a command and return the exit code.
     */
    public function runCommand(string $command): int
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $process->run();

        return $process->getExitCode();
    }

    /**
     * Run the given command silently.
     */
    public function quietly(string $command): string
    {
        return $this->run($command . ' > /dev/null 2>&1');
    }

    /**
     * Run the given command silently as the non-root user.
     */
    public function quietlyAsUser(string $command): string
    {
        return $this->runAsUser($command . ' > /dev/null 2>&1');
    }
}
