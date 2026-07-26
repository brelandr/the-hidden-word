<?php
/**
 * Explain preload job helpers tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * In-memory Local Bible backend for preload tests.
 */
class HWBL_Explain_Preload_Bible_Backend {

	/**
	 * @var array<string, array<string, mixed>>
	 */
	private $translations;

	/**
	 * @var array<string, int>
	 */
	private $chapters;

	/**
	 * @param array<string, array<string, mixed>> $translations Translation rows.
	 * @param array<string, int>                  $chapters     Chapter counts by slug.
	 */
	public function __construct( array $translations, array $chapters = array() ) {
		$this->translations = $translations;
		$this->chapters     = $chapters;
	}

	/**
	 * @param string $slug Slug.
	 * @return bool
	 */
	public function is_installed( $slug ) {
		$row = $this->get_translation( $slug );
		return is_array( $row ) && 'ready' === ( $row['status'] ?? '' ) && (int) ( $row['verse_count'] ?? 0 ) > 0;
	}

	/**
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	public function get_translation( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return $this->translations[ $slug ] ?? null;
	}

	/**
	 * @param string $slug Slug.
	 * @return int
	 */
	public function count_verses( $slug ) {
		$row = $this->get_translation( $slug );
		return is_array( $row ) ? (int) ( $row['verse_count'] ?? 0 ) : 0;
	}

	/**
	 * @param string $slug Slug.
	 * @return int
	 */
	public function count_chapters( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return (int) ( $this->chapters[ $slug ] ?? 0 );
	}
}

/**
 * Class HWBL_Explain_Preload_Test
 */
class HWBL_Explain_Preload_Test extends TestCase {

	/**
	 * Load classes under test.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-local-bible-store.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/thw-premium-functions.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-explain-preload.php';
	}

	/**
	 * Reset options / backend.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options           = array();
		HWBL_Local_Bible_Store::$test_backend = null;
	}

	/**
	 * Sanitize maps "both" and rejects empty Bibles.
	 */
	public function test_sanitize_start_args_scopes_and_requires_bible() {
		HWBL_Local_Bible_Store::$test_backend = new HWBL_Explain_Preload_Bible_Backend(
			array(
				'kjv' => array(
					'status'      => 'ready',
					'verse_count' => 10,
					'label'       => 'KJV',
				),
			)
		);

		$err = THW_Premium_Explain_Preload::sanitize_start_args(
			array(
				'translations' => array(),
				'traditions'   => array( 'general' ),
				'scopes'       => array( 'both' ),
			)
		);
		$this->assertInstanceOf( WP_Error::class, $err );

		$ok = THW_Premium_Explain_Preload::sanitize_start_args(
			array(
				'translations' => array( 'kjv' ),
				'traditions'   => array( 'general', 'nondenom' ),
				'scopes'       => array( 'both' ),
			)
		);
		$this->assertIsArray( $ok );
		$this->assertSame( array( 'kjv' ), $ok['translations'] );
		$this->assertSame( array( 'verse', 'chapter' ), $ok['scopes'] );
		$this->assertContains( 'general', $ok['traditions'] );
		$this->assertContains( 'nondenom', $ok['traditions'] );
	}

	/**
	 * Estimate multiplies verses/chapters by traditions and scopes.
	 */
	public function test_estimate_total_multiplies_scopes_and_traditions() {
		HWBL_Local_Bible_Store::$test_backend = new HWBL_Explain_Preload_Bible_Backend(
			array(
				'kjv' => array(
					'status'      => 'ready',
					'verse_count' => 100,
					'label'       => 'KJV',
				),
			),
			array( 'kjv' => 10 )
		);

		$total = THW_Premium_Explain_Preload::estimate_total( array( 'kjv' ), array( 'general', 'nondenom' ), array( 'verse' ) );
		$this->assertSame( 200, $total );

		$total_both = THW_Premium_Explain_Preload::estimate_total( array( 'kjv' ), array( 'general' ), array( 'verse', 'chapter' ) );
		$this->assertSame( 110, $total_both );
	}

	/**
	 * Cursor advances across scopes then traditions then translations.
	 */
	public function test_advance_to_next_scope_combo() {
		$job                 = THW_Premium_Explain_Preload::default_job();
		$job['translations'] = array( 'kjv', 'bsb' );
		$job['traditions']   = array( 'general', 'nondenom' );
		$job['scopes']       = array( 'verse', 'chapter' );
		$job['cursor']       = array(
			't'           => 0,
			'd'           => 0,
			's'           => 0,
			'book_id'     => 1,
			'chapter'     => 1,
			'verse'       => 1,
			'initialized' => true,
		);

		$job = THW_Premium_Explain_Preload::advance_to_next_scope_combo( $job );
		$this->assertSame( 0, $job['cursor']['t'] );
		$this->assertSame( 0, $job['cursor']['d'] );
		$this->assertSame( 1, $job['cursor']['s'] );
		$this->assertFalse( $job['cursor']['initialized'] );

		$job = THW_Premium_Explain_Preload::advance_to_next_scope_combo( $job );
		$this->assertSame( 0, $job['cursor']['t'] );
		$this->assertSame( 1, $job['cursor']['d'] );
		$this->assertSame( 0, $job['cursor']['s'] );

		$job = THW_Premium_Explain_Preload::advance_to_next_scope_combo( $job );
		$job = THW_Premium_Explain_Preload::advance_to_next_scope_combo( $job );
		$this->assertSame( 1, $job['cursor']['t'] );
		$this->assertSame( 0, $job['cursor']['d'] );
		$this->assertSame( 0, $job['cursor']['s'] );
	}

	/**
	 * Start persists a running job when args are valid.
	 */
	public function test_start_persists_running_job() {
		HWBL_Local_Bible_Store::$test_backend = new HWBL_Explain_Preload_Bible_Backend(
			array(
				'web' => array(
					'status'      => 'ready',
					'verse_count' => 5,
					'label'       => 'WEB',
				),
			)
		);

		$job = THW_Premium_Explain_Preload::start(
			array(
				'translations' => array( 'web' ),
				'traditions'   => array( 'nondenom' ),
				'scopes'       => array( 'verse' ),
			)
		);

		$this->assertIsArray( $job );
		$this->assertSame( 'running', $job['status'] );
		$this->assertSame( array( 'web' ), $job['translations'] );
		$this->assertSame( 5, $job['stats']['total'] );

		$status = THW_Premium_Explain_Preload::status_payload();
		$this->assertSame( 'running', $status['status'] );
		$this->assertSame( 5, $status['stats']['total'] );

		$paused = THW_Premium_Explain_Preload::pause();
		$this->assertSame( 'paused', $paused['status'] );

		THW_Premium_Explain_Preload::clear_job();
		$this->assertSame( 'idle', THW_Premium_Explain_Preload::status_payload()['status'] );
	}

	/**
	 * Force tradition honors requested preset when user picker is off.
	 */
	public function test_force_tradition_resolves_requested_preset() {
		HWBL_Test_Options::$options['thw_ai_allow_user_tradition'] = 0;
		HWBL_Test_Options::$options['thw_ai_explain_rules_preset'] = 'general';
		HWBL_Test_Options::$options['thw_ai_explain_rules']        = thw_premium_default_ai_explain_rules();

		$resolved = thw_premium_resolve_explain_rules_for_request( 'nondenom', '', true );
		$this->assertSame( 'nondenom', $resolved['preset'] );
	}

	/**
	 * Mode helper + Batch request shape.
	 */
	public function test_openai_batch_mode_helpers() {
		$this->assertSame( 'realtime', THW_Premium_Explain_Preload::normalize_mode( 'nope' ) );
		$this->assertSame( 'openai_batch', THW_Premium_Explain_Preload::normalize_mode( 'openai_batch' ) );

		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-ai-client.php';
		$req = THW_Premium_AI_Client::build_openai_batch_request( '0.0.0.1.1.1', 'user prompt', 'system rules' );
		$this->assertSame( '0.0.0.1.1.1', $req['custom_id'] );
		$this->assertSame( 'POST', $req['method'] );
		$this->assertSame( '/v1/chat/completions', $req['url'] );
		$this->assertSame( THW_Premium_AI_Client::OPENAI_CHAT_MODEL, $req['body']['model'] );
		$this->assertSame( 'system', $req['body']['messages'][0]['role'] );
		$this->assertSame( 'user', $req['body']['messages'][1]['role'] );
	}

	/**
	 * Resume can switch a paused realtime job into OpenAI Batch mode without resetting cursor.
	 */
	public function test_resume_switches_to_openai_batch_keeps_cursor() {
		HWBL_Local_Bible_Store::$test_backend = new HWBL_Explain_Preload_Bible_Backend(
			array(
				'kjv' => array(
					'status'      => 'ready',
					'verse_count' => 50,
					'label'       => 'KJV',
				),
			)
		);
		HWBL_Test_Options::$options['thw_openai_api_key'] = 'sk-test-key';

		$job = THW_Premium_Explain_Preload::start(
			array(
				'translations' => array( 'kjv' ),
				'traditions'   => array( 'nondenom' ),
				'scopes'       => array( 'verse' ),
				'mode'         => 'realtime',
			)
		);
		$this->assertSame( 'realtime', $job['mode'] );
		$job['cursor'] = array(
			't'           => 0,
			'd'           => 0,
			's'           => 0,
			'book_id'     => 1,
			'chapter'     => 2,
			'verse'       => 5,
			'initialized' => true,
		);
		$job['stats']['generated'] = 7;
		THW_Premium_Explain_Preload::save_job( $job );
		THW_Premium_Explain_Preload::pause();

		$resumed = THW_Premium_Explain_Preload::resume( array( 'mode' => 'openai_batch' ) );
		$this->assertIsArray( $resumed );
		$this->assertSame( 'running', $resumed['status'] );
		$this->assertSame( 'openai_batch', $resumed['mode'] );
		$this->assertSame( 2, (int) $resumed['cursor']['chapter'] );
		$this->assertSame( 5, (int) $resumed['cursor']['verse'] );
		$this->assertSame( 7, (int) $resumed['stats']['generated'] );

		THW_Premium_Explain_Preload::clear_job();
	}
}
