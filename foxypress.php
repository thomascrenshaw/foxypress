<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.webmovementllc.com/foxypress/forum
Description: FoxyPress is a WP + FoxyCart E-commerce plugin to easily integrated FoxyCart into your site and add items to your WordPress pages/posts
Author: WebMovement, LLC
Version: 0.1.2
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

include_once("cart_validation.php");

$foxyClass = new FoxyCart_Helper;

$foxycart_options = get_option('foxycart');

// init process for button control
add_action('init', 'myplugin_addbuttons');

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

add_action('wp_head', 'foxypress_wp_head');

function foxypress_wp_head() {
	$version = get_option('foxycart_storeversion');
	if(get_option('foxycart_storeurl')!=''){
		echo"
		<script type='text/javascript'>
			if (typeof jQuery == 'undefined') {
				var head = document.getElementsByTagName('head')[0];
				script = document.createElement('script');
				script.id = 'jQuery';
				script.type = 'text/javascript';
				script.src = 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js';
				head.appendChild(script);
			}
		</script>
		";
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
}

function foxypress_request($name, $default=null) {
    if (!isset($_REQUEST[$name])) return $default;
    return stripslashes_deep($_REQUEST[$name]);
}


add_action( 'admin_menu', 'foxypress_add_menu' );
function foxypress_add_menu()
{
    add_options_page('Setup FoxyPress', 'FoxyPress', 8, 'foxypress.php', 'foxypress_options');
}

function foxypress_options()
{
	$len = 16;
	$base='ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
	$max=strlen($base)-1;
	$activatecode='';
	mt_srand((double)microtime()*1000000);
	while (strlen($activatecode)<$len+1){
	  $activatecode.=$base{mt_rand(0,$max)};
	}
	$today = getdate();
	$apikey .= "wmm" . $today['mon'] . $today['mday'] . $today['year'] . $today['seconds'] . $activatecode;

    ?>
    <div class="wrap" style="text-align:center;">
    <img src="../wp-content/plugins/foxypress/img/foxycart_logo.png" />

    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>

    <table class="form-table">
    <tr>
    	<td colspan="2">
    		<p>The FoxyPress Plugin was created to provide users a way to harness the easy to use e-commerce functionality of FoxyCart.</p>
			<p>The plugin can be implemented two different ways:
				<ul>
					<li>Typed WordPress ShortCode</li>
					<li>WordPress Generated ShortCode from the WYSIWYG Editor</li>
				</ul>
			</p>
    	</td>
    </tr>
    <tr valign="top">
		<td align="right" width="300">FoxyCart Store URL</td>
		<td align="left">
			<input type="text" name="foxycart_storeurl" value="<?php echo get_option('foxycart_storeurl'); ?>" size="50" />
		</td>
    </tr>
    <tr valign="top">
		<td align="right" width="300">FoxyCart API Key</td>
		<td align="left">
			<?php
				if(get_option('foxycart_apikey')==''){
					echo'<input type="text" name="foxycart_apikey" value="' . $apikey . '" size="50" readonly />';
				}else{
					echo'<input type="text" name="foxycart_apikey" value="' . get_option("foxycart_apikey") . '" size="50" readonly />';
				}
			?>
			<br />
			*Please copy this into your FoxyCart settings for your Datafeed/API Key
		</td>
    </tr>
    <tr valign="top">
		<td align="right" width="300">FoxyCart Store Version</td>
		<td align="left">
			<select name="foxycart_storeversion" width="300" style="width: 300px">
			<?php
				$version = get_option('foxycart_storeversion');
				if($version=="0.7.1"){
					echo("<option value='0.7.1' selected>0.7.1</option>");
					echo("<option value='0.7.0'>0.7.0</option>");
				}else{
					echo("<option value='0.7.1'>0.7.1</option>");
					echo("<option value='0.7.0' selected>0.7.0</option>");
				}

			?>

			</select>
		</td>
    </tr>
    <tr>
    	<td colspan="2" align="center">
    		<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="foxycart_storeurl,foxycart_apikey,foxycart_storeversion" />
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
    	</td>
    </tr>
    </table>
	<?
		echo($here);
	?>

	</form>
	<img src="../wp-content/plugins/foxypress/img/footer.png" />
	<p style="text-align:center;">Please visit our forum for info and help for all your needs.
		<br />
		<a href="http://www.webmovementllc.com/foxypress/forum" target="_blank">http://www.webmovementllc.com/foxypress/forum</a>
		<br /><br />
		Need a FoxyCart account?  Go to <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">FoxyCart</a> today and sign up!
	</p>
	</div>
    <?php
}
?>