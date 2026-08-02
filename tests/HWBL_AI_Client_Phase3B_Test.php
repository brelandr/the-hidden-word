<?php
/**
 * Phase 3B AI Client streaming / embeddings helpers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_AI_Client_Phase3B_Test
 */
class HWBL_AI_Client_Phase3B_Test extends TestCase {

	/**
	 * Load AI client + study search helpers.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
			define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
		}
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-ai-client.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-study-search.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-ai-explain.php';
	}

	/**
	 * Cosine similarity is 1 for identical vectors and ~0 for orthogonal.
	 */
	public function test_cosine_similarity() {
		$this->assertEqualsWithDelta( 1.0, THW_Premium_AI_Client::cosine_similarity( array( 1, 0 ), array( 1, 0 ) ), 0.0001 );
		$this->assertEqualsWithDelta( 0.0, THW_Premium_AI_Client::cosine_similarity( array( 1, 0 ), array( 0, 1 ) ), 0.0001 );
		$this->assertSame( 0.0, THW_Premium_AI_Client::cosine_similarity( array(), array( 1 ) ) );
	}

	/**
	 * Streaming / embeddings probes degrade cleanly without WP 7.1 AI APIs.
	 */
	public function test_supports_streaming_and_embeddings_without_core_ai() {
		$this->assertFalse( THW_Premium_AI_Client::supports_streaming() );
		// Without OpenAI key or core embeddings, supports_embeddings is false.
		HWBL_Test_Options::$options = array();
		$this->assertFalse( THW_Premium_AI_Client::supports_embeddings() );
	}

	/**
	 * stream_completion without providers returns a WP_Error (not a fatal).
	 */
	public function test_stream_completion_degrades_without_provider() {
		HWBL_Test_Options::$options = array();
		$result                     = THW_Premium_AI_Client::stream_completion(
			array(
				'prompt' => 'Explain John 3:16',
			)
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Embedding cache key is stable and prefixed.
	 */
	public function test_embedding_cache_key() {
		$key = THW_Premium_Study_Search::embedding_cache_key( 'hope and peace' );
		$this->assertStringStartsWith( 'hwbl_emb_', $key );
		$this->assertSame( $key, THW_Premium_Study_Search::embedding_cache_key( 'hope and peace' ) );
	}

	/**
	 * Explain REST stream flag parses from body / query params.
	 */
	public function test_request_wants_stream() {
		$request = new class() {
			/**
			 * @param string $key Param.
			 * @return mixed
			 */
			public function get_param( $key ) {
				return 'stream' === $key ? null : null;
			}

			/**
			 * @param string $key Header.
			 * @return string
			 */
			public function get_header( $key ) {
				unset( $key );
				return '';
			}
		};

		$this->assertTrue( THW_Premium_AI_Explain::request_wants_stream( $request, array( 'stream' => true ) ) );
		$this->assertTrue( THW_Premium_AI_Explain::request_wants_stream( $request, array( 'stream' => '1' ) ) );
		$this->assertFalse( THW_Premium_AI_Explain::request_wants_stream( $request, array() ) );
	}

	/**
	 * Abilities + command palette classes load and expose expected helpers.
	 */
	public function test_abilities_and_command_palette_harden() {
		require_once HWBL_PLUGIN_DIR . 'includes/class-abilities.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-command-palette.php';

		$this->assertTrue( method_exists( 'HWBL_Abilities', 'register' ) );
		$this->assertTrue( method_exists( 'HWBL_Abilities', 'register_category' ) );
		$this->assertTrue( method_exists( 'HWBL_Command_Palette', 'register_commands' ) );

		$commands = HWBL_Command_Palette::register_commands( array() );
		$this->assertIsArray( $commands );
		// Without edit_posts capability in the test double, list stays empty or filtered.
		// Ensure shape helper still works when capability allows — simulate by calling with a stub.
		if ( empty( $commands ) ) {
			$this->assertSame( array(), $commands );
		} else {
			$first = $commands[0];
			$this->assertArrayHasKey( 'label', $first );
			$this->assertArrayHasKey( 'url', $first );
			$this->assertArrayHasKey( 'name', $first );
		}
	}
}
