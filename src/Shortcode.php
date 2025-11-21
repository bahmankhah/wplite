<?php

namespace WPLite;
use WPLite\Contracts\ShortcodeProvider;

abstract class Shortcode implements ShortcodeProvider
{
 /**
     * The shortcode tag: [example]
     */
    protected string $tag;

    /**
     * Shortcode attributes
     */
    protected array $attributes = [];

    /**
     * Shortcode content (if exists)
     */
    protected ?string $content = null;

    /**
     * Register the shortcode with WordPress
     */
    public static function register()
    {
        $instance = new static();
        add_shortcode($instance->tag, function ($atts = [], $content = null) use ($instance) {
            $instance->attributes = shortcode_atts($instance->defaults(), $atts);
            $instance->content = $content;

            return $instance->render();
        });
    }

    /**
     * Default attributes for the shortcode
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Render the output HTML
     * Must be implemented by child classes
     */
    abstract public function render(): string;
}
