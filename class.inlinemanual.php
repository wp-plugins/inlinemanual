<?php

class InlineManual {
	public static function plugin_activation() {
        // launch default Inline Manual tour
        add_option( 'Activated_Plugin', 'InlineManual' );
	}
	public static function plugin_deactivation() {
	}
    public static function init() {
        // check API Key
        $site_api_key = esc_attr( get_option('site_api_key') );
        // replace site API key with predefined from Inline Manual if empty
        if (empty($site_api_key)) $site_api_key = 'cc1bf7b1596c76e15d9ea6c36dc1c402';

        wp_register_script ( 'im_player', 'https://inlinemanual.com/embed/player.' . $site_api_key . '.js', false, false, true );
        wp_enqueue_script( 'im_player' );
    }
}