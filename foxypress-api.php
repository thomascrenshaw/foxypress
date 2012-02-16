<?php
session_start();
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');	
global $wpdb;		
/* 
	API Required Variables
	- api_key
	- store_domain
	- blog_id (if mulitsite)

	API Actions		
	- quantity_decrease
		 Required Variables
			- product_code
			- product_quantity
	- quantity_set
		 Required Variables
			- product_code
			- product_quantity
*/	
if(isset($_POST['api_key']))
{
	$api_key = $_POST['api_key'];
	$store_domain = $_POST['store_domain'];
	$product_code = $_POST['product_code'];
	$product_quantity = $_POST['product_quantity'];
	$blog_id = $_POST['blog_id'];
	$action = $_POST['action'];
	
	//check api key and store domain
	$real_api_key = get_option('foxycart_apikey');
	$real_store_domain = get_option('foxycart_storeurl');		
	
	//validate
	if($api_key == $real_api_key && $store_domain == $real_store_domain)
	{
		$IsMultiSite = foxypress_IsMultiSite();
		$original_blog = "";
		$error = "";
		//check if we have a blog id when we have a multisite
		if($IsMultiSite && $blog_id == "")
		{
			$error = "blog_id cannot be blank for multi-sites";
		}
		//process if we don't have errors		
		if($error == "")
		{	
			//swith blogs	
			if($IsMultiSite) { $original_blog = $wpdb->blogid; switch_to_blog($blog_id); }
			//process action
			if($action == "quantity_decrease")
			{
				foxypress_API_QuantityDecrease($product_code, $product_quantity, $store_domain, $api_key);
			}
			else if($action == "quantity_set")
			{
				foxypress_API_QuantitySet($product_code, $product_quantity, $store_domain, $api_key);
			}
			//restore blog
			if($IsMultiSite) { switch_to_blog($original_blog); }			
		}
		else
		{
			echo(foxypress_API_GetReturnXML($error));			
		}
	}
	else
	{
		echo(foxypress_API_GetReturnXML("Invalid api_key / store_domain combination"));	
	}
}

function foxypress_API_QuantitySet($product_code, $product_quantity, $store_domain, $api_key)
{
	global $wpdb;	
	$error = "";
	if($product_code == "" || $product_quantity == "")
	{
		$error = "product_code and/or product_quantity cannot be blank";	
	}
	else
	{
		$product = $wpdb->get_row("select post_id, meta_id from " . $wpdb->prefix . "postmeta where meta_key='_code' and meta_value='" . mysql_escape_string($product_code) . "'");
		if(!empty($product))
		{
			// set quantity
			foxypress_save_meta_data($product->post_id, '_quantity', $product_quantity);
		}
		else
		{
			//check option level
			$product = $wpdb->get_row("select option_id, inventory_id, option_quantity from " . $wpdb->prefix . "foxypress_inventory_options where option_code='" . mysql_escape_string($product_code) . "'");
			if(!empty($product))
			{
				$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options set option_quantity = '" . $product_quantity . "' where option_id = '" . $product->option_id . "'");
			}	
			else
			{
				$error = "Invalid product_code";
			}
		}
	}
	echo(foxypress_API_GetReturnXML($error));
}

function foxypress_API_QuantityDecrease($product_code, $product_quantity, $store_domain, $api_key)
{
	global $wpdb;	
	$error = "";
	if($product_code == "" || $product_quantity == "")
	{
		$error = "product_code and/or product_quantity cannot be blank";	
	}
	else
	{
		$product = $wpdb->get_row("select post_id, meta_id from " . $wpdb->prefix . "postmeta where meta_key='_code' and meta_value='" . mysql_escape_string($product_code) . "'");
		if(!empty($product))
		{
			//get quantity
			$quantity = get_post_meta($product->post_id,'_quantity',TRUE);
			$new_quantity = "0";
			if($quantity > $product_quantity)
			{
				$new_quantity = $quantity - $product_quantity;
			}
			// set quantity
			foxypress_save_meta_data($product->post_id, '_quantity', $new_quantity);
		}
		else
		{
			//check option level
			$product = $wpdb->get_row("select option_id, inventory_id, option_quantity from " . $wpdb->prefix . "foxypress_inventory_options where option_code='" . mysql_escape_string($product_code) . "'");
			if(!empty($product))
			{
				//reduce quantity
				$new_quantity = "0";
				if($product->option_quantity > $product_quantity)
				{
					$new_quantity = $product->option_quantity - $product_quantity;
				}
				$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options set option_quantity = '" . $new_quantity . "' where option_id = '" . $product->option_id . "'");
			}	
			else
			{
				$error = "Invalid product_code";
			}
		}
	}
	echo(foxypress_API_GetReturnXML($error));
}

function foxypress_API_GetReturnXML($error)
{
	$return = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
	if($error == "")
	{
		$return .= "<request>
						<status>success</status>
					</request>";
	}
	else
	{
		$return .= "<request>
						<status>error</status>
						<message>$error</message>
					</request>";
	}
	return $return;
}
	
?>