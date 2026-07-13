<?php
/**
 * Admin functionality.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Admin
 */
class THW_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Add settings submenu under Lessons CPT menu.
	 */
	public function add_settings_submenu() {
		add_submenu_page(
			'edit.php?post_type=thw_lesson',
			__( 'Settings', 'the-hidden-word' ),
			__( 'Settings', 'the-hidden-word' ),
			'manage_options',
			'thw-settings',
			array( 'THW_Settings', 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles globally for plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( false === strpos( $hook, 'thw_lesson' ) && 'thw_lesson_page_thw-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'thw-admin',
			THW_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			THW_VERSION
		);
	}
}
