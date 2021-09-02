<?php
	
class Waymark_Submission {

	function __construct() {	
		//Let's ensure that the user can make submissions
		//This is then stored in the constant WAYMARK_SUBMISSIONS_ALLOWED
	
		//Get user
		$user = wp_get_current_user();

		//Signed-in user	
		if(sizeof($user->roles)) {			
			//Admin
			if(in_array('administrator', $user->roles)) {
				define('WAYMARK_SUBMISSIONS_ALLOWED', true);
			//Signed-in user
			} else {
				//Match permissions
				$role_intersect = array_intersect($user->roles, Waymark_Config::get_setting('submission', 'submission_options', 'submission_roles'));
				
				//Allowed to submit
				if(sizeof($role_intersect)) {
					define('WAYMARK_SUBMISSIONS_ALLOWED', true);
				//Not allowed to submit
				} else {
					define('WAYMARK_SUBMISSIONS_ALLOWED', false);
				}
			}
		//Not signed-in
		} else {
			//Public submissions allowed	
			if(Waymark_Config::get_setting('submission', 'submission_options', 'submission_public')) {
				define('WAYMARK_SUBMISSIONS_ALLOWED', true);
			//NO Public submissions!
			} else {
				define('WAYMARK_SUBMISSIONS_ALLOWED', false);
			}		
		}
		
		//Load
		if(WAYMARK_SUBMISSIONS_ALLOWED) {
			Waymark_Helper::require('Admin/Waymark_AJAX.php');									
		}
	}
	
	function render_front($shortcode_data = array()) {
		global $post;
		
		$content = '';
			
		//Submissions allowed
		if(defined('WAYMARK_SUBMISSIONS_ALLOWED') && WAYMARK_SUBMISSIONS_ALLOWED == true) {
			//Messages
			if(array_key_exists('waymark_status', $_REQUEST)) {
				switch($_REQUEST['waymark_status']) {
					case 'draft' :
						$content .= '<div class="waymark-message waymark-success">' . "\n";
						$content .= __('Your submission has been received and is awaiting moderation.');
						$content .= '</div>' . "\n";					

						return $content;		
					
						break;
				}
			}

			//Create new Map object
			Waymark_JS::add_call('var Waymark_Map_Editor = window.Waymark_Map_Factory.editor()');

			//Default view
			if($default_latlng = Waymark_Config::get_setting('misc', 'map_options', 'map_default_latlng')) {
				//We have a valid LatLng
				if($default_latlng_array = Waymark_Helper::latlng_string_to_array($default_latlng)) {
					Waymark_JS::add_call('Waymark_Map_Editor.fallback_latlng = [' . $default_latlng_array[0] . ',' . $default_latlng_array[1] . ']');					
				}
			}
			if($default_zoom = Waymark_Config::get_setting('misc', 'map_options', 'map_default_zoom')) {
				Waymark_JS::add_call('Waymark_Map_Editor.fallback_zoom = ' . $default_zoom);		
			}

			//Map Div
			$content .= '<div id="waymark-map"></div>' . "\n";
	
			//Output Config
			Waymark_JS::add_chunk('var waymark_settings  = ' . json_encode(get_option('Waymark_Settings')));					
			Waymark_JS::add_call('var waymark_user_config = ' . json_encode(Waymark_Config::get_map_config()) . ';');				
			//Waymark_JS::add_call('waymark_user_config.map_height = 600;');				
	
			//Set basemap
			if($editor_basemap = Waymark_Config::get_setting('misc', 'editor_options', 'editor_basemap')) {
				Waymark_JS::add_call('waymark_user_config.map_init_basemap = "' . $editor_basemap . '"');		
			}

			//Go!
			Waymark_JS::add_call('Waymark_Map_Editor.init(waymark_user_config)');

			$content .= '<form action="' . Waymark_Helper::http_url() . '" method="post" id="waymark-map-add" class="waymark-map-add">' . "\n";
			$content .= '	<input type="hidden" name="waymark_action" value="public_add_map" />' . "\n";
			$content .= '	<input type="hidden" name="waymark_security" value="' . wp_create_nonce('Waymark_Nonce') . '" />' . "\n";
			$content .= '	<input type="hidden" name="waymark_redirect" value="' . get_permalink($post) . '" />' . "\n";
		
			//Title
			$content .= '	<div class="waymark-control-group waymark-control-type-text" id="map_date-container">' . "\n";
			$content .= '		<label class="waymark-control-label" for="map_date">' . __('Title', 'waymark') . '</label>' . "\n";
			$content .= '		<div class="waymark-controls">' . "\n";
			$content .= '			<input class="waymark-input" type="text" name="map_title" id="map_title" value="">' . "\n";
			$content .= '		</div>' . "\n";
			$content .= '	</div>' . "\n";

			//Create Form
			$Map = new Waymark_Map;		
			$content .= $Map->create_form();		

			$content .= '	<input type="submit" value="' . __('Submit', 'waymark') . '" class="button button-primary button-large" />' . "\n";

			$content .= '</form>' . "\n";

			return $content;		
		}
	}	
}