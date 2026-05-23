<?php
/**
 * Core plugin class — singleton that wires everything together.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_Plugin {

	/** @var AIWC_Plugin|null Singleton instance */
	private static ?AIWC_Plugin $instance = null;

	/** Plugin sub-objects */
	public AIWC_REST_API     $rest_api;
	public AIWC_WooCommerce  $woocommerce;
	public AIWC_Session      $session;
	public AIWC_Settings     $settings;
	public AIWC_Lead_Capture $lead_capture;
	public AIWC_Analytics    $analytics;

	/**
	 * Returns the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_components();
		$this->register_hooks();
	}

	private function init_components(): void {
		$this->settings     = new AIWC_Settings();
		$this->woocommerce  = new AIWC_WooCommerce();
		$this->session      = new AIWC_Session();
		$this->rest_api     = new AIWC_REST_API();
		$this->lead_capture = new AIWC_Lead_Capture();
		$this->analytics    = new AIWC_Analytics();
	}

	private function register_hooks(): void {
		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_footer',             array( $this, 'render_chat_widget' ) );
		add_action( 'admin_menu',            array( $this, 'register_admin_menu' ) );
	}

	/** Enqueue frontend JS / CSS. */
	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'aiwc-widget',
			AIWC_PLUGIN_URL . 'assets/css/chat-widget.css',
			array(),
			AIWC_VERSION
		);

		wp_enqueue_script(
			'aiwc-widget',
			AIWC_PLUGIN_URL . 'assets/js/chat-widget.js',
			array(),
			AIWC_VERSION,
			true
		);

		wp_enqueue_script(
			'aiwc-exit-intent',
			AIWC_PLUGIN_URL . 'assets/js/exit-intent.js',
			array( 'aiwc-widget' ),
			AIWC_VERSION,
			true
		);

		wp_enqueue_script(
			'aiwc-cart-watcher',
			AIWC_PLUGIN_URL . 'assets/js/cart-watcher.js',
			array( 'aiwc-widget' ),
			AIWC_VERSION,
			true
		);

		// Pass configuration to the widget.
		wp_localize_script( 'aiwc-widget', 'AIWC_Config', array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'restUrl'        => rest_url( 'ai-chatbot/v1' ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'botName'        => $this->settings->get( 'bot_name', 'ShopBot' ),
			'welcomeMessage' => $this->settings->get( 'welcome_message', 'Hi! 👋 How can I help you?' ),
			'primaryColor'   => $this->settings->get( 'primary_color', '#4f46e5' ),
			'position'       => $this->settings->get( 'position', 'bottom-right' ),
			'avatarUrl'      => AIWC_PLUGIN_URL . 'assets/images/bot-avatar.svg',
			'isLoggedIn'     => is_user_logged_in(),
			'currentProductId' => $this->get_current_product_id(),
		) );
	}

	/** Enqueue admin-only assets. */
	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, 'aiwc' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'aiwc-admin',
			AIWC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AIWC_VERSION
		);
	}

	/** Output the chat widget HTML in the footer. */
	public function render_chat_widget(): void {
		include AIWC_PLUGIN_DIR . 'templates/chat-window.php';
	}

	/** Register the admin menu pages. */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'AI Chatbot', 'ai-woo-chatbot' ),
			__( 'AI Chatbot', 'ai-woo-chatbot' ),
			'manage_options',
			'aiwc-settings',
			function() { include AIWC_PLUGIN_DIR . 'admin/settings-page.php'; },
			'dashicons-format-chat',
			56
		);

		add_submenu_page(
			'aiwc-settings',
			__( 'Settings', 'ai-woo-chatbot' ),
			__( 'Settings', 'ai-woo-chatbot' ),
			'manage_options',
			'aiwc-settings',
			function() { include AIWC_PLUGIN_DIR . 'admin/settings-page.php'; }
		);

		add_submenu_page(
			'aiwc-settings',
			__( 'Analytics', 'ai-woo-chatbot' ),
			__( 'Analytics', 'ai-woo-chatbot' ),
			'manage_options',
			'aiwc-analytics',
			function() { include AIWC_PLUGIN_DIR . 'admin/analytics-page.php'; }
		);

		add_submenu_page(
			'aiwc-settings',
			__( 'FAQ Manager', 'ai-woo-chatbot' ),
			__( 'FAQ Manager', 'ai-woo-chatbot' ),
			'manage_options',
			'aiwc-faq-manager',
			function() { include AIWC_PLUGIN_DIR . 'admin/faq-manager.php'; }
		);
	}

	/** Return the current WooCommerce product ID (if on a single product page). */
	private function get_current_product_id(): ?int {
		if ( is_singular( 'product' ) ) {
			return (int) get_queried_object_id();
		}
		return null;
	}

	/** Plugin activation: create DB tables and default options. */
	public static function activate(): void {
		AIWC_Analytics::create_table();
		AIWC_Lead_Capture::create_table();
		AIWC_Session::create_table();
	}

	/** Plugin deactivation: flush rewrite rules. */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
