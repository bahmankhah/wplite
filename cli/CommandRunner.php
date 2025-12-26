<?php

namespace WPLiteCLI;

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
            'install' => InstallCommand::class,
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
        echo "  \033[1;36mWPLite CLI\033[0m - WordPress Plugin Framework Branding Tool\n";
        echo "\n";
        echo "  \033[1;33mUsage:\033[0m\n";
        echo "    php wplite <command> [options]\n";
        echo "\n";
        echo "  \033[1;33mAvailable Commands:\033[0m\n";
        echo "    \033[32minstall\033[0m       Brand the framework with your plugin namespace\n";
        echo "\n";
        echo "  \033[1;33mInstall Options:\033[0m\n";
        echo "    --prefix=<Name>   The namespace prefix (e.g., MyPlugin)\n";
        echo "    --dry-run         Preview changes without modifying files\n";
        echo "    --force           Skip confirmation prompt\n";
        echo "\n";
        echo "  \033[1;33mExamples:\033[0m\n";
        echo "    php wplite install --prefix=MyPlugin\n";
        echo "    php wplite install --prefix=MyPlugin --dry-run\n";
        echo "    php wplite install --prefix=MyPlugin --force\n";
        echo "\n";
    }

    private function error(string $message): void
    {
        echo "\033[31mError: {$message}\033[0m\n";
    }
}
