<?php
/*
Plugin Name: Optimize Wordpress
Description: Optimize wordpress to speed up the whole process
Version: 1.4.1
Author: Stratos Nikolaidis
Author URI: https://gr.linkedin.com/in/stratosnikolaidis
Plugin URI: https://gr.linkedin.com/in/stratosnikolaidis
License: Toptal
*/


/*
Prevent Contact Form 7 to reload the contact us forms data, in case the page is cached.
*/

/*

Disabled for now, need to complete tests first

remove_action( 'wp_enqueue_scripts', 'wpcf7_do_enqueue_scripts' );

add_action( 'wp_enqueue_scripts', 'wpcf7_do_enqueue_scripts_with_no_cache' );
function wpcf7_do_enqueue_scripts_with_no_cache() {
	if ( wpcf7_load_js() ) {
		wpcf7_enqueue_scripts_with_no_cache();
	}

	if ( wpcf7_load_css() ) {
		wpcf7_enqueue_styles();
	}
}

function wpcf7_enqueue_scripts_with_no_cache() {
	// jquery.form.js originally bundled with WordPress is out of date and deprecated
	// so we need to deregister it and re-register the latest one
	wp_deregister_script( 'jquery-form' );
	wp_register_script( 'jquery-form',
		wpcf7_plugin_url( 'includes/js/jquery.form.min.js' ),
		array( 'jquery' ), '3.51.0-2014.06.20', true );

	$in_footer = true;

	if ( 'header' === wpcf7_load_js() ) {
		$in_footer = false;
	}

	wp_enqueue_script( 'contact-form-7',
		wpcf7_plugin_url( 'includes/js/scripts.js' ),
		array( 'jquery', 'jquery-form' ), WPCF7_VERSION, $in_footer );

	$_wpcf7 = array(
		'loaderUrl' => wpcf7_ajax_loader(),
		'sending' => __( 'Sending ...', 'contact-form-7' ) );

	// if ( defined( 'WP_CACHE' ) && WP_CACHE )
	// 	$_wpcf7['cached'] = 1;

	if ( wpcf7_support_html5_fallback() )
		$_wpcf7['jqueryUi'] = 1;

	wp_localize_script( 'contact-form-7', '_wpcf7', $_wpcf7 );

	do_action( 'wpcf7_enqueue_scripts' );
}

*/


/**
 * Fix Mandrill reset password issue
 * Fix provided by @paulveevers
 */
add_filter( 'retrieve_password_message', 'forgot_password_email', 10, 2 );
function forgot_password_email($message, $key) {

  // Replace first open bracket
  $message = str_replace('<', '', $message);

  // Replace second open bracket
  $message = str_replace('>', '', $message);

  // Convert line returns to <br>'s
  $message = str_replace("\r\n", '<br>', $message);

  return $message;
}

/**
 * Disable plugins for specific pages in order to speed up the page load
 */
// add_filter( 'option_active_plugins', 'disable_plugins_on_demand' );
function disable_plugins_on_demand($plugins){
	// Template
    // if(strpos($_SERVER['REQUEST_URI'], '/store/') === FALSE AND strpos($_SERVER['REQUEST_URI'], '/wp-admin/') === FALSE) {
    //     $key = array_search( 'cart66/cart66.php' , $plugins );
    //     if ( false !== $key ) {
    //         unset( $plugins[$key] );
    //     }
    // }
    return $plugins;
}

require_once(dirname(__FILE__) . "/Segment_curl.php");
Segment_curl::init(
	"wcHZ0hb6Xk6YaoouR6EKNqWYlxwXn6Hu",
	array(
		"consumer" => "fork_curl",
		"debug" => true,
		"max_queue_size" => 5,
		"batch_size" => 5,
	)
);

//add_action( 'user_register', 'analytics_on_user_registration', 10, 1 );
function analytics_on_user_registration( $user_id ) {
    // if ( isset( $_POST['first_name'] ) )
    //     update_user_meta($user_id, 'first_name', $_POST['first_name']);
    if ( $user = get_userdata( $user_id ) ) {
    	Segment_curl::identify(
		    array(
		       'userId' => $user->ID,
		       'traits'  => array(
					'username'  => $user->user_login,
					'email'     => $user->user_email,
					'firstName' => $user->user_firstname,
					'lastName'  => $user->user_lastname,
		       	)
		    )
		);
    	Segment_curl::track(
		    array(
		       	'userId' => $user->ID,
				'event' => 'User registered',
		    )
		);
    }
}

add_action( 'wc_memberships_grant_membership_access_from_purchase', 'tms_track_full_access_pass', 10, 2 );
function tms_track_full_access_pass( $membership_plan, $args ) {

	// If the tracking library isn't active, stop here
	if ( !class_exists( 'Segment_Cookie' ) ) {
		return;
	}

	// If this isn't the full access pass membership plan, stop
	if ( !$membership_plan->id == 3737 ) {
		return;
	}

	$user_id = $args['user_id'];
	Segment_Cookie::set_cookie( 'Full Access Pass', json_encode( $user_id ) );
}


/*
How to use the following scripts. Right now, the shortcode [wcm_restrict plans="free-members"] is like this:

<div class="gdlr-item gdlr-column-shortcode">
    <div class="gdlr-shortcode-wrapper">
        ...

By using this code, we change it like this:

<div class="gdlr-item gdlr-column-shortcode with-image">
    <div class="gdlr-image-wrapper">
        <img src="http://mindsummit.staging.wpengine.com/wp-content/uploads/2015/10/r1.jpg">
    </div>
    <div class="gdlr-shortcode-wrapper">
        ...

And the magic begins. The page by default will display the gdlr-image section, and on hover (or touch) the
page will fade out the image and replace it with the the gdlr-shortcode section (fade in).

Yes, it's responsive also. :)
*/
add_action('wp_enqueue_scripts', 'gdlr_with_imageload_scripts');
function gdlr_with_imageload_scripts() {
    wp_enqueue_style('gdlr-with-image', plugin_dir_url(__FILE__).'assets/css/gdlr-with-image.css');
    wp_enqueue_script('gdlr-with-image', plugin_dir_url(__FILE__).'assets/js/gdlr-with-image.js', array(), '1.1.1', true);
}