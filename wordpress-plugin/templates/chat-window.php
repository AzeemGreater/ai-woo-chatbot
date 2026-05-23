<?php
/**
 * Chat widget HTML injected into the site footer.
 * All dynamic text is escaped before output.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- AI WooCommerce Chatbot Widget -->
<div id="aiwc-wrapper" role="region" aria-label="<?php esc_attr_e( 'Chat with ShopBot', 'ai-woo-chatbot' ); ?>">

	<!-- Toggle bubble -->
	<button id="aiwc-toggle-btn" aria-label="<?php esc_attr_e( 'Open chat', 'ai-woo-chatbot' ); ?>" aria-expanded="false">
		<img src="<?php echo esc_url( AIWC_PLUGIN_URL . 'assets/images/bot-avatar.svg' ); ?>" alt="" width="32" height="32" />
		<span id="aiwc-unread-badge" class="aiwc-badge" style="display:none;">1</span>
	</button>

	<!-- Chat window -->
	<div id="aiwc-chat-window" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Chat window', 'ai-woo-chatbot' ); ?>" style="display:none;">

		<!-- Header -->
		<div id="aiwc-header">
			<img src="<?php echo esc_url( AIWC_PLUGIN_URL . 'assets/images/bot-avatar.svg' ); ?>" alt="" class="aiwc-header-avatar" />
			<div class="aiwc-header-info">
				<strong id="aiwc-bot-name"><?php echo esc_html( aiwc()->settings->get( 'bot_name', 'ShopBot' ) ); ?></strong>
				<span class="aiwc-online-status"><?php esc_html_e( 'Online', 'ai-woo-chatbot' ); ?></span>
			</div>
			<button id="aiwc-close-btn" aria-label="<?php esc_attr_e( 'Close chat', 'ai-woo-chatbot' ); ?>">&times;</button>
		</div>

		<!-- Messages -->
		<div id="aiwc-messages" role="log" aria-live="polite" aria-relevant="additions">
			<!-- Populated by chat-widget.js -->
		</div>

		<!-- Typing indicator -->
		<div id="aiwc-typing" style="display:none;" aria-hidden="true">
			<span></span><span></span><span></span>
		</div>

		<!-- Quick replies -->
		<div id="aiwc-quick-replies" aria-label="<?php esc_attr_e( 'Quick reply options', 'ai-woo-chatbot' ); ?>">
			<!-- Populated by chat-widget.js -->
		</div>

		<!-- Input area -->
		<div id="aiwc-input-area">
			<input
				type="text"
				id="aiwc-input"
				placeholder="<?php esc_attr_e( 'Type a message…', 'ai-woo-chatbot' ); ?>"
				maxlength="500"
				autocomplete="off"
				aria-label="<?php esc_attr_e( 'Chat message input', 'ai-woo-chatbot' ); ?>"
			/>
			<button id="aiwc-send-btn" aria-label="<?php esc_attr_e( 'Send message', 'ai-woo-chatbot' ); ?>">
				&#9658;
			</button>
		</div>

	</div><!-- /#aiwc-chat-window -->

</div><!-- /#aiwc-wrapper -->
