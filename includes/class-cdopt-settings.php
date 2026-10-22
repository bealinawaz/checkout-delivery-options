<?php
/**
 * Plugin settings and upgrade routine.
 *
 * @package Checkout_Delivery_Options
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads, validates, and migrates the plugin settings.
 */
final class CDOPT_Settings {

	const OPTION         = 'cdopt_settings';
	const GROUP          = 'cdopt';
	const VERSION_OPTION = 'cdopt_version';
	const LEGACY_OPTION  = 'woo-ship-data';
	const MAX_OPTIONS    = 50;

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'label'         => __( 'Delivery option', 'checkout-delivery-options' ),
			'description'   => '',
			'required'      => true,
			'display'       => 'select',
			'position'      => 'after_order_notes',
			'shipping_only' => true,
			'options'       => array(),
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved    = get_option( self::OPTION, array() );
		$settings = array_replace( self::defaults(), is_array( $saved ) ? $saved : array() );
		if ( '' === trim( (string) $settings['label'] ) ) {
			$settings['label'] = self::defaults()['label'];
		}
		if ( ! is_array( $settings['options'] ) ) {
			$settings['options'] = array();
		}
		return $settings;
	}

	/**
	 * Delivery options keyed by their ID.
	 *
	 * @return array
	 */
	public static function options() {
		$options = array();
		foreach ( self::get()['options'] as $option ) {
			if ( is_array( $option ) && ! empty( $option['id'] ) ) {
				$options[ $option['id'] ] = $option;
			}
		}
		return $options;
	}

	/**
	 * Finds one delivery option by ID.
	 *
	 * @param string $id Option ID.
	 * @return array|null
	 */
	public static function find( $id ) {
		$options = self::options();
		return ( is_string( $id ) && isset( $options[ $id ] ) ) ? $options[ $id ] : null;
	}

	/**
	 * Creates a new option ID.
	 *
	 * @return string
	 */
	public static function new_id() {
		return 'opt_' . strtolower( wp_generate_password( 8, false, false ) );
	}

	/**
	 * Sanitizes the settings form.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$clean = array(
			'label'         => isset( $input['label'] ) ? sanitize_text_field( $input['label'] ) : $defaults['label'],
			'description'   => isset( $input['description'] ) ? sanitize_text_field( $input['description'] ) : '',
			'required'      => ! empty( $input['required'] ),
			'display'       => ( isset( $input['display'] ) && 'radio' === $input['display'] ) ? 'radio' : 'select',
			'position'      => ( isset( $input['position'] ) && 'before_payment' === $input['position'] ) ? 'before_payment' : 'after_order_notes',
			'shipping_only' => ! empty( $input['shipping_only'] ),
			'options'       => array(),
		);

		if ( '' === $clean['label'] ) {
			$clean['label'] = $defaults['label'];
		}

		$rows = ( isset( $input['options'] ) && is_array( $input['options'] ) ) ? $input['options'] : array();
		$seen = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
			if ( '' === $label ) {
				continue;
			}
			$id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = self::new_id();
			}
			$seen[ $id ] = true;

			$fee = isset( $row['fee'] ) ? wc_format_decimal( sanitize_text_field( $row['fee'] ) ) : '0';
			if ( '' === $fee || ! is_numeric( $fee ) || (float) $fee < 0 ) {
				$fee = '0';
			}

			$clean['options'][] = array(
				'id'      => $id,
				'label'   => $label,
				'fee'     => $fee,
				'taxable' => ! empty( $row['taxable'] ),
			);
			if ( count( $clean['options'] ) >= self::MAX_OPTIONS ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Runs once after an update: converts the 1.x option list (price => label) into delivery options.
	 */
	public static function maybe_upgrade() {
		if ( version_compare( (string) get_option( self::VERSION_OPTION, '0' ), CDOPT_VERSION, '>=' ) ) {
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION, null );
		if ( is_array( $legacy ) && false === get_option( self::OPTION, false ) ) {
			$settings = self::defaults();
			foreach ( $legacy as $price => $label ) {
				$label = sanitize_text_field( (string) $label );
				if ( '' === $label ) {
					continue;
				}
				$fee                   = wc_format_decimal( (string) $price );
				$settings['options'][] = array(
					'id'      => self::new_id(),
					'label'   => $label,
					'fee'     => ( is_numeric( $fee ) && (float) $fee > 0 ) ? $fee : '0',
					'taxable' => false,
				);
			}
			update_option( self::OPTION, $settings );
		}
		if ( null !== $legacy ) {
			delete_option( self::LEGACY_OPTION );
		}

		update_option( self::VERSION_OPTION, CDOPT_VERSION );
	}
}
