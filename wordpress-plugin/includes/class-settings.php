<?php
/**
 * Admin settings manager.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_Settings {

	const OPTION_KEY = 'aiwc_settings';

	/** @var array<string, mixed> */
	private array $settings = array();

	public function __construct() {
		$stored         = get_option( self::OPTION_KEY, array() );
		$this->settings = is_array( $stored ) ? $stored : array();

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/** Get a setting value with an optional default. */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->settings[ $key ] ?? $default;
	}

	/** Persist settings to the WordPress options table. */
	public function save( array $data ): void {
		$this->settings = array_merge( $this->settings, $data );
		update_option( self::OPTION_KEY, $this->settings );
	}

	/** Register settings with the Settings API. */
	public function register_settings(): void {
		register_setting(
			'aiwc_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize/validate all settings fields before saving.
	 *
	 * @param mixed $input Raw submitted data.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();

		// General
		$clean['bot_name']        = sanitize_text_field( $input['bot_name'] ?? 'ShopBot' );
		$clean['welcome_message'] = sanitize_textarea_field( $input['welcome_message'] ?? '' );
		$clean['avatar_url']      = esc_url_raw( $input['avatar_url'] ?? '' );

		// AI configuration
		$clean['backend_url'] = esc_url_raw( $input['backend_url'] ?? '' );
		// Never log or expose the API key in output — just store it.
		$clean['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] ?? '' );

		// Appearance
		$clean['primary_color'] = sanitize_hex_color( $input['primary_color'] ?? '#4f46e5' ) ?: '#4f46e5';
		$clean['position']      = in_array( $input['position'] ?? '', array( 'bottom-right', 'bottom-left' ), true )
			? $input['position']
			: 'bottom-right';

		// Behaviour toggles
		$clean['enable_exit_intent']   = ! empty( $input['enable_exit_intent'] );
		$clean['enable_cart_recovery'] = ! empty( $input['enable_cart_recovery'] );
		$clean['enable_lead_capture']  = ! empty( $input['enable_lead_capture'] );

		return $clean;
	}
}
