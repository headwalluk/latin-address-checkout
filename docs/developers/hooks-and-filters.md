# Hooks and filters

The plugin's public extension surface. Anything not listed here (functions, classes, stored
keys) is internal and may change in any release.

## `latinaddr_process_checkout`

Filter. Decides whether a checkout is converted and checked at all.

```php
apply_filters( 'latinaddr_process_checkout', bool $process, string $route, array $addresses );
```

- `$process`: `true` by default. Return `false` to leave the checkout completely alone: no
  conversion, no refusal, no note
- `$route`: `'classic'` for the shortcode checkout, `'block'` for the Checkout block
- `$addresses`: the values as the customer entered them, before conversion, keyed by
  `'billing'` and `'shipping'`, then by field (`first_name`, `last_name`, `company`,
  `address_1`, `address_2`, `city`, `state`, `postcode`)

It runs while the order is being placed, so `WC()->cart` and the chosen shipping methods
(`WC()->session->get( 'chosen_shipping_methods' )`) are available. The return value is read as
a boolean (`'no'`, `'0'` and `''` count as false).

Since 1.0.0.

## `latinaddr_blocked_message`

Filter. The message shown when the checkout is refused.

```php
apply_filters( 'latinaddr_blocked_message', string $message, string[] $field_labels, string $route );
```

- `$message`: the default, translated message. Plain text: any HTML is escaped
- `$field_labels`: the labels of the fields the customer needs to change, as the checkout
  form shows them for their country
- `$route`: `'classic'` or `'block'`

Returning an empty or non-string value falls back to the default message.

Since 1.0.0.

## `latinaddr_replacement_map`

Filter. The characters replaced before WordPress's `remove_accents()` runs.

```php
apply_filters( 'latinaddr_replacement_map', array $map );
```

- `$map`: an array of `character => replacement`. Keys and values are UTF-8 strings.
  Includes the fullwidth forms of ASCII

Entries that aren't a non-empty string key with a string value are dropped, and logged. A
non-array return value falls back to the default map. Replacements must themselves be
printable ASCII, or the field still can't be converted.

Since 1.0.0.

## `latinaddr_address_converted`

Action. Fires after converted fields are recorded on an order.

```php
do_action( 'latinaddr_address_converted', WC_Order $order, array $changes );
```

- `$order`: the order just placed
- `$changes`: a list of changes, each an array with `address_type` (`'billing'` or
  `'shipping'`), `field`, `original` and `converted`

It fires once per order and set of changes, at the same moment as the order note is written.
Use it to send the original values somewhere else, rather than reading the order note.

Since 1.0.0.

## `latinaddr_updater_enabled`

Filter. Return `false` to stop this plugin checking GitHub for updates. It affects this plugin
only.

Since 1.0.0.

## Store API error code: `latinaddr_non_latin_address`

When the Checkout block refuses a checkout, the Store API responds with HTTP 400 and this
error code. Front-end code that handles checkout errors can rely on it.

In the classic checkout, the same code is used for the validation error.

Since 1.0.0.
