<?php

class Waymark_Front {
	function __construct() {
		//Don't do anything if we're in the back-end
		if(is_admin()) {
			return;
		}
		
		add_action('init', array($this, 'init'));
		add_action('wp_head', array($this, 'wp_head'));			
	}
	
	function init() {
		require_once('Front/Waymark_JS.php');
		require_once('Front/Waymark_CSS.php');
		require_once('Front/Waymark_Shortcode.php');
		require_once('Front/Waymark_Content.php');
		require_once('Front/Waymark_HTTP.php');
		if(Waymark_Config::get_setting('misc', 'submission_options', 'allow_submission')) {
			require_once('Admin/Waymark_AJAX.php');					
			require_once('Front/Waymark_Submission.php');
		}
	}

	function wp_head() {
		echo '<meta name="' . Waymark_Config::get_name(true, true) . ' Version" content="' . Waymark_Config::get_version() . '" />' . "\n";	
	}
}	
new Waymark_Front;
