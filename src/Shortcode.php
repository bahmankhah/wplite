<?php

namespace WPLite;

/**
 * Shortcode — abstract base class for OOP WordPress shortcodes.
 *
 * Role: Encapsulates a WordPress shortcode as a class with typed
 *       attributes, defaults, and a render method.
 *
 * Responsibilities:
 *   - Define the shortcode tag ($tag) and default attributes.
 *   - Parse shortcode attributes via shortcode_atts().
 *   - Render output via the abstract render() method.
 *   - Register with WordPress via the static register() method.
 *
 * How to use:
 *   - Extend this class, set $tag, implement render(), optionally override defaults():
 *     class PricingTable extends Shortcode {
 *         protected $tag = 'pricing_table';
 *         public function render() { return view('shortcodes.pricing', $this->attributes); }
 *     }
 *   - Call YourShortcode::register() in a provider's boot() or onInit().
 *
 * Avoid:
 *   - Do not echo output; return it from render().
 *   - Do not call register() outside of provider hooks.
 *
 * @see \WPLite\Provider  Register shortcodes in provider lifecycle hooks.
 */
abstract class Shortcode
{
 /**
     * The shortcode tag: [example]
     * @var string
     */
    protected $tag;

    /**
     * Shortcode attributes
     * @var array
     */
    protected $attributes = [];

    /**
     * Shortcode content (if exists)
     * @var string|null
     */
    protected $content = null;

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
    abstract public function render();
}
