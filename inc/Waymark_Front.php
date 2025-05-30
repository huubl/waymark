<?php

class Waymark_Front {
	function __construct() {
		//Don't do anything if we're in the back-end
		if (is_admin()) {
			return;
		}

		add_action('init', [$this, 'init']);
		add_action('wp_head', [$this, 'wp_head']);
	}

	function init() {
		require_once 'Front/Waymark_JS.php';
		require_once 'Front/Waymark_CSS.php';
		require_once 'Front/Waymark_Shortcode.php';
		require_once 'Front/Waymark_Content.php';
		require_once 'Front/Waymark_HTTP.php';
		require_once 'Front/Waymark_Submission.php';
	}

	function wp_head() {
		echo '<meta name="' . esc_attr(Waymark_Config::get_name(true, true)) . ' Version" content="' . esc_attr(Waymark_Config::get_version()) . '" />' . "\n";
	}
}
new Waymark_Front;
