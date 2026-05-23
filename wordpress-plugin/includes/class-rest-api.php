<?php
/**
 * Custom WP REST API endpoints for the chatbot.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_REST_API {

	const NAMESPACE = 'ai-chatbot/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		// Chat
		register_rest_route( self::NAMESPACE, '/chat', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_chat' ),
			'permission_callback' => array( $this, 'public_permission' ),
			'args'                => array(
				'session_id' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'message'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
				'history'    => array( 'required' => false, 'default' => array() ),
			),
		) );

		// Products search
		register_rest_route( self::NAMESPACE, '/products/search', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_product_search' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );

		// Single product
		register_rest_route( self::NAMESPACE, '/products/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_product_detail' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );

		// Cart: add item
		register_rest_route( self::NAMESPACE, '/cart/add', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_cart_add' ),
			'permission_callback' => array( $this, 'nonce_permission' ),
		) );

		// Order tracking
		register_rest_route( self::NAMESPACE, '/order/track', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_order_track' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );

		// Lead capture
		register_rest_route( self::NAMESPACE, '/leads', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_lead' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );
	}

	// -----------------------------------------------------------------------
	// Handlers
	// -----------------------------------------------------------------------

	/** Forward the chat message to the Python backend and return the response. */
	public function handle_chat( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$backend_base = aiwc()->settings->get( 'backend_url', '' );

		if ( empty( $backend_base ) ) {
			return new WP_Error( 'backend_not_configured', __( 'Backend API URL is not configured.', 'ai-woo-chatbot' ), array( 'status' => 503 ) );
		}

		$backend_url = trailingslashit( $backend_base ) . 'api/v1/chat';

		$payload = array(
			'session_id'         => $request->get_param( 'session_id' ),
			'message'            => $request->get_param( 'message' ),
			'history'            => $request->get_param( 'history' ) ?: array(),
			'current_product_id' => $request->get_param( 'current_product_id' ),
			'cart_items'         => $request->get_param( 'cart_items' ) ?: array(),
		);

		$response = wp_remote_post( $backend_url, array(
			'body'    => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'backend_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			return new WP_Error( 'backend_error', $body['detail'] ?? 'Backend error', array( 'status' => $code ) );
		}

		// Track analytics
		aiwc()->analytics->record_message( $payload['session_id'], $payload['message'] );

		return rest_ensure_response( $body );
	}

	/** Search products via WooCommerce. */
	public function handle_product_search( WP_REST_Request $request ): WP_REST_Response {
		$keyword   = sanitize_text_field( $request->get_param( 'q' ) ?? '' );
		$category  = sanitize_text_field( $request->get_param( 'category' ) ?? '' );
		$min_price = floatval( $request->get_param( 'min_price' ) ?? 0 );
		$max_price = floatval( $request->get_param( 'max_price' ) ?? 0 );

		$products = aiwc()->woocommerce->search_products( array(
			'keyword'   => $keyword,
			'category'  => $category,
			'min_price' => $min_price ?: null,
			'max_price' => $max_price ?: null,
			'limit'     => 6,
		) );

		return rest_ensure_response( array( 'products' => $products ) );
	}

	/** Return a single product. */
	public function handle_product_detail( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$product_id = (int) $request->get_param( 'id' );
		$product    = aiwc()->woocommerce->get_product( $product_id );

		if ( ! $product ) {
			return new WP_Error( 'not_found', __( 'Product not found.', 'ai-woo-chatbot' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $product );
	}

	/** Add a product to the WooCommerce cart. */
	public function handle_cart_add( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$product_id  = (int) $request->get_param( 'product_id' );
		$quantity    = max( 1, (int) ( $request->get_param( 'quantity' ) ?? 1 ) );
		$variation_id = (int) ( $request->get_param( 'variation_id' ) ?? 0 );

		if ( ! $product_id ) {
			return new WP_Error( 'missing_product', __( 'product_id is required.', 'ai-woo-chatbot' ), array( 'status' => 400 ) );
		}

		$result = aiwc()->woocommerce->add_to_cart( $product_id, $quantity, $variation_id );

		if ( ! $result ) {
			return new WP_Error( 'cart_error', __( 'Could not add item to cart.', 'ai-woo-chatbot' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true, 'cart_url' => wc_get_cart_url() ) );
	}

	/** Track an order by ID + email. */
	public function handle_order_track( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$order_id = sanitize_text_field( $request->get_param( 'order_id' ) ?? '' );
		$email    = sanitize_email( $request->get_param( 'email' ) ?? '' );

		if ( empty( $order_id ) ) {
			return new WP_Error( 'missing_order_id', __( 'order_id is required.', 'ai-woo-chatbot' ), array( 'status' => 400 ) );
		}

		$order = aiwc()->woocommerce->get_order( (int) $order_id, $email );

		if ( ! $order ) {
			return new WP_Error( 'not_found', __( 'Order not found. Please check your order ID and email.', 'ai-woo-chatbot' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $order );
	}

	/** Save a lead captured via the chat widget. */
	public function handle_lead( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$email = sanitize_email( $request->get_param( 'email' ) ?? '' );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'A valid email address is required.', 'ai-woo-chatbot' ), array( 'status' => 400 ) );
		}

		aiwc()->lead_capture->save( array(
			'email'      => $email,
			'name'       => sanitize_text_field( $request->get_param( 'name' ) ?? '' ),
			'phone'      => sanitize_text_field( $request->get_param( 'phone' ) ?? '' ),
			'session_id' => sanitize_text_field( $request->get_param( 'session_id' ) ?? '' ),
		) );

		return rest_ensure_response( array( 'status' => 'ok', 'message' => __( 'Thank you! We\'ll be in touch soon.', 'ai-woo-chatbot' ) ) );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/** Allow public access (rate-limited via nonce on sensitive operations). */
	public function public_permission(): bool {
		return true;
	}

	/** Require a valid REST nonce for write operations that touch the cart. */
	public function nonce_permission( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
