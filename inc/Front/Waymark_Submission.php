<?php
	
class Waymark_Submission {

	private $allowed;
	
	private $data;
	private $Map;
	
	private $user;
	private $status;
	private $alert;		
	
	public function __construct($data = array()) {	
		//Default
		$this->allowed = false;

		// ======= Check User ========
		
		$this->user = wp_get_current_user();

		//Signed-in user	
		if(sizeof($this->user->roles)) {			
			//Admin
			if(in_array('administrator', $this->user->roles)) {
				$this->allowed = true;
				
				$this->status = 'publish';
			//Current user can
			} elseif($this->user_can_submit()) {
				$this->allowed = true;

				$this->status = Waymark_Config::get_setting('submission', 'from_users', 'submission_status');
				$this->alert = Waymark_Config::get_setting('submission', 'from_users', 'submission_alert');
			//Treat as public?
			} elseif(Waymark_Config::get_setting('submission', 'from_public', 'submission_public')) {
				$this->allowed = true;

				$this->status = Waymark_Config::get_setting('submission', 'from_public', 'submission_status');				
				$this->alert = Waymark_Config::get_setting('submission', 'from_public', 'submission_alert');
			//Curent user can not
			} else {
				$this->allowed = false;		
			}
		//Guest
		} else {
			//Public submissions allowed	
			if(Waymark_Config::get_setting('submission', 'from_public', 'submission_public')) {
				$this->allowed = true;

				$this->status = Waymark_Config::get_setting('submission', 'from_public', 'submission_status');				
				$this->alert = Waymark_Config::get_setting('submission', 'from_public', 'submission_alert');				
			//NO Public submissions!
			} else {
				$this->allowed = false;
			}		
		}
		
		// ======= Other checks? ========

		//!!!
		$this->data = $data;	
		
		//Load
		if($this->allowed) {
			Waymark_Helper::require('Admin/Waymark_AJAX.php');									
		}
	}
	
	public function get_allowed() {
		return $this->allowed;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_alert() {
		return $this->alert;
	}


	private function user_can_submit() {
		//Guest
		if(! sizeof($this->user->roles)) {
			return false;
		}

		//Admin
		if(in_array('administrator', $this->user->roles)) {
			return true;
		}
		
		//Other user
		$submission_roles = Waymark_Config::get_setting('submission', 'from_users', 'submission_roles');
		//Current role can
		if(is_array($submission_roles) && sizeof(array_intersect($this->user->roles, $submission_roles))) {
			return true;
		}
		
		return false;
	}

	public function render_front($data = array()) {
		//Ensure Submissions allowed
		if(! $this->allowed) {
			return false;
		}

		global $post;
		
		$content = '';
			
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
	
	public function create_map() {
		//Ensure Submissions allowed
		if(! $this->allowed) {
			return false;
		}

		//Create Map
		$this->Map = new Waymark_Map;
		$this->Map->set_data($this->data);				
		
		return $this->Map->create_post($this->data['map_title'], array(
			'post_status' => $this->status
		));		
	}
}