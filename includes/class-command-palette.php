<?php
/**
 * Admin Command Palette entries (WordPress 7.0+).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Command_Palette
 */
class HWBL_Command_Palette {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_filter( 'wp_command_palette_commands', array( __CLASS__, 'register_commands' ) );
		// Alternate hook names used while the API stabilizes.
		add_filter( 'command_palette_commands', array( __CLASS__, 'register_commands' ) );
		add_filter( 'wp_admin_command_palette_commands', array( __CLASS__, 'register_commands' ) );
	}

	/**
	 * Append Hidden Word admin commands.
	 *
	 * Emits both Core-style keys (`name`, `label`, `url`) and legacy aliases
	 * (`id`, `title`) so either WP signature works.
	 *
	 * @param array<int, array<string, mixed>> $commands Existing commands.
	 * @return array<int, array<string, mixed>>
	 */
	public static function register_commands( $commands ) {
		if ( ! is_array( $commands ) ) {
			$commands = array();
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $commands;
		}

		$commands[] = self::command(
			'hwbl-add-lesson',
			__( 'Add new Bible lesson', 'hidden-word-bible-lessons' ),
			admin_url( 'post-new.php?post_type=hwbl_lesson' )
		);
		$commands[] = self::command(
			'hwbl-safety-reports',
			__( 'View safety reports', 'hidden-word-bible-lessons' ),
			admin_url( 'edit.php?post_type=hwbl_safety_report' )
		);
		$commands[] = self::command(
			'hwbl-settings',
			__( 'Hidden Word settings', 'hidden-word-bible-lessons' ),
			admin_url( 'edit.php?post_type=hwbl_lesson&page=hwbl-settings' )
		);
		if ( current_user_can( 'manage_options' ) ) {
			$commands[] = self::command(
				'hwbl-local-bibles',
				__( 'Import a local Bible', 'hidden-word-bible-lessons' ),
				admin_url( 'edit.php?post_type=hwbl_lesson&page=hwbl-local-bibles' )
			);
		}

		return $commands;
	}

	/**
	 * Normalize a command entry for evolving WP Command Palette shapes.
	 *
	 * @param string $name  Command id / name.
	 * @param string $label Human label.
	 * @param string $url   Destination URL.
	 * @return array<string, string>
	 */
	private static function command( $name, $label, $url ) {
		return array(
			'name'  => (string) $name,
			'id'    => (string) $name,
			'label' => (string) $label,
			'title' => (string) $label,
			'url'   => (string) $url,
		);
	}
}
