<?
	session_start();
	require_once('../../../wp-includes/wp-db.php');	
	require_once('../../../wp-config.php');	
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
									FROM " .  WP_TRANSACTION_TABLE . " as t
									INNER JOIN " . WP_TRANSACTION_STATUS_TABLE . " as s on t.foxy_transaction_status = s.foxy_transaction_status
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
	else if($mode == "deletephoto")
	{
		//delete photo vars
		$session_id = foxypress_FixGetVar('sid');
		$image_id = foxypress_FixGetVar('imageid');
		$inventory_id = foxypress_FixGetVar('inventoryid');		
		if($session_id == session_id())
		{
			if ($image_id != "" && $inventory_id != "") 
			{
				$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
				$query = sprintf('SELECT inventory_image FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);
				$data = $wpdb->get_row($query);			
				if (!empty($data)) {
					//only delete the file if it's not our default image
					if($data->inventory_image != INVENTORY_DEFAULT_IMAGE)
					{
						foxypress_DeleteImage($directory . $data->inventory_image);
					}
					$query = sprintf('DELETE FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);		
					$wpdb->query($query);
				}						
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