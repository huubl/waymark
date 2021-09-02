<?php
	
class Waymark_Submission {
	function render_front($shortcode_data = array()) {	
		
		//!!!
		//Waymark_Helper::debug($shortcode_data);

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
		//Waymark_JS::add_call('waymark_user_config.map_height = 600;');				
	
		//Set basemap
		if($editor_basemap = Waymark_Config::get_setting('misc', 'editor_options', 'editor_basemap')) {
			Waymark_JS::add_call('waymark_user_config.map_init_basemap = "' . $editor_basemap . '"');		
		}

		//Go!
		Waymark_JS::add_call('Waymark_Map_Editor.init(waymark_user_config)');

		$content  = '<form action="' . Waymark_Helper::http_url() . '" method="post" id="waymark-map-add" class="waymark-map-add">' . "\n";
		$content .= '	<input type="hidden" name="waymark_action" value="public_add_map" />' . "\n";
		$content .= '	<input type="hidden" name="waymark_security" value="' . wp_create_nonce('Waymark_Nonce') . '" />' . "\n";
		
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