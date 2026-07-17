<?php
/**
 * Admin functionality.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Admin
 */
class HWBL_Admin {

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
			'edit.php?post_type=hwbl_lesson',
			__( 'Settings', 'hidden-word-bible-lessons' ),
			__( 'Settings', 'hidden-word-bible-lessons' ),
			'manage_options',
			'hwbl-settings',
			array( 'HWBL_Settings', 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles globally for plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( false === strpos( $hook, 'hwbl_lesson' ) && 'hwbl_lesson_page_hwbl-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hwbl-admin',
			HWBL_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			HWBL_VERSION
		);
	}
}
