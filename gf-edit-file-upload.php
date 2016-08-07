<?php
/**
 * GF Edit File Upload
 *
 * @package   GFEditFileUpload
 * @author    Stephen Siegel
 * @copyright 2016 Stephen Siegel
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: GF Edit File Upload
 * Description: Allows users to edit a file upload field on the frontend. Use shortcode [gfeditupload entryid=# fieldid=#] to edit file upload fields.
 * Version:     1.0
 * Author:      Stephen Siegel
 * Text Domain: gf-edit-file-upload
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

function gfeditupload_func( $atts ) {
	//TODO: handle exceptions
    $a = shortcode_atts( array(
        'entryid' => 0,
        'fieldid' => 0
    ), $atts );
	
	if( $a['entryid'] != 0 && $a['fieldid'] != 0 ) {
		if (class_exists("GFAPI")) {
			do_action('admin_footer');
			
			$entry = GFAPI::get_entry( $a['entryid'] );
			if(!is_wp_error( $entry )) {
				$stuff = json_decode($entry[ $a['fieldid'] ]);
				if($stuff != "") {
					echo "<div id='output-display-uploads'>";
					echo displayFileUploads($stuff, $a['entryid'], $a['fieldid']);
					echo "</div>";
					echo addAFile($a['entryid'], $a['fieldid']);
					echo "<div id='output-upload-errors'></div>";		
				} else {
					echo "Entry #" . $a['entryid'] . " with field ID " . $a['fieldid'] . " appears to be invalid.";
				}
			} else {
				echo "Entry #" . $a['entryid'] . " could not be loaded";
			}
		} else {
			echo "GFAPI class not found.";
		}
	} else {
		echo "both entryid and fieldid must be specified in the shortcode.";
	}
}//end gfeditupload
add_shortcode( 'gfeditupload', 'gfeditupload_func' );


	
add_action( 'wp_ajax_my_del_gf_upload', 'my_del_gf_upload' );
add_action( 'wp_ajax_nopriv_my_del_gf_upload', 'my_del_gf_upload' );

function my_del_gf_upload() {
	
	$errors         = array();      // array to hold validation errors
	$data           = array();      // array to pass back data
	
	if (empty($_POST['entrynum']))
		$errors['entrynum'] = "entry number could not be found";
	if (empty($_POST['fieldid']))
		$errors['fieldid'] = "field ID could not be found";
	if (empty($_POST['filename']))
		$errors['filename'] = "file name could not be found";

	$entrynum = $_POST['entrynum'];
	$fieldid = $_POST['fieldid'];
	$filename = $_POST['filename'];
	
	if (empty($errors)) {
			//get the entry and store the file upload field in an array
			$entry = GFAPI::get_entry( $entrynum );
			$file_split = json_decode($entry[ $fieldid ]);
			
			//remove the filename from the array
			$removed = false;
			foreach($file_split as $key => $value) {
				if(basename($value) == $filename) {
					unset( $file_split[$key] );
					$removed = true;
				}
			}
			
			if (!$removed)
				$errors['unremoved'] = "The specified filename doesn't exist";
			
			//format it back to a string
			if(count($file_split) > 0) {
				$value = '["' . implode('", "', $file_split) . '"]';
			} else {
				$value = '[]';
			}
			
			//update it with the GFAPI
			$result_update = GFAPI::update_entry_field( $entrynum, $fieldid, $value );
			if(!$result_update)
				$errors['unupdated'] = "The file upload field could not be updated";
	}
	
	if ( ! empty($errors)) {
        // if there are items in our errors array, return those errors
        $data['success'] = false;
        $data['errors']  = $errors;
    } else {
        // if there are no errors process our form, then return a message		
		$entry = GFAPI::get_entry( $entrynum );
		$stuff = json_decode( $entry[$fieldid] );
		$data['output'] = displayFileUploads($stuff, $entrynum, $fieldid);
        // show a message of success and provide a true success variable
        $data['success'] = true;
    }

    // return all our data to an AJAX call
    echo json_encode($data);

	die();
}

function displayFileUploads($stuff, $entryid, $fieldid) {	
	$upload_form_counter = 0;
	if(count($stuff) > 0) {
		$returnstring = "<table>";
		foreach($stuff as $thing) {
			$upload_form_counter++;
			$returnstring .= '<tr><td>';
			$returnstring .= '<form method="post" action="' . admin_url( 'admin-ajax.php' ) . '?action=my_del_gf_upload" id="del-gf-upload-' . $upload_form_counter . '">
								
								<input type="hidden" name="entrynum" value="' . $entryid . '">
								<input type="hidden" name="fieldid" value="' . $fieldid . '">
								<input type="hidden" name="filename" value="' . basename($thing) . '">
								
								<button type="submit" id="submit-del-gf-upload-' . $upload_form_counter . '" class="del-gf-upload-style"><i class="fa fa-trash-o" aria-hidden="true"></i></a></button>
							  </form>';
			$returnstring .= '</td><td><div id="display-file-' . $upload_form_counter . '"><a href="' . $thing . '" target="_blank">';
			$returnstring .= basename($thing);
			$returnstring .= '</a></div></td></tr>';
		}
		$returnstring .= "</table>";
	} else {
		$returnstring = "No files currently exist.";
	}
	$returnstring .= "</div>";
	return $returnstring;
}

function addAFile($entryid, $fieldid) {
	echo '
	<div id="reveal-file-upload">
		<i class="fa fa-plus-circle" aria-hidden="true"></i> Add a file
	</div>
	<br/>
	<div id="progress">
			<div id="bar"></div>
			<div id="percent">0%</div >
	</div>
	<br/>
		
	<div id="upload-form-message"></div>
	
	<div id="form-file-upload">
		<form action="' . admin_url( 'admin-ajax.php' ) . '?action=my_add_gf_upload" method="post" class="upload-form" enctype="multipart/form-data">
			Select image to upload:
			<input type="file" name="fileToUpload" id="fileToUpload">
			<input type="hidden" name="entrynum" value="' . $entryid . '">
			<input type="hidden" name="fieldid" value="' . $fieldid . '">
			<input type="submit" value="Upload Image" name="submit">
		</form>
	</div>
	';
}

//upload functionallity

add_action( 'wp_ajax_my_add_gf_upload', 'my_add_gf_upload' );
add_action( 'wp_ajax_nopriv_my_add_gf_upload', 'my_add_gf_upload' );

function my_add_gf_upload() {
	$errors         = array();      // array to hold validation errors
	$data           = array();      // array to pass back data
	
	if (empty($_POST['entrynum']))
		$errors['entrynum'] = "entry number could not be found";
	
	if (empty($_POST['fieldid']))
		$errors['fieldid'] = "fieldid could not be found";
	
	if (empty($_FILES["fileToUpload"]["name"]))
		$errors['filename'] = "file name could not be found";
	
	
	$entrynum = $_POST['entrynum'];
	$fieldid = $_POST['fieldid'];
	$filename = $_FILES["fileToUpload"]["name"];
	
	$upload_dir = wp_upload_dir();	 
	$target_dir = $upload_dir['path'] . '/';
	
	
	if (empty($errors)) {
		$target_file = $target_dir . basename($filename);
		$target_file_url = $upload_dir['url'] . '/' . basename($filename);
		$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
		
		// Check if file already exists
		if (file_exists($target_file)) {
			$errors['exists'] = "the specified file already exists";
		}
		
		// Check file size
		if ($_FILES["fileToUpload"]["size"] > 500000) {
			$errors['size'] = "the specified file exceeds maximum file size";
		}
		
		// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
		&& $imageFileType != "gif" && $imageFileType != "pdf" ) {
			$errors['type'] =  "Only JPG, JPEG, PNG, GIF & PDF files are allowed.";
		}
	}
	
	//format it for writing to entry
	if(empty($errors)) {
		$entry = GFAPI::get_entry( $entrynum );
		$file_split = json_decode($entry[ $fieldid ]);
		
		//add the file to the entry
		array_push($file_split, $target_file_url);
		
		//format it back to a string
		$value = '["' . implode('", "', $file_split) . '"]';
		
		//update it with the GFAPI
		$result_update = GFAPI::update_entry_field( $entrynum, $fieldid, $value );
		//echo 'entrynum: ' . $entrynum . ' - fieldid: ' . $fieldid . ' - value: ' . $value;
		//print_r($data);
		
		if(!$result_update)
			$errors['unupdated'] = "The file upload field could not be updated";
		
	}
	
	//check for errors
	if (!empty($errors)) {
		$data['success'] = false;
        $data['errors']  = $errors;
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			$data['success'] = true;
			
			$entry = GFAPI::get_entry( $entrynum );
			$stuff = json_decode( $entry[$fieldid] );
			$data['output'] = displayFileUploads($stuff, $entrynum, $fieldid);
			//$data['success'] = "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
		} else {
			$data['success'] = false;
			$data['errors']  = $errors;
		}
	}
	
	//echo 'hello world';
	echo json_encode($data);

	die();
}

function wpdocs_theme_name_scripts() {
	wp_register_script( 'form_upload_ajax',  plugin_dir_url(__FILE__) . 'assets/form_ajax.js' );
	wp_register_script( 'jquery_form',  plugin_dir_url(__FILE__) . 'assets/jquery.form.js' );
	wp_register_style( 'file_upload_styles',  plugin_dir_url(__FILE__) . 'assets/file_upload.css' );
	
    wp_enqueue_style( 'fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css' );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'form_upload_ajax');
    wp_enqueue_script( 'jquery_form');
	wp_enqueue_style('file_upload_styles');
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );

?>