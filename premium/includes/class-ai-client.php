<?php
/**
 * WordPress 7.0 AI Client wrapper with legacy BYOK fallback (WP 6.x only).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_AI_Client
 */
class THW_Premium_AI_Client {

	/** Default Chat Completions model for OpenAI BYOK + Batch API. */
	const OPENAI_CHAT_MODEL = 'gpt-4o-mini';

	/**
	 * Whether WordPress core exposes the AI Client (7.0+).
	 *
	 * @return bool
	 */
	public static function uses_core_ai() {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Whether Premium BYOK OpenAI/Claude keys are stored.
	 *
	 * Also true when Settings → Connectors has a usable OpenAI/Anthropic key we can
	 * call directly (needed when the WP AI Client path is blocked, e.g. Connector Approvals).
	 *
	 * @return bool
	 */
	public static function has_byok_keys() {
		return '' !== self::resolve_openai_api_key() || '' !== self::resolve_anthropic_api_key();
	}

	/**
	 * Resolve an OpenAI API key from Premium settings or Settings → Connectors.
	 *
	 * @return string
	 */
	public static function resolve_openai_api_key() {
		$key = trim( (string) get_option( 'thw_openai_api_key', '' ) );
		if ( '' !== $key ) {
			return $key;
		}

		$key = self::resolve_connector_api_key( array( 'openai' ) );
		if ( '' !== $key ) {
			return $key;
		}

		if ( defined( 'OPENAI_API_KEY' ) ) {
			$const = constant( 'OPENAI_API_KEY' );
			if ( is_string( $const ) && '' !== trim( $const ) ) {
				return trim( $const );
			}
		}

		$env = getenv( 'OPENAI_API_KEY' );
		return ( is_string( $env ) && '' !== trim( $env ) ) ? trim( $env ) : '';
	}

	/**
	 * Resolve an Anthropic/Claude API key from Premium settings or Settings → Connectors.
	 *
	 * @return string
	 */
	public static function resolve_anthropic_api_key() {
		$key = trim( (string) get_option( 'thw_claude_api_key', '' ) );
		if ( '' !== $key ) {
			return $key;
		}

		$key = self::resolve_connector_api_key( array( 'anthropic', 'claude' ) );
		if ( '' !== $key ) {
			return $key;
		}

		if ( defined( 'ANTHROPIC_API_KEY' ) ) {
			$const = constant( 'ANTHROPIC_API_KEY' );
			if ( is_string( $const ) && '' !== trim( $const ) ) {
				return trim( $const );
			}
		}

		$env = getenv( 'ANTHROPIC_API_KEY' );
		return ( is_string( $env ) && '' !== trim( $env ) ) ? trim( $env ) : '';
	}

	/**
	 * Read an AI provider API key from the WordPress Connectors registry/options.
	 *
	 * @param string[] $provider_ids Provider IDs to match (e.g. openai, anthropic).
	 * @return string
	 */
	private static function resolve_connector_api_key( $provider_ids ) {
		$provider_ids = array_map( 'strtolower', array_map( 'strval', (array) $provider_ids ) );

		if ( function_exists( 'wp_get_connectors' ) ) {
			// call_user_func avoids Plugin Check wp_function_not_compatible_with_requires_wp (WP 7.0 API on 6.2 min).
			$connectors = call_user_func( 'wp_get_connectors' );
			if ( is_array( $connectors ) ) {
				foreach ( $connectors as $connector_id => $connector ) {
					if ( ! is_array( $connector ) ) {
						continue;
					}
					$type = isset( $connector['type'] ) ? (string) $connector['type'] : '';
					if ( 'ai_provider' !== $type && 'ai' !== $type ) {
						continue;
					}
					$id = strtolower( (string) $connector_id );
					if ( ! in_array( $id, $provider_ids, true ) ) {
						continue;
					}
					$auth = isset( $connector['authentication'] ) && is_array( $connector['authentication'] )
						? $connector['authentication']
						: array();
					$setting = isset( $auth['setting_name'] ) ? (string) $auth['setting_name'] : '';
					if ( '' === $setting ) {
						continue;
					}
					$key = trim( (string) get_option( $setting, '' ) );
					if ( '' !== $key ) {
						return $key;
					}
				}
			}
		}

		// Common Connectors option names when wp_get_connectors() is unavailable.
		foreach ( $provider_ids as $id ) {
			foreach ( array(
				'connectors_ai_' . $id . '_api_key',
				'connectors_' . $id . '_' . $id . '_api_key',
			) as $option ) {
				$key = trim( (string) get_option( $option, '' ) );
				if ( '' !== $key ) {
					return $key;
				}
			}
		}

		return '';
	}

	/**
	 * Admin URL for the site-wide AI Connectors settings screen.
	 *
	 * @return string
	 */
	public static function get_connectors_settings_url() {
		if ( file_exists( ABSPATH . 'wp-admin/options-connectors.php' ) ) {
			return admin_url( 'options-connectors.php' );
		}

		return admin_url( 'options-general.php?page=connectors-wp-admin' );
	}

	/**
	 * Whether AI lesson drafting is available on this site.
	 *
	 * Prefer WordPress Settings → Connectors (WP 7.0+). Fall back to BYOK keys
	 * when the AI Client is absent or no connector can generate text yet.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		if ( self::core_supports_text_generation() ) {
			return true;
		}

		return self::has_byok_keys();
	}

	/**
	 * Whether Premium should show BYOK key fields.
	 *
	 * Hide BYOK when Connectors can already generate text. Otherwise show keys
	 * (WordPress 6.x, or WP 7.0 before a connector is Connected).
	 *
	 * @return bool
	 */
	public static function uses_byok_fallback() {
		return ! self::core_supports_text_generation();
	}

	/**
	 * Provider IDs that match Connectors UI “Connected” (registered + configured).
	 *
	 * @return string[]
	 */
	public static function get_connected_ai_provider_ids() {
		$ids = array();

		if ( class_exists( '\WordPress\AiClient\AiClient' ) ) {
			try {
				$registry = \WordPress\AiClient\AiClient::defaultRegistry();
				if ( is_object( $registry ) && method_exists( $registry, 'getRegisteredProviderIds' ) ) {
					foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {
						$provider_id = (string) $provider_id;
						if ( '' === $provider_id ) {
							continue;
						}
						$configured = false;
						if ( method_exists( '\WordPress\AiClient\AiClient', 'isConfigured' ) ) {
							$configured = (bool) \WordPress\AiClient\AiClient::isConfigured( $provider_id );
						} elseif ( method_exists( $registry, 'isProviderConfigured' ) ) {
							$configured = (bool) $registry->isProviderConfigured( $provider_id );
						}
						if ( $configured ) {
							$ids[] = $provider_id;
						}
					}
				}
			} catch ( Exception $e ) {
				$ids = array();
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Generate plain text from a prompt.
	 *
	 * Uses WordPress AI Connectors when available; otherwise BYOK keys.
	 *
	 * @param string $prompt             Prompt text.
	 * @param string $system_instruction Optional system instruction (e.g. resolved
	 *                                   tradition/explain rules). Sent as a real
	 *                                   system-role instruction, not concatenated
	 *                                   into the user prompt, so it carries more
	 *                                   weight with the model.
	 * @return string|WP_Error
	 */
	public static function generate_text( $prompt, $system_instruction = '' ) {
		// Prefer the WordPress AI Client when the support probe passes.
		if ( self::core_supports_text_generation() ) {
			$result = self::generate_text_via_core( $prompt, $system_instruction );
			if ( ! is_wp_error( $result ) || 'hwbl_no_ai_connector' !== $result->get_error_code() ) {
				return $result;
			}
		} elseif ( self::core_ai_environment_enabled() && ! empty( self::get_connected_ai_provider_ids() ) ) {
			// Connected but probe failed (common with Connector Approvals). Try once,
			// then fall through to direct Connectors-key HTTP calls.
			$result = self::generate_text_via_core( $prompt, $system_instruction, true );
			if ( ! is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( self::has_byok_keys() ) {
			return self::generate_text_via_byok( $prompt, $system_instruction );
		}

		if ( self::uses_core_ai() ) {
			return self::core_not_configured_error();
		}

		return self::generate_text_via_byok( $prompt, $system_instruction );
	}

	/**
	 * Build a WordPress AI Client prompt using Connectors auto-discovery.
	 *
	 * Prefer auto-discovery (no provider/temperature). When a Connected provider
	 * ID is supplied, pin to that provider — some sites only pass support checks
	 * after using_provider() even though Settings → Connectors shows Connected.
	 *
	 * @param string $prompt             Prompt text.
	 * @param string $system_instruction Optional system instruction.
	 * @param string $provider_id        Optional Connected provider ID (e.g. openai).
	 * @return object|null Prompt builder from wp_ai_client_prompt().
	 */
	private static function core_prompt_builder( $prompt, $system_instruction = '', $provider_id = '' ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return null;
		}

		// call_user_func avoids Plugin Check wp_function_not_compatible_with_requires_wp (WP 7.0 API on 6.2 min).
		$builder = call_user_func( 'wp_ai_client_prompt', $prompt );
		if ( ! is_object( $builder ) ) {
			return null;
		}

		// Keep registry IDs intact (do not sanitize_key — dots/casing can matter).
		$provider_id = trim( (string) $provider_id );
		if ( '' !== $provider_id && method_exists( $builder, 'using_provider' ) ) {
			$builder = $builder->using_provider( $provider_id );
		}

		if ( '' !== trim( (string) $system_instruction ) && method_exists( $builder, 'using_system_instruction' ) ) {
			$builder = $builder->using_system_instruction( $system_instruction );
		}

		return $builder;
	}

	/**
	 * Candidate provider IDs for support/generation attempts (auto-discover first).
	 *
	 * @return string[] Empty string first (auto), then Connected provider IDs.
	 */
	private static function core_provider_attempt_ids() {
		$ids = array( '' );
		foreach ( self::get_connected_ai_provider_ids() as $provider_id ) {
			$provider_id = trim( (string) $provider_id );
			if ( '' !== $provider_id ) {
				$ids[] = $provider_id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Polyfill for array_is_list() (PHP 8.1 / WP 6.5+) so Requires WP 6.2 stays valid.
	 *
	 * @param array<mixed> $array Array to test.
	 * @return bool
	 */
	private static function is_list_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		if ( array() === $array ) {
			return true;
		}
		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	/**
	 * Generate a JSON string from a prompt with an optional JSON schema (WP 7.0+).
	 *
	 * @param string               $prompt             Prompt text.
	 * @param array<string, mixed> $schema             Optional JSON schema for structured output.
	 * @param string               $system_instruction Optional system instruction.
	 * @return string|WP_Error JSON string.
	 */
	public static function generate_json( $prompt, $schema = array(), $system_instruction = '' ) {
		if ( self::uses_core_ai() && self::core_supports_text_generation() && ! empty( $schema ) ) {
			$wrapped_array_root = isset( $schema['type'] ) && 'array' === $schema['type'];
			$schema             = self::normalize_json_schema_for_providers( $schema );
			$builder            = self::core_prompt_builder( $prompt, $system_instruction );

			if ( is_object( $builder ) && method_exists( $builder, 'as_json_response' ) ) {
				$builder = $builder->as_json_response( $schema );
			} else {
				$builder = null;
			}

			if ( is_object( $builder ) && method_exists( $builder, 'is_supported_for_text_generation' ) && $builder->is_supported_for_text_generation() ) {
				$json = $builder->generate_text();
				if ( is_wp_error( $json ) ) {
					return self::maybe_wrap_connector_approval_error( $json );
				}

				$trimmed = trim( (string) $json );
				if ( $wrapped_array_root ) {
					$trimmed = self::unwrap_json_array_root( $trimmed );
				}

				return $trimmed;
			}
		}

		$json_prompt = $prompt . "\n\nRespond with valid JSON only. No markdown fences or commentary outside JSON.";
		return self::generate_text( $json_prompt, $system_instruction );
	}

	/**
	 * Second-pass check: does a generated response conflict with the rules it
	 * was supposed to follow? Uses its own short, low-temperature AI call with
	 * a strict-but-conservative reviewer instruction, so it only flags clear,
	 * specific conflicts rather than stylistic preferences.
	 *
	 * Fails open: if the check itself errors (e.g. provider hiccup), the
	 * original response is treated as compliant rather than blocking the user
	 * on an unrelated infrastructure failure. This is a backstop against
	 * clear rule violations, not a hard guarantee — pair it with an on-screen
	 * disclaimer.
	 *
	 * @param string $rules         Resolved rules text the response should follow.
	 * @param string $response_text The generated response to check (plain text; strip HTML first if needed).
	 * @return array{compliant:bool, reason:string}
	 */
	public static function check_compliance( $rules, $response_text ) {
		$rules         = trim( (string) $rules );
		$response_text = trim( (string) $response_text );

		if ( '' === $rules || '' === $response_text ) {
			return array(
				'compliant' => true,
				'reason'    => '',
			);
		}

		$prompt = "Rules the response must follow:\n{$rules}\n\n"
			. "Response to review:\n{$response_text}\n\n"
			. 'Does the response above clearly conflict with, contradict, or violate any of the Rules? '
			. 'Only flag a specific, substantive conflict — not tone, style, or omissions. '
			. 'Respond as JSON with keys "compliant" (boolean) and "reason" (short string, empty when compliant).';

		$system = 'You are a strict but conservative compliance reviewer for a church website. '
			. 'Flag only clear, specific rule violations. When in doubt, mark the response compliant.';

		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'compliant' => array( 'type' => 'boolean' ),
				'reason'    => array( 'type' => 'string' ),
			),
			'required'             => array( 'compliant', 'reason' ),
			'additionalProperties' => false,
		);

		$json = self::generate_json( $prompt, $schema, $system );
		if ( is_wp_error( $json ) ) {
			return array(
				'compliant' => true,
				'reason'    => '',
			);
		}

		$decoded = json_decode( (string) $json, true );
		if ( ! is_array( $decoded ) || ! array_key_exists( 'compliant', $decoded ) ) {
			return array(
				'compliant' => true,
				'reason'    => '',
			);
		}

		return array(
			'compliant' => (bool) $decoded['compliant'],
			'reason'    => isset( $decoded['reason'] ) ? sanitize_text_field( (string) $decoded['reason'] ) : '',
		);
	}

	/**
	 * If callers passed an array-root schema, unwrap { "items": [...] } back to a JSON array string.
	 *
	 * @param string $json JSON string from the provider.
	 * @return string
	 */
	private static function unwrap_json_array_root( $json ) {
		$decoded = json_decode( (string) $json, true );
		if ( ! is_array( $decoded ) ) {
			return (string) $json;
		}
		if ( isset( $decoded['items'] ) && is_array( $decoded['items'] ) ) {
			$encoded = wp_json_encode( $decoded['items'] );
			return is_string( $encoded ) ? $encoded : (string) $json;
		}
		return (string) $json;
	}

	/**
	 * Normalize JSON schemas for strict providers (e.g. OpenAI response_format).
	 *
	 * OpenAI requires root type "object". Array roots are wrapped as
	 * { "items": <array-schema> }. Object nodes must set additionalProperties false.
	 *
	 * @param array<string, mixed> $schema  JSON schema.
	 * @param bool                 $is_root Whether this is the top-level schema (array wrap only at root).
	 * @return array<string, mixed>
	 */
	public static function normalize_json_schema_for_providers( array $schema, $is_root = true ) {
		if ( $is_root && isset( $schema['type'] ) && 'array' === $schema['type'] ) {
			$schema = array(
				'type'                 => 'object',
				'properties'           => array(
					'items' => $schema,
				),
				'required'             => array( 'items' ),
				'additionalProperties' => false,
			);
		}

		if ( isset( $schema['type'] ) && 'object' === $schema['type'] ) {
			if ( ! array_key_exists( 'additionalProperties', $schema ) ) {
				$schema['additionalProperties'] = false;
			}
			if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
				foreach ( $schema['properties'] as $key => $property ) {
					if ( is_array( $property ) ) {
						$schema['properties'][ $key ] = self::normalize_json_schema_for_providers( $property, false );
					}
				}
			}
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			// Tuple-style schemas use a list of schemas; object-style uses a single schema map.
			$is_list = self::is_list_array( $schema['items'] );

			if ( $is_list ) {
				foreach ( $schema['items'] as $index => $item ) {
					if ( is_array( $item ) ) {
						$schema['items'][ $index ] = self::normalize_json_schema_for_providers( $item, false );
					}
				}
			} else {
				$schema['items'] = self::normalize_json_schema_for_providers( $schema['items'], false );
			}
		}

		foreach ( array( 'anyOf', 'oneOf', 'allOf' ) as $combiner ) {
			if ( empty( $schema[ $combiner ] ) || ! is_array( $schema[ $combiner ] ) ) {
				continue;
			}
			foreach ( $schema[ $combiner ] as $index => $branch ) {
				if ( is_array( $branch ) ) {
					$schema[ $combiner ][ $index ] = self::normalize_json_schema_for_providers( $branch, false );
				}
			}
		}

		return $schema;
	}

	/**
	 * Generate a JSON array of strings from a prompt.
	 *
	 * @param string $prompt             Prompt text.
	 * @param string $system_instruction Optional system instruction.
	 * @return string|WP_Error JSON string.
	 */
	public static function generate_json_string_array( $prompt, $system_instruction = '' ) {
		$schema = array(
			'type'  => 'array',
			'items' => array(
				'type' => 'string',
			),
		);

		return self::generate_json( $prompt, $schema, $system_instruction );
	}

	/**
	 * Whether WordPress reports AI as enabled in this environment (WP_AI_SUPPORT).
	 *
	 * @return bool
	 */
	public static function core_ai_environment_enabled() {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return false;
		}

		// call_user_func: WP 7.0 helpers must not be direct calls when Requires at least is 6.2.
		if ( function_exists( 'wp_supports_ai' ) && ! call_user_func( 'wp_supports_ai' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether WordPress AI Connectors can generate text (official WP 7.0 check).
	 *
	 * Tries auto-discovery first, then each Connected provider via using_provider().
	 *
	 * @return bool
	 */
	public static function core_supports_text_generation() {
		if ( ! self::core_ai_environment_enabled() ) {
			return false;
		}

		foreach ( self::core_provider_attempt_ids() as $provider_id ) {
			$builder = self::core_prompt_builder( 'Availability check', '', $provider_id );
			if ( ! is_object( $builder ) || ! method_exists( $builder, 'is_supported_for_text_generation' ) ) {
				continue;
			}
			if ( $builder->is_supported_for_text_generation() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate text through WordPress 7.0 AI Client / Connectors.
	 *
	 * @param string $prompt             Prompt text.
	 * @param string $system_instruction Optional system instruction.
	 * @param bool   $force_connected    When true, attempt Connected providers even if
	 *                                   is_supported_for_text_generation() is false.
	 * @return string|WP_Error
	 */
	private static function generate_text_via_core( $prompt, $system_instruction = '', $force_connected = false ) {
		$last_error = null;

		foreach ( self::core_provider_attempt_ids() as $provider_id ) {
			$builder = self::core_prompt_builder( $prompt, $system_instruction, $provider_id );
			if ( ! is_object( $builder ) || ! method_exists( $builder, 'generate_text' ) ) {
				continue;
			}

			$supported = ! method_exists( $builder, 'is_supported_for_text_generation' )
				|| (bool) $builder->is_supported_for_text_generation();

			if ( ! $supported ) {
				// Skip unsupported auto-discovery; optionally force Connected providers.
				if ( '' === $provider_id || ! $force_connected ) {
					continue;
				}
			}

			$text = $builder->generate_text();
			if ( is_wp_error( $text ) ) {
				$last_error = self::maybe_wrap_connector_approval_error( $text );
				continue;
			}

			return trim( (string) $text );
		}

		if ( $last_error instanceof WP_Error ) {
			return $last_error;
		}

		return self::core_not_configured_error();
	}

	/**
	 * Surface Connector Approvals blocks with an actionable message.
	 *
	 * @param WP_Error $error Upstream error.
	 * @return WP_Error
	 */
	private static function maybe_wrap_connector_approval_error( $error ) {
		$code = $error->get_error_code();
		$msg  = $error->get_error_message();

		if ( 'wpai_connector_not_approved' === $code || false !== stripos( $msg, 'not approved' ) ) {
			return new WP_Error(
				'wpai_connector_not_approved',
				__( 'An AI connector blocked this request. If you use the official AI plugin’s Connector Approvals experiment, open Tools → Connector Approvals and approve The Hidden Word Premium for your Connected provider (e.g. OpenAI).', 'hidden-word-bible-lessons' ),
				$error->get_error_data()
			);
		}

		return $error;
	}

	/**
	 * Error when no WordPress AI connector is configured.
	 *
	 * @return WP_Error
	 */
	private static function core_not_configured_error() {
		return new WP_Error(
			'hwbl_no_ai_connector',
			sprintf(
				/* translators: %s: Settings > Connectors admin URL */
				__( 'No AI provider can generate text yet. Open Settings → Connectors, Connect OpenAI (or another provider) with a valid API key, then try again: %s', 'hidden-word-bible-lessons' ),
				self::get_connectors_settings_url()
			)
		);
	}

	/**
	 * Generate text using legacy BYOK OpenAI / Claude keys (WordPress 6.x).
	 *
	 * @param string $prompt             Prompt text.
	 * @param string $system_instruction Optional system instruction.
	 * @return string|WP_Error
	 */
	private static function generate_text_via_byok( $prompt, $system_instruction = '' ) {
		$openai_key = self::resolve_openai_api_key();
		$claude_key = self::resolve_anthropic_api_key();

		if ( $openai_key ) {
			return self::call_openai( $openai_key, $prompt, $system_instruction );
		}

		if ( $claude_key ) {
			return self::call_claude( $claude_key, $prompt, $system_instruction );
		}

		return new WP_Error(
			'hwbl_no_key',
			__( 'No AI provider configured. On WordPress 7.0+, use Settings → Connectors. On older sites, add an OpenAI or Claude API key in Premium settings.', 'hidden-word-bible-lessons' )
		);
	}

	/**
	 * Call OpenAI API.
	 *
	 * @param string $api_key            API key.
	 * @param string $prompt             Prompt.
	 * @param string $system_instruction Optional system instruction, sent as a
	 *                                   real 'system' role message ahead of the
	 *                                   user prompt (OpenAI Chat Completions
	 *                                   weighs system messages more heavily than
	 *                                   instructions embedded in user content).
	 * @return string|WP_Error
	 */
	private static function call_openai( $api_key, $prompt, $system_instruction = '' ) {
		$messages = array();
		if ( '' !== trim( (string) $system_instruction ) ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => $system_instruction,
			);
		}
		$messages[] = array( 'role' => 'user', 'content' => $prompt );

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => self::OPENAI_CHAT_MODEL,
						'temperature' => 0.2,
						'messages'    => $messages,
					)
				),
			)
		);

		$http_error = HWBL_Http_Utils::response_error( $response, 'hwbl_api_error' );
		if ( $http_error ) {
			return $http_error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'hwbl_api_error', __( 'OpenAI returned an empty response.', 'hidden-word-bible-lessons' ) );
		}

		// AI prose may include newlines/HTML; sanitize at output (wp_kses_post), not here.
		return trim( (string) $body['choices'][0]['message']['content'] );
	}

	/**
	 * Build Chat Completions messages array (system + user).
	 *
	 * @param string $prompt             User prompt.
	 * @param string $system_instruction Optional system instruction.
	 * @return array<int, array{role:string,content:string}>
	 */
	public static function build_openai_chat_messages( $prompt, $system_instruction = '' ) {
		$messages = array();
		if ( '' !== trim( (string) $system_instruction ) ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => (string) $system_instruction,
			);
		}
		$messages[] = array(
			'role'    => 'user',
			'content' => (string) $prompt,
		);
		return $messages;
	}

	/**
	 * Build one OpenAI Batch JSONL request object for chat completions.
	 *
	 * @param string               $custom_id          Unique id (max 64 chars).
	 * @param string               $prompt             User prompt.
	 * @param string               $system_instruction Optional system instruction.
	 * @param array<string, mixed> $body_extra         Extra body fields.
	 * @return array<string, mixed>
	 */
	public static function build_openai_batch_request( $custom_id, $prompt, $system_instruction = '', array $body_extra = array() ) {
		$body = array_merge(
			array(
				'model'       => self::OPENAI_CHAT_MODEL,
				'temperature' => 0.2,
				'messages'    => self::build_openai_chat_messages( $prompt, $system_instruction ),
			),
			$body_extra
		);

		return array(
			'custom_id' => substr( sanitize_text_field( (string) $custom_id ), 0, 64 ),
			'method'    => 'POST',
			'url'       => '/v1/chat/completions',
			'body'      => $body,
		);
	}

	/**
	 * Upload a local JSONL file for the OpenAI Batch API.
	 *
	 * @param string $file_path Absolute path to .jsonl file.
	 * @return array<string, mixed>|WP_Error File object (includes id).
	 */
	public static function openai_upload_batch_file( $file_path ) {
		$api_key = self::resolve_openai_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'hwbl_no_openai_key', __( 'An OpenAI API key is required for Batch API preload.', 'hidden-word-bible-lessons' ) );
		}
		$file_path = (string) $file_path;
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			return new WP_Error( 'hwbl_batch_file_missing', __( 'Batch input file is missing or unreadable.', 'hidden-word-bible-lessons' ) );
		}

		$contents = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents || '' === $contents ) {
			return new WP_Error( 'hwbl_batch_file_empty', __( 'Batch input file is empty.', 'hidden-word-bible-lessons' ) );
		}

		$boundary = '----hwbl' . wp_generate_password( 16, false, false );
		$filename = basename( $file_path );
		$body     = '';
		$body    .= '--' . $boundary . "\r\n";
		$body    .= 'Content-Disposition: form-data; name="purpose"' . "\r\n\r\n";
		$body    .= "batch\r\n";
		$body    .= '--' . $boundary . "\r\n";
		$body    .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . "\r\n";
		$body    .= "Content-Type: application/jsonl\r\n\r\n";
		$body    .= $contents . "\r\n";
		$body    .= '--' . $boundary . '--' . "\r\n";

		$response = wp_remote_post(
			'https://api.openai.com/v1/files',
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);

		return self::openai_decode_response( $response, 'hwbl_batch_upload_failed' );
	}

	/**
	 * Create an OpenAI batch job.
	 *
	 * @param string $input_file_id Uploaded file id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function openai_create_batch( $input_file_id ) {
		$api_key = self::resolve_openai_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'hwbl_no_openai_key', __( 'An OpenAI API key is required for Batch API preload.', 'hidden-word-bible-lessons' ) );
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/batches',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'input_file_id'     => (string) $input_file_id,
						'endpoint'          => '/v1/chat/completions',
						'completion_window' => '24h',
					)
				),
			)
		);

		return self::openai_decode_response( $response, 'hwbl_batch_create_failed' );
	}

	/**
	 * Retrieve an OpenAI batch job.
	 *
	 * @param string $batch_id Batch id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function openai_get_batch( $batch_id ) {
		$api_key = self::resolve_openai_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'hwbl_no_openai_key', __( 'An OpenAI API key is required for Batch API preload.', 'hidden-word-bible-lessons' ) );
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/batches/' . rawurlencode( (string) $batch_id ),
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		return self::openai_decode_response( $response, 'hwbl_batch_get_failed' );
	}

	/**
	 * Cancel an OpenAI batch job.
	 *
	 * @param string $batch_id Batch id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function openai_cancel_batch( $batch_id ) {
		$api_key = self::resolve_openai_api_key();
		if ( '' === $api_key || '' === (string) $batch_id ) {
			return new WP_Error( 'hwbl_batch_cancel_skipped', 'missing_key_or_id' );
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/batches/' . rawurlencode( (string) $batch_id ) . '/cancel',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => '{}',
			)
		);

		return self::openai_decode_response( $response, 'hwbl_batch_cancel_failed' );
	}

	/**
	 * Download an OpenAI file's content to a local path.
	 *
	 * @param string $file_id   OpenAI file id.
	 * @param string $dest_path Local destination path.
	 * @return true|WP_Error
	 */
	public static function openai_download_file( $file_id, $dest_path ) {
		$api_key = self::resolve_openai_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'hwbl_no_openai_key', __( 'An OpenAI API key is required for Batch API preload.', 'hidden-word-bible-lessons' ) );
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/files/' . rawurlencode( (string) $file_id ) . '/content',
			array(
				'timeout'  => 120,
				'headers'  => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'stream'   => false,
			)
		);

		$http_error = HWBL_Http_Utils::response_error( $response, 'hwbl_batch_download_failed' );
		if ( $http_error ) {
			return $http_error;
		}

		$contents = wp_remote_retrieve_body( $response );
		$dir      = dirname( (string) $dest_path );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$written = file_put_contents( $dest_path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			return new WP_Error( 'hwbl_batch_download_write', __( 'Could not write OpenAI batch output file.', 'hidden-word-bible-lessons' ) );
		}

		return true;
	}

	/**
	 * Decode an OpenAI JSON API response.
	 *
	 * @param array|WP_Error $response   HTTP response.
	 * @param string         $error_code Error code on failure.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function openai_decode_response( $response, $error_code ) {
		$http_error = HWBL_Http_Utils::response_error( $response, $error_code );
		if ( $http_error ) {
			return $http_error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return new WP_Error( $error_code, __( 'OpenAI returned an invalid JSON response.', 'hidden-word-bible-lessons' ) );
		}
		if ( ! empty( $body['error']['message'] ) ) {
			return new WP_Error( $error_code, (string) $body['error']['message'] );
		}

		return $body;
	}

	/**
	 * Call Claude API.
	 *
	 * @param string $api_key            API key.
	 * @param string $prompt             Prompt.
	 * @param string $system_instruction Optional system instruction. Anthropic's
	 *                                   Messages API takes this as a top-level
	 *                                   'system' field rather than a message
	 *                                   with role "system".
	 * @return string|WP_Error
	 */
	private static function call_claude( $api_key, $prompt, $system_instruction = '' ) {
		$body = array(
			'model'       => 'claude-3-haiku-20240307',
			'max_tokens'  => 4096,
			'temperature' => 0.2,
			'messages'    => array(
				array( 'role' => 'user', 'content' => $prompt ),
			),
		);

		if ( '' !== trim( (string) $system_instruction ) ) {
			$body['system'] = $system_instruction;
		}

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		$http_error = HWBL_Http_Utils::response_error( $response, 'hwbl_api_error' );
		if ( $http_error ) {
			return $http_error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['content'][0]['text'] ) ) {
			return new WP_Error( 'hwbl_api_error', __( 'Claude returned an empty response.', 'hidden-word-bible-lessons' ) );
		}

		return trim( (string) $body['content'][0]['text'] );
	}

	/**
	 * Normalize an AI HTML/prose response for safe front-end display.
	 *
	 * Strips markdown/code fences (```html … ```) and converts plain-text
	 * section headings into <h3>/<p> markup when the model ignores HTML instructions.
	 *
	 * @param string $raw Raw model output.
	 * @return string HTML-ish string ready for wp_kses_post().
	 */
	public static function normalize_html_response( $raw ) {
		$text = trim( (string) $raw );
		if ( '' === $text ) {
			return '';
		}

		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );

		// Full fenced block: ```html … ``` (also smart-quote backticks).
		if ( preg_match( '/^\s*[`\'"“”‘’]{0,3}\s*```(?:html)?\s*\n([\s\S]*?)\n\s*```\s*[`\'"“”‘’]{0,3}\s*$/iu', $text, $m ) ) {
			$text = trim( $m[1] );
		} else {
			$text = preg_replace( '/^\s*```(?:html)?\s*\n?/i', '', $text );
			$text = preg_replace( '/\n?\s*```\s*$/', '', (string) $text );
			// Models sometimes emit “`html or similar instead of ```html.
			$text = preg_replace( '/^\s*[“”‘’`\']{1,3}\s*html\s*\n?/iu', '', (string) $text );
			$text = preg_replace( '/\n?\s*[“”‘’`\']{1,3}\s*$/u', '', (string) $text );
			$text = trim( (string) $text );
		}

		if ( '' === $text ) {
			return '';
		}

		// Already real HTML — leave structure for wp_kses_post().
		if ( preg_match( '/<(?:p|h[1-6]|ul|ol|li|div|blockquote|br|strong|em)\b/i', $text ) ) {
			return $text;
		}

		$known_headings = array(
			'Historical lead-up',
			'Why this verse was written',
			'True meaning',
			'Living it today',
			'What follows',
			'Context',
			'Meaning',
			'Application',
			'Prayer',
		);

		$lines      = explode( "\n", $text );
		$chunks     = array();
		$buffer     = array();
		$saw_heading = false;

		$flush_buffer = static function () use ( &$buffer, &$chunks ) {
			$para = trim( implode( "\n", $buffer ) );
			$buffer = array();
			if ( '' === $para ) {
				return;
			}
			$chunks[] = array(
				'type' => 'p',
				'text' => $para,
			);
		};

		foreach ( $lines as $line ) {
			$trim       = trim( $line );
			$is_heading = false;
			foreach ( $known_headings as $heading ) {
				if ( 0 === strcasecmp( $trim, $heading ) ) {
					$flush_buffer();
					$chunks[]    = array(
						'type' => 'h3',
						'text' => $heading,
					);
					$saw_heading = true;
					$is_heading  = true;
					break;
				}
			}
			if ( $is_heading ) {
				continue;
			}
			$buffer[] = $line;
		}
		$flush_buffer();

		if ( ! $saw_heading || empty( $chunks ) ) {
			return wpautop( $text );
		}

		$html = '';
		foreach ( $chunks as $chunk ) {
			if ( 'h3' === $chunk['type'] ) {
				$html .= '<h3>' . esc_html( $chunk['text'] ) . '</h3>';
				continue;
			}
			$paras = preg_split( '/\n\s*\n/', $chunk['text'] );
			if ( ! is_array( $paras ) ) {
				$paras = array( $chunk['text'] );
			}
			foreach ( $paras as $para ) {
				$para = trim( preg_replace( '/\s*\n\s*/', ' ', (string) $para ) );
				if ( '' !== $para ) {
					$html .= '<p>' . esc_html( $para ) . '</p>';
				}
			}
		}

		return $html;
	}

	/**
	 * Normalize + allowlist-sanitize AI HTML for storage and display.
	 *
	 * @param string $raw Raw model output.
	 * @return string Safe HTML.
	 */
	public static function format_html_response( $raw ) {
		return wp_kses_post( self::normalize_html_response( $raw ) );
	}

	/**
	 * Whether token streaming is available (WP 7.1+ AI Client stream API).
	 *
	 * BYOK-only sites do not get progressive tokens — callers should fall back
	 * to a single non-stream completion.
	 *
	 * @return bool
	 */
	public static function supports_streaming() {
		if ( ! self::core_ai_environment_enabled() ) {
			return false;
		}

		foreach ( self::core_provider_attempt_ids() as $provider_id ) {
			$builder = self::core_prompt_builder( 'Streaming availability check', '', $provider_id );
			if ( ! is_object( $builder ) ) {
				continue;
			}
			if ( method_exists( $builder, 'stream_generate_text' ) || method_exists( $builder, 'streamGenerateText' ) ) {
				return true;
			}
			// Probe via __call support: some WP wrappers expose methods only through magic.
			if ( is_callable( array( $builder, 'stream_generate_text' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether embedding generation is available (WP AI Client or OpenAI BYOK).
	 *
	 * @return bool
	 */
	public static function supports_embeddings() {
		if ( self::core_supports_embeddings() ) {
			return true;
		}

		return '' !== self::resolve_openai_api_key();
	}

	/**
	 * Stream a text completion, invoking $on_chunk for each delta when supported.
	 *
	 * Args:
	 * - prompt (string, required)
	 * - system_instruction (string, optional)
	 * - on_chunk (callable, optional) receives string deltas
	 *
	 * When streaming APIs are missing, generates the full text once and calls
	 * on_chunk a single time (pseudo-stream) so callers can share one code path.
	 *
	 * @param array<string, mixed> $args Stream args.
	 * @return string|WP_Error Full assembled text.
	 */
	public static function stream_completion( $args ) {
		$args               = is_array( $args ) ? $args : array();
		$prompt             = isset( $args['prompt'] ) ? (string) $args['prompt'] : '';
		$system_instruction = isset( $args['system_instruction'] ) ? (string) $args['system_instruction'] : '';
		$on_chunk           = isset( $args['on_chunk'] ) && is_callable( $args['on_chunk'] ) ? $args['on_chunk'] : null;

		if ( '' === trim( $prompt ) ) {
			return new WP_Error( 'hwbl_empty_prompt', __( 'Prompt text is required.', 'hidden-word-bible-lessons' ) );
		}

		if ( self::supports_streaming() ) {
			$result = self::stream_completion_via_core( $prompt, $system_instruction, $on_chunk );
			if ( ! is_wp_error( $result ) || 'hwbl_no_ai_connector' !== $result->get_error_code() ) {
				return $result;
			}
		}

		// Graceful degrade: single-shot completion, optionally emitted as one chunk.
		$text = self::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $text ) ) {
			return $text;
		}
		$text = (string) $text;
		if ( $on_chunk && '' !== $text ) {
			call_user_func( $on_chunk, $text );
		}

		return $text;
	}

	/**
	 * Generate an embedding vector for text.
	 *
	 * Prefers WordPress AI Client embeddings (7.1+), then OpenAI BYOK.
	 *
	 * @param string $text Text to embed.
	 * @return array<int, float>|WP_Error
	 */
	public static function generate_embedding( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return new WP_Error( 'hwbl_empty_embed', __( 'Text is required for embeddings.', 'hidden-word-bible-lessons' ) );
		}

		if ( self::core_supports_embeddings() ) {
			$result = self::generate_embedding_via_core( $text );
			if ( ! is_wp_error( $result ) ) {
				return $result;
			}
		}

		$openai_key = self::resolve_openai_api_key();
		if ( '' !== $openai_key ) {
			return self::generate_embedding_via_openai( $openai_key, $text );
		}

		return new WP_Error(
			'hwbl_no_embeddings',
			__( 'Embeddings are not available. Configure an AI connector that supports embeddings, or an OpenAI API key.', 'hidden-word-bible-lessons' )
		);
	}

	/**
	 * Cosine similarity between two embedding vectors.
	 *
	 * @param array<int, float|int> $a Vector A.
	 * @param array<int, float|int> $b Vector B.
	 * @return float Similarity in [-1, 1], or 0.0 when invalid.
	 */
	public static function cosine_similarity( $a, $b ) {
		if ( ! is_array( $a ) || ! is_array( $b ) || empty( $a ) || empty( $b ) ) {
			return 0.0;
		}

		$len = min( count( $a ), count( $b ) );
		if ( $len < 1 ) {
			return 0.0;
		}

		$dot = 0.0;
		$na  = 0.0;
		$nb  = 0.0;
		for ( $i = 0; $i < $len; $i++ ) {
			$av = (float) $a[ $i ];
			$bv = (float) $b[ $i ];
			$dot += $av * $bv;
			$na  += $av * $av;
			$nb  += $bv * $bv;
		}

		if ( $na <= 0.0 || $nb <= 0.0 ) {
			return 0.0;
		}

		return $dot / ( sqrt( $na ) * sqrt( $nb ) );
	}

	/**
	 * Stream text through WordPress AI Client when stream_generate_text exists.
	 *
	 * @param string        $prompt             Prompt.
	 * @param string        $system_instruction System instruction.
	 * @param callable|null $on_chunk           Chunk callback.
	 * @return string|WP_Error
	 */
	private static function stream_completion_via_core( $prompt, $system_instruction, $on_chunk ) {
		$last_error = null;

		foreach ( self::core_provider_attempt_ids() as $provider_id ) {
			$builder = self::core_prompt_builder( $prompt, $system_instruction, $provider_id );
			if ( ! is_object( $builder ) ) {
				continue;
			}

			$assembled = '';
			try {
				if ( is_callable( array( $builder, 'stream_generate_text' ) ) ) {
					foreach ( $builder->stream_generate_text() as $delta ) {
						$delta = (string) $delta;
						if ( '' === $delta ) {
							continue;
						}
						$assembled .= $delta;
						if ( $on_chunk ) {
							call_user_func( $on_chunk, $delta );
						}
					}
					if ( '' !== $assembled ) {
						return $assembled;
					}
				} elseif ( is_callable( array( $builder, 'stream_generate_text_result' ) ) ) {
					foreach ( $builder->stream_generate_text_result() as $chunk ) {
						$delta = '';
						if ( is_object( $chunk ) && method_exists( $chunk, 'getDeltaText' ) ) {
							$delta = (string) $chunk->getDeltaText();
						} elseif ( is_object( $chunk ) && method_exists( $chunk, 'get_delta_text' ) ) {
							$delta = (string) $chunk->get_delta_text();
						} elseif ( is_string( $chunk ) ) {
							$delta = $chunk;
						}
						if ( '' === $delta ) {
							continue;
						}
						$assembled .= $delta;
						if ( $on_chunk ) {
							call_user_func( $on_chunk, $delta );
						}
					}
					if ( '' !== $assembled ) {
						return $assembled;
					}
				} else {
					continue;
				}
			} catch ( Exception $e ) {
				$last_error = new WP_Error( 'hwbl_stream_failed', $e->getMessage() );
				continue;
			}
		}

		if ( $last_error instanceof WP_Error ) {
			return $last_error;
		}

		return self::core_not_configured_error();
	}

	/**
	 * Whether the AI Client can generate embeddings.
	 *
	 * @return bool
	 */
	private static function core_supports_embeddings() {
		if ( ! class_exists( '\WordPress\AiClient\AiClient' ) ) {
			return false;
		}

		if ( ! self::core_ai_environment_enabled() ) {
			return false;
		}

		try {
			if ( method_exists( '\WordPress\AiClient\AiClient', 'input' ) ) {
				$builder = \WordPress\AiClient\AiClient::input( 'Availability check' );
				if ( is_object( $builder ) && method_exists( $builder, 'isSupported' ) && $builder->isSupported() ) {
					return true;
				}
			}
		} catch ( Exception $e ) {
			return false;
		}

		// Prompt-builder probe used by some WP 7.0/7.1 builds.
		$builder = self::core_prompt_builder( 'Availability check' );
		if ( is_object( $builder ) && method_exists( $builder, 'is_supported_for_embedding_generation' ) ) {
			return (bool) $builder->is_supported_for_embedding_generation();
		}

		return false;
	}

	/**
	 * Generate embedding via WordPress AI Client.
	 *
	 * @param string $text Text.
	 * @return array<int, float>|WP_Error
	 */
	private static function generate_embedding_via_core( $text ) {
		try {
			if ( method_exists( '\WordPress\AiClient\AiClient', 'generateEmbedding' ) ) {
				$embedding = \WordPress\AiClient\AiClient::generateEmbedding( $text );
				return self::extract_embedding_values( $embedding );
			}
			if ( method_exists( '\WordPress\AiClient\AiClient', 'input' ) ) {
				$builder = \WordPress\AiClient\AiClient::input( $text );
				if ( is_object( $builder ) && method_exists( $builder, 'generateEmbedding' ) ) {
					return self::extract_embedding_values( $builder->generateEmbedding() );
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'hwbl_embed_failed', $e->getMessage() );
		}

		return new WP_Error( 'hwbl_no_embeddings', __( 'Embeddings are not available on this site.', 'hidden-word-bible-lessons' ) );
	}

	/**
	 * Normalize an Embedding object / array into a float list.
	 *
	 * @param mixed $embedding Embedding result.
	 * @return array<int, float>|WP_Error
	 */
	private static function extract_embedding_values( $embedding ) {
		if ( is_wp_error( $embedding ) ) {
			return $embedding;
		}
		if ( is_object( $embedding ) && method_exists( $embedding, 'getValues' ) ) {
			$values = $embedding->getValues();
			return is_array( $values ) ? array_map( 'floatval', array_values( $values ) ) : new WP_Error( 'hwbl_embed_empty', __( 'Empty embedding vector.', 'hidden-word-bible-lessons' ) );
		}
		if ( is_array( $embedding ) ) {
			return array_map( 'floatval', array_values( $embedding ) );
		}

		return new WP_Error( 'hwbl_embed_invalid', __( 'Unexpected embedding response.', 'hidden-word-bible-lessons' ) );
	}

	/**
	 * OpenAI embeddings API (BYOK / Connectors key).
	 *
	 * @param string $api_key API key.
	 * @param string $text    Text.
	 * @return array<int, float>|WP_Error
	 */
	private static function generate_embedding_via_openai( $api_key, $text ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => 'text-embedding-3-small',
						'input' => $text,
					)
				),
			)
		);

		$http_error = HWBL_Http_Utils::response_error( $response, 'hwbl_embed_failed' );
		if ( $http_error ) {
			return $http_error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data'][0]['embedding'] ) || ! is_array( $body['data'][0]['embedding'] ) ) {
			return new WP_Error( 'hwbl_embed_empty', __( 'OpenAI returned an empty embedding.', 'hidden-word-bible-lessons' ) );
		}

		return array_map( 'floatval', array_values( $body['data'][0]['embedding'] ) );
	}
}
