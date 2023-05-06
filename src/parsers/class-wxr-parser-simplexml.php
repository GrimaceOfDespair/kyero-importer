<?php
/**
 * WordPress eXtended RSS file parser implementations
 *
 * @package Kyero
 * @subpackage Importer
 */

/**
 * WXR Parser that makes use of the SimpleXML PHP extension.
 */
class WXR_Parser_SimpleXML {
	function parse( $file ) {
		$authors    = array();
		$posts      = array();
		$categories = array();
		$tags       = array();
		$terms      = array();

		$internal_errors = libxml_use_internal_errors( true );

		$dom       = new DOMDocument;
		$old_value = null;
		if ( function_exists( 'libxml_disable_entity_loader' ) && PHP_VERSION_ID < 80000 ) {
			$old_value = libxml_disable_entity_loader( true );
		}
		$success = $dom->loadXML( file_get_contents( $file ) );
		if ( ! is_null( $old_value ) ) {
			libxml_disable_entity_loader( $old_value );
		}

		if ( ! $success || isset( $dom->doctype ) ) {
			return new WP_Error( 'SimpleXML_parse_error', __( 'There was an error when reading this WXR file', 'kyero-importer' ), libxml_get_errors() );
		}

		$xml = simplexml_import_dom( $dom );
		unset( $dom );

		// halt if loading produces an error
		if ( ! $xml ) {
			return new WP_Error( 'SimpleXML_parse_error', __( 'There was an error when reading this WXR file', 'kyero-importer' ), libxml_get_errors() );
		}

		$wxr_version = $xml->xpath( '/root/kyero/feed_version' );
		if ( ! $wxr_version ) {
			return new WP_Error( 'WXR_parse_error', __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'kyero-importer' ) );
		}

		$wxr_version = (string) trim( $wxr_version[0] );
		// confirm that we are dealing with the correct file format
		if ( ! preg_match( '/^\d+(\.\d+)?$/', $wxr_version ) ) {
			return new WP_Error( 'WXR_parse_error', __( 'bThis does not appear to be a WXR file, missing/invalid WXR version number', 'kyero-importer' ) );
		}

		$base_url = $xml->xpath( '/rss/channel/wp:base_site_url' );
		$base_url = (string) trim( isset( $base_url[0] ) ? $base_url[0] : '' );

		$base_blog_url = $xml->xpath( '/rss/channel/wp:base_blog_url' );
		if ( $base_blog_url ) {
			$base_blog_url = (string) trim( $base_blog_url[0] );
		} else {
			$base_blog_url = $base_url;
		}

		$namespaces = $xml->getDocNamespaces();
		if ( ! isset( $namespaces['wp'] ) ) {
			$namespaces['wp'] = 'http://wordpress.org/export/1.1/';
		}
		if ( ! isset( $namespaces['excerpt'] ) ) {
			$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
		}

		/*
		// grab authors
		foreach ( $xml->xpath( '/rss/channel/wp:author' ) as $author_arr ) {
			$a                 = $author_arr->children( $namespaces['wp'] );
			$login             = (string) $a->author_login;
			$authors[ $login ] = array(
				'author_id'           => (int) $a->author_id,
				'author_login'        => $login,
				'author_email'        => (string) $a->author_email,
				'author_display_name' => (string) $a->author_display_name,
				'author_first_name'   => (string) $a->author_first_name,
				'author_last_name'    => (string) $a->author_last_name,
			);
		}

		// grab cats, tags and terms
		foreach ( $xml->xpath( '/rss/channel/wp:category' ) as $term_arr ) {
			$t        = $term_arr->children( $namespaces['wp'] );
			$category = array(
				'term_id'              => (int) $t->term_id,
				'category_nicename'    => (string) $t->category_nicename,
				'category_parent'      => (string) $t->category_parent,
				'cat_name'             => (string) $t->cat_name,
				'category_description' => (string) $t->category_description,
			);

			foreach ( $t->termmeta as $meta ) {
				$category['termmeta'][] = array(
					'key'   => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				);
			}

			$categories[] = $category;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:tag' ) as $term_arr ) {
			$t   = $term_arr->children( $namespaces['wp'] );
			$tag = array(
				'term_id'         => (int) $t->term_id,
				'tag_slug'        => (string) $t->tag_slug,
				'tag_name'        => (string) $t->tag_name,
				'tag_description' => (string) $t->tag_description,
			);

			foreach ( $t->termmeta as $meta ) {
				$tag['termmeta'][] = array(
					'key'   => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				);
			}

			$tags[] = $tag;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:term' ) as $term_arr ) {
			$t    = $term_arr->children( $namespaces['wp'] );
			$term = array(
				'term_id'          => (int) $t->term_id,
				'term_taxonomy'    => (string) $t->term_taxonomy,
				'slug'             => (string) $t->term_slug,
				'term_parent'      => (string) $t->term_parent,
				'term_name'        => (string) $t->term_name,
				'term_description' => (string) $t->term_description,
			);

			foreach ( $t->termmeta as $meta ) {
				$term['termmeta'][] = array(
					'key'   => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				);
			}

			$terms[] = $term;
		}
		*/

		$post_id = $image_id = 1;

		// grab posts
		foreach ( $xml->property as $property ) {

			$property_type = (string) $property->type;

			if ( empty( $property->town ) ) {
				$title = $property_type;
			} else {
				$title = "$property_type in $property->town";
			}

			$decription = '';
			$descriptions = $property->desc;
			if ( !empty($descriptions) ) {
				$decription = $descriptions->en;
			}
			$content = $decription;

			$location = '';
			if ( !empty( $property->location ) ) {
				$property_location = $property->location;
				$location = "$property_location->latitude,$property_location->longitude";
			}

			$property_size = '';
			$lot_size = '';
			if ( !empty( $property->surface_area ) ) {
				$property_size = (int) $property->surface_area->built;
				$lot_size = (int) $property->surface_area->plot;
			}

			$address = array();
			if ( !empty( $property->location_detail ) ) {
				$address[]= (string) $property->location_detail;
			}
			if ( !empty( $property->town ) ) {
				$address[]= (string) $property->town;
			}
			if ( !empty( $property->province ) ) {
				$address[]= (string) $property->province;
			}
			if ( !empty( $property->country ) ) {
				$address[]= (string) $property->country;
			}
			$address_text = join( ', ', $address );

			$postmeta = array(
				array(
					'key'   => 'REAL_HOMES_property_id',
					'value' => (string) $property->ref,
				),
				array(
					'key'   => 'REAL_HOMES_property_price',
					'value' => (string) $property->price,	
				),
				array(
					'key'   => 'REAL_HOMES_property_location',
					'value' => $location,
				),
				array(
					'key'   => 'REAL_HOMES_property_bedrooms',
					'value' => (string) $property->beds,
				),
				array(
					'key'   => 'REAL_HOMES_property_bathrooms',
					'value' => (string) $property->baths,
				),
				array(
					'key'   => 'REAL_HOMES_property_size',
					'value' => $property_size,
				),
				array(
					'key'   => 'REAL_HOMES_property_lot_size',
					'value' => $lot_size,
				),
				array(
					'key'   => 'REAL_HOMES_property_address',
					'value' => $address_text,
				),
			);

			$property_city = (string) $property->country;

			$terms = array(
				array(
					'name'          => $property_type,
					'slug'          => sanitize_title( $property_type ),
					'domain'        => 'property-type',
					'term_name'     => $property_type,
					'term_taxonomy' => 'property-type',
				),
				array(
					'name'          => $property_city,
					'slug'          => sanitize_title( $property_city ),
					'domain'        => 'property-city',
					'term_name'     => $property_city,
					'term_taxonomy' => 'property-city',
				),
			);

			if ( $property->features ) {
				foreach ( $property->features->feature as $feature ) {
					$feature_string = (string) $feature;
	
					$terms[] = array(
						'name'          => $feature_string,
						'slug'          => sanitize_title( $feature_string ),
						'domain'        => 'property-feature',
						'term_name'     => $feature_string,
						'term_taxonomy' => 'property-feature',
					);
				}
			}

			if ( $property->images ) {
				$image_index = 1;
				foreach ( $property->images->image as $image ) {

					$image_id++;

					if ( $image_index == 1 ) {
						$postmeta[] = array(
							'key' => '_thumbnail_id',
							'value' => $image_id
						);
					}

					$image_title = "Image $image_index for $title";
					$image_index++;
					$posts[] = array(
						'post_id'        => $image_id,
						'post_title'     => $image_title,
						'post_name'      => sanitize_title( $image_title ),
						'post_type'      => 'attachment',
						'post_date'      => (string) $property->date,
						'post_date_gmt'  => get_gmt_from_date( $property->date ),
						'post_author'    => 'kyero',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => '',
						'comment_status' => 'closed',
						'ping_status'    => 'open',
						'status'         => 'draft',
						'post_parent'    => $post_id,
						'menu_order'     => 0,
						'post_password'  => '',
						'is_sticky'      => false,
						'attachment_url' => (string) $image->url,
					);
				}
			}

			$posts[] = array(
				'post_id'        => $post_id,
				'post_title'     => $title,
				'post_name'      => sanitize_title( $title ),
				'post_type'      => 'property',
				'post_date'      => (string) $property->date,
				'post_date_gmt'  => get_gmt_from_date( $property->date ),
				'post_author'    => 'kyero',
				'post_content'   => $content,
				'post_excerpt'   => wp_trim_excerpt( $decription ),
				'guid'           => '',
				'comment_status' => 'closed',
				'ping_status'    => 'open',
				'status'         => 'draft',
				'post_parent'    => null,
				'menu_order'     => 0,
				'post_password'  => '',
				'is_sticky'      => false,
				'postmeta'       => $postmeta,
				'terms'          => $terms,
			);

			$post_id = ++$image_id;

			/*
			if ( isset( $wp->attachment_url ) ) {
				$post['attachment_url'] = (string) $wp->attachment_url;
			}

			foreach ( $property->category as $c ) {
				$att = $c->attributes();
				if ( isset( $att['nicename'] ) ) {
					$post['terms'][] = array(
						'name'   => (string) $c,
						'slug'   => (string) $att['nicename'],
						'domain' => (string) $att['domain'],
					);
				}
			}

			foreach ( $wp->postmeta as $meta ) {
				$post['postmeta'][] = array(
					'key'   => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				);
			}

			foreach ( $wp->comment as $comment ) {
				$meta = array();
				if ( isset( $comment->commentmeta ) ) {
					foreach ( $comment->commentmeta as $m ) {
						$meta[] = array(
							'key'   => (string) $m->meta_key,
							'value' => (string) $m->meta_value,
						);
					}
				}

				$post['comments'][] = array(
					'comment_id'           => (int) $comment->comment_id,
					'comment_author'       => (string) $comment->comment_author,
					'comment_author_email' => (string) $comment->comment_author_email,
					'comment_author_IP'    => (string) $comment->comment_author_IP,
					'comment_author_url'   => (string) $comment->comment_author_url,
					'comment_date'         => (string) $comment->comment_date,
					'comment_date_gmt'     => (string) $comment->comment_date_gmt,
					'comment_content'      => (string) $comment->comment_content,
					'comment_approved'     => (string) $comment->comment_approved,
					'comment_type'         => (string) $comment->comment_type,
					'comment_parent'       => (string) $comment->comment_parent,
					'comment_user_id'      => (int) $comment->comment_user_id,
					'commentmeta'          => $meta,
				);
			}
			*/
		}
		return array(
			'authors'       => $authors,
			'posts'         => $posts,
			'categories'    => $categories,
			'tags'          => $tags,
			'terms'         => $terms,
			'base_url'      => $base_url,
			'base_blog_url' => $base_blog_url,
			'version'       => $wxr_version,
		);
	}
}
