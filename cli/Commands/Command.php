<?php

namespace WPLiteCLI\Commands;

abstract class Command
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    abstract public function execute(): void;

    protected function option(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    protected function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    protected function warning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    protected function line(string $message = ''): void
    {
        echo "{$message}\n";
    }

    protected function comment(string $message): void
    {
        echo "\033[90m{$message}\033[0m\n";
    }

    protected function confirm(string $question): bool
    {
        echo "\033[33m{$question} [y/N]: \033[0m";
        $handle = fopen('php://stdin', 'r');
        $response = trim(fgets($handle));
        fclose($handle);
        
        return strtolower($response) === 'y' || strtolower($response) === 'yes';
    }
}
