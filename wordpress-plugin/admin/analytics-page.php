<?php
/**
 * Analytics dashboard page.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

$stats   = aiwc()->analytics->get_stats();
$popular = aiwc()->analytics->get_popular_queries( 10 );
?>
<div class="wrap aiwc-admin">
	<h1><?php esc_html_e( 'AI Chatbot — Analytics', 'ai-woo-chatbot' ); ?></h1>

	<div class="aiwc-stats-cards">
		<div class="aiwc-stat-card">
			<span class="aiwc-stat-number"><?php echo esc_html( number_format( $stats['total_sessions'] ) ); ?></span>
			<span class="aiwc-stat-label"><?php esc_html_e( 'Total Chat Sessions', 'ai-woo-chatbot' ); ?></span>
		</div>
		<div class="aiwc-stat-card">
			<span class="aiwc-stat-number"><?php echo esc_html( number_format( $stats['total_messages'] ) ); ?></span>
			<span class="aiwc-stat-label"><?php esc_html_e( 'Total Messages', 'ai-woo-chatbot' ); ?></span>
		</div>
		<div class="aiwc-stat-card">
			<span class="aiwc-stat-number"><?php echo esc_html( $stats['avg_messages_per_session'] ); ?></span>
			<span class="aiwc-stat-label"><?php esc_html_e( 'Avg Messages / Session', 'ai-woo-chatbot' ); ?></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Popular Query Keywords', 'ai-woo-chatbot' ); ?></h2>
	<?php if ( ! empty( $popular ) ) : ?>
	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Keyword', 'ai-woo-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Occurrences', 'ai-woo-chatbot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $popular as $row ) : ?>
			<tr>
				<td><?php echo esc_html( $row['word'] ); ?></td>
				<td><?php echo esc_html( $row['count'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No analytics data yet. Chat activity will appear here once customers start using the widget.', 'ai-woo-chatbot' ); ?></p>
	<?php endif; ?>
</div>
