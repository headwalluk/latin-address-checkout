# Extending

Recipes for the hooks in [hooks-and-filters.md](hooks-and-filters.md). Put them in a small
must-use plugin or a site-specific plugin.

## Only check orders that go by one carrier

If some shipping methods use a courier that accepts any address, skip the plugin for those.
This checks the chosen shipping method and only processes checkouts going by Royal Mail,
assumed here to be a flat-rate method with instance ID 3:

```php
/**
 * Only convert and check addresses for orders shipping by Royal Mail.
 *
 * @param mixed $process Whether to process the checkout.
 * @return bool Whether to process the checkout.
 */
function example_latinaddr_royal_mail_only( $process ): bool {
	$chosen_methods = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();

	return (bool) $process && in_array( 'flat_rate:3', $chosen_methods, true );
}
add_filter( 'latinaddr_process_checkout', 'example_latinaddr_royal_mail_only' );
```

Find a method's ID by hovering over it under **WooCommerce → Settings → Shipping**, or look at
the `value` of its radio button on the checkout page.

## Skip some destination countries

```php
/**
 * Leave addresses in Greece and Cyprus alone.
 *
 * @param mixed  $process   Whether to process the checkout.
 * @param string $route     Checkout route.
 * @param array  $addresses Addresses as entered.
 * @return bool Whether to process the checkout.
 */
function example_latinaddr_skip_countries( $process, $route, $addresses ): bool {
	$country = WC()->customer ? WC()->customer->get_shipping_country() : '';

	return (bool) $process && ! in_array( $country, array( 'GR', 'CY' ), true );
}
add_filter( 'latinaddr_process_checkout', 'example_latinaddr_skip_countries', 10, 3 );
```

## Change the message

```php
/**
 * Use the store's own wording for the refusal message.
 *
 * @param string   $message      Default message.
 * @param string[] $field_labels Fields to change.
 * @return string Message.
 */
function example_latinaddr_message( $message, $field_labels ): string {
	return sprintf(
		'Our courier needs your address in English letters. Please check: %s.',
		implode( ', ', $field_labels )
	);
}
add_filter( 'latinaddr_blocked_message', 'example_latinaddr_message', 10, 2 );
```

If the store is multilingual, wrap the text in your own translation function.

## Convert another character

```php
/**
 * Convert the Maltese letter ħ, which remove_accents() leaves alone.
 *
 * @param array $map Characters to replace.
 * @return array Characters to replace.
 */
function example_latinaddr_extra_characters( $map ): array {
	$map['ħ'] = 'h';
	$map['Ħ'] = 'H';

	return $map;
}
add_filter( 'latinaddr_replacement_map', 'example_latinaddr_extra_characters' );
```

## Copy the original address somewhere else

```php
/**
 * Keep the customer's original shipping address in order meta for a label integration.
 *
 * @param WC_Order $order   The order.
 * @param array    $changes Converted fields.
 */
function example_latinaddr_store_originals( $order, $changes ): void {
	$originals = array();

	foreach ( $changes as $change ) {
		if ( 'shipping' === $change['address_type'] ) {
			$originals[ $change['field'] ] = $change['original'];
		}
	}

	$order->update_meta_data( '_example_original_shipping_address', $originals );
	$order->save();
}
add_action( 'latinaddr_address_converted', 'example_latinaddr_store_originals', 10, 2 );
```
