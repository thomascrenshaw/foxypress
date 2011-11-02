<?php
	session_start();
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');	
	global $wpdb;	
	//global vars
	$mode = foxypress_FixGetVar('m');	
	header('Content-type: application/json'); 	
	if($mode == "tracking")
	{
		//tracking vars
		$lastname = foxypress_FixGetVar('ln');
		$id = foxypress_FixGetVar('id');	
		
		if($id != "" && $lastname != "")
		{
			//search table for tracking number
			$item = $wpdb->get_row("SELECT t.*, s.foxy_transaction_status_description
									FROM " .  $wpdb->prefix . "foxypress_transaction as t
									INNER JOIN " . $wpdb->prefix . "foxypress_transaction_status as s on t.foxy_transaction_status = s.foxy_transaction_status
									WHERE t.foxy_transaction_id = '" . $id . "'
										and LOWER(t.foxy_transaction_last_name) = LOWER('" . $lastname . "')");
			if(!empty($item))
			{
				$name = $item->foxy_transaction_first_name . " " . $item->foxy_transaction_last_name;
				$shipping_address = ($item->foxy_transaction_shipping_address1 == "") ? 
										$item->foxy_transaction_billing_address1 . " " .  $item->foxy_transaction_billing_address2 . "<br/>" .	$item->foxy_transaction_billing_city . ", " . $item->foxy_transaction_billing_state . " " . $item->foxy_transaction_billing_zip . " " . $item->foxy_transaction_billing_country
										:
										$item->foxy_transaction_shipping_address1 . " " .  $item->foxy_transaction_shipping_address2 . "<br/>" . $item->foxy_transaction_shipping_city . ", " . $item->foxy_transaction_shipping_state . " " . $item->foxy_transaction_shipping_zip . " " . $item->foxy_transaction_shipping_country;		
				$status = $item->foxy_transaction_status_description;
				$tracking = $item->foxy_transaction_trackingnumber;
				echo("{\"ajax_status\":\"ok\", \"name\": \"" . $name . "\",\"shipping_address\": \"" . $shipping_address . "\",\"current_status\": \"" . $status . "\",\"tracking_number\": \"" . $tracking . "\"}");
			}
			else
			{
				echo(GetErrorJSON());
			}
		}
		else
		{
			echo(GetErrorJSON());
		}
	}
	else if($mode == "deletedownloadable")
	{
		//we don't want to delete any downloads in case people still are downloading them.
		$session_id = foxypress_FixGetVar('sid');
		$downloadable_id = foxypress_FixGetVar('downloadableid');
		$inventory_id = foxypress_FixGetVar('inventoryid');		
		if($session_id == session_id())
		{
			if ($downloadable_id != "" && $inventory_id != "") 
			{
				$query = "UPDATE ". $wpdb->prefix  . "foxypress_inventory_downloadables SET status = '0' WHERE downloadable_id='" . mysql_escape_string($downloadable_id) . "'";		
				$wpdb->query($query);			
			}
			echo("{\"ajax_status\":\"ok\"}");
		}
		else
		{
			echo(GetErrorJSON());	
		}
	}
	else if($mode == "savemaxdownloads")
	{
		$session_id = foxypress_FixGetVar('sid');
		$downloadable_id = foxypress_FixGetVar('downloadableid');
		$inventory_id = foxypress_FixGetVar('inventoryid');		
		$maxdownloads = foxypress_FixGetVar('maxdownloads');
		if($session_id == session_id())
		{
			if ($downloadable_id != "" && $inventory_id != "") 
			{			
				$query = "UPDATE " . $wpdb->prefix  . "foxypress_inventory_downloadables SET maxdownloads='" . $maxdownloads. "' WHERE downloadable_id='" . mysql_escape_string($downloadable_id) . "'";		
				$wpdb->query($query);		
				
			}
			echo("{\"ajax_status\":\"ok\"}");	
		}
		else
		{
			echo(GetErrorJSON());	
		}
	}
	else if($mode == "resetdownloadcount")
	{
		$session_id = foxypress_FixGetVar('sid');
		$downloadable_id = foxypress_FixGetVar('downloadableid');
		$download_transaction_id = foxypress_FixGetVar('downloadtransactionid');		
		if($session_id == session_id())
		{
			if ($downloadable_id != "" && $download_transaction_id != "") 
			{			
				$query = "UPDATE " . $wpdb->prefix . "foxypress_downloadable_transaction" . " SET download_count='0' WHERE downloadable_id='" . mysql_escape_string($downloadable_id) . "' AND download_transaction_id='" . mysql_escape_string($download_transaction_id) . "'";		
				$wpdb->query($query);						
			}
			echo("{\"ajax_status\":\"ok\"}");	
		}
		else
		{
			echo(GetErrorJSON());	
		}
	}
	else if($mode == "save-image-order")
	{
		$session_id = foxypress_FixGetVar('sid');
		if($session_id == session_id())
		{
			$imageorder = foxypress_FixGetVar('order');
			$images = explode(",", $imageorder);
			$x = 1;
			foreach($images as $image)
			{
				$imageExploded = explode("-", $image);
				$imageid = $imageExploded[1];
				$query = "UPDATE " . $wpdb->prefix . "posts SET menu_order='" . $x . "' WHERE ID='" . mysql_escape_string($imageid) . "'";		
				$wpdb->query($query);
				$x++;
			}
			echo("{\"ajax_status\":\"ok\"}");	
		}
		else
		{
			echo(GetErrorJSON());	
		}
	}
	else
	{
		echo(GetErrorJSON());	
	}
	
	function GetErrorJSON()
	{
		return "{\"ajax_status\":\"error\"}";
	}
	
	exit;
?>