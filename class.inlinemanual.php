<?php

class InlineManual {
	public static function plugin_activation() {
        // launch default Inline Manual tour
        add_option( 'Activated_Plugin', 'InlineManual' );
        $defaults = array(
          'administrator' => '1'
        );
        update_option('im_admin_roles', $defaults);
	}
	public static function plugin_deactivation() {
	}
    public static function init() {
        // check API Key
        $site_api_key = '';
        if ( is_admin() ) {
            $site_api_key = esc_attr( get_option('site_api_key') );
            // replace site API key with predefined from Inline Manual if empty
            if (empty($site_api_key)) $site_api_key = 'cc1bf7b1596c76e15d9ea6c36dc1c402';
            $roles = get_option('im_admin_roles');
        }
        else {
            $site_api_key = esc_attr( get_option('front_site_api_key') );
            $roles = get_option('im_frontend_roles');
        }
        // check permissions
        $user_ID = get_current_user_id();
        $user_info = get_userdata( $user_ID );
        if (!empty($user_info)) {
            $user_roles = $user_info->roles;
        }
        else {
            $user_roles = array('im_anonymous');
        }

        $roles_match = array_intersect(array_keys($roles), $user_roles);

        // TODO: make it work with Base Path
        // $site_url = parse_url(get_site_url());
        // $site_path = $site_url['path'].'/';
        if (!empty($site_api_key) && !empty($roles_match)) {
            wp_register_script ( 'im_player', 'https://inlinemanual.com/embed/player.' . $site_api_key . '.js', false, false, true );
            wp_enqueue_script( 'im_player' );
        }
    }
}