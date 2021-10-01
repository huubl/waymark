<?php

class Waymark_AJAX {
	
	function __construct() {
		//Front-end & Admin
		add_action('wp_ajax_waymark_read_file', array($this, 'read_file'));				
		add_action('wp_ajax_nopriv_waymark_read_file', array($this, 'read_file'));				

		//Admin only
		add_action('wp_ajax_waymark_get_attatchment_meta', array($this, 'get_attatchment_meta'));				
		//add_action('wp_ajax_waymark_shortcode_form', array($this, 'shortcode_form'));				
		
		//Add nonce
		Waymark_JS::add_chunk('var waymark_ajax_security = "' . wp_create_nonce(Waymark_Config::get_item('nonce_string')) . '";');					
	}

// 	function shortcode_form() {
// 		check_ajax_referer(Waymark_Config::get_item('nonce_string'), 'waymark_security');
// 
// 		$Map = new Waymark_Map;
// 
// 		echo '<div class="waymark-parameters-container waymark-self-clear">';
// 		echo '	<div class="waymark-parameter-group waymark-self-clear" id="waymark-shortcode-form">' . "\n";
// 		echo '		<div class="waymark-parameter-group-content">' . "\n";
// 		echo '			<p class="waymark-parameter-group-description">The following options will be used to insert a unique [Waymark]<br /> shortcode.</p>' . "\n";
// 		
// 		$map_id = (isset($_POST['set_data']['map_id'])) ? $_POST['set_data']['map_id'] : null;
// 		echo Waymark_Input::create_field($Map->relationship_field('one', 'Map', 'map_id', 'meta', 'Select which Map to display. Maps can be added or edited in Waymark &gt; Maps.'), $map_id);
// 		
// 		//Height
// 		$map_height = (isset($_POST['set_data']['map_height'])) ? $_POST['set_data']['map_height'] : null;
// 		echo Waymark_Input::create_field(array(
// 			'input_types' => array('meta'),
// 			'name' => 'map_height',
// 			'id' => 'map_height',
// 			'type' => 'text',				
// 			'class' => 'waymark-short-input',
// 			'tip' => 'Specify the desired height of the Map (in pixels). The Map will automatically adjust it’s width to fill the space available to it.',
// 			'group' => 'meta',
// 			'default' => Waymark_Config::get_setting('misc', 'map_options', 'map_height'),
// 			'title' => 'Map Height'
// 		), $map_height);
// 		
// 		echo '			<p style="font-size:10px"><br /><br /><b style="font-size:10px">Pro Tip!</b> You can update an existing shortcode by highlighting it fully in the editor before clicking<br />the Waymark icon.</p>' . "\n";
// 		echo '		</div>' . "\n";
// 		echo '	</div>' . "\n";
// 		echo '</div>' . "\n";
// 										
// 		die;
// 	}	

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
	
	function read_file() {
		check_ajax_referer(Waymark_Config::get_item('nonce_string'), 'waymark_security');

		//Back-end or front-end request?
		$ajax_context = (strpos(wp_get_referer(), get_admin_url()) !== false) ? 'back' : 'front';	
		
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
							//Front-end Submission
							if($ajax_context == 'front') {
								//Ensure user is allowed to do this
								$Submission = new Waymark_Submission;
								if(! in_array('file', $Submission->get_features())) {	
									//Not allowed
									$response_json = json_encode(array(
										'error' => esc_html__('File Permission denied.', 'waymark')
									));				
									
									break;
								}
							}						

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
							//Front-end Submission
							if($ajax_context == 'front') {
									$response_json = json_encode(array(
										'error' => esc_html__('joetest front.', 'waymark')
									));				
									
									break;							
							
								//Ensure user is allowed to do this
								$Submission = new Waymark_Submission;
								if(! in_array('photo', $Submission->get_features())) {	
									//Not allowed
									$response_json = json_encode(array(
										'error' => esc_html__('Photo Permission denied.', 'waymark')
									));				
									
									break;
								}
							}						

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