<?php
/**
 * WooCommerce data layer — products, orders, categories, cart.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_WooCommerce {

	// -----------------------------------------------------------------------
	// Products
	// -----------------------------------------------------------------------

	/**
	 * Search products and return a simplified array suitable for the chat API.
	 *
	 * @param array{keyword?: string, category?: string, min_price?: float|null, max_price?: float|null, limit?: int, featured?: bool, on_sale?: bool} $args
	 * @return array<int, array<string, mixed>>
	 */
	public function search_products( array $args = array() ): array {
		$defaults = array(
			'keyword'   => '',
			'category'  => '',
			'min_price' => null,
			'max_price' => null,
			'limit'     => 6,
			'featured'  => false,
			'on_sale'   => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $args['limit'],
		);

		if ( ! empty( $args['keyword'] ) ) {
			$query_args['s'] = $args['keyword'];
		}

		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_title( $args['category'] ),
				),
			);
		}

		if ( $args['featured'] ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
			);
		}

		if ( $args['on_sale'] ) {
			$query_args['post__in'] = wc_get_product_ids_on_sale();
		}

		if ( ! empty( $args['min_price'] ) || ! empty( $args['max_price'] ) ) {
			$query_args['meta_query'] = array( 'relation' => 'AND' );
			if ( ! empty( $args['min_price'] ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_price',
					'value'   => (float) $args['min_price'],
					'compare' => '>=',
					'type'    => 'NUMERIC',
				);
			}
			if ( ! empty( $args['max_price'] ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_price',
					'value'   => (float) $args['max_price'],
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
		}

		$query    = new WP_Query( $query_args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$wc_product = wc_get_product( $post->ID );
			if ( $wc_product ) {
				$products[] = $this->format_product( $wc_product );
			}
		}

		return $products;
	}

	/**
	 * Get a single product by ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_product( int $product_id ): ?array {
		$wc_product = wc_get_product( $product_id );
		return $wc_product ? $this->format_product( $wc_product ) : null;
	}

	/**
	 * Format a WC_Product into a chat-friendly array.
	 *
	 * @return array<string, mixed>
	 */
	public function format_product( WC_Product $product ): array {
		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( (int) $image_id, 'medium' ) : wc_placeholder_img_src();

		$categories = array();
		foreach ( $product->get_category_ids() as $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$categories[] = $term->name;
			}
		}

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'permalink'         => get_permalink( $product->get_id() ),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price() ?: null,
			'price_html'        => $product->get_price_html(),
			'short_description' => wp_strip_all_tags( $product->get_short_description() ),
			'description'       => wp_strip_all_tags( $product->get_description() ),
			'sku'               => $product->get_sku(),
			'in_stock'          => $product->is_in_stock(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'image_url'         => $image_url ?: '',
			'categories'        => $categories,
			'rating_average'    => (float) $product->get_average_rating(),
			'rating_count'      => $product->get_rating_count(),
		);
	}

	/**
	 * Return a list of product categories.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_categories(): array {
		$terms  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
		$result = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$result[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return $result;
	}

	// -----------------------------------------------------------------------
	// Orders
	// -----------------------------------------------------------------------

	/**
	 * Look up an order by ID.  If $email is provided it must match billing email.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_order( int $order_id, string $email = '' ): ?array {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return null;
		}

		if ( ! empty( $email ) && strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) {
			return null;
		}

		return $this->format_order( $order );
	}

	/**
	 * Format a WC_Order into a chat-friendly array.
	 *
	 * @return array<string, mixed>
	 */
	public function format_order( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'subtotal' => $item->get_subtotal(),
			);
		}

		return array(
			'id'           => $order->get_id(),
			'status'       => $order->get_status(),
			'status_label' => wc_get_order_status_name( $order->get_status() ),
			'date_created' => $order->get_date_created()?->date( 'Y-m-d H:i:s' ) ?? '',
			'total'        => $order->get_total(),
			'currency'     => $order->get_currency(),
			'billing_name' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'items'        => $items,
		);
	}

	// -----------------------------------------------------------------------
	// Cart
	// -----------------------------------------------------------------------

	/**
	 * Add a product to the WooCommerce cart.
	 *
	 * @return string|bool Cart item key on success, false on failure.
	 */
	public function add_to_cart( int $product_id, int $quantity = 1, int $variation_id = 0 ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}
		return WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
	}
}
