<?php

namespace WPLite;

class WordpressManager{
    public static function shortcode($name, $callback){
        add_shortcode($name, $callback);
    }
    public static function action($name, $callback, int $priority = 10, int $accepted_args = 1){
        add_action($name, $callback, $priority, $accepted_args);
    }
    public static function filter($name, $callback, int $priority = 10, int $accepted_args = 1){
        add_filter($name, $callback, $priority, $accepted_args);
    }
}