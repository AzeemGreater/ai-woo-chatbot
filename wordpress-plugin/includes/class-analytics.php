<?php
/**
 * Chat analytics — tracks conversation statistics.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

class AIWC_Analytics {

	const TABLE = 'aiwc_analytics';

	/** Create analytics table on plugin activation. */
	public static function create_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(128)    NOT NULL,
			message    TEXT            NOT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** Record a single chat message for analytics. */
	public function record_message( string $session_id, string $message ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . self::TABLE,
			array(
				'session_id' => sanitize_text_field( $session_id ),
				'message'    => sanitize_textarea_field( $message ),
			)
		);
	}

	/**
	 * Return aggregate statistics.
	 *
	 * @return array{total_sessions: int, total_messages: int, avg_messages_per_session: float}
	 */
	public function get_stats(): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_messages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table}" );
		$avg            = $total_sessions > 0 ? round( $total_messages / $total_sessions, 1 ) : 0.0;

		return array(
			'total_sessions'            => $total_sessions,
			'total_messages'            => $total_messages,
			'avg_messages_per_session'  => $avg,
		);
	}

	/**
	 * Return the most frequently asked phrases (top N words).
	 *
	 * @return array<int, array{word: string, count: int}>
	 */
	public function get_popular_queries( int $limit = 10 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$messages = $wpdb->get_col( "SELECT message FROM {$table} ORDER BY created_at DESC LIMIT 500" );

		$word_count = array();
		foreach ( $messages as $msg ) {
			$words = preg_split( '/\s+/', strtolower( strip_tags( $msg ) ) );
			foreach ( (array) $words as $word ) {
				$word = trim( $word, '.,!?;:\'"' );
				if ( strlen( $word ) > 3 ) {
					$word_count[ $word ] = ( $word_count[ $word ] ?? 0 ) + 1;
				}
			}
		}

		arsort( $word_count );
		$top    = array_slice( $word_count, 0, $limit, true );
		$result = array();
		foreach ( $top as $word => $count ) {
			$result[] = array( 'word' => $word, 'count' => $count );
		}
		return $result;
	}
}
