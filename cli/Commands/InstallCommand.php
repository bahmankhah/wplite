<?php

namespace WPLiteCLI\Commands;

class InstallCommand extends Command
{
    private string $prefix;
    private bool $dryRun;
    private string $srcPath;
    private array $modifiedFiles = [];
    private array $changes = [];

    // Original namespace placeholder
    private const ORIGINAL_NAMESPACE = 'WPLite';

    public function execute(): void
    {
        $this->prefix = $this->option('prefix');
        $this->dryRun = $this->hasOption('dry-run');
        $this->srcPath = dirname(__DIR__, 2) . '/src';

        // Validate prefix
        if (empty($this->prefix)) {
            $this->error('The --prefix option is required.');
            $this->line('Usage: php wplite install --prefix=MyPlugin');
            exit(1);
        }

        if (!$this->isValidNamespace($this->prefix)) {
            $this->error("Invalid prefix: '{$this->prefix}'");
            $this->line('Prefix must be a valid PHP namespace (e.g., MyPlugin, My_Plugin)');
            exit(1);
        }

        // Check if src directory exists
        if (!is_dir($this->srcPath)) {
            $this->error("Source directory not found: {$this->srcPath}");
            exit(1);
        }

        $this->line();
        $this->info("╔══════════════════════════════════════════════════════════════╗");
        $this->info("║              WPLite Framework Branding Tool                  ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝");
        $this->line();

        if ($this->dryRun) {
            $this->warning("🔍 DRY RUN MODE - No files will be modified");
            $this->line();
        }

        $this->line("Prefix: \033[1;36m{$this->prefix}\033[0m");
        $this->line("New namespace: \033[1;36m{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\033[0m");
        $this->line();

        // Confirmation prompt (unless --force or --dry-run)
        if (!$this->dryRun && !$this->hasOption('force')) {
            $this->warning("⚠️  This action will modify all PHP files in the src/ directory.");
            $this->warning("   Make sure you have committed your changes to version control.");
            $this->line();
            
            if (!$this->confirm('Do you want to continue?')) {
                $this->line('Operation cancelled.');
                exit(0);
            }
            $this->line();
        }

        // Execute the branding process
        $this->brand();

        // Show summary
        $this->showSummary();
    }

    private function brand(): void
    {
        $this->info("📦 Starting namespace branding...");
        $this->line();

        // Step 1: Process all PHP files in src/
        $files = $this->getPhpFiles($this->srcPath);
        
        foreach ($files as $file) {
            $this->processFile($file);
        }

        // Step 2: Process helpers file with special handling
        $helpersFile = $this->srcPath . '/Helpers/main.php';
        if (file_exists($helpersFile)) {
            $this->processHelpersFile($helpersFile);
        }

        // Step 3: Update autoload.php
        $autoloadFile = $this->srcPath . '/autoload.php';
        if (file_exists($autoloadFile)) {
            $this->processAutoloadFile($autoloadFile);
        }
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $relativePath = str_replace(dirname($this->srcPath) . '/', '', $filePath);
        $fileChanges = [];

        // Skip helpers file here - it has special processing
        if (str_contains($filePath, 'Helpers/main.php')) {
            return;
        }

        // 1. Replace namespace declarations
        // namespace WPLite; -> namespace MyPlugin\WPLite;
        // namespace WPLite\Facades; -> namespace MyPlugin\WPLite\Facades;
        $content = preg_replace_callback(
            '/^namespace\s+' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '(\\\\[^;]+)?;/m',
            function ($matches) use (&$fileChanges) {
                $subNamespace = $matches[1] ?? '';
                $old = "namespace " . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $new = "namespace {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $fileChanges[] = ['type' => 'namespace', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 2. Replace use statements
        // use WPLite\Container; -> use MyPlugin\WPLite\Container;
        $content = preg_replace_callback(
            '/^use\s+' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '(\\\\[^;]+)?;/m',
            function ($matches) use (&$fileChanges) {
                $subNamespace = $matches[1] ?? '';
                $old = "use " . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $new = "use {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $fileChanges[] = ['type' => 'use', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 3. Replace fully qualified class references in code
        // \WPLite\RouteManager::class -> \MyPlugin\WPLite\RouteManager::class
        $content = preg_replace_callback(
            '/\\\\' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '\\\\([A-Za-z0-9_\\\\]+)/',
            function ($matches) use (&$fileChanges) {
                $className = $matches[1];
                $old = "\\" . self::ORIGINAL_NAMESPACE . "\\{$className}";
                $new = "\\{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\\{$className}";
                $fileChanges[] = ['type' => 'fqcn', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 4. Replace PHPDoc references
        // @see \WPLite\Application -> @see \MyPlugin\WPLite\Application
        // @method static \WPLite\Application -> @method static \MyPlugin\WPLite\Application
        $content = preg_replace_callback(
            '/@(see|method|return|param|var)\s+([^\\\\]*?)\\\\' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '\\\\/',
            function ($matches) use (&$fileChanges) {
                $tag = $matches[1];
                $prefix = $matches[2];
                return "@{$tag} {$prefix}\\{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\\";
            },
            $content
        );

        // Save changes if content was modified
        if ($content !== $originalContent) {
            $this->modifiedFiles[] = $relativePath;
            $this->changes[$relativePath] = $fileChanges;

            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->comment("  ✓ {$relativePath}");
        }
    }

    private function processHelpersFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $relativePath = str_replace(dirname($this->srcPath) . '/', '', $filePath);
        $fileChanges = [];

        // 1. Update use statements first
        $content = preg_replace_callback(
            '/^use\s+' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '(\\\\[^;]+)?;/m',
            function ($matches) use (&$fileChanges) {
                $subNamespace = $matches[1] ?? '';
                $old = "use " . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $new = "use {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $fileChanges[] = ['type' => 'use', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 2. Update inline FQCN references (like in PHPDoc or type hints)
        $content = preg_replace_callback(
            '/\\\\' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '\\\\([A-Za-z0-9_\\\\]+)/',
            function ($matches) use (&$fileChanges) {
                $className = $matches[1];
                $old = "\\" . self::ORIGINAL_NAMESPACE . "\\{$className}";
                $new = "\\{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\\{$className}";
                $fileChanges[] = ['type' => 'fqcn', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 3. Add namespace declaration after <?php if not present
        // We wrap the entire file in a namespace block
        if (!preg_match('/^namespace\s+/m', $content)) {
            // Check if file starts with <?php
            if (preg_match('/^<\?php\s*/', $content)) {
                $content = preg_replace(
                    '/^<\?php\s*/',
                    "<?php\n\nnamespace {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . ";\n\n",
                    $content
                );
                $fileChanges[] = [
                    'type' => 'namespace_add',
                    'from' => '(none)',
                    'to' => "namespace {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . ";"
                ];
            }
        }

        // Save changes if content was modified
        if ($content !== $originalContent) {
            $this->modifiedFiles[] = $relativePath;
            $this->changes[$relativePath] = $fileChanges;

            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->comment("  ✓ {$relativePath} (helpers - namespaced)");
        }
    }

    private function processAutoloadFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $relativePath = str_replace(dirname($this->srcPath) . '/', '', $filePath);
        $fileChanges = [];

        // 1. Update use statements
        $content = preg_replace_callback(
            '/^use\s+' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '(\\\\[^;]+)?;/m',
            function ($matches) use (&$fileChanges) {
                $subNamespace = $matches[1] ?? '';
                $old = "use " . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $new = "use {$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "{$subNamespace};";
                $fileChanges[] = ['type' => 'use', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // 2. Update the autoloader prefix string
        // $prefix = 'WPLite\\'; -> $prefix = 'MyPlugin\\WPLite\\';
        $pattern = '/\$prefix\s*=\s*\'' . preg_quote(self::ORIGINAL_NAMESPACE, '/') . '\\\\\\\\\';/';
        $content = preg_replace_callback(
            $pattern,
            function ($matches) use (&$fileChanges) {
                $old = "\$prefix = '" . self::ORIGINAL_NAMESPACE . "\\\\';";
                $new = "\$prefix = '{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\\\\';";
                $fileChanges[] = ['type' => 'autoloader', 'from' => $old, 'to' => $new];
                return $new;
            },
            $content
        );

        // Save changes if content was modified
        if ($content !== $originalContent) {
            $this->modifiedFiles[] = $relativePath;
            $this->changes[$relativePath] = $fileChanges;

            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->comment("  ✓ {$relativePath} (autoloader updated)");
        }
    }

    private function getPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function isValidNamespace(string $namespace): bool
    {
        // Valid PHP namespace: starts with letter or underscore, contains only alphanumeric and underscores
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $namespace);
    }

    private function showSummary(): void
    {
        $this->line();
        $this->info("═══════════════════════════════════════════════════════════════");
        
        if ($this->dryRun) {
            $this->warning("DRY RUN COMPLETE - No files were modified");
            $this->line();
            $this->line("Files that would be modified: " . count($this->modifiedFiles));
        } else {
            $this->info("✅ Branding complete!");
            $this->line();
            $this->line("Files modified: " . count($this->modifiedFiles));
        }

        $this->line();

        if (!empty($this->modifiedFiles)) {
            $this->comment("Modified files:");
            foreach ($this->modifiedFiles as $file) {
                $this->comment("  - {$file}");
            }
        }

        $this->line();
        $this->info("═══════════════════════════════════════════════════════════════");
        
        if (!$this->dryRun) {
            $this->line();
            $this->warning("📝 Next steps:");
            $this->line("   1. Update your composer.json autoload section:");
            $this->line("      \"autoload\": {");
            $this->line("          \"psr-4\": {");
            $this->line("              \"{$this->prefix}\\\\WPLite\\\\\": \"src/\"");
            $this->line("          }");
            $this->line("      }");
            $this->line();
            $this->line("   2. Run: composer dump-autoload");
            $this->line();
            $this->line("   3. Update your main plugin file to use the new namespace:");
            $this->line("      use {$this->prefix}\\WPLite\\Application;");
            $this->line();
        }
    }
}
