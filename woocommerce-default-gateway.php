<?php
/*
 * Plugin Name: WooCommerce Default Gateway
 * Description: Define the default gateway pre-selected on checkout page.
 * Version: 0.0.1
 * Author: Shazzad Hossain Khan
 * Requires at least: 5.4.2
 * Tested up to: 5.4.2
 * WC requires at least: 4.0.0
 * WC tested up to: 4.2.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define base file
if ( ! defined( 'WCDG_PLUGIN_FILE' ) ) {
	define( 'WCDG_PLUGIN_FILE', __FILE__ );
}

/**
 * WooCommerce missing fallback notice.
 *
 * @return string
 */
function wcdg_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'WooCommerce Default Gateway requires WooCommerce to be installed and active. You can download %s here.', 'stcpay' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * WooCommerce version fallback notice.
 *
 * @return string
 */
function wcdg_version_wc_notice() {
	echo '<div class="error"><p><strong>' . esc_html__( 'WooCommerce Default Gateway requires mimumum WooCommerce 4.0.0. Please upgrade.', 'stcpay' ) . '</strong></p></div>';
}

/**
 * Intialize everything after plugins_loaded action
 */
add_action( 'plugins_loaded', 'wcdg_init', 5 );
function wcdg_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcdg_missing_wc_notice' );
		return;
	}

	if ( version_compare( WC_VERSION, '4.0.0', '<') ) {
		add_action( 'admin_notices', 'wcdg_version_wc_notice' );
		return;
	}

	// Load the main plug class
	if ( ! class_exists( 'WC_Default_Gateway' ) ) {
		require dirname( __FILE__ ) . '/class-wc-default-gateway.php';
	}

	wcdg();
}

/**
 * Plugin instance
 *
 * @return WC_Default_Gateway Main class instance.
 */
function wcdg() {
	return WC_Default_Gateway::get_instance();
}
