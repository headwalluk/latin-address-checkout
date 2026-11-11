<?php
/**
 * Main Plugin Class
 *
 * @package Latin_Address_Checkout
 * @since 1.0.0
 */

namespace Latin_Address_Checkout;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Main Plugin Class.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @since 1.0.0
	 * @var ?Plugin
	 */
	private static $instance = null;

	/**
	 * Shortcode checkout adapter.
	 *
	 * @since 1.0.0
	 * @var ?Classic_Checkout
	 */
	private ?Classic_Checkout $classic_checkout = null;

	/**
	 * Checkout block adapter.
	 *
	 * @since 1.0.0
	 * @var ?Block_Checkout
	 */
	private ?Block_Checkout $block_checkout = null;

	/**
	 * Get single instance of the class.
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialise hooks.
	 *
	 * @since 1.0.0
	 */
	public function run(): void {
		$processor  = new Address_Processor();
		$order_note = new Order_Note();

		$this->classic_checkout = new Classic_Checkout( $processor, $order_note );
		$this->classic_checkout->run();

		$this->block_checkout = new Block_Checkout( $processor, $order_note );
		$this->block_checkout->run();

		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );

		// Supports MU plugin installation where activation hooks don't fire.
		add_action( 'admin_init', array( $this, 'check_first_run' ), 1 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Get the shortcode checkout adapter.
	 *
	 * @since 1.0.0
	 * @return ?Classic_Checkout
	 */
	public function get_classic_checkout(): ?Classic_Checkout {
		return $this->classic_checkout;
	}

	/**
	 * Get the Checkout block adapter.
	 *
	 * @since 1.0.0
	 * @return ?Block_Checkout
	 */
	public function get_block_checkout(): ?Block_Checkout {
		return $this->block_checkout;
	}

	/**
	 * Declare HPOS and Checkout block compatibility.
	 *
	 * @since 1.0.0
	 */
	public function declare_woocommerce_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', LATINADDR_FILE, true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', LATINADDR_FILE, true );
		}
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * Needed for the translations shipped in the plugin's own languages/ directory.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'latin-address-checkout', false, dirname( LATINADDR_BASENAME ) . '/languages' );
	}

	/**
	 * Record the installed version when activation didn't, and keep it current.
	 *
	 * @since 1.0.0
	 */
	public function check_first_run(): void {
		$stored_version = get_option( OPTION_VERSION );

		if ( false === $stored_version ) {
			add_option( OPTION_VERSION, LATINADDR_VERSION, '', true );
		} elseif ( LATINADDR_VERSION !== $stored_version ) {
			update_option( OPTION_VERSION, LATINADDR_VERSION );
		} else {
			// Stored version is already current.
		}
	}
}
