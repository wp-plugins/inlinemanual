<?php

class InlineManual_Admin {
	public static function init() {
		add_action( 'admin_menu', array( 'InlineManual_Admin', 'admin_menu' ) );
		add_action( 'admin_init', array( 'InlineManual_Admin', 'register_settings' ) );
		wp_register_style( 'inlineManualAdminStyle', plugins_url('css/im-admin.css', __FILE__) );

	    if( is_admin() && get_option('Activated_Plugin') == 'InlineManual') {
		    delete_option('Activated_Plugin');
			add_action( 'admin_print_scripts', array('InlineManual_Admin', 'welcome_tour_inline_js' ) );
	    }
	}
	public static function admin_menu() {
		$page = add_submenu_page( 'options-general.php', 'Inline Manual', 'Inline Manual', 'activate_plugins', 'inlinemanual', array('InlineManual_Admin', 'admin_page') );
		add_action( 'admin_print_styles-' . $page, array( 'InlineManual_Admin', 'admin_styles' ) );
	}
	public static function admin_page() {
		echo '<div class="wrap im-config-wrap">';
		echo '<h2 class="im-header">Inline <strong>Manual</strong></h2>';
		echo '<h3>Learn or teach WordPress through in-app tutorials.</h3>';

		echo '<div class="im-column-left">';
		echo '<form method="post" action="options.php">';
		settings_fields( 'general-group' );
		do_settings_sections( 'general-group' );
		echo '<div class="im-box">';
		echo '<h3>Basic settings</h3>';
		echo '<p>';
		_e('These settings allows you to use different Site API key for admin and front-end or you can have same Site API key for both.');
		echo '</p>';
		echo '<table class="form-table">';
		echo '<tbody><tr>';
		echo '<th scope="row"><label for="front_site_api_key">WP Front-end - Site API key</label></th>';
		echo '<td><input type="text" name="front_site_api_key" value="' . esc_attr( get_option('front_site_api_key') ) . '" class="regular-text" />';

		// print the full list of roles with the primary one selected.
		$options = get_option('im_frontend_roles');
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$roles = $wp_roles->get_names();
		echo '<p><input type="checkbox" name="im_frontend_roles[im_anonymous]" value="1" ' . checked( 1, $options['im_anonymous'], false ) . '>Anonymous</p>';
		foreach ($roles as $role_value => $role_name) {
			echo '<p><input type="checkbox" name="im_frontend_roles['.$role_value.']" value="1" ' . checked( 1, $options[$role_value], false ) . '>'.$role_name.'</p>';
		}

		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<th scope="row"><label for="site_api_key">WP Admin - Site API key</label></th>';
		echo '<td><input type="text" name="site_api_key" value="' . esc_attr( get_option('site_api_key') ) . '" class="regular-text" />';

		// print the full list of roles with the primary one selected.
		$options = get_option('im_admin_roles');
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$roles = $wp_roles->get_names();
		foreach ($roles as $role_value => $role_name) {
			echo '<p><input type="checkbox" name="im_admin_roles['.$role_value.']" value="1" ' . checked( 1, $options[$role_value], false ) . '>'.$role_name.'</p>';
		}

		echo '</td>';
		echo '</tr>';
		echo '</table>';

		submit_button();
		echo '</div>';
		echo '</form>';
		echo '</div>';

		echo '<div class="im-column-right">';
		echo '<div class="im-box">';
		echo '<h3>Create your own tutorials</h3>';
		echo '<p>Want to create tutorials for your site to teach your end-users and clients? <br /><a class="button button-cta" href="https://inlinemanual.com/?ref=wordpress-plugin" target="_blank">Sign up for free at InlineManual.com</a></p>';
		echo '<h3>Re-use community created content</h3>';
		echo '<p>Imagine you can create a site documentation in 5 minutes, no more screenshots or videos. We love open source and we want to allow everyone to create <strong>public tutorials</strong> that everyone can easily re-use, extend, improve,...</p>';
		echo '<p>Visit <a href="https://inlinemanual.com/platforms/wordpress?ref=wordpress-plugin" target="_blank">WordPress platform page</a> for public tutorials.</p>';
		echo '<h3>We are on a mission to</h3>';
		echo '<p>revolutionize customer support and how we create documentation for clients and end-users.<br /><strong>Help us spread the word.</strong></p>';
		echo '<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://inlinemanual.com" data-text="Engage and support your customers through in-app tutorials" data-size="large" data-dnt="true">Tweet</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\'://platform.twitter.com/widgets.js\';fjs.parentNode.insertBefore(js,fjs);}}(document, \'script\', \'twitter-wjs\');</script>';
		echo '<p></p>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}
	public static function register_settings() { // whitelist options
	  register_setting( 'general-group', 'site_api_key' );
	  register_setting( 'general-group', 'front_site_api_key' );
	  register_setting( 'general-group', 'im_frontend_roles' );
	  register_setting( 'general-group', 'im_admin_roles' );
	}
	public static function admin_styles() {
		wp_enqueue_style( 'inlineManualAdminStyle' );
	}
	// one time code to launch a welcome tour upon plugin activation
    public static function welcome_tour_inline_js() {
        echo "<script type='text/javascript'>localStorage.setItem('InmPlayerCurrent', '{\"topic_id\":\"2089\",\"step_id\":0}');</script>";
    }
}