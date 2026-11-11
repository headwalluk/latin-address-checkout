<?php
/**
 * Plugin Name: Latin Address Checkout
 * Plugin URI: https://github.com/headwalluk/latin-address-checkout
 * Description: Converts accented and typographic characters in WooCommerce checkout addresses to plain ASCII, and asks the customer to re-enter anything that can't be converted, for carriers that only accept ASCII.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 8.3
 * WC tested up to: 11.2
 * Author: Paul Faulkner
 * Author URI: https://headwall-hosting.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: latin-address-checkout
 * Domain Path: /languages
 *
 * @package Latin_Address_Checkout
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

define( 'LATINADDR_VERSION', '1.0.0' );
define( 'LATINADDR_FILE', __FILE__ );
define( 'LATINADDR_PATH', plugin_dir_path( __FILE__ ) );
define( 'LATINADDR_URL', plugin_dir_url( __FILE__ ) );
define( 'LATINADDR_BASENAME', plugin_basename( __FILE__ ) );

require_once LATINADDR_PATH . 'constants.php';
require_once LATINADDR_PATH . 'functions-private.php';

require_once LATINADDR_PATH . 'includes/class-processing-result.php';
require_once LATINADDR_PATH . 'includes/class-address-processor.php';
require_once LATINADDR_PATH . 'includes/class-order-note.php';
require_once LATINADDR_PATH . 'includes/class-classic-checkout.php';
require_once LATINADDR_PATH . 'includes/class-block-checkout.php';
require_once LATINADDR_PATH . 'includes/class-plugin.php';

// GitHub auto-updates: admin, cron and WP-CLI only.
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once LATINADDR_PATH . 'includes/class-github-updater.php';
	new Latin_Address_Checkout\Github_Updater( LATINADDR_FILE, Latin_Address_Checkout\UPDATER_GITHUB_REPO, LATINADDR_VERSION, Latin_Address_Checkout\FILTER_UPDATER_ENABLED );
}

/**
 * Record the installed version on activation.
 *
 * Must-use installs never fire this; Plugin::check_first_run() covers them.
 *
 * @since 1.0.0
 */
function latinaddr_activate(): void {
	add_option( Latin_Address_Checkout\OPTION_VERSION, LATINADDR_VERSION, '', true );
}
register_activation_hook( __FILE__, 'latinaddr_activate' );

/**
 * Initialise the plugin.
 *
 * @since 1.0.0
 */
function latinaddr_run(): void {
	$plugin = Latin_Address_Checkout\Plugin::instance();
	$plugin->run();
}
latinaddr_run();
