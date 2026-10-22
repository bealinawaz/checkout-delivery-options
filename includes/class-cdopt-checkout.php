<?php
/**
 * Checkout field, fee, and order display.
 *
 * @package Checkout_Delivery_Options
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the delivery option field to the classic checkout and charges the matching fee.
 */
final class CDOPT_Checkout {

	const FIELD       = 'cdopt_delivery_option';
	const SESSION_KEY = 'cdopt_delivery_option';
	const META_ID     = '_cdopt_option_id';
	const META_LABEL  = '_cdopt_option_label';
	const META_FEE    = '_cdopt_option_fee';
	const META_FIELD  = '_cdopt_field_label';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_after_order_notes' ) );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'render_before_payment' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_from_order_review' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'update_from_checkout_post' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_fee' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_to_order' ) );
		add_action( 'woocommerce_cart_emptied', array( __CLASS__, 'clear_session' ) );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'order_item_totals' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'admin_order_display' ) );
	}

	/**
	 * Whether the field applies to the current cart.
	 *
	 * @param WC_Cart|null $cart Cart.
	 * @return bool
	 */
	private static function applies( $cart = null ) {
		$cart = $cart ? $cart : ( function_exists( 'WC' ) ? WC()->cart : null );
		if ( ! $cart || ! self::has_options() ) {
			return false;
		}
		$settings = CDOPT_Settings::get();
		$applies  = ! $settings['shipping_only'] || $cart->needs_shipping();

		/**
		 * Filters whether the delivery option field is shown and charged for the cart.
		 *
		 * @param bool    $applies Whether the field applies.
		 * @param WC_Cart $cart    Cart.
		 */
		return (bool) apply_filters( 'cdopt_field_applies', $applies, $cart );
	}

	/**
	 * Whether any delivery options are configured.
	 *
	 * @return bool
	 */
	private static function has_options() {
		return array() !== CDOPT_Settings::options();
	}

	/**
	 * Option ID saved in the customer session.
	 *
	 * @return string
	 */
	private static function chosen() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '';
		}
		$id = WC()->session->get( self::SESSION_KEY, '' );
		return ( is_string( $id ) && CDOPT_Settings::find( $id ) ) ? $id : '';
	}

	/**
	 * Saves a submitted option ID in the session. Unknown IDs clear the choice.
	 *
	 * @param mixed $value Submitted value.
	 */
	private static function remember( $value ) {
		if ( ! WC()->session ) {
			return;
		}
		$id = is_string( $value ) ? sanitize_key( $value ) : '';
		WC()->session->set( self::SESSION_KEY, CDOPT_Settings::find( $id ) ? $id : '' );
	}

	/**
	 * Fee amount for display, including tax when the store shows prices with tax.
	 *
	 * @param array $option Delivery option.
	 * @return float
	 */
	private static function display_fee( $option ) {
		$fee = (float) $option['fee'];
		if ( $fee > 0 && ! empty( $option['taxable'] ) && wc_tax_enabled() && WC()->cart && 'incl' === WC()->cart->get_tax_price_display_mode() ) {
			$rates = WC_Tax::get_rates( '', WC()->customer );
			$fee  += array_sum( WC_Tax::calc_tax( $fee, $rates, false ) );
		}
		return $fee;
	}

	/**
	 * Text for one choice: "Label (+ €5.00)", or just the label when free.
	 *
	 * @param array $option Delivery option.
	 * @param bool  $html   Whether to return formatted price HTML.
	 * @return string
	 */
	private static function choice_text( $option, $html = false ) {
		$fee = self::display_fee( $option );
		if ( $fee <= 0 ) {
			return $html ? esc_html( $option['label'] ) : $option['label'];
		}
		$price = wc_price( $fee );
		if ( ! $html ) {
			$price = html_entity_decode( wp_strip_all_tags( $price ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}
		/* translators: 1: delivery option name, 2: fee amount. */
		$format = __( '%1$s (+ %2$s)', 'checkout-delivery-options' );
		return $html ? sprintf( esc_html( $format ), esc_html( $option['label'] ), $price ) : sprintf( $format, $option['label'], $price );
	}

	/**
	 * Renders the field after the order notes when that position is selected.
	 */
	public static function render_after_order_notes() {
		if ( 'after_order_notes' === CDOPT_Settings::get()['position'] ) {
			self::render_field();
		}
	}

	/**
	 * Renders the field above the payment methods when that position is selected.
	 */
	public static function render_before_payment() {
		if ( 'before_payment' === CDOPT_Settings::get()['position'] ) {
			self::render_field();
		}
	}

	/**
	 * Renders the select or radio field.
	 */
	public static function render_field() {
		if ( ! self::applies() ) {
			return;
		}
		$settings = CDOPT_Settings::get();
		$options  = CDOPT_Settings::options();
		$chosen   = self::chosen();

		echo '<div class="cdopt-checkout">';

		if ( 'radio' === $settings['display'] ) {
			$desc_id = self::FIELD . '-description';
			?>
			<fieldset id="<?php echo esc_attr( self::FIELD ); ?>_field" class="form-row form-row-wide cdopt-field update_totals_on_change<?php echo $settings['required'] ? ' validate-required' : ''; ?>" role="radiogroup"<?php echo $settings['required'] ? ' aria-required="true"' : ''; ?><?php echo '' !== $settings['description'] ? ' aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?>>
				<legend class="cdopt-field__legend">
					<?php echo esc_html( $settings['label'] ); ?>
					<?php if ( $settings['required'] ) : ?>
						<abbr class="required" title="<?php esc_attr_e( 'required', 'checkout-delivery-options' ); ?>">*</abbr>
					<?php else : ?>
						<span class="optional"><?php esc_html_e( '(optional)', 'checkout-delivery-options' ); ?></span>
					<?php endif; ?>
				</legend>
				<?php if ( '' !== $settings['description'] ) : ?>
					<p id="<?php echo esc_attr( $desc_id ); ?>" class="description"><?php echo esc_html( $settings['description'] ); ?></p>
				<?php endif; ?>
				<span class="woocommerce-input-wrapper">
					<?php foreach ( $options as $id => $option ) : ?>
						<label class="cdopt-field__choice">
							<input type="radio" class="input-radio" name="<?php echo esc_attr( self::FIELD ); ?>" value="<?php echo esc_attr( $id ); ?>" <?php checked( $chosen, $id ); ?> />
							<?php echo wp_kses_post( self::choice_text( $option, true ) ); ?>
						</label>
					<?php endforeach; ?>
				</span>
			</fieldset>
			<?php
		} else {
			$choices = array( '' => __( 'Choose an option', 'checkout-delivery-options' ) );
			foreach ( $options as $id => $option ) {
				$choices[ $id ] = self::choice_text( $option );
			}
			woocommerce_form_field(
				self::FIELD,
				array(
					'type'        => 'select',
					'label'       => $settings['label'],
					'description' => $settings['description'],
					'required'    => $settings['required'],
					'class'       => array( 'form-row-wide', 'cdopt-field', 'update_totals_on_change' ),
					'options'     => $choices,
				),
				$chosen
			);
		}

		echo '</div>';
	}

	/**
	 * Loads the small stylesheet on the checkout page.
	 */
	public static function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! self::has_options() ) {
			return;
		}
		wp_enqueue_style( 'cdopt-checkout', plugin_dir_url( CDOPT_FILE ) . 'assets/checkout.css', array(), CDOPT_VERSION );
	}

	/**
	 * Reads the choice from the checkout form data that WooCommerce posts when it refreshes the order review.
	 *
	 * WooCommerce verifies its own update-order-review nonce before this action runs.
	 *
	 * @param string $posted_data Serialized checkout form.
	 */
	public static function update_from_order_review( $posted_data ) {
		if ( ! is_string( $posted_data ) || '' === $posted_data ) {
			return;
		}
		$data = array();
		wp_parse_str( $posted_data, $data );
		if ( array_key_exists( self::FIELD, $data ) ) {
			self::remember( wp_unslash( $data[ self::FIELD ] ) );
		}
	}

	/**
	 * Uses the submitted choice when the order is placed, so the fee always matches the form.
	 *
	 * WooCommerce verifies the checkout nonce before this action runs.
	 */
	public static function update_from_checkout_post() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce in WC_Checkout::process_checkout().
		if ( isset( $_POST[ self::FIELD ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in remember().
			self::remember( wp_unslash( $_POST[ self::FIELD ] ) );
		}
	}

	/**
	 * Adds the fee for the chosen option. The amount always comes from the settings, never from the request.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function add_fee( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! self::applies( $cart ) ) {
			return;
		}
		$option = CDOPT_Settings::find( self::chosen() );
		if ( ! $option || (float) $option['fee'] <= 0 ) {
			return;
		}
		$settings = CDOPT_Settings::get();
		$cart->add_fee(
			/* translators: 1: field label, such as "Delivery option", 2: chosen option name. */
			sprintf( __( '%1$s: %2$s', 'checkout-delivery-options' ), $settings['label'], $option['label'] ),
			(float) $option['fee'],
			! empty( $option['taxable'] ),
			''
		);
	}

	/**
	 * Validates the choice when the order is placed.
	 *
	 * @param array    $data   Posted checkout data.
	 * @param WP_Error $errors Validation errors.
	 */
	public static function validate( $data, $errors ) {
		if ( ! self::applies() ) {
			return;
		}
		$settings = CDOPT_Settings::get();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce in WC_Checkout::process_checkout().
		$value = isset( $_POST[ self::FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD ] ) ) : '';

		if ( '' === $value ) {
			if ( $settings['required'] ) {
				$errors->add(
					'cdopt_required',
					/* translators: %s: field label. */
					sprintf( __( '%s is a required field.', 'checkout-delivery-options' ), '<strong>' . esc_html( $settings['label'] ) . '</strong>' ),
					array( 'id' => self::FIELD )
				);
			}
			return;
		}
		if ( ! CDOPT_Settings::find( $value ) ) {
			$errors->add(
				'cdopt_invalid',
				/* translators: %s: field label. */
				sprintf( __( 'Please choose a valid %s.', 'checkout-delivery-options' ), '<strong>' . esc_html( $settings['label'] ) . '</strong>' ),
				array( 'id' => self::FIELD )
			);
		}
	}

	/**
	 * Saves the chosen option on the order.
	 *
	 * @param WC_Order $order Order being created.
	 */
	public static function save_to_order( $order ) {
		if ( ! self::applies() ) {
			return;
		}
		$option = CDOPT_Settings::find( self::chosen() );
		if ( ! $option ) {
			return;
		}
		$order->update_meta_data( self::META_ID, $option['id'] );
		$order->update_meta_data( self::META_LABEL, $option['label'] );
		$order->update_meta_data( self::META_FEE, wc_format_decimal( $option['fee'] ) );
		$order->update_meta_data( self::META_FIELD, CDOPT_Settings::get()['label'] );
	}

	/**
	 * Forgets the choice once the cart is emptied after an order.
	 */
	public static function clear_session() {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->__unset( self::SESSION_KEY );
		}
	}

	/**
	 * Label and value saved on an order.
	 *
	 * @param WC_Order $order Order.
	 * @return array|null Array with label and value, or null.
	 */
	private static function order_choice( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return null;
		}
		$value = (string) $order->get_meta( self::META_LABEL );
		if ( '' === $value ) {
			return null;
		}
		$label = (string) $order->get_meta( self::META_FIELD );
		return array(
			'label' => '' !== $label ? $label : CDOPT_Settings::get()['label'],
			'value' => $value,
			'fee'   => (float) $order->get_meta( self::META_FEE ),
		);
	}

	/**
	 * Shows free choices in order totals (emails, thank-you page, My Account).
	 * Paid choices already appear there as a fee line named after the option.
	 *
	 * @param array    $rows  Total rows.
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function order_item_totals( $rows, $order ) {
		$choice = self::order_choice( $order );
		if ( ! $choice || $choice['fee'] > 0 ) {
			return $rows;
		}
		$row = array(
			'cdopt_delivery_option' => array(
				'label' => $choice['label'] . ':',
				'value' => esc_html( $choice['value'] ),
			),
		);
		$position = array_search( 'order_total', array_keys( $rows ), true );
		if ( false === $position ) {
			return $rows + $row;
		}
		return array_slice( $rows, 0, $position, true ) + $row + array_slice( $rows, $position, null, true );
	}

	/**
	 * Shows the choice on the admin order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function admin_order_display( $order ) {
		$choice = self::order_choice( $order );
		if ( ! $choice ) {
			return;
		}
		?>
		<div class="cdopt-admin-order">
			<p>
				<strong><?php echo esc_html( $choice['label'] ); ?>:</strong><br />
				<?php echo esc_html( $choice['value'] ); ?>
				<?php if ( $choice['fee'] > 0 ) : ?>
					(<?php echo wp_kses_post( wc_price( $choice['fee'], array( 'currency' => $order->get_currency() ) ) ); ?>)
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
