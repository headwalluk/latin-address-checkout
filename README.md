# Latin Address Checkout

A WooCommerce plugin for stores whose carrier only accepts plain ASCII addresses.

Royal Mail's API, and plenty of label printers, reject anything outside basic Latin letters,
digits and punctuation. Customers don't know that. They type "José Núñez", "Łódź" or
"Straße", their phone turns an apostrophe into a curly one, or they enter the whole address in
Cyrillic, Hebrew or Japanese. The order goes through, the customer pays, and then the label
fails. Someone at the store has to spot it, work out what the address should say, and fix it
by hand.

Latin Address Checkout fixes this at the checkout, before the order is placed.

## What it does

For every name and address field, billing and shipping, the plugin does one of three things:

- **Plain ASCII already**: leaves it exactly as typed
- **Can be converted**: converts it, and places the order with the converted value.
  "José Núñez, Łódź" is stored as "Jose Nunez, Lodz". A private order note records each
  original value, so you can still see "Zoë" where the label says "Zoe"
- **Can't be converted** (Cyrillic, Greek, Hebrew, Arabic, Chinese, Japanese and so on): if
  it's part of the delivery address, the checkout stops and asks the customer to re-enter that
  field in Latin letters, naming the field. Nothing has been charged at that point

Only the address the label is printed from can stop a checkout. A customer with a Cyrillic
billing address who is sending a gift to Leeds can still check out, and orders that don't
ship anything (downloads, virtual products) are never stopped.

Email, phone and the order notes field are left alone.

### Examples

- `Straße 5` becomes `Strasse 5`
- `O’Brien` (curly apostrophe) becomes `O'Brien`
- `Flat 2 – Block B` (en dash) becomes `Flat 2 - Block B`
- A non-breaking space, invisible to the customer, becomes an ordinary space
- `1st Floor ¾` becomes `1st Floor 3/4`
- `Ærø`, `Þórshöfn`, `İstanbul` become `AEro`, `THorshofn`, `Istanbul`
- `Ｔｏｋｙｏ` (fullwidth letters from a Japanese keyboard) becomes `Tokyo`
- `Москва`, `תל אביב`, `東京` can't be converted, so the customer is asked to re-enter them

## Both checkouts

It works with the classic checkout (the `[woocommerce_checkout]` shortcode) and with the
Checkout block, which has been WooCommerce's default for new stores since 8.3. It's compatible
with High-Performance Order Storage.

## Requirements

- WordPress 6.9 or later
- PHP 8.2 or later
- WooCommerce 8.3 or later

## Installation

1. Download `latin-address-checkout.zip` from the
   [latest release](https://github.com/headwalluk/latin-address-checkout/releases/latest)
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin** and upload the zip
3. Activate it

There are no settings. Once it's active, it works on every checkout. Updates arrive through
the normal WordPress updates screen.

## Documentation

- [How it works](docs/how-it-works.md): what gets converted, what stops a checkout, and the
  order note
- [Troubleshooting](docs/troubleshooting.md)
- [Hooks and filters](docs/developers/hooks-and-filters.md) and
  [recipes](docs/developers/extending.md), for developers who want to change the message,
  add characters, or switch it off for some orders

## Licence

GPL v2 or later. See [LICENSE](LICENSE).
