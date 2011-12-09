<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

//Tracking Info
$referer 	= $_SERVER['HTTP_REFERER'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$user_ip	= $_SERVER['REMOTE_ADDR'];
$affiliate_id = $_GET['aff_id'];
if (isset($_GET['url'])) {
	$destination_url = $_GET['url'];
} else {
	$destination_url = get_bloginfo('url');
}


//Insert to database
global $wpdb;
$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_tracking (affiliate_id, destination_url, user_ip, user_agent) values ('" . $affiliate_id . "', '" . $destination_url . "', '" . $user_ip . "', '" . $user_agent . "')";
$wpdb->query($sql);

//Set Session Variable
$_SESSION['affiliate_id'] = $affiliate_id;

//Redirect to final destination
header('Location: ' . $destination_url);
?>