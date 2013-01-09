<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/


$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');	
global $wpdb;	

if (isset($_GET['fcsid']) && isset($_GET['timestamp'])) 
{
	global $current_user;	
	$login_url = get_bloginfo("url") . "/wp-login.php";
	if(is_user_logged_in()) 
	{
		get_currentuserinfo();
		$customer_email = $current_user->user_email;
		$foxycart_customer_id = get_user_meta($current_user->ID, "foxycart_customer_id", TRUE);		
		//if we don't have the foxycart customer id in the wp db, then lets check if it exists and sync up data.
		if (!$foxycart_customer_id)
		{
			 $foxycart_customer_id = foxypress_CheckForFoxyCartUser($customer_email);
		}
		//if we still don't have the foxycart customer id, we need to create
		if (!$foxycart_customer_id) 
		{
			$foxycart_customer_id = foxypress_CreateFoxyCartUser($customer_email, $current_user->user_pass, $current_user->user_firstname, $current_user->user_lastname);
		}
	}
	else
	{
		//Force a Straight Redirect
		header('Location: ' . $login_url . '?redirect_to=' . urlencode(plugins_url() . '/foxypress/foxysso.php?timestamp=' . $_GET['timestamp'] . '&fcsid=' . $_GET['fcsid']) . '&foxycart_checkout=1&reauth=1');
		die;
		
	}	
	$fcsid = $_GET['fcsid'];
	$timestamp = $_GET['timestamp'];
	$newtimestamp = strtotime("+60 minutes", $timestamp);
	$auth_token = sha1($foxycart_customer_id . '|' . $newtimestamp . '|' . get_option('foxycart_apikey'));
	$redirect_complete = 'https://' . get_option('foxycart_storeurl') . '.foxycart.com/checkout?fc_auth_token=' . $auth_token . '&fcsid=' . $fcsid . '&fc_customer_id=' . $foxycart_customer_id . '&timestamp=' . $newtimestamp;
	header('Location: ' . $redirect_complete);
}

?>