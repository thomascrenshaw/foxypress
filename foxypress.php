<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.foxy-press.com/
Description: FoxyPress provides a complete shopping cart and inventory management tool for use with FoxyCart's e-commerce solution. Easily manage inventory, view and track orders, generate reports and much more.
Author: WebMovement, LLC
Version: 0.3.6.1
Author URI: http://www.webmovementllc.com/

**************************************************************************

FoxyPress provides a complete shopping cart and inventory management tool for use with FoxyCart's e-commerce solution.
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
your profits my way. Or even better, we'd love to include your new
functionality in the master package we distribute.  I can be contacted
through the plugin website at any time.

Thanks and enjoy this plugin!

**************************************************************************/

register_activation_hook( __FILE__ , 'foxypress_activate');
register_uninstall_hook( __FILE__ , 'foxypress_deactivate');
global $foxypress_url;
$foxypress_url = get_option('foxycart_storeurl');

//init
include_once('custom-post-type.php');
include_once('foxypress-settings.php');
add_action('admin_menu', 'foxypress_menu');
add_action('admin_print_styles', 'foxypress_admin_css');
add_action('admin_print_footer_scripts', 'foxypress_admin_js');
add_action('admin_init', 'foxypress_RunUpdates');
add_action('init', 'foxypress_FlushRewrites');
if ( !empty ( $foxypress_url ) ){
	add_action('init', 'foxypress_addbuttons');
	add_action('wp_head', 'foxypress_ImportFoxypressScripts' );
	add_shortcode('foxypress', 'foxypress_shortcode');
	add_action('widgets_init', 'foxypress_load_minicart' );

	add_action('edit_user_profile', 'foxypress_affiliate_profile_fields');
	add_action('show_user_profile', 'foxypress_affiliate_profile_fields');
	add_action('personal_options_update', 'foxypress_save_affiliate_profile_fields');
	add_action('edit_user_profile_update', 'foxypress_save_affiliate_profile_fields');
	add_action('admin_print_scripts-user-edit.php', 'affiliate_profile_enqueue');
	add_action('admin_print_scripts-profile.php', 'affiliate_profile_enqueue');
	add_action('admin_print_scripts-foxypress_product_page_affiliate-signup', 'affiliate_profile_enqueue');
}
if(get_option('foxycart_enable_sso') == "1")
{
	add_action('profile_update', 'foxypress_UpdateUser');
	add_action('password_reset', 'foxypress_PasswordReset');
}
if (function_exists('is_multisite') && is_multisite())
{
	if(get_option('foxycart_enable_sso') == "1") { add_action('wpmu_new_user', 'foxypress_RegisterUser'); }
	add_action('wpmu_new_blog', 'foxypress_InstallBlog');
	add_action('delete_blog', 'foxypress_UninstallBlog');
}
else
{
	if(get_option('foxycart_enable_sso') == "1") { add_action('user_register', 'foxypress_RegisterUser'); }
}
$foxypress_locale = get_option('foxycart_currency_locale');
setlocale(LC_MONETARY, ($foxypress_locale != "") ? $foxypress_locale  : get_locale());
$foxypress_localesettings = localeconv();
if ($foxypress_localesettings['int_curr_symbol'] == "") setlocale(LC_MONETARY, 'en_US');
// foxypress constants
define('INVENTORY_IMAGE_DIR', get_bloginfo("url") . "/wp-content/inventory_images");
define('INVENTORY_IMAGE_LOCAL_DIR', "wp-content/inventory_images/");
define('INVENTORY_DOWNLOADABLE_DIR', get_bloginfo("url") . "/wp-content/inventory_downloadables");
define('INVENTORY_DOWNLOADABLE_LOCAL_DIR', "wp-content/inventory_downloadables/");
define('INVENTORY_DEFAULT_IMAGE', "default-product-image.jpg");
define('FOXYPRESS_USE_COLORBOX', '1');
define('FOXYPRESS_USE_LIGHTBOX', '2');
define('FOXYPRESS_CUSTOM_POST_TYPE', 'foxypress_product');
define('WP_FOXYPRESS_CURRENT_VERSION', "0.3.6.1");
define('FOXYPRESS_PATH', dirname(__FILE__));
if ( !empty ( $foxypress_url ) ){

	include_once('foxypress-helpers.php');
	include_once('foxypress-redirect.php');
	include_once('inventory-option-groups.php');
	include_once('inventory-category.php');
	include_once('reports.php');
	include_once('status-management.php');
	include_once('subscriptions.php');
	include_once('import-export.php');
	include_once('order-management.php');
	include_once('affiliate-management.php');
	include_once('affiliate-signup.php');
	include_once('foxypress-templates.php');
	include_once('foxypress-manage-emails.php');

	if(defined('FOXYPRESS_SMTP_MAIL_PATH') && defined('FOXYPRESS_SMTP_MAIL_PATH')!='') {
		require_once(FOXYPRESS_SMTP_MAIL_PATH);
	}
	require_once('classes/foxycart.cart_validation.php');
	FoxyCart_Helper::$cart_url = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/cart";
	FoxyCart_Helper::$secret = get_option('foxycart_apikey');
}

if(get_option("foxycart_show_dashboard_widget") == "1")
{
	add_action('wp_dashboard_setup', 'foxypress_DashboardSetup');
}

function foxypress_menu()
{
	global $foxypress_url;
	global $current_user;
	if ( !empty ( $foxypress_url  ) )
	{
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Option Groups'), __('Manage Option Groups'), 'manage_options', 'inventory-option-groups', 'foxypress_inventory_option_groups_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Categories'), __('Manage Categories'), 'manage_options', 'inventory-category', 'foxypress_inventory_category_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Emails'), __('Manage Emails'), 'manage_options', 'manage-emails', 'foxypress_manage_emails_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Order Management'), __('Order Management'), 'manage_options', 'order-management', 'order_management_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Status Management'), __('Status Management'), 'manage_options', 'status-management', 'status_management_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Affiliate Management'), __('Affiliate Management'), 'manage_options', 'affiliate-management', 'foxypress_create_affiliate_table');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Reports'), __('Reports'), 'manage_options', 'reports', 'foxypress_reports_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Subscriptions'), __('Subscriptions'), 'manage_options', 'subscriptions', 'foxypress_subscriptions_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Templates'), __('Templates'), 'manage_options', 'templates', 'foxypress_templates_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Import/Export'), __('Import/Export'), 'manage_options', 'import-export', 'import_export_page_load');
		$user_id = $current_user->ID;
		$affiliate_user = get_the_author_meta('affiliate_user', $user_id);
		if ($affiliate_user !== 'true' && !current_user_can('administrator')) {
			add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Affiliate Sign Up'), __('Affiliate Sign Up'), 'read', 'affiliate-signup', 'foxypress_create_affiliate_signup');
		}
	}
	add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Settings'), __('Settings'), 'manage_options', 'foxypress-settings', 'foxypress_settings_page_load');
}

function foxypress_save_meta_data($post_id, $fieldname, $input)
{
	$current_data = get_post_meta($post_id, $fieldname, TRUE);
 	$new_data = $input;
 	if ($new_data == "") $new_data = NULL;
	if (!is_null($current_data))
	{
		if (is_null($new_data)) delete_post_meta($post_id,$fieldname);
		else update_post_meta($post_id,$fieldname,$new_data);
	}
	elseif (!is_null($new_data)) {
		add_post_meta($post_id,$fieldname,$new_data);
	}
}

function foxypress_affiliate_profile_fields($user)
{
	if (current_user_can('administrator'))
	{
		global $wpdb;

		$affiliate_user 		 = get_user_option('affiliate_user', $user->ID);
		$affiliate_avatar_name   = get_user_option('affiliate_avatar_name', $user->ID);
		$affiliate_avatar_ext    = get_user_option('affiliate_avatar_ext', $user->ID);
		$affiliate_payout_type   = get_user_option('affiliate_payout_type', $user->ID);
		$affiliate_payout    	 = get_user_option('affiliate_payout', $user->ID);
		$affiliate_url  		 = get_user_option('affiliate_url', $user->ID);
		$affiliate_facebook_page = get_user_option('affiliate_facebook_page', $user->ID);
		$affiliate_age 			 = get_user_option('affiliate_age', $user->ID);
		$affiliate_gender		 = get_user_option('affiliate_gender', $user->ID); ?>

		<h3>FoxyPress Affiliate Information</h3>
			<table class="form-table">
				<tr>
					<th><label for="affiliate_user">Enable Affiliate</label></th>
					<td><input type="checkbox" <?php if ($affiliate_user == 'true') { ?>checked="yes" <?php } ?>name="affiliate_user" id="affiliate_user" value="true" /> Is this an affiliate user?</td>
				</tr>
				<tr>
					<th><label for="affiliate_avatar">Avatar</label></th>
					<td>
						<div id="avatar"><?php if ($affiliate_avatar_name) { ?><img src="<?php echo content_url(); ?>/wp-content/affiliate_images/<?php echo $affiliate_avatar_name; ?>-large<?php echo $affiliate_avatar_ext; ?>" width="96" height="96" alt="" /><?php } ?></div>
						<input type="file" name="avatar_upload" id="avatar_upload" value="">
						<input type="hidden" name="affiliate_avatar_name" id="affiliate_avatar_name" value="<?php echo $affiliate_avatar_name; ?>">
						<input type="hidden" name="affiliate_avatar_ext" id="affiliate_avatar_ext" value="<?php echo $affiliate_avatar_ext; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_facebook_page">Affiliate Facebook Page</label></th>
					<td>
						<input class="regular-text" type="text" name="affiliate_facebook_page" id="affiliate_facebook_page" value="<?php echo $affiliate_facebook_page; ?>">
						<span class="description">Affiliate's Facebook Page.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_age">Affiliate Age</label></th>
					<td>
						<input type="text" name="affiliate_age" id="affiliate_age" value="<?php echo $affiliate_age; ?>">
						<span class="description">Affiliate's age.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_gender">Affiliate Gender</label></th>
					<td>
						<input type="text" name="affiliate_gender" id="affiliate_gender" value="<?php echo $affiliate_gender; ?>">
						<span class="description">Affiliate's gender.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_payout_type">Affiliate Payout Type</label></th>
					<td>
						<input type="radio" <?php if ($affiliate_payout_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="percentage">
						<span class="description">Percentage of each order.</span>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="radio" <?php if ($affiliate_payout_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="dollars">
						<span class="description">Dollar amount of each order.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_payout">Affiliate Payout</label></th>
					<td>
						<input type="text" name="affiliate_payout" id="affiliate_payout" value="<?php echo $affiliate_payout; ?>">
						<span class="description">How much will this affiliate earn per sale? <b>(Enter 30 for 30% or $30.00)</b></span>
					</td>
				</tr>
				<tr>
					<th><label>Affiliate URL</label></th>
					<td><?php echo $affiliate_url; ?></td>
				</tr>
			</table>
	<?php }
}

function affiliate_profile_enqueue() { ?>
	<link href="<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript" language="javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" language="javascript" src="<?php echo plugins_url(); ?>/foxypress/uploadify/jquery.uploadify.min.js"></script>
	<script type="text/javascript" language="javascript">
		$(document).ready(function() {
			$("#avatar_upload").uploadify({
				'swf'			: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.swf',
				'uploader'		: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.php',
				'cancelImage'	: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify-cancel.png',
				'createFolder'  : true,
				'checkExisting' : false,
				'fileSizeLimit' : 1*1024, // 1MB
				'fileTypeDesc'  : 'Only images with extensions: *.jpg, *.jpeg, *.png, *.gif are allowed',
				'fileTypeExts'  : '*.gif;*.jpg;*.jpeg;*.png',
				'method'        : 'post',
				'queueSizeLimit': 1,
				'postData'      : {},
				'progressData'  : 'all',
				'multi'			: false,
				'auto'			: true,
				'buttonText'	: 'UPLOAD IMAGE',
				'onUploadStart' : function(file) {
					$('div#avatar').html('<span class="avatar-loader"><img src="<?php echo plugins_url(); ?>/foxypress/img/ajax-loader-cir.gif" width="32" height="32" alt="" /></span>');
				},
				'onUploadSuccess': function (file,data,response) {
					var fileinfo = jQuery.parseJSON(data);
					$('input#affiliate_avatar_name').val(fileinfo.raw_file_name);
					$('input#affiliate_avatar_ext').val(fileinfo.file_ext);
					$('div#avatar').html('<img src="<?php echo content_url(); ?>/wp-content/affiliate_images/' + fileinfo.raw_file_name + '-large' + fileinfo.file_ext + '" width="96" height="96" alt="" />');
				}
			});
		});
	</script>
<?php }


function foxypress_save_affiliate_profile_fields($user_id) {
	if (current_user_can('administrator'))
	{
		global $wpdb, $foxypress_url;
		if (!current_user_can('edit_user', $user_id))
			return false;

		$affiliate_user = $_POST['affiliate_user'];
		//On Save check to see if user is currently pending so we don't clear that with a blank save
		//Below is for when admin submits form without filling out affiliate fields
		if (!$affiliate_user) {
			$affiliate_user = get_user_option('affiliate_user', $user->ID);
		}

		$affiliate_avatar_name   = $_POST['affiliate_avatar_name'];
		$affiliate_avatar_ext    = $_POST['affiliate_avatar_ext'];
		$affiliate_facebook_page = $_POST['affiliate_facebook_page'];
		$affiliate_age 			 = $_POST['affiliate_age'];
		$affiliate_gender 		 = $_POST['affiliate_gender'];
		$affiliate_payout_type 	 = $_POST['affiliate_payout_type'];
		$affiliate_payout 		 = $_POST['affiliate_payout'];

		if ($affiliate_payout_type == 'percentage') {
			$affiliate_payout_type = 1;
		} else if ($affiliate_payout_type == 'dollars') {
			$affiliate_payout_type = 2;
		}

		update_user_option($user_id, 'affiliate_user', $affiliate_user);
		update_user_option($user_id, 'affiliate_avatar_name', $affiliate_avatar_name);
		update_user_option($user_id, 'affiliate_avatar_ext', $affiliate_avatar_ext);
		update_user_option($user_id, 'affiliate_facebook_page', $affiliate_facebook_page);
		update_user_option($user_id, 'affiliate_age', $affiliate_age);
		update_user_option($user_id, 'affiliate_gender', $affiliate_gender);
		update_user_option($user_id, 'affiliate_payout_type', $affiliate_payout_type);
		update_user_option($user_id, 'affiliate_payout', $affiliate_payout);

		if ($affiliate_user == 'true') {
			$affiliate_url = plugins_url() . '/foxypress/foxypress-affiliate.php?aff_id=' . $user_id;
			update_user_option($user_id, 'affiliate_url', $affiliate_url);
		} else {
			update_user_option($user_id, 'affiliate_url', '');
		}
	}
}

function foxypress_FlushRewrites()
{
	global $wp_rewrite;
	$permalink_structure = get_option("permalink_structure");
	$last_permalink_structure = get_option("foxypress_last_permalink_structure");
	if(get_option("foxypress_flush_rewrite_rules") == "1")
	{
		update_option("foxypress_flush_rewrite_rules", "0");
		$wp_rewrite->flush_rules();
	}
	else if($permalink_structure != $last_permalink_structure)
	{
		update_option("foxypress_last_permalink_structure", $permalink_structure);
		$wp_rewrite->flush_rules();
	}
}

function foxypress_admin_css()
{

	echo("<link rel=\"stylesheet\" href=\"" . plugins_url() .  "/foxypress/css/smoothness/jquery-ui-1.8.11.custom.css\">");
	echo("<link rel=\"stylesheet\" href=\"" . plugins_url() . "/foxypress/css/admin.css\">");
}

function foxypress_admin_js()
{
   echo("<script type=\"text/javascript\" src=\"" . plugins_url() . "/foxypress/js/jquery-ui-1.8.11.custom.min.js\"></script>");
}

function foxypress_load_minicart()
{
	register_widget( 'FoxyPress_MiniCart' );
}

function foxypress_ShowDashboardStats()
{
	global $wpdb;
	$statsQuery = "select
					(select sum(foxy_transaction_order_total) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 30 DAY))) MonthTotal
					,(select count(*) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 30 DAY))) MonthOrders
					,(select sum(foxy_transaction_order_total) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 7 DAY))) WeekTotal
					,(select count(*) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 7 DAY))) WeekOrders
					,(select sum(foxy_transaction_order_total) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 1 DAY))) OneDayTotal
					,(select count(*) from " . $wpdb->prefix . "foxypress_transaction where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 1 DAY))) DayOrders
					,(select sum(foxy_transaction_order_total) from " . $wpdb->prefix . "foxypress_transaction) OverallTotal
					,(select count(*) from " . $wpdb->prefix . "foxypress_transaction) OverallOrders
					,(select count(ID) from " . $wpdb->prefix . "posts where post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "' and post_status='publish') TotalProducts
					,(select count(category_id) from " . $wpdb->prefix . "foxypress_inventory_categories) TotalCategories";
	$dtStats = $wpdb->get_row($statsQuery);
	echo("<div style=\"float:left;\">
			<h4>Order History</h4>
			<p>
				1 Day: " . $dtStats->DayOrders . " order" . (($dtStats->DayOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OneDayTotal) . "<br />
				7 Days: " . $dtStats->WeekOrders . " order" . (($dtStats->WeekOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->WeekTotal) . "<br />
				30 Days: " . $dtStats->MonthOrders . " order" . (($dtStats->MonthOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->MonthTotal) . " <br />
				Overall: " . $dtStats->OverallOrders . " order" . (($dtStats->OverallOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OverallTotal) .
			"</p>
			<h4>Product Summary</h4>
			<p>
				" . $dtStats->TotalProducts . " Product" . (($dtStats->TotalProducts == 1) ? "" : "s") . "<br />
				" . $dtStats->TotalCategories . (($dtStats->TotalCategories == 1) ? " Category" : " Categories") .
			"</p>
		  </div>
		  <div style=\"float:right;padding-right:10px;padding-top:10px;\"><img src=\"" . plugins_url() . "/foxypress/img/FoxyPressLogoSmall.png\" alt=\"FoxyPress\" /></div>
		  <div style=\"clear:both;\"></div>");
}

function foxypress_DashboardSetup()
{
	wp_add_dashboard_widget( 'foxypress_dashboard', __( 'FoxyPress Statistics' ), 'foxypress_ShowDashboardStats' );
}

function foxypress_addbuttons() {
   // Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
    	return;

   // Add only in Rich Editor mode
   	if ( get_user_option('rich_editing') == 'true') {
    	add_filter("mce_external_plugins", "foxypress_add_tinymce_plugin");
    	add_filter('mce_buttons_3', 'foxypress_register_plugin_button');
   	}
}

function foxypress_register_plugin_button($buttons) {
	array_push($buttons, "foxypress");
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function foxypress_add_tinymce_plugin($plugin_array) {
	$path = foxypress_GetEditorPluginURL("foxypress");
	$plugin_array['foxypress'] = $path;
	return $plugin_array;
}

// determine absolute url path of editor_plugin.js
function foxypress_GetEditorPluginURL($type) {
    //check if defined WordPress Plugins URL
	if (defined('WP_PLUGINS_URL'))  {
		return WP_PLUGINS_URL."/". $type ."/editor_plugin.js";
	}else{
	//if not assumme it is default location.
		return "../../../wp-content/plugins/". $type ."/editor_plugin.js";
	}
}

function foxypress_Mail($mail_to, $mail_subject, $mail_body, $mail_from = "")
{
	$from = $mail_from;
	if(get_option("foxypress_smtp_host")!='' && get_option("foxypress_email_username")!='' && get_option("foxypress_email_password")!=''){
		if($mail_from == "")
		{
			$from = get_option("foxypress_email_username");
		}
		$to = $mail_to;
		$subject = $mail_subject;

		$host = get_option("foxypress_smtp_host");
		$username = get_option("foxypress_email_username");
		$password = get_option("foxypress_email_password");

		$headers = array ('From' => $from,
			   'To' => $to,
			   'Subject' => $subject,
				'MIME-Version' => '1.0',
				'Content-type' => 'text/html;charset=iso-8859-1');
		//check if they are using a port or not for secure mail
		if(get_option("foxypress_secure_port")!=''){
			$port = get_option("foxypress_secure_port");
			 $smtp = Mail::factory('smtp',
			   array ('host' => $host,
			     'port' => $port,
			     'auth' => true,
			     'username' => $username,
			     'password' => $password));
		}else{
			 $smtp = Mail::factory('smtp',
			   array ('host' => $host,
			     'username' => $username,
			     'password' => $password));
		}
		//send email to customer
		$mail = $smtp->send($to, $headers, $mail_body);
		if (PEAR::isError($mail)) {
		  $emailSent=$mail->getMessage();
		} else {
		  $emailSent="Your status message has been sent.";
		}
	}else{
		if($mail_from == "")
		{
			$from = get_settings("admin_email ");
		}
		//send email to customer
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
		$headers .= 'From: <' . $mail_from . '>' . "\r\n";
		mail($mail_to,$subject,$mail_body,$headers);
	}
}

function foxypress_GetCurrencySymbol()
{
	return substr(foxypress_FormatCurrency(0), 0, 1);
}

function foxypress_FormatCurrency($price)
{
	$res = "";
	if (function_exists('money_format'))
	{
		$res = utf8_encode(money_format("%" . ".2n", (double)$price));
	}
	else
	{
		$currency = (get_option('foxycart_currency_locale') == "en_GB" ? "£" : "$");
		$res = utf8_encode($currency . number_format((double)$price,2,".",","));
	}
	return $res;
}

function foxypress_handle_search_module()
{
	global $wpdb;
	$current_search_term = "";
	if(isset($_POST['foxy_search_submit']))
	{
		$current_search_term = foxypress_FixPostVar('foxy_search_term');
	}
	$search_form = "<div class=\"foxypress_search\">
						<form name=\"foxypress_search_form\" id=\"foxypress_search_form\" method=\"POST\">
							<div>
								<input type=\"text\" id=\"foxy_search_term\" name=\"foxy_search_term\" value=\"" . $current_search_term . "\" />
								<input type=\"submit\" id=\"foxy_search_submit\" name=\"foxy_search_submit\" value=\"Search\" />
							</div>
					   </form>
				   </div>";
	$search_results = "";
	if($current_search_term != "")
	{
		//do search
		$searchSQL = "SELECT i.* FROM " . $wpdb->prefix ."posts as i
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																	and pm_active.meta_key = '_item_active'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																				and pm_start_date.meta_key = '_item_start_date'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																				and pm_end_date.meta_key = '_item_end_date'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																				and pm_code.meta_key = '_code'
						WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
							AND i.post_status = 'publish'
							AND pm_active.meta_value = '1'
							AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							AND (
									pm_code.meta_value = '" . mysql_escape_string($current_search_term) . "'
										or i.post_title = '" . mysql_escape_string($current_search_term) . "'
										or i.post_content = '" . mysql_escape_string($current_search_term) . "'
								)
					  UNION
					  SELECT i.* FROM " . $wpdb->prefix ."posts as i
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																	and pm_active.meta_key = '_item_active'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																				and pm_start_date.meta_key = '_item_start_date'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																				and pm_end_date.meta_key = '_item_end_date'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																				and pm_code.meta_key = '_code'
						WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
							AND i.post_status = 'publish'
							AND pm_active.meta_value = '1'
							AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							AND (
									pm_code.meta_value LIKE '%" . mysql_escape_string($current_search_term) . "%'
										or i.post_title LIKE '%" . mysql_escape_string($current_search_term) . "%'
										or i.post_content LIKE '%" . mysql_escape_string($current_search_term) . "%'
								)";
		$results = $wpdb->get_results($searchSQL);
		if(!empty($results))
		{
			$search_results = "<div class=\"search_results\">";
			foreach($results as $item)
			{
				$search_results .= "<div class=\"search_result_item\">
										<a href=\"" . foxypress_get_product_url($item->ID) . "\">" . $item->post_title . "</a>
								    </div>";
			}
			$search_results .= "</div>";
		}
	}
	return $search_form . $search_results;
}

function foxypress_get_product_url($ID)
{
	global $post, $wpdb;
	$hasPermalinks = (get_option('permalink_structure') != "");
	$myPost = get_post($ID);
	$url = "";
	if($hasPermalinks)
	{
		$url = get_bloginfo("url") . '/products/' . $myPost->post_name;
	}
	else
	{
		$url =  get_bloginfo("url") . "?" . FOXYPRESS_CUSTOM_POST_TYPE . "=" . $myPost->post_name;
	}
	return $url;
}

function foxypress_handle_tracking_module()
{
	$url = plugins_url() . "/foxypress/ajax.php";
	$trackingform = "<div> Enter your order number </div>
		 <div><input type=\"text\" id=\"foxypress_order_number\" name=\"foxypress_order_number\" value=\"\" /></div>
		 <div> Enter your last name </div>
		 <div><input type=\"text\" id=\"foxypress_order_name\" name=\"foxypress_order_name\" value=\"\" /></div>
		 <div><input type=\"button\" id=\"foxypress_tracking_button\"	name=\"foxypress_tracking_button\"	value=\"Find Tracking Number\" onclick=\"foxypress_find_tracking('" . $url . "');\" />
		 </div>
		 <div id=\"foxypress_find_tracking_return\"></div>
	 ";
	return $trackingform;
}

function foxypress_GetMinMaxFormFields($DownloadableID, $min, $max, $qty)
{
	$IsDownloadable =  foxypress_IsDownloadable($DownloadableID);
	$MaxField = "";
	$MinField = "";
	//min
	if($min > 0)
	{
		if($qty != null && $qty < $min)
		{
			$MinField  = "<input type=\"hidden\" name=\"quantity_min\" value=\"" . stripslashes($qty) . "\" />";
		}
		else
		{
			$MinField  = "<input type=\"hidden\" name=\"quantity_min\" value=\"" . stripslashes($min) . "\" />";
		}
	}
	//max
	if($max > 0)
	{
		if($qty != null && $qty < $max)
		{
			$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($qty) . "\" />";
		}
		else
		{
			$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($max) . "\" />";
		}
	}
	else
	{
		$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($qty) . "\" />";
	}

	if($IsDownloadable)
	{
		return "<input type=\"hidden\" name=\"quantity_max\" value=\"1\" />";
	}
	return  $MinField .
			$MaxField;
}

function foxypress_handle_shortcode_listing($CategoryID, $Limit=5, $ItemsPerRow=2, $showMoreDetail, $ShowMainImage=true, $ShowAddToCart=false, $ShowQuantityField=false)
{
	global $wpdb;
	$Output = "";
	$CssSuffix = ($ShowAddToCart) ? "_nodetail" : "";
	$targetpage = foxypress_RemoveQSValue(foxypress_GetCurrentPageURL(), "fp_pn");
	if (strrpos($targetpage, "?") === false) {
		$targetpage .= "?";
	}
	$drRows = $wpdb->get_row("SELECT count(i.ID) as RowCount
								FROM " . $wpdb->prefix . "posts as i
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic on i.ID=ic.inventory_id
																						and ic.category_id = '" .  $CategoryID . "'
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
								");
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? ($pageNumber - 1) * $Limit : 0;
	//get all items within this category. format the result set somehow
	$items = $wpdb->get_results("SELECT i.*
								FROM " . $wpdb->prefix . "posts as i
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic ON i.ID=ic.inventory_id
																								and ic.category_id = '" .  $CategoryID . "'
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
								ORDER BY ic.sort_order, i.ID DESC
								LIMIT $start, $Limit");
	if(!empty($items))
	{
		$counter = 0;
		foreach($items as $item)
		{
			if($counter == 0)
			{
				$Output .= "<div class=\"foxypress_item_row\">";
			}
			$Output .= foxypress_handle_shortcode_item($item->ID, $showMoreDetail, $ShowAddToCart, $ShowMainImage, $ShowQuantityField, $CssSuffix, true);
			$counter++;
			if($counter == $ItemsPerRow)
			{
				$Output .= "	<div class=\"foxypress_item_row_clear\">&nbsp;</div>
							</div>";
				$counter = 0;
			}
		}
		//close out the last div if we haven't
		if($counter != 0)
		{
			$Output .= "	<div class=\"foxypress_item_row_clear\"></div>
						</div>";
		}
		//pagination
		if($drRows->RowCount > $Limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $Limit, $targetpage, 'fp_pn');
			$Output .= "<Br>" . $Pagination;
		}
	}
	return $Output;
}

function foxypress_GetMainInventoryImage($inventory_id)
{
	global $wpdb, $post;
	$featuredImageID = (has_post_thumbnail($inventory_id)) ? get_post_thumbnail_id($inventory_id) : 0;
	if($featuredImageID != 0)
	{
		$featuredSrc = wp_get_attachment_image_src($featuredImageID, "full");
		return $featuredSrc[0];
	}
	else
	{
		$current_images = get_posts(array('numberposts' => 1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $inventory_id, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
		if(!empty($current_images))
		{
			foreach ($current_images as $img)
			{
				$src = wp_get_attachment_image_src($img->ID, "full");
				return $src[0];
			}
		}
	}
	return "";
}

function foxypress_GetFirstInventoryImage($inventory_id)
{
	global $wpdb, $post;
	$current_images = get_posts(array('numberposts' => 1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $inventory_id, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
		if(!empty($current_images))
		{
			foreach ($current_images as $img)
			{
				$src = wp_get_attachment_image_src($img->ID, "full");
				return $src[0];
			}
		}
	return "";
}

function foxypress_GetFeaturedInventoryImage($inventory_id)
{
	global $wpdb, $post;
	$featuredImageID = (has_post_thumbnail($inventory_id)) ? get_post_thumbnail_id($inventory_id) : 0;
	if($featuredImageID != 0)
	{
		$featuredSrc = wp_get_attachment_image_src($featuredImageID, "full");
		return $featuredSrc[0];
	}
	return "";
}

function foxypress_handle_shortcode_item($InventoryID, $showMoreDetail = false, $ShowAddToCart = true, $ShowMainImage = true, $ShowQuantityField = false, $CssSuffix = '', $IsPartOfListing = false)
{
	global $wpdb; global $foxypress_url;
	$Foxypress_Image_Mode = get_option('foxypress_image_mode');
	$OutOfStockMessage = (get_option("foxypress_out_of_stock_message") != "") ? get_option("foxypress_out_of_stock_message") : "Sorry, we are out of stock for this item, please check back later.";
	$UnavailableMessage = (get_option("foxypress_inactive_message") != "") ? get_option("foxypress_inactive_message") : "Sorry, this item is no longer available.";
	$MoreDetail = "";
	$Output = "";
	$ItemImage = "";
	$item = $wpdb->get_row("SELECT i.*
									,c.category_name
									,d.downloadable_id
							FROM " . $wpdb->prefix . "posts as i
							INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . $wpdb->prefix . "foxypress_inventory_to_category
											GROUP BY inventory_id) as ic on i.ID = ic.inventory_id
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
							LEFT JOIN " . $wpdb->prefix . "foxypress_inventory_downloadables as d on i.ID = d.inventory_id
																									and d.status = 1
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																					and pm_start_date.meta_key = '_item_start_date'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																					and pm_end_date.meta_key = '_item_end_date'
							WHERE i.ID = '" . $InventoryID . "'
								AND i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
								AND i.post_status = 'publish'
								AND pm_active.meta_value = '1'
								AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())");
	if(empty($item))
	{
		return $UnavailableMessage;
	}
	//get product information from post meta
	$ItemImage = foxypress_GetMainInventoryImage($item->ID);
	$_code = get_post_meta($item->ID,'_code',TRUE);
	$_name = $item->post_title;
	$_description = $item->post_content;
	$_weight = get_post_meta($item->ID,'_weight',TRUE);
	$_weight2 = get_post_meta($item->ID,'_weight2',TRUE);
	$_quantity = get_post_meta($item->ID,'_quantity',TRUE);
	$_quantity_min = get_post_meta($item->ID,'_quantity_min',TRUE);
	$_quantity_max = get_post_meta($item->ID,'_quantity_max',TRUE);
	$_price = get_post_meta($item->ID,'_price',TRUE);
	$_sale_price = get_post_meta($item->ID,'_saleprice',TRUE);
	$_sale_start = get_post_meta($item->ID,'_salestartdate',TRUE);
	$_sale_end = get_post_meta($item->ID,'_saleenddate',TRUE);
	$_discount_quantity_amount = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
	$_discount_quantity_percentage = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
	$_discount_price_amount = get_post_meta($item->ID,'_discount_price_amount',TRUE);
	$_discount_price_percentage = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
	$_start_date = get_post_meta($item->ID,'_item_start_date',TRUE);
	$_end_date = get_post_meta($item->ID,'_item_end_date',TRUE);
	$_active = get_post_meta($item->ID,'_item_active',TRUE);
	$_sub_frequency = get_post_meta($item->ID,'_sub_frequency',TRUE);
	$_sub_startdate = get_post_meta($item->ID,'_sub_startdate',TRUE);
	$_sub_enddate = get_post_meta($item->ID,'_sub_enddate',TRUE);
	$ActualPrice = foxypress_GetActualPrice($_price, $_sale_price, $_sale_start, $_sale_end);
	$Multiship = (get_option('foxycart_enable_multiship') == "1") ?
				  "<div class=\"shipto_container_wrapper" . $CssSuffix . "\">
						<div class=\"shipto_select\" style=\"display:none\">
							<label>Ship this item to:</label><br />
							(you will be able to input shipping addresses during checkout)<br />
							<select name=\"x:shipto_name_select\">
							</select>
						</div>
						<div class=\"shipto_name\">
							<label>Enter the name of the recipient (or leave it empty to ship it to yourself):</label><br />
							<input type=\"text\" name=\"shipto\" value=\"\" />
						</div>
					</div>" : "";
	$QuantityField = ($ShowQuantityField)
					  ? "<div class=\"foxypress_item_quantity_wrapper" . $CssSuffix . "\">
								Quantity: <input type=\"text\" name=\"quantity\" value=\"1\" class=\"foxypress_item_quantity" . $CssSuffix . "\" />
							</div>"
					  : "";
	$CanAddToCart = foxypress_CanAddToCart($item->ID, $_quantity);
	$ItemOptions = foxypress_BuildOptionList($item->ID, "foxypress_form", $_quantity_max);
	//check to see if we need to link to a detail page
	if($showMoreDetail)
	{
		$MoreDetail = "<div class=\"foxypress_item_readmore" . $CssSuffix . "\"><a href=\"" . foxypress_get_product_url($item->ID) . "\">Read More</a></div>";
	}

	if($ShowAddToCart)
	{
		$FormID = "foxypress_form_" . foxypress_GenerateRandomString(8);
		$ItemImages = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $item->ID, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
		if(!empty($ItemImages) && count($ItemImages) > 1)
		{
			$ItemThumbs = "<ul class=\"foxypress_item_image_thumbs" . $CssSuffix . "\">";
			//check to see if we have a featured image, if we do, use that as the first thumb
			$FeaturedImage = foxypress_GetFeaturedInventoryImage($item->ID);
			if($FeaturedImage != "")
			{
				if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
				{
					$ItemThumbs .= "<li><a href=\"" . $FeaturedImage . "\" rel=\"colorbox\"><img src=\"" . $FeaturedImage . "\" /></a></li>";
				}
				else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
				{
					$ItemThumbs .= "<li><a href=\"" . $FeaturedImage . "\" rel=\"lightbox[foxypress" . $item->ID. "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $FeaturedImage . "\" /></a></li>";
				}
				else
				{
					$ItemThumbs .= "<li><a href=\"javascript:ToggleItemImage('" . $FeaturedImage . "');\" ><img src=\"" . $FeaturedImage . "\" /></a></li>";
				}
			}
			//loop through all the images
			foreach($ItemImages as $ii)
			{
				$temp_src = wp_get_attachment_image_src($ii->ID, "full");
				//make sure were not repeating the featured image
				if($FeaturedImage != $temp_src[0])
				{
					if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
					{
						$ItemThumbs .= "<li><a href=\"" . $temp_src[0] . "\" rel=\"colorbox\"><img src=\"" . $temp_src[0] . "\" /></a></li>";
					}
					else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
					{
						$ItemThumbs .= "<li><a href=\"" . $temp_src[0] . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($ii->post_title) . "\"><img src=\"" . $temp_src[0] . "\" /></a></li>";
					}
					else
					{
						$ItemThumbs .= "<li><a href=\"javascript:ToggleItemImage('" . $temp_src[0] . "');\" ><img src=\"" . $temp_src[0] . "\" /></a></li>";
					}
				}
			}
			$ItemThumbs .= "</ul>";
		}
		if($ShowMainImage)
		{
			$MainImageOutput = "";
			if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
			{
				$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"colorbox\"><img src=\"" . $ItemImage . "\" /></a>";
			}
			else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
			{
				$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $ItemImage . "\" /></a>";
			}
			else
			{
				$MainImageOutput = "<img src=\"" . $ItemImage . "\" id=\"foxypress_main_item_image\" />";
			}
			$ImageOutput = ($ItemImage != "") //if we have an image show it, else show default
							? ($ItemThumbs == "") //if we have no thumbs, make the main image clickable, else just <img>
								? $MainImageOutput
								: "<img src=\"" . $ItemImage . "\" id=\"foxypress_main_item_image\" />"
							: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" />";
		}
		$ImageOutput = "<div class=\"foxypress_item_image" . $CssSuffix . "\">" . $ImageOutput . $ItemThumbs . "</div>";

		//show item
		$Output = "<div class=\"foxy_item_wrapper" . $CssSuffix . "\">
				   		<div class=\"foxypress_item_content_wrapper" . $CssSuffix . "\">
							<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"foxypress_form\">"
								.
									( (!$showQuantityField)
										? "<input type=\"hidden\" name=\"quantity\" value=\"1\" />"
										: ""
									)
								.
								"<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->post_title) . "\" />
								<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($_code) . "\" />
								<input type=\"hidden\" name=\"price\" value=\"" . $ActualPrice . "\" />
								<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
								<input type=\"hidden\" name=\"image\" value=\"" . ( ($ItemImage != "") ? $ItemImage : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE ) . "\" />
								<input type=\"hidden\" name=\"weight\" value=\"" . foxypress_GetActualWeight($_weight, $_weight2) . "\" />
								<input type=\"hidden\" name=\"inventory_id\" value=\"" . $item->ID . "\" />
								<input type=\"hidden\" name=\"h:blog_id\" value=\"" . $wpdb->blogid . "\" />
								<input type=\"hidden\" name=\"h:affiliate_id\" value=\"" . $_SESSION['affiliate_id'] . "\" />"
								 .
									( (get_option('foxypress_include_memberid') == "1")
										? "<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />"
										: ""
									)
								 .
									foxypress_GetMinMaxFormFields($item->downloadable_id, $_quantity_min, $_quantity_max, $_quantity)
								 .
								 	( ($_discount_quantity_amount != "")
										? "<input type=\"hidden\" name=\"discount_quantity_amount\" value=\"" . stripslashes($_discount_quantity_amount) . "\" />"
										: ""
									)
								 .
								 	( ($_discount_quantity_percentage != "")
										? "<input type=\"hidden\" name=\"discount_quantity_percentage\" value=\"" . stripslashes($_discount_quantity_percentage) . "\" />"
										: ""
									)
								 .
								 	( ($_discount_price_amount != "")
										? "<input type=\"hidden\" name=\"discount_price_amount\" value=\"" . stripslashes($_discount_price_amount) . "\" />"
										: ""
									)
								 .
								 	( ($_discount_price_percentage != "")
										? "<input type=\"hidden\" name=\"discount_price_percentage\" value=\"" . stripslashes($_discount_price_percentage) . "\" />"
										: ""
									)
								 .
								 	( ($_sub_frequency != "")
										? "<input type=\"hidden\" name=\"sub_frequency\" value=\"" . stripslashes($_sub_frequency) . "\" />"
										: ""
									)
								 .
								 	( ($_sub_startdate != "")
										? "<input type=\"hidden\" name=\"sub_startdate\" value=\"" . stripslashes($_sub_startdate) . "\" />"
										: ""
									)
								 .
								 	( ($_sub_enddate != "")
										? "<input type=\"hidden\" name=\"sub_enddate\" value=\"" . stripslashes($_sub_enddate) . "\" />"
										: ""
									)
								 .
									foxypress_BuildAttributeForm($InventoryID)
								 .
								 "<div class=\"foxypress_item_name" . $CssSuffix . "\">" . stripslashes($item->post_title) . "</div>
								 <div class=\"foxypress_item_price" . $CssSuffix . "\">"
								 .
									( ($ActualPrice == $_price)
										? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
										: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
									)
								 .
								 "</div>
								 <div class=\"foxypress_item_description" . $CssSuffix . "\">" .  stripslashes($item->post_content) . "</div>"
								 .
									foxypress_BuildAttributeList($InventoryID, $CssSuffix)
								 .
									( ($ItemOptions != "")
										? "<div class=\"foxypress_item_options" . $CssSuffix . "\">" . $ItemOptions . "</div>"
										: ""
									)
								 .
									$MoreDetail
								 .
								 ( ($CanAddToCart)
									?   $QuantityField .
										$Multiship .
										"<div class=\"foxypress_item_submit_wrapper" . $CssSuffix . "\">
											<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit" . $CssSuffix . "\" />
										</div>"
									:  "<div class=\"foxypress_item_submit_wrapper" . $CssSuffix . "\">
											<span>" . $OutOfStockMessage . "</span>
										</div>"
								 )
								 .
							"</form>
						</div>"
						.
						$ImageOutput
						.
					"</div>";
	}
	else
	{
		$ImageSrc = ($ItemImage != "") ? $ItemImage : INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;
		$Output = "<div class=\"foxy_item_wrapper\">
				   		<div class=\"foxypress_item_image\">"
						.
							( ($MoreDetail != "")
								? "<a href=\"" . foxypress_get_product_url($item->ID) . "\"><img src=\"" . $ImageSrc . "\" /></a>"
								: "<img src=\"" . $ImageSrc . "\" />"
							)
						.
						"</div>
						<div class=\"foxypress_item_content_wrapper\">
							<div class=\"foxypress_item_name\">" . stripslashes($item->post_title) . "</div>
							<div class=\"foxypress_item_price\">"
							 .
							 	( ($ActualPrice == $_price)
									? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
									: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
								)
							 .
							"</div>
							<div class=\"foxypress_item_description\">" . foxypress_TruncateHTML(stripslashes($item->post_content), 70) . "</div>"
							.
								foxypress_BuildAttributeList($InventoryID, "")
							.
								$MoreDetail
							.
						"</div>
					</div>";
	}
	return $Output;
}

function foxypress_handle_shortcode_detail($showMainImage, $showQuantityField, $inventory_id = '')
{
	global $wpdb; global $foxypress_url;
	if($inventory_id == '')
	{
		$inventory_id = foxypress_FixGetVar('id');
	}
	$Foxypress_Image_Mode = get_option('foxypress_image_mode');
	$OutOfStockMessage = (get_option("foxypress_out_of_stock_message") != "") ? get_option("foxypress_out_of_stock_message") : "Sorry, we are out of stock for this item, please check back later.";
	$UnavailableMessage = (get_option("foxypress_inactive_message") != "") ? get_option("foxypress_inactive_message") : "Sorry, this item is no longer available.";
	$Output = "";
	$ImageOutput = "";
	$item = $wpdb->get_row("SELECT i.*
									,c.category_name
									,d.downloadable_id
							FROM " . $wpdb->prefix . "posts as i
							INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . $wpdb->prefix . "foxypress_inventory_to_category
											GROUP BY inventory_id) as ic on i.ID = ic.inventory_id
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
							LEFT JOIN " . $wpdb->prefix . "foxypress_inventory_downloadables as d on i.ID = d.inventory_id
																								and d.status = 1
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																					and pm_start_date.meta_key = '_item_start_date'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																					and pm_end_date.meta_key = '_item_end_date'
							WHERE (i.ID = '" . mysql_escape_string($inventory_id) . "')
								AND i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
								AND i.post_status = 'publish'
								AND pm_active.meta_value = '1'
								AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							");
	if(empty($item)){ return $UnavailableMessage; }
	//else set up item

	//get product information from post meta
	$ItemImage = foxypress_GetMainInventoryImage($item->ID);
	$_code = get_post_meta($item->ID,'_code',TRUE);
	$_name = $item->post_title;
	$_description = $item->post_content;
	$_weight = get_post_meta($item->ID,'_weight',TRUE);
	$_weight2 = get_post_meta($item->ID,'_weight2',TRUE);
	$_quantity = get_post_meta($item->ID,'_quantity',TRUE);
	$_quantity_min = get_post_meta($item->ID,'_quantity_min',TRUE);
	$_quantity_max = get_post_meta($item->ID,'_quantity_max',TRUE);
	$_price = get_post_meta($item->ID,'_price',TRUE);
	$_sale_price = get_post_meta($item->ID,'_saleprice',TRUE);
	$_sale_start = get_post_meta($item->ID,'_salestartdate',TRUE);
	$_sale_end = get_post_meta($item->ID,'_saleenddate',TRUE);
	$_discount_quantity_amount = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
	$_discount_quantity_percentage = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
	$_discount_price_amount = get_post_meta($item->ID,'_discount_price_amount',TRUE);
	$_discount_price_percentage = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
	$_start_date = get_post_meta($item->ID,'_item_start_date',TRUE);
	$_end_date = get_post_meta($item->ID,'_item_end_date',TRUE);
	$_active = get_post_meta($item->ID,'_item_active',TRUE);
	$_sub_frequency = get_post_meta($item->ID,'_sub_frequency',TRUE);
	$_sub_startdate = get_post_meta($item->ID,'_sub_startdate',TRUE);
	$_sub_enddate = get_post_meta($item->ID,'_sub_enddate',TRUE);
	$ActualPrice = foxypress_GetActualPrice($_price, $_sale_price, $_sale_start, $_sale_end);
	$Multiship = (get_option('foxycart_enable_multiship') == "1") ?
				  "<div class=\"shipto_container_wrapper_detail\">
						<div class=\"shipto_select\" style=\"display:none\">
							<label>Ship this item to:</label><br />
							(you will be able to input shipping addresses during checkout)<br />
							<select name=\"x:shipto_name_select\">
							</select>
						</div>
						<div class=\"shipto_name\">
							<label>Enter the name of the recipient (or leave it empty to ship it to yourself):</label><br />
							<input type=\"text\" name=\"shipto\" value=\"\" />
						</div>
					</div>" : "";
	$QuantityField = ($showQuantityField)
					  ? "<div class=\"foxypress_item_quantity_wrapper_detail\">
								Quantity: <input type=\"text\" name=\"quantity\" value=\"1\" class=\"foxypress_item_quantity_detail\" />
							</div>"
					  : "";
	$CanAddToCart = foxypress_CanAddToCart($item->ID, $_quantity);
	$ItemOptions = foxypress_BuildOptionList($item->ID, "foxypress_form", $_quantity_max);

	$ItemImages = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $item->ID, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
	if(!empty($ItemImages) && count($ItemImages) > 1)
	{
		$ItemThumbs = "<ul class=\"foxypress_item_image_thumbs_detail\">";
		//check to see if we have a featured image, if we do, use that as the first thumb
		$FeaturedImage = foxypress_GetFeaturedInventoryImage($item->ID);
		if($FeaturedImage != "")
		{
			if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
			{
				$ItemThumbs .= "<li><a href=\"" . $FeaturedImage . "\" rel=\"colorbox\"><img src=\"" . $FeaturedImage . "\" /></a></li>";
			}
			else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
			{
				$ItemThumbs .= "<li><a href=\"" . $FeaturedImage . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $FeaturedImage . "\" /></a></li>";
			}
			else
			{
				$ItemThumbs .= "<li><a href=\"javascript:ToggleItemImage('" . $FeaturedImage . "');\" ><img src=\"" . $FeaturedImage . "\" /></a></li>";
			}
		}
		//loop through all the images
		foreach($ItemImages as $ii)
		{
			$temp_src = wp_get_attachment_image_src($ii->ID, "full");
			//make sure were not repeating the featured image
			if($FeaturedImage != $temp_src[0])
			{
				if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
				{
					$ItemThumbs .= "<li><a href=\"" . $temp_src[0] . "\" rel=\"colorbox\"><img src=\"" . $temp_src[0] . "\" /></a></li>";
				}
				else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
				{
					$ItemThumbs .= "<li><a href=\"" . $temp_src[0] . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($ii->post_title) . "\"><img src=\"" . $temp_src[0] . "\" /></a></li>";
				}
				else
				{
					$ItemThumbs .= "<li><a href=\"javascript:ToggleItemImage('" . $temp_src[0] . "');\" ><img src=\"" . $temp_src[0] . "\" /></a></li>";
				}
			}
		}
		$ItemThumbs .= "</ul>";
	}
	if($showMainImage)
	{
		$MainImageOutput = "";
		if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
		{
			$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"colorbox\"><img src=\"" . $ItemImage . "\" /></a>";
		}
		else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
		{
			$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $ItemImage . "\" /></a>";
		}
		else
		{
			$MainImageOutput = "<img src=\"" . $ItemImage . "\" id=\"foxypress_main_item_image\" />";
		}
		$ImageOutput = ($ItemImage != "") //if we have an image show it, else show default
						? ($ItemThumbs == "") //if we have no thumbs, make the main image clickable, else just <img>
							? $MainImageOutput
							: "<img src=\"" . $ItemImage . "\" id=\"foxypress_main_item_image\"/>"
						: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" />";
	}
	$ImageOutput = "<div class=\"foxypress_item_image_detail\">" . $ImageOutput . $ItemThumbs . "</div>";
	//show item
	$Output = "<div class=\"foxypress_detail\">
				<div class=\"foxy_item_wrapper_detail\">
					<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"foxypress_form\">"
						.
							( (!$showQuantityField)
								? "<input type=\"hidden\" name=\"quantity\" value=\"1\" />"
								: ""
							)
						.
						"<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->post_title) . "\" />
						<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($_code) . "\" />
						<input type=\"hidden\" name=\"price\" value=\"" . $ActualPrice . "\" />
						<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
						<input type=\"hidden\" name=\"image\" value=\"" . ( ($ItemImage != "") ? $ItemImage : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE ) . "\" />
						<input type=\"hidden\" name=\"weight\" value=\"" . foxypress_GetActualWeight($_weight, $_weight2) . "\" />
						<input type=\"hidden\" name=\"inventory_id\" value=\"" . $item->ID . "\" />
						<input type=\"hidden\" name=\"h:blog_id\" value=\"" . $wpdb->blogid . "\" />
						<input type=\"hidden\" name=\"h:affiliate_id\" value=\"" . $_SESSION['affiliate_id'] . "\" />"
						 .
							( (get_option('foxypress_include_memberid') == "1")
								? "<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />"
								: ""
							)
						 .
							foxypress_GetMinMaxFormFields($item->downloadable_id, $_quantity_min, $_quantity_max, $_quantity)
						 .
							( ($_discount_quantity_amount != "")
								? "<input type=\"hidden\" name=\"discount_quantity_amount\" value=\"" . stripslashes($_discount_quantity_amount) . "\" />"
								: ""
							)
						 .
							( ($_discount_quantity_percentage != "")
								? "<input type=\"hidden\" name=\"discount_quantity_percentage\" value=\"" . stripslashes($_discount_quantity_percentage) . "\" />"
								: ""
							)
						 .
							( ($_discount_price_amount != "")
								? "<input type=\"hidden\" name=\"discount_price_amount\" value=\"" . stripslashes($_discount_price_amount) . "\" />"
								: ""
							)
						 .
							( ($_discount_price_percentage != "")
								? "<input type=\"hidden\" name=\"discount_price_percentage\" value=\"" . stripslashes($_discount_price_percentage) . "\" />"
								: ""
							)
						 .
							( ($_sub_frequency != "")
								? "<input type=\"hidden\" name=\"sub_frequency\" value=\"" . stripslashes($_sub_frequency) . "\" />"
								: ""
							)
						 .
							( ($_sub_startdate != "")
								? "<input type=\"hidden\" name=\"sub_startdate\" value=\"" . stripslashes($_sub_startdate) . "\" />"
								: ""
							)
						 .
							( ($_sub_enddate != "")
								? "<input type=\"hidden\" name=\"sub_enddate\" value=\"" . stripslashes($_sub_enddate) . "\" />"
								: ""
							)
						 .
						 	foxypress_BuildAttributeForm($inventory_id)
						 .
						 "<div class=\"foxypress_item_name_detail\">" . stripslashes($item->post_title) . "</div>
						 <div class=\"foxypress_item_price_detail\">"
						 .
						 	( ($ActualPrice == $_price)
								? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
								: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
							)
						 .
						 "</div>
						 <div class=\"foxypress_item_description_detail\">" .  stripslashes($item->post_content) . "</div>"
						 .
						 	foxypress_BuildAttributeList($inventory_id, "_detail")
						 .
						 	( ($ItemOptions != "")
								? "<div class=\"foxypress_item_options_detail\">" . $ItemOptions . "</div>"
								: ""
							)
						 .
						 ( ($CanAddToCart)
						    ?   $QuantityField .
								$Multiship .
								"<div class=\"foxypress_item_submit_wrapper_detail\">
									<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit_detail\" />
								</div>"
							:  "<div class=\"foxypress_item_submit_wrapper_detail\">
							    	<span>" . $OutOfStockMessage . "</span>
							    </div>"
						 )
						 .
				"</form>
			</div>"
			.
			$ImageOutput
			.
		   "<div style=\"clear:both;\"></div>
		   </div>";
	return $Output;
}

function foxypress_IsDownloadable($DownloadableID)
{
	if($DownloadableID != null && $DownloadableID != "" && $DownloadableID != "0")
	{
		return true;
	}
	return false;
}

function foxypress_GetActualWeight($_weight1, $_weight2)
{
	$weight2 = number_format($_weight2 /  16, 3);
	$arr_weight2 = explode('.', $weight2);
	$weight2 = ((strpos($weight2, '.') !== false) ? end($arr_weight2) : $weight2);
	return $_weight1 . "." . $weight2;
}

function foxypress_GetActualPrice($price, $saleprice, $startdate, $enddate)
{
	$ActualPrice = $price;
	if($saleprice != "" && $saleprice > 0)
	{
		$CanUseSalePrice = false;
		//check dates
		if($startdate == null && $enddate == null)
		{
			$CanUseSalePrice = true;
		}
		$Today = strtotime(date("Y-m-d"));
		if(!$CanUseSalePrice && strtotime($startdate) <= $Today && strtotime($enddate) >= $Today)
		{
			$CanUseSalePrice = true;
		}
		if($CanUseSalePrice)
		{
			$ActualPrice = $saleprice;
		}
	}
	return $ActualPrice;
}

function foxypress_CanAddToCart($inventory_id, $quantity)
{
	//check the options available, if any of the option lists have 0 items, then we cannot add to cart
	global $wpdb;
	if($quantity == "0")
	{
		return false;
	}
	//get option groups
	$itemOptionGroups = $wpdb->get_results("SELECT distinct option_group_id
										FROM " . $wpdb->prefix . "foxypress_inventory_options
										WHERE inventory_id = '" . $inventory_id . "'");
	if(!empty($itemOptionGroups))
	{

		foreach($itemOptionGroups as $foxyoptiongroup)
		{
			//get option info
			$itemOptions = $wpdb->get_row(" SELECT (SELECT count(*)
															FROM " . $wpdb->prefix . "foxypress_inventory_options
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
														) AS TotalOptions,
														( SELECT count(*)
															FROM " . $wpdb->prefix . "foxypress_inventory_options
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
															AND (option_active = '0'
																	OR (option_quantity='0' AND option_code != '')
															     )
														) AS InactiveOptions");
			if(!empty($itemOptions))
			{
				if($itemOptions->TotalOptions == $itemOptions->InactiveOptions)
				{
					return false;
				}
			}
		}
	}

	return true;
}

function foxypress_BuildAttributeForm($inventory_id)
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
	 											,a.attribute_value
	 									      FROM " . $wpdb->prefix . "foxypress_inventory_attributes as a
											  WHERE a.inventory_id = '" . $inventory_id . "'
											  order by a.attribute_text");

	$formAttributes = "";
	if(!empty($itemAttributes))
	{
		foreach($itemAttributes as $foxyatt)
		{
			$formAttributes .= "<input type=\"hidden\" name=\"" . stripslashes($foxyatt->attribute_text) . "\" value=\"" . stripslashes($foxyatt->attribute_value) . "\" />";
		}
	}
	return $formAttributes;
}

function foxypress_BuildOptionList($inventory_id, $formid, $defaultMaxQty)
{
	global $wpdb;
	$MasterList = "";
	$HasCartValidation = foxypress_HasCartValidation();
	//get distinct option groups so we loop through those individually to create dropdowns
	$optionGroups = $wpdb->get_results("select distinct option_group_id from " . $wpdb->prefix . "foxypress_inventory_options where inventory_id='" . $inventory_id . "'");
	if(!empty($optionGroups))
	{
		$ProductCode = get_post_meta($inventory_id,'_code',TRUE);
		foreach($optionGroups as $optionGroup)
		{
			//get options
			$soldOutList = array();
			$listItems = "";
			$jsData = "";
			$groupName = "";
			$soldOutItems = "";
			$initialMaxValue = "";
			$initialMaxValueHashedName = "";
			$itemOptions = $wpdb->get_results("SELECT o.*
												,og.option_group_name
											   FROM " . $wpdb->prefix . "foxypress_inventory_options as o
											   INNER JOIN " . $wpdb->prefix . "foxypress_inventory_option_group as og on o.option_group_id = og.option_group_id
											   WHERE o.option_group_id = '" . $optionGroup->option_group_id . "'
											   	AND o.inventory_id='" . $inventory_id . "'
											   ORDER BY option_order");
			if(!empty($itemOptions))
			{
				foreach($itemOptions as $option)
				{
					if($groupName == "")
					{
						$groupName = $option->option_group_name;
					}
					if($option->option_active == "1" && $option->option_quantity != "0")
					{
						$extraattribute = "";
						$extraattributefriendly = "";
						if($option->option_extra_price != "" && $option->option_extra_price != 0)
						{
							$isNegative = ($option->option_extra_price < 0);
							$extraattribute = "p" . ($isNegative ? "" : "+") . number_format($option->option_extra_price, 2);
							$extraattributefriendly = ($isNegative ? " " : " +") . foxypress_FormatCurrency($option->option_extra_price)
							;
						}
						if($option->option_extra_weight != "" && $option->option_extra_weight != 0)
						{
							$isNegative = ($option->option_extra_weight < 0);
							$extraattribute .= ($extraattribute == "") ? "w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2) : "|w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2);
						}
						if($option->option_code != "")
						{
							$extraattribute .= ($extraattribute == "") ? "c:" . $option->option_code : "|c:" . $option->option_code;
						}
						if($extraattribute != "")
						{
							$extraattribute = "{" . $extraattribute . "}";
						}
						$listItems  .= "<option value=\"" . htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "\">" . htmlspecialchars(stripslashes($option->option_text)) . $extraattributefriendly . "</option>";
						if($option->option_code != "")
						{
							if($HasCartValidation)
							{
								if($option->option_quantity != null && $option->option_quantity < $defaultMaxQty)
								{
									$HashedName = FoxyCart_Helper::fc_hash_value($ProductCode, "quantity_max", $option->option_quantity, "name", false, false);
								}
								else
								{
									$HashedName = FoxyCart_Helper::fc_hash_value($ProductCode, "quantity_max", $defaultMaxQty, "name", false, false);
								}
							}
							$tempJsData = htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "~" . $option->option_quantity . "~" . $HashedName;
							$jsData .= ($jsData == "") ? $tempJsData : "," . $tempJsData;
							if($initialMaxValue == "")
							{
								if($defaultMaxQty > 0)
								{
									if($option->option_quantity != null && $option->option_quantity < $defaultMaxQty)
									{
										$initialMaxValue = $option->option_quantity;
										if($HasCartValidation)
										{
											$initialMaxValueHashedName = FoxyCart_Helper::fc_hash_value($ProductCode, "quantity_max", $option->option_quantity, "name", false, false);}
									}
									else
									{
										$initialMaxValue = $defaultMaxQty;
										if($HasCartValidation)
										{
											$initialMaxValueHashedName = FoxyCart_Helper::fc_hash_value($ProductCode, "quantity_max", $defaultMaxQty, "name", false, false);
										}
									}
								}
								else
								{
									if($option->option_quantity != null && $option->option_quantity > 0)
									{
										$initialMaxValue = 	$option->option_quantity;
										if($HasCartValidation)
										{
											$initialMaxValueHashedName = FoxyCart_Helper::fc_hash_value($ProductCode, "quantity_max", $option->option_quantity, "name", false, false);
										}
									}
								}
							}
						}
					}
					else
					{
						$soldOutList[] = $option->option_text;
					}
				}
				if(count($soldOutList) > 0)
				{
					foreach($soldOutList as $soldOutItem)
					{
						$soldOutItems .= ($soldOutItems == "") ? $soldOutItem : ", " . $soldOutItem;
					}
					$soldOutItems = "<div class=\"foxypress_item_otions_soldout\">Sold Out Options: " . $soldOutItems . "</div>";
				}
				$JsToAdd = "";
				if(count($optionGroups) == 1 && $jsData != "")
				{
					$JsToAdd = "onchange=\"foxypress_modify_max('" . $formid . "', '" . $jsData . "', this.value, " . $defaultMaxQty . ", '');\"";
					$SetDefaultJS = "<script type=\"text/javascript\" language=\"javascript\">
										jQuery(document).ready(function() {
											var maxfield" . $formid . " = jQuery(\"#" . $formid . "\").find('input[name^=quantity_max]');
											maxfield" . $formid . ".val(" . $initialMaxValue . ");"
											.
											(	($HasCartValidation)
												? "maxfield" . $formid . ".attr('name', '" . $initialMaxValueHashedName . "');"
												: ""
											)
											.
										"});
									 </script>";
				}
				$MasterList .= "<div class=\"foxypress_item_options\">" .
									 stripslashes($groupName) . ":
									<select name=\"" . stripslashes($groupName) . "\" " . $JsToAdd . ">"
										. $listItems .
									"</select>" .
									$SetDefaultJS .
									$soldOutItems .
							   "</div>";
				unset($soldOutList);
			}
		}
	}
	return $MasterList;
}

function foxypress_BuildAttributeList($inventory_id, $CssSuffix = '')
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
											,a.attribute_value
										  FROM " . $wpdb->prefix . "foxypress_inventory_attributes as a
										  WHERE a.inventory_id = '" . $inventory_id . "'
										  order by a.attribute_text");

	$foxyAttributes = "";
	if(!empty($itemAttributes))
	{
		$foxyAttributes = "<div class=\"foxypress_item_attributes" . $CssSuffix . "\">";
		foreach($itemAttributes as $foxyatt)
		{
			$foxyAttributes .= "<div>" . stripslashes($foxyatt->attribute_text) . ": " . stripslashes($foxyatt->attribute_value) .  "</div>";
		}
		$foxyAttributes .= "</div>";
	}
	return $foxyAttributes;
}

/*
function foxypress_BuildOptionList($inventory_id, $formid, $defaultMaxQty)
{
	global $wpdb;
	$MasterList = "";
	//get distinct option groups so we loop through those individually to create dropdowns
	$optionGroups = $wpdb->get_results("select distinct option_group_id from " . $wpdb->prefix . "foxypress_inventory_options where inventory_id='" . $inventory_id . "'");
	if(!empty($optionGroups))
	{
		$ProductCode = get_post_meta($inventory_id,'_code',TRUE);
		foreach($optionGroups as $optionGroup)
		{
			//get options
			$soldOutList = array();
			$listItems = "";
			$jsData = "";
			$groupName = "";
			$soldOutItems = "";
			$initialMaxValue = "";
			$itemOptions = $wpdb->get_results("SELECT o.*
												,og.option_group_name
											   FROM " . $wpdb->prefix . "foxypress_inventory_options as o
											   INNER JOIN " . $wpdb->prefix . "foxypress_inventory_option_group as og on o.option_group_id = og.option_group_id
											   WHERE o.option_group_id = '" . $optionGroup->option_group_id . "'
											   	AND o.inventory_id='" . $inventory_id . "'
											   ORDER BY option_order");
			if(!empty($itemOptions))
			{
				foreach($itemOptions as $option)
				{
					if($groupName == "")
					{
						$groupName = $option->option_group_name;
					}
					if($option->option_active == "1" && $option->option_quantity != "0")
					{
						$extraattribute = "";
						$extraattributefriendly = "";
						if($option->option_extra_price != "" && $option->option_extra_price != 0)
						{
							$isNegative = ($option->option_extra_price < 0);
							$extraattribute = "p" . ($isNegative ? "" : "+") . number_format($option->option_extra_price, 2);
							$extraattributefriendly = ($isNegative ? " " : " +") . foxypress_FormatCurrency($option->option_extra_price)
							;
						}
						if($option->option_extra_weight != "" && $option->option_extra_weight != 0)
						{
							$isNegative = ($option->option_extra_weight < 0);
							$extraattribute .= ($extraattribute == "") ? "w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2) : "|w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2);
						}
						if($option->option_code != "")
						{
							$extraattribute .= ($extraattribute == "") ? "c:" . $option->option_code : "|c:" . $option->option_code;
						}
						if($extraattribute != "")
						{
							$extraattribute = "{" . $extraattribute . "}";
						}
						$listItems  .= "<option value=\"" . htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "\">" . htmlspecialchars(stripslashes($option->option_text)) . $extraattributefriendly . "</option>";
						if($option->option_code != "")
						{
							$tempJsData = htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "~" . $option->option_quantity;
							$jsData .= ($jsData == "") ? $tempJsData : "," . $tempJsData;
							if($initialMaxValue == "")
							{
								if($defaultMaxQty > 0)
								{
									if($option->option_quantity != null && $option->option_quantity < $defaultMaxQty)
									{
										$initialMaxValue = $option->option_quantity;
									}
									else
									{
										$initialMaxValue = $defaultMaxQty;
									}
								}
								else
								{
									if($option->option_quantity != null && $option->option_quantity > 0)
									{
										$initialMaxValue = 	$option->option_quantity;
									}
								}
							}
						}
					}
					else
					{
						$soldOutList[] = $option->option_text;
					}
				}
				if(count($soldOutList) > 0)
				{
					foreach($soldOutList as $soldOutItem)
					{
						$soldOutItems .= ($soldOutItems == "") ? $soldOutItem : ", " . $soldOutItem;
					}
					$soldOutItems = "<div class=\"foxypress_item_otions_soldout\">Sold Out Options: " . $soldOutItems . "</div>";
				}
				$JsToAdd = "";
				if(count($optionGroups) == 1 && $jsData != "")
				{
					$JsToAdd = "onchange=\"foxypress_modify_max('" . $formid . "', '" . $jsData . "', this.value, " . $defaultMaxQty . ");\"";
					$SetDefaultJS = "<script type=\"text/javascript\" language=\"javascript\">
										jQuery(document).ready(function() {
											var maxfield" . $formid . " = jQuery(\"#" . $formid . "\").find('input[name^=quantity_max]');
											maxfield" . $formid . ".val(" . $initialMaxValue . ");
										});
									 </script>";
				}
				$MasterList .= "<div class=\"foxypress_item_options\">" .
									 stripslashes($groupName) . ":
									<select name=\"" . stripslashes($groupName) . "\" " . $JsToAdd . ">"
										. $listItems .
									"</select>" .
									$SetDefaultJS .
									$soldOutItems .
							   "</div>";
				unset($soldOutList);
			}
		}
	}
	return $MasterList;
}
*/

function foxypress_shortcode( $atts, $content = null) {
	global $wpdb;
	global $foxypress_url;
	$mode = trim($atts['mode']);
	$mode = ($mode == "") ? "single" : $mode;
	$showMainImage = (strtolower(trim($atts['show_main_image'])) == "false") ? false : true;
	$showAddToCart = (trim($atts['addtocart']) == "1") ? true : false;
	$showQuantity = (trim($atts['show_qty']) == "1") ? true : false;
	$showMoreDetail = (trim($atts['showmoredetail']) == "1" || trim($atts['detailurl']) != "") ? true : false;
	$returnHTML = "";

	if(trim($atts['id']) != '' && $mode == 'single')
	{
		$returnHTML =  foxypress_handle_shortcode_item(trim($atts['id']), $showMoreDetail, true, $showMainImage, $showQuantity, "_single", false);
	}
	else if(trim($atts['categoryid']) != '' && $mode == 'list')
	{
		$returnHTML = foxypress_handle_shortcode_listing(trim($atts['categoryid']), trim($atts['items']), trim($atts['cols']), $showMoreDetail, $showMainImage, $showAddToCart, $showQuantity);
	}
	else if($mode == 'detail')
	{
		$returnHTML = foxypress_handle_shortcode_detail($showMainImage, $showQuantity);
	}
	else if($mode == 'tracking')
	{
		return foxypress_handle_tracking_module();
	}
	else if($mode  == 'search')
	{
		return foxypress_handle_search_module();
	}
	//do we need to hash it?
	if(foxypress_HasCartValidation())
	{
		$returnHTML = FoxyCart_Helper::fc_hash_html($returnHTML);
	}
	return $returnHTML;
}

function foxypress_category_item_count($category_name)
{
	global $wpdb;
	$item = $wpdb->get_row("SELECT count(i.ID) as ItemCount
							FROM " . $wpdb->prefix . "posts as i
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic ON i.ID=ic.inventory_id
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																					and pm_start_date.meta_key = '_item_start_date'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																					and pm_end_date.meta_key = '_item_end_date'
							WHERE c.category_name='" . $category_name . "'
								AND i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE. "'
								AND i.post_status = 'publish'
								AND pm_active.meta_value = '1'
								AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							");
	if(!empty($item))
	{
		return $item->ItemCount;
	}
	return "0";
}

function foxypress_FixGetVar($variable, $default = '')
{
	$value = $default;
	if(isset($_GET[$variable]))
	{
		$value = trim($_GET[$variable]);
		if(get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_FixPostVar($variable, $default = '')
{
	$value = $default;
	if(isset($_POST[$variable]))
	{
		$value = trim($_POST[$variable]);
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_DeleteItem($fileloc)
{
	if (file_exists($fileloc))
	{
		unlink($fileloc);
	}
}

function foxypress_ParseFileExtension($filename)
{
	$filename = strtolower($filename);
	$exts = split("[/\\.]", $filename);
	$n = count($exts)-1;
	$exts = $exts[$n];
	return $exts;
}

function foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, $prefix)
{
	$newName = $prefix . foxypress_GenerateRandomString(10) . "_" . $inventory_id . "." . $fileExtension;
	$directory = $targetpath;
	$directory .= ($directory!="") ? "/" : "";
	if(file_exists($directory . $newName))
	{
		return foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, $prefix);
	}
	return $newName;
}

function foxypress_Encrypt($item)
{
	$key = get_option('foxypress_encryption_key');
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $item, MCRYPT_MODE_CBC, md5(md5($key))));
}

function foxypress_Decrypt($item)
{
	$key = get_option('foxypress_encryption_key');
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($item), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
}

function foxypress_curlPostRequest($url, $postData) {
	$ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($postData))
	{
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	}
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
  	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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

function foxypress_GetCurrentPageURL($includeQS = true)
{
	if(defined('SUB_FOLDER_PATH') && defined('SUB_FOLDER_PATH')!=''){
		$pageURL = str_replace(SUB_FOLDER_PATH, "", get_bloginfo('url'));
		if(substr($pageURL, -1) == '/') {
		    $pageURL = substr($pageURL, 0, -1);
		}
	}else{
		$pageURL = get_bloginfo('url');
	}

	if($includeQS)
	{
		$pageURL .= $_SERVER['REQUEST_URI'];
	}
	else
	{
		$pageURL .=  str_replace("?", "", str_replace($_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']));
	}
	return $pageURL;
}

function foxypress_RemoveQSValue($url,$remove) {
    $infos=parse_url($url);
    $str=$infos["query"];
    $op = array();
    $pairs = explode("&", $str);
    foreach ($pairs as $pair) {
       list($k, $v) = array_map("urldecode", explode("=", $pair));
        $op[$k] = $v;
    }
    if(isset($op[$remove])){
        unset($op[$remove]);
    }
    return str_replace($str,http_build_query($op),$url);
}

function foxypress_AddQSValue($url, $key, $value) {
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    if (strpos($url, '?') === false) {
        return ($url . '?' . $key . '=' . $value);
    } else {
        return ($url . '&' . $key . '=' . $value);
    }
}

function foxypress_GetPagination($page, $total_pages, $limit, $targetpage, $qspagename = 'pagenum')
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
		$pagination .= "<div class=\"foxy_item_pagination\">";
		//previous button
		if ($page > 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$prev\"><< previous</a>";
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
					$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
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
						$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage?" . $qspagename . "=$counter\">$counter</a>";
				}
			}
		}
		//next button
		if ($page < $counter - 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$next\">next >></a>";
		else
			$pagination.= "<span class=\"disabled\">next >></span>";
		$pagination.= "</div>\n";
	}
	return $pagination;
}

function foxypress_ReplaceNewLine($string)
{
	return (string)str_replace(array("\r", "\r\n", "\n"), '', $string);
}

function foxypress_TruncateString($str, $length)
{
	$str = foxypress_ReplaceNewLine($str);
	if(strlen($str) > $length)
	{
		return substr($str, 0, $length) . "...";
	}
	return $str;
}

function foxypress_TruncateHTML($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
{
	$text = foxypress_ReplaceNewLine($text);
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
					unset($open_tags[$pos]);
					}
				// if tag is an opening tag
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length>= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// if the words shouldn't be cut in the middle...
	if (!$exact) {
		// ...search the last occurance of a space...
		$spacepos = strrpos($truncate, ' ');
		if (isset($spacepos)) {
			// ...and cut the text in this position
			$truncate = substr($truncate, 0, $spacepos);
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}

function foxypress_GenerateRandomString($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

function foxypress_UploadImage($key, $image_id)
{
	if (!isset($_FILES[$key])) { return "";	}
	$image = $_FILES[$key];
	$name = $image["name"];
	$targetpath = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
	if ($name)
	{
		$imgtypes = array("JPG", "JPEG", "GIF", "PNG", "BMP");
		$ext = strtoupper( substr($name ,strlen($name )-(strlen( $name  ) - (strrpos($name ,".") ? strrpos($name ,".")+1 : 0) ))  ) ;
		if (!in_array($ext, $imgtypes))
		{
			echo "Warning! NOT an image file! File not uploaded.";
			return "";
		}
		//get new file name
		$fileExtension = foxypress_ParseFileExtension($name);
		$name = foxypress_GenerateNewFileName($fileExtension, $image_id, $targetpath, "fp_");
		//make sure it doesn't exist already
		if (foxypress_UploadFile($image, $name, $targetpath, true))
		{
			return $name;
		}
		else
		{
			return false;
		}
	}
}

function foxypress_UploadFile($field, $filename, $savetopath, $overwrite, $name="") {
    global $message;
    if ( !is_array( $field ) ) {
		$field = $_FILES[$field];
    }
    if ( !file_exists( $savetopath ) ) {
		echo "<br>The save-to path doesn't exist.... attempting to create...<br>";
		mkdir(ABSPATH . "/" . str_replace("../", "", $savetopath));
    }
    if ( !file_exists( $savetopath ) ) {
		echo "<br>The save-to directory (" . $savetopath . ") does not exist, and could not be created automatically.<br>";
		return false;
    }
    $saveto = $savetopath . "/" . $filename;
    if ($overwrite!=true) {
		if(file_exists($saveto)) {
			echo "<br>The " . $name . " file " . $saveto . " already exists.<br>";
			return false;
		}
    }
    if ( $field["error"] > 0 ) {
		switch ($field["error"]) {
			case 1:
				$error = "The file is too big. (php.ini)"; // php installation max file size error
				break;
			case 2:
				$error = "The file is too big. (form)"; // form max file size error
				break;
			case 3:
				$error = "Only part of the file was uploaded";
				break;
			case 4:
				$error = "No file was uploaded";
				break;
			case 6:
				$error = "Missing a temporary folder.";
				break;
			case 7:
				$error = "Failed to write file to disk";
				break;
			case 8:
				$error = "File upload stopped by extension";
				break;
			default:
			  	$error = "Unknown error (" . $field["error"] . ")";
			  	break;
		}

		echo $field["error"];
		echo $error;
        return "<br>Error: " . $error . "<br>";
	} else {
		if (move_uploaded_file($field["tmp_name"], $saveto)) {
			return true;
		} else {
			die("Unable to write uploaded file.  Check permissions on upload directory.");
		}
    }
}

function foxypress_recursiveDelete($str){
	if(is_file($str)){
		return @unlink($str);
	}
	elseif(is_dir($str)){
		$scan = glob(rtrim($str,'/').'/*');
		foreach($scan as $index=>$path){
			foxypress_recursiveDelete($path);
		}
		return @rmdir($str);
	}
}

function foxypress_GetPaginationStart()
{
	global $wpdb;
	$PageStart = "1";
	$FoxyCart_Version = get_option('foxycart_storeversion');
	if($FoxyCart_Version == "0.7.0" || $FoxyCart_Version == "0.7.1")
	{
		$PageStart = "0";
	}
	return $PageStart;
}

function foxypress_GetFoxyPressIncludes()
{
	global $wpdb;

	$version = get_option('foxycart_storeversion');
	$includejq = get_option('foxycart_include_jquery');
	$enablemuliship = get_option('foxycart_enable_multiship');
	$scripts = "";
	if($version == "0.7.2")
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
					.
					(
						($includejq)
							? "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js\"></script>"
							: ""
					)
					.
					"<script src=\"http://cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.colorbox.js\" type=\"text/javascript\" charset=\"utf-8\"></script>
					<link rel=\"stylesheet\" href=\"http://cdn.foxycart.com/static/scripts/colorbox/1.3.17/style1_fc/colorbox.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	else if($version=="0.7.1")
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
					.
					(
						($includejq)
							? "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js\"></script>"
							: ""
					)
					.
					"<script src=\"http://cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.complete.3.js\" type=\"text/javascript\" charset=\"utf-8\"></script>
					<link rel=\"stylesheet\" href=\"http://static.foxycart.com/scripts/colorbox/1.3.16/style1_fc/colorbox.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	else
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
					.
					(
						($includejq)
							? "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js\"></script>"
							: ""
					)
					.
					"<script src=\"http://cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.complete.js\" type=\"text/javascript\" charset=\"utf-8\"></script>
					<link rel=\"stylesheet\" href=\"http://static.foxycart.com/scripts/colorbox/1.3.9/style1/colorbox.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	if($enablemuliship == "1")
	{
		$scripts .= "<script src=\"" . plugins_url() . "/foxypress/js/multiship.jquery.js\" type=\"text/javascript\" charset=\"utf-8\"></script>";
	}
	if(get_option('foxypress_image_mode') == FOXYPRESS_USE_LIGHTBOX)
	{
		$scripts .= "<script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/prototype.js\"></script>
					 <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/scriptaculous.js?load=effects,builder\"></script>
					 <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/lightbox.js\"></script>
					 <link rel=\"stylesheet\" href=\"". plugins_url() ."/foxypress/css/lightbox.css\" type=\"text/css\" media=\"screen\" />";
	}
	return $scripts;
}

function foxypress_ImportFoxypressScripts()
{
	if(get_option('foxycart_storeurl')!='')
	{
		echo(foxypress_GetFoxyPressIncludes());
		if(get_option('foxypress_include_default_stylesheet'))
		{
			echo("<link rel=\"stylesheet\" href=\"" .  plugins_url() . "/foxypress/style.css\">");
		}
		?>
		<script type="text/javascript" src="<?php echo( plugins_url() );?>/foxypress/js/jquery.qtip.js"></script>
		<script type="text/javascript">
			function foxypress_find_tracking(baseurl)
			{
				var ordernumber = jQuery('#foxypress_order_number').val();
				var lastname = jQuery('#foxypress_order_name').val();
				if(ordernumber != '' && lastname != '')
				{
					var url = baseurl + '?m=tracking&id=' + ordernumber + '&ln=' + lastname;
					jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false",
							success : function (data) { foxypress_find_tracking_callback(data); }
						}
					);
				}
				else
				{
					alert('Please fill out both the order number and you last name');
				}
			}

			function foxypress_find_tracking_callback(data)
			{
				var res = '';
				if(data.ajax_status == 'ok')
				{
					res =  '<div><div id="foxy_order_details">Order Details</div><div id="foxy_order_details_name">' + data.name + '</div><div id="foxy_order_details_address">' + data.shipping_address + '</div><div id="foxy_order_details_status">Status: ' + data.current_status + '</div><div id="foxy_order_details_tracking">Tracking Number: ' + ((data.tracking_number != '') ? data.tracking_number : 'n/a') + '</div></div>';
				}
				else
				{
					res = 'We could not find that order number in our system, please try again or check back later.';
				}
				jQuery('#foxypress_find_tracking_return').html(res);

			}

			jQuery(document).ready(function() {
				jQuery("a[rel='colorbox']").colorbox();
			});

			function foxypress_modify_max(formid, data, selectedvalue, defaultmax)
			{
				var options = data.split(",");
				var maxfield = jQuery("#" + formid).find('input[name^=quantity_max]');
				maxfield.val(defaultmax);
				for(i = 0; i < options.length; i++)
				{
					var optionData = options[i].split("~");
					var OptionValue = optionData[0];
					var OptionQuantity = optionData[1];
					var OptionHashedName = optionData[2];
					//if we don't have an exact match, we might have a signed form so check for the value and pipes
					if(OptionValue == selectedvalue || selectedvalue.indexOf(OptionValue + "||") == 0)
					{
						if(OptionQuantity != null && OptionQuantity != '')
						{
							if(defaultmax != '' && defaultmax > OptionQuantity)
							{
								maxfield.val(OptionQuantity);
							}
							else if(defaultmax == '' || defaultmax == '0')
							{
								maxfield.val(OptionQuantity);
							}
						}
						if(OptionHashedName != "") //if cart validation is on
						{
							maxfield.attr('name', OptionHashedName);
						}
					}
				}
			}

			/*function foxypress_modify_max(formid, data, selectedvalue, defaultmax)
			{
				var options = data.split(",");
				var maxfield = jQuery("#" + formid).find('input[name^=quantity_max]');
				maxfield.val(defaultmax);
				for(i = 0; i < options.length; i++)
				{
					var optionData = options[i].split("~");
					var OptionValue = optionData[0];
					var OptionQuantity = optionData[1];
					//if we don't have an exact match, we might have a signed form so check for the value and pipes
					if(OptionValue == selectedvalue || selectedvalue.indexOf(OptionValue + "||") == 0)
					{
						if(OptionQuantity != null && OptionQuantity != '')
						{
							if(defaultmax != '' && defaultmax > OptionQuantity)
							{
								maxfield.val(OptionQuantity);
							}
							else if(defaultmax == '' || defaultmax == '0')
							{
								maxfield.val(OptionQuantity);
							}
						}
					}
				}
			}*/

			function ToggleItemImage(newImage)
			{
				jQuery('#foxypress_main_item_image').attr('src', newImage)
			}

		</script>
	<?php
	}
}

function foxypress_HasCartValidation()
{
	global $wpdb;
	return (get_option("foxycart_hmac") == "1");
}

function foxypress_RegisterUser($UserID)
{
	$userdata = get_user_by('id', $UserID);
	//sync with foxycart
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "customer_save";
	$foxyData["customer_email"] = $userdata->user_email;
	$foxyData["customer_password_hash"] = $userdata->user_pass;
	$foxyData["customer_country"] = "US";
	$foxyData["customer_password_hash_type"] = "phpass";
	$foxyData["customer_password_hash_config"] = "8";
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	$foxycart_customer_id = (string)$foxyXMLResponse->customer_id;
	if($foxycart_customer_id)
	{
		add_user_meta($UserID, 'foxycart_customer_id', $foxycart_customer_id, true); //get_user_by('id', '1');
	}
}

function foxypress_UpdateUser($UserID)
{
	$foxycartCustomerID = get_user_meta($user_id, 'foxycart_customer_id', true);
	//sync with foxycart
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "customer_save";
	$foxyData["customer_id"] = $foxycartCustomerID;
	$foxyData["customer_email"] =  $_POST['email'];
	if (isset($_POST['pass1']))
	{
		$userdata = get_user_by('id', $UserID);
		$foxyData["customer_password_hash"] = $userdata->user_pass;
		$foxyData["customer_password_hash_type"] = "phpass";
		$foxyData["customer_password_hash_config"] = "8";
	}
	if (isset($_POST['first_name'])) $foxyData['customer_first_name'] = $_POST['first_name'];
	if (isset($_POST['last_name'])) $foxyData['customer_last_name'] = $_POST['last_name'];
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	$foxycart_customer_id = (string)$foxyXMLResponse->customer_id;
	if($foxycart_customer_id)
	{
		add_user_meta($UserID, 'foxycart_customer_id', $foxycart_customer_id, true);
	}
}

function foxypress_CheckForFoxyCartUser($customer_email)
{
	global $current_user;
	get_currentuserinfo();
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "customer_get";
	$foxyData["customer_email"] = $customer_email;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	if ($foxyXMLResponse->result == "SUCCESS")
	{
		$foxycart_customer_id = (string)$foxyXMLResponse->customer_id;
		if ($foxycart_customer_id)
		{
			add_user_meta($current_user->ID, 'foxycart_customer_id', $foxycart_customer_id, true);
		}
		return $foxycart_customer_id;
	}
	else
	{
		return false;
	}
}

function foxypress_CreateFoxyCartUser($customer_email, $customer_pass, $customer_first_name, $customer_last_name)
{
	global $current_user;
	get_currentuserinfo();
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "customer_save";
	if($customer_first_name != "")
	{
		$foxyData["customer_first_name"] = $customer_first_name;
	}
	if($customer_last_name != "")
	{
		$foxyData["customer_last_name"] = $customer_last_name;
	}
	$foxyData["customer_email"] = $customer_email;
	$foxyData["customer_password_hash"] = $customer_pass;
	$foxyData["customer_country"] = "US";
	$foxyData["customer_password_hash_type"] = "phpass";
	$foxyData["customer_password_hash_config"] = "8";
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	$foxycart_customer_id = (string)$foxyXMLResponse->customer_id;
	if($foxycart_customer_id)
	{
		add_user_meta($current_user->ID, 'foxycart_customer_id', $foxycart_customer_id, true);
	}
	return $foxycart_customer_id;
}

function foxypress_PasswordReset($user, $new_pass)
{
	if(empty($new_pass)) { $new_pass = $_POST['pass1']; }
	$hashed = wp_hash_password($new_pass);
	$foxycartCustomerID = get_user_meta($user->ID, 'foxycart_customer_id', true);
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "customer_save";
	$foxyData["customer_id"] = $foxycartCustomerID;
	$foxyData["customer_password_hash"] = $hashed;
	$foxyData["customer_password_hash_type"] = "phpass";
	$foxyData["customer_password_hash_config"] = "8";
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
}

function foxypress_IsMultiSite()
{
	return (function_exists('is_multisite') && is_multisite());
}

function foxypress_IsMainBlog()
{
	if(foxypress_IsMultiSite())
	{
		return (get_option('foxypress_main_blog') == "1");
	}
	return true;
}

function foxypress_HasMainBlog()
{
	global $wpdb;
	$switched_blog = false;
	$OriginalBlog = $wpdb->blogid;
	if(foxypress_IsMultiSite())
	{
		$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
		foreach ($blogids as $blog_id)
		{
			$switched_blog = true;
			switch_to_blog($blog_id);
			if(get_option('foxypress_main_blog') == "1")
			{
				switch_to_blog($OriginalBlog);
				return true;
			}
		}
	}
	if($switched_blog) { switch_to_blog($OriginalBlog); }
	return false;
}

function foxypress_InstallBlog($new_blog_id)
{
	global $wpdb;
	$switched_blog = false;
	$foxycart_apikey = "";
	$foxypress_encryptionkey = "";
	$OriginalBlog = $wpdb->blogid;
	//get api key already created
	$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
	foreach ($blogids as $blog_id)
	{
		if($foxycart_apikey == "" || $foxypress_encryptionkey == "")
		{
			if ( $blog_id != $wpdb->blogid )
			{
				switch_to_blog($blog_id);
				$switched_blog = true;
			}
			//get api key if we don't have it yet
			if($foxycart_apikey == "")
			{
				$foxycart_apikey = get_option('foxycart_apikey');
			}
			//get encryption key if we don't have it yet
			if($foxypress_encryptionkey == "")
			{
				$foxypress_encryptionkey = get_option('foxypress_encryption_key');
			}
		}
	}
	if ($switched_blog)	{ switch_to_blog($OriginalBlog); }

	//defaultswitched  back to false
	$switched_blog = false;
	//switch to new blog if we aren't in it already
	if ( $new_blog_id != $wpdb->blogid )
	{
		$switched_blog = true;
		switch_to_blog( $new_blog_id );
	}
	foxypress_Install($foxycart_apikey, $foxypress_encryptionkey);
	if ($switched_blog)	{ switch_to_blog($OriginalBlog); }
}

function foxypress_UninstallBlog($blog_id, $drop = false)
{
	global $wpdb;
	$switched_blog = false;
	$OriginalBlog = $wpdb->blogid;
	if ( $blog_id != $wpdb->blogid )
	{
		$switched_blog = true;
		switch_to_blog( $blog_id );
	}
	foxypress_Uninstall();
	if ($switched_blog)	{ switch_to_blog($OriginalBlog); }
}

function foxypress_option_exists($option_name)
{
	global $wpdb;
	if( get_option($option_name) === false)
	{
		return false;
	}
	return true;
}

/***************************************************************************************************/
/***************************************************************************************************/
/************************************ Foxypress Installation ***************************************/
/***************************************************************************************************/
/***************************************************************************************************/

function foxypress_RunUpdates()
{
	global $wpdb;

	if(foxypress_Installation_CanRunUpdates())
	{
		$apikey = get_option('foxycart_apikey');
		$encryptionkey = get_option('foxypress_encryption_key');
		if (foxypress_IsMultiSite())
		{
			$OriginalBlog = $wpdb->blogid;
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			foreach ($blogids as $blog_id)
			{
				switch_to_blog($blog_id);
				foxypress_Install($apikey, $encryptionkey);
			}
			switch_to_blog($OriginalBlog);
		}
		else
		{
			foxypress_Install($apikey, $encryptionkey);
		}
		foxypress_FlushRewrites();
	}
}

function foxypress_Installation_CanRunUpdates()
{
	if(foxypress_option_exists("foxypress_version"))
	{
		$installed_version = get_option("foxypress_version");
		if($installed_version != WP_FOXYPRESS_CURRENT_VERSION)
		{
			return true;
		}
		return false;
	}
	return true;
}

function foxypress_activate()
{
	global $wpdb;
	$today = getdate();
	$apikey = "wmm" . $today['mon'] . $today['mday'] . $today['year'] . $today['seconds'] . foxypress_GenerateRandomString(16);
	$encryptionkey = foxypress_GenerateRandomString(10);
	if (foxypress_IsMultiSite())
	{
		$OriginalBlog = $wpdb->blogid;
		// check if it is a network activation - if so, run the activation function for each blog id
		if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1))
		{
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			foreach ($blogids as $blog_id)
			{
				switch_to_blog($blog_id);
				foxypress_Install($apikey, $encryptionkey);
			}
			switch_to_blog($OriginalBlog);
		}
	}
	else
	{
		foxypress_Install($apikey, $encryptionkey);
	}
}

function foxypress_deactivate()
{
	global $wpdb;
	if (foxypress_IsMultiSite())
	{
		$OriginalBlog = $wpdb->blogid;
		$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
		foreach ($blogids as $blog_id)
		{
			switch_to_blog($blog_id);
			foxypress_Uninstall();
		}
		switch_to_blog($OriginalBlog);		
	}
	else
	{
		foxypress_Uninstall();
	}

	//delete downloadable directory
	foxypress_recursiveDelete(ABSPATH . "wp-content/inventory_downloadables/");
	//delete inventory images directory
	foxypress_recursiveDelete(ABSPATH . "wp-content/inventory_images/");
}

function foxypress_Uninstall()
{
	global $wpdb;
	//delete tables
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_note");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_status");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_options");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_attributes");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_option_group");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_categories");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_to_category");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_downloadables");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_downloadable_transaction");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_downloadable_download");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_affiliate_tracking");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_affiliate_payments");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_email_templates");

	//check option first before we delete
	$keep_products = get_option("foxypress_uninstall_keep_products");
	//delete custom settings
	$keys = array(
        'foxycart_storeurl',
        'foxycart_apikey',
        'foxycart_storeversion',
        'foxycart_include_jquery',
		'foxypress_base_url',
		'foxycart_enable_multiship',
		'foxypress_max_downloads',
		'foxypress_encryption_key',
		'foxycart_use_lightbox',
		'foxypress_image_mode',
		'foxycart_show_dashboard_widget',
		'foxypress_qty_alert',
		'foxycart_datafeeds',
		'foxycart_currency_locale',
		'foxypress_inactive_message',
		'foxypress_out_of_stock_message',
		'foxycart_enable_sso',
		'foxypress_main_blog',
		'foxypress_flush_rewrite_rules',
		'foxypress_detail_slug',
		'foxypress_transaction_sync_date',
		'foxypress_transaction_sync_timestamp',
		'foxypress_version',
		'foxypress_uninstall_keep_products',
		'foxypress_last_permalink_structure',
		'foxypress_skip_settings_wizard',
		'foxypress_packing_slip_header',
		'foxycart_hmac',
		'foxypress_include_default_stylesheet',
		'foxypress_smtp_host',
		'foxypress_secure_port',
		'foxypress_email_username',
		'foxypress_email_password'
		);
	foreach( $keys as $key )
	{
		delete_option( $key );
	}
	//delete user meta data
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='foxycart_customer_id'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='foxypress_foxycart_subscriptions'");
	//delete affiliate user meta data
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='affiliate_user'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='affiliate_url'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='affiliate_percentage'");

	if($keep_products != "1")
	{
		$foxypress_posts = 	get_posts(array('numberposts' => -1, 'post_type' => FOXYPRESS_CUSTOM_POST_TYPE, 'post_status' => null));
		foreach($foxypress_posts as $fp)
		{
			//delete meta
			$wpdb->query("DELETE FROM " . $wpdb->prefix  . "postmeta WHERE post_id='" . $fp->ID . "'");
			//delete attachments
			$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $fp->ID, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
			foreach($attachments as $att)
			{
				wp_delete_attachment($att->ID, true);
			}
			//delete post
			wp_delete_post($fp->ID, true);
		}
	}
}

function foxypress_Install($apikey, $encryptionkey)
{
	global $wpdb;
	$new_install = false;
	$tables = array();
	$tempTables = $wpdb->get_results("show tables like '" . $wpdb->prefix . "foxypress%';");
	if(count($tempTables) == 0)
	{
		$new_install = true;
	}
	else
	{
		foreach ( $tempTables as $table )
		{
			foreach($table as $table_name)
			{
				$tables[] = $table_name;
			}
		}
	}
	if ($new_install)
	{
		foxypress_Installation_CreateInventoryCategoryTable();
		foxypress_Installation_CreateTransactionTable();
		foxypress_Installation_CreateTransactionStatusTable();
		foxypress_Installation_CreateTransactionNoteTable();
		foxypress_Installation_CreateInventoryOptionsTable();
		foxypress_Installation_CreateInventoryOptionGroupsTable();
		foxypress_Installation_CreateInventoryAttributesTable();
		foxypress_Installation_CreateInventoryToCategoryTable();
		foxypress_Installation_CreateInventoryDownloadablesTable();
		foxypress_Installation_CreateDownloadTransactionTable();
		foxypress_Installation_CreateDownloadableDownloadTable();

		foxypress_Installation_CreateAffiliatePaymentsTable();
		foxypress_Installation_CreateAffiliateTrackingTable();
		foxypress_Installation_CreateEmailTemplatesTable();

		foxypress_Installation_CreateSettings($encryptionkey, $apikey);
		foxypress_Installation_CreateInventoryDownloadablesDirectory();
		foxypress_Installation_CreateInventoryImagesDirectory();
		
	}
	else
	{
		//if we are upgrading from 0.3.2 we need to convert our inventory
		if(in_array($wpdb->prefix . "foxypress_config", $tables))
		{
			$dtVersion = $wpdb->get_row("select foxy_current_version from " . $wpdb->prefix . "foxypress_config");
			if(!empty($dtVersion))
			{
				if($dtVersion->foxy_current_version == "0.3.2")
				{
					foxypress_ConvertVersion();
				}
			}
		}

		//create settings
		foxypress_Installation_CreateSettings($encryptionkey, $apikey);

		//create download and images dirs if not already created
		foxypress_Installation_CreateInventoryDownloadablesDirectory();
		foxypress_Installation_CreateInventoryImagesDirectory();


		///////////////////////////////////////////////////////////////////////////
		//check to see if we are missing any tables, if so create them as needed.
		///////////////////////////////////////////////////////////////////////////

		//inventory categories
		if(!in_array($wpdb->prefix . "foxypress_inventory_categories", $tables))
		{
			foxypress_Installation_CreateInventoryCategoryTable();
		}
		//transactions
		if(!in_array($wpdb->prefix . "foxypress_transaction", $tables))
		{
			foxypress_Installation_CreateTransactionTable();
		}
		//transaction statuses
		if(!in_array($wpdb->prefix . "foxypress_transaction_status", $tables))
		{
			foxypress_Installation_CreateTransactionStatusTable();
		}
		//transaction notes
		if(!in_array($wpdb->prefix . "foxypress_transaction_note", $tables))
		{
			foxypress_Installation_CreateTransactionNoteTable();
		}
		//inventory options
		if(!in_array($wpdb->prefix . "foxypress_inventory_options", $tables))
		{
			foxypress_Installation_CreateInventoryOptionsTable();
		}
		//inventory option groups
		if(!in_array($wpdb->prefix . "foxypress_inventory_option_group", $tables))
		{
			foxypress_Installation_CreateInventoryOptionGroupsTable();
		}
		//inventory attributes
		if(!in_array($wpdb->prefix . "foxypress_inventory_attributes", $tables))
		{
			foxypress_Installation_CreateInventoryAttributesTable();
		}
		//inventory to category
		if(!in_array($wpdb->prefix . "foxypress_inventory_to_category", $tables))
		{
			foxypress_Installation_CreateInventoryToCategoryTable();
		}
		//inventory downloadables
		if(!in_array($wpdb->prefix . "foxypress_inventory_downloadables", $tables))
		{
			foxypress_Installation_CreateInventoryDownloadablesTable();
		}
		//download transaction
		if(!in_array($wpdb->prefix . "foxypress_downloadable_transaction", $tables))
		{
			foxypress_Installation_CreateDownloadTransactionTable();
		}
		//downloadable download
		if(!in_array($wpdb->prefix . "foxypress_downloadable_download", $tables))
		{
			foxypress_Installation_CreateDownloadableDownloadTable();
		}
		//affiliate tracking
		if(!in_array($wpdb->prefix . "foxypress_affiliate_tracking", $tables))
		{
			foxypress_Installation_CreateAffiliateTrackingTable();
		}
		//affiliate payments
		if(!in_array($wpdb->prefix . "foxypress_affiliate_payments", $tables))
		{
			foxypress_Installation_CreateAffiliatePaymentsTable();
		}
		//email templates
		if(!in_array($wpdb->prefix . "foxypress_email_templates", $tables))
		{
			foxypress_Installation_CreateEmailTemplatesTable();
		}

		//handle alterations
		foxypress_Installation_HandleTableAlterations();

		//update current version
		foxypress_Installation_UpdateCurrentVersion();
	}
}

function foxypress_Installation_HandleTableAlterations()
{
	global $wpdb;
	//all tables should be created up to this point if they are upgrading
	//we can run all the alters everytime for sake of consistency, since they don't update very often it won't be too big of a performance
	//hit. This way sql will realize its dupe columns and not create as opposed to us manually checking every table for every column needed.
		
	
	///////////////////////////////////////////////////////////////////////////
	//foxypress_inventory_to_category
	///////////////////////////////////////////////////////////////////////////
	
	//add sort order to inventory_to_category
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_to_category ADD sort_order int DEFAULT '99' AFTER category_id";
	$wpdb->query($sql);	
	
	
	///////////////////////////////////////////////////////////////////////////
	//foxypress_transaction
	///////////////////////////////////////////////////////////////////////////
	
	//add is test
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0' AFTER foxy_transaction_shipping_country;";
	$wpdb->query($sql);
	//add date
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_date DATETIME AFTER foxy_transaction_is_test;";
	$wpdb->query($sql);
	//add product total
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_product_total FLOAT(10, 2) AFTER foxy_transaction_date;";
	$wpdb->query($sql);
	//add tax total
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_tax_total FLOAT(10, 2) AFTER foxy_transaction_product_total;";
	$wpdb->query($sql);
	//add shipping total
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_shipping_total FLOAT(10, 2) AFTER foxy_transaction_tax_total;";
	$wpdb->query($sql);
	//add order total
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_order_total FLOAT(10, 2) AFTER foxy_transaction_shipping_total;";
	$wpdb->query($sql);
	//add cc type
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_cc_type varchar(50) AFTER foxy_transaction_order_total;";
	$wpdb->query($sql);
	//add blog id
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_blog_id BIGINT(20) NULL AFTER foxy_transaction_cc_type;";
	$wpdb->query($sql);		
	//add affiliate id		
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_affiliate_id BIGINT(20) NULL AFTER foxy_blog_id;";
	$wpdb->query($sql);
	//add affiliate id		
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_rmanumber VARCHAR(100) NULL AFTER foxy_transaction_trackingnumber;";
	$wpdb->query($sql);
	
	///////////////////////////////////////////////////////////////////////////
	//foxypress_iventory_options
	///////////////////////////////////////////////////////////////////////////
	// add option order
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_options ADD option_order INT DEFAULT '99' AFTER option_active;";
	$wpdb->query($sql);		
	//add option extra weight
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_options ADD option_extra_weight FLOAT(10,2) NOT NULL DEFAULT '0' AFTER option_extra_price";
	$wpdb->query($sql);
	//add option code
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_options ADD option_code VARCHAR(30) NULL AFTER option_extra_weight";
	$wpdb->query($sql);
	//add option quantity
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_options ADD option_quantity INT(11) NULL AFTER option_code";
	$wpdb->query($sql);
	
	
	///////////////////////////////////////////////////////////////////////////
	//foxypress_inventory_categories
	///////////////////////////////////////////////////////////////////////////
	//add category image
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_categories ADD category_image VARCHAR(100) NULL AFTER category_name;";
	$wpdb->query($sql);		
	
	
	///////////////////////////////////////////////////////////////////////////
	//updates
	///////////////////////////////////////////////////////////////////////////
	
	//update blog id
	$sql = "UPDATE " . $wpdb->prefix . "foxypress_transaction SET foxy_blog_id = (select min(blog_id) from " . $wpdb->prefix . "blogs) where foxy_blog_id = '0' or foxy_blog_id is null;";
	$wpdb->query($sql);

	///////////////////////////////////////////////////////////////////////////
	//Upgrading Affiliate Functionality
	///////////////////////////////////////////////////////////////////////////
	$affiliate_percentage = $wpdb->get_results("SHOW COLUMNS FROM " . $wpdb->prefix . "foxypress_affiliate_payments LIKE 'foxy_affiliate_percentage'");
	if (!empty($affiliate_percentage)) {
		//Add affiliate_payout_type
        $affiliate_ids = $wpdb->get_results("SELECT user_id FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_percentage'");
        foreach ($affiliate_ids as $affiliate)
        {
        	update_user_meta($affiliate->user_id, 'affiliate_payout_type', '0');
        }

		//Change affiliate_percentage to affiliate_payout
		$sql = "UPDATE " . $wpdb->prefix . "usermeta SET meta_key = replace(meta_key, 'affiliate_percentage', 'affiliate_payout');";
		$wpdb->query($sql);

		//Alter payments table
		$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_affiliate_payments CHANGE foxy_affiliate_percentage foxy_affiliate_payout int(11) NOT NULL";
		$wpdb->query($sql);

		$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_affiliate_payments ADD foxy_affiliate_payout_type tinyint(1) NOT NULL AFTER foxy_affiliate_payout;";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_payments SET foxy_affiliate_payout_type = '0'";
		$wpdb->query($sql);
	}
}

function foxypress_Installation_CreateInventoryCategoryTable()
{
	global $wpdb;
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_categories (
				category_id INT(11) NOT NULL AUTO_INCREMENT,
				category_name VARCHAR(30) NOT NULL,
				category_image VARCHAR(100) NULL,
				PRIMARY KEY (category_id)
			)";
	$wpdb->query($sql);
	//insert default data
	$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_categories" . " SET category_id=1, category_name='Default'";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateTransactionTable()
{
	global $wpdb;
	//create main transaction table to hold data that gets synched up.
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_transaction (
			foxy_transaction_id INT(11) NOT NULL PRIMARY KEY,
			foxy_transaction_status VARCHAR(30) NOT NULL,
			foxy_transaction_first_name VARCHAR(50) NULL,
			foxy_transaction_last_name VARCHAR(50) NULL,
			foxy_transaction_email VARCHAR(50) NULL,
			foxy_transaction_trackingnumber VARCHAR(100) NULL,
			foxy_transaction_rmanumber VARCHAR(100) NULL,
			foxy_transaction_billing_address1 VARCHAR(50) NULL,
			foxy_transaction_billing_address2 VARCHAR(50) NULL,
			foxy_transaction_billing_city VARCHAR(50) NULL,
			foxy_transaction_billing_state VARCHAR(2) NULL,
			foxy_transaction_billing_zip VARCHAR(10) NULL,
			foxy_transaction_billing_country VARCHAR(50) NULL,
			foxy_transaction_shipping_address1 VARCHAR(50) NULL,
			foxy_transaction_shipping_address2 VARCHAR(50) NULL,
			foxy_transaction_shipping_city VARCHAR(50) NULL,
			foxy_transaction_shipping_state VARCHAR(2) NULL,
			foxy_transaction_shipping_zip VARCHAR(10) NULL,
			foxy_transaction_shipping_country VARCHAR(50) NULL,
			foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0',
			foxy_transaction_date DATETIME,
			foxy_transaction_product_total FLOAT(10, 2),
			foxy_transaction_tax_total FLOAT(10, 2),
			foxy_transaction_shipping_total FLOAT(10, 2),
			foxy_transaction_order_total FLOAT(10, 2),
			foxy_transaction_cc_type varchar(50),
			foxy_blog_id BIGINT(20) NULL,
			foxy_affiliate_id BIGINT(20) NULL
		)";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateTransactionStatusTable()
{
	global $wpdb;
	//create custom status table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_transaction_status (
			foxy_transaction_status INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			foxy_transaction_status_description VARCHAR(50) NULL,
			foxy_transaction_status_email_flag tinyint(1) NOT NULL DEFAULT '0',
			foxy_transaction_status_email_subject TEXT NULL,
			foxy_transaction_status_email_body TEXT NULL,
			foxy_transaction_status_email_tracking tinyint(1) NOT NULL DEFAULT '0'
		)";
	$wpdb->query($sql);
	//insert the default category
	$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_transaction_status (foxy_transaction_status, foxy_transaction_status_description) values ('1', 'Uncategorized')";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateTransactionNoteTable()
{
	global $wpdb;
	//create transaction note table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_transaction_note (
				foxy_transaction_note_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_transaction_id INT(11) NOT NULL,
				foxy_transaction_note TEXT NOT NULL,
				foxy_transaction_entered_by VARCHAR(30),
				foxy_transaction_date_entered DATE
			)";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryOptionsTable()
{
	global $wpdb;
	//create options table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_options (
				option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL ,
				option_group_id INT(11) NOT NULL ,
				option_text VARCHAR(50) NOT NULL ,
				option_value VARCHAR(50) NOT NULL ,
				option_extra_price FLOAT(10,2) NOT NULL DEFAULT '0',
				option_extra_weight FLOAT(10,2) NOT NULL DEFAULT '0',
				option_code VARCHAR(30) NULL,
				option_quantity INT(11) NULL,
				option_active TINYINT NOT NULL DEFAULT '1',
				option_order INT DEFAULT '99'
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryOptionGroupsTable()
{
	global $wpdb;
	//create options group table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_option_group (
				option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				option_group_name VARCHAR(50) NOT NULL
			)";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryAttributesTable()
{
	global $wpdb;
	//create custom inventory attributes
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_attributes (
				attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL ,
				attribute_text VARCHAR(50) NOT NULL ,
				attribute_value VARCHAR(50) NOT NULL
		   ) ";
	$wpdb->query($sql);
}


function foxypress_Installation_CreateInventoryToCategoryTable()
{
	global $wpdb;
	//create inventory to category table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_to_category (
				itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL,
				category_id INT(11) NOT NULL,
				sort_order INT(11) NOT NULL DEFAULT '99'
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryDownloadablesTable()
{
	global $wpdb;
	//create inventory downloadables table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_inventory_downloadables (
				downloadable_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL,
				filename varchar(255) NOT NULL,
				maxdownloads INT(11) NOT NULL DEFAULT '0',
				status INT(11) NOT NULL DEFAULT '1'
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateDownloadTransactionTable()
{
	global $wpdb;
	//create inventory download transation table, this id will be unique per download (the id used for the link)
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_downloadable_transaction (
				download_transaction_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_transaction_id INT(11) NOT NULL,
				downloadable_id INT(11) NOT NULL,
				download_count INT(11) NOT NULL DEFAULT '0'
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateDownloadableDownloadTable()
{
	global $wpdb;
	//create downloadable downloads table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_downloadable_download (
				download_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				download_transaction_id INT(11) NOT NULL,
				download_date DATETIME,
				ip_address varchar(25),
				referrer varchar(255)
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateAffiliateTrackingTable()
{	
	global $wpdb;
	//create affliliate tracking table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_affiliate_tracking (
  				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  				affiliate_id bigint(20) NOT NULL,
				destination_url varchar(255) NOT NULL,
				user_ip varchar(255) NOT NULL,
				user_agent varchar(255) NOT NULL,
				date_visited timestamp NOT NULL default CURRENT_TIMESTAMP
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateAffiliatePaymentsTable()
{	
	global $wpdb;
	//create affliliate payments table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_affiliate_payments (
  				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_affiliate_id bigint(20) NOT NULL,
				foxy_transaction_id int(11) NOT NULL,
				foxy_transaction_order_total float(10,2) NOT NULL,
				foxy_affiliate_payout int(11) NOT NULL,
				foxy_affiliate_payout_type tinyint(1) NOT NULL,
				foxy_affiliate_commission float(10,2) NOT NULL,
				foxy_affiliate_payment_method varchar(50) collate utf8_bin NOT NULL,
				foxy_affiliate_payment_date date NOT NULL,
				date_submitted timestamp NOT NULL default CURRENT_TIMESTAMP
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateEmailTemplatesTable()
{	
	global $wpdb;
	//create email templates table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_email_templates (
  				email_template_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_email_template_name varchar(50) NOT NULL,
				foxy_email_template_subject TEXT NULL,
				foxy_email_template_email_body TEXT NULL,
				foxy_email_template_from varchar(100) NULL
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateSettings($encryption_key, $api_key)
{
	global $wpdb;
	if(!foxypress_option_exists("foxypress_encryption_key"))
	{
		add_option("foxypress_encryption_key", $encryption_key, '', 'yes');
	}
	if(!foxypress_option_exists("foxypress_transaction_sync_date"))
	{
		add_option("foxypress_transaction_sync_date", '1900-01-01', '', 'yes');
	}
	if(!foxypress_option_exists("foxypress_transaction_sync_timestamp"))
	{
		add_option("foxypress_transaction_sync_timestamp", date("Y-m-d H:i:s"), '', 'yes');
	}
	if(!foxypress_option_exists("foxypress_version"))
	{
		add_option("foxypress_version", WP_FOXYPRESS_CURRENT_VERSION);
	}
	if(!foxypress_option_exists("foxycart_apikey"))
	{
		add_option("foxycart_apikey", $api_key);
	}
	if(!foxypress_option_exists("foxypress_uninstall_keep_products"))
	{
		add_option("foxypress_uninstall_keep_products", "1");
	}
}

function foxypress_Installation_CreateInventoryDownloadablesDirectory()
{
	//create downloadables folder
	$downlodablefolder = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR;
	if(!is_dir($downlodablefolder))
	{
		mkdir($downlodablefolder, 0777);
		chmod($downlodablefolder, 0777);
	}
}

function foxypress_Installation_CreateInventoryImagesDirectory()
{
	//create images folder and copy default image
	$inventoryfolder = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
	$defaultImage = WP_PLUGIN_DIR . '/foxypress/img/' . INVENTORY_DEFAULT_IMAGE;
	if(!is_dir($inventoryfolder))
	{
		mkdir($inventoryfolder, 0777);
		chmod($inventoryfolder, 0777);
	}
	if (file_exists($defaultImage))
	{
		copy($defaultImage, ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . INVENTORY_DEFAULT_IMAGE);
	}
}


function foxypress_Installation_UpdateCurrentVersion()
{
	 global $wpdb;
	 update_option("foxypress_version", WP_FOXYPRESS_CURRENT_VERSION);
}

function foxypress_ConvertVersion()
{	//converting from 0.3.2
	global $wpdb;

	//insert inventory items in posts
	$items = $wpdb->get_results("select * from " . $wpdb->prefix  . "foxypress_inventory");
	if(!empty($items))
	{
		foreach($items as $item)
		{
			$old_inventory_id = $item->inventory_id;
			//create new item post
			$my_post = array(
				 'post_title' => stripslashes($item->inventory_name),
				 'post_content' => stripslashes($item->inventory_description),
				 'post_status' => 'publish',
				 'post_author' => 1,
				 'post_type' => FOXYPRESS_CUSTOM_POST_TYPE

			  );
			$new_inventory_id = wp_insert_post( $my_post );
			//save details
			foxypress_save_meta_data($new_inventory_id, '_code', $item->inventory_code);
			foxypress_save_meta_data($new_inventory_id, '_price', $item->inventory_price);
			foxypress_save_meta_data($new_inventory_id, '_saleprice', $item->inventory_sale_price);
			foxypress_save_meta_data($new_inventory_id, '_salestartdate', $item->inventory_sale_start);
			foxypress_save_meta_data($new_inventory_id, '_saleenddate', $item->inventory_sale_end);
			foxypress_save_meta_data($new_inventory_id, '_weight', $item->inventory_weight);
			foxypress_save_meta_data($new_inventory_id, '_quantity', $item->inventory_quantity);
			foxypress_save_meta_data($new_inventory_id, '_quantity_min', $item->inventory_quantity_min);
			foxypress_save_meta_data($new_inventory_id, '_quantity_max', $item->inventory_quantity_max);
			foxypress_save_meta_data($new_inventory_id, '_discount_quantity_amount', $item->inventory_discount_quantity_amount);
			foxypress_save_meta_data($new_inventory_id, '_discount_quantity_percentage', $item->inventory_discount_quantity_percentage);
			foxypress_save_meta_data($new_inventory_id, '_discount_price_amount', $item->inventory_discount_price_amount);
			foxypress_save_meta_data($new_inventory_id, '_discount_price_percentage', $item->inventory_discount_price_percentage);
			foxypress_save_meta_data($new_inventory_id, '_sub_frequency', $item->inventory_sub_frequency);
			foxypress_save_meta_data($new_inventory_id, '_sub_startdate', $item->inventory_sub_startdate);
			foxypress_save_meta_data($new_inventory_id, '_sub_enddate', $item->inventory_sub_enddate);
			foxypress_save_meta_data($new_inventory_id, '_item_start_date', $item->inventory_start_date);
			foxypress_save_meta_data($new_inventory_id, '_item_end_date', $item->inventory_end_date);
			foxypress_save_meta_data($new_inventory_id, '_item_active', $item->	inventory_active);
			//keep track of old inventory id
			foxypress_save_meta_data($new_inventory_id, '_old_inventory_id', $old_inventory_id);

			//update attributes table with new inventoryid
			$wpdb->query("UPDATE " . $wpdb->prefix  . "foxypress_inventory_attributes SET inventory_id = '" . $new_inventory_id . "' WHERE inventory_id = '" . $old_inventory_id . "'");
			//update downloadables table with new inventoryid
			$wpdb->query("UPDATE " . $wpdb->prefix  . "foxypress_inventory_downloadables SET inventory_id = '" . $new_inventory_id . "' WHERE inventory_id = '" . $old_inventory_id . "'");
			//update options table with new inventoryid
			$wpdb->query("UPDATE " . $wpdb->prefix  . "foxypress_inventory_options SET inventory_id = '" . $new_inventory_id . "' WHERE inventory_id = '" . $old_inventory_id . "'");
			//update inventory to category table with new inventoryid
			$wpdb->query("UPDATE " . $wpdb->prefix  . "foxypress_inventory_to_category SET inventory_id = '" . $new_inventory_id . "' WHERE inventory_id = '" . $old_inventory_id . "'");
			//move images to media library
			$images = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_images where inventory_id='" . $old_inventory_id . "'");
			if(!empty($images))
			{
				foreach($images as $img)
				{
					$temp_path = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . $img->inventory_image;
					foxypress_ConvertImage($temp_path, $new_inventory_id, $img->image_order);
				}
			}
		}
	}

	//insert config value into wp_options
	add_option("foxypress_version", WP_FOXYPRESS_CURRENT_VERSION);
	add_option("foxypress_skip_settings_wizard", "1");

	//insert sync values into wp_options
	$synch_values = $wpdb->get_row("select * from " . $wpdb->prefix  . "foxypress_transaction_sync");
	if(!empty($sync_values))
	{
		add_option("foxypress_transaction_sync_date", $synch_values->foxy_transaction_sync_date, '', 'yes');
		add_option("foxypress_transaction_sync_timestamp", $synch_values->foxy_transaction_sync_timestamp, '', 'yes');
	}
	else
	{
		add_option("foxypress_transaction_sync_date", '1900-01-01', '', 'yes');
		add_option("foxypress_transaction_sync_timestamp", date("Y-m-d H:i:s"), '', 'yes');
	}

	//delete tables
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_config");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_sync");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_images");

	//delete physical files
	foxypress_DeleteItem(FOXYPRESS_PATH . '/imagehandler.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/inventory.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/settings.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/setup.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/uninstall.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/view-inventory.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/cart_validation.php');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/foxy.txt');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/css/style.css');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/screenshot-4a.jpg');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/screenshot-7b.jpg');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/screenshot-7c.jpg');
	foxypress_DeleteItem(FOXYPRESS_PATH . '/screenshot-9a.jpg');

	//update default image
	$defaultImage = WP_PLUGIN_DIR . '/foxypress/img/' . INVENTORY_DEFAULT_IMAGE;
	if (file_exists($defaultImage))
	{
		copy($defaultImage, ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . INVENTORY_DEFAULT_IMAGE);
	}
}

function foxypress_ConvertImage($file_to_move, $post_id, $menu_order)
{
	global $wpdb;
	//$file_to_move = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . "fp_test_1234.jpg";
	$uploads = wp_upload_dir();
	$file_name = basename($file_to_move);
	$new_file = $uploads[path] . '/' . $file_name;
  	if(!file_exists($new_file) and !is_dir($new_file) and !is_dir($old_file))
	{
    	copy($file_to_move, $new_file);
  	}
	$new_file_path = $uploads[path] . '/' . $file_name;
	$wp_filetype = wp_check_filetype($new_file_path, null );
	$mime_type = $wp_filetype[type];
	$attachment = array(
	  'post_mime_type' => $wp_filetype['type'],
	  'post_title' => preg_replace('/\.[^.]+$/', '', basename($new_file_path)),
	  'post_name' => preg_replace('/\.[^.]+$/', '', basename($new_file_path)),
	  'post_content' => '',
	  'post_parent' => $post_id,
	  'post_status' => 'inherit',
	  'menu_order' => $menu_order
	);
	$attachment_id = wp_insert_attachment($attachment, $new_file_path, $post_id);
	if($attachment_id != 0)
	{
		require_once(ABSPATH . 'wp-admin/includes/image.php');
  		$attachment_data = wp_generate_attachment_metadata($attachment_id, $new_file_path);
  		wp_update_attachment_metadata($attachment_id, $attachment_data);
	}
	//delete original image
	foxypress_DeleteItem($file_to_move);
}

/***************************************************************************************************/
/***************************************************************************************************/
/************************************ End Foxypress Installation ***********************************/
/***************************************************************************************************/
/***************************************************************************************************/


class FoxyPress_MiniCart extends WP_Widget {

	function FoxyPress_MiniCart() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'min', 'description' => __('A widget that will display the FoxyCart cart as a dropdown or in your website\'s sidebar.', 'example') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'mini-cart-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'mini-cart-widget', __('FoxyPress Mini-Cart', 'example'), $widget_ops, $control_ops );
	}

	//Display widget on frontend
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		$hideonzero = apply_filters('widget_title', $instance['hideonzero'] );
		$dropdowndisplay = apply_filters('widget_title', $instance['dropdowndisplay'] );
		echo $before_widget;
		if ( $title )
		{
			echo $before_title . $title . $after_title;
		}
		if ( $dropdowndisplay == "" )
		{
			if ( $hideonzero == "1" )
			{
				?> <div id="fc_minicart"> <?php
			}
			?>
				<span id="fc_quantity">0</span> items<br />
				<span id="fc_total_price">0.00</span>
				<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?cart=view" class="foxycart">View Cart</a>
			<?php
			if ( $hideonzero == "1" )
			{
				?> </div> <?php
			}
		}
		else
		{
			?>
			<a href="#" class="fc_link"><strong>Your Cart</strong></a>
			<div id="fc_cart">
				<img src="<?php echo(plugins_url()) ?>/foxypress/img/cart.png" alt="your cart"/>
				<h2>Your Cart</h2>
				<div class="fc_clear"></div>
				<table>
					<thead>
					<th>item</th>
					<th>qty</th>
					<th>price</th>
					</thead>
					<tbody id="cart_content">
					</tbody>
				</table>
				<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?checkout" id="fc_checkout_link">Check Out</a>
				<div class="fc_clear"></div>
			</div>
			<script type="text/javascript" charset="utf-8">
				var StoreURL = '<?php echo(get_option('foxycart_storeurl')) ?>';
				var FoxyDomain = StoreURL + ".foxycart.com/";
				var timer = 0;
				// this function hides the cart in a very nice way
				function json_cart_fade_out(){
				     if (timer != 0){
				          clearTimeout(timer);
				          timer = 0;
				     }
				     $("#fc_cart").animate({
						  top: 0 - $("#fc_cart").height(),
						  opacity: 0
				      }, 1000);
				}
				$(document).ready(function(){
					fcc.events.cart.postprocess.add(function(){
						fcc.cart_update.call(fcc);
						jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
							console.info(cart.product_count);
							console.info(cart.total_price);
							console.info(cart.total_discount);
							var total_price = cart.total_price - cart.total_discount;
							fc_FoxyCart = "";
					        for (i=0;i<cart.products.length;i++) {
					                fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
					        }
					        // fc_FoxyCart is a javascript variable that now holds your shopping cart data
					        // if you have some products in your cart, why not display it?
					        if (cart.products.length > 0) {
					                $("#fc_cart #cart_content").html(fc_FoxyCart);
					        } else {
					                $("#fc_cart #cart_content").html("");
					        }
						});
					});
					jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
						console.info(cart.product_count);
						console.info(cart.total_price);
						console.info(cart.total_discount);
						var total_price = cart.total_price - cart.total_discount;
						fc_FoxyCart = "";
				        for (i=0;i<cart.products.length;i++) {
				                fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
				        }
				        // fc_FoxyCart is a javascript variable that now holds your shopping cart data
				        // if you have some products in your cart, why not display it?
				        if (cart.products.length > 0) {
				                $("#fc_cart #cart_content").html(fc_FoxyCart);
				        } else {
				                $("#fc_cart #cart_content").html("");
				        }
					});
				   // shows the cart when the mouse is positioned on an specific link
				   $(".fc_link").mouseover(function(){

					   if (timer!= 0){
					        clearTimeout(timer);
					        timer = 0;
					   }

					   $("#fc_cart").animate({
					             opacity: '.99',
					             top: '0px'
					       }, 1000);
					   timer = setTimeout(function(){
					      json_cart_fade_out();
					   }, 2500);
					   // if the user is looking/using the cart don't hide it
					   $("#fc_cart").hover(function(){
					      clearTimeout(timer);
					      timer = 0;
					   }, function(){
					      timer = setTimeout(function(){
					        json_cart_fade_out();
					      }, 1000);
					   });

				   });

				});

				function fc_BuildFoxyCartRow(fc_name,fc_code,fc_options,fc_quantity,fc_price_each,fc_price) {
				        fc_FoxyCart += "<tr>";
				        fc_FoxyCart += "<td>" + fc_name + "</td>";
				//      fc_FoxyCart += "<td>" + fc_options + "</td>";
				//      fc_FoxyCart += "<td>" + fc_code + "</td>";
				        fc_FoxyCart += "<td class=\"right-align\">" + fc_quantity + "</td>";
				//      fc_FoxyCart += "<td>" + fc_price_each + "</td>";
				        fc_FoxyCart += "<td class=\"right-align\">" + fc_price.toFixed(2) + "</td>";
				        fc_FoxyCart += "</tr>";
				}
			</script>
			<?php
		}
		echo $after_widget;
	}

	//update widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['hideonzero'] = strip_tags( $new_instance['hideonzero'] );
		$instance['dropdowndisplay'] = strip_tags( $new_instance['dropdowndisplay'] );
		return $instance;
	}

	//displays the widget settings
	function form( $instance ) {
		//default settings
		$defaults = array( 'title' => __('Your Cart', 'example'), 'hideonzero' => __('0', 'example'), 'dropdowndisplay' => __('0', 'example'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" type="text" />
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'hideonzero' ); ?>" name="<?php echo $this->get_field_name( 'hideonzero' ); ?>" value="1" <?php echo(($instance['hideonzero'] == "1") ? "checked=\"checked\"" : "") ?>  type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'hideonzero' ); ?>"><?php _e('Hide Cart with 0 Items', 'hybrid'); ?></label>
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>" name="<?php echo $this->get_field_name( 'dropdowndisplay' ); ?>" value="1" <?php echo(($instance['dropdowndisplay'] == "1") ? "checked=\"checked\"" : "") ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>"><?php _e('Drop Down Display', 'hybrid'); ?></label>
		</p>
	<?php
	}
}
?>