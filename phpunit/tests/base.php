<?php

abstract class WP_Import_UnitTestCase extends WP_UnitTestCase {
	/**
	 * Import a Kyero file.
	 *
	 * The $users parameter provides information on how users specified in the import
	 * file should be imported. Each key is a user login name and indicates if the user
	 * should be mapped to an existing user, created as a new user with a particular login
	 * or imported with the information held in the Kyero file. An example of this:
	 *
	 * <code>
	 * $users = array(
	 *   'alice' => 1,      // alice will be mapped to user ID 1.
	 *   'bob'   => 'john', // bob will be transformed into john.
	 *   'eve'   => false   // eve will be imported as is.
	 * );</code>
	 *
	 * @param string $filename Full path of the file to import
	 * @param array $users User import settings
	 * @param bool $fetch_files Whether or not do download remote attachments
	 */
	protected function _import_wp( $filename, $users = array(), $fetch_files = true ) {
		$importer = new Kyero_Import();
		$file     = realpath( $filename );

		$this->assertNotEmpty( $file, 'Path to import file is empty.' );
		$this->assertTrue( is_file( $file ), 'Import file is not a file.' );

		$authors = array();
		$mapping = array();
		$new     = array();
		$i       = 0;

		// Each user is either mapped to a given ID, mapped to a new user
		// with given login or imported using details in Kyero file.
		foreach ( $users as $user => $map ) {
			$authors[ $i ] = $user;
			if ( is_int( $map ) ) {
				$mapping[ $i ] = $map;
			} elseif ( is_string( $map ) ) {
				$new[ $i ] = $map;
			}

			$i++;
		}

		ob_start();
		$importer->fetch_attachments = $fetch_files;
		$importer->import( $file, $authors, $mapping, $new );
		ob_end_clean();

		$_POST = array();
	}
}
