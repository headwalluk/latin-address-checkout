# How it works

For store owners and the people who look after their sites.

## The fields

The plugin looks at these fields, in both the billing and the shipping address:

- First name and last name
- Company
- Address lines 1 and 2
- Town / city
- State / county
- Postcode

It never touches the email address, the phone number or the order notes.

## Converting a field

Each value goes through these steps:

1. **Plain ASCII already?** If every character is an ordinary letter, digit, space or
   punctuation mark, the value is kept exactly as typed, extra spaces included
2. **Swap out typographic characters.** Curly quotes and apostrophes become straight ones.
   Dashes become hyphens. Non-breaking and other special spaces become plain spaces.
   Invisible characters (zero-width spaces, direction marks, soft hyphens) and separate
   accent marks (an `e` followed by a combining accent, as some phones type it) are removed. `ß`
   becomes `ss`, `…` becomes `...`, fractions such as `½` become `1/2`, and the fullwidth
   letters and digits that Chinese and Japanese keyboards produce become their ordinary forms
3. **Remove accents** with WordPress's own `remove_accents()`, so `é` becomes `e`, `ł`
   becomes `l`, `Æ` becomes `AE`. It always uses the same rules, whatever language the site is
   in, so `ü` becomes `u` on a German site too, not `ue`
4. **Tidy spacing.** Runs of spaces become one, and spaces at the ends are trimmed
5. **Check what's left.** If everything is now plain ASCII, the converted value is used.
   If anything else remains, the field can't be converted

The conversion doesn't depend on the site language, the server's locale or optional PHP
extensions such as `intl`, so it gives the same result on every host.

Scripts that aren't written in Latin letters, such as Cyrillic, Greek, Hebrew, Arabic, Chinese
and Japanese, can't be converted. Turning `Москва` into `Moskva` is possible in principle, but
the result often isn't what the local post office expects, and a wrong delivery address is
worse than asking.

## When a checkout is stopped

A field that can't be converted only stops the checkout if it's part of the address the label
is printed from:

- **The cart ships something and "Ship to a different address" is ticked**: the shipping
  address
- **The cart ships something and it isn't ticked**: the billing address, because WooCommerce
  uses it as the shipping address
- **The Checkout block**: the shipping address, which is the one the block asks for first
- **Nothing in the cart ships, or the customer chose local pickup**: nothing stops the checkout

The customer sees a message naming each field to change:

> We can only print delivery labels in Latin (English) letters. Please re-enter your Street
> address and Town / City using Latin letters only, or give an address, such as a PO box,
> that can be written that way.

This happens before payment, so the customer hasn't been charged. In the classic checkout, the
first field named is highlighted.

A field that can't be converted and isn't on the label (a Cyrillic billing address on an order
going to Leeds, for example) is kept exactly as the customer typed it.

## The order note

When anything is converted, the order gets a private note, visible only to staff:

```
Latin Address Checkout converted these address fields to plain ASCII for the delivery label:
Shipping first name: Zoë → Zoe
Shipping town / city: Łódź → Lodz
```

If the same order is processed again (after a failed payment, say) with the same changes, no
second note is added.

## Saved addresses

For a logged-in customer, WooCommerce saves the checkout address to their account. That
saved copy is the converted one, so next time the checkout is already filled in with ASCII.

Addresses already saved in My Account before the plugin was installed are converted the next
time the customer checks out.

## What isn't covered

- Orders created in the WordPress admin, through the REST API or by an import
- The pay-for-order page used for orders created in the admin
- Editing addresses in My Account (they are converted at the next checkout)
- Extra checkout fields added by other plugins

## What's stored

- The option `latinaddr_version` (the installed version)
- The order meta `_latinaddr_note_hash`, on orders with a conversion note, so a repeated
  "order processed" event doesn't add the note twice
- The order note itself, which stays with the order

Deleting the plugin removes the version option and the update cache. Order notes and order
meta stay with their orders.

## Privacy

The plugin sends nothing anywhere. It never writes address values to the server log. The
order note holds the customer's original values, which are part of the order like everything
else on it.
