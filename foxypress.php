<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.webmovementllc.com/foxypress/forum
Description: FoxyPress is a WP + FoxyCart E-commerce plugin to easily integrated FoxyCart into your site and add items to your WordPress pages/posts
Author: WebMovement, LLC
Version: 0.1.4
Author URI: http://www.webmovementllc.com/

**************************************************************************

Copyright (C) 2008-2010 WebMovement, LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************

In short, this plugin is free to use by anyone and everyone. You are
welcome to use it on a commercial site or whatever you want. However, I do
very much appreciate donations for all of my time and effort, although
they obviously aren't required for you to use this plugin.

If you sell this code (i.e. are a web developer selling features provided
via this plugin to clients), it would be very nice if you threw some of
your profits my way. After all, you are profiting off my hard work. ;)

Thanks and enjoy this plugin!

**************************************************************************/

include_once( 'settings.php' );
global $foxypress_url;
$foxypress_url = get_option('foxycart_storeurl');

if ( !empty ( $foxypress_url ) ){
  // init process for button control
  add_action('init', 'myplugin_addbuttons');
  add_action('get_header', 'foxypress_wp_head' );


}

function myplugin_addbuttons() {
   // Don't bother doing this stuff if the current user lacks permissions
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;

   // Add only in Rich Editor mode
   if ( get_user_option('rich_editing') == 'true') {
     add_filter("mce_external_plugins", "add_myplugin_tinymce_plugin");
     add_filter('mce_buttons_3', 'register_myplugin_button');
   }
}

function register_myplugin_button($buttons) {
   array_push($buttons, "foxypress");
   return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_myplugin_tinymce_plugin($plugin_array) {
   $path = url("foxypress");
   $plugin_array['foxypress'] = $path;
   return $plugin_array;
}

// determine absolute url path of editor_plugin.js
function url($type) {
    //check if defined WordPress Plugins URL
	if (defined('WP_PLUGINS_URL'))  {

		return WP_PLUGINS_URL."/". $type ."/editor_plugin.js";

	}else{
    //if not assumme it is default location.
	return "../../../wp-content/plugins/". $type ."/editor_plugin.js";

	}
}

function foxypress_shortcode( $atts, $content = null) {
 	$querystring = "";
 	while (list($key, $value) = each($atts)) {
	    $querystring .= "$key=$value&";
	}
	return '<a class="foxycart" href="https://' . get_option('foxycart_storeurl') . '.foxycart.com/cart?' . $querystring . '">' . $content . '</a>';
}
add_shortcode('foxypress', 'foxypress_shortcode');


function foxypress_wp_head() {
	$version = get_option('foxycart_storeversion');
		echo"
		<script type='text/javascript'>
			if (typeof jQuery == 'undefined') {
				var head = document.getElementsByTagName('head')[0];
				var script = document.createElement('script');
				script.setAttribute('id', 'jQuery' );
				script.setAttribute('type','text/javascript')
				script.setAttribute('src', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
				head.appendChild(script);
			}
    </script>";

		if($version=="0.7.1"){
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.9/style1_fc/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}else{
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.9/style1/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}
}

function foxypress_request($name, $default=null) {
    if (!isset($_REQUEST[$name])) return $default;
    return stripslashes_deep($_REQUEST[$name]);
}


add_action( 'admin_menu', 'foxypress_add_menu' );
function foxypress_add_menu() {
    // Set admin as the only one who can use Inventory for security
    $allowed_group = 'manage_options';

      // Add the admin panel pages for Inventory. Use permissions pulled from above
    if ( function_exists( 'add_menu_page' ) ) {
       add_menu_page( __( 'Foxypress','foxypress' ), __( 'Foxypress','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
    if ( function_exists( 'add_submenu_page' ) ) {
       add_submenu_page( 'foxypress', __( 'Settings','foxypress' ), __( 'Manage Settings','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options');
     }
}

if ( !empty ( $foxypress_url ) ){
  // Include inventory settings and functionality \\
  include_once( 'inventory.php');
}
?>