<?php
/**
 * Product card template rendered inside the chat.
 * Variables available: $product (array from AIWC_WooCommerce::format_product()).
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="aiwc-product-card">
	<?php if ( ! empty( $product['image_url'] ) ) : ?>
	<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank" rel="noopener">
		<img
			src="<?php echo esc_url( $product['image_url'] ); ?>"
			alt="<?php echo esc_attr( $product['name'] ); ?>"
			class="aiwc-product-img"
			loading="lazy"
		/>
	</a>
	<?php endif; ?>

	<div class="aiwc-product-info">
		<a href="<?php echo esc_url( $product['permalink'] ); ?>" class="aiwc-product-name" target="_blank" rel="noopener">
			<?php echo esc_html( $product['name'] ); ?>
		</a>

		<span class="aiwc-product-price">
			<?php echo wp_kses_post( $product['price_html'] ); ?>
		</span>

		<?php if ( ! empty( $product['short_description'] ) ) : ?>
		<p class="aiwc-product-desc">
			<?php echo esc_html( mb_strimwidth( $product['short_description'], 0, 80, '…' ) ); ?>
		</p>
		<?php endif; ?>

		<div class="aiwc-product-actions">
			<?php if ( $product['in_stock'] ) : ?>
			<button
				class="aiwc-add-to-cart-btn"
				data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
				data-product-name="<?php echo esc_attr( $product['name'] ); ?>"
			>
				<?php esc_html_e( '🛒 Add to Cart', 'ai-woo-chatbot' ); ?>
			</button>
			<?php else : ?>
			<span class="aiwc-out-of-stock"><?php esc_html_e( 'Out of Stock', 'ai-woo-chatbot' ); ?></span>
			<?php endif; ?>

			<a href="<?php echo esc_url( $product['permalink'] ); ?>" class="aiwc-view-details" target="_blank" rel="noopener">
				<?php esc_html_e( 'View Details', 'ai-woo-chatbot' ); ?>
			</a>
		</div>
	</div>
</div>
