<?php
/**
 * Per-user reading plan progress (user meta).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Progress
 */
class HWBL_Plan_Progress {

	/**
	 * Meta key for a plan.
	 *
	 * @param int $plan_id Plan ID.
	 * @return string
	 */
	public static function meta_key( $plan_id ) {
		return '_hwbl_plan_progress_' . (int) $plan_id;
	}

	/**
	 * Empty progress shape.
	 *
	 * @return array{current_day:int,started_at:string,last_advanced_at:string,completed_days:array<int,int>}
	 */
	public static function empty_progress() {
		return array(
			'current_day'       => 0,
			'started_at'        => '',
			'last_advanced_at'  => '',
			'completed_days'    => array(),
		);
	}

	/**
	 * Get progress for user + plan.
	 *
	 * @param int $user_id User ID.
	 * @param int $plan_id Plan ID.
	 * @return array{current_day:int,started_at:string,last_advanced_at:string,completed_days:array<int,int>}
	 */
	public static function get( $user_id, $plan_id ) {
		$raw = get_user_meta( (int) $user_id, self::meta_key( $plan_id ), true );
		if ( ! is_array( $raw ) ) {
			return self::empty_progress();
		}
		$completed = array();
		if ( ! empty( $raw['completed_days'] ) && is_array( $raw['completed_days'] ) ) {
			foreach ( $raw['completed_days'] as $d ) {
				$completed[] = (int) $d;
			}
		}
		$started = isset( $raw['started_at'] ) ? (string) $raw['started_at'] : '';
		$last    = isset( $raw['last_advanced_at'] ) ? (string) $raw['last_advanced_at'] : '';
		if ( '' === $last && '' !== $started ) {
			$last = $started;
		}
		return array(
			'current_day'      => isset( $raw['current_day'] ) ? (int) $raw['current_day'] : 0,
			'started_at'       => $started,
			'last_advanced_at' => $last,
			'completed_days'   => array_values( array_unique( $completed ) ),
		);
	}

	/**
	 * Persist progress.
	 *
	 * @param int                  $user_id  User ID.
	 * @param int                  $plan_id  Plan ID.
	 * @param array<string, mixed> $progress Progress.
	 */
	public static function save( $user_id, $plan_id, $progress ) {
		$normalized = array(
			'current_day'      => isset( $progress['current_day'] ) ? (int) $progress['current_day'] : 0,
			'started_at'       => isset( $progress['started_at'] ) ? (string) $progress['started_at'] : '',
			'last_advanced_at' => isset( $progress['last_advanced_at'] ) ? (string) $progress['last_advanced_at'] : '',
			'completed_days'   => array(),
		);
		if ( ! empty( $progress['completed_days'] ) && is_array( $progress['completed_days'] ) ) {
			foreach ( $progress['completed_days'] as $d ) {
				$normalized['completed_days'][] = (int) $d;
			}
			$normalized['completed_days'] = array_values( array_unique( $normalized['completed_days'] ) );
		}
		update_user_meta( (int) $user_id, self::meta_key( $plan_id ), $normalized );
	}

	/**
	 * Whether user has an active (started, not finished) plan.
	 *
	 * @param int $user_id User ID.
	 * @param int $plan_id Plan ID.
	 * @return bool
	 */
	public static function is_active( $user_id, $plan_id ) {
		$progress = self::get( $user_id, $plan_id );
		if ( $progress['current_day'] < 1 || '' === $progress['started_at'] ) {
			return false;
		}
		$length = (int) get_post_meta( (int) $plan_id, HWBL_CPT_Plan::META_LENGTH, true );
		if ( $length < 1 && class_exists( 'HWBL_CPT_Plan' ) ) {
			$length = count( HWBL_CPT_Plan::get_days( $plan_id ) );
		}
		if ( $length < 1 ) {
			return false;
		}
		return count( $progress['completed_days'] ) < $length;
	}

	/**
	 * Start a plan at day 1.
	 *
	 * @param int $user_id User ID.
	 * @param int $plan_id Plan ID.
	 * @return array{current_day:int,started_at:string,last_advanced_at:string,completed_days:array<int,int>}|WP_Error
	 */
	public static function start( $user_id, $plan_id ) {
		$plan_id = (int) $plan_id;
		$user_id = (int) $user_id;
		$post    = get_post( $plan_id );
		if ( ! $post || HWBL_CPT_Plan::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'hwbl_plan_not_found', __( 'Plan not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		$days = HWBL_CPT_Plan::get_days( $plan_id );
		if ( ! $days ) {
			return new WP_Error( 'hwbl_plan_empty', __( 'This plan has no days yet.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$existing = self::get( $user_id, $plan_id );
		if ( $existing['current_day'] > 0 && '' !== $existing['started_at'] ) {
			return $existing;
		}
		$now      = gmdate( 'c' );
		$progress = array(
			'current_day'      => 1,
			'started_at'       => $now,
			'last_advanced_at' => $now,
			'completed_days'   => array(),
		);
		self::save( $user_id, $plan_id, $progress );
		return $progress;
	}

	/**
	 * Mark current day complete and advance.
	 *
	 * @param int $user_id User ID.
	 * @param int $plan_id Plan ID.
	 * @return array{current_day:int,started_at:string,last_advanced_at:string,completed_days:array<int,int>,completed:bool}|WP_Error
	 */
	public static function advance( $user_id, $plan_id ) {
		$plan_id  = (int) $plan_id;
		$user_id  = (int) $user_id;
		$progress = self::get( $user_id, $plan_id );
		if ( $progress['current_day'] < 1 || '' === $progress['started_at'] ) {
			return new WP_Error( 'hwbl_plan_not_started', __( 'Start this plan first.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$days   = HWBL_CPT_Plan::get_days( $plan_id );
		$length = count( $days );
		if ( $length < 1 ) {
			return new WP_Error( 'hwbl_plan_empty', __( 'This plan has no days yet.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$current = (int) $progress['current_day'];
		if ( ! in_array( $current, $progress['completed_days'], true ) ) {
			$progress['completed_days'][] = $current;
		}
		$completed = count( $progress['completed_days'] ) >= $length;
		if ( ! $completed && $current < $length ) {
			$progress['current_day'] = $current + 1;
		}
		$progress['last_advanced_at'] = gmdate( 'c' );
		self::save( $user_id, $plan_id, $progress );

		return array_merge(
			$progress,
			array( 'completed' => $completed )
		);
	}

	/**
	 * Active plans for a user (started, not finished), most recently advanced first.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array{plan_id:int,title:string,url:string,progress:array<string,mixed>,today:array<string,mixed>|null}>
	 */
	public static function get_active_for_user( $user_id ) {
		$user_id = (int) $user_id;
		$plans   = get_posts(
			array(
				'post_type'      => HWBL_CPT_Plan::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
			)
		);
		$out = array();
		foreach ( $plans as $plan_id ) {
			$plan_id = (int) $plan_id;
			if ( ! self::is_active( $user_id, $plan_id ) ) {
				continue;
			}
			$progress = self::get( $user_id, $plan_id );
			$today    = HWBL_CPT_Plan::get_day( $plan_id, (int) $progress['current_day'] );
			$out[]    = array(
				'plan_id'  => $plan_id,
				'title'    => get_the_title( $plan_id ),
				'url'      => (string) get_permalink( $plan_id ),
				'progress' => $progress,
				'today'    => $today,
			);
		}

		usort(
			$out,
			static function ( $a, $b ) {
				$ta = (string) ( $a['progress']['last_advanced_at'] ?? '' );
				$tb = (string) ( $b['progress']['last_advanced_at'] ?? '' );
				if ( '' === $ta ) {
					$ta = (string) ( $a['progress']['started_at'] ?? '' );
				}
				if ( '' === $tb ) {
					$tb = (string) ( $b['progress']['started_at'] ?? '' );
				}
				return strcmp( $tb, $ta );
			}
		);

		return $out;
	}
}
