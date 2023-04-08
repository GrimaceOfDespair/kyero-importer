<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Kyero Importer
 * Plugin URI:        https://wordpress.org/plugins/kyero-importer/
 * Description:       Import Easy Real Estate properties and images from a Kyero feed.
 * Author:            grimaceofdespair
 * Author URI:        https://www.bithive.be/
 * Version:           0.1
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Text Domain:       kyero-importer
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'KYERO_LOAD_IMPORTERS' ) ) {
	return;
}

/** Display verbose errors */
if ( ! defined( 'IMPORT_DEBUG' ) ) {
	define( 'IMPORT_DEBUG', WP_DEBUG );
}

/** WordPress Import Administration API */
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require $class_wp_importer;
	}
}

/** Functions missing in older WordPress versions. */
require_once dirname( __FILE__ ) . '/compat.php';

/** WXR_Parser class */
require_once dirname( __FILE__ ) . '/parsers/class-wxr-parser.php';

/** WXR_Parser_SimpleXML class */
require_once dirname( __FILE__ ) . '/parsers/class-wxr-parser-simplexml.php';

/** WXR_Parser_XML class */
require_once dirname( __FILE__ ) . '/parsers/class-wxr-parser-xml.php';

/** WXR_Parser_Regex class */
require_once dirname( __FILE__ ) . '/parsers/class-wxr-parser-regex.php';

/** Kyero_Import class */
require_once dirname( __FILE__ ) . '/class-kyero-import.php';

function kyero_importer_init() {
	load_plugin_textdomain( 'kyero-importer' );

	/**
	 * WordPress Importer object for registering the import callback
	 * @global Kyero_Import $kyero_import
	 */
	$GLOBALS['kyero_import'] = new Kyero_Import();
	// phpcs:ignore WordPress.WP.CapitalPDangit
	register_importer( 'wordpress', 'WordPress', __( 'Import Easy Real Estate <strong>properties and images</strong> from a Kyero feed.', 'kyero-importer' ), array( $GLOBALS['kyero_import'], 'dispatch' ) );
}
add_action( 'admin_init', 'kyero_importer_init' );
