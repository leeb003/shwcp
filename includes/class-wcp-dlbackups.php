<?php
/**
 * WCP Class for downloading the zipped backup
 */

    class wcp_dl_backups extends main_wcp {
        // properties

        // methods

		//public function __construct() {
		//	parent::__construct();
		//}

        /**
         * Admin handle download backup zip
         **/


        public function dlbackups_callback() {
			// nonce check
			$nonce = isset($_POST['wcp_dlb_nonce']) ? $_POST['wcp_dlb_nonce'] : '';
			if (!wp_verify_nonce( $nonce, 'wcp_dlb_nonce' )) {
				wp_die("Nonce unverified.");
			}

			// Do the zip and send back
			$backup = trim($_POST['file']);
            $backup_dir = $this->shwcp_upload . '_backups/' . $backup;

            # create a temp file &amp; open it
            $tmp_file = tempnam('.','');
			$zipper = $this->Zip($backup_dir, $tmp_file);

            # send the file to the browser as a download
            //$response['files'] = $files;
            header('Content-disposition: attachment; filename=WP_contacts-' . $backup . '.zip');
            header('Content-type: application/zip');
            readfile($tmp_file);
			exit;
        }

		/*
		 * Zipping recursive process function
         * Source: https://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
		 */
		protected function Zip($source, $destination) {
    		if (!extension_loaded('zip') || !file_exists($source)) {
        		return false;
    		}

    		$zip = new ZipArchive();
    		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        		return false;
    		}

    		$source = str_replace('\\', '/', realpath($source));

    		if (is_dir($source) === true) {
        		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        		foreach ($files as $file) {
            		$file = str_replace('\\', '/', $file);

            		// Ignore "." and ".." folders
            		if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                		continue;

            		$file = realpath($file);

  			 		if (is_dir($file) === true) {
                		$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            		} else if (is_file($file) === true) {
                		$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            		}
        		}
    		}
    		else if (is_file($source) === true) {
        		$zip->addFromString(basename($source), file_get_contents($source));
    		}
    		return $zip->close();
		}


	} // end class
