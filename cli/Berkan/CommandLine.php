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
        return $this->run('sudo -u "' . user() . '" ' . $command, $onError);
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
