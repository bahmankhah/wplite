<?php

namespace WPLite;

class PostType
{

    public function __construct()
    {
        $this->register();
    }

    public function register()
    {
        $args = array(
            'labels' => array(
                'name' => __('Videos', 'text-domain'),
                'singular_name' => __('Video', 'text-domain'),
                'add_new' => __('Add New', 'text-domain'),
                'add_new_item' => __('Add New Video', 'text-domain'),
                'edit_item' => __('Edit Video', 'text-domain'),
                'new_item' => __('New Video', 'text-domain'),
                'view_item' => __('View Video', 'text-domain'),
                'all_items' => __('All Videos', 'text-domain'),
                'search_items' => __('Search Videos', 'text-domain'),
                'not_found' => __('No videos found', 'text-domain'),
                'not_found_in_trash' => __('No videos found in Trash', 'text-domain'),
                'menu_name' => __('Videos', 'text-domain'),
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'supports' => array('title', 'thumbnail'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'videos'),
            'show_in_rest' => true,
        );

        register_post_type('dnp-video', $args);

        // Add meta box
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'video_url',
                __('Video URL', 'text-domain'),
                function ($post) {
                    $video_url = get_post_meta($post->ID, '_video_url', true);

                    // Display the URL input field
                    echo '<label for="video_url">' . esc_html__('Video URL:', 'text-domain') . '</label>';
                    echo '<input type="text" id="video_url" name="video_url" value="' . esc_attr($video_url) . '" style="width: 100%;" />';

                    // Add nonce field
                    wp_nonce_field('save_video_url', 'video_url_nonce');
                },
                'dnp-video',
                'normal',
                'high'
            );
        });

        // Save post meta
        add_action('save_post', function ($post_id) {
            // Check nonce
            if (!isset($_POST['video_url_nonce']) || !wp_verify_nonce($_POST['video_url_nonce'], 'save_video_url')) {
                return $post_id;
            }

            // Check post type
            if (get_post_type($post_id) !== 'dnp-video') {
                return $post_id;
            }

            // Save the custom field data
            if (isset($_POST['video_url'])) {
                update_post_meta($post_id, '_video_url', sanitize_text_field($_POST['video_url']));
            }

            return $post_id;
        });
    }
}
