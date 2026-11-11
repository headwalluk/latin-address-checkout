<?php
/**
 * Block Checkout
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Adapts the Checkout block (Store API checkout route) to Address_Processor.
 *
 * Converts on the customer before the draft order copies its addresses from it.
 * See docs/how-it-works.md for the order of events.
 *
 * @since 1.0.0
 */
class Block_Checkout {

	/**
	 * Create the adapter.
	 *
	 * @since 1.0.0
	 *
	 * @param Address_Processor $processor  Shared converter.
	 * @param Order_Note        $order_note Shared order note writer.
	 */
	public function __construct(
		private readonly Address_Processor $processor,
		private readonly Order_Note $order_note
	) {
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function run(): void {
		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this, 'convert_customer' ), 20, 2 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'record_changes' ), 10, 1 );
	}

	/**
	 * Convert the customer's billing and shipping fields, refusing the checkout if the label address can't be converted.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $customer The session customer, a WC_Customer.
	 * @param mixed $request  The checkout request, a WP_REST_Request.
	 * @throws RouteException When a shipping field can't be converted.
	 */
	public function convert_customer( mixed $customer, mixed $request ): void {
		$this->order_note->queue_changes( array() );

		if ( ! $customer instanceof \WC_Customer ) {
			log_error( 'woocommerce_store_api_checkout_update_customer_from_request passed no WC_Customer; the address was not checked.' );
			return;
		}

		if ( ! $this->is_place_order_request( $request ) ) {
			return;
		}

		$addresses = $this->read_addresses( $customer );

		if ( should_process_checkout( ROUTE_BLOCK, $addresses ) ) {
			$blocking_types = $this->cart_needs_shipping() ? array( ADDRESS_TYPE_SHIPPING ) : array();
			$result         = $this->processor->process( $addresses, $blocking_types );

			foreach ( $result->changes as $change ) {
				$setter = 'set_' . $change['address_type'] . '_' . $change['field'];

				if ( is_callable( array( $customer, $setter ) ) ) {
					$customer->{$setter}( $change['converted'] );
				}
			}

			$this->order_note->queue_changes( $result->changes );

			if ( $result->is_blocked() ) {
				log_debug( sprintf( 'Block checkout refused: %d field(s) cannot be converted.', count( $result->blocked_fields ) ) );

				throw new RouteException(
					esc_html( ERROR_CODE_NON_LATIN ),
					esc_html( get_blocked_message( $result->blocked_fields, ROUTE_BLOCK, (string) $customer->get_shipping_country() ) ),
					(int) ERROR_STATUS_NON_LATIN
				);
			}
		} else {
			log_debug( 'Block checkout skipped by the latinaddr_process_checkout filter.' );
		}
	}

	/**
	 * Write the order note for the order this checkout placed.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $order The order.
	 */
	public function record_changes( mixed $order ): void {
		if ( $order instanceof \WC_Order ) {
			$this->order_note->write_for_order( $order );
		} elseif ( ! empty( $this->order_note->get_pending_changes() ) ) {
			log_error( 'woocommerce_store_api_checkout_order_processed passed no WC_Order; converted values were not recorded.' );
		} else {
			// Nothing to record.
		}
	}

	/**
	 * Whether the request is the Store API route that places an order.
	 *
	 * The pay-for-order route fires the same hook after it has already copied the
	 * request's addresses into the order, so converting the customer there does nothing.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $request The checkout request.
	 * @return bool True for the checkout route.
	 */
	private function is_place_order_request( mixed $request ): bool {
		return $request instanceof \WP_REST_Request
			&& 1 === preg_match( STORE_API_CHECKOUT_ROUTE_PATTERN, $request->get_route() );
	}

	/**
	 * Read the processed address fields through the customer's getters.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Customer $customer The customer.
	 * @return array Address values keyed by address type, then field.
	 */
	private function read_addresses( \WC_Customer $customer ): array {
		$addresses = array();

		foreach ( ADDRESS_TYPES as $address_type ) {
			$addresses[ $address_type ] = array();

			foreach ( ADDRESS_FIELDS as $field ) {
				$getter = 'get_' . $address_type . '_' . $field;

				if ( is_callable( array( $customer, $getter ) ) ) {
					$addresses[ $address_type ][ $field ] = $customer->{$getter}();
				}
			}
		}

		return $addresses;
	}

	/**
	 * Whether the cart has anything that ships.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if a label will be printed.
	 */
	private function cart_needs_shipping(): bool {
		$needs_shipping = false;

		if ( function_exists( 'WC' ) && WC()->cart instanceof \WC_Cart ) {
			$needs_shipping = WC()->cart->needs_shipping();
		} else {
			log_error( 'No cart during block checkout; the label address was not checked.' );
		}

		return $needs_shipping;
	}
}
