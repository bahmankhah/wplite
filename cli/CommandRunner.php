<?php

namespace WPLiteCLI;

use WPLiteCLI\Commands\BuildCommand;
use WPLiteCLI\Commands\InstallCommand;

class CommandRunner
{
    private array $argv;
    private array $commands = [];

    public function __construct(array $argv)
    {
        $this->argv = $argv;
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->commands = [
            'build' => BuildCommand::class,
            'install' => InstallCommand::class, // Deprecated: kept for backward compatibility
            // Future commands can be added here:
            // 'make:controller' => MakeControllerCommand::class,
            // 'make:model' => MakeModelCommand::class,
        ];
    }

    public function run(): void
    {
        if (count($this->argv) < 2) {
            $this->showHelp();
            return;
        }

        $commandName = $this->argv[1];

        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }

        if (!isset($this->commands[$commandName])) {
            $this->error("Unknown command: {$commandName}");
            $this->showHelp();
            return;
        }

        $options = $this->parseOptions();
        $commandClass = $this->commands[$commandName];
        $command = new $commandClass($options);
        
        try {
            $command->execute();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit(1);
        }
    }

    private function parseOptions(): array
    {
        $options = [];
        
        for ($i = 2; $i < count($this->argv); $i++) {
            $arg = $this->argv[$i];
            
            // Handle --option=value format
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $options[$matches[1]] = $matches[2];
            }
            // Handle --option value format
            elseif (preg_match('/^--([^=]+)$/', $arg, $matches)) {
                $key = $matches[1];
                // Check if next arg is a value (not another option)
                if (isset($this->argv[$i + 1]) && !str_starts_with($this->argv[$i + 1], '--')) {
                    $options[$key] = $this->argv[++$i];
                } else {
                    $options[$key] = true; // Flag option
                }
            }
            // Handle -o format (short options)
            elseif (preg_match('/^-([a-zA-Z])$/', $arg, $matches)) {
                $options[$matches[1]] = true;
            }
        }
        
        return $options;
    }

    private function showHelp(): void
    {
        echo "\n";
        echo "  \033[1;36mWPLite CLI\033[0m - WordPress Plugin Framework Build Tool\n";
        echo "\n";
        echo "  \033[1;33mUsage:\033[0m\n";
        echo "    php wplite <command> [options]\n";
        echo "\n";
        echo "  \033[1;33mAvailable Commands:\033[0m\n";
        echo "    \033[32mbuild\033[0m         Build the framework with your plugin namespace\n";
        echo "    \033[90minstall\033[0m       [deprecated] Modify src/ directly (destructive)\n";
        echo "\n";
        echo "  \033[1;33mBuild Options:\033[0m\n";
        echo "    --prefix=<Name>   The namespace prefix (e.g., Forooshyar)\n";
        echo "    --output=<path>   Output directory (default: src/WPLite)\n";
        echo "    --dry-run         Preview changes without creating files\n";
        echo "\n";
        echo "  \033[1;33mExamples:\033[0m\n";
        echo "    php vendor/hsm/wplite/wplite build --prefix=Forooshyar\n";
        echo "    php vendor/hsm/wplite/wplite build   # uses saved prefix\n";
        echo "\n";
        echo "  \033[1;33mHow it works:\033[0m\n";
        echo "    Builds WPLite into src/WPLite/ with your namespace prefix.\n";
        echo "    Classes autoload via Composer's PSR-4 (e.g., Forooshyar\\WPLite\\*).\n";
        echo "    Just require src/WPLite/helpers.php for helper functions.\n";
        echo "\n";
    }

    private function error(string $message): void
    {
        echo "\033[31mError: {$message}\033[0m\n";
    }
}
