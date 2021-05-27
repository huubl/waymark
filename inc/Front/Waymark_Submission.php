<?php
	
class Waymark_Submission {
	function __construct() {
		add_filter('the_content', array($this, 'the_content'));
	}

	function the_content($content) {
		global $post;
			
		//Don't do anything if password required
		if(post_password_required()) {
			return $content;
		}
		
		//Only modify Map page		
		if($post->post_type != 'waymark_map') {
			return $content;		
		}
		
		//!!!

		//Front-end submission

		//http://joe-mbp.local/waymark/waymark-wp/wp-admin/post.php?post=848&action=edit
		if($post->ID == 848) {
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
			echo '<div id="waymark-map"></div>' . "\n";
		
			//Output Config
			Waymark_JS::add_chunk('var waymark_settings  = ' . json_encode(get_option('Waymark_Settings')));					
			Waymark_JS::add_call('var waymark_user_config = ' . json_encode(Waymark_Config::get_map_config()) . ';');				
			Waymark_JS::add_call('waymark_user_config.map_height = 600;');				
		
			//Set basemap
			if($editor_basemap = Waymark_Config::get_setting('misc', 'editor_options', 'editor_basemap')) {
				Waymark_JS::add_call('waymark_user_config.map_init_basemap = "' . $editor_basemap . '"');		
			}

			//Go!
			Waymark_JS::add_call('Waymark_Map_Editor.init(waymark_user_config)');

			//Create Feed meta input
			$Map = new Waymark_Map;		
//			$Map->set_input_type('meta');

			$content  = '<form action="' . Waymark_Helper::http_url() . '" method="post" id="waymark-map-add" class="waymark-map-add">' . "\n";
			$content .= '	<input type="hidden" name="waymark_action" value="public_add_map" />' . "\n";
			$content .= '	<input type="hidden" name="waymark_security" value="' . wp_create_nonce('Waymark_Nonce') . '" />' . "\n";
			$content .= '	<textarea id="map_data" name="map_data"></textarea>' . "\n";
			$content .= '	<input type="text" name="map_title" />' . "\n";
			$content .= '	<input type="submit" value="' . __('Submit', 'waymark') . '" class="button" />' . "\n";

			//Meta
			$content .= $Map->create_form();		

			$content .= '</form>' . "\n";
			
			return $content;
		}

		return $content;
	}	
}
new Waymark_Submission;