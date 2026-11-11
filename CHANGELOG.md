# Changelog

All notable changes to Latin Address Checkout will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Converts billing and shipping name and address fields to plain ASCII at checkout: typographic quotes, dashes and spaces, invisible characters, `ß`, fractions, fullwidth forms, then accents through `remove_accents()`. Already-ASCII values are kept byte for byte.
- Refuses the checkout, naming each field, when the address the label is printed from can't be converted. Orders that don't ship anything are never refused.
- Private order note listing each converted field's original and converted value, written once per order and set of changes.
- Works with the classic (shortcode) checkout and the Checkout block. Declares HPOS and Checkout block compatibility.
- Filters `latinaddr_process_checkout`, `latinaddr_blocked_message`, `latinaddr_replacement_map` and `latinaddr_updater_enabled`; action `latinaddr_address_converted`; Store API error code `latinaddr_non_latin_address`.
- Updates through GitHub releases.
- Translations: de_DE, el_GR, en_GB, es_ES, fr_FR, it_IT, nl_NL, pl_PL.
