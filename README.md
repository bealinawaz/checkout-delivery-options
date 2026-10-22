# Checkout Delivery Options for WooCommerce

Let customers pick a paid delivery option at WooCommerce checkout, such as standard, express, next-day, or Saturday delivery, and add the matching fee to the order automatically. The fee comes from your settings on the server, the checkout totals refresh as soon as the customer chooses, and the choice is saved on the order.

![Tested up to WordPress 7.1](https://img.shields.io/badge/WordPress-7.1-blue)
![WooCommerce 7.1+](https://img.shields.io/badge/WooCommerce-7.1%2B-96588a)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License GPLv2 or later](https://img.shields.io/badge/license-GPLv2%2B-green)

## Features

- **Paid or free choices**, each with its own fee and an optional taxable flag.
- **Dropdown or radio buttons**, shown after the order notes or above the payment methods.
- **Live totals** through WooCommerce's own `update_checkout` refresh; no custom AJAX endpoint.
- **Server-side pricing:** only the option ID is posted; the fee is looked up from the settings.
- **Validation:** required check and rejection of unknown options.
- **Order records:** saved as order meta (HPOS compatible) and shown in the admin, emails, thank-you page, and My Account.
- **Sortable settings repeater** in vanilla JavaScript with keyboard-friendly move buttons.
- **Migration** from the 1.x `woo-ship-data` option.

## Setup

1. Activate WooCommerce and the plugin.
2. Open **WooCommerce → Delivery Options** and add your options.
3. Use the classic checkout (`[woocommerce_checkout]` shortcode). The Checkout block is not supported, and the plugin declares that to WooCommerce.

## How it works

- The field wrapper has WooCommerce's `update_totals_on_change` class, so changing it triggers `update_checkout`.
- `woocommerce_checkout_update_order_review` parses the serialized `post_data` and stores the option ID in the WooCommerce session.
- `woocommerce_cart_calculate_fees` adds the fee for the stored option.
- `woocommerce_after_checkout_validation` validates the posted value, and `woocommerce_checkout_create_order` saves it to the order.

Order meta keys: `_cdopt_option_id`, `_cdopt_option_label`, `_cdopt_option_fee`, `_cdopt_field_label`.

## Hooks

```php
// Show the field only for carts over 50.
add_filter( 'cdopt_field_applies', function ( $applies, $cart ) {
	return $applies && $cart->get_subtotal() >= 50;
}, 10, 2 );
```

## Project structure

```
checkout-delivery-options.php      Plugin header, bootstrap, WooCommerce feature declarations
includes/class-cdopt-settings.php  Settings defaults, sanitizing, 1.x migration
includes/class-cdopt-checkout.php  Checkout field, session, fee, validation, order display
includes/class-cdopt-admin.php     WooCommerce → Delivery Options screen and plugin links
assets/admin.js                    Sortable options repeater
assets/admin.css, checkout.css     Styles
uninstall.php                      Removes settings (order data is kept)
```

## Custom development

Need delivery dates and time slots, fees by postcode or cart total, or a custom checkout flow? [Hire Ali Nawaz for custom WooCommerce work](https://bealinawaz.com/contact-me/?utm_source=github&utm_medium=readme&utm_campaign=checkout-delivery-options).

## License

GPLv2 or later. See [LICENSE](LICENSE).
