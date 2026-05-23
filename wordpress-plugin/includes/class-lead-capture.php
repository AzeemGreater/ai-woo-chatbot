<?php
/**
 * Lead capture — store customer email/contact info collected via the chat.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_Lead_Capture {

	const TABLE = 'aiwc_leads';

	/** Create the leads table on plugin activation. */
	public static function create_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			email      VARCHAR(200)    NOT NULL,
			name       VARCHAR(200)    DEFAULT '',
			phone      VARCHAR(50)     DEFAULT '',
			session_id VARCHAR(128)    DEFAULT '',
			source     VARCHAR(100)    DEFAULT 'chatbot',
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email (email)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Save a lead to the database.
	 *
	 * @param array{email: string, name?: string, phone?: string, session_id?: string} $data
	 */
	public function save( array $data ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'email'      => sanitize_email( $data['email'] ),
				'name'       => sanitize_text_field( $data['name'] ?? '' ),
				'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
				'session_id' => sanitize_text_field( $data['session_id'] ?? '' ),
				'source'     => 'chatbot',
			)
		);

		return (bool) $result;
	}

	/**
	 * Return all leads (for admin display).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all( int $limit = 100 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ), ARRAY_A );
		return $rows ?: array();
	}
}
