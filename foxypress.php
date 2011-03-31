<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.webmovementllc.com/foxypress/forum
Description: FoxyPress is a WP + FoxyCart E-commerce plugin to easily integrated FoxyCart into your site and add items to your WordPress pages/posts
Author: WebMovement, LLC
Version: 0.1.9
Author URI: http://www.webmovementllc.com/

**************************************************************************

Copyright (C) 2008-2011 WebMovement, LLC

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
  add_action('wp_head', 'importFoxyScripts' );
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
  global $wpdb; global $foxypress_url;
  $querystring = "";
  $invItems = array();

  $items = $wpdb->get_results("SELECT " . WP_INVENTORY_TABLE . ".*, " . WP_INVENTORY_CATEGORIES_TABLE . ".category_name, " . WP_INVENTORY_IMAGES_TABLE . ".*
    FROM " . WP_INVENTORY_TABLE . ", " . WP_INVENTORY_CATEGORIES_TABLE . ", " . WP_INVENTORY_IMAGES_TABLE . "
    WHERE " . WP_INVENTORY_TABLE .".inventory_id = " . WP_INVENTORY_IMAGES_TABLE . ".inventory_id AND "
    . WP_INVENTORY_TABLE .".category_id = " . WP_INVENTORY_CATEGORIES_TABLE . ".category_id AND " . WP_INVENTORY_TABLE .".inventory_code = " . $atts['code'] . "
    ORDER BY inventory_code DESC");

  // Default product quantity to 1.  Can be overridden by shortcode
  $invItems['quantity'] = 1;

  foreach ( $atts as $key => $value ){
    $invItems[$key] = $value;
    if ( $key == 'image' ){
      $invItems[$key] = INVENTORY_IMAGE_DIR . '/' . $value;
    }
  }
  foreach ( $items as $item ) {
      $invItems['name'] = stripslashes($item->inventory_name);
      $invItems['code'] = stripslashes( $item->inventory_code );
      $invItems['price'] = stripslashes( $item->inventory_price );
      $invItems['category'] = stripslashes( $item->category_name );
      $invItems['image'] = INVENTORY_LOAD_FROM. '/' . stripslashes($item->inventory_image);
      $invItems['weight'] = stripslashes( $item->inventory_weight );
  }

  foreach ( $invItems as $key => $value ){
    //echo $key . ' : ' . $value . '<br />';
    $querystring .= "$key=$value&";
  }

  return '<a class="foxycart" href="https://' . $foxypress_url . '.foxycart.com/cart?' . $querystring . '">' . $content . '</a>';
}
add_shortcode('foxypress', 'foxypress_shortcode');

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
       add_submenu_page( 'foxypress', __( 'Settings','foxypress' ), __( 'Manage Settings','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
}

if ( !empty ( $foxypress_url ) ){
  // Include inventory settings and functionality \\
  include_once( 'inventory.php');
  include_once( 'order-management.php');
  include_once( 'status-management.php');
}

function FixGetVar($variable, $default = '')
{
  $value = $default;
  if(isset($_GET[$variable]))
  {
    $value = trim($_GET[$variable]);
      if(get_magic_quotes_gpc())
      {
      $value = stripslashes($value);
      }
  }
  return $value;
}

function FixPostVar($variable, $default = '')
{
  $value = $default;
  if(isset($_POST[$variable]))
  {
      $value = trim($_POST[$variable]);
      $value = addslashes($value);
  }
  return $value;
}

function curlPostRequest($url, $postData) {
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
  if(!empty($postData))
  {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
  }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
  curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
  $response = curl_exec($ch);
  $info = curl_getinfo($ch);

  if ($response === false || $info['http_code'] != 200) {
    $output = "No cURL data returned for $url [". $info['http_code']. "]";
      if (curl_error($ch))
    {
      $response .= "\n". curl_error($ch);
    }
  }

  curl_close($ch);
    return($response);
}

function GetCurrentPageURL() {
  $pageURL = 'http';
  if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
  $pageURL .= "://";
  if ($_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];
  } else {
    $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER['PHP_SELF'];
  }
  return $pageURL;
}

function GetPagination($page, $total_pages, $limit, $targetpage)
{
	$adjacents = 3;
	if ($page == 0) $page = 1;
	$prev = $page - 1;
	$next = $page + 1;
	$lastpage = ceil($total_pages/$limit);
	$lpm1 = $lastpage - 1;

	$pagination = "";
	if($lastpage > 1)
	{
		$pagination .= "<div class=\"pagination\">";
		//previous button
		if ($page > 1)
			$pagination.= "<a href=\"$targetpage&pagenum=$prev\"><< previous</a>";
		else
			$pagination.= "<span class=\"disabled\"><< previous</span>";

		//pages
		if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
		{
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $page)
					$pagination.= "<span class=\"current\">$counter</span>";
				else
					$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
			}
		}
		elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
		{
			//close to beginning; only hide later pages
			if($page < 1 + ($adjacents * 2))
			{
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&pagenum=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&pagenum=$lastpage\">$lastpage</a>";
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
			{
				$pagination.= "<a href=\"$targetpage&pagenum=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&pagenum=2\">2</a>";
				$pagination.= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&pagenum=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&pagenum=$lastpage\">$lastpage</a>";
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a href=\"$targetpage&pagenum=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&pagenum=2\">2</a>";
				$pagination.= "...";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage?pagenum=$counter\">$counter</a>";
				}
			}
		}
		//next button
		if ($page < $counter - 1)
			$pagination.= "<a href=\"$targetpage&pagenum=$next\">next >></a>";
		else
			$pagination.= "<span class=\"disabled\">next >></span>";
		$pagination.= "</div>\n";
	}
	return $pagination;
}

function importFoxyScripts(){
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
      <script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.2.js" type="text/javascript" charset="utf-8"></script>
      <link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.9/style1_fc/colorbox.2.css" type="text/css" media="screen" charset="utf-8" />
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

?>