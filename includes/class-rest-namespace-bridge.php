<?php
/**
 * Deprecated thw/v1 REST aliases pointing at hwbl/v1 handlers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_REST_Namespace_Bridge
 */
class HWBL_REST_Namespace_Bridge {

	/**
	 * @var array<int, array{deprecated:string,primary:string,route:string,args:array<string,mixed>}>
	 */
	private static $aliases = array();

	/**
	 * Initialize bridge on rest_api_init.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_aliases' ), 20 );
	}

	/**
	 * Queue a deprecated alias route.
	 *
	 * @param string               $deprecated_ns Deprecated namespace (e.g. thw/v1).
	 * @param string               $primary_ns    Primary namespace (e.g. hwbl/v1).
	 * @param string               $route         Route path.
	 * @param array<string, mixed> $args          register_rest_route args.
	 */
	public static function register_alias( $deprecated_ns, $primary_ns, $route, $args ) {
		self::$aliases[] = array(
			'deprecated' => (string) $deprecated_ns,
			'primary'    => (string) $primary_ns,
			'route'      => (string) $route,
			'args'       => $args,
		);
	}

	/**
	 * Register all queued alias routes.
	 */
	public static function register_aliases() {
		foreach ( self::$aliases as $alias ) {
			$args = $alias['args'];
			if ( ! isset( $args['args'] ) || ! is_array( $args['args'] ) ) {
				$args['args'] = array();
			}
			$args['args']['_deprecated_namespace'] = array(
				'type'    => 'string',
				'default' => $alias['deprecated'],
			);

			register_rest_route( $alias['deprecated'], $alias['route'], $args );
		}

		self::register_core_aliases();
	}

	/**
	 * Built-in aliases for routes moved to hwbl/v1.
	 */
	private static function register_core_aliases() {
		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain' ) ) {
			return;
		}

		// Primary hwbl/v1 route is registered by Premium; thw/v1 alias remains for one release.
	}
}
