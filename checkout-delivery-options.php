<?php
/**
 * Plugin Name:          Checkout Delivery Options for WooCommerce – Paid Delivery Choices & Fees
 * Plugin URI:           https://github.com/bealinawaz/checkout-delivery-options
 * Description:          Let customers pick a delivery option at checkout, such as standard, express, or a delivery day, and add the matching fee to the order.
 * Version:              2.0.0
 * Requires at least:    6.2
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Ali Nawaz
 * Author URI:           https://bealinawaz.com
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          checkout-delivery-options
 * WC requires at least: 7.1
 * WC tested up to:      11.2
 *
 * @package Checkout_Delivery_Options
 */

defined( 'ABSPATH' ) || exit;

define( 'CDOPT_VERSION', '2.0.0' );
define( 'CDOPT_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-cdopt-settings.php';
require_once __DIR__ . '/includes/class-cdopt-checkout.php';

// Orders are read and written through the WooCommerce CRUD API, so both order storage types work.
// The field is added with classic checkout hooks, so the Checkout block is declared incompatible.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CDOPT_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CDOPT_FILE, false );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		add_action( 'init', array( 'CDOPT_Settings', 'maybe_upgrade' ), 5 );
		CDOPT_Checkout::init();

		if ( is_admin() ) {
			require_once __DIR__ . '/includes/class-cdopt-admin.php';
			CDOPT_Admin::init();
		}
	}
);
