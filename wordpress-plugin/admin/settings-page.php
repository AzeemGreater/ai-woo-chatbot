<?php
/**
 * Admin settings page template.
 *
 * @package AI_WooCommerce_Chatbot
 */

defined( 'ABSPATH' ) || exit;

// Handle form save
if ( isset( $_POST['aiwc_save_settings'] ) && check_admin_referer( 'aiwc_settings_nonce' ) ) {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	aiwc()->settings->save( aiwc()->settings->sanitize( wp_unslash( $_POST ) ) );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'ai-woo-chatbot' ) . '</p></div>';
}

$s = aiwc()->settings;
?>
<div class="wrap aiwc-admin">
	<h1><?php esc_html_e( 'AI WooCommerce Chatbot — Settings', 'ai-woo-chatbot' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="#general"    class="nav-tab nav-tab-active"><?php esc_html_e( 'General', 'ai-woo-chatbot' ); ?></a>
		<a href="#ai-config"  class="nav-tab"><?php esc_html_e( 'AI Configuration', 'ai-woo-chatbot' ); ?></a>
		<a href="#appearance" class="nav-tab"><?php esc_html_e( 'Appearance', 'ai-woo-chatbot' ); ?></a>
		<a href="#behaviour"  class="nav-tab"><?php esc_html_e( 'Behaviour', 'ai-woo-chatbot' ); ?></a>
	</nav>

	<form method="post">
		<?php wp_nonce_field( 'aiwc_settings_nonce' ); ?>

		<!-- General -->
		<div id="general" class="aiwc-tab-content">
			<h2><?php esc_html_e( 'General', 'ai-woo-chatbot' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Bot Name', 'ai-woo-chatbot' ); ?></th>
					<td><input type="text" name="bot_name" value="<?php echo esc_attr( $s->get( 'bot_name', 'ShopBot' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Welcome Message', 'ai-woo-chatbot' ); ?></th>
					<td><textarea name="welcome_message" rows="3" class="large-text"><?php echo esc_textarea( $s->get( 'welcome_message', "Hi! 👋 I'm your shopping assistant. How can I help you today?" ) ); ?></textarea></td>
				</tr>
			</table>
		</div>

		<!-- AI Configuration -->
		<div id="ai-config" class="aiwc-tab-content" style="display:none;">
			<h2><?php esc_html_e( 'AI Configuration', 'ai-woo-chatbot' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Backend API URL', 'ai-woo-chatbot' ); ?></th>
					<td>
						<input type="url" name="backend_url" value="<?php echo esc_attr( $s->get( 'backend_url', '' ) ); ?>" class="large-text" placeholder="http://localhost:8000" />
						<p class="description"><?php esc_html_e( 'URL of the Python FastAPI backend.', 'ai-woo-chatbot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'OpenAI API Key', 'ai-woo-chatbot' ); ?></th>
					<td>
						<input type="password" name="openai_api_key" value="<?php echo esc_attr( $s->get( 'openai_api_key', '' ) ); ?>" class="large-text" autocomplete="off" />
						<p class="description"><?php esc_html_e( 'Optional: stored securely. Used by the backend, not exposed on the frontend.', 'ai-woo-chatbot' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Appearance -->
		<div id="appearance" class="aiwc-tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Appearance', 'ai-woo-chatbot' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Primary Color', 'ai-woo-chatbot' ); ?></th>
					<td><input type="color" name="primary_color" value="<?php echo esc_attr( $s->get( 'primary_color', '#4f46e5' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Widget Position', 'ai-woo-chatbot' ); ?></th>
					<td>
						<select name="position">
							<option value="bottom-right" <?php selected( $s->get( 'position', 'bottom-right' ), 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'ai-woo-chatbot' ); ?></option>
							<option value="bottom-left"  <?php selected( $s->get( 'position', 'bottom-right' ), 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'ai-woo-chatbot' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<!-- Behaviour -->
		<div id="behaviour" class="aiwc-tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Behaviour', 'ai-woo-chatbot' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Exit Intent', 'ai-woo-chatbot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_exit_intent" value="1" <?php checked( $s->get( 'enable_exit_intent', true ) ); ?> />
							<?php esc_html_e( 'Show chat when visitor is about to leave', 'ai-woo-chatbot' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Cart Recovery', 'ai-woo-chatbot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_cart_recovery" value="1" <?php checked( $s->get( 'enable_cart_recovery', true ) ); ?> />
							<?php esc_html_e( 'Nudge users with items in cart after inactivity', 'ai-woo-chatbot' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Lead Capture', 'ai-woo-chatbot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_lead_capture" value="1" <?php checked( $s->get( 'enable_lead_capture', true ) ); ?> />
							<?php esc_html_e( 'Ask for email/name during chat', 'ai-woo-chatbot' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'ai-woo-chatbot' ), 'primary', 'aiwc_save_settings' ); ?>
	</form>
</div>

<script>
(function() {
	var tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
	var contents = document.querySelectorAll('.aiwc-tab-content');
	tabs.forEach(function(tab) {
		tab.addEventListener('click', function(e) {
			e.preventDefault();
			tabs.forEach(function(t) { t.classList.remove('nav-tab-active'); });
			contents.forEach(function(c) { c.style.display = 'none'; });
			tab.classList.add('nav-tab-active');
			var target = tab.getAttribute('href').substring(1);
			document.getElementById(target).style.display = 'block';
		});
	});
})();
</script>
