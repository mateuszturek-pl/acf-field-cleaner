<?php
/*
Plugin Name: ACF Field Cleaner
Plugin URI: https://mateuszturek.pl/
Description: Plugin enabling the clearing of selected ACF fields
Version: 1.3
Author: Mateusz Turek
Author URI: https://mateuszturek.pl/
License: GPLv2 or later
Text Domain: acf-field-cleaner
*/

// Add a new menu item in the admin dashboard
add_action('admin_menu', 'acf_field_cleaner_menu');

function acf_field_cleaner_menu() {
    add_menu_page('ACF Field Cleaner', 'ACF Field Cleaner', 'manage_options', 'acf-field-cleaner', 'acf_field_cleaner_page', 'dashicons-admin-generic', 100);
}

// Admin page content
function acf_field_cleaner_page() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    echo '<div class="wrap"><h1>ACF Field Cleaner</h1>';

    // Fetch ACF fields
    $field_groups = acf_get_field_groups();
    $fields = [];
    foreach ($field_groups as $group) {
        $fields = array_merge($fields, acf_get_fields($group));
    }

    // Form submission logic
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acf_fields'])) {
		$selected_fields = $_POST['acf_fields'];

		foreach ($selected_fields as $field_key) {
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => -1
			);

			$query = new WP_Query($args);
			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$post_id = get_the_ID();

					// Checking if the field is a repeatable field
					$field = get_field_object($field_key, $post_id);
					if ($field && $field['type'] === 'repeater') {
						// Emptying a repeatable field by setting an empty array
						update_field($field_key, array(), $post_id);
					} else {
						// For other types of fields, simply remove the field
						delete_field($field_key, $post_id);
					}
				}
			}
		}
		echo '<div id="message" class="updated notice notice-success is-dismissible"><p>Selected fields have been cleared.</p></div>';
	}

    // Display form
	echo '<form method="post" action="">';

	foreach ($field_groups as $group) {
		$fields = acf_get_fields($group);
		
		if (!empty($fields)) {
			echo '<h3>' . esc_html($group['title']) . '</h3>';
			foreach ($fields as $field) {
				echo '<input type="checkbox" name="acf_fields[]" value="' . esc_attr($field['key']) . '"> ' . esc_html($field['label']) . ' (' . esc_html($field['name']) . ')<br>';
			}
		}
	}

	echo '<input type="submit" class="button button-primary" value="Clear Selected Fields">';
	echo '</form>';
    echo '</div>';
}