<?php
/**
 * Settings screen under the WooCommerce menu.
 *
 * @package Checkout_Delivery_Options
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin settings page, plugin links, and notices.
 */
final class CDOPT_Admin {

	const PAGE        = 'cdopt-settings';
	const CAPABILITY  = 'manage_woocommerce';
	const AUTHOR_URL  = 'https://bealinawaz.com/';
	const CONTACT_URL = 'https://bealinawaz.com/contact-me/';
	const DONATE_URL  = 'https://www.buymeacoffee.com/bealinawaz';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 60 );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'option_page_capability_' . CDOPT_Settings::GROUP, array( __CLASS__, 'capability' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CDOPT_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	/**
	 * Lets shop managers save the settings.
	 *
	 * @return string
	 */
	public static function capability() {
		return self::CAPABILITY;
	}

	/**
	 * Adds the settings page under WooCommerce.
	 */
	public static function add_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Checkout Delivery Options', 'checkout-delivery-options' ),
			__( 'Delivery Options', 'checkout-delivery-options' ),
			self::CAPABILITY,
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Registers the settings option.
	 */
	public static function register_setting() {
		register_setting(
			CDOPT_Settings::GROUP,
			CDOPT_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'CDOPT_Settings', 'sanitize' ),
			)
		);
	}

	/**
	 * Loads the repeater script and styles on the settings page only.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}
		$url = plugin_dir_url( CDOPT_FILE ) . 'assets/';
		wp_enqueue_style( 'cdopt-admin', $url . 'admin.css', array(), CDOPT_VERSION );
		wp_enqueue_script( 'cdopt-admin', $url . 'admin.js', array( 'wp-i18n', 'wp-a11y' ), CDOPT_VERSION, true );
		wp_set_script_translations( 'cdopt-admin', 'checkout-delivery-options' );
	}

	/**
	 * Adds a Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'checkout-delivery-options' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds custom development and donation links under the plugin description.
	 *
	 * @param array  $links Plugin meta links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public static function row_meta( $links, $file ) {
		if ( plugin_basename( CDOPT_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( self::CONTACT_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Custom development', 'checkout-delivery-options' ) . '</a>';
		$links[] = '<a href="' . esc_url( self::DONATE_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Buy me a coffee', 'checkout-delivery-options' ) . '</a>';
		return $links;
	}

	/**
	 * Settings field name.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private static function name( $key ) {
		return CDOPT_Settings::OPTION . '[' . $key . ']';
	}

	/**
	 * Whether the checkout page uses the Checkout block, which this plugin does not support.
	 *
	 * @return bool
	 */
	private static function uses_checkout_block() {
		$page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
		return $page_id > 0 && has_block( 'woocommerce/checkout', $page_id );
	}

	/**
	 * Renders one repeater row.
	 *
	 * @param string|int $index  Row index, or the template placeholder.
	 * @param array      $option Option values.
	 */
	private static function render_row( $index, $option ) {
		$base = self::name( 'options' ) . '[' . $index . ']';
		$id   = 'cdopt-option-' . $index;
		?>
		<tr class="cdopt-row" draggable="true">
			<td class="cdopt-row__handle" aria-hidden="true"><span class="dashicons dashicons-menu"></span></td>
			<td>
				<input type="hidden" class="cdopt-row__id" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $option['id'] ); ?>" />
				<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-label' ); ?>"><?php esc_html_e( 'Option name', 'checkout-delivery-options' ); ?></label>
				<input type="text" class="regular-text cdopt-row__label" id="<?php echo esc_attr( $id . '-label' ); ?>" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( $option['label'] ); ?>" placeholder="<?php esc_attr_e( 'Express delivery', 'checkout-delivery-options' ); ?>" />
			</td>
			<td>
				<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-fee' ); ?>"><?php esc_html_e( 'Fee', 'checkout-delivery-options' ); ?></label>
				<input type="text" inputmode="decimal" class="small-text cdopt-row__fee" id="<?php echo esc_attr( $id . '-fee' ); ?>" name="<?php echo esc_attr( $base . '[fee]' ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $option['fee'] ) ); ?>" placeholder="0" />
			</td>
			<td class="cdopt-row__center">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-taxable' ); ?>"><?php esc_html_e( 'Taxable', 'checkout-delivery-options' ); ?></label>
				<input type="checkbox" id="<?php echo esc_attr( $id . '-taxable' ); ?>" name="<?php echo esc_attr( $base . '[taxable]' ); ?>" value="1" <?php checked( ! empty( $option['taxable'] ) ); ?> />
			</td>
			<td class="cdopt-row__actions">
				<button type="button" class="button cdopt-move" data-direction="up"><span aria-hidden="true">&uarr;</span><span class="screen-reader-text"><?php esc_html_e( 'Move up', 'checkout-delivery-options' ); ?></span></button>
				<button type="button" class="button cdopt-move" data-direction="down"><span aria-hidden="true">&darr;</span><span class="screen-reader-text"><?php esc_html_e( 'Move down', 'checkout-delivery-options' ); ?></span></button>
				<button type="button" class="button cdopt-remove"><?php esc_html_e( 'Remove', 'checkout-delivery-options' ); ?></button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the settings page.
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$s       = CDOPT_Settings::get();
		$options = $s['options'];
		$empty   = array(
			'id'      => '',
			'label'   => '',
			'fee'     => '',
			'taxable' => false,
		);
		?>
		<div class="wrap cdopt-settings">
			<h1><?php esc_html_e( 'Checkout Delivery Options', 'checkout-delivery-options' ); ?></h1>
			<p><?php esc_html_e( 'Customers choose one of these options at checkout, and its fee is added to the order total.', 'checkout-delivery-options' ); ?></p>

			<?php if ( self::uses_checkout_block() ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'Your checkout page uses the Checkout block. This plugin works with the classic checkout, so the field will not appear. Replace the Checkout block with the [woocommerce_checkout] shortcode (or the "Classic Checkout" block) to use it.', 'checkout-delivery-options' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php settings_fields( CDOPT_Settings::GROUP ); ?>

				<h2><?php esc_html_e( 'Delivery options', 'checkout-delivery-options' ); ?></h2>
				<p class="description" id="cdopt-options-help"><?php esc_html_e( 'Enter fees without currency symbols. Use 0 for a free option. Drag rows or use the arrow buttons to change the order.', 'checkout-delivery-options' ); ?></p>
				<table class="widefat cdopt-table" aria-describedby="cdopt-options-help">
					<thead>
						<tr>
							<th scope="col" class="cdopt-table__handle"><span class="screen-reader-text"><?php esc_html_e( 'Order', 'checkout-delivery-options' ); ?></span></th>
							<th scope="col"><?php esc_html_e( 'Option name', 'checkout-delivery-options' ); ?></th>
							<?php /* translators: %s: currency symbol. */ ?>
							<th scope="col"><?php echo esc_html( sprintf( __( 'Fee (%s)', 'checkout-delivery-options' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ); ?></th>
							<th scope="col" class="cdopt-row__center"><?php esc_html_e( 'Taxable', 'checkout-delivery-options' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'checkout-delivery-options' ); ?></span></th>
						</tr>
					</thead>
					<tbody id="cdopt-rows">
						<?php
						foreach ( array_values( $options ) as $index => $option ) {
							self::render_row( $index, array_replace( $empty, $option ) );
						}
						?>
					</tbody>
				</table>
				<template id="cdopt-row-template"><?php self::render_row( '__INDEX__', $empty ); ?></template>
				<p><button type="button" class="button" id="cdopt-add"><?php esc_html_e( 'Add option', 'checkout-delivery-options' ); ?></button></p>

				<h2><?php esc_html_e( 'Checkout field', 'checkout-delivery-options' ); ?></h2>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th scope="row"><label for="cdopt-label"><?php esc_html_e( 'Field label', 'checkout-delivery-options' ); ?></label></th>
						<td><input id="cdopt-label" class="regular-text" type="text" name="<?php echo esc_attr( self::name( 'label' ) ); ?>" value="<?php echo esc_attr( $s['label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="cdopt-description"><?php esc_html_e( 'Help text', 'checkout-delivery-options' ); ?></label></th>
						<td>
							<input id="cdopt-description" class="large-text" type="text" name="<?php echo esc_attr( self::name( 'description' ) ); ?>" value="<?php echo esc_attr( $s['description'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional. Shown below the label, for example "Orders placed before 2 pm ship the same day."', 'checkout-delivery-options' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Required', 'checkout-delivery-options' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::name( 'required' ) ); ?>" value="1" <?php checked( $s['required'] ); ?> /> <?php esc_html_e( 'Customers must choose an option to place the order', 'checkout-delivery-options' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Display as', 'checkout-delivery-options' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Display as', 'checkout-delivery-options' ); ?></legend>
								<label><input type="radio" name="<?php echo esc_attr( self::name( 'display' ) ); ?>" value="select" <?php checked( $s['display'], 'select' ); ?> /> <?php esc_html_e( 'Dropdown', 'checkout-delivery-options' ); ?></label><br />
								<label><input type="radio" name="<?php echo esc_attr( self::name( 'display' ) ); ?>" value="radio" <?php checked( $s['display'], 'radio' ); ?> /> <?php esc_html_e( 'Radio buttons', 'checkout-delivery-options' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'checkout-delivery-options' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Position', 'checkout-delivery-options' ); ?></legend>
								<label><input type="radio" name="<?php echo esc_attr( self::name( 'position' ) ); ?>" value="after_order_notes" <?php checked( $s['position'], 'after_order_notes' ); ?> /> <?php esc_html_e( 'After the order notes', 'checkout-delivery-options' ); ?></label><br />
								<label><input type="radio" name="<?php echo esc_attr( self::name( 'position' ) ); ?>" value="before_payment" <?php checked( $s['position'], 'before_payment' ); ?> /> <?php esc_html_e( 'Above the payment methods', 'checkout-delivery-options' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Shipping only', 'checkout-delivery-options' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::name( 'shipping_only' ) ); ?>" value="1" <?php checked( $s['shipping_only'] ); ?> /> <?php esc_html_e( 'Only show the field when the cart needs shipping (hide it for virtual and downloadable orders)', 'checkout-delivery-options' ); ?></label></td>
					</tr>
				</tbody></table>

				<?php submit_button(); ?>
			</form>
			<?php self::render_support(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the author box with support, custom work, and donation links.
	 */
	private static function render_support() {
		?>
		<div class="cdopt-support">
			<h2><?php esc_html_e( 'Need help or a custom version?', 'checkout-delivery-options' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: link to the author's website. */
					esc_html__( 'Checkout Delivery Options is free and built by Ali Nawaz, a WordPress and WooCommerce developer at %s. Need delivery date and time slot pickers, fees by postcode or cart total, or a custom checkout flow?', 'checkout-delivery-options' ),
					'<a href="' . esc_url( self::AUTHOR_URL ) . '" target="_blank" rel="noopener">bealinawaz.com</a>'
				);
				?>
			</p>
			<p class="cdopt-support__actions">
				<a class="button button-primary" href="<?php echo esc_url( self::CONTACT_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Hire me for custom work', 'checkout-delivery-options' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::AUTHOR_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit bealinawaz.com', 'checkout-delivery-options' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::DONATE_URL ); ?>" target="_blank" rel="noopener">&#9749; <?php esc_html_e( 'Buy me a coffee', 'checkout-delivery-options' ); ?></a>
			</p>
		</div>
		<?php
	}
}
