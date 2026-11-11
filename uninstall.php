<?php
/**
 * Latin Address Checkout: Uninstaller
 *
 * Runs when the plugin is deleted from the Plugins screen, not on deactivation.
 * The plugin keeps no settings: this removes the stored version and the
 * updater's cache. Order notes and order meta stay with their orders.
 *
 * @package Latin_Address_Checkout
 */

namespace Latin_Address_Checkout;

// Bail out if not invoked by WordPress's uninstall handler.
defined( 'WP_UNINSTALL_PLUGIN' ) || die();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/includes/class-github-updater.php';

delete_option( OPTION_VERSION );
Github_Updater::delete_cache( basename( __DIR__ ) );
