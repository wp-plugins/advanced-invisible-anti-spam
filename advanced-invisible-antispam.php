<?php
/*
Plugin Name: Advanced Invisible Anti-Spam
Plugin URI: http://mattkeys.me
Description: Block bots without making your users fill out captchas. Cache Friendly solution with rotating keys!
Author: Matt Keys
Version: 1.2.1
Author URI: http://mattkeys.me
*/

//Path to this file
if ( ! defined( 'AIA_PLUGIN_FILE' ) ) {
	define( 'AIA_PLUGIN_FILE', __FILE__ );
}

//Publicly Accessible path
if ( ! defined( 'AIA_PUBLIC_PATH' ) ) {
	define( 'AIA_PUBLIC_PATH', plugin_dir_url( __FILE__ ) );
}

require 'core/class-advanced-invisible-antispam.php';