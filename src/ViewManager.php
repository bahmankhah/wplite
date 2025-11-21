<?php

namespace WPLite;

use WPLite\Facades\App;

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