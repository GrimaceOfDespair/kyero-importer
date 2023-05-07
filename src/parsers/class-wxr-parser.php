<?php
/**
 * WordPress eXtended RSS file parser implementations
 *
 * @package Kyero
 * @subpackage Importer
 */

/**
 * WordPress Importer class for managing parsing of WXR files.
 */
class WXR_Parser {
	function parse( $file ) {
		// Attempt to use proper XML parsers first
		if ( extension_loaded( 'simplexml' ) ) {
			$parser = new Kyero_Parser;
			$result = $parser->parse( $file );

			// If SimpleXML succeeds or this is an invalid WXR file then return the results
			if ( ! is_wp_error( $result ) || 'SimpleXML_parse_error' != $result->get_error_code() ) {
				return $result;
			}
		}

		// We have a malformed XML file, so display the error and fallthrough to regex
		if ( isset( $result ) && defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
			echo '<pre>';
			if ( 'SimpleXML_parse_error' == $result->get_error_code() ) {
				foreach ( $result->get_error_data() as $error ) {
					echo $error->line . ':' . $error->column . ' ' . esc_html( $error->message ) . "\n";
				}
			} elseif ( 'XML_parse_error' == $result->get_error_code() ) {
				$error = $result->get_error_data();
				echo $error[0] . ':' . $error[1] . ' ' . esc_html( $error[2] );
			}
			echo '</pre>';
			echo '<p><strong>' . __( 'There was an error when reading this WXR file', 'kyero-importer' ) . '</strong><br />';
			echo __( 'Details are shown above. The importer will now try again with a different parser...', 'kyero-importer' ) . '</p>';
		}

		return array();
	}
}
