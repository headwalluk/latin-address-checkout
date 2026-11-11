<?php
/**
 * Processing Result
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Outcome of converting one checkout's addresses.
 *
 * @since 1.0.0
 */
final class Processing_Result {

	/**
	 * Create a result.
	 *
	 * @since 1.0.0
	 *
	 * @param array $addresses      Address values after conversion, keyed by address type, then field. Unconvertible values are kept as entered.
	 * @param array $changes        List of array{address_type: string, field: string, original: string, converted: string}.
	 * @param array $blocked_fields List of array{address_type: string, field: string} that couldn't be converted and refuse the checkout.
	 */
	public function __construct(
		public readonly array $addresses,
		public readonly array $changes,
		public readonly array $blocked_fields
	) {
	}

	/**
	 * Whether the checkout must be refused.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if any blocking field couldn't be converted.
	 */
	public function is_blocked(): bool {
		return ! empty( $this->blocked_fields );
	}
}
