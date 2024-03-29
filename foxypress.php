<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.foxy-press.com/
Description: FoxyPress provides a complete shopping cart and inventory management tool for use with FoxyCart's e-commerce solution. Easily manage inventory, view and track orders, generate reports and much more.
Author: WebMovement, LLC
Version: 0.4.4.0
Author URI: http://www.webmovementllc.com/

**************************************************************************

FoxyPress provides a complete shopping cart and inventory management tool for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC

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
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

register_activation_hook( __FILE__ , 'foxypress_activate');
register_uninstall_hook( __FILE__ , 'foxypress_deactivate');
global $foxypress_url;
$foxypress_url = get_option('foxycart_storeurl');

//init
add_action('init', 'foxypress_localization', 1);
include_once('custom-post-type.php');
include_once('foxypress-settings.php');
add_action('admin_menu', 'foxypress_menu');
add_action('admin_print_styles', 'foxypress_admin_css');
add_action('admin_print_footer_scripts', 'foxypress_admin_js');
add_action('admin_init', 'foxypress_RunUpdates');
add_action('init', 'foxypress_FlushRewrites');

// Set up AJAX actions
add_action('wp_ajax_foxypress_upload', 'foxypress_ajax_upload');
add_action('wp_ajax_nopriv_foxypress_upload', 'foxypress_ajax_upload');
add_action('wp_ajax_foxypress_download', 'foxypress_ajax_documenthandler');
add_action('wp_ajax_nopriv_foxypress_download', 'foxypress_ajax_documenthandler');

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
	add_action('admin_print_scripts-foxypress_product_page_affiliate-management', 'affiliate_profile_enqueue');

	//user info page
	if(get_option('foxypress_user_portal') == "1")
	{
		add_filter('rewrite_rules_array','foxy_user_portal_rewrite_rules');
		add_filter('query_vars','foxy_user_portal_vars');
		add_action('wp_loaded','foxy_user_portal_flush_check');

		// Add the scripts to the portal page
		add_action('template_redirect','add_portal_scripts');
		function add_portal_scripts() {
			if (is_page(FOXYPRESS_USER_PORTAL)) {
				add_action('wp_head', 'client_affiliate_profile_enqueue');
			}
		}

	} else {
		add_action('wp_loaded','foxy_user_portal_remove_flush_check');
	}
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
define('FOXYPRESS_USE_EASYIMAGEZOOM', '3');
define('FOXYPRESS_CUSTOM_POST_TYPE', 'foxypress_product');
define('WP_FOXYPRESS_CURRENT_VERSION', "0.4.4.0");
define('FOXYPRESS_PATH', dirname(__FILE__));
define('FOXYPRESS_USER_PORTAL','user');
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

function filter($data) {
    $data = trim(htmlentities(strip_tags($data)));
 
    if (get_magic_quotes_gpc())
        $data = stripslashes($data);
 
    $data = mysql_real_escape_string($data);
 
    return $data;
}

function foxypress_ajax_upload() {
	// Since flash requests don't pass cookies
	if ( ! is_user_logged_in() && isset( $_REQUEST['auth_cookie'] ) ) {
		$user_id = wp_validate_auth_cookie( $_REQUEST['auth_cookie'], 'logged_in' );
		if ( $user_id ){
			wp_set_current_user( $user_id );
		}
	}

	check_ajax_referer( 'foxy-upload', 'security' );

	if ( current_user_can( 'upload_files' ) ) {
		include dirname( __FILE__ ) . '/uploadify/uploadify.php';
	}else{
		die( 'Not permitted.' );
	}
	die();
}

function foxypress_ajax_documenthandler() {
	// Since flash requests don't pass cookies
	if ( ! is_user_logged_in() && isset( $_REQUEST['auth_cookie'] ) ) {
		$user_id = wp_validate_auth_cookie( $_REQUEST['auth_cookie'], 'logged_in' );
		if ( $user_id ) {
			wp_set_current_user( $user_id );
		}
	}

	check_ajax_referer( 'foxy-download', 'security' );

	if ( current_user_can( 'upload_files' ) ) {
		include dirname( __FILE__ ) . '/documenthandler.php';
	}else{
		die( 'Not permitted.' );
	}
	die();
}

function foxypress_localization()
{
//echo(dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	load_plugin_textdomain( 'foxypress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function foxypress_menu()
{
	global $foxypress_url;
	global $current_user;
	if ( !empty ( $foxypress_url  ) )
	{
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Option Groups', 'foxypress'), __('Manage Option Groups', 'foxypress'), 'manage_options', 'inventory-option-groups', 'foxypress_inventory_option_groups_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Categories', 'foxypress'), __('Manage Categories', 'foxypress'), 'manage_options', 'inventory-category', 'foxypress_inventory_category_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Manage Emails', 'foxypress'), __('Manage Emails', 'foxypress'), 'manage_options', 'manage-emails', 'foxypress_manage_emails_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Order Management', 'foxypress'), __('Order Management', 'foxypress'), 'manage_options', 'order-management', 'order_management_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Status Management', 'foxypress'), __('Status Management', 'foxypress'), 'manage_options', 'status-management', 'status_management_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Affiliate Management', 'foxypress'), __('Affiliate Management', 'foxypress'), 'manage_options', 'affiliate-management', 'foxypress_create_affiliate_table');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Reports', 'foxypress'), __('Reports', 'foxypress'), 'manage_options', 'reports', 'foxypress_reports_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Subscriptions', 'foxypress'), __('Subscriptions', 'foxypress'), 'manage_options', 'subscriptions', 'foxypress_subscriptions_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Templates', 'foxypress'), __('Templates', 'foxypress'), 'manage_options', 'templates', 'foxypress_templates_page_load');
		add_submenu_page('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE, __('Import/Export', 'foxypress'), __('Import/Export', 'foxypress'), 'manage_options', 'import-export', 'import_export_page_load');
		$user_id = $current_user->ID;
		$affiliate_user = get_user_option('affiliate_user', $user_id);
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

		$affiliate_user 		   		= get_user_option('affiliate_user', $user->ID);
		$affiliate_avatar_name     		= get_user_option('affiliate_avatar_name', $user->ID);
		$affiliate_avatar_ext      		= get_user_option('affiliate_avatar_ext', $user->ID);
		$affiliate_url  		   		= get_user_option('affiliate_url', $user->ID);
		$affiliate_facebook_page   		= get_user_option('affiliate_facebook_page', $user->ID);
		$affiliate_age 			   		= get_user_option('affiliate_age', $user->ID);
		$affiliate_gender		   		= get_user_option('affiliate_gender', $user->ID);
		$affiliate_payout_type     		= get_user_option('affiliate_payout_type', $user->ID);
		$affiliate_payout    	   		= get_user_option('affiliate_payout', $user->ID);
		$affiliate_referral 			= get_user_option('affiliate_referral', $user->ID);
		$affiliate_referral_payout_type = get_user_option('affiliate_referral_payout_type', $user->ID);
		$affiliate_referral_payout 	 	= get_user_option('affiliate_referral_payout', $user->ID);
		$affiliate_discount        		= get_user_option('affiliate_discount', $user->ID);
		$affiliate_discount_type   		= get_user_option('affiliate_discount_type', $user->ID);
		$affiliate_discount_amount 		= get_user_option('affiliate_discount_amount', $user->ID); ?>

		<h3><?php _e('FoxyPress Affiliate Information', 'foxypress'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="affiliate_user"><?php _e('Enable Affiliate', 'foxypress'); ?></label></th>
					<td><input type="checkbox" <?php if ($affiliate_user == 'true') { ?>checked="yes" <?php } ?>name="affiliate_user" id="affiliate_user" value="true" /> <?php _e('Is this an affiliate user?', 'foxypress'); ?></td>
				</tr>
				<tr>
					<th><label for="affiliate_avatar"><?php _e('Avatar', 'foxypress'); ?></label></th>
					<td>
						<div id="avatar"><?php if ($affiliate_avatar_name) { ?><img src="<?php echo content_url(); ?>/affiliate_images/<?php echo $affiliate_avatar_name; ?>-large<?php echo $affiliate_avatar_ext; ?>" width="96" height="96" alt="" /><?php } ?></div>
						<input type="file" name="avatar_upload" id="avatar_upload" value="">
						<input type="hidden" name="affiliate_avatar_name" id="affiliate_avatar_name" value="<?php echo $affiliate_avatar_name; ?>">
						<input type="hidden" name="affiliate_avatar_ext" id="affiliate_avatar_ext" value="<?php echo $affiliate_avatar_ext; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_facebook_page"><?php _e('Affiliate Facebook Page', 'foxypress'); ?></label></th>
					<td>
						<input class="regular-text" type="text" name="affiliate_facebook_page" id="affiliate_facebook_page" value="<?php echo $affiliate_facebook_page; ?>">
						<span class="description"><?php _e('Affiliate\'s Facebook Page.', 'foxypress'); ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_age"><?php _e('Affiliate Age', 'foxypress'); ?></label></th>
					<td>
						<input type="text" name="affiliate_age" id="affiliate_age" value="<?php echo $affiliate_age; ?>">
						<span class="description"><?php _e('Affiliate\'s age.', 'foxypress'); ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_gender"><?php _e('Affiliate Gender', 'foxypress'); ?></label></th>
					<td>
						<input type="text" name="affiliate_gender" id="affiliate_gender" value="<?php echo $affiliate_gender; ?>">
						<span class="description"><?php _e('Affiliate\'s gender.', 'foxypress'); ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_payout_type"><?php _e('Affiliate Payout Type', 'foxypress'); ?></label></th>
					<td>
						<input type="radio" <?php if ($affiliate_payout_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="percentage">
						<span class="description"><?php _e('Percentage of each order.', 'foxypress'); ?></span>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="radio" <?php if ($affiliate_payout_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="dollars">
						<span class="description"><?php _e('Dollar amount of each order.', 'foxypress'); ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_payout"><?php _e('Affiliate Payout', 'foxypress'); ?></label></th>
					<td>
						<input type="text" name="affiliate_payout" id="affiliate_payout" value="<?php echo $affiliate_payout; ?>">
						<span class="description"><?php _e('How much will this affiliate earn per sale?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_referral"><?php _e('Enable Affiliate Referrals', 'foxypress'); ?></label></th>
					<td><input type="checkbox" <?php if ($affiliate_referral == 'true') { ?>checked="yes" <?php } ?>name="affiliate_referral" id="affiliate_referral" value="true" /> <?php _e('Does this user\'s link allow for affiliate referrals?', 'foxypress'); ?></td>
				</tr>
				<tr>
					<th><label for="affiliate_referral_payout_type"><?php _e('Affiliate Referral Payout Type', 'foxypress'); ?></label></th>
					<td>
						<input type="radio" <?php if ($affiliate_referral_payout_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="percentage">
						<span class="description"><?php _e('Percentage of each order', 'foxypress'); ?>.</span>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="radio" <?php if ($affiliate_referral_payout_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="dollars">
						<span class="description"><?php _e('Dollar amount of each order', 'foxypress'); ?>.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_referral_payout"><?php _e('Affiliate Referral Payout', 'foxypress'); ?></label></th>
					<td>
						<input type="text" name="affiliate_referral_payout" id="affiliate_referral_payout" value="<?php echo $affiliate_referral_payout; ?>">
						<span class="description"><?php _e('How much will this affiliate earn per sale of their referrals?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_discount"><?php _e('Enable Affiliate Discount', 'foxypress'); ?></label></th>
					<td><input type="checkbox" <?php if ($affiliate_discount == 'true') { ?>checked="yes" <?php } ?>name="affiliate_discount" id="affiliate_discount" value="true" /> <?php _e('Does this user\'s link allow for an additional discount?', 'foxypress'); ?></td>
				</tr>
				<tr>
					<th><label for="affiliate_payout_type"><?php _e('Affiliate Discount Type', 'foxypress'); ?></label></th>
					<td>
						<input type="radio" <?php if ($affiliate_discount_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="percentage">
						<span class="description"><?php _e('Percentage off of each order', 'foxypress'); ?>.</span>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="radio" <?php if ($affiliate_discount_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="dollars">
						<span class="description"><?php _e('Dollar amount off of each order', 'foxypress'); ?>.</span>
					</td>
				</tr>
				<tr>
					<th><label for="affiliate_discount_amount"><?php _e('Affiliate Discount Amount', 'foxypress'); ?></label></th>
					<td>
						<input type="text" name="affiliate_discount_amount" id="affiliate_discount_amount" value="<?php echo $affiliate_discount_amount; ?>">
						<span class="description"><?php _e('How much of a discount will user\'s receive?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span>
					</td>
				</tr>
				<tr>
					<th><label><?php _e('Affiliate URL', 'foxypress'); ?></label></th>
					<td><?php echo $affiliate_url; ?></td>
				</tr>
			</table>
	<?php }
}

function affiliate_profile_enqueue() {
	$ajax_nonce = wp_create_nonce("foxy-upload");
?>
	<link href="<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript" language="javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" language="javascript" src="<?php echo plugins_url(); ?>/foxypress/uploadify/jquery.uploadify.min.js"></script>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			$("#avatar_upload").uploadify({
				'swf'			: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.swf',
				'uploader'		: ajaxurl,
				'cancelImage'	: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify-cancel.png',
				'createFolder'  : true,
				'checkExisting' : false,
				'fileSizeLimit' : 1*1024, // 1MB
				'fileTypeDesc'  : 'Only images with extensions: *.jpg, *.jpeg, *.png, *.gif are allowed',
				'fileTypeExts'  : '*.gif;*.jpg;*.jpeg;*.png',
				'method'        : 'post',
				'queueSizeLimit': 1,
				'postData'      : {'action' : 'foxypress_upload', 'auth_cookie' : '<?php echo $_COOKIE[LOGGED_IN_COOKIE]; ?>', 'security' : '<?php echo($ajax_nonce); ?>'},
				'progressData'  : 'all',
				'multi'			: false,
				'auto'			: true,
				'buttonText'	: 'UPLOAD IMAGE',
				'onUploadStart' : function(file) {
					jQuery('div#avatar').html('<span class="avatar-loader"><img src="<?php echo plugins_url(); ?>/foxypress/img/ajax-loader-cir.gif" width="32" height="32" alt="" /></span>');
				},
				'onUploadSuccess': function (file,data,response) {
					var fileinfo = jQuery.parseJSON(data);
					jQuery('input#affiliate_avatar_name').val(fileinfo.raw_file_name);
					jQuery('input#affiliate_avatar_ext').val(fileinfo.file_ext);
					jQuery('div#avatar').html('<img src="<?php echo content_url(); ?>/affiliate_images/' + fileinfo.raw_file_name + '-large' + fileinfo.file_ext + '" width="96" height="96" alt="" />');
				}
			});
		});
	</script>
<?php }

function client_affiliate_profile_enqueue() {
	$ajax_nonce = wp_create_nonce("foxy-upload");
?>
	<link href="<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript" language="javascript" src="<?php echo plugins_url(); ?>/foxypress/uploadify/jquery.uploadify.min.js"></script>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			$("#avatar_upload").uploadify({
				'swf'			: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify.swf',
				'uploader'		: ajaxurl,
				'cancelImage'	: '<?php echo plugins_url(); ?>/foxypress/uploadify/uploadify-cancel.png',
				'createFolder'  : true,
				'checkExisting' : false,
				'fileSizeLimit' : 1*1024, // 1MB
				'fileTypeDesc'  : 'Only images with extensions: *.jpg, *.jpeg, *.png, *.gif are allowed',
				'fileTypeExts'  : '*.gif;*.jpg;*.jpeg;*.png',
				'method'        : 'post',
				'queueSizeLimit': 1,
				'postData'      : {'action' : 'foxypress_upload', 'auth_cookie' : '<?php echo $_COOKIE[LOGGED_IN_COOKIE]; ?>', 'security' : '<?php echo($ajax_nonce); ?>'},
				'progressData'  : 'all',
				'multi'			: false,
				'auto'			: true,
				'buttonText'	: 'UPLOAD IMAGE',
				'onUploadStart' : function(file) {
					jQuery('div#avatar').html('<span class="avatar-loader"><img src="<?php echo plugins_url(); ?>/foxypress/img/ajax-loader-cir.gif" width="32" height="32" alt="" /></span>');
				},
				'onUploadSuccess': function (file,data,response) {
					var fileinfo = jQuery.parseJSON(data);
					jQuery('input#affiliate_avatar_name').val(fileinfo.raw_file_name);
					jQuery('input#affiliate_avatar_ext').val(fileinfo.file_ext);
					jQuery('div#avatar').html('<img src="<?php echo content_url(); ?>/affiliate_images/' + fileinfo.raw_file_name + '-large' + fileinfo.file_ext + '" width="96" height="96" alt="" />');
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
			$affiliate_check = get_user_option('affiliate_user', $user_id);
			if ($affiliate_check == 'pending') {
				$affiliate_user = $affiliate_check;
			}
		}

		if(isset($_POST['affiliate_avatar_name'])){$affiliate_avatar_name = $_POST['affiliate_avatar_name'];}else{$affiliate_avatar_name="";};
		if(isset($_POST['affiliate_avatar_ext'])){$affiliate_avatar_ext = $_POST['affiliate_avatar_ext'];}else{$affiliate_avatar_ext="";};
		if(isset($_POST['affiliate_facebook_page'])){$affiliate_facebook_page = $_POST['affiliate_facebook_page'];}else{$affiliate_facebook_page="";};
		if(isset($_POST['affiliate_age'])){$affiliate_age = $_POST['affiliate_age'];}else{$affiliate_age="";};
		if(isset($_POST['affiliate_gender'])){$affiliate_gender = $_POST['affiliate_gender'];}else{$affiliate_gender="";};
		if(isset($_POST['affiliate_payout_type'])){$affiliate_payout_type = $_POST['affiliate_payout_type'];}else{$affiliate_payout_type="";};
		if(isset($_POST['affiliate_payout'])){$affiliate_payout = $_POST['affiliate_payout'];}else{$affiliate_payout="";};
		if(isset($_POST['affiliate_referral'])){$affiliate_referral = $_POST['affiliate_referral'];}else{$affiliate_referral="";};
		if(isset($_POST['affiliate_referral_payout_type'])){$affiliate_referral_payout_type = $_POST['affiliate_referral_payout_type'];}else{$affiliate_referral_payout_type="";};
		if(isset($_POST['affiliate_referral_payout'])){$affiliate_referral_payout = $_POST['affiliate_referral_payout'];}else{$affiliate_referral_payout="";};
		if(isset($_POST['affiliate_referral_payout'])){$affiliate_referral_payout = $_POST['affiliate_referral_payout'];}else{$affiliate_referral_payout="";};
		if(isset($_POST['affiliate_discount'])){$affiliate_discount = $_POST['affiliate_discount'];}else{$affiliate_discount="";};
		if(isset($_POST['affiliate_discount_type'])){$affiliate_discount_type = $_POST['affiliate_discount_type'];}else{$affiliate_discount_type="";};
		if(isset($_POST['affiliate_discount_amount'])){$affiliate_discount_amount = $_POST['affiliate_discount_amount'];}else{$affiliate_discount_amount="";};
		if(isset($_POST['affiliate_discount_amount'])){$affiliate_discount_amount = $_POST['affiliate_discount_amount'];}else{$affiliate_discount_amount="";};

		if ($affiliate_payout_type == 'percentage') {
			$affiliate_payout_type = 1;
		} else if ($affiliate_payout_type == 'dollars') {
			$affiliate_payout_type = 2;
		}

		if ($affiliate_referral_payout_type == 'percentage') {
			$affiliate_referral_payout_type = 1;
		} else if ($affiliate_referral_payout_type == 'dollars') {
			$affiliate_referral_payout_type = 2;
		}

		if ($affiliate_discount_type == 'percentage') {
			$affiliate_discount_type = 1;
		} else if ($affiliate_discount_type == 'dollars') {
			$affiliate_discount_type = 2;
		}

		update_user_option($user_id, 'affiliate_user', $affiliate_user);
		update_user_option($user_id, 'affiliate_avatar_name', $affiliate_avatar_name);
		update_user_option($user_id, 'affiliate_avatar_ext', $affiliate_avatar_ext);
		update_user_option($user_id, 'affiliate_facebook_page', $affiliate_facebook_page);
		update_user_option($user_id, 'affiliate_age', $affiliate_age);
		update_user_option($user_id, 'affiliate_gender', $affiliate_gender);
		update_user_option($user_id, 'affiliate_payout_type', $affiliate_payout_type);
		update_user_option($user_id, 'affiliate_payout', $affiliate_payout);
		update_user_option($user_id, 'affiliate_referral', $affiliate_referral);
		update_user_option($user_id, 'affiliate_referral_payout_type', $affiliate_referral_payout_type);
		update_user_option($user_id, 'affiliate_referral_payout', $affiliate_referral_payout);
		update_user_option($user_id, 'affiliate_discount', $affiliate_discount);
		update_user_option($user_id, 'affiliate_discount_type', $affiliate_discount_type);
		update_user_option($user_id, 'affiliate_discount_amount', $affiliate_discount_amount);

		if ($affiliate_user == 'true') {
			$affiliate_url = plugins_url() . '/foxypress/foxypress-affiliate.php?aff_id=' . $user_id;
			update_user_option($user_id, 'affiliate_url', $affiliate_url);
		} else {
			update_user_option($user_id, 'affiliate_url', '');
		}
	}
}

function foxy_user_portal_flush_check()
{
	$rules = get_option('rewrite_rules');

	if (!isset($rules['(' . FOXYPRESS_USER_PORTAL . ')/(.*?)/(.*?)$'])) {
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}

function foxy_user_portal_remove_flush_check()
{
	$rules = get_option('rewrite_rules');

	if (isset($rules['(' . FOXYPRESS_USER_PORTAL . ')/(.*?)/(.*?)$'])) {
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}

function foxy_user_portal_rewrite_rules($rules)
{
	$newrules = array();
	$newrules['(' . FOXYPRESS_USER_PORTAL . ')/(.*?)/(.*?)$'] = 'index.php?pagename=$matches[1]&user_name=$matches[2]&mode=$matches[3]';
	return $newrules + $rules;
}

function foxy_user_portal_vars( $vars )
{
    array_push($vars, 'user_name');
    array_push($vars, 'mode');
    return $vars;
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
	echo("<link rel=\"stylesheet\" href=\"" . plugins_url() .  "/foxypress/css/smoothness/jquery-ui-1.8.17.custom.css\">");
	echo("<link rel=\"stylesheet\" href=\"" . plugins_url() . "/foxypress/css/admin.css?ver=20131025\">");
}

function foxypress_admin_js()
{
	$pluginfolder = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__));    
//	wp_enqueue_script('jquery-ui-core');
	//wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('jquery-ui-datepicker', $pluginfolder . '/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
	if (jQuery.ui) {
//		alert("got it");
	}
	//echo("<script type=\"text/javascript\" src=\"" . plugins_url() . "/foxypress/js/jquery-ui-1.8.17.custom.min.js\"></script>");
	//echo("<script type=\"text/javascript\" src=\"" . plugins_url() . "/foxypress/js/jquery-ui-timepicker-addon.js\"></script>");
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
			<h4>" . __('Order History', 'foxypress') . "</h4>
			<p>
				" . __('1 Day', 'foxypress') . ": " . $dtStats->DayOrders . " order" . (($dtStats->DayOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OneDayTotal) . "<br />
				" . __('7 Days', 'foxypress') . ": " . $dtStats->WeekOrders . " order" . (($dtStats->WeekOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->WeekTotal) . "<br />
				" . __('30 Days', 'foxypress') . ": " . $dtStats->MonthOrders . " order" . (($dtStats->MonthOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->MonthTotal) . " <br />
				" . __('Overall', 'foxypress') . ": " . $dtStats->OverallOrders . " order" . (($dtStats->OverallOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OverallTotal) .
			"</p>
			<h4>" . __('Product Summary', 'foxypress') . "</h4>
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
    $url = plugins_url();
	if (defined('WP_PLUGINS_URL'))  {
		return WP_PLUGINS_URL."/". $type ."/editor_plugin.js";
	}else{
		//if not assumme it is default location.
		return $url . "/". $type ."/editor_plugin.js";
	}
}

function foxypress_Mail($mail_to, $mail_subject, $mail_body, $mail_from = "", $plaintext = false)
{
	$from = $mail_from;
	if(get_option("foxypress_smtp_host")!='' && get_option("foxypress_email_username")!='' && get_option("foxypress_email_password")!=''){
		
		if($from == "")
		{
			$from = get_option("foxypress_email_username");
		}
		$to = $mail_to;
		$host = get_option("foxypress_smtp_host");
		$username = get_option("foxypress_email_username");
		$password = get_option("foxypress_email_password");

		$headers = array ('From' => $from,
			   'To' => $to,
			   'Subject' => $mail_subject,
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
		if($from == "")
		{
			$from = get_option('admin_email');
		}
		
		// If plaintext is false (default), submit email as HTML
		if ($plaintext === false) {
			$headers[] = "Content-type:text/html;charset=iso-8859-1";
		}
		$headers[] = 'From: ' . get_option('blogname') . ' <' . $from . '>';
		
		$mail_result = wp_mail($mail_to,$mail_subject,$mail_body,$headers);
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
								<input type=\"submit\" id=\"foxy_search_submit\" name=\"foxy_search_submit\" value=\"" . __('Search', 'foxypress') . "\" />
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

/**
 * Shortcode function to display a list of items. Does not
 * currently support pagination
 * 
 * @since 0.4.3.3
 * 
 * @param array $items An integer array of product IDs to output in list
 * @param int $ItemsPerRow Number of items to display on each row
 * @param bool $showMoreDetail 
 * @param bool $ShowMainImage
 * @param bool $ShowAddToCart
 * @param bool $ShowQuantityField
 */
function foxypress_handle_shortcode_list($items, $ItemsPerRow=2, $showMoreDetail, $ShowMainImage=true, $ShowAddToCart=false, $ShowQuantityField=false)
{
	$output = "";
	
	if (!empty($items)) {
		
		for ($i = 0; $i < count($items); $i++) {
			// Determine if this is the start of a row
			if ($i % $ItemsPerRow == 0) {
				$output .= "<div class=\"foxypress_item_row\">";
			}
			
			// Print item
			$output .= foxypress_handle_shortcode_item($items[$i], $showMoreDetail, $ShowAddToCart, $ShowMainImage, $ShowQuantityField, $CssSuffix, true);
			
			// Determine if this is the end of a row or the last item
			if (($i + 1) % $ItemsPerRow == 0 || $i + 1 == count($items)) {
				$output .= "<div class=\"foxypress_item_row_clear\">&nbsp;</div></div>";
			}
		}
	}
	
	return $output;
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
	
	// Get list of this category and child categories
	$cat_list = $CategoryID;
	$child_cats = foxypress_get_product_categories($CategoryID);
	foreach ($child_cats as $child_cat)
	{
		$cat_list .= "," . $child_cat->category_id;
	}
	
	$drRows = $wpdb->get_row("SELECT count(i.ID) as RowCount
								FROM " . $wpdb->prefix . "posts as i
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic on i.ID=ic.inventory_id
																						and ic.category_id IN (" .  $cat_list . ")
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
																								and ic.category_id IN (" .  $cat_list . ") 
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
	
	if (has_post_thumbnail($inventory_id)) {
		// Get featured image if it exists
		$featuredImage = wp_get_attachment_image_src(get_post_thumbnail_id($inventory_id), "full");
		return $featuredImage[0];
	} else {
		// Use first valid image
		$inventory_images = $wpdb->get_results( 
			"
			SELECT attached_image_id, image_id
			FROM " . $wpdb->prefix . "foxypress_inventory_images
			WHERE inventory_id = " . $inventory_id . " 
			ORDER BY image_order ASC
			"
		);
		
		foreach ( $inventory_images as $image ) 
		{
			if ($image != null) {
				
				// Check to see if image has been deleted
				if (get_post($image->attached_image_id) == null) {
					// Delete image attachment
					foxypress_RemoveInventoryImage($image->image_id);
				} else {
					$featuredImage = wp_get_attachment_image_src($image->attached_image_id, "full");
					return $featuredImage[0];
				}
			}
		}
		
		// No valid image found - return the FoxyPress default product image
		return plugins_url( 'img/' . INVENTORY_DEFAULT_IMAGE , __FILE__ );
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

/**
 * Gets the Featured Image URL for the given post ID. If none
 *   exists, returns an empty string.
 * 
 * @param $inventory_id  Post ID to get Featured Image
 * @param $size          Size of image. If not provided, returns full
 * 						   size. Options: thumbnail, medium, large or full
 */
function foxypress_GetFeaturedInventoryImage($inventory_id, $size = "full")
{
	global $wpdb, $post;
	$featuredImageID = (has_post_thumbnail($inventory_id)) ? get_post_thumbnail_id($inventory_id) : 0;
	if($featuredImageID != 0)
	{
		$featuredSrc = wp_get_attachment_image_src($featuredImageID, $size);
		return $featuredSrc[0];
	}
	return "";
}

/**
 * Returns an unordered list of thumbnails for the requested product post ID
 * 
 * @param $post_id    Post ID of product
 * @param $title      Title of product
 * @param $css_class  CSS class to attach to ul element
 * @param $image_mode Image mode
 * @param $min_thumbs Minimum number of thumbnails required in order to display
 */
function foxypress_GetImageThumbs($post_id, $title, $css_class, $image_mode, $min_thumbs = 0) {
	global $wpdb;
	
	// Track how many thumbnails have been added
	$numThumbs = 0;
	// Set up string used to hold all thumbnails
	$ItemThumbs = "";
	
	// Display featured image as first thumb if it exists
	$FeaturedImageThumb = foxypress_GetFeaturedInventoryImage($post_id, "thumbnail");
	if ($FeaturedImageThumb != "") {
		$FeaturedImageFull = foxypress_GetFeaturedInventoryImage($post_id, "full");
		
		if($image_mode == FOXYPRESS_USE_COLORBOX) {
			$ItemThumbs .= "<li><a href=\"" . $FeaturedImageFull . "\" rel=\"colorbox\"><img src=\"" . $FeaturedImageThumb . "\" /></a></li>";
		} else if($image_mode == FOXYPRESS_USE_LIGHTBOX) {
			$ItemThumbs .= "<li><a href=\"" . $FeaturedImageFull . "\" rel=\"lightbox[foxypress" . $post_id. "]\" title=\"" . stripslashes($title) . "\"><img src=\"" . $FeaturedImageThumb . "\" /></a></li>";
		} else {
			$ToggleID = "toggle-image-" . foxypress_GenerateRandomString(8);
			$ItemThumbs .= "<li><a id=\"$ToggleID\" href=\"javascript:ToggleItemImage('#$ToggleID', '" . $FeaturedImageFull . "');\" ><img src=\"" . $FeaturedImageThumb . "\" /></a></li>";
		}
		
		$numThumbs++;
	}
	
	// Get remaining inventory images attached to this product
	$inventory_images = foxypress_GetInventoryImages($post_id);
	
	foreach ( $inventory_images as $image ) 
	{
		// Check to see if image has been deleted
		if (get_post($image->attached_image_id) == null) {
			// Delete image attachment
			foxypress_RemoveInventoryImage($image->image_id);
		} else {
			$ImageFullAttributes = wp_get_attachment_image_src($image->attached_image_id, "full");
			$ImageFullURL = $ImageFullAttributes[0];
			$ImageThumbURL = wp_get_attachment_thumb_url($image->attached_image_id);
			
			if($image_mode == FOXYPRESS_USE_COLORBOX) {
				$ItemThumbs .= "<li><a href=\"" . $ImageFullURL . "\" rel=\"colorbox\"><img src=\"" . $ImageThumbURL . "\" /></a></li>";
			} else if($image_mode == FOXYPRESS_USE_LIGHTBOX) {
				$ItemThumbs .= "<li><a href=\"" . $ImageFullURL . "\" rel=\"lightbox[foxypress" . $post_id. "]\" title=\"" . stripslashes($title) . "\"><img src=\"" . $FeaturedImageThumb . "\" /></a></li>";
			} else {
				$ToggleID = "toggle-image-" . foxypress_GenerateRandomString(8);
				$ItemThumbs .= "<li><a id=\"$ToggleID\" href=\"javascript:ToggleItemImage('#$ToggleID', '" . $ImageFullURL . "');\" ><img src=\"" . $ImageThumbURL . "\" /></a></li>";
			}
			
			$numThumbs++;
		}
	}
	
	// Determine if the number of thumbnails matches the minimum requested, and that it's not empty
	if ($numThumbs >= $min_thumbs && strlen($ItemThumbs) > 0) {
		// Return the thumbnails unordered list
		return "<ul class=\"$css_class\">$ItemThumbs</ul>";
	} else {
		// Otherwise return a blank string
		return "";
	}
}

function foxypress_RemoveInventoryImage($inventory_image_id) {
	global $wpdb;
	
	$query = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_images WHERE image_id='" . $wpdb->escape($inventory_image_id) . "'";
	return $wpdb->query($query);
}

/**
 * Inserts a new inventory->category association
 *
 * @since 0.4.4.0
 *
 * @param int $post_id ID of post to retrieve images for
 *
 * @return Inventory images as a PHP object
 */
function foxypress_GetInventoryImages($post_id) {
	global $wpdb;
	$inventory_images = $wpdb->get_results( 
		"
		SELECT attached_image_id, image_id
		FROM " . $wpdb->prefix . "foxypress_inventory_images
		WHERE inventory_id = " . $post_id . " 
		ORDER BY image_order ASC
		"
	);

	return $inventory_images;
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
	$_item_deal_active = get_post_meta($item->ID,'_item_deal_active',TRUE);
	$_item_deal_code_type = get_post_meta($item->ID,'_item_deal_code_type',TRUE);
	$_item_deal_static_code = get_post_meta($item->ID,'_item_deal_static_code',TRUE);
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
	
	$primaryCategories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id, itc.category_primary
												FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " as itc inner join " .
												$wpdb->prefix . "foxypress_inventory_categories" . " as c on itc.category_id = c.category_id
												WHERE inventory_id='" . $item->ID . "'");
	$primary_category = "";
	foreach($primaryCategories as $pc)
	{
		if($pc->category_primary == 1) {
			$primary_category = $pc->category_name;
		} 
	}
	
	//use previous category name if a new primary one hasn't been selected yet
	if($primary_category == "") {
		$primary_category = stripslashes($item->category_name);
	}
	
	//check to see if we need to link to a detail page
	if($showMoreDetail)
	{
		$MoreDetail = "<div class=\"foxypress_item_readmore" . $CssSuffix . "\"><a href=\"" . foxypress_get_product_url($item->ID) . "\">Read More</a></div>";
	}

	if($ShowAddToCart)
	{
		// Get image thumbnails
		$ItemThumbs = foxypress_GetImageThumbs($item->ID, $item->post_title, "foxypress_item_image_thumbs", $Foxypress_Image_Mode, 2); 
		
		if($ShowMainImage)
		{
			$MainImageOutput = "";
			if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
			{
				$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"colorbox\"><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
			}
			else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
			{
				$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
			}
			else if($Foxypress_Image_Mode == FOXYPRESS_USE_EASYIMAGEZOOM)
			{
				$MainImageOutput = "<a href=\"" . $ItemImage . "\" class=\"easyzoom\" /><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
			}
			else
			{
				$MainImageOutput = "<img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" />";
			}
			
			if ($ItemImage == "") {
				// If there is no item image, use the default
				$ImageOutput = "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" class=\"foxypress_main_item_image\" />";
			}
			else if ($ItemThumbs == "") {
				// If there are no thumbnails, main image needs to be clickable
				$ImageOutput = $MainImageOutput;
			}
			else {
				if($Foxypress_Image_Mode == FOXYPRESS_USE_EASYIMAGEZOOM) {
					// If Easy Image Zoom is in use, add easyzoom link container
					$ImageOutput = "<a href=\"" . $ItemImage . "\" class=\"easyzoom\" /><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
				} else {
					// Otherwise display main image without link
					$ImageOutput = "<img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" />";
				}
			}
		}
		$ImageOutput = "<div class=\"foxypress_zoom_image foxypress_item_image" . $CssSuffix . "\">" . $ImageOutput . $ItemThumbs . "</div>";
		if(isset($_SESSION['affiliate_id'])){$affiliate_id=$_SESSION['affiliate_id'];}else{$affiliate_id="";}
		$CssSuffix="";
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
								<input type=\"hidden\" name=\"category\" value=\"" . $primary_category . "\" />
								<input type=\"hidden\" name=\"image\" value=\"" . ( ($ItemImage != "") ? $ItemImage : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE ) . "\" />
								<input type=\"hidden\" name=\"weight\" value=\"" . foxypress_GetActualWeight($_weight, $_weight2) . "\" />
								<input type=\"hidden\" name=\"inventory_id\" value=\"" . $item->ID . "\" />
								<input type=\"hidden\" name=\"h:blog_id\" value=\"" . $wpdb->blogid . "\" />
								<input type=\"hidden\" name=\"h:affiliate_id\" value=\"" . $affiliate_id . "\" />"
								 .
									( ($_item_deal_active == "1" && $_item_deal_code_type == "static")
										? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . $_item_deal_static_code . "\" />"
										: ""
									)
								 .
								 	( ($_item_deal_active == "1" && $_item_deal_code_type == "random")
										? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . getGUID() . "\" />"
										: ""
									)
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
	$_item_deal_active = get_post_meta($item->ID,'_item_deal_active',TRUE);
	$_item_deal_code_type = get_post_meta($item->ID,'_item_deal_code_type',TRUE);
	$_item_deal_static_code = get_post_meta($item->ID,'_item_deal_static_code',TRUE);
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

	$primaryCategories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id, itc.category_primary
												FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " as itc inner join " .
												$wpdb->prefix . "foxypress_inventory_categories" . " as c on itc.category_id = c.category_id
												WHERE inventory_id='" . $item->ID . "'");
	$primary_category = "";
	foreach($primaryCategories as $pc)
	{
		if($pc->category_primary == 1) {
			$primary_category = $pc->category_name;
		} 
	}
	
	//use previous category name if a new primary one hasn't been selected yet
	if($primary_category == "") {
		$primary_category = stripslashes($item->category_name);
	}
	
	// Get image thumbnails
	$ItemThumbs = foxypress_GetImageThumbs($item->ID, $item->post_title, "foxypress_item_image_thumbs_detail", $Foxypress_Image_Mode, 2);
	
	if($showMainImage)
	{
		$MainImageOutput = "";
		if($Foxypress_Image_Mode == FOXYPRESS_USE_COLORBOX)
		{
			$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"colorbox\"><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
		}
		else if($Foxypress_Image_Mode == FOXYPRESS_USE_LIGHTBOX)
		{
			$MainImageOutput = "<a href=\"" . $ItemImage . "\" rel=\"lightbox[foxypress" . $item->ID . "]\" title=\"" . stripslashes($item->post_title) . "\"><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
		}
		else if($Foxypress_Image_Mode == FOXYPRESS_USE_EASYIMAGEZOOM)
		{
			$MainImageOutput = "<a href=\"" . $ItemImage . "\" class=\"easyzoom\" /><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
		}
		else
		{
			$MainImageOutput = "<img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" />";
		}
		
		if ($ItemImage == "") {
			// If there is no item image, use the default
			$ImageOutput = "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" class=\"foxypress_main_item_image\" />";
		}
		else if ($ItemThumbs == "") {
			// If there are no thumbnails, main image needs to be clickable
			$ImageOutput = $MainImageOutput;
		}
		else {
			if($Foxypress_Image_Mode == FOXYPRESS_USE_EASYIMAGEZOOM) {
				// If Easy Image Zoom is in use, add easyzoom link container
				$ImageOutput = "<a href=\"" . $ItemImage . "\" class=\"easyzoom\" /><img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" /></a>";
			} else {
				// Otherwise display main image without link
				$ImageOutput = "<img src=\"" . $ItemImage . "\" class=\"foxypress_main_item_image\" />";
			}
		}
	}
	$ImageOutput = "<div class=\"foxypress_zoom_image  foxypress_item_image_detail\">" . $ImageOutput . $ItemThumbs . "</div>";
	//show item
	if(isset($_SESSION['affiliate_id'])){$affiliate_id=$_SESSION['affiliate_id'];}else{$affiliate_id="";}
	$CssSuffix="";
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
						<input type=\"hidden\" name=\"category\" value=\"" . $primary_category . "\" />
						<input type=\"hidden\" name=\"image\" value=\"" . ( ($ItemImage != "") ? $ItemImage : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE ) . "\" />
						<input type=\"hidden\" name=\"weight\" value=\"" . foxypress_GetActualWeight($_weight, $_weight2) . "\" />
						<input type=\"hidden\" name=\"inventory_id\" value=\"" . $item->ID . "\" />
						<input type=\"hidden\" name=\"h:blog_id\" value=\"" . $wpdb->blogid . "\" />
						<input type=\"hidden\" name=\"h:affiliate_id\" value=\"" . $affiliate_id . "\" />"
						 .
							( ($_item_deal_active == "1" && $_item_deal_code_type == "static")
								? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . $_item_deal_static_code . "\" />"
								: ""
							)
						 .
						 	( ($_item_deal_active == "1" && $_item_deal_code_type == "random")
								? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . getGUID() . "\" />"
								: ""
							)
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
							:  "<div class=\"foxypress_item_submit_wrapper_detail out-of-stock\">
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
	if(isset($_SESSION['affiliate_discount_type']))
	{
		$affiliate_discount_type = $_SESSION['affiliate_discount_type'];
	}else{
		$affiliate_discount_type = "";
	}
	if(isset($_SESSION['affiliate_discount_amount']))
	{
		$affiliate_discount_amount = $_SESSION['affiliate_discount_amount'];
	}else{
		$affiliate_discount_amount = "";
	}
	$ActualPrice = $price;
	if($saleprice != "" && $saleprice > 0)
	{
		$CanUseSalePrice = false;
		//check dates
		if($startdate == null && $enddate == null)
		{
			$CanUseSalePrice = true;
		}
		$Today = current_time('timestamp');
		if(!$CanUseSalePrice && strtotime($startdate) <= $Today && strtotime($enddate) >= $Today)
		{
			$CanUseSalePrice = true;
		}
		if($CanUseSalePrice)
		{
			$ActualPrice = $saleprice;
		}
	}
	//Affiliate Discount
	if ($affiliate_discount_type && $affiliate_discount_amount) {
		if ($affiliate_discount_type == 1) {
			$subtract = $affiliate_discount_amount / 100 * $ActualPrice;
			$affiliate_price = $ActualPrice - $subtract;
			$affiliate_discount = number_format((double)$affiliate_price,2,".","");
		} else if ($affiliate_discount_type == 2) {
			$affiliate_price = $ActualPrice - $affiliate_discount_amount;
			$affiliate_discount = number_format((double)$affiliate_price,2,".","");
		} else {
			$affiliate_discount = $ActualPrice;
		}
		return $affiliate_discount;
	} else {
		return $ActualPrice;
	}
}

function foxypress_CanAddToCart($inventory_id, $quantity)
{
	//check the options available, if any of the option lists have 0 items, then we cannot add to cart
	global $wpdb;
	if($quantity!=""){
		$quantity = (int)$quantity;
		if($quantity <= 0){
			return false;
		}
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
			$HashedName="";
			$soldOutList = array();
			$listItems = "";
			$jsData = "";
			$image_js = false;
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
					// Determine if option is active, and if option quantity is > 0 OR option quantity is blank (infinite)
					if($option->option_active == "1" && ($option->option_quantity > 0 || $option->option_quantity == ""))
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
						if(!empty($option->option_image))
						{
							$opt_image = $option->option_image;
							$image_js = true;
						}
						else
						{
							$opt_image = INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;
						}
						if($extraattribute != "")
						{
							$extraattribute = "{" . $extraattribute . "}";
						}
						$listItems  .= "<option rel=\"" . $opt_image . "\" value=\"" . htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "\">" . htmlspecialchars(stripslashes($option->option_text)) . $extraattributefriendly . "</option>";
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
					$JsToAdd = "foxypress_modify_max('" . $formid . "', '" . $jsData . "', this.value, " . $defaultMaxQty . ", '');";
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
				if ($image_js == true)
				{
					$JsToAdd = "foxypress_change_option_image(this, '" . stripslashes($groupName) . "');";
				}
				$MasterList .= "<div class=\"foxypress_item_options\">" .
									 stripslashes($groupName) . ":
									<select id=\"" . str_replace(" ", "_", stripslashes($groupName)) . "\" name=\"" . stripslashes($groupName) . "\" onchange=\"" . $JsToAdd . "\"><option rel='" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "' value=''>--Select--</option>"
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
					$soldOutItems = "<div class=\"foxypress_item_otions_soldout\">" . __('Sold Out Options', 'foxypress') . ": " . $soldOutItems . "</div>";
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
	$limit = trim($atts['items']);
	$limit = (empty($limit)) ? 5 : $limit;
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
		$returnHTML = foxypress_handle_shortcode_listing(trim($atts['categoryid']), $limit, trim($atts['cols']), $showMoreDetail, $showMainImage, $showAddToCart, $showQuantity);
	}
	else if($mode == 'related')
	{
		$related_items = foxypress_GetRelatedItems(trim($atts['productid']));
		$returnHTML = foxypress_handle_shortcode_list($related_items, trim($atts['cols']), $showMoreDetail, $showMainImage, $showAddToCart, $showQuantity);
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
			_e("Warning! NOT an image file! File not uploaded.", 'foxypress');
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
				$error = _e("The file is too big. (php.ini)", 'foxypress'); // php installation max file size error
				break;
			case 2:
				$error = _e("The file is too big. (form)", 'foxypress'); // form max file size error
				break;
			case 3:
				$error = _e("Only part of the file was uploaded", 'foxypress');
				break;
			case 4:
				$error = _e("No file was uploaded", 'foxypress');
				break;
			case 6:
				$error = _e("Missing a temporary folder.", 'foxypress');
				break;
			case 7:
				$error = _e("Failed to write file to disk", 'foxypress');
				break;
			case 8:
				$error = _e("File upload stopped by extension", 'foxypress');
				break;
			default:
			  	$error = _e("Unknown error (" . $field["error"] . ")", 'foxypress');
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
	$remoteDomain = get_option('foxycart_remote_domain');
	$scripts = "";
	
	/*
	<script src="//cdn.foxycart.com/adamwoloszyn/foxycart.colorbox.js?ver=2" type="text/javascript" charset="utf-8"></script>
	<script src="//cdn.foxycart.com/store.adamwoloszyn.com/foxycart.colorbox.js?ver=2" type="text/javascript" charset="utf-8"></script>
	*/
	
	if($version == "1.1")
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
							.
							(
								($includejq)
									? "<script type=\"text/javascript\" src=\"//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js\"></script>"
									: ""
							)
							.
							"<script src=\"//cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.colorbox.js?ver=2\" type=\"text/javascript\" charset=\"utf-8\"></script>
							<link rel=\"stylesheet\" href=\"//cdn.foxycart.com/static/scripts/colorbox/1.3.23/style1_fc/colorbox.css?ver=1\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	else if($version == "1.0")
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
							.
							(
								($includejq)
									? "<script type=\"text/javascript\" src=\"//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js\"></script>"
									: ""
							)
							.
							"<script src=\"//cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.colorbox.js?ver=2\" type=\"text/javascript\" charset=\"utf-8\"></script>
							<link rel=\"stylesheet\" href=\"//cdn.foxycart.com/static/scripts/colorbox/1.3.19/style1_fc/colorbox.css?ver=1\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	else if($version == "0.7.2")
	{
		$scripts = "<!-- BEGIN FOXYCART FILES -->"
					.
					(
						($includejq)
							? "<script type=\"text/javascript\" src=\"//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js\"></script>"
							: ""
					)
					.
					"<script src=\"//cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.colorbox.js\" type=\"text/javascript\" charset=\"utf-8\"></script>
					<link rel=\"stylesheet\" href=\"//cdn.foxycart.com/static/scripts/colorbox/1.3.18/style1_fc/colorbox.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
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
							? "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js\"></script>"
							: ""
					)
					.
					"<script src=\"http://cdn.foxycart.com/" . get_option('foxycart_storeurl') . "/foxycart.complete.2.js\" type=\"text/javascript\" charset=\"utf-8\"></script>
					<link rel=\"stylesheet\" href=\"http://static.foxycart.com/scripts/colorbox/1.3.16/style1_fc/colorbox.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />
					<!-- END FOXYCART FILES -->";
	}
	if($enablemuliship == "1")
	{
		$scripts .= "<script src=\"" . plugins_url() . "/foxypress/js/multiship.jquery.js\" type=\"text/javascript\" charset=\"utf-8\"></script>";
	}
	if(get_option('foxypress_image_mode') == FOXYPRESS_USE_LIGHTBOX)
	{
		/*$scripts .= "<script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/prototype.js\"></script>
					 <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/scriptaculous.js?load=effects,builder\"></script>
					 <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/lightbox.js\"></script>
					 <link rel=\"stylesheet\" href=\"". plugins_url() ."/foxypress/css/lightbox.css\" type=\"text/css\" media=\"screen\" />";*/
	}
	
	/* load Easy Image Zoom javascript libraries if it's enabled */
	if (get_option('foxypress_image_mode') == FOXYPRESS_USE_EASYIMAGEZOOM) {
		$scripts .= "<script src=\"" . plugins_url() . "/foxypress/js/easyzoom.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script>" . "<link rel=\"stylesheet\" href=\"". plugins_url() ."/foxypress/css/easyzoom.css\" type=\"text/css\" media=\"screen\" />";
		$scripts .= "<script type=\"text/javascript\">
		
		jQuery(function(){
			
			// Only enable EasyImageZoom if there is one zoom element
			if (jQuery('.easyzoom').length == 1) {
				console.log('one easyzoom element found');
				jQuery('.easyzoom').easyZoom({
					parent: '.foxypress_zoom_image'
				});
			}
		});
		
		</script>";
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
					alert(__('Please fill out both the order number and you last name', 'foxypress'));
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

				jQuery('#foxypress-errors').hide();
				var curProdID = jQuery('[name=inventory_id]').val();
				jQuery('#foxypress_' + curProdID).submit(function(event) {
					var error = false;

					jQuery('#foxypress-errors').html('');
					jQuery(this).find('select').each(function() {
						if (jQuery(this).val() == '') {
							error = true;
							var selectName = jQuery(this).attr('name');
							jQuery(this).addClass('select-error');
							jQuery('#foxypress-errors').append('Please select a ' + selectName + ' option.<br />')
						}
					});
					
					if (error == false) {
						// Let form submit on its own	
						return true;					
					} else {
						// Prevent form from being submitted and show errors
						event.preventDefault();
						jQuery('#foxypress-errors').show();
						return false;
					}
				});
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

			function foxypress_change_option_image(select_id, group_name)
			{
				var img = jQuery(select_id).find('option:selected').attr('rel');
				if (img != "")
				{
					jQuery('div.productimage').html('<img src="' + img + '" class="foxypress_main_item_image" />');
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

			function ToggleItemImage(clickedElement, newImage)
			{
				jQuery(clickedElement).parents().eq(2).find('.foxypress_main_item_image').attr('src', newImage);
				
				<?php 
					// Only perform easyzoom Javascript functions if it's selected
					if (get_option('foxypress_image_mode') == FOXYPRESS_USE_EASYIMAGEZOOM): 
				?>
				// Only enable easyzoom if there is just one element
				if (jQuery('.easyzoom').length == 1) {
			    	jQuery('.easyzoom').attr('href', newImage);
				    jQuery('.easyzoom').easyZoom({
				    	parent: '.foxypress_zoom_image'
				    });
			    }
				<?php endif; ?>
			}

		</script>
	<?php
	}
}

function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
            //.chr(125); "}"
        return $uuid;
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
	
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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

function foxypress_CheckValidAPIKey()
{
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "category_list";
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	if ($foxyXMLResponse->result == "SUCCESS")
	{
		return true;
	}
	else
	{
		return false;
	}
}

function foxypress_CheckForFoxyCartUser($customer_email)
{
	global $current_user;
	get_currentuserinfo();
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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

/**
 * Logs an event to the foxypress_event_log table.
 *
 * @since 0.4.3.4
 * 
 * @param string $event_data String to log to event table
 * @return bool Returns false if insert was unsuccessful, true if insert was a success
 */
function foxypress_LogEvent($event_data) {
	global $wpdb;
	
	$result = $wpdb->insert( 
		$wpdb->prefix . "foxypress_event_log", 
		array( 
			'data' => $event_data
		), 
		array( 
			'%s'
		) 
	);
	
	
	if ($result === false) {
		// Insert failed
		return false;
	} else {
		// Insert successful
		return true;
	}
}

/**
 * Returns a sorted and tiered array of product categories. Can return only
 * children of a supplied category if specified.
 *
 * @since 0.4.3.6
 * 
 * @param int $parent_category If specified, will only return children of this
 *                             category (optional)
 * @return array Array of tiered categories
 */
function foxypress_get_product_categories( $parent_category = -1 ) {
	if ($parent_category === -1) {
		// Retrieve all categories
		$categories = array();
		foxypress_get_product_categories_array($categories);
		return $categories;
	} else {
		// Retrieve only categories that are children of the specified $parent_category
		$categories = array();
		foxypress_get_product_categories_array($categories, $parent_category);
		return $categories;
	}
}

/**
 * Returns a sorted and tiered array of all product categories excepting children
 * of the specified category
 *
 * @since 0.4.3.6
 * 
 * @param int $not_children_of_cat_id Does not return children from this
 *                                    category given its category ID
 * @return array Array of tiered categories
 */
function foxypress_get_product_categories_not( $not_children_of_cat_id = 0 ) {
	// Retrieve all categories except children of the $not_cat_id
	$categories = array();
	foxypress_get_product_categories_array($categories);
	$not_categories = array();
	foxypress_get_product_categories_array($not_categories, $not_children_of_cat_id);
	
	// Remove all not categories from $all_categories
	for ($i = 0; $i < count($categories); $i++) {
		for ($j = 0; $j < count($not_categories); $j++) {
			if ($categories[$i]->category_id === $not_categories[$j]->category_id) {
				unset($categories[$i]);
				break;
			}
		} 
	}
	
	return $categories;
}

/**
 * Recursive function that retrieves product categories, sorts them in 
 * alphebetical order and arranges child categories as tiered subcategories.
 *
 * @since 0.4.3.6
 * 
 * @param array &$category_array Array to insert category objects into
 * @param int    $parent_id      Category ID to start at
 * @param int    $tier           Tier of initial category
 */
function foxypress_get_product_categories_array( &$category_array, $parent_id = 0, $tier = 0 ) {
	// Get all child categories of $parent_id
	global $wpdb;
	$categories = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories WHERE category_parent_id = " . $parent_id);
	
	if (count($categories) == 0) {
		return;
	}
	
	// Sort the child categories in alphabetical order
	usort($categories, "foxypress_compare_categories");
	
	// Loop through all child categories and get further children
	foreach ( $categories as $category ) {
		$category->category_name = str_repeat("&mdash; ", $tier) . $category->category_name;
		$category_array[] = $category;
		
		foxypress_get_product_categories_array( $category_array, $category->category_id, $tier + 1 );
	}
}

/**
 * Category comparison function, used by foxypress_get_product_categories_array()
 *
 * @since 0.4.3.6
 * 
 * @param object $a Object A
 * @param object $b Object B
 */
function foxypress_compare_categories($a, $b) {
	if ($a->category_name == $b->category_name) {
		return 0;
	}
	return ($a->category_name < $b->category_name) ? -1 : 1;
}

/**
 * Generates the FoxyPress export file as a CSV. Default location is at 
 * /wp-content/plugins/foxypress/Export.csv
 *
 * @since 0.4.3.6
 * 
 * @param String $filename Optional filename for the export file
 * @param String $location Optional location relative to the foxypress plugin folder
 *
 * @return bool True if export was successful, false if not
 */
function foxypress_generate_export( $filename = "Export.csv", $location = "/foxypress/" ) {
	$list = array();
	
	// CSV Step 1: Store the CSV version number. Used for import
	//   compatibility of updated CSV export files 
	$list[] = array('%%%VERSION', 2);
	
	// CSV Step 2: Store category information
	$list[] = array('%%%CATEGORIES');
	$list[] = array("Category ID","Category Name","Category Parent");
	
	global $wpdb;
	$categories = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories" );
	foreach ( $categories as $category ) {
		if ( $category->category_id == 1 ) {
			// Do not export Default row
		} else {
			$row = array();
			$row[] = $category->category_id;
			$row[] = $category->category_name;
			$row[] = $category->category_parent_id;
			$list[] = $row;
		}
	}
	
	$list[] = array('%%%PRODUCTS');
	
	$row = array();
	$row[] = 'Item Code';
	$row[] = 'Item Name';
	$row[] = 'Item Description';
	$row[] = 'Item Category';
	$row[] = 'Item Price';
	$row[] = 'Item Sale Price';
	$row[] = 'Item Sale Start Date';
	$row[] = 'Item Sale End Date';
	$row[] = 'Item Weight';
	$row[] = 'Item Quantity';
	$row[] = 'Item Quantity Min';
	$row[] = 'Item Quantity Max';
	$row[] = 'Item Options';
	$row[] = 'Item Attributes';	
	$row[] = 'Item Discount Quantity Amount';	
	$row[] = 'Item Discount Quantity Percentage';	
	$row[] = 'Item Discount Price Amount';	
	$row[] = 'Item Discount Price Percentage';	
	$row[] = 'Subscription Frequency';	
	$row[] = 'Subscription Start Date';	
	$row[] = 'Subscription End Date';	
	$row[] = 'Item Start Date';	
	$row[] = 'Item End Date';	
	$row[] = 'Item Active';				
	$row[] = 'Item Images';
	$list[] = $row;
	
	$cats = "";
	$opts = "";
	$attrs = "";
	$Items = $wpdb->get_results("select * from " . $wpdb->prefix . "posts where post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "' and post_status='publish' order by ID");
	if(!empty($Items))
	{
		foreach($Items as $item)
		{
			//get categories
			$cats = "";
			$InventoryCategories = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_to_category where inventory_id='" . $item->ID. "'");
			if(!empty($InventoryCategories))
			{
				foreach($InventoryCategories as $ic)
				{
					$cats .= ($cats == "") ? $ic->category_id : "|" . $ic->category_id;
				}
			}		
			//get options
			$opts = "";
			$InventoryOptions = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_options where inventory_id='" . $item->ID. "'");
			if(!empty($InventoryOptions))
			{
				foreach($InventoryOptions as $io)
				{
					//GroupName|Text|Value|Price|Weight|code|Quantity|Active|Order
					$opt = foxypress_get_option_group_name($io->option_group_id) . "|" 
							. stripslashes($io->option_text) . "|" 
							. stripslashes($io->option_value) . "|" 
							. $io->option_extra_price . "|" 
							. $io->option_extra_weight . "|" 
							. $io->option_code . "|" 
							. $io->option_quantity . "|" 
							. $io->option_active . "|" 
							. $io->option_order;
					$opts .= ($opts == "") ? $opt : "~~" . $opt ;
				}
			}	
			//get attributes
			$attrs = "";
			$InventoryAttributes = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_attributes where inventory_id='" . $item->ID. "'");
			if(!empty($InventoryAttributes))
			{
				foreach($InventoryAttributes as $ia)
				{
					//text|value
					$attr = $ia->attribute_text . "|" . $ia->attribute_value;
					$attrs .= ($attrs == "") ? $attr : "~~" . $attr;
				}
			}			
			
			//get images
			$images = "";
			//get images
			$imageList = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $item->ID, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
			if(!empty($imageList))
			{
				foreach ($imageList as $img) 
				{
					$image_source = wp_get_attachment_image_src($img->ID, "full");
					$images .= ($images == "") ? $image_source[0] : "|" . $image_source[0];							
				}
			}
			
			//write row					
			$row = array(); //clear previous items
			$row[] = get_post_meta($item->ID, "_code", true);
			$row[] = $item->post_title;
			$row[] = $item->post_content;
			$row[] = $cats;
			$row[] = get_post_meta($item->ID, "_price", true);
			$row[] = get_post_meta($item->ID, "_saleprice", true);
			$row[] = get_post_meta($item->ID, "_salestartdate", true);
			$row[] = get_post_meta($item->ID, "_saleenddate", true);
			$row[] = get_post_meta($item->ID,'_weight', true);
			$row[] = get_post_meta($item->ID,'_quantity', true);
			$row[] = get_post_meta($item->ID,'_quantity_min', true);
			$row[] = get_post_meta($item->ID,'_quantity_max', true);
			$row[] = $opts;
			$row[] = $attrs;
			$row[] = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
			$row[] = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
			$row[] = get_post_meta($item->ID,'_discount_price_amount',TRUE);
			$row[] = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
			$row[] = get_post_meta($item->ID,'_sub_frequency',TRUE);
			$row[] = get_post_meta($item->ID,'_sub_startdate',TRUE);
			$row[] = get_post_meta($item->ID,'_sub_enddate',TRUE);
			$row[] = get_post_meta($item->ID,'_item_start_date',TRUE);
			$row[] = get_post_meta($item->ID,'_item_end_date',TRUE);
			$row[] = get_post_meta($item->ID,'_item_active',TRUE);		
			$row[] = $images;
			$list[] = $row;
		}
	}
	if (file_exists(WP_PLUGIN_DIR . $location . $filename)) 
	{
		unlink(WP_PLUGIN_DIR . $location . $filename);
	}
	$f = fopen(WP_PLUGIN_DIR . $location . $filename, "x+");
	
	foreach ($list as $line)
	{
		fputcsv($f, $line );
		fseek($f, -1, SEEK_CUR);
		fwrite($f, "\r\n"); 
	}
	fclose($f);
	
	return true;
}

/**
 * Processes a FoxyPress CSV import file. Default location is at 
 * /wp-content/plugins/foxypress/Import.csv
 *
 * @since 0.4.3.6
 * 
 * @param bool   $preview  Optional If true, will only generate a preview. If false, will add 
 *                                  the import data to the database
 * @param String $filename Optional filename for the export file
 * @param String $location Optional location relative to the foxypress plugin folder
 *
 * @return array Response object as array
 */
function foxypress_process_import( $preview = true, $filename = "Import.csv", $location = "/foxypress/" ) {
	
	if ( ! $preview ) {
		// Increase time before PHP times out. Required when fetching images.
		set_time_limit(360);
	}

	$response = array();
	$file = fopen( WP_PLUGIN_DIR . $location . $filename, 'r' );

	// Determine Import.csv version
	$version = -1;
	$row_version = fgetcsv( $file );
	if ( $row_version[0] == "%%%VERSION" ){
		$version = $row_version[1];
	} else {
		$version = 1;
	}

	// If import is not the new enough for the parser, return an error
	if ( $version < 2 ) {
		$response[error] = true;
		$response[message] = "Unable to import old FoxyPress export. Please update the import file to version 2.";
		return $response;
	}

	global $wpdb;
	$response[categories] = array();
	$response[products] = array();
	$current_row = 1;
	$import_mode = "";
	$categories = array();
	$new_cat_ids = array();
	$empty_rows = 0;
	$new_cat_count = 0;
	$new_prod_count = 0;

	while( ! feof( $file ) ) {
		$current_row++;
		$row = fgetcsv( $file );

		// Ignore empty rows
		if ( empty($row[0]) ) {
			continue;
		}

		// Check to see if this is a mode set/change row
		if ( substr( $row[0], 0, 3) == "%%%" ) {
			// Set new mode
			$import_mode = substr( $row[0], 3 );
			// Continue to next row
			continue;
		}

		switch ( $import_mode ) {
			case "CATEGORIES":
				// Parse category rows here

				// Ignore the title row
				if ( $row[0] == "Category ID" ) {
					break;
				}

				// Validate category data
				if ( ! is_numeric( $row[0] ) ) {
					$response[error] = true;
					$response[message] = "Import parse error on line " . $current_row . "; Category ID must be numeric.";
					return $response;
				}
				if ( empty( $row[1] ) ) {
					$response[error] = true;
					$response[message] = "Import parse error for category " . $row[0] . "; Category name cannot be empty.";
					return $response;
				}
				if ( ! is_numeric( $row[2] ) ) {
					$response[error] = true;
					$response[message] = "Import parse error for category " . $row[0] . "; Category parent ID must be numeric.";
					return $response;
				}

				$cat_import_id = $row[0];
				$cat_import_name = $row[1];
				$cat_import_parent_id = $row[2];

				// Determine if matching category name already exists in this FoxyPress install
				$cat_new_id = foxypress_get_category_by_name( $cat_import_name );

				// Propogate response object for preview
				if ( $preview ) {
					$cat_insert_required = false;
					if ( is_null( $cat_new_id ) ) {
						$cat_insert_required = true;
					}

					$response[categories][] = array(
						"category_import_id" => $cat_import_id,
						"category_name" => $cat_import_name,
						"category_parent_id" => $cat_import_parent_id,
						"category_insert" => $cat_insert_required
					);

				// If this is not a preview, continue executing import of categories "for real"
				} else {
					// Insert category, retrieve new category ID
					if ( is_null( $cat_new_id ) ) {
						$cat_new_id = foxypress_insert_category( $cat_import_name );
						$new_cat_count++;
					}

					// Add category to categories array, to be further processed after all categories
					//   have been added to the FoxyPress install
					$new_cat_ids[$cat_import_id] = $cat_new_id;
					$new_categories[] = array(
						"cat_import_id" => $cat_import_id,
						"cat_import_name" => $cat_import_name,
						"cat_import_parent_id" => $cat_import_parent_id,
						"cat_new_id" => $cat_new_id
					);
				}
				break;

			case "PRODUCTS":
				// Parse product rows here

				// Ignore the title row
				if ( $row[0] == "Item Code" ) {
					break;
				}

				$name = mysql_escape_string($row[1]);
				$description = $row[2];
				$code = mysql_escape_string($row[0]);
				$price = mysql_escape_string(str_replace('$','',$row[4]));
				$saleprice = mysql_escape_string(str_replace('$','',$row[5]));
				$salestartdate = mysql_escape_string($row[6]);
				$saleenddate = mysql_escape_string($row[7]);
				$weight = mysql_escape_string($row[8]);
				$quantity = mysql_escape_string($row[9]);
				$quantity_min = mysql_escape_string($row[10]);
				$quantity_max = mysql_escape_string($row[11]);
				$discount_quantity_amount = mysql_escape_string($row[14]);
				$discount_quantity_percentage = mysql_escape_string($row[15]);
				$discount_price_amount = mysql_escape_string($row[16]);
				$discount_price_percentage = mysql_escape_string($row[17]);
				$sub_frequency = mysql_escape_string($row[18]);
				$sub_startdate = mysql_escape_string($row[19]);
				$sub_enddate = mysql_escape_string($row[20]);
				$item_start_date = mysql_escape_string($row[21]);
				$item_end_date = mysql_escape_string($row[22]);
				$item_active = mysql_escape_string($row[23]);
				$categories = $row[3];
				$options = $row[12];
				$attributes = $row[13];
				$images = mysql_escape_string($row[24]);

				// Generate preview if requested
				if ( $preview ) {
					$response['products'][] = array(
						"_name" => $name,
						"_description" => $description,
						"_code" => $code,
						"_price" => $price,
						"_saleprice" => $saleprice,
						"_salestartdate" => $salestartdate,
						"_saleenddate" => $saleenddate,
						"_weight" => $weight,
						"_quantity" => $quantity,
						"_quantity_min" => $quantity_min,
						"_quantity_max" => $quantity_max,
						"_discount_quantity_amount" => $discount_quantity_amount,
						"_discount_quantity_percentage" => $discount_quantity_percentage,
						"_sub_frequency" => $sub_frequency,
						"_sub_startdate" => $sub_startdate,
						"_sub_enddate" => $sub_enddate,
						"_item_start_date" => $item_start_date,
						"_item_end_date" => $item_end_date,
						"_item_active" => $item_active,
						"_categories" => $categories,
						"_options" => $options,
						"_attributes" => $attributes,
						"_images" => $images
					);

				// If this is not a preview, continue executing import of products "for real"
				} else {
					$my_post = array(
						'post_title' => $name,
						'post_content' => $description,
						'post_status' => 'publish',
						'post_author' => 1,
						'post_type' => FOXYPRESS_CUSTOM_POST_TYPE
			  	);
		  		$inventory_id = wp_insert_post( $my_post );
					foxypress_save_meta_data($inventory_id, '_code', $code);
					foxypress_save_meta_data($inventory_id, '_price', $price);
					foxypress_save_meta_data($inventory_id, '_saleprice', $saleprice);
					foxypress_save_meta_data($inventory_id, '_salestartdate', $salestartdate);
					foxypress_save_meta_data($inventory_id, '_saleenddate', $saleenddate);
					foxypress_save_meta_data($inventory_id, '_weight', $weight);
					foxypress_save_meta_data($inventory_id, '_quantity', $quantity);
					foxypress_save_meta_data($inventory_id, '_quantity_min', $quantity_min);
					foxypress_save_meta_data($inventory_id, '_quantity_max', $quantity_max);						
					foxypress_save_meta_data($inventory_id, '_discount_quantity_amount', $discount_quantity_amount);
					foxypress_save_meta_data($inventory_id, '_discount_quantity_percentage', $discount_quantity_percentage);
					foxypress_save_meta_data($inventory_id, '_discount_price_amount', $discount_price_amount);
					foxypress_save_meta_data($inventory_id, '_discount_price_percentage', $discount_price_percentage);
					foxypress_save_meta_data($inventory_id, '_sub_frequency', $sub_frequency);
					foxypress_save_meta_data($inventory_id, '_sub_startdate', $sub_startdate);
					foxypress_save_meta_data($inventory_id, '_sub_enddate', $sub_enddate);
					foxypress_save_meta_data($inventory_id, '_item_start_date', $item_start_date);
					foxypress_save_meta_data($inventory_id, '_item_end_date', $item_end_date);
					foxypress_save_meta_data($inventory_id, '_item_active', $item_active);

					// Configure product categories
					$category_array = explode("|", $categories);
					foreach ( $category_array as $category ) {
						if ( $category == 1 ) {
							// Maintain value for default category
							foxypress_insert_inventory_category_assoc( $inventory_id, 1 );
						} else {
							// Set category association to the existing or new category ID
							foxypress_insert_inventory_category_assoc( $inventory_id, $new_cat_ids[$category] );
						}
					}

					// Configure product options
					$options_array = explode( "~~", $options );
					foreach ( $options_array as $option ) {
						$option_array = explode("|", $option);
						if( count( $option_array ) == 9 ) {
							//get option group id
							$OptionGroupID = foxypress_get_option_group_id( $option_array[0] );					
							$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_options (inventory_id, option_group_id, option_text, option_value, option_extra_price, option_extra_weight, option_code, option_quantity, option_active, option_order) values ('$inventory_id', '" . $OptionGroupID . "', '" . mysql_escape_string($option_array[1]) . "', '" . mysql_escape_string($option_array[2]) . "' , '" . mysql_escape_string(str_replace('$','',$option_array[3])) . "', '" . mysql_escape_string($option_array[4]) . "', '" . mysql_escape_string($option_array[5]) . "', '" . mysql_escape_string($option_array[6]) . "', '" . mysql_escape_string($option_array[7]) . "', '" . mysql_escape_string($option_array[8]) . "')");
						}	
					}			
			
					// Configure product attributes
					$attributes_array = explode( "~~", $attributes );
					foreach ( $attributes_array as $attribute ) {
						$attribute_array = explode("|", $attribute);
						if( count($attribute_array) == 2 )
						{
							$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_attributes (inventory_id, attribute_text, attribute_value) values ('$inventory_id', '" . mysql_escape_string($attribute_array[0]) . "', '" . mysql_escape_string($attribute_array[1]) . "')");
						}		
					}
					
					// Configure product images
					$images_array = explode("|", $images);
					$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
					$ImageOrder = 0;
					foreach ( $images_array as $image ) {
						$ImageOrder++;
						$path_parts = pathinfo($image);
						//generate random file name
						$temp_extension = $path_parts['extension'];									
						$temp_file_name = foxypress_GenerateNewFileName($temp_extension, $inventory_id, $directory, "fp_");									
						$temp_destination = $directory . $temp_file_name;
						//try to get file
						$img = file_get_contents($image);			
						if($img)
						{
							file_put_contents($temp_destination, $img);
							foxypress_ConvertImage($temp_destination, $inventory_id, $ImageOrder);
						}
					}

					$new_prod_count++;
				}

				break;
		}

		// Once all the categories and products have been processed, calculate the correct parent IDs for
		//   categories with parents specified
		foreach ( $new_categories as $category ) {
			if ( $category[cat_import_parent_id] != 0 ) {
				foxypress_update_category_parent_id( $category[cat_new_id], $new_cat_ids[$category[cat_import_parent_id]] );
			}
		}
	}

	fclose($file);
	$response[error] = false;
	$response[message] = "Import successful. $new_prod_count products and $new_cat_count categories added. <a href=\"" . admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "\">View Inventory</a>";
	unlink(WP_PLUGIN_DIR . '/foxypress/Inventory.csv');
	return $response;
}

/**
 * Returns category ID of the category with the name supplied. If the category name does not
 *   exist in FoxyPress, null will be returned.
 *
 * @since 0.4.3.6
 * 
 * @param String  $category_name  Category name to search for
 *
 * @return int/null ID of category with matching name, null if match does not exist
 */
function foxypress_get_category_by_name( $category_name ) {
	global $wpdb;
	return $wpdb->get_var( "SELECT category_id from " . $wpdb->prefix  . "foxypress_inventory_categories WHERE LOWER( category_name ) = '" . strtolower( $category_name ) . "'" );
}

/**
 * Inserts a new category into the foxypress categories table
 *
 * @since 0.4.3.6
 * 
 * @param String  $category_name       Category name to add
 * @param int     $category_parent_id  ID of category parent. 0 for no parent (default).
 * @param String  $category_image      Image to attach to category. Blank by default.
 *
 * @return int/boolean ID of inserted row, or false if row could not be inserted
 */
function foxypress_insert_category( $category_name, $category_parent_id = 0, $category_image = "" ) {
	global $wpdb;
	$wpdb->insert( 
		$wpdb->prefix . 'foxypress_inventory_categories', 
		array( 
			'category_name' => $category_name, 
			'category_parent_id' => $category_parent_id,
			'category_image' => $category_image
		), 
		array( 
			'%s',
			'%d',
			'%s'
		)
	);
	return $wpdb->insert_id;
}

/**
 * Updates category parent ID
 *
 * @since 0.4.3.6
 * 
 * @param int  $category_id         Category to update
 * @param int  $category_parent_id  ID of category parent. 0 for no parent (default).
 */
function foxypress_update_category_parent_id( $category_id, $category_parent_id = 0 ) {
	global $wpdb;
	return $wpdb->update( 
		// table
		$wpdb->prefix . "foxypress_inventory_categories", 
		// data
		array( 'category_parent_id' => $category_parent_id ), 
		// where
		array( 'category_id' => $category_id ),
		// data_format
		array( '%d' ), 
		// where_format
		array( '%d' )
	);
}

/**
 * Inserts a new inventory->category association
 *
 * @since 0.4.3.6
 * 
 * @param int  $inventory_id  Inventory ID of item to associate with category
 * @param int  $category_id   Category ID
 *
 * @return int/boolean ID of inserted row, or false if row could not be inserted
 */
function foxypress_insert_inventory_category_assoc( $inventory_id, $category_id ) {

	global $wpdb;
	$wpdb->insert( 
		$wpdb->prefix . 'foxypress_inventory_to_category', 
		array( 
			'inventory_id' => $inventory_id, 
			'category_id' => $category_id
		), 
		array( 
			'%d',
			'%d'
		)
	);
	return $wpdb->insert_id;
}

/**
 * Returns option ID of given the name supplied. If the option name does not
 *   exist in FoxyPress, null will be returned.
 *
 * @since 0.4.3.6
 * 
 * @param String  $option_name  Option name to search for
 *
 * @return int/null ID of option with matching name, null if match does not exist
 */
function foxypress_get_option_group_id( $option_name ) {
	global $wpdb;
	return $wpdb->get_var( "SELECT option_group_id from " . $wpdb->prefix  . "foxypress_inventory_option_group WHERE LOWER( option_group_name ) = '" . strtolower( $option_name ) . "'" );
}

/**
 * Returns option group name from given ID. If the option name does not
 *   exist in FoxyPress, null will be returned.
 *
 * @since 0.4.3.6
 * 
 * @param int  $option_id  Option name to search for
 *
 * @return String/null ID of option with matching name, null if match does not exist
 */
function foxypress_get_option_group_name( $option_id ) {
	global $wpdb;
	return $wpdb->get_var( "SELECT option_group_name from " . $wpdb->prefix  . "foxypress_inventory_option_group WHERE LOWER( option_group_id ) = '" . $option_id . "'" );
}

/**
 * Returns primary category of given product
 *
 * @since 0.4.3.9
 * 
 * @param int  $inventory_id  Inventory ID of product to find primary category
 *
 * @return String Name of inventory item primary category, or the first category if there is no primary cat set
 */
function foxypress_get_primary_category( $inventory_id ) {
	global $wpdb;
	$primaryCategories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id, itc.category_primary
												FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " as itc inner join " .
												$wpdb->prefix . "foxypress_inventory_categories" . " as c on itc.category_id = c.category_id
												WHERE inventory_id='" . $inventory_id . "'");
	$primary_category = "";
	foreach($primaryCategories as $pc)
	{
		if($pc->category_primary == 1) {
			$primary_category = $pc->category_name;
		} 
	}

	if ( $primary_category === "" ) {
		$category_names = $wpdb->get_col( $wpdb->prepare( 
			"
			SELECT      ic.category_name
			FROM        " . $wpdb->prefix . "foxypress_inventory_to_category as itc
			JOIN        " . $wpdb->prefix . "foxypress_inventory_categories as ic
			ON          itc.category_id = ic.category_id
			WHERE       itc.inventory_id = %d
			",
			$inventory_id
		) );

		if ( sizeof( $category_names ) > 0 ) {
			$primary_category = $category_names[0];
		}
	}

	return $primary_category;
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
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_affiliate_referrals");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_email_templates");
	$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_affiliate_assets");

	//check option first before we delete
	$keep_products = get_option("foxypress_uninstall_keep_products");
	//delete custom settings
	$keys = array(
        'foxycart_remote_domain',
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
		'foxypress_user_portal',
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
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_user'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_url'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_facebook_page'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_gender'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_age'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_payout'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_payout_type'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_referral'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_referral_payout'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_referral_payout_type'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_avatar_name'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_avatar_ext'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_discount'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_discount_type'");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "affiliate_discount_amount'");


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
		foxypress_Installation_CreateInventoryImagesTable();
		
		foxypress_Installation_CreateAffiliatePaymentsTable();
		foxypress_Installation_CreateAffiliateTrackingTable();
		foxypress_Installation_CreateAffiliateReferralsTable();
		foxypress_Installation_CreateAffiliateAssetsTable();

		foxypress_Installation_CreateEmailTemplatesTable();
		
		foxypress_Installation_CreateEventLogTable();
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
		//inventory images
		if(!in_array($wpdb->prefix . "foxypress_inventory_images", $tables))
		{
			foxypress_Installation_CreateInventoryImagesTable();
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
		//affiliate referrals
		if(!in_array($wpdb->prefix . "foxypress_affiliate_referrals", $tables))
		{
			foxypress_Installation_CreateAffiliateReferralsTable();
		}
		//affiliate assets
		if(!in_array($wpdb->prefix . "foxypress_affiliate_assets", $tables))
		{
			foxypress_Installation_CreateAffiliateAssetsTable();
		}

		//email templates
		if(!in_array($wpdb->prefix . "foxypress_email_templates", $tables))
		{
			foxypress_Installation_CreateEmailTemplatesTable();
		}
		//event logging
		if(!in_array($wpdb->prefix . "foxypress_event_log", $tables))
		{
			foxypress_Installation_CreateEventLogTable();
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
	//add shipping address company
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_billing_company VARCHAR(100) NULL AFTER foxy_transaction_rmanumber;";
	$wpdb->query($sql);
	//add billing address company
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_transaction ADD foxy_transaction_shipping_company VARCHAR(100) NULL AFTER foxy_transaction_billing_country;";
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
	//add option image
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_options ADD option_image VARCHAR(255) NULL AFTER option_order";
	$wpdb->query($sql);


	///////////////////////////////////////////////////////////////////////////
	//foxypress_inventory_categories
	///////////////////////////////////////////////////////////////////////////
	//add category image
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_categories ADD category_image VARCHAR(100) NULL AFTER category_name;";
	$wpdb->query($sql);
	//add category parent_id
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_categories ADD category_parent_id INT(11) NOT NULL DEFAULT '0' AFTER category_image;";
	$wpdb->query($sql);


	///////////////////////////////////////////////////////////////////////////
	//updates
	///////////////////////////////////////////////////////////////////////////

	//update blog id
	$sql = "UPDATE " . $wpdb->prefix . "foxypress_transaction SET foxy_blog_id = (select min(blog_id) from " . $wpdb->prefix . "blogs) where foxy_blog_id = '0' or foxy_blog_id is null;";
	$wpdb->query($sql);
	
	//update primary category functionality
	$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_inventory_to_category ADD category_primary INT(11) NULL AFTER sort_order;";
	$wpdb->query($sql);

	///////////////////////////////////////////////////////////////////////////
	//Upgrading Affiliate Functionality
	///////////////////////////////////////////////////////////////////////////
	$cur_version = get_option('foxypress_version');
	// Old affiliate versions
	if ($cur_version === '0.3.5' || $cur_version === '0.3.5.1' || $cur_version === '0.3.5.2') {
		//Add affiliate_payout_type
		$affiliate_ids = $wpdb->get_results("SELECT user_id FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'affiliate_user'");
		foreach ($affiliate_ids as $affiliate)
		{
			update_user_option($affiliate->user_id, 'affiliate_payout_type', '1');
			$primary_blog = get_user_option('primary_blog', $affiliate->user_id);
			if ($primary_blog && $primary_blog != '1') {
				//Change affiliate_percentage to affiliate_payout and update to multisite
				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_payout' WHERE meta_key = 'affiliate_percentage' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);
			} else {
				//Change affiliate_percentage to affiliate_payout and update to multisite
				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_payout' WHERE meta_key = 'affiliate_percentage' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);
			}

		}

		//Alter payments table
		$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_affiliate_payments CHANGE foxy_affiliate_percentage foxy_affiliate_payout float(10,2) NOT NULL";
		$wpdb->query($sql);

		$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_affiliate_payments ADD foxy_affiliate_payout_type tinyint(1) NOT NULL AFTER foxy_affiliate_payout;";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_payments SET foxy_affiliate_payout_type = '1'";
		$wpdb->query($sql);
	}
	//Newest Version
	if ($cur_version === '0.3.5.3') {
		//Update percentage
		$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_value = '2' where meta_key = 'affiliate_payout_type' AND meta_value='1';";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_value = '1' where meta_key = 'affiliate_payout_type' AND meta_value='0';";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_payments SET foxy_affiliate_payout_type = '2' where foxy_affiliate_payout_type = '1';";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_payments SET foxy_affiliate_payout_type = '1' where foxy_affiliate_payout_type = '0' or foxy_affiliate_payout_type is null;";
		$wpdb->query($sql);
	}

	if ($cur_version === '0.3.5' || $cur_version === '0.3.5.1' || $cur_version === '0.3.5.2' || $cur_version === '0.3.5.3') {
		$affiliate_ids = $wpdb->get_results("SELECT user_id FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'affiliate_user'");
		foreach ($affiliate_ids as $affiliate)
		{
			$primary_blog = get_user_option('primary_blog', $affiliate->user_id);
			if ($primary_blog && $primary_blog != '1') {
				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_facebook_page' WHERE meta_key = 'affiliate_facebook_page' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_age' WHERE meta_key = 'affiliate_age' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_gender' WHERE meta_key = 'affiliate_gender' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_user' WHERE meta_key = 'affiliate_user' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_payout' WHERE meta_key = 'affiliate_payout' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_payout_type' WHERE meta_key = 'affiliate_payout_type' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->base_prefix . $primary_blog . "_affiliate_url' WHERE meta_key = 'affiliate_url' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);
			} else {
				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_facebook_page' WHERE meta_key = 'affiliate_facebook_page' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_age' WHERE meta_key = 'affiliate_age' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_gender' WHERE meta_key = 'affiliate_gender' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_user' WHERE meta_key = 'affiliate_user' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_payout' WHERE meta_key = 'affiliate_payout' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_payout_type' WHERE meta_key = 'affiliate_payout_type' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);

				$sql = "UPDATE " . $wpdb->base_prefix . "usermeta SET meta_key = '" . $wpdb->prefix . "affiliate_url' WHERE meta_key = 'affiliate_url' AND user_id = '" . $affiliate->user_id . "';";
				$wpdb->query($sql);
			}
		}
	}

	if ($cur_version === '0.3.5' || $cur_version === '0.3.5.1' || $cur_version === '0.3.5.2' || $cur_version === '0.3.5.3' || $cur_version === '0.3.6' || $cur_version === '0.3.6.1' || $cur_version === '0.3.6.2' || $cur_version === '0.3.6.3' || $cur_version === '0.3.7' || $cur_version === '0.3.7.1' || $cur_version === '0.3.7.2') {
		//add commission type
		$sql = "ALTER TABLE " . $wpdb->prefix . "foxypress_affiliate_payments ADD foxy_affiliate_commission_type tinyint(1) NOT NULL AFTER foxy_affiliate_commission";
		$wpdb->query($sql);

		$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_payments SET foxy_affiliate_commission_type = '1'";
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
				category_parent_id INT(11) NOT NULL DEFAULT '0',
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
			foxy_transaction_billing_company VARCHAR(100) NULL,
			foxy_transaction_billing_address1 VARCHAR(50) NULL,
			foxy_transaction_billing_address2 VARCHAR(50) NULL,
			foxy_transaction_billing_city VARCHAR(50) NULL,
			foxy_transaction_billing_state VARCHAR(2) NULL,
			foxy_transaction_billing_zip VARCHAR(10) NULL,
			foxy_transaction_billing_country VARCHAR(50) NULL,
			foxy_transaction_shipping_company VARCHAR(100) NULL,
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
				option_order INT DEFAULT '99',
				option_image VARCHAR(255) NULL
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
				sort_order INT(11) NOT NULL DEFAULT '99',
				category_primary INT(11) NOT NULL
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
				foxy_affiliate_payout float(10,2) NOT NULL,
				foxy_affiliate_payout_type tinyint(1) NOT NULL,
				foxy_affiliate_commission float(10,2) NOT NULL,
				foxy_affiliate_commission_type tinyint(1) NOT NULL,
				foxy_affiliate_payment_method varchar(50) collate utf8_bin NOT NULL,
				foxy_affiliate_payment_date date NOT NULL,
				date_submitted timestamp NOT NULL default CURRENT_TIMESTAMP
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateAffiliateReferralsTable()
{
	global $wpdb;
	//create affliliate payments table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_affiliate_referrals (
  				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_affiliate_referred_by_id int(11) NOT NULL,
				foxy_affiliate_id int(11) NOT NULL
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateAffiliateAssetsTable()
{
	global $wpdb;
	//create affliliate payments table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_affiliate_assets (
  				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_asset_type varchar(50) collate utf8_bin NOT NULL,
				foxy_asset_name varchar(100) collate utf8_bin NOT NULL,
				foxy_asset_file_name varchar(100) collate utf8_bin NOT NULL,
				foxy_asset_file_ext varchar(20) collate utf8_bin NOT NULL,
				foxy_asset_landing_url varchar(250) collate utf8_bin NOT NULL
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

function foxypress_Installation_CreateEventLogTable()
{
	global $wpdb;
	//create email templates table
	$sql = "CREATE TABLE " . $wpdb->prefix . "foxypress_event_log (
  			event_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				data varchar(255) NOT NULL,
				timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
			) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryImagesTable()
{
	global $wpdb;
	//create inventory images table
	$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "foxypress_inventory_images (
				image_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id bigint(20) unsigned NOT NULL,
				attached_image_id bigint(20) unsigned NOT NULL,
				image_order int(11) NOT NULL
			)";
	$wpdb->query($sql);
	
	// Pull in all attached images when creating this table for the first time
	$attachment_posts = $wpdb->get_results( 
		"
		SELECT ID, post_parent, menu_order
		FROM " . $wpdb->prefix . "posts
		WHERE post_type = 'attachment' 
		"
	);
	
	// Loop through all attachment posts and create an entry for each in the 
	//   new wp_foxypress_inventory_images table
	foreach ( $attachment_posts as $attachment_post ) 
	{
		if ($attachment_post->post_parent > 0) {
			$wpdb->insert( 
				$wpdb->prefix . "foxypress_inventory_images", 
				array( 
					'inventory_id' => $attachment_post->post_parent, 
					'attached_image_id' => $attachment_post->ID,
					'image_order' => $attachment_post->menu_order
				), 
				array( 
					'%d', 
					'%d',
					'%d'
				) 
			);
		}
	}
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
	if(!foxypress_option_exists("foxypress_affiliate_approval_email_subject"))
	{
		add_option("foxypress_affiliate_approval_email_subject", "Affiliate status approved!");
	}
	if(!foxypress_option_exists("foxypress_affiliate_approval_email_body"))
	{
		add_option("foxypress_affiliate_approval_email_body", "Hi {{first_name}} {{last_name}},<br />You have been approved to be an affiliate. Your affiliate details are below.<br /><br />{{affiliate_commission}}<br /><br />Affiliate URL: {{affiliate_url}}");
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
		
		// Attach image to post via wp_foxypress_inventory_images table
		$wpdb->insert( 
			$wpdb->prefix . "foxypress_inventory_images", 
			array( 
				'inventory_id' => $post_id, 
				'attached_image_id' => $attachment_id,
				'image_order' => $menu_order
			), 
			array( 
				'%d', 
				'%d',
				'%d'
			) 
		);
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
		$widget_ops = array( 'classname' => 'min', 'description' => __('A widget that will display the FoxyCart cart as a dropdown or in your website\'s sidebar.', 'foxypress') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'mini-cart-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'mini-cart-widget', __('FoxyPress Mini-Cart', 'foxypress'), $widget_ops, $control_ops );
	}

	//Display widget on frontend
	function widget( $args, $instance ) {
		extract( $args );
		
		$remoteDomain = get_option('foxycart_remote_domain');
		if($remoteDomain){
			$foxyStoreURL = get_option('foxycart_storeurl');
		}else{
			$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
		}
		
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
				<a href="https://<?php echo($foxyStoreURL) ?>/cart?cart=view" class="foxycart">View Cart</a>
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
				<a href="https://<?php echo($foxyStoreURL) ?>/cart?checkout" id="fc_checkout_link">Check Out</a>
				<div class="fc_clear"></div>
			</div>
			<script type="text/javascript" charset="utf-8">
				var StoreURL = '<?php echo($foxyStoreURL) ?>';
				var FoxyDomain = StoreURL + "/";
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
		$defaults = array( 'title' => __('Your Cart', 'foxypress'), 'hideonzero' => __('0', 'foxypress'), 'dropdowndisplay' => __('0', 'foxypress'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'foxypress'); ?></label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" type="text" />
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'hideonzero' ); ?>" name="<?php echo $this->get_field_name( 'hideonzero' ); ?>" value="1" <?php echo(($instance['hideonzero'] == "1") ? "checked=\"checked\"" : "") ?>  type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'hideonzero' ); ?>"><?php _e('Hide Cart with 0 Items', 'foxypress'); ?></label>
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>" name="<?php echo $this->get_field_name( 'dropdowndisplay' ); ?>" value="1" <?php echo(($instance['dropdowndisplay'] == "1") ? "checked=\"checked\"" : "") ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>"><?php _e('Drop Down Display', 'foxypress'); ?></label>
		</p>
	<?php
	}
}
?>