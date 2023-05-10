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

if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
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

/** Kyero_Parser class */
require_once dirname( __FILE__ ) . '/parsers/class-kyero-parser.php';

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
	register_importer( 'kyero', 'Kyero', __( 'Import Easy Real Estate <strong>properties and images</strong> from a Kyero feed.', 'kyero-importer' ), array( $GLOBALS['kyero_import'], 'dispatch' ) );
}
add_action( 'admin_init', 'kyero_importer_init' );

function kyero_post_meta( $postmeta, $post_id, $post ) {

	$property_id =  get_existing_property_id( $post );

	// Skip setting kyero properties on reimport
	if ( $property_id ) {
		return array();
	}

	return $postmeta;
}

add_filter( 'wp_import_post_meta', 'kyero_post_meta', 10, 3 );

function kyero_property_exists( $post_exists, $post ) {

	return get_existing_property_id( $post );
}

add_filter( 'wp_import_existing_post', 'kyero_property_exists', 10, 2 );

function get_existing_property_id( $post ) {
	$kyero_ref = get_kyero_ref( $post );

	if ( $kyero_ref ) {

		$properties = get_posts(
			array(
				'numberposts' => 1,
				'post_type'   => 'property',
				'post_status' => 'any',
				'meta_key'    => 'REAL_HOMES_property_id',
				'meta_value'  => $kyero_ref,
			)
		);

		if ( count( $properties ) > 0) {

			$post['postmeta'] = array();

			return $properties[0]->ID;	
		}
	}

	return 0;
}

function get_kyero_ref( $post ) {

	if ( isset( $post['postmeta']) ) {

		$postmeta = $post['postmeta'];

		$index = array_search('REAL_HOMES_property_id', array_column($postmeta, 'key'));

		if ( $index !== false ) {
			return $postmeta[$index]['value'];
		}
	}

	return null;
}