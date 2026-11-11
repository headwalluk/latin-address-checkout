<?php
/**
 * Plugin Constants
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Options.
 *
 * @since 1.0.0
 */
const OPTION_VERSION = 'latinaddr_version';

/**
 * Order meta keys.
 *
 * META_NOTE_HASH holds a hash of the change list last written as an order note,
 * so a repeated "order processed" event on the same order adds no second note.
 *
 * @since 1.0.0
 */
const META_NOTE_HASH = '_latinaddr_note_hash';

/**
 * Prefixes shared by every option and meta key the plugin stores.
 *
 * @since 1.0.0
 */
const OPTION_PREFIX = 'latinaddr_';
const META_PREFIX   = '_latinaddr_';

/**
 * Updater filter, passed to Github_Updater. The plugin's other hooks are named
 * literally at each call, so phpcs can check their prefix.
 *
 * @since 1.0.0
 */
const FILTER_UPDATER_ENABLED = 'latinaddr_updater_enabled';

/**
 * Checkout routes, passed to the public filters.
 *
 * @since 1.0.0
 */
const ROUTE_CLASSIC = 'classic';
const ROUTE_BLOCK   = 'block';

/**
 * Address types and the fields processed in each.
 *
 * @since 1.0.0
 */
const ADDRESS_TYPE_BILLING  = 'billing';
const ADDRESS_TYPE_SHIPPING = 'shipping';

const ADDRESS_TYPES = array(
	ADDRESS_TYPE_BILLING,
	ADDRESS_TYPE_SHIPPING,
);

const ADDRESS_FIELDS = array(
	'first_name',
	'last_name',
	'company',
	'address_1',
	'address_2',
	'city',
	'state',
	'postcode',
);

/**
 * Store API error returned when the label address can't be converted.
 *
 * @since 1.0.0
 */
const ERROR_CODE_NON_LATIN   = 'latinaddr_non_latin_address';
const ERROR_STATUS_NON_LATIN = 400;

/**
 * Store API route that places an order. The pay-for-order route
 * (/checkout/{id}) also fires the customer hook and is skipped.
 *
 * @since 1.0.0
 */
const STORE_API_CHECKOUT_ROUTE_PATTERN = '#^/wc/store(/v\d+)?/checkout/?$#';

/**
 * Conversion patterns.
 *
 * @since 1.0.0
 */
const PRINTABLE_ASCII_PATTERN = '/^[\x20-\x7E]*$/D';
const WHITESPACE_RUN_PATTERN  = '/\s+/';

/**
 * Fullwidth forms of printable ASCII (U+FF01 to U+FF5E), typed by Chinese and
 * Japanese input methods. Each maps to its code point minus FULLWIDTH_OFFSET.
 *
 * @since 1.0.0
 */
const FULLWIDTH_FIRST  = 0xFF01;
const FULLWIDTH_LAST   = 0xFF5E;
const FULLWIDTH_OFFSET = 0xFEE0;

/**
 * Characters remove_accents() leaves alone, and what they become.
 *
 * Applied before remove_accents(). Fullwidth forms are added at run time by
 * get_replacement_map(). Fractions carry a leading space so "2½" becomes "2 1/2";
 * runs of spaces are collapsed afterwards.
 *
 * @since 1.0.0
 */
const REPLACEMENT_MAP = array(
	// Single quotes, apostrophes and primes.
	"\u{2018}" => "'",
	"\u{2019}" => "'",
	"\u{201A}" => "'",
	"\u{201B}" => "'",
	"\u{2032}" => "'",
	"\u{00B4}" => "'",
	"\u{02B9}" => "'",
	"\u{02BB}" => "'",
	"\u{02BC}" => "'",
	"\u{02BE}" => "'",
	"\u{02BF}" => "'",
	"\u{02CB}" => "'",
	"\u{2039}" => "'",
	"\u{203A}" => "'",

	// Double quotes.
	"\u{201C}" => '"',
	"\u{201D}" => '"',
	"\u{201E}" => '"',
	"\u{201F}" => '"',
	"\u{2033}" => '"',
	"\u{00AB}" => '"',
	"\u{00BB}" => '"',

	// Hyphens, dashes and minus.
	"\u{2010}" => '-',
	"\u{2011}" => '-',
	"\u{2012}" => '-',
	"\u{2013}" => '-',
	"\u{2014}" => '-',
	"\u{2015}" => '-',
	"\u{2212}" => '-',

	// Unicode spaces.
	"\u{00A0}" => ' ',
	"\u{2000}" => ' ',
	"\u{2001}" => ' ',
	"\u{2002}" => ' ',
	"\u{2003}" => ' ',
	"\u{2004}" => ' ',
	"\u{2005}" => ' ',
	"\u{2006}" => ' ',
	"\u{2007}" => ' ',
	"\u{2008}" => ' ',
	"\u{2009}" => ' ',
	"\u{200A}" => ' ',
	"\u{202F}" => ' ',
	"\u{205F}" => ' ',
	"\u{3000}" => ' ',

	// ASCII control whitespace.
	"\t"       => ' ',
	"\n"       => ' ',
	"\r"       => ' ',
	"\v"       => ' ',
	"\f"       => ' ',

	// Invisible characters: zero-width, direction marks, word joiner, BOM, soft hyphen.
	"\u{200B}" => '',
	"\u{200C}" => '',
	"\u{200D}" => '',
	"\u{200E}" => '',
	"\u{200F}" => '',
	"\u{2060}" => '',
	"\u{FEFF}" => '',
	"\u{00AD}" => '',

	// Letters remove_accents() gets wrong or misses without a locale.
	"\u{00DF}" => 'ss',
	"\u{1E9E}" => 'SS',

	// Punctuation and symbols.
	"\u{2026}" => '...',
	"\u{00B7}" => '.',
	"\u{2044}" => '/',
	"\u{00D7}" => 'x',
	"\u{2116}" => 'No',
	"\u{00B0}" => 'o',

	// Vulgar fractions.
	"\u{00BC}" => ' 1/4',
	"\u{00BD}" => ' 1/2',
	"\u{00BE}" => ' 3/4',
	"\u{2150}" => ' 1/7',
	"\u{2151}" => ' 1/9',
	"\u{2152}" => ' 1/10',
	"\u{2153}" => ' 1/3',
	"\u{2154}" => ' 2/3',
	"\u{2155}" => ' 1/5',
	"\u{2156}" => ' 2/5',
	"\u{2157}" => ' 3/5',
	"\u{2158}" => ' 4/5',
	"\u{2159}" => ' 1/6',
	"\u{215A}" => ' 5/6',
	"\u{215B}" => ' 1/8',
	"\u{215C}" => ' 3/8',
	"\u{215D}" => ' 5/8',
	"\u{215E}" => ' 7/8',
);

/**
 * GitHub updater.
 *
 * @since 1.0.0
 */
const UPDATER_GITHUB_REPO = 'headwalluk/latin-address-checkout';
