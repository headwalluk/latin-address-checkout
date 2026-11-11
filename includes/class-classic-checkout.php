<?php
/**
 * Classic Checkout
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Adapts the shortcode checkout (WC_Checkout::process_checkout()) to Address_Processor.
 *
 * See docs/how-it-works.md for the order of events.
 *
 * @since 1.0.0
 */
class Classic_Checkout {

	/**
	 * Fields refused by the latest conversion, waiting for checkout validation.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $blocked_fields = array();

	/**
	 * Country of the address the blocked fields belong to.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $blocked_country = '';

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
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'convert_posted_data' ), 20, 1 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'add_validation_errors' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'record_changes' ), 10, 3 );
	}

	/**
	 * Convert the billing and shipping fields in the posted checkout data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Posted checkout data from WC_Checkout::get_posted_data().
	 * @return mixed The data, with converted address fields.
	 */
	public function convert_posted_data( mixed $data ): mixed {
		$this->blocked_fields  = array();
		$this->blocked_country = '';
		$this->order_note->queue_changes( array() );

		if ( ! is_array( $data ) ) {
			return $data;
		}

		$addresses = $this->extract_addresses( $data );

		if ( should_process_checkout( ROUTE_CLASSIC, $addresses ) ) {
			$label_type = $this->get_label_address_type( $data );
			$result     = $this->processor->process( $addresses, null === $label_type ? array() : array( $label_type ) );

			foreach ( $result->changes as $change ) {
				$data[ $change['address_type'] . '_' . $change['field'] ] = $change['converted'];
			}

			$this->order_note->queue_changes( $result->changes );

			if ( $result->is_blocked() ) {
				$this->blocked_fields  = $result->blocked_fields;
				$this->blocked_country = is_string( $data[ $label_type . '_country' ] ?? null ) ? $data[ $label_type . '_country' ] : '';

				log_debug( sprintf( 'Classic checkout refused: %d field(s) cannot be converted.', count( $result->blocked_fields ) ) );
			}
		} else {
			log_debug( 'Classic checkout skipped by the latinaddr_process_checkout filter.' );
		}

		return $data;
	}

	/**
	 * Refuse the checkout when a label-address field couldn't be converted.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data   Posted checkout data.
	 * @param mixed $errors Validation errors, a WP_Error.
	 */
	public function add_validation_errors( mixed $data, mixed $errors = null ): void {
		if ( empty( $this->blocked_fields ) ) {
			return;
		}

		if ( $errors instanceof \WP_Error ) {
			$first_field = $this->blocked_fields[0];

			$errors->add(
				ERROR_CODE_NON_LATIN,
				esc_html( get_blocked_message( $this->blocked_fields, ROUTE_CLASSIC, $this->blocked_country ) ),
				array( 'id' => $first_field['address_type'] . '_' . $first_field['field'] )
			);
		} else {
			log_error( 'woocommerce_after_checkout_validation passed no WP_Error; could not refuse an unconvertible address.' );
		}
	}

	/**
	 * Write the order note for the order this checkout created.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $order_id    Order ID.
	 * @param mixed $posted_data Posted checkout data.
	 * @param mixed $order       The order.
	 */
	public function record_changes( mixed $order_id, mixed $posted_data = null, mixed $order = null ): void {
		if ( ! $order instanceof \WC_Order && is_numeric( $order_id ) ) {
			$order = wc_get_order( (int) $order_id );
		}

		if ( $order instanceof \WC_Order ) {
			$this->order_note->write_for_order( $order );
		} elseif ( ! empty( $this->order_note->get_pending_changes() ) ) {
			log_error( 'woocommerce_checkout_order_processed passed no usable order; converted values were not recorded.' );
		} else {
			// Nothing to record.
		}
	}

	/**
	 * Pull the processed address fields out of the posted data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Posted checkout data.
	 * @return array Address values keyed by address type, then field.
	 */
	private function extract_addresses( array $data ): array {
		$addresses = array();

		foreach ( ADDRESS_TYPES as $address_type ) {
			$addresses[ $address_type ] = array();

			foreach ( ADDRESS_FIELDS as $field ) {
				$key = $address_type . '_' . $field;

				if ( array_key_exists( $key, $data ) ) {
					$addresses[ $address_type ][ $field ] = $data[ $key ];
				}
			}
		}

		return $addresses;
	}

	/**
	 * Get the address type whose fields the customer sees for the delivery label.
	 *
	 * Unticked "ship to a different address" means WooCommerce copied billing into
	 * shipping, so the billing fields are the ones to name and highlight.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Posted checkout data.
	 * @return string|null ADDRESS_TYPE_SHIPPING, ADDRESS_TYPE_BILLING, or null when no label is printed.
	 */
	private function get_label_address_type( array $data ): ?string {
		$label_type = null;

		if ( ! does_cart_need_label( $data['shipping_method'] ?? null ) ) {
			// Nothing ships, or it's all local pickup, so no label is printed.
		} elseif ( ! empty( $data['ship_to_different_address'] ) && WC()->cart->needs_shipping_address() ) {
			// The same condition WC_Checkout uses to read the shipping fieldset.
			$label_type = ADDRESS_TYPE_SHIPPING;
		} else {
			$label_type = ADDRESS_TYPE_BILLING;
		}

		return $label_type;
	}
}
