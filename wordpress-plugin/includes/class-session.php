<?php
/**
 * Chat session manager (stored in the WordPress database).
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_Session {

	const TABLE = 'aiwc_sessions';

	public function __construct() {}

	/** Create the sessions table on plugin activation. */
	public static function create_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id    VARCHAR(128)    NOT NULL,
			email         VARCHAR(200)    DEFAULT NULL,
			message_count INT UNSIGNED    NOT NULL DEFAULT 0,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_id (session_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** Upsert a session record. */
	public function upsert( string $session_id, array $data = array() ): void {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE session_id = %s", $session_id ) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array_merge( array( 'message_count' => $this->get_count( $session_id ) + 1 ), $data ),
				array( 'session_id' => $session_id ),
				null,
				array( '%s' )
			);
		} else {
			$wpdb->insert(
				$table,
				array_merge( array( 'session_id' => $session_id, 'message_count' => 1 ), $data )
			);
		}
		// phpcs:enable
	}

	/** Return message count for a session. */
	public function get_count( string $session_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT message_count FROM {$table} WHERE session_id = %s", $session_id ) );
		return (int) $count;
	}
}
