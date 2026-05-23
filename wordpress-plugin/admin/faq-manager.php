<?php
/**
 * FAQ Manager admin page — CRUD for FAQ entries stored in a custom DB table.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table = $wpdb->prefix . 'aiwc_faqs';

// Ensure FAQs table exists (safe to call multiple times)
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"CREATE TABLE IF NOT EXISTS {$table} (
		id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		question   TEXT            NOT NULL,
		answer     TEXT            NOT NULL,
		keywords   VARCHAR(500)    DEFAULT '',
		created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) " . $wpdb->get_charset_collate()
);

// Handle Add / Delete actions
if ( isset( $_POST['aiwc_faq_action'] ) && check_admin_referer( 'aiwc_faq_nonce' ) ) {
	$action = sanitize_text_field( wp_unslash( $_POST['aiwc_faq_action'] ) );

	if ( 'add' === $action ) {
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'question' => sanitize_textarea_field( wp_unslash( $_POST['faq_question'] ?? '' ) ),
				'answer'   => sanitize_textarea_field( wp_unslash( $_POST['faq_answer'] ?? '' ) ),
				'keywords' => sanitize_text_field( wp_unslash( $_POST['faq_keywords'] ?? '' ) ),
			)
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'FAQ added.', 'ai-woo-chatbot' ) . '</p></div>';
	}

	if ( 'delete' === $action && ! empty( $_POST['faq_id'] ) ) {
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array( 'id' => (int) $_POST['faq_id'] ),
			array( '%d' )
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'FAQ deleted.', 'ai-woo-chatbot' ) . '</p></div>';
	}
}

// Fetch all FAQs
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$faqs = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ) ?: array();
?>
<div class="wrap aiwc-admin">
	<h1><?php esc_html_e( 'AI Chatbot — FAQ Manager', 'ai-woo-chatbot' ); ?></h1>

	<h2><?php esc_html_e( 'Add New FAQ', 'ai-woo-chatbot' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( 'aiwc_faq_nonce' ); ?>
		<input type="hidden" name="aiwc_faq_action" value="add" />
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Question', 'ai-woo-chatbot' ); ?></th>
				<td><input type="text" name="faq_question" class="large-text" required /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Answer', 'ai-woo-chatbot' ); ?></th>
				<td><textarea name="faq_answer" rows="4" class="large-text" required></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Keywords', 'ai-woo-chatbot' ); ?></th>
				<td>
					<input type="text" name="faq_keywords" class="large-text" placeholder="return, refund, exchange" />
					<p class="description"><?php esc_html_e( 'Comma-separated keywords to improve matching.', 'ai-woo-chatbot' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Add FAQ', 'ai-woo-chatbot' ) ); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Existing FAQs', 'ai-woo-chatbot' ); ?></h2>
	<?php if ( empty( $faqs ) ) : ?>
		<p><?php esc_html_e( 'No FAQs yet. Add some above!', 'ai-woo-chatbot' ); ?></p>
	<?php else : ?>
	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Question', 'ai-woo-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Answer', 'ai-woo-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Keywords', 'ai-woo-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ai-woo-chatbot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $faqs as $faq ) : ?>
			<tr>
				<td><?php echo esc_html( $faq['question'] ); ?></td>
				<td><?php echo esc_html( mb_strimwidth( $faq['answer'], 0, 100, '…' ) ); ?></td>
				<td><?php echo esc_html( $faq['keywords'] ); ?></td>
				<td>
					<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this FAQ?', 'ai-woo-chatbot' ); ?>');">
						<?php wp_nonce_field( 'aiwc_faq_nonce' ); ?>
						<input type="hidden" name="aiwc_faq_action" value="delete" />
						<input type="hidden" name="faq_id" value="<?php echo esc_attr( $faq['id'] ); ?>" />
						<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete', 'ai-woo-chatbot' ); ?></button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
