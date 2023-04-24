<?php

require_once dirname( __FILE__ ) . '/base.php';

/**
 * @group import
 */
class Tests_Import_Import extends WP_Import_UnitTestCase {
	function set_up() {
		parent::set_up();

		if ( ! defined( 'KYERO_IMPORTING' ) ) {
			define( 'KYERO_IMPORTING', true );
		}

		if ( ! defined( 'KYERO_LOAD_IMPORTERS' ) ) {
			define( 'KYERO_LOAD_IMPORTERS', true );
		}

		add_filter( 'import_allow_create_users', '__return_true' );

		global $wpdb;
		// Crude but effective: make sure there's no residual data in the main tables.
		foreach ( array( 'posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta' ) as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$wpdb->$table}" );
		}
	}

	function tear_down() {
		remove_filter( 'import_allow_create_users', '__return_true' );

		parent::tear_down();
	}

	function test_kyero_import() {
		global $wpdb;

		$authors = array(
			'admin'  => false,
		);
		$this->_import_wp( DIR_TESTDATA_KYERO_IMPORTER . '/kyero.xml', $authors );

		// Check that posts/pages were imported correctly.
		$property_count = wp_count_posts( 'property' );
		$this->assertSame( '2', $property_count->draft );

		$properties = get_posts(
			array(
				'numberposts' => 3,
				'post_type'   => 'any',
				'post_status' => 'any',
				'orderby'     => 'ID',
			)
		);
		$this->assertCount( 2, $properties );

		$property = $properties[0];
		$this->assertSame( 'Apartment in Torre de la Horadada', $property->post_title );
		$this->assertSame( 'apartment-in-torre-de-la-horadada', $property->post_name );
		$this->assertSame( (string) $admin->ID, $property->post_author );
		$this->assertSame( 'property', $property->post_type );
		$this->assertSame( 'draft', $property->post_status );
		$this->assertSame( 0, $property->post_parent );

		$property = $properties[1];
		$this->assertSame( 'Commercial in Dehesa de Campoamor', $property->post_title );
		$this->assertSame( 'commercial-in-dehesa-de-campoamor', $property->post_name );
		$this->assertSame( (string) $admin->ID, $property->post_author );
		$this->assertSame( 'property', $property->post_type );
		$this->assertSame( 'draft', $property->post_status );
		$this->assertSame( 0, $property->post_parent );
	}
}
