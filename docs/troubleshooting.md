# Troubleshooting

## A customer says they can't check out

They'll have seen a message asking them to re-enter one or more address fields in Latin
letters. Usually the address is in a script the carrier can't print, such as Cyrillic, Greek
or Chinese. Ask them for the address as they'd write it for an international parcel. Most
countries that use another script have an official way of writing addresses in Latin letters.

If the field looks like ordinary Latin text, there's probably a character in it that the
plugin doesn't know how to convert. See the next section.

## An address that looks fine is refused

Some characters look like Latin letters but aren't, such as a Cyrillic `а` in place of a Latin
`a`. Copying an address from an email or a web page can bring these in, along with unusual
symbols.

To find the character, paste the value into a tool that shows Unicode code points. If it has a
sensible ASCII equivalent, a developer can add it with the `latinaddr_replacement_map` filter
(see [hooks and filters](developers/hooks-and-filters.md)). Please also
[open an issue](https://github.com/headwalluk/latin-address-checkout/issues) with the code
point, so it can go into the plugin for everyone.

## Some orders go by a courier that accepts any address

Then you probably don't want those orders stopped. A developer can switch the plugin off for
particular orders, such as one shipping method, with the `latinaddr_process_checkout` filter.
There's a worked example in [extending](developers/extending.md).

## Nothing seems to happen

- Check the plugin is active, and that WooCommerce is 8.3 or later
- Check the order notes: conversions are recorded there, not shown to the customer
- Orders created in the admin or through the REST API aren't checked
- Another plugin may be switching it off through `latinaddr_process_checkout`

With `WP_DEBUG` on, the plugin logs each refused or skipped checkout to the PHP error log,
without the address values. Genuine problems, such as another plugin passing WooCommerce
hooks the wrong kind of data, are logged whether `WP_DEBUG` is on or not. Each line starts with
`Latin Address Checkout:`.

## Turning off automatic updates

Updates come from this plugin's GitHub releases. To turn them off, add this to a small
must-use plugin or your theme's `functions.php`:

```php
add_filter( 'latinaddr_updater_enabled', '__return_false' );
```
