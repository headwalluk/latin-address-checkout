<?php
/**
 * Order Note
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Records each converted field's original value as a private order note.
 *
 * Changes are queued when the checkout is converted, before the order exists,
 * and written when WooCommerce reports the order as processed.
 *
 * @since 1.0.0
 */
class Order_Note {

	/**
	 * Changes from the current request's checkout, waiting for an order.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $pending_changes = array();

	/**
	 * Replace the queued changes with those from the latest conversion.
	 *
	 * @since 1.0.0
	 *
	 * @param array $changes Change list from Processing_Result::$changes.
	 */
	public function queue_changes( array $changes ): void {
		$this->pending_changes = $changes;
	}

	/**
	 * Get the queued changes.
	 *
	 * @since 1.0.0
	 *
	 * @return array Change list.
	 */
	public function get_pending_changes(): array {
		return $this->pending_changes;
	}

	/**
	 * Write the queued changes to an order as a private note, once.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order $order Order placed by this request.
	 */
	public function write_for_order( \WC_Order $order ): void {
		$changes = $this->pending_changes;

		$this->pending_changes = array();

		$changes_hash = md5( (string) wp_json_encode( $changes ) );

		if ( empty( $changes ) ) {
			// Nothing was converted.
		} elseif ( (string) $order->get_meta( META_NOTE_HASH ) === $changes_hash ) {
			log_debug( sprintf( 'Order %d already has a note for these changes.', $order->get_id() ) );
		} else {
			$order->add_order_note( $this->build_note_text( $changes ), 0, false );
			$order->update_meta_data( META_NOTE_HASH, $changes_hash );
			$order->save_meta_data();

			log_debug( sprintf( 'Recorded %d converted field(s) on order %d.', count( $changes ), $order->get_id() ) );

			/**
			 * Fires after converted address fields are recorded on an order.
			 *
			 * @since 1.0.0
			 *
			 * @param \WC_Order $order   The order.
			 * @param array     $changes List of array{address_type: string, field: string, original: string, converted: string}.
			 */
			do_action( 'latinaddr_address_converted', $order, $changes );
		}
	}

	/**
	 * Build the note text, one line per changed field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $changes Change list.
	 * @return string Note text.
	 */
	public function build_note_text( array $changes ): string {
		$lines = array(
			__( 'Latin Address Checkout converted these address fields to plain ASCII for the delivery label:', 'latin-address-checkout' ),
		);

		foreach ( $changes as $change ) {
			$lines[] = sprintf(
				/* translators: 1: field name, such as "Shipping town / city", 2: value as the customer entered it, 3: value stored on the order */
				__( '%1$s: %2$s → %3$s', 'latin-address-checkout' ),
				get_field_label( $change['address_type'], $change['field'] ),
				$change['original'],
				$change['converted']
			);
		}

		return implode( "\n", $lines );
	}
}
