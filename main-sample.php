<?php

use WPLite\Facades\App;
/**
 * Plugin Name: WPLite Core
 * Description: WPLite Core.
 * Version: 1.0
 * Author: Hesam
 */

if (!defined('ABSPATH')) exit;

define('WPLITE_FILE', __FILE__);
define('WPLITE_PATH', plugin_dir_path(__FILE__));
require __DIR__ . '/vendor/autoload.php';
App::setPluginFile(WPLITE_FILE);
App::setPluginPath(WPLITE_PATH);

App::boot();
