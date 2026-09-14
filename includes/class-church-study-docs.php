<?php
/**
 * Church docs: how to publish shared notes + pin weekly plans.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Church_Study_Docs
 */
class HWBL_Church_Study_Docs {

	/**
	 * Initialize.
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 30 );
		}
	}

	/**
	 * Admin submenu.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Church study docs', 'hidden-word-bible-lessons' ),
			__( 'Church study docs', 'hidden-word-bible-lessons' ),
			'edit_posts',
			'hwbl-church-study-docs',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render docs page.
	 */
	public static function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'hidden-word-bible-lessons' ) );
		}
		$notes_url = admin_url( 'edit.php?post_type=hwbl_lesson&page=hwbl-pastor-notes' );
		$plans_url = admin_url( 'edit.php?post_type=hwbl_plan' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Church study guide', 'hidden-word-bible-lessons' ); ?></h1>
			<div class="card" style="max-width:720px;padding:1rem 1.25rem;">
				<h2><?php esc_html_e( 'Publish shared study notes', 'hidden-word-bible-lessons' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Open Shared study notes and choose book, chapter, and optional verse.', 'hidden-word-bible-lessons' ); ?></li>
					<li><?php esc_html_e( 'Write a short pastoral title and body. Use type Intro for chapter context chips.', 'hidden-word-bible-lessons' ); ?></li>
					<li><?php esc_html_e( 'Optionally set a Series name and Publish on date for Sunday teaching.', 'hidden-word-bible-lessons' ); ?></li>
					<li><?php esc_html_e( 'Check Published so signed-in readers see Church notes in the Bible reader.', 'hidden-word-bible-lessons' ); ?></li>
				</ol>
				<p><a class="button button-primary" href="<?php echo esc_url( $notes_url ); ?>"><?php esc_html_e( 'Open Shared study notes', 'hidden-word-bible-lessons' ); ?></a></p>
			</div>
			<div class="card" style="max-width:720px;padding:1rem 1.25rem;margin-top:1rem;">
				<h2><?php esc_html_e( 'Pin a weekly reading plan', 'hidden-word-bible-lessons' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Create or edit a Reading Plan and set day readings + discussion prompts.', 'hidden-word-bible-lessons' ); ?></li>
					<li><?php esc_html_e( 'Share the plan link with your group; members can submit reflections.', 'hidden-word-bible-lessons' ); ?></li>
					<li><?php esc_html_e( 'Ask members to enable Share with leader so answers appear in the leader inbox.', 'hidden-word-bible-lessons' ); ?></li>
				</ol>
				<p><a class="button" href="<?php echo esc_url( $plans_url ); ?>"><?php esc_html_e( 'Manage reading plans', 'hidden-word-bible-lessons' ); ?></a></p>
			</div>
			<div class="card" style="max-width:720px;padding:1rem 1.25rem;margin-top:1rem;">
				<h2><?php esc_html_e( 'Markdown bulk import', 'hidden-word-bible-lessons' ); ?></h2>
				<p><?php esc_html_e( 'On Shared study notes, paste multiple blocks separated by a line with only ---. Each block may use @ref, @type (study|intro|sermon), @title, @series, @publish_on, and @published.', 'hidden-word-bible-lessons' ); ?></p>
				<p><?php esc_html_e( 'Type Intro overrides chapter context chips (“From your church”). Engagement counts show how often signed-in readers open church notes—no identities are stored.', 'hidden-word-bible-lessons' ); ?></p>
			</div>
		</div>
		<?php
	}
}
