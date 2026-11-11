# CLAUDE.md

Repository: public

Follows the **headwall-wp-plugin** skill. Load it before working on this plugin; this file holds
only what is specific to Latin Address Checkout.

## Project Overview

A WooCommerce plugin that converts checkout names and addresses to plain ASCII, for carriers
whose APIs and label printers reject anything else. A value that can't be converted refuses
the checkout, but only when it's part of the address the label is printed from.

- **Namespace:** `Latin_Address_Checkout` · **Text domain:** `latin-address-checkout` · **Prefixes:** `latinaddr_` (options, hooks), `_latinaddr_` (order meta), `LATINADDR_` (constants)
- **PHP / WordPress floor:** 8.2 / 6.9. `convert_to_ascii()` calls `wp_is_valid_utf8()`, new in 6.9
- **WooCommerce floor:** 8.3. The block-checkout hook, `woocommerce_store_api_checkout_update_customer_from_request`, arrived in 8.2; 8.3 made the Checkout block the default for new stores
- **Primary route:** classic checkout. It's what the first production site uses, so it gets the deepest testing. The block route is supported and tested too
- **No build system, no settings page, no assets, no WP-CLI commands**

## Request Flow

Both routes hand the address fields to `Address_Processor::process()`, which returns a
`Processing_Result` (converted addresses, change list, blocked fields). The route adapters only
translate WooCommerce data to and from it. `docs/how-it-works.md` covers the behaviour for
store owners.

**Classic** (`Classic_Checkout`), inside `WC_Checkout::process_checkout()`:

1. `woocommerce_checkout_posted_data` (priority 20): convert. By this point WooCommerce has
   already copied billing into shipping unless it read the shipping fieldset, which it does only
   when "ship to a different address" is ticked **and** `needs_shipping_address()`. The label
   address type follows the same condition: shipping, else billing. None when
   `does_cart_need_label()` is false
2. `woocommerce_after_checkout_validation`: add one `WP_Error` naming every blocked field,
   with `id` set to the first, so WooCommerce highlights it
3. `woocommerce_checkout_order_processed`: write the order note

**Block** (`Block_Checkout`), inside the Store API `Checkout::process_order()`:

1. `woocommerce_store_api_checkout_update_customer_from_request`: convert on `WC()->customer`,
   before `$customer->save()` and before the draft order copies its addresses from the
   customer. Throw `RouteException( 'latinaddr_non_latin_address', ..., 400 )` to refuse.
   Label address is shipping when `does_cart_need_label()`. The pay-for-order route
   (`/checkout/{id}`) fires the same hook after it has set the order's addresses, so it is
   skipped (`STORE_API_CHECKOUT_ROUTE_PATTERN`, case-insensitive because `WP_REST_Server`
   matches routes case-insensitively: a case-sensitive pattern let `/Checkout` bypass the check)

`does_cart_need_label()` is false when nothing ships or every chosen rate is local pickup,
matching the Store API's own address validation (`OrderController::validate_addresses()`).

`convert_to_ascii()` passes `REMOVE_ACCENTS_LOCALE` (`en_US`) to `remove_accents()`, which
otherwise uses the site locale (German turns `ü` into `ue`). Combining marks are stripped by the
map because `remove_accents()` only recomposes them when `intl` is loaded.
2. `woocommerce_store_api_checkout_order_processed`: write the order note

`Order_Note` is shared by both routes. Changes are queued at conversion and written when the
order is processed. A hash in `_latinaddr_note_hash` stops a repeated event adding the same note
twice.

## Key Files

| File | Purpose |
|------|---------|
| `latin-address-checkout.php` | Header, constants, requires, updater, activation |
| `constants.php` | Keys, field list, `REPLACEMENT_MAP`, patterns, error code, route pattern |
| `functions-private.php` | `convert_to_ascii()` (pure), `get_replacement_map()`, labels, `get_blocked_message()`, `should_process_checkout()`, logging |
| `includes/class-address-processor.php` | Converts a checkout's fields, decides what blocks |
| `includes/class-processing-result.php` | Readonly result of `process()` |
| `includes/class-classic-checkout.php` | Shortcode checkout adapter |
| `includes/class-block-checkout.php` | Store API adapter |
| `includes/class-order-note.php` | Note text, hash guard, `latinaddr_address_converted` |
| `includes/class-plugin.php` | Wiring, WooCommerce compatibility declaration, textdomain, version |
| `includes/class-github-updater.php` | Copied from Quick 2FA; change only `namespace` and `@package` |

## Data Storage

- Option `latinaddr_version`: installed version, written on activation and by
  `Plugin::check_first_run()`
- Order meta `_latinaddr_note_hash`: md5 of the last change list written as a note. Internal
- Order notes: one private note per order and set of changes
- Updater transients, named from the slug: `latin_address_checkout_github_release` and
  `latin_address_checkout_github_failed`
- `uninstall.php` deletes the version option and the updater cache. Order data stays

## Public Contracts

The skill's `public-contracts.md` has the rules. This plugin's:

| Contract | Names |
|----------|-------|
| Filters | `latinaddr_process_checkout`, `latinaddr_blocked_message`, `latinaddr_replacement_map`, `latinaddr_updater_enabled` |
| Actions | `latinaddr_address_converted` |
| Store API / validation error code | `latinaddr_non_latin_address` |
| Stored keys | `latinaddr_version`, `_latinaddr_note_hash` |
| Change record shape | `address_type`, `field`, `original`, `converted`, passed to `latinaddr_address_converted` |

Hook names are written literally at each `apply_filters()` / `do_action()` call, not through
constants, so phpcs can check their prefix. `FILTER_UPDATER_ENABLED` is the exception because it
is passed to `Github_Updater` as an argument.

All of them are documented in `docs/developers/hooks-and-filters.md`.

### High-Impact Code

These paths can refuse a paying customer's order, and they rewrite personal data on the order:

- `Address_Processor::process()` and `convert_to_ascii()`
- `does_cart_need_label()` and `is_local_pickup_only()`
- `Classic_Checkout::convert_posted_data()`, `add_validation_errors()` and `get_label_address_type()`
- `Block_Checkout::convert_customer()` and `is_place_order_request()`

Never log address values: they are personal data. `log_error()` and `log_debug()` take
messages about counts and hooks only.

## Testing

Harnesses live in `dev-notes/testing/`. Run each with
`wp eval-file wp-content/plugins/latin-address-checkout/dev-notes/testing/<file>.php`:

- `test-functions-private.php`: the converter table and helpers
- `test-address-processor.php`, `test-order-note.php`: shared classes
- `test-classic-checkout.php`, `test-block-checkout.php`: each adapter through its hooks,
  including wrong argument types
- `test-e2e-classic.php`: real orders through `WC_Checkout::process_checkout()` in-process
- `test-e2e-block.php`: real orders through the Store API route with `rest_do_request()`

The end-to-end harnesses place and then delete real orders, and use the dev store's products
and shipping zones (GB and US only). All of them must pass before a release and after any
change to high-impact code.

## Release

Follow the skill's `release.md` (public). The updater's repository is `UPDATER_GITHUB_REPO` in
`constants.php`.

## Reference Files

`docs/` (tracked, public):

- `docs/how-it-works.md`, `docs/troubleshooting.md`: store owners
- `docs/developers/hooks-and-filters.md`, `docs/developers/extending.md`: developers

`dev-notes/` (private, untracked):

- `dev-notes/00-requirements.md`: the original hand-off
- `dev-notes/01-skill-feedback.md`: gaps found in the headwall-wp-plugin skill
- `dev-notes/02-build-plan.md`: decisions, milestones, test matrix
- `dev-notes/testing/`: the harnesses

<!-- wp-translate:begin v=1.2.0 hash=43b28f57691552f443c4328905e571c0771641015f5c3f28e5bd2bbd2cb4e51e -->
## Translating this plugin (wp-translate conventions)

This plugin's `.po`/`.mo` files are generated from source by
[wp-translate](https://github.com/headwalluk/wp-translate-tool), which
machine-translates strings with DeepL. Machine translation is only as good as
the strings you give it — follow these conventions when adding or editing
user-facing text.

### 1. Disambiguate short or ambiguous strings with `_x()`

DeepL handles full sentences well but guesses badly on short, context-free
labels. Give it context with `_x()` (or `esc_html_x()`, `_ex()`):

```php
// Ambiguous out of context — DeepL may read "Sent" as "late", "Folder" as "leaflet"
__( 'Sent', 'latin-address-checkout' );

// Disambiguated — the context is passed to the translator and to DeepL
_x( 'Sent', 'email delivery status', 'latin-address-checkout' );
_x( 'Folder', 'IMAP mailbox', 'latin-address-checkout' );
_x( 'Open', 'verb; button label', 'latin-address-checkout' );
```

The context (2nd argument) is never shown to users. Use it whenever a string is a
single word, a short label, or has more than one plausible meaning.

### 2. Use placeholders, never concatenation

Build dynamic text with `printf`/`sprintf` so the whole sentence translates as a
unit, and add a `translators:` comment to explain each placeholder:

```php
/* translators: %s is the user's display name */
printf( esc_html__( 'Welcome back, %s', 'latin-address-checkout' ), $name );
```

Never split a sentence across multiple translation calls — word order differs
between languages.

### 3. Use `_n()` for anything that can be counted

Never build a count-dependent sentence by hand, and never settle for a single
form that reads correctly only for one number. Languages differ in how many
plural forms they have — English and German have two, French treats 0 as
singular, Polish and Russian have three, Japanese has one, Arabic has six — and
`_n()` is the only way to express that.

```php
// Wrong — "1 reviews", and untranslatable into languages with other forms
printf( esc_html__( '%d reviews', 'latin-address-checkout' ), $count );

// Right — wp-translate fills every form the target locale needs
printf(
    esc_html( _n( '%d review', '%d reviews', $count, 'latin-address-checkout' ) ),
    $count
);
```

Keep the placeholder in **both** forms, even when the singular reads fine
without it (`'%d review'`, not `'One review'`) — some locales use the singular
slot for other numbers too.

For a short or ambiguous countable noun, use `_nx()` — the plural equivalent of
`_x()` — so the context reaches DeepL:

```php
// "Review" alone is ambiguous: critique? opinion? inspection?
_nx( '%d review', '%d reviews', $count, 'customer feedback on a company', 'latin-address-checkout' );
```

**Locales needing more than two forms will have their extra slots left empty for
a human translator.** DeepL supplies a singular and a plural; nobody can invent
Polish's third form from those, and wp-translate deliberately leaves it blank
rather than filling it with a plausible guess. Expect to see empty
`msgstr[2]` entries in `pl_PL` — that is correct behaviour, not a failure.

### 4. Acronyms and technical tokens

wp-translate keeps common acronyms (`TLS`, `API`, `SMTP`, `URL`, `ID`, `UTC`, …)
verbatim automatically. If you introduce an unusual acronym or product name that
must not be translated, keep it as its own standalone string so it is recognised,
or ask the maintainer to add it to the tool's acronym list.

### 5. Don't translate dates — let WordPress localise them

Never add month or day-of-week names (full or abbreviated) as translatable
strings. DeepL frequently mistranslates short forms like `Mon`, `Tue`, `Jan`,
`Feb` even with context hints. WordPress already ships locale-aware names — use
`$wp_locale`:

```php
global $wp_locale;
$wp_locale->get_month( $month_number );        // "January" (1-based)
$wp_locale->get_month_abbrev( $month_name );   // "Jan"
$wp_locale->get_weekday( $weekday_number );     // "Monday" (0 = Sunday)
$wp_locale->get_weekday_abbrev( $weekday_name ); // "Mon"
```

For formatted dates, prefer `wp_date()` / `date_i18n()`, which localise month and
day names automatically.

### 6. English source dialect

Write source strings in standard English. wp-translate handles English targets
locally (no DeepL): `en`/`en_US` use the source as-is, and `en_GB`/`en_AU`/… get
American spellings converted to British automatically (`color` → `colour`).

### Running wp-translate

After changing strings, regenerate translations:

```bash
wp-translate /path/to/this-plugin              # auto-detect locales from languages/
wp-translate /path/to/this-plugin en_GB,fr_FR  # explicit locales
wp-translate /path/to/this-plugin --dry-run    # preview; no API calls, no writes
```

Requires WP-CLI (`wp`) and a DeepL API key at `~/.config/deepl.env`. The tool
regenerates the `.pot` from source, translates new/changed strings for each
locale, and compiles the `.mo` files.
<!-- wp-translate:end -->
