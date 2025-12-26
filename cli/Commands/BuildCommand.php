<?php

namespace WPLiteCLI\Commands;

class BuildCommand extends Command
{
    private string $prefix;
    private bool $dryRun;
    private string $basePath;
    private string $srcPath;
    private string $corePath;
    private string $configFile;
    private array $processedFiles = [];

    private const ORIGINAL_NAMESPACE = 'WPLite';
    private const CONFIG_FILE = 'wplite-config.json';

    public function execute(): void
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->srcPath = $this->basePath . '/src';
        $this->dryRun = $this->hasOption('dry-run');

        // Determine output path (default: ./core, or custom via --output)
        $outputOption = $this->option('output');
        if ($outputOption) {
            // If relative path, resolve from current working directory
            $this->corePath = str_starts_with($outputOption, '/')
                ? $outputOption
                : getcwd() . '/' . $outputOption;
            $this->configFile = dirname($this->corePath) . '/' . self::CONFIG_FILE;
        } else {
            $this->corePath = $this->basePath . '/core';
            $this->configFile = $this->basePath . '/' . self::CONFIG_FILE;
        }

        // Load or set prefix
        $this->prefix = $this->resolvePrefix();

        if (empty($this->prefix)) {
            $this->error('No prefix configured.');
            $this->line('Run: php wplite build --prefix=MyPlugin');
            exit(1);
        }

        if (!$this->isValidNamespace($this->prefix)) {
            $this->error("Invalid prefix: '{$this->prefix}'");
            $this->line('Prefix must be a valid PHP namespace (e.g., MyPlugin, My_Plugin)');
            exit(1);
        }

        if (!is_dir($this->srcPath)) {
            $this->error("Source directory not found: {$this->srcPath}");
            exit(1);
        }

        $this->showHeader();

        $outputRelative = str_replace(getcwd() . '/', '', $this->corePath);

        if ($this->dryRun) {
            $this->warning("🔍 DRY RUN MODE - No files will be created or modified");
            $this->line();
        }

        $this->line("Prefix: \033[1;36m{$this->prefix}\033[0m");
        $this->line("Source: \033[90msrc/\033[0m (read-only template)");
        $this->line("Output: \033[90m{$outputRelative}/\033[0m (generated)");
        $this->line("Namespace: \033[1;36m{$this->prefix}\\" . self::ORIGINAL_NAMESPACE . "\033[0m");
        $this->line();

        // Execute build
        $this->build();

        // Show summary
        $this->showSummary();
    }

    private function resolvePrefix(): string
    {
        // Priority: CLI option > config file
        $cliPrefix = $this->option('prefix');

        if (!empty($cliPrefix)) {
            // Save to config for future runs
            if (!$this->dryRun) {
                $this->saveConfig(['prefix' => $cliPrefix]);
            }
            return $cliPrefix;
        }

        // Try loading from config
        $config = $this->loadConfig();
        return $config['prefix'] ?? '';
    }

    private function loadConfig(): array
    {
        if (!file_exists($this->configFile)) {
            return [];
        }

        $content = file_get_contents($this->configFile);
        $config = json_decode($content, true);

        return is_array($config) ? $config : [];
    }

    private function saveConfig(array $config): void
    {
        $existing = $this->loadConfig();
        $merged = array_merge($existing, $config);

        file_put_contents(
            $this->configFile,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->comment("  📝 Saved configuration to " . self::CONFIG_FILE);
    }

    private function build(): void
    {
        $this->info("📦 Building namespaced framework...");
        $this->line();

        // Step 1: Clean core/ directory
        $this->cleanCoreDirectory();

        // Step 2: Copy src/ to core/
        $this->copySrcToCore();

        // Step 3: Transform files in core/
        $this->transformFiles();

        // Step 4: Generate autoload.php
        $this->generateAutoload();
    }

    private function cleanCoreDirectory(): void
    {
        if (is_dir($this->corePath)) {
            $this->comment("  🗑️  Cleaning existing core/ directory...");
            if (!$this->dryRun) {
                $this->deleteDirectory($this->corePath);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function copySrcToCore(): void
    {
        $this->comment("  📁 Copying src/ to core/...");

        if ($this->dryRun) {
            return;
        }

        $this->copyDirectory($this->srcPath, $this->corePath);
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = "{$src}/{$file}";
            $dstPath = "{$dst}/{$file}";

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    private function transformFiles(): void
    {
        $this->comment("  🔄 Transforming namespaces...");
        $this->line();

        $outputRelative = str_replace(getcwd() . '/', '', $this->corePath);

        if ($this->dryRun) {
            // In dry run, show what would be transformed from src/
            $files = $this->getPhpFiles($this->srcPath);
            foreach ($files as $file) {
                $relativePath = str_replace($this->srcPath . '/', '', $file);
                $this->processedFiles[] = "{$outputRelative}/{$relativePath}";
                $this->comment("     ✓ {$outputRelative}/{$relativePath}");
            }
            return;
        }

        $files = $this->getPhpFiles($this->corePath);

        foreach ($files as $file) {
            $this->transformFile($file);
        }

        // Special handling for helpers
        $helpersFile = "{$this->corePath}/Helpers/main.php";
        if (file_exists($helpersFile)) {
            $this->transformHelpersFile($helpersFile);
        }
    }

    private function transformFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $relativePath = str_replace($this->corePath . '/', '', $filePath);
        $outputRelative = str_replace(getcwd() . '/', '', $this->corePath);

        // Skip helpers - handled separately
        if (str_contains($filePath, 'Helpers/main.php')) {
            return;
        }

        $newNamespace = "{$this->prefix}\\" . self::ORIGINAL_NAMESPACE;

        // Use a placeholder to prevent double-replacement
        $placeholder = '___WPLITE_PLACEHOLDER___';

        // 1. Replace namespace declarations first
        // namespace WPLite; -> namespace MyPlugin\WPLite;
        // namespace WPLite\Facades; -> namespace MyPlugin\WPLite\Facades;
        $content = preg_replace(
            '/^(namespace\s+)WPLite(\s*;|\s*\\\\)/m',
            '$1' . $placeholder . '$2',
            $content
        );

        // 2. Replace use statements (without leading backslash)
        // use WPLite\Container; -> use MyPlugin\WPLite\Container;
        $content = preg_replace(
            '/^(use\s+)WPLite\\\\/m',
            '$1' . $placeholder . '\\',
            $content
        );

        // 3. Replace fully qualified class references (with leading backslash)
        // \WPLite\RouteManager -> \MyPlugin\WPLite\RouteManager
        $content = preg_replace(
            '/\\\\WPLite\\\\/',
            '\\' . $placeholder . '\\',
            $content
        );

        // 4. Now replace all placeholders with the actual namespace
        $content = str_replace($placeholder, $newNamespace, $content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = "{$outputRelative}/{$relativePath}";
            $this->comment("     ✓ {$outputRelative}/{$relativePath}");
        }
    }

    private function transformHelpersFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace($this->corePath . '/', '', $filePath);
        $outputRelative = str_replace(getcwd() . '/', '', $this->corePath);
        $newNamespace = "{$this->prefix}\\" . self::ORIGINAL_NAMESPACE;

        // Use a placeholder to prevent double-replacement
        $placeholder = '___WPLITE_PLACEHOLDER___';

        // 1. First, add namespace declaration if not present (before any replacements)
        $needsNamespace = !preg_match('/^namespace\s+/m', $content);

        // 2. Update use statements (without leading backslash)
        // use WPLite\Facades\App; -> use MyPlugin\WPLite\Facades\App;
        $content = preg_replace(
            '/^(use\s+)WPLite\\\\/m',
            '$1' . $placeholder . '\\',
            $content
        );

        // 3. Update inline FQCN references (with leading backslash)
        $content = preg_replace(
            '/\\\\WPLite\\\\/',
            '\\' . $placeholder . '\\',
            $content
        );

        // 4. Replace all placeholders with the actual namespace
        $content = str_replace($placeholder, $newNamespace, $content);

        // 5. Add namespace declaration at the top if needed
        if ($needsNamespace) {
            $content = preg_replace(
                '/^<\?php\s*/',
                "<?php\n\nnamespace {$newNamespace};\n\n",
                $content
            );
        }

        file_put_contents($filePath, $content);
        $this->processedFiles[] = "{$outputRelative}/{$relativePath}";
        $this->comment("     ✓ {$outputRelative}/{$relativePath} (helpers)");
    }

    private function generateAutoload(): void
    {
        $this->line();
        $this->comment("  📄 Generating core/autoload.php...");

        if ($this->dryRun) {
            return;
        }

        $autoloadContent = $this->buildAutoloadContent();
        file_put_contents("{$this->corePath}/autoload.php", $autoloadContent);
    }

    private function buildAutoloadContent(): string
    {
        $namespace = "{$this->prefix}\\" . self::ORIGINAL_NAMESPACE;
        $namespaceEscaped = str_replace('\\', '\\\\', $namespace);

        return <<<PHP
<?php

/**
 * WPLite Framework Autoloader
 * 
 * Generated by WPLite CLI for namespace: {$namespace}
 * 
 * @generated Do not edit this file directly. Run `php wplite build` to regenerate.
 */

// PSR-4 Autoloader for {$namespace}
spl_autoload_register(function (\$class) {
    \$prefix = '{$namespaceEscaped}\\\\';
    \$baseDir = __DIR__ . '/';

    \$len = strlen(\$prefix);
    if (strncmp(\$prefix, \$class, \$len) !== 0) {
        return;
    }

    \$relativeClass = substr(\$class, \$len);
    \$file = \$baseDir . str_replace('\\\\', '/', \$relativeClass) . '.php';

    if (file_exists(\$file)) {
        require \$file;
    }
});

// Load helper functions
foreach (glob(__DIR__ . '/Helpers/*.php') as \$file) {
    require_once \$file;
}

PHP;
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
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $namespace);
    }

    private function showHeader(): void
    {
        $this->line();
        $this->info("╔══════════════════════════════════════════════════════════════╗");
        $this->info("║              WPLite Framework Build Tool                     ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝");
        $this->line();
    }

    private function showSummary(): void
    {
        $outputRelative = str_replace(getcwd() . '/', '', $this->corePath);
        
        $this->line();
        $this->info("═══════════════════════════════════════════════════════════════");

        if ($this->dryRun) {
            $this->warning("DRY RUN COMPLETE - No files were created");
            $this->line();
            $this->line("Files that would be generated: " . \count($this->processedFiles));
        } else {
            $this->info("✅ Build complete!");
            $this->line();
            $this->line("Files generated: " . \count($this->processedFiles));
            $this->line("Output directory: {$outputRelative}/");
        }

        $this->line();
        $this->info("═══════════════════════════════════════════════════════════════");

        if (!$this->dryRun) {
            $this->line();
            $this->warning("📝 Next steps:");
            $this->line("   1. Require the autoloader in your main plugin file:");
            $this->line();
            $this->line("      \033[36mrequire_once __DIR__ . '/{$outputRelative}/autoload.php';\033[0m");
            $this->line();
            $this->line("   2. Use the namespaced classes:");
            $this->line();
            $this->line("      \033[36muse {$this->prefix}\\WPLite\\Facades\\App;\033[0m");
            $this->line();
            $this->line("   3. Add \033[33m/{$outputRelative}/\033[0m to your \033[33m.gitignore\033[0m");
            $this->line();
            $this->comment("   💡 After updating WPLite, rebuild with: php vendor/hsm/wplite/wplite build");
            $this->line();
        }
    }
}
