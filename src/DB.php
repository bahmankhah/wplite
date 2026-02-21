<?php

namespace WPLite;

/**
 * DB — static database helper for raw queries and WP_Query access.
 *
 * Role: Provides a thin static interface over WordPress $wpdb for
 *       cases where the Model query builder is insufficient.
 *
 * Responsibilities:
 *   - Execute raw prepared SQL queries via DB::query().
 *   - Expose the underlying $wpdb instance via DB::wpdb().
 *   - Run WP_Query searches via DB::wpQuery().
 *
 * How to use:
 *   - DB::query('DELETE FROM table WHERE id = %d', [42]);
 *   - $wpdb = DB::wpdb();
 *   - Prefer Model for standard CRUD; use DB for complex/raw SQL.
 *
 * Avoid:
 *   - Do not use for simple CRUD that Model can handle.
 *
 * @see \WPLite\Model  Fluent query builder (preferred for most queries).
 */
abstract class DBI {}
class DB
{
    private $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public static function __callStatic($name, $arguments)
    {
        return (new DB())->{$name . 'Main'}(...$arguments);
    }

    public static function query($sql, $args = null){}
    public function queryMain($sql, $args= null){
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                $sql,
                $args
            )
        );
        return $result;
    }

    public function wpdbMain()
    {
        return $this->wpdb;
    }

    public function getCategoryId($slug)
    {
        $category_id = $this->wpdbMain()->get_var($this->wpdbMain()->prepare("
            SELECT term_id 
            FROM {$this->wpdbMain()->terms} 
            WHERE slug = %s
        ", $slug));
        return $category_id;
    }

    public static function select($query) {}

    public static function wpQuery($args)
    {
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $list = array();

            while ($query->have_posts()) {
                $query->the_post();
                $list[] = array(
                    'ID'    => get_the_ID(),
                );
            }

            wp_reset_postdata();
            return $list;
        }

        return array(); // Return an empty array if no products match
    }
}
