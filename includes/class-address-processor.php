<?php
/**
 * Address Processor
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Converts a checkout's address fields and decides whether the checkout is refused.
 *
 * Shared by both checkout routes. Knows nothing about WooCommerce objects.
 *
 * @since 1.0.0
 */
class Address_Processor {

	/**
	 * Convert every address field and report what changed and what blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $addresses      Address values keyed by address type, then field. Non-string values are skipped.
	 * @param string[] $blocking_types Address types whose unconvertible fields refuse the checkout.
	 * @return Processing_Result Converted addresses, changes and blocked fields.
	 */
	public function process( array $addresses, array $blocking_types ): Processing_Result {
		$replacement_map     = get_replacement_map();
		$converted_addresses = $addresses;
		$changes             = array();
		$blocked_fields      = array();

		foreach ( ADDRESS_TYPES as $address_type ) {
			if ( ! isset( $addresses[ $address_type ] ) || ! is_array( $addresses[ $address_type ] ) ) {
				continue;
			}

			foreach ( ADDRESS_FIELDS as $field ) {
				$original = $addresses[ $address_type ][ $field ] ?? null;

				if ( ! is_string( $original ) ) {
					continue;
				}

				$converted = convert_to_ascii( $original, $replacement_map );

				if ( null === $converted ) {
					// Unconvertible: kept as entered, and blocks only on a label address.
					if ( in_array( $address_type, $blocking_types, true ) ) {
						$blocked_fields[] = array(
							'address_type' => $address_type,
							'field'        => $field,
						);
					}
				} elseif ( $converted !== $original ) {
					$converted_addresses[ $address_type ][ $field ] = $converted;

					$changes[] = array(
						'address_type' => $address_type,
						'field'        => $field,
						'original'     => $original,
						'converted'    => $converted,
					);
				} else {
					// Already printable ASCII.
				}
			}
		}

		return new Processing_Result( $converted_addresses, $changes, $blocked_fields );
	}
}
