=== Checkout Delivery Options for WooCommerce – Paid Delivery Choices & Fees ===
Contributors: imalinawaz
Donate link: https://www.buymeacoffee.com/bealinawaz
Tags: checkout fields, delivery options, checkout fee, extra fee, woocommerce checkout
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a delivery option field to WooCommerce checkout, such as express, standard, or Saturday delivery, and charge the matching fee automatically.

== Description ==

Checkout Delivery Options adds a **delivery option field to the WooCommerce checkout** with an **extra fee for each choice**. Offer standard, express, next-day, Saturday, or gift-wrapped delivery, and the fee for the option your customer picks is added to the order total straight away.

Use it when your shipping methods are fine but you want to sell a paid upgrade on top: a faster dispatch, a delivery day, a time window, or an insurance option. Each choice shows its price in the field, for example "Express (+ €7.50)", and the checkout totals update as soon as the customer changes it.

**Features**

* **Paid or free delivery choices:** add as many options as you need, each with its own fee. Use 0 for a free option.
* **Dropdown or radio buttons:** pick the style that fits your checkout.
* **Two positions:** after the order notes, or right above the payment methods.
* **Live totals:** the fee appears in the order review as soon as the customer chooses, using WooCommerce's own checkout refresh.
* **Tax aware:** mark a fee as taxable and it is taxed like any other WooCommerce fee. Prices in the field include tax when your store shows prices with tax.
* **Required or optional:** make customers choose before they can place the order, with a clear error if they forget.
* **Safe by design:** the fee always comes from your settings, never from the browser, and the submitted option is checked on the server.
* **Saved on the order:** the choice appears on the admin order screen, in order emails, on the thank-you page, and in My Account.
* **Easy to manage:** a sortable list of options with add, remove, and move buttons that work with mouse and keyboard.
* **HPOS ready:** works with WooCommerce High-Performance Order Storage.

**Classic checkout only**

The field is added with the classic checkout hooks. If your checkout page uses the Checkout block, switch it to the `[woocommerce_checkout]` shortcode or the Classic Checkout block. The settings page warns you when the block is in use. The plugin declares itself incompatible with the Checkout block, so WooCommerce shows the same warning.

**Custom development**

Need delivery dates and time slots, fees based on postcode, weight, or cart total, or a custom checkout flow? The plugin is built and maintained by Ali Nawaz, a WordPress and WooCommerce developer. [Get in touch for custom work](https://bealinawaz.com/contact-me/?utm_source=wordpress.org&utm_medium=plugin-readme&utm_campaign=checkout-delivery-options).

== Installation ==

1. Install and activate WooCommerce and this plugin.
2. Go to **WooCommerce → Delivery Options**.
3. Add your options, for example "Standard" with a fee of 0 and "Express" with a fee of 7.50.
4. Choose the field label, dropdown or radio buttons, and the position, then save.
5. Make sure your checkout page uses the classic checkout (`[woocommerce_checkout]`).

== Frequently Asked Questions ==

= How do I add an extra fee at WooCommerce checkout based on the customer's choice? =

Add each choice under WooCommerce → Delivery Options with its fee. When the customer picks one at checkout, the fee is added to the order as a fee line named after the field and the option, such as "Delivery option: Express".

= Is this a shipping method? =

No. It works alongside your shipping methods and zones. Shipping is still calculated by WooCommerce; this plugin adds a separate fee for the delivery option the customer chooses.

= Does it work with the WooCommerce Checkout block? =

Not yet. The Checkout block cannot refresh fees from a custom field without extra JavaScript, so the plugin supports the classic checkout only and tells WooCommerce so. Use the `[woocommerce_checkout]` shortcode on your checkout page.

= Can a customer change the fee by editing the page? =

No. The form only sends the ID of the chosen option. The amount is looked up from your settings on the server, and unknown options are rejected.

= Are fees taxed? =

Only when you tick "Taxable" for that option. Taxable fees use the standard tax rate for the customer's location.

= Where do I see the customer's choice? =

On the order edit screen below the shipping address, in the order emails, on the thank-you page, and in My Account. Paid choices also appear as a fee line in the order totals.

= Can I hide the field for downloadable products? =

Yes. "Shipping only" is on by default, so the field is hidden when nothing in the cart needs shipping. Developers can also use the `cdopt_field_applies` filter.

= I used the old "Woo Shipping Days" version. Are my options kept? =

Yes. On update, your price and label pairs are converted into delivery options and the old setting is removed.

== Changelog ==

= 2.0.0 =
* Complete rewrite, renamed from Woo BB Extra Fields to Checkout Delivery Options for WooCommerce.
* New: settings page under WooCommerce with a sortable list of options (name, fee, taxable).
* New: field label, help text, required setting, dropdown or radio buttons, and two field positions.
* New: fees are taxable per option and shown with tax when the store displays prices with tax.
* New: the choice is saved on the order and shown in the admin, emails, thank-you page, and My Account.
* New: High-Performance Order Storage (HPOS) compatibility.
* Fixed: options with the same price no longer overwrite each other.
* Fixed: the fee is read from the settings on the server instead of trusting the browser.
* Security: nonces, capability checks, sanitized input, and escaped output throughout. The custom AJAX endpoint and the external Font Awesome stylesheet were removed.
* Your existing options are migrated automatically and the old `woo-ship-data` setting is deleted.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.0 =
Complete rewrite. Your options are migrated to the new settings page under WooCommerce → Delivery Options. Classic checkout only.
