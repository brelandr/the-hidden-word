<?php
/**
 * Admin analytics dashboard.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Analytics
 */
class THW_Premium_Analytics {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_thw_export_analytics_csv', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Add analytics submenu.
	 */
	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Analytics', 'hidden-word-bible-lessons' ),
			__( 'Analytics', 'hidden-word-bible-lessons' ),
			'manage_options',
			'thw-analytics',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Aggregate lesson completion counts.
	 *
	 * @return array<int, int> lesson_id => count
	 */
	public static function get_lesson_completion_counts() {
		$counts = array();
		$users  = get_users( array( 'fields' => 'ID' ) );

		foreach ( $users as $user_id ) {
			$progress = THW_Premium_Progress::get_user_progress( (int) $user_id );
			foreach ( array_keys( $progress ) as $lesson_id ) {
				$lesson_id = (int) $lesson_id;
				if ( ! isset( $counts[ $lesson_id ] ) ) {
					$counts[ $lesson_id ] = 0;
				}
				++$counts[ $lesson_id ];
			}
		}

		arsort( $counts );
		return $counts;
	}

	/**
	 * Count users with memorization activity in the last N days.
	 *
	 * @param int $days Days to look back.
	 * @return int
	 */
	public static function get_active_users_count( $days = 30 ) {
		$cutoff = strtotime( '-' . absint( $days ) . ' days' );
		$active = 0;

		foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
			$last = THW_Premium_Progress::get_last_activity_date( (int) $user_id );
			if ( $last && strtotime( $last ) >= $cutoff ) {
				++$active;
			}
		}

		return $active;
	}

	/**
	 * Cohort completion summary for analytics.
	 *
	 * @return array<int, array{title: string, members: int, completions: int}>
	 */
	public static function get_cohort_summaries() {
		$query = new WP_Query(
			array(
				'post_type'      => 'thw_cohort',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$summaries = array();
		foreach ( $query->posts as $post ) {
			$members = THW_Premium_Cohort::get_member_user_ids( $post->ID );
			$track   = THW_Premium_Cohort::get_cohort_track( $post->ID );
			$done    = 0;

			foreach ( $members as $user_id ) {
				foreach ( $track as $lesson_id ) {
					if ( THW_Premium_Progress::is_memorized( $user_id, (int) $lesson_id ) ) {
						++$done;
					}
				}
			}

			$summaries[ $post->ID ] = array(
				'title'       => $post->post_title,
				'members'     => count( $members ),
				'completions' => $done,
			);
		}

		return $summaries;
	}

	/**
	 * Render analytics page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$cache_key = 'hwbl_analytics_summary_' . wp_date( 'Y-m-d-H' );
		$summary   = get_transient( $cache_key );
		if ( ! is_array( $summary ) ) {
			$summary = array(
				'counts'       => self::get_lesson_completion_counts(),
				'total_users'  => count( get_users( array( 'fields' => 'ID' ) ) ),
				'active_users' => self::get_active_users_count( 30 ),
				'cohorts'      => self::get_cohort_summaries(),
			);
			set_transient( $cache_key, $summary, 5 * MINUTE_IN_SECONDS );
		}

		$counts       = $summary['counts'];
		$total_users  = (int) $summary['total_users'];
		$active_users = (int) $summary['active_users'];
		$cohorts      = $summary['cohorts'];
		$export_url   = wp_nonce_url( admin_url( 'admin-post.php?action=thw_export_analytics_csv' ), 'thw_export_analytics_csv' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Memorization Analytics', 'hidden-word-bible-lessons' ); ?></h1>
			<p><?php
			echo esc_html(
				sprintf(
					/* translators: 1: total users tracked, 2: active users in last 30 days */
					__( '%1$d users tracked. %2$d active in the last 30 days.', 'hidden-word-bible-lessons' ),
					$total_users,
					$active_users
				)
			);
			?></p>
			<p><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'hidden-word-bible-lessons' ); ?></a></p>

			<?php if ( ! empty( $cohorts ) ) : ?>
				<h2><?php esc_html_e( 'Cohort comparison', 'hidden-word-bible-lessons' ); ?></h2>
				<table class="widefat striped" style="margin-bottom: 2em;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Cohort', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Members', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Track completions', 'hidden-word-bible-lessons' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cohorts as $summary ) : ?>
							<tr>
								<td><?php echo esc_html( $summary['title'] ); ?></td>
								<td><?php echo esc_html( (string) $summary['members'] ); ?></td>
								<td><?php echo esc_html( (string) $summary['completions'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Lesson completions', 'hidden-word-bible-lessons' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Lesson', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Completions', 'hidden-word-bible-lessons' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $counts ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No memorization data yet.', 'hidden-word-bible-lessons' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $counts as $lesson_id => $count ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_title( $lesson_id ) ); ?></td>
								<td><?php echo esc_html( (string) $count ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Export analytics CSV.
	 */
	public static function export_csv() {
		check_admin_referer( 'thw_export_analytics_csv' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$counts = self::get_lesson_completion_counts();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="thw-analytics.csv"' );

		echo esc_html( self::to_csv_line( array( 'lesson_id', 'title', 'completions' ) ) );
		foreach ( $counts as $lesson_id => $count ) {
			echo esc_html(
				self::to_csv_line(
					array(
						(string) absint( $lesson_id ),
						(string) get_the_title( $lesson_id ),
						(string) absint( $count ),
					)
				)
			);
		}
		exit;
	}

	/**
	 * Format one CSV row without fopen/fclose (Plugin Check AlternativeFunctions).
	 *
	 * Strips commas/quotes/newlines so the line can be late-escaped with esc_html()
	 * without turning CSV delimiters into HTML entities.
	 *
	 * @param array<int, scalar> $fields Row values.
	 * @return string
	 */
	private static function to_csv_line( $fields ) {
		$cells = array();
		foreach ( $fields as $field ) {
			$cells[] = preg_replace( '/[\r\n,"]+/', ' ', (string) $field );
		}
		return implode( ',', $cells ) . "\n";
	}
}
