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

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		add_filter( 'import_allow_create_users', '__return_true' );
		register_post_type(
			'property',
			array( 'public' => true )
		);
		register_taxonomy( 'property-type', 'property' );
		register_taxonomy( 'property-city', 'property' );
		register_taxonomy( 'property-feature', 'property' );

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

		$this->_import_wp(
			DIR_TESTDATA_KYERO_IMPORTER . '/kyero.xml',
			array( 'kyero' => 'admin' )
		);

		$user_count = count_users();
		$this->assertSame( 1, $user_count['total_users'] );
		$admin = get_user_by( 'login', 'admin' );
		$this->assertSame( 'admin', $admin->user_login );

		// Check that posts/pages were imported correctly.
		$property_count = wp_count_posts( 'property' );
		$this->assertSame( '2', $property_count->draft );

		$properties = get_posts(
			array(
				'numberposts' => 10,
				'post_type'   => 'property',
				'post_status' => 'any',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$this->assertCount( 2, $properties );

		$attachments = get_posts(
			array(
				'numberposts' => 10,
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$this->assertCount( 3, $attachments );

		$property = $properties[0];
		$this->assertSame( 'Apartment in Torre de la Horadada', $property->post_title );
		$this->assertSame( 'apartment-in-torre-de-la-horadada', $property->post_name );
		$this->assertSame( (string) $admin->ID, $property->post_author );
		$this->assertSame( 'property', $property->post_type );
		$this->assertSame( 'draft', $property->post_status );
		$this->assertSame( 0, $property->post_parent );

		$post_meta = get_post_meta( $property->ID );
		$this->assertSame( 'CBW-510187', $post_meta['REAL_HOMES_property_id'][0] );
		$this->assertSame( '249500', $post_meta['REAL_HOMES_property_price'][0] );
		$this->assertSame( '37.8652808,-0.7649835', $post_meta['REAL_HOMES_property_location'][0] );
		$this->assertSame( '2', $post_meta['REAL_HOMES_property_bedrooms'][0] );
		$this->assertSame( '2', $post_meta['REAL_HOMES_property_bathrooms'][0] );

		$property = $properties[1];
		$this->assertSame( 'Commercial in Dehesa de Campoamor', $property->post_title );
		$this->assertSame( 'commercial-in-dehesa-de-campoamor', $property->post_name );
		$this->assertSame( (string) $admin->ID, $property->post_author );
		$this->assertSame( 'property', $property->post_type );
		$this->assertSame( 'draft', $property->post_status );
		$this->assertSame( 0, $property->post_parent );
	}

	function test_kyero_import_twice() {
		global $wpdb;

		$this->_import_wp(
			DIR_TESTDATA_KYERO_IMPORTER . '/kyero.xml',
			array( 'kyero' => 'admin' )
		);

		$this->_import_wp(
			DIR_TESTDATA_KYERO_IMPORTER . '/kyero.xml',
			array( 'kyero' => 'admin' )
		);

		// Check that posts/pages were imported correctly.
		$property_count = wp_count_posts( 'property' );
		$this->assertSame( '2', $property_count->draft );

		$properties = get_posts(
			array(
				'numberposts' => 10,
				'post_type'   => 'property',
				'post_status' => 'any',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$this->assertCount( 2, $properties );

		$attachments = get_posts(
			array(
				'numberposts' => 10,
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$this->assertCount( 3, $attachments );

		$property = $properties[0];
		$this->assertSame( 'Apartment in Torre de la Horadada', $property->post_title );

		$post_meta = get_post_meta( $property->ID );
		$this->assertSame( array( 'CBW-510187' ), $post_meta['REAL_HOMES_property_id'] );
		$this->assertSame( array( '249500' ), $post_meta['REAL_HOMES_property_price'] );
		$this->assertSame( array( '37.8652808,-0.7649835' ), $post_meta['REAL_HOMES_property_location'] );
		$this->assertSame( array( '2' ), $post_meta['REAL_HOMES_property_bedrooms'] );
		$this->assertSame( array( '2' ), $post_meta['REAL_HOMES_property_bathrooms'] );

		$property = $properties[1];
		$this->assertSame( 'Commercial in Dehesa de Campoamor', $property->post_title );
	}
}
