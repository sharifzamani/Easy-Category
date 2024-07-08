<?php
/*
Plugin Name: Easy Category
Plugin URI: https://github.com/sharifzamani/Easy-Category
Description: Assign categories to authors.
Version: 1.0.0
Author: Sharif Zamani
Author URI: https://x.com/sharif_zamani
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'author_category_panel_class.php';

function activation_author_category() {
    $authorCategory = new Author_Category_Panel_Class();
    $authorCategory->create_tables();
}
register_activation_hook(__FILE__, 'activation_author_category');

function deactivation_author_category() {
    $authorCategory = new Author_Category_Panel_Class();
    $authorCategory->delete_tables();
}
register_deactivation_hook(__FILE__, 'deactivation_author_category');

function get_author_categories() {
    $authorCategory = new Author_Category_Panel_Class();
    return $authorCategory->get_categories();
}
add_action('init', 'get_author_categories');

function save_author_category($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $author_id = $post->post_author;
    $category_id = (int) get_post_meta($post_id, 'category_id', true);

    if ($category_id) {
        $authorCategory = new Author_Category_Panel_Class();
        $authorCategory->insert_author_category($author_id, $category_id);
    }
}
add_action('save_post', 'save_author_category', 10, 2);

// Add user profile fields for category selection
function add_author_category_profile_fields($user) {
    $categories = get_categories(['hide_empty' => false]);
    $authorCategory = new Author_Category_Panel_Class();
    $user_categories = $authorCategory->get_categories_for_user($user->ID);
    ?>
    <h3><?php _e("Author Categories", "blank"); ?></h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="author_category"><?php _e("Select Categories"); ?></label>
            </th>
            <td>
                <select name="author_categories[]" id="author_categories" multiple="multiple" style="width: 100%;">
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo in_array($category->term_id, $user_categories) ? 'selected="selected"' : ''; ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_author_category_profile_fields');
add_action('edit_user_profile', 'add_author_category_profile_fields');

function save_author_category_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $authorCategory = new Author_Category_Panel_Class();
    $authorCategory->clear_categories_for_user($user_id);

    if (isset($_POST['author_categories']) && is_array($_POST['author_categories'])) {
        foreach ($_POST['author_categories'] as $category_id) {
            $authorCategory->insert_author_category($user_id, (int)$category_id);
        }
    }
}
add_action('personal_options_update', 'save_author_category_profile_fields');
add_action('edit_user_profile_update', 'save_author_category_profile_fields');

// Filter categories in post editor based on author categories
function restrict_author_categories($terms, $taxonomies, $args) {
    if (!in_array('category', $taxonomies)) {
        return $terms;
    }

    if (!current_user_can('administrator')) {
        $authorCategory = new Author_Category_Panel_Class();
        $user_categories = $authorCategory->get_categories_for_user(get_current_user_id());
        $filtered_terms = [];

        foreach ($terms as $term) {
            if (in_array($term->term_id, $user_categories)) {
                $filtered_terms[] = $term;
            }
        }

        return $filtered_terms;
    }

    return $terms;
}
add_filter('get_terms', 'restrict_author_categories', 10, 3);
?>
