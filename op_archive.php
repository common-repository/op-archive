<?php
/**
 * @package OrangePants
 * @subpackage Archive
 * @version 0.1.0
 */
/*
Plugin Name: OrangePants Archive
Plugin URI: http://wordpress.org/extend/plugins/op-archive/
Description: Archive your wordpress files and databases.
Author: Derek Downey
Version: 0.1.0
Author URI: http://orange-pants.com/
License: GPLv2
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * OrangePants Archive
 *
 * Archive wordpress site files and database tables
 *
 */
class OP_Archive {
	// MEMBER VARS //
	/**
	 * @var string Location of the local archive directory
	 */
	private $_archiveLocal;

	/**
	 * @var string Location of the url archive directory
	 */
	private $_archiveUrl;

	/**
	 * @var string name of the file to be generated
	 */
	private $_saveFile;

	/**
	 * Create the actions needed to run the plugin
	 *
	 */
	function __construct(){
		$this->doActions();
	}

	// ACCESSORS //
	/**
	 * Current version of the plugin
	 *
	 * @return string
	 */
	public function getVersion() {return "0.1.0";}

	/**
	 * Blog directory is based on Wordpress define 'ABSPATH', but strips trailing slash
	 *
	 * @return string
	 */
	public function getBlogDirectory() {
		return rtrim( ABSPATH, '/' );
	}

	/**
	 * Basic archive directory
	 *
	 * Located under the uploads directory, 'op-archives'.
	 *
	 * @return string
	 */
	public function getArchiveDirectory() {
		if ( !isset( $this->_archiveLocal) ) { $this->setArchiveDirectory(); }
		return $this->_archiveLocal;
	}

	/**
	 * Basic archive url
	 *
	 * Located under the uploads directory, 'op-archives'.
	 *
	 * @return string
	 */
	public function getArchiveUrl() {
		if ( !isset( $this->_archiveUrl) ) { $this->setArchiveDirectory(); }
		return $this->_archiveUrl;
	}

	/**
	 * Name of the savefile
	 *
	 * @return unknown
	 */
	public function getSaveFile() {
		if ( !isset( $this->_saveFile ) ) { $this->setSaveFile(); }
		return $this->_saveFile;
	}

	// MUTATORS //
	/**
	 * Sets archive directory and url information.
	 * Will create the directory if it doesn't exist
	 *
	 * @todo user-controlled save file name
	 * @todo ability to change location of archives
	 */
	private function setArchiveDirectory() {
		$uploadDir = wp_upload_dir();
		$this->_archiveLocal = $uploadDir[ 'basedir' ] ."/op-archives";
		$this->_archiveUrl = $uploadDir[ 'baseurl' ] ."/op-archives";
		@mkdir( $this->_archiveLocal );
	}

	/**
	 * Sets save file
	 *
	 * @todo user-controlled save file name
	 */
	private function setSaveFile() {
		$this->_saveFile = $this->getArchiveDirectory() ."/archive_". date( 'Ymd' ) ."_". date("His") ;
	}

	// ADMIN SETUP //
	/*
	 * Centralized place for adding all actions and filters for the plugin into wordpress
	 */
	public function doActions() {
		add_action( "admin_menu" , array( &$this, "admin_menu" ) );
	}

	/**
	 * Create the admin page under the 'tool' menu
	 *
	 */
    public function admin_menu() {
        $menu = add_management_page( 'OrangePants Archive', 'OP Archive', 10, basename( __FILE__ ), array( &$this, 'admin_tools_page' ) );
        add_action( "admin_print_styles-" . $menu, array( &$this, "admin_styles" ) ); // ONLY ON PLUGIN MENU //
    }

    /**
     * Add screen stylesheet
     *
     */
    public function admin_styles() {
    	wp_enqueue_style( "opArchiveStylesheet", WP_PLUGIN_URL ."/op-archive/styles.css", array(), false, "screen" );
    }

    /**
     * Create the admin tool panel
     *
     * @todo remove selected archives
     * @todo restore from archive
     */
    function admin_tools_page() {
    	// CHECK BACKUP //
        if( $_POST['op_archive_backup'] ){
        	if (! wp_verify_nonce($_POST['_wpnonce'], 'op_archive_form') ) die(_e('There was a problem with the backup post. Please go back and try again.', 'op_archive'));
        	$this->doArchive();
        }

        include_once( "view/adminPanel.php" );
    }

	// FUNCTIONALITY //
	/**
	 * Sanity checks
	 *
	 */
	private function doSanity() {
		// CHECK EXEC RIGHTS //
		// CHECK WRITEABLE FOLDER //
	}

	/**
	 * Perform requested archive
	 *
	 * @todo implement database backup
	 * @todo ability to select what to archive (files, DB, both)
	 * @todo sanity checks: files writeable?
	 */
	private function doArchive() {
		/*@todo SANITY CHECKS */

		_e( "<div id='op_processText'>Beginning archive...<br />", "op_archive" );
		// GENERATE SQL //
		$this->doSQLBackup();

		// RUN FILE ZIP //
		if ($this->doCompress()) {
			_e( "Backup location ". self::getArchiveDirectory() ."/". $this->getSaveFile() ."<br />", "op_archive" );
		}
		_e( "Complete. <br /></div>", "op_archive" );
	}

	/**
	 * Generate sql file of wordpress tables
	 *
	 * @todo add read locks
	 * @todo support mysqldump
	 */
	private function doSQLBackup() {
		global $wpdb;

		// LOOP THROUGH LIST OF WORDPRESS TABLES //
		$sqlFile = $this->getSaveFile().".sql";
		_e( "Creating '". basename( $sqlFile ) ."' file...", "op_archive");

		$sql = "\n"; // START WITH A BLANK LINE //
		foreach ( $wpdb->tables as $wpTable) {

			// DROP TABLE //
			$sql .= "DROP TABLE IF EXISTS `". $wpdb->$wpTable ."`;\n";

			// CREATE TABLE SYNTAX //
			$createTable = array_shift( $wpdb->get_results( "SHOW CREATE TABLE `".$wpdb->$wpTable ."`", ARRAY_A ) );

			$sql .= $createTable['Create Table'] .";\n";

			// ALL INSERTS //
			$records = $wpdb->get_results( "SELECT * FROM `". $wpdb->$wpTable ."`",  ARRAY_A);
			$recordSQL = "INSERT INTO `". $wpdb->$wpTable ."` VALUES ";
			$vals = array();
			foreach ($records as $record) {
				// ESCAPE IT //
				$record = $wpdb->escape( $record );
				$recordStr = '"'. implode('", "', $record) .'"'; // RECORD STRING SUROUNDS THE ARRAY VALUES WITH DOUBLE-QUOTES //
				$recordStr = preg_replace("/\n/", "\\n", $recordStr);

				array_push( $vals, $recordStr );
			}
			$sql .= $recordSQL ." (". implode( " ), (", $vals ) .");\n\n";
		}
		if (@file_put_contents( $sqlFile, $sql )) {
			_e( "success<br />", "op_archive");
		} else {
			_e( "fail<br />", "op_archive");
		}
	}

	/**
	 * Compress wordpress directory
	 *
	 * @todo be able to change compression file types (zip, tar, etc)
	 */
	private function doCompress() {
		// SWITCH TO PARENT DIRECTORY //
		chdir($this->getBlogDirectory());

		// ZIP UP ALL THE FILES/FOLDERS IN THE PATH //
		$archiveFile = $this->getSaveFile() .".tar.gz";
		_e( "Creating '". basename( $archiveFile ) ."' tarball...", "op_archive");
		$cmd = "tar -czvf ". $archiveFile ." * ";
		exec( $cmd );

		if ( file_exists( $archiveFile ) ) {
			_e( "success<br />", "op_archive");
		} else {
			_e( "failed<br />", "op_archive");
		}
	}

	/**
	 * Utilize mysqldump command
	 *
	 * @param string $sqlFile File to dump mysql into
	 * @internal just a holding function, not utilized at the moment
	 */
	private function doMysqldump($sqlFile) {
		$dumpCmd = "/usr/local/mysql/bin/mysqldump -u".DB_USER." -p".DB_PASSWORD." ".DB_NAME." > ".$sqlFile;
		exec($dumpCmd);
	}

	/**
	 * Generate list of archives grouped by date modified
	 *
	 * @return array
	 */
	public function archiveList() {
		$fileArr = array();
		foreach ( glob( $this->getArchiveDirectory() ."/*" ) as $file) {
			$date = date( "m-d-Y", filemtime( $file ) );

			$fileArr[$date][] = str_replace( $this->getArchiveDirectory(), $this->getArchiveUrl(),  $file );
		}

		return $fileArr;
	}
}

$OP_Archive = new OP_Archive();
?>