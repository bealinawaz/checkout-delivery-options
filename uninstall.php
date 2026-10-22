<?php
/**
 * Removes the plugin settings. Delivery choices saved on orders are kept,
 * because they are part of the order record.
 *
 * @package Checkout_Delivery_Options
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'cdopt_settings' );
delete_option( 'cdopt_version' );
delete_option( 'woo-ship-data' );
