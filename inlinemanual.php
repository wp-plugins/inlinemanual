<?php
/*
Plugin Name: Inline Manual
Plugin URI: https://inlinemanual.com
Description: Inline Manual for Wordpress.
Author: Inline Manual
Version: 0.9
Author URI: https://inlinemanual.com
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'INLINEMANUAL__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'INLINEMANUAL__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array( 'InlineManual', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'InlineManual', 'plugin_deactivation' ) );

require_once( INLINEMANUAL__PLUGIN_DIR . 'class.inlinemanual.php' );

add_action( 'init', array( 'InlineManual', 'init' ) );
add_action('wp_enqueue_scripts', array('InlineManual', 'player_inject') );

if ( is_admin() ) {
	require_once( INLINEMANUAL__PLUGIN_DIR . 'class.inlinemanual-admin.php' );
	add_action( 'init', array( 'InlineManual_Admin', 'init' ) );
    add_action('admin_enqueue_scripts', array('InlineManual', 'player_inject') );
}
