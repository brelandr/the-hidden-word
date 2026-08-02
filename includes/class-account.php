<?php
/**
 * Companion account deletion / deactivation (App Store 5.1.1(v)).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Account
 */
class HWBL_Account {

	const META_STATUS    = '_hwbl_account_status';
	const META_REQUESTED = '_hwbl_account_deletion_requested_at';
	const STATUS_PENDING = 'pending_deletion';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'wp_authenticate_user', array( __CLASS__, 'block_pending_deletion_login' ), 20, 2 );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/account/delete',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_delete_account' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/account/devices',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_list_devices' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/account/devices/(?P<uuid>[a-f0-9\-]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'rest_revoke_device' ),
				'permission_callback' => $auth,
				'args'                => array(
					'uuid' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * List Application Passwords (connected companion devices/apps).
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_list_devices() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return new WP_Error(
				'hwbl_forbidden',
				__( 'You must be signed in.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return new WP_Error(
				'hwbl_app_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		$items   = WP_Application_Passwords::get_user_application_passwords( $user_id );
		$devices = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$devices[] = array(
					'uuid'      => isset( $item['uuid'] ) ? (string) $item['uuid'] : '',
					'name'      => isset( $item['name'] ) ? (string) $item['name'] : '',
					'created'   => isset( $item['created'] ) ? (int) $item['created'] : 0,
					'last_used' => isset( $item['last_used'] ) ? (int) $item['last_used'] : 0,
				);
			}
		}

		return new WP_REST_Response(
			array(
				'devices' => $devices,
			),
			200
		);
	}

	/**
	 * Revoke one Application Password by UUID.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_revoke_device( $request ) {
		$user_id = get_current_user_id();
		$uuid    = sanitize_text_field( (string) $request['uuid'] );
		if ( $user_id < 1 || ! $uuid ) {
			return new WP_Error(
				'hwbl_bad_request',
				__( 'Missing device id.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return new WP_Error(
				'hwbl_app_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		$result = WP_Application_Passwords::delete_application_password( $user_id, $uuid );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'hwbl_device_missing',
				__( 'That connected device was not found.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'revoked' => true,
				'uuid'    => $uuid,
			),
			200
		);
	}

	/**
	 * Whether a user is pending deletion / deactivated for companion access.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_pending_deletion( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return false;
		}
		return self::STATUS_PENDING === (string) get_user_meta( $user_id, self::META_STATUS, true );
	}

	/**
	 * Block login for accounts that requested deletion.
	 *
	 * @param WP_User|WP_Error $user     User or error.
	 * @param string           $password Password (unused).
	 * @return WP_User|WP_Error
	 */
	public static function block_pending_deletion_login( $user, $password = '' ) {
		unset( $password );
		if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
			return $user;
		}
		if ( self::is_pending_deletion( (int) $user->ID ) ) {
			return new WP_Error(
				'hwbl_account_pending_deletion',
				__( 'This account has a deletion request in progress and can no longer sign in. Contact your church administrator if you need help.', 'hidden-word-bible-lessons' )
			);
		}
		return $user;
	}

	/**
	 * POST delete/deactivate the current user's account for companion compliance.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_delete_account( $request ) {
		$params  = $request->get_json_params();
		$params  = is_array( $params ) ? $params : $request->get_params();
		$confirm = isset( $params['confirm'] ) ? trim( (string) $params['confirm'] ) : '';
		if ( 'DELETE' !== $confirm ) {
			return new WP_Error(
				'hwbl_confirm_required',
				__( 'Send confirm: "DELETE" to start account deletion.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return new WP_Error(
				'hwbl_forbidden',
				__( 'You must be signed in to delete your account.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		$result = self::request_deletion( $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Revoke Application Passwords, mark pending deletion, notify admin.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function request_deletion( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'hwbl_user_missing',
				__( 'User not found.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		$revoked = false;
		if ( class_exists( 'WP_Application_Passwords' ) ) {
			WP_Application_Passwords::delete_all_application_passwords( $user_id );
			$revoked = true;
		}

		update_user_meta( $user_id, self::META_STATUS, self::STATUS_PENDING );
		update_user_meta( $user_id, self::META_REQUESTED, time() );

		$report_id = 0;
		if ( class_exists( 'HWBL_Community_Safety' ) ) {
			$report_id = HWBL_Community_Safety::create_report(
				array(
					'type'            => 'account_deletion',
					'reporter_id'     => $user_id,
					'reported_user_id'=> $user_id,
					'reason'          => 'account_deletion',
					'details'         => sprintf(
						/* translators: 1: username, 2: email */
						__( 'User requested account deletion via Hidden Word companion. Username: %1$s. Email: %2$s.', 'hidden-word-bible-lessons' ),
						$user->user_login,
						$user->user_email
					),
					'question'        => '',
					'answer'          => '',
				)
			);
		}

		self::email_admin_deletion_request( $user, $report_id );

		return array(
			'status'               => self::STATUS_PENDING,
			'revokedAppPasswords'  => $revoked,
			'reportId'             => (int) $report_id,
			'message'              => __( 'Your account is deactivated and a deletion request was sent to your church administrator. Application Passwords for this account were revoked.', 'hidden-word-bible-lessons' ),
		);
	}

	/**
	 * Email site admin about a deletion request.
	 *
	 * @param WP_User $user      User.
	 * @param int     $report_id Report post ID.
	 */
	private static function email_admin_deletion_request( $user, $report_id ) {
		$admin = get_option( 'admin_email' );
		if ( ! $admin || ! is_email( $admin ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Account deletion request', 'hidden-word-bible-lessons' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body = sprintf(
			/* translators: 1: username, 2: email, 3: user id, 4: report id */
			__( "A member requested account deletion from the Hidden Word companion app.\n\nUsername: %1\$s\nEmail: %2\$s\nUser ID: %3\$d\nReport ID: %4\$d\n\nThe account is marked pending_deletion and Application Passwords were revoked. Please complete or dismiss the request in WordPress admin (Safety Reports).", 'hidden-word-bible-lessons' ),
			$user->user_login,
			$user->user_email,
			(int) $user->ID,
			(int) $report_id
		);

		wp_mail( $admin, $subject, $body );
	}
}
