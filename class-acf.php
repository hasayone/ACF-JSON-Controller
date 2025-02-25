<?php
/**
 * ACF JSON Controller
 *
 * Manages ACF JSON synchronization, provides sync notifications,
 * and configures paths for JSON storage in the theme directory.
 *
 * @package    YourTheme
 * @subpackage Controllers
 */

namespace YourTheme\Controller;

/**
 * Class ACF
 *
 * Handles ACF JSON sync functionality and HTML escaping configuration.
 */
class ACF
{

	/**
	 * Constructor
	 *
	 * Sets up filters and actions for ACF JSON functionality.
	 */
	public function __construct()
	{
		// If ACF plugin is not active, exit early
		if ( ! class_exists('acf')) {
			return;
		}

		// Setup JSON save/load paths
		add_filter('acf/settings/save_json', [$this, 'acf_json_save_callback']);
		add_filter('acf/settings/load_json', [$this, 'acf_json_load_callback']);

		// Configure HTML escaping
		$this->setup_html_escaping();

		// Admin notification for available field sync
		add_action('admin_notices', [$this, 'acf_sync_notice']);

		// Remove automatic <p> tags from WYSIWYG editor
		remove_filter('acf_the_content', 'wpautop');
	}

	/**
	 * Setup HTML escaping settings and logging
	 *
	 * @return void
	 */
	private function setup_html_escaping(): void
	{
		// Prevent ACF from escaping HTML in the admin
		add_filter('acf/admin/prevent_escaped_html_notice', '__return_true');

		// Prevent ACF from escaping HTML in the front-end
		add_filter('acf/the_field/escape_html_optin', '__return_true');

		// Add detailed logging for HTML escaping
		add_action('acf/will_remove_unsafe_html', [$this, 'acf_enable_detailed_escape_logging_to_php_error_log'], 10, 4);
		add_action('acf/removed_unsafe_html', [$this, 'acf_enable_detailed_escape_logging_to_php_error_log'], 10, 4);
	}

	/**
	 * Set the directory path for saving ACF JSON files
	 *
	 * @param  string  $path  Default path
	 *
	 * @return string Modified path pointing to theme's acf-json directory
	 */
	public function acf_json_save_callback(string $path): string
	{
		return get_stylesheet_directory() . '/acf-json';
	}

	/**
	 * Set the directory path for loading ACF JSON files
	 *
	 * @param  array  $paths  Default paths
	 *
	 * @return array Modified paths including theme's acf-json directory
	 */
	public function acf_json_load_callback(array $paths): array
	{
		// Remove default path
		unset($paths[0]);

		// Add theme path
		$paths[] = get_stylesheet_directory() . '/acf-json';

		return $paths;
	}

	/**
	 * Enable detailed logging of ACF HTML escaping to PHP error log
	 *
	 * Based on https://github.com/lgladdy/acf-escaping-debug-plugin/
	 *
	 * @param  string  $function      Function being used (the_field, get_field, etc)
	 * @param  string  $selector      Field name/key
	 * @param  array   $field_object  Field object
	 * @param  mixed   $post_id       Post ID
	 *
	 * @return void
	 */
	public function acf_enable_detailed_escape_logging_to_php_error_log($function, $selector, $field_object, $post_id): void
	{
		// Get field value based on function type
		if ($function === 'the_sub_field') {
			$field = get_sub_field_object($selector);
			$value = (is_array($field) && isset($field['value'])) ? $field['value'] : false;
		} else {
			$value = get_field($selector, $post_id);
		}

		// Convert array values to string for logging
		if (is_array($value)) {
			$value = implode(', ', $value);
		}

		// Check if field type supports HTML escaping
		$field_type              = is_array($field_object) && isset($field_object['type']) ? $field_object['type'] : 'text';
		$field_type_escapes_html = acf_field_type_supports($field_type, 'escaping_html');

		// Get escaped value for comparison
		if ($field_type_escapes_html) {
			if ($function === 'the_sub_field') {
				$field     = get_sub_field_object($selector, true, true, true);
				$new_value = (is_array($field) && isset($field['value'])) ? $field['value'] : false;
			} else {
				$new_value = get_field($selector, $post_id, true, true);
			}

			if (is_array($new_value)) {
				$new_value = implode(', ', $new_value);
			}
		} else {
			$new_value = acf_esc_html($value);
		}

		// Ensure post ID is valid
		if (empty($post_id)) {
			$post_id = acf_get_valid_post_id($post_id);
		}

		// Get template information
		if ($function === 'acf_shortcode') {
			$template = get_page_template() . ' (likely not relevant for shortcode)';
		} else {
			$template = get_page_template();
		}

		// Log detailed escaping information
		error_log(
			'***ACF HTML Escaping Debug***' . PHP_EOL .
			'HTML modification detected the value of ' . $selector . ' on post ID ' . $post_id . ' via ' . $function . PHP_EOL .
			'Raw Value: ' . var_export($value, true) . PHP_EOL .
			'Escaped Value: ' . var_export($new_value, true) . PHP_EOL .
			'Template: ' . $template
		);
	}

	/**
	 * Display admin notice when ACF field synchronization is available
	 *
	 * @return void
	 */
	public function acf_sync_notice(): void
	{
		// Check if required ACF functions exist
		if (function_exists('acf_get_local_field_groups') && function_exists('acf_get_field_groups')) {
			// Get count of field groups requiring sync
			$sync_count = $this->get_acf_sync_count();

			// Display notice if sync is available
			if ($sync_count > 0) { ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
						<?php printf(
							__('ACF Pro field synchronization available', 'your-theme-textdomain')
							. ' <code>(%d)</code> <a href="%s">'
							. __('Go to synchronization', 'your-theme-textdomain') . '</a>',
							$sync_count,
							admin_url('edit.php?post_type=acf-field-group&post_status=sync')
						); ?>
                    </p>
                </div>
			<?php }
		}
	}

	/**
	 * Get the count of ACF field groups requiring synchronization
	 *
	 * @return int Number of field groups needing sync
	 */
	private function get_acf_sync_count(): int
	{
		// Create ACF admin list object
		$acf_admin_list            = new \ACF_Admin_Internal_Post_Type_List();
		$acf_admin_list->post_type = 'acf-field-group';
		$acf_admin_list->setup_sync();

		// Return count of items requiring sync
		return count($acf_admin_list->sync);
	}
}