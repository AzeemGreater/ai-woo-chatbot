<?php
/**
 * Plugin Name:       AI WooCommerce Chatbot
 * Plugin URI:        https://github.com/AzeemGreater/ai-woo-chatbot
 * Description:       AI-powered shopping assistant for WooCommerce — reads product details, guides customers, handles orders and FAQs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            AzeemGreater
 * License:           GPL-2.0-or-later
 * Text Domain:       ai-woo-chatbot
 * WC requires at least: 7.0
 * WC tested up to:      8.9
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'AIWC_VERSION',    '1.0.0' );
define( 'AIWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIWC_PLUGIN_FILE', __FILE__ );

// Autoload includes.
require_once AIWC_PLUGIN_DIR . 'includes/class-plugin.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-woocommerce.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-session.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-settings.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-lead-capture.php';
require_once AIWC_PLUGIN_DIR . 'includes/class-analytics.php';

/**
 * Returns the main plugin instance.
 *
 * @return AIWC_Plugin
 */
function aiwc() {
	return AIWC_Plugin::instance();
}

// Kick off the plugin.
aiwc();

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( 'AIWC_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AIWC_Plugin', 'deactivate' ) );
