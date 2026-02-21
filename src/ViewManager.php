<?php

namespace WPLite;

use WPLite\Facades\App;

/**
 * ViewManager — PHP-based view/template engine with dot notation.
 *
 * Role: Renders PHP view files from the plugin's views/ directory,
 *       extracting data variables into the template scope.
 *
 * Responsibilities:
 *   - Resolve dot-notation view paths to filesystem paths
 *     (e.g., 'emails.welcome' → views/emails/welcome.view.php).
 *   - Extract data array into variables available in the template.
 *   - Capture template output via output buffering.
 *
 * How to use:
 *   - Via facade: View::render('dashboard.index', ['stats' => $stats]);
 *   - Via helper: view('dashboard.index', ['stats' => $stats]);
 *
 * Avoid:
 *   - Do not include complex PHP logic in view files.
 *   - Views must be .view.php files in the views/ directory.
 *
 * @see \WPLite\Facades\View  Facade for this class.
 */
class ViewManager{
    private function make(string $view, array $data = []): string {
        $filePath = App::pluginPath() . "views/{$view}.view.php";
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("View [{$view}] does not exist.");
        }
        
        ob_start();
        extract($data);
        include $filePath;
        return ob_get_clean();
    }

    public function render(string $view, array $data = []): void {
        $view = str_replace('.', '/', $view);
        echo $this->make($view, $data);
    }
}