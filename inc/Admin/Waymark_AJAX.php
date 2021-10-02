<?php

class Waymark_AJAX {
	
	function __construct() {
		//Public
		add_action('wp_ajax_nopriv_waymark_read_file', array($this, 'handle_read_file'));				

		//User
		add_action('wp_ajax_waymark_read_file', array($this, 'handle_read_file'));				
		add_action('wp_ajax_waymark_get_attatchment_meta', array($this, 'get_attatchment_meta'));				
		
		//Add nonce
		Waymark_JS::add_chunk('var waymark_ajax_security = "' . wp_create_nonce(Waymark_Config::get_item('nonce_string')) . '";');					
	}

	function get_attatchment_meta() {
		check_ajax_referer(Waymark_Config::get_item('nonce_string'), 'waymark_security');

		$response_json = json_encode(array(
			'error' => esc_html__('No image metadata available.', 'waymark')
		));

		//Get photo metadata
		if(array_key_exists('attachment_id', $_POST) && is_numeric($_POST['attachment_id'])) {
			$attachment_metadata = wp_get_attachment_metadata($_POST['attachment_id']);

			if(array_key_exists('image_meta', $attachment_metadata) && is_array($attachment_metadata['image_meta'])) {
				$response_json = json_encode($attachment_metadata['image_meta']);							
			}
		}

		header('Content-Type: text/javascript');
		echo $response_json;
		die;
	}
	
	//Public Submissions
	//Perform additional checks	to ensure user is allowed to do this
	function handle_read_file() {
		check_ajax_referer(Waymark_Config::get_item('nonce_string'), 'waymark_security');

		//Back-end
		if(strpos(wp_get_referer(), get_admin_url()) !== false) {
			//Let's read some files!
			$this->read_file();		
		//Front-end
		} else {
			//This is a submission
			Waymark_Helper::require('Front/Waymark_Submission.php');
			$Submission = new Waymark_Submission;

			$response = array();

			//If we have files
			if(sizeof($_FILES)) {
				//Each file
				foreach($_FILES as $file_key => $file_data) {
					//If no WP error
					if(! $file_data['error']) {
						switch($file_key) {
							//Read from file
							case 'add_file' :
								//Ensure feature allowed
								if(! in_array('file', $Submission->get_features())) {
									//Not allowed
									$response['error'] = esc_html__('Operation not allowed.', 'waymark');				
								}				
							
								break;
							//Photo upload
							case 'marker_photo' :
							case 'add_photo' :
								//Ensure feature allowed
								if(! in_array('photo', $Submission->get_features())) {
									//Not allowed
									$response['error'] = esc_html__('Operation not allowed.', 'waymark');				
								}		
													
								break;
						}
					//WP Error
					} else {
						//Not allowed
						$response['error'] = esc_html__('Operation not allowed.', 'waymark');					
					}
				}
			//No files
			} else {
				//Not allowed
				$response['error'] = $file_data['error'];						
			}

			//Error?
			if(isset($response['error'])) {
				//Do not continue
				header('Content-Type: text/javascript');
				echo json_encode(array(
					'error' => $response['error']
				));	

				die;	
			//Good to continue
			} else {
				//Let's read some files!
				$this->read_file();
			}		
		}
	}
	
	function read_file() {
		check_ajax_referer(Waymark_Config::get_item('nonce_string'), 'waymark_security');

		$response_json = json_encode(array(
			'error' => esc_html__('Unknown file upload error.', 'waymark')
		));

		//If we have files
		if(sizeof($_FILES)) {
			//Each file
			foreach($_FILES as $file_key => $file_data) {
				//If no WP error
				if(! $file_data['error']) {
					$response_json = json_encode($file_data);
					
					switch($file_key) {
						//Read file contents
						case 'add_file' :
							$file_contents = Waymark_Input::get_file_contents($file_data);				
							
							//Good data		
							if(isset($file_contents['file_type']) && in_array($file_contents['file_type'], array('geojson', 'json', 'kml', 'gpx'))) {
								$response_json = json_encode($file_contents);																	
							//Error?
							} elseif(isset($file_contents['error'])) {
								//Use it
								$response_json = json_encode(array(
									'error' => $file_contents['error']
								));								
							}
							
							break;
						case 'marker_photo' :
						case 'add_photo' :
// 							if ( ! function_exists( 'wp_handle_upload' ) ) {
// 									require_once( ABSPATH . 'wp-admin/includes/file.php' );
// 							}
 
 							//Upload
 							$attachment_id = media_handle_upload($file_key, 0);

							$attachment_url = wp_get_attachment_url($attachment_id);

							$response = array(
								'url' => $attachment_url								
							);
							
							//Meta?
							$attachment_metadata = wp_get_attachment_metadata($attachment_id);
							
							//Image Meta
							if(array_key_exists('image_meta', $attachment_metadata) && is_array($attachment_metadata['image_meta'])) {
								//Location EXIF
								if(array_key_exists('GPSLatitudeNum', $attachment_metadata['image_meta']) && array_key_exists('GPSLongitudeNum', $attachment_metadata['image_meta'])) {
									$response = array_merge($response, array(
										'GPSLatitudeNum' => $attachment_metadata['image_meta']['GPSLatitudeNum'],
										'GPSLongitudeNum' => $attachment_metadata['image_meta']['GPSLongitudeNum']										
									));							
								}
							}

							//Sizes
							if(array_key_exists('sizes', $attachment_metadata) && is_array($attachment_metadata['sizes'])) {
								//Each size
								foreach($attachment_metadata['sizes'] as $size_key => &$size) {
									//Add URL
									$size['url'] = wp_get_attachment_image_url($attachment_id, $size_key);
								}
							
								$response = array_merge($response, array(
									'sizes' => $attachment_metadata['sizes']
								));
							}
							
							$response_json = json_encode($response);
  
 							break;
					}								
				//WP error
				} else {
					//Use that
					$response_json = json_encode(array(
						'error' => $file_data['error']
					));				
				}				
			}						
		}

		header('Content-Type: text/javascript');
		echo $response_json;
		die;
	}
}
new Waymark_AJAX;