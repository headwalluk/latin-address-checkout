<?php
/**
 * Plugin Functions
 *
 * Namespaced helpers private to the plugin. Integrations use the hooks in
 * docs/developers/hooks-and-filters.md instead.
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Block direct access.
defined( 'ABSPATH' ) || die();

// ============================================================================
// Conversion
// ============================================================================

/**
 * Convert text to printable ASCII, or return null if that isn't possible.
 *
 * Already-printable ASCII is returned unchanged, byte for byte.
 *
 * @since 1.0.0
 *
 * @param string $text            Text to convert.
 * @param array  $replacement_map Characters to replace before remove_accents(), as from get_replacement_map().
 * @return string|null Converted text, or null if anything outside printable ASCII remains.
 */
function convert_to_ascii( string $text, array $replacement_map ): ?string {
	$result = null;

	if ( is_printable_ascii( $text ) ) {
		$result = $text;
	} elseif ( wp_is_valid_utf8( $text ) ) {
		$converted = strtr( $text, $replacement_map );
		$converted = remove_accents( $converted );
		$converted = trim( (string) preg_replace( WHITESPACE_RUN_PATTERN, ' ', $converted ) );

		// A value made only of invisible characters would otherwise become an empty field.
		if ( '' !== $converted && is_printable_ascii( $converted ) ) {
			$result = $converted;
		}
	} else {
		// Invalid UTF-8 is never converted.
	}

	return $result;
}

/**
 * Whether text contains only printable ASCII (0x20 to 0x7E).
 *
 * @since 1.0.0
 *
 * @param string $text Text to check.
 * @return bool True if every byte is printable ASCII.
 */
function is_printable_ascii( string $text ): bool {
	return 1 === preg_match( PRINTABLE_ASCII_PATTERN, $text );
}

/**
 * Get the replacement map, including fullwidth forms, after the public filter.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Characters to replace, mapped to their ASCII replacements.
 */
function get_replacement_map(): array {
	$default_map = REPLACEMENT_MAP + get_fullwidth_map();

	/**
	 * Filter the characters replaced before remove_accents().
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $map Characters to replace, mapped to their replacements.
	 */
	$filtered_map = apply_filters( 'latinaddr_replacement_map', $default_map );

	$replacement_map = array();

	if ( is_array( $filtered_map ) ) {
		foreach ( $filtered_map as $search => $replacement ) {
			if ( ! is_string( $search ) || '' === $search || ! is_string( $replacement ) ) {
				log_error( 'Ignored a malformed entry returned by the latinaddr_replacement_map filter.' );
				continue;
			}

			$replacement_map[ $search ] = $replacement;
		}
	} else {
		log_error( 'The latinaddr_replacement_map filter returned a non-array; using the default map.' );
		$replacement_map = $default_map;
	}

	return $replacement_map;
}

/**
 * Map each fullwidth ASCII form, and the ideographic space, to plain ASCII.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Fullwidth character mapped to its ASCII equivalent.
 */
function get_fullwidth_map(): array {
	static $fullwidth_map = null;

	if ( null === $fullwidth_map ) {
		$fullwidth_map = array();

		for ( $code_point = FULLWIDTH_FIRST; $code_point <= FULLWIDTH_LAST; ++$code_point ) {
			$fullwidth_map[ encode_three_byte_utf8( $code_point ) ] = chr( $code_point - FULLWIDTH_OFFSET );
		}
	}

	return $fullwidth_map;
}

/**
 * Encode a code point from U+0800 to U+FFFF as UTF-8, without mbstring.
 *
 * @since 1.0.0
 *
 * @param int $code_point Unicode code point.
 * @return string Three-byte UTF-8 sequence.
 */
function encode_three_byte_utf8( int $code_point ): string {
	return chr( 0xE0 | ( $code_point >> 12 ) )
		. chr( 0x80 | ( ( $code_point >> 6 ) & 0x3F ) )
		. chr( 0x80 | ( $code_point & 0x3F ) );
}

// ============================================================================
// Field labels and messages
// ============================================================================

/**
 * Get the plugin's own label for an address field, used in order notes.
 *
 * @since 1.0.0
 *
 * @param string $address_type ADDRESS_TYPE_BILLING or ADDRESS_TYPE_SHIPPING.
 * @param string $field        One of ADDRESS_FIELDS.
 * @return string Translated label, or the raw key if it isn't known.
 */
function get_field_label( string $address_type, string $field ): string {
	$labels = array(
		ADDRESS_TYPE_BILLING  => array(
			'first_name' => __( 'Billing first name', 'latin-address-checkout' ),
			'last_name'  => __( 'Billing last name', 'latin-address-checkout' ),
			'company'    => __( 'Billing company', 'latin-address-checkout' ),
			'address_1'  => __( 'Billing address line 1', 'latin-address-checkout' ),
			'address_2'  => __( 'Billing address line 2', 'latin-address-checkout' ),
			'city'       => __( 'Billing town / city', 'latin-address-checkout' ),
			'state'      => __( 'Billing state / county', 'latin-address-checkout' ),
			'postcode'   => __( 'Billing postcode / ZIP', 'latin-address-checkout' ),
		),
		ADDRESS_TYPE_SHIPPING => array(
			'first_name' => __( 'Shipping first name', 'latin-address-checkout' ),
			'last_name'  => __( 'Shipping last name', 'latin-address-checkout' ),
			'company'    => __( 'Shipping company', 'latin-address-checkout' ),
			'address_1'  => __( 'Shipping address line 1', 'latin-address-checkout' ),
			'address_2'  => __( 'Shipping address line 2', 'latin-address-checkout' ),
			'city'       => __( 'Shipping town / city', 'latin-address-checkout' ),
			'state'      => __( 'Shipping state / county', 'latin-address-checkout' ),
			'postcode'   => __( 'Shipping postcode / ZIP', 'latin-address-checkout' ),
		),
	);

	return $labels[ $address_type ][ $field ] ?? $address_type . '_' . $field;
}

/**
 * Get the label the checkout form shows for a field, for the customer's country.
 *
 * Falls back to get_field_label() when WooCommerce has no label for the field.
 *
 * @since 1.0.0
 *
 * @param string $address_type ADDRESS_TYPE_BILLING or ADDRESS_TYPE_SHIPPING.
 * @param string $field        One of ADDRESS_FIELDS.
 * @param string $country      Two-letter country code, or empty.
 * @return string Plain-text label.
 */
function get_customer_field_label( string $address_type, string $field, string $country ): string {
	$label = '';

	if ( function_exists( 'WC' ) && WC()->countries instanceof \WC_Countries ) {
		$address_fields = WC()->countries->get_address_fields( $country, $address_type . '_' );
		$field_key      = $address_type . '_' . $field;

		if ( is_array( $address_fields ) && isset( $address_fields[ $field_key ]['label'] ) && is_string( $address_fields[ $field_key ]['label'] ) ) {
			$label = trim( wp_strip_all_tags( $address_fields[ $field_key ]['label'] ) );
		}
	}

	if ( '' === $label ) {
		$label = get_field_label( $address_type, $field );
	}

	return $label;
}

/**
 * Build the message shown when the checkout is refused.
 *
 * @since 1.0.0
 *
 * @param array  $blocked_fields List of array{address_type: string, field: string}, from Processing_Result.
 * @param string $route          ROUTE_CLASSIC or ROUTE_BLOCK.
 * @param string $country        Country of the blocked address, for field labels.
 * @return string Plain-text message.
 */
function get_blocked_message( array $blocked_fields, string $route, string $country ): string {
	$field_labels = array();

	foreach ( $blocked_fields as $blocked_field ) {
		$field_labels[] = get_customer_field_label( $blocked_field['address_type'], $blocked_field['field'], $country );
	}

	$default_message = sprintf(
		/* translators: %s: list of checkout field names, such as "Street address and Town / City" */
		__( 'We can only print delivery labels in Latin (English) letters. Please re-enter your %s using Latin letters only, or give an address, such as a PO box, that can be written that way.', 'latin-address-checkout' ),
		wp_sprintf( '%l', $field_labels )
	);

	/**
	 * Filter the message shown when the checkout is refused.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $message      Plain-text message. HTML is escaped.
	 * @param string[] $field_labels Labels of the fields that need re-entering.
	 * @param string   $route        "classic" or "block".
	 */
	$message = apply_filters( 'latinaddr_blocked_message', $default_message, $field_labels, $route );

	if ( ! is_string( $message ) || '' === trim( $message ) ) {
		log_error( 'The latinaddr_blocked_message filter returned an empty or non-string message; using the default.' );
		$message = $default_message;
	}

	return $message;
}

/**
 * Ask the public filter whether this checkout should be processed.
 *
 * @since 1.0.0
 *
 * @param string $route     ROUTE_CLASSIC or ROUTE_BLOCK.
 * @param array  $addresses Address values keyed by address type, then field.
 * @return bool True to convert and check the checkout.
 */
function should_process_checkout( string $route, array $addresses ): bool {
	/**
	 * Filter whether a checkout is converted and checked.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $process   True to convert and check. Return false to leave the checkout alone.
	 * @param string $route     "classic" or "block".
	 * @param array  $addresses Address values as entered, keyed by "billing"/"shipping", then field.
	 */
	$process = apply_filters( 'latinaddr_process_checkout', true, $route, $addresses );

	return (bool) filter_var( $process, FILTER_VALIDATE_BOOLEAN );
}

// ============================================================================
// Logging
// ============================================================================

/**
 * Log a genuine failure. Always logs.
 *
 * Never pass address values: they are personal data.
 *
 * @since 1.0.0
 *
 * @param string $message Message to log.
 */
function log_error( string $message ): void {
	error_log( 'Latin Address Checkout: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Errors must reach the server log without WP_DEBUG.
}

/**
 * Log routine flow tracing. Only logs when WP_DEBUG is on.
 *
 * Never pass address values: they are personal data.
 *
 * @since 1.0.0
 *
 * @param string $message Message to log.
 */
function log_debug( string $message ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Latin Address Checkout: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Flow tracing, only under WP_DEBUG.
	}
}
