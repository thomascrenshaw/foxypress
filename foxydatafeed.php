<?php	
session_start();
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');	
include_once('classes/class.rc4crypt.php');
include_once('classes/class.xmlparser_php5.php');	
global $wpdb, $post;	
$debug = 0;
$output = "";
$xmlkey = get_option('foxycart_apikey');
if (!isset($_POST['FoxyData']) && !isset($_POST['FoxySubscriptionData']))
{
	$output .= "Error: no post data \n";
}
else
{
	if(isset($_POST['FoxyData']))
	{
		//push the feed to any additional feeds specified
		$foxycart_datafeeds = get_option('foxycart_datafeeds');
		$foxy_response = "foxy";
		if($foxycart_datafeeds != "")
		{
			$responses = array();
			$urls = explode(",", $foxycart_datafeeds);
			foreach($urls as $url) 
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array("FoxyData" => $_POST["FoxyData"]));
				$result = curl_exec($ch);
				curl_close($ch);	
				if($result != "foxy") 
				{
					$foxy_response = FALSE;
				}
				$responses[$url] = $result;
				$result = "";
			}	
		}	
		if($foxy_response === FALSE) 
		{
			foreach($responses as $k => $v) 
			{
				$output .= $k . " responded " . $v . "\n";
			}
		}	
		
		//get data
		$FoxyData_encrypted = urldecode($_POST['FoxyData']);
		$FoxyData_decrypted = rc4crypt::decrypt($xmlkey,$FoxyData_encrypted);		
		$foxyXMLResponse = simplexml_load_string($FoxyData_decrypted, NULL, LIBXML_NOCDATA);
		if (!empty($foxyXMLResponse)){
			$x = 1;
			$QuantityAlertLevel = get_option('foxypress_qty_alert');
			$Downloads = array();
			$BlogID = ""; // if this remains empty then we don't have a multi-site to deal with.
			//get transaction data
			foreach ($foxyXMLResponse->transactions->transaction as $transaction)
			{
				$TransactionID = $transaction->id;
				$TransactionDate = $transaction->transaction_date;
				$CustomerID = $transaction->customer_id;
				$FirstName = $transaction->customer_first_name;
				$LastName = $transaction->customer_last_name;
				$Email = $transaction->customer_email;
				$x++;
			}
			//check for multi-site
			foreach ($transaction->custom_fields->custom_field as $customfield)
			{	
				if($BlogID == "" && strtolower($customfield->custom_field_name) == "blog_id")
				{
					$BlogID = $customfield->custom_field_value;
				}
			}
			
			//if we have a mult-site, we need to switch to the correct blog
			if($BlogID != "" && $BlogID != "0")
			{
				switch_to_blog($BlogID);
			}
			
			//get order details		
			foreach ($transaction->transaction_details->transaction_detail as $detail)
			{			
				$InventoryID = "";
				$SubTokenURL = "";
				$ProductCode = "";
				$Downloadable = false;
			
				$ProductCode = $detail->product_code;			
				$SubTokenURL = $detail->sub_token_url;	
				
				//get inventory id
				foreach ($detail->transaction_detail_options->transaction_detail_option as $option) 
				{
					if (strtolower($option->product_option_name) == 'inventory_id') 
					{
						$InventoryID = $option->product_option_value;
					}
				}	
				
				if ($InventoryID != '')
				{										
					//check if the item is a downloadable
					$dt_downloadable = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_downloadables WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' and status = '1'");
					if(!empty($dt_downloadable) && count($dt_downloadable) > 0)
					{
						$Downloadable = true;
					}	
									
					//if we have a downloadable product, we need to insert into our transactions table(s)
					//and send out appropriate emails					
					if($Downloadable)
					{
						//delete transaction if one exists already
						$wpdb->query("DELETE FROM " . $wpdb->prefix . "foxypress_downloadable_transaction WHERE foxy_transaction_id='" . mysql_escape_string($TransactionID) . "' AND downloadable_id='" . mysql_escape_string($dt_downloadable->downloadable_id) . "'");
						//insert transaction
						$wpdb->query("INSERT INTO " . $wpdb->prefix . "foxypress_downloadable_transaction SET foxy_transaction_id='" . mysql_escape_string($TransactionID) . "', downloadable_id='" . mysql_escape_string($dt_downloadable->downloadable_id) . "', download_count='0'");
						$download_transaction_id = $wpdb->insert_id;
						//generate link & store in array
						$Downloads[] = plugins_url() . "/foxypress/download.php?d=" . urlencode(foxypress_Encrypt($dt_downloadable->downloadable_id)) . "&t=" . urlencode(foxypress_Encrypt($download_transaction_id)) . "&b=" . urlencode(foxypress_Encrypt($wpdb->blogid));					
					}			
					
					//get quantity and adjust table as needed
					$QuantityPurchased = $detail->product_quantity;				
					
					//check to see if we have a option level product code
					$dt_OptionLevelCount = $wpdb->get_row("SELECT count(*) as ProductCount FROM " . $wpdb->prefix . "foxypress_inventory_options where inventory_id='" . mysql_escape_string($InventoryID) . "' and option_code='" . mysql_escape_string($ProductCode) . "'");		
					if(!empty($dt_OptionLevelCount) && $dt_OptionLevelCount->ProductCount == 1)
					{
						//update quantity 
						$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_options SET option_quantity = (option_quantity - " . $QuantityPurchased . ") WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' AND option_code='" . mysql_escape_string($ProductCode) . "' AND option_quantity != 'null'");					
						if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0")
						{
							//check new quantity to see if we need to send an email
							$dt_item = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_options WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' AND option_code='" . mysql_escape_string($ProductCode) . "'");						
							if($dt_item->option_quantity != null && $dt_item->option_quantity >= 0 && $dt_item->option_quantity < $QuantityAlertLevel)
							{
								//uh oh!
								$body = $dt_item->option_code . " is running low, " . $dt_item->option_quantity . " remain.  Please check your inventory by logging into your WordPress dashboard.";
								foxypress_Mail($tRow->foxy_transaction_email, get_bloginfo("name") . " - Quantity Alert", $body);								
							}						
						}	
					}
					else //standard product
					{	
						//update quantity 
						$wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = (meta_value - " . $QuantityPurchased . ") WHERE post_id = '" . mysql_escape_string($InventoryID) . "' AND meta_key = '_quantity' AND meta_value != 'null'");
						
						if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0")
						{
							//check new quantity to see if we need to send an email
							$inventory_quantity = get_post_meta($InventoryID,'_quantity',TRUE);
							$inventory_name = get_post($InventoryID)->post_title;
							if($inventory_quantity != null && $inventory_quantity >= 0 && $inventory_quantity < $QuantityAlertLevel)
							{
								//uh oh!
								$body = $inventory_name . " is running low, " . $inventory_quantity . " remain.  Please check your inventory by logging into your WordPress dashboard.";
								foxypress_Mail(get_settings("admin_email"), get_bloginfo("name") . " - Quantity Alert", $body);
							}
						}		
					}		
					
					//subscription logic, add active subscription to our wp user
					if ($SubTokenURL != "") 
					{
						$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'foxycart_customer_id' AND meta_value = '$CustomerID'");
						if ($user_id) 
						{		
							$foxypress_foxycart_subscriptions = unserialize(get_user_meta($user_id, 'foxypress_foxycart_subscriptions', true));
							if (!is_array($foxypress_foxycart_subscriptions)) $foxypress_foxycart_subscriptions = array();		
							$foxypress_foxycart_subscriptions[$InventoryID] = array(
								"is_active" => 1,
								"sub_token_url" => $SubTokenURL
							);		
							update_user_meta($user_id, 'foxypress_foxycart_subscriptions', serialize($foxypress_foxycart_subscriptions));
						}		
					}		
				}
				else
				{
					$output .= "Error: Invalid product\n";
					continue;
				}
			} //end order details foreach 
			
			//email downloads
			if(count($Downloads) > 0)
			{				
				$body = "Thank you for shopping in our store! The link(s) to download your product(s) are below. <br /><br />";
				foreach($Downloads as $d)
				{
					$body .= "<a href=\"" . $d . "\" target=\"_blank\">" . $d . "</a> <br /><br />";	
				}
				//email customer
				foxypress_Mail($Email, get_bloginfo("name") . " - Digital Downloads", $body);	
			}		

			//if we have a mult-site, we need to restore the blog
			if($BlogID != ""  && $BlogID != "0") {	restore_current_blog();	}
		}
		else
		{
			$output .= "Error: not an object - check your key.\n";
		}
	} //end normal data feed
	else if (isset($_POST['FoxySubscriptionData']))
	{
		//subscriptions
		$FoxyData_encrypted = urldecode($_POST['FoxySubscriptionData']);
		$FoxyData_decrypted = rc4crypt::decrypt($xmlkey,$FoxyData_encrypted);
		$foxyXMLResponse = simplexml_load_string($FoxyData_decrypted, NULL, LIBXML_NOCDATA);
		if (!empty($foxyXMLResponse))
		{
			$failedDaysBeforeCancel = 7;
			foreach ($foxyXMLResponse->subscriptions->subscription as $subscription)
			{
				$customer_id = $subscription->customer_id;
				$sub_token_url = (string)$subscription->sub_token_url;
				$past_due_amount = $subscription->past_due_amount;
				$end_date =  $subscription->end_date;
				$transaction_date = $subscription->transaction_date;

				foreach ($subscription->transaction_details->transaction_detail as $detail)
				{	
					$product_code = $detail->product_code;						
					foreach ($detail->transaction_detail_options->transaction_detail_option as $option) 
					{
						if (strtolower($option->product_option_name) == 'inventory_id') 
						{
							$inventory_id = $option->product_option_value;
						}
					}					
				}		

				$canceled = 0;
				if (date("Y-m-d",strtotime("now")) == date("Y-m-d", strtotime($end_date))) 
				{
					$canceled = 1;
				}
				if (!$canceled && $past_due_amount > 0) 
				{
					$failedDays = floor((strtotime("now") - strtotime($transaction_date)) / (60 * 60 * 24));
					if ($failedDays > $failedDaysBeforeCancel) 
					{
						$canceled = 1;
					} 
				}		
				if ($canceled) 
				{		
					$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'foxycart_customer_id' AND meta_value = '$customer_id'");
					if ($user_id) 
					{		
						//Get User's Subscription Array
						$foxypress_foxycart_subscriptions = unserialize(get_user_meta($user_id, 'foxypress_foxycart_subscriptions', true));
						if (!is_array($foxypress_foxycart_subscriptions)) $foxypress_foxycart_subscriptions = array();		
						$foxypress_foxycart_subscriptions[$inventory_id] = array(
							"is_active" => 0,
							"sub_token_url" => $sub_token_url
						);		
						//Write Serialized Array Back to DB
						update_user_meta($user_id, 'foxypress_foxycart_subscriptions', serialize($foxypress_foxycart_subscriptions));
					 }		
				 }

	 		}//foreach sub
		}
		else
		{
			$output .= "Error: not an object (subscriptions) - check your key.\n";
		}
	}
}
			
//check for errors
if ($output != '')
{
	//data feed issue
}
else
{
	if(isset($_POST['FoxySubscriptionData']))
	{
		$output = 'foxysub';
	}
	else
	{
		$output = 'foxy';
	}
}	
print "$output";
exit;
?>
