<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

//Tracking Info
//$referer 	= $_SERVER['HTTP_REFERER'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$user_ip	= $_SERVER['REMOTE_ADDR'];
$affiliate_id = filter($_GET['aff_id']);
if (isset($_GET['url'])) {
	$destination_url = $_GET['url'];
} else {
	$destination_url = get_bloginfo('url');
}
if(esc_url($_GET['url'])!=''){
	global $wpdb;
	//Insert to database
	$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_tracking (affiliate_id, destination_url, user_ip, user_agent) values ('" . $affiliate_id . "', '" . $destination_url . "', '" . $user_ip . "', '" . $user_agent . "')";
	$wpdb->query($sql);
	
	//Get affiliate discount fields
	$data = "SELECT 
	        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_discount' AND user_id = '" . $affiliate_id . "') AS affiliate_discount,
	        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_discount_type' AND user_id = '" . $affiliate_id . "') AS affiliate_discount_type,
	        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_discount_amount' AND user_id = '" . $affiliate_id . "') AS affiliate_discount_amount";
	$discount = $wpdb->get_results($data);
	
	//Set Session Variables
	$_SESSION['affiliate_id'] = $affiliate_id;
	if ($discount[0]->affiliate_discount == 'true') {
		$_SESSION['affiliate_discount_type'] = $discount[0]->affiliate_discount_type;
		$_SESSION['affiliate_discount_amount'] = $discount[0]->affiliate_discount_amount;
	}
	
	//Redirect to final destination
	header('Location: ' . $destination_url);
}else{
	print("This URL is not valid.");	
}

?>