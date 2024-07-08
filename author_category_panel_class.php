<?php

class Author_Category_Panel_Class {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'author_category';
    }

    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            author_id mediumint(9) NOT NULL,
            category_id mediumint(9) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function delete_tables() {
        global $wpdb;

        $sql = "DROP TABLE IF EXISTS $this->table_name;";
        $wpdb->query($sql);
    }

    public function get_categories() {
        global $wpdb;

        $author_id = get_current_user_id();
        $sql = $wpdb->prepare("SELECT category_id FROM $this->table_name WHERE author_id = %d", $author_id);
        return $wpdb->get_results($sql);
    }

    public function get_categories_for_user($user_id) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT category_id FROM $this->table_name WHERE author_id = %d", $user_id);
        return $wpdb->get_col($sql);
    }

    public function insert_author_category(int $author_id, int $category_id) {
        global $wpdb;

        // Check if the category already exists for the author
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE author_id = %d AND category_id = %d",
            $author_id, $category_id
        ));

        if ($existing) {
            return;
        }

        $wpdb->insert(
            $this->table_name,
            [
                'author_id' => $author_id,
                'category_id' => $category_id,
            ],
            [
                '%d',
                '%d',
            ]
        );
    }

    public function clear_categories_for_user($user_id) {
        global $wpdb;
        $wpdb->delete($this->table_name, ['author_id' => $user_id], ['%d']);
    }
}
?>
