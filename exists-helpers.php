<?php
/*
 * FILE EXISTS HELPER FUNCTIONS
 */
 
//helper function to append a number to a filename if the file exists
function apendNumberToFile($str, $entrynum, $fieldid) {
	$path_parts = pathinfo($str);
	$files = listFilesFromFieldAndDirectory($entrynum, $fieldid);
	$filename = $path_parts['filename'];
	$matches = array();
	
	error_log("filename before pregmatch: " . $filename);
	
	if (preg_match('#(\d+)$#', $filename, $matches)) {
		$greatest_increment = incrementGreatestFile($files, $filename);
		
		$trailing_digits = $matches[0];
		$td_count = strlen($trailing_digits);
		$leading_filename = substr($filename, 0, strlen($filename) - $td_count);
		error_log("final file path: " . $leading_filename . $greatest_increment);
		return $leading_filename . incrementGreatestFile($files, $filename) . '.' . $path_parts['extension'];
	} else {
		$path_parts = pathinfo($str);
		return $path_parts['filename'] . '1' . '.' . $path_parts['extension'];
	}
}
//helper function to list all file names to check for existence upon file upload`
function listFilesFromFieldAndDirectory($entrynum, $fieldid) {
	//get folder files
	$upload_dir = wp_upload_dir();
	$directory = $upload_dir['path'];
	$folder_files = scandir($directory);
	//get entry files
	$files = array();
	$entry = GFAPI::get_entry( $entrynum );
	$stuff = json_decode( $entry[$fieldid] );
	foreach($stuff as $filepath) {
		$files[] = basename($filepath);
	}
	return array_merge($files, $folder_files);
}

//helper function to find the greatest trailing number given a file prefix
function incrementGreatestFile($files, $prefix){
	$prefix = preg_replace("/\d+$/", "", $prefix); //remove ending numbers
	$prefix_instances = array();
	foreach($files as $file) {
		if( $prefix === substr($file, 0, strlen($prefix)) ) {
			$thisprefix = pathinfo($file);
			$prefix_instances[] = substr($thisprefix['filename'], strlen($prefix));
		}
	}
	return max($prefix_instances) + 1;
}