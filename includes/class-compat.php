<?php
/**
 * Backward compatibility for thw_/THW_ identifiers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Compat
 */
class HWBL_Compat {

	/**
	 * Boot aliases for shortcodes, blocks, classes, and legacy hooks.
	 */
	public static function init() {
		self::register_class_aliases();
		add_action( 'init', array( __CLASS__, 'register_block_aliases' ), 20 );
		self::register_hook_bridges();
	}

	/**
	 * Alias former free-plugin class names used by older Premium builds.
	 */
	private static function register_class_aliases() {
		$map = array(
			'THW_Plugin'              => 'HWBL_Plugin',
			'THW_Activator'           => 'HWBL_Activator',
			'THW_Deactivator'         => 'HWBL_Deactivator',
			'THW_CPT_Lesson'          => 'HWBL_CPT_Lesson',
			'THW_Lesson_Meta'         => 'HWBL_Lesson_Meta',
			'THW_Settings'            => 'HWBL_Settings',
			'THW_Scheduler'           => 'HWBL_Scheduler',
			'THW_Translation_Service' => 'HWBL_Translation_Service',
			'THW_Translation_Provider'=> 'HWBL_Translation_Provider',
			'THW_Bundled_Provider'    => 'HWBL_Bundled_Provider',
			'THW_Shortcodes'          => 'HWBL_Shortcodes',
			'THW_Blocks'              => 'HWBL_Blocks',
			'THW_Lesson_List'         => 'HWBL_Lesson_List',
			'THW_Lesson_Renderer'     => 'HWBL_Lesson_Renderer',
			'THW_Public'              => 'HWBL_Public',
			'THW_Admin'               => 'HWBL_Admin',
			'THW_Curriculum'          => 'HWBL_Curriculum',
			'THW_Books'               => 'HWBL_Books',
			'THW_Widget_Verse_Of_Week'=> 'HWBL_Widget_Verse_Of_Week',
		);

		foreach ( $map as $legacy => $current ) {
			if ( class_exists( $current ) && ! class_exists( $legacy ) ) {
				class_alias( $current, $legacy );
			}
			if ( interface_exists( $current ) && ! interface_exists( $legacy ) ) {
				class_alias( $current, $legacy );
			}
		}
	}

	/**
	 * Keep legacy Gutenberg block names renderable.
	 */
	public static function register_block_aliases() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$blocks = new HWBL_Blocks();
		register_block_type(
			'thw/lesson',
			array(
				'render_callback' => array( $blocks, 'render_lesson_block' ),
				'attributes'      => array(
					'lessonId'         => array( 'type' => 'string', 'default' => 'auto' ),
					'showMemorization' => array( 'type' => 'boolean', 'default' => true ),
					'showDiscussion'   => array( 'type' => 'boolean', 'default' => true ),
				),
			)
		);
		register_block_type(
			'thw/lesson-list',
			array(
				'render_callback' => array( $blocks, 'render_lesson_list_block' ),
				'attributes'      => array(
					'group'     => array( 'type' => 'string', 'default' => 'book' ),
					'book'      => array( 'type' => 'string', 'default' => '' ),
					'testament' => array( 'type' => 'string', 'default' => '' ),
					'perPage'   => array( 'type' => 'string', 'default' => '50' ),
					'show'      => array( 'type' => 'string', 'default' => 'both' ),
				),
			)
		);
	}

	/**
	 * Bridge legacy thw_* hooks to hwbl_* hooks for older Premium builds.
	 */
	private static function register_hook_bridges() {
		$action_pairs = array(
			'hwbl_register_premium_features' => 'thw_register_premium_features',
			'hwbl_lesson_render_before_tabs' => 'thw_lesson_render_before_tabs',
			'hwbl_lesson_render_panels'      => 'thw_lesson_render_panels',
			'hwbl_lesson_render_after_echo'  => 'thw_lesson_render_after_echo',
		);

		foreach ( $action_pairs as $new => $legacy ) {
			add_action(
				$new,
				static function () use ( $legacy ) {
					$args = func_get_args();
					call_user_func_array( 'do_action', array_merge( array( $legacy ), $args ) );
				},
				10,
				5
			);
		}

		$filter_pairs = array(
			'hwbl_ai_enabled'              => 'thw_ai_enabled',
			'hwbl_schedule_modes'          => 'thw_schedule_modes',
			'hwbl_current_lesson_id'       => 'thw_current_lesson_id',
			'hwbl_lesson_data'             => 'thw_lesson_data',
			'hwbl_lesson_tabs'             => 'thw_lesson_tabs',
			'hwbl_translation_providers'   => 'thw_translation_providers',
			'hwbl_get_verse_text'          => 'thw_get_verse_text',
			'hwbl_supported_translations'  => 'thw_supported_translations',
			'hwbl_render_copyright'        => 'thw_render_copyright',
			'hwbl_seed_batch_size'         => 'thw_seed_batch_size',
			'hwbl_sync_batch_size'         => 'thw_sync_batch_size',
		);

		foreach ( $filter_pairs as $new => $legacy ) {
			add_filter(
				$new,
				static function ( $value ) use ( $legacy ) {
					$args = func_get_args();
					// $legacy is always one of the hardcoded 'thw_*' strings in $filter_pairs above.
					return apply_filters( $legacy, ...$args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				},
				10,
				5
			);
		}
	}
}
