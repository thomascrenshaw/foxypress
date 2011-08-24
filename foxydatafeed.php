<?php	

session_start();
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');	
include_once('classes/class.rc4crypt.php');
include_once('classes/class.xmlparser_php5.php');	
global $wpdb;	
$debug = 0;
$output = "";
$xmlkey = get_option('foxycart_apikey');
if (!isset($_POST['FoxyData'])){
	$output .= "Error: no post data \n";
	//exit;
}
else
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
	$data = new XMLParser($FoxyData_decrypted);
	$data->Parse();
	if (is_object($data)){
		$x = 1;
		$QuantityAlertLevel = get_option('foxypress_qty_alert');
		$Downloads = array();
		//get transaction data
		foreach ($data->document->transactions[0]->transaction as $transaction)
		{
			$TransactionID = $transaction->id[0]->tagData;
			$TransactionDate = $transaction->transaction_date[0]->tagData;
			$CustomerID = $transaction->customer_id[0]->tagData;
			$FirstName = $transaction->customer_first_name[0]->tagData;
			$LastName = $transaction->customer_last_name[0]->tagData;
			$Email = $transaction->customer_email[0]->tagData;
			$x++;
		}
		//get order details		
		foreach ($transaction->transaction_details[0]->transaction_detail as $detail)
		{			
			$InventoryID = "";
			$Downloadable = false;
			if ($detail->product_code != '')
			{
				$ProductCode = $detail->product_code[0]->tagData;				
				foreach ($detail->transaction_detail_options[0]->transaction_detail_option as $option) 
				{
					if (strtolower($option->product_option_name[0]->tagData) == 'downloadable') 
					{
						$Downloadable = $option->product_option_value[0]->tagData;
					}
					else if (strtolower($option->product_option_name[0]->tagData) == 'inventory_id') 
					{
						$InventoryID = $option->product_option_value[0]->tagData;
					}
				}
				//if we have a downloadable product, we need to insert into our transactions table(s)
				//and send out appropriate emails					
				if($Downloadable == "true")
				{
					//get downloadid
					$dt_downloadable = $wpdb->get_row("SELECT * FROM " . WP_INVENTORY_DOWNLOADABLES . " WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' and status = '1'");
					//delete transaction if one exists already
					$wpdb->query("DELETE FROM " . WP_DOWNLOADABLE_TRANSACTION . " WHERE foxy_transaction_id='" . mysql_escape_string($TransactionID) . "' AND downloadable_id='" . mysql_escape_string($dt_downloadable->downloadable_id) . "'");
					//insert transaction
					$wpdb->query("INSERT INTO " . WP_DOWNLOADABLE_TRANSACTION . " SET foxy_transaction_id='" . mysql_escape_string($TransactionID) . "', downloadable_id='" . mysql_escape_string($dt_downloadable->downloadable_id) . "', download_count='0'");
					$download_transaction_id = $wpdb->insert_id;
					//generate link & store in array
					$Downloads[] = plugins_url() . "/foxypress/download.php?d=" . urlencode(foxypress_Encrypt($dt_downloadable->downloadable_id)) . "&t=" . urlencode(foxypress_Encrypt($download_transaction_id));					
				}			
				//get quantity and adjust table as needed
				$QuantityPurchased = $detail->product_quantity[0]->tagData;				
				
				//check to see if we have a option level product code
				$dt_OptionLevelCount = $wpdb->get_row("SELECT count(*) as ProductCount FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " where inventory_id='" . mysql_escape_string($InventoryID) . "' and option_code='" . mysql_escape_string($ProductCode) . "'");		
				if(!empty($dt_OptionLevelCount) && $dt_OptionLevelCount->ProductCount == 1)
				{
					//update quantity 
					$wpdb->query("UPDATE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " SET option_quantity = (option_quantity - " . $QuantityPurchased . ") WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' AND option_code='" . mysql_escape_string($ProductCode) . "' AND option_quantity != 'null'");					
					if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0")
					{
						//check new quantity to see if we need to send an email
						$dt_item = $wpdb->get_row("SELECT * FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' AND option_code='" . mysql_escape_string($ProductCode) . "'");						
						if($dt_item->option_quantity != null && $dt_item->option_quantity >= 0 && $dt_item->option_quantity < $QuantityAlertLevel)
						{
							//uh oh!
							$headers = "From: " . get_settings("admin_email") . "\r\n";
							$headers .= "Content-type: text/html\r\n"; 
							$body = $dt_item->option_code . " is running low, " . $dt_item->option_quantity . " remain.  Please check your inventory by logging into your WordPress dashboard.";
							mail(get_settings("admin_email"), get_bloginfo("name") . " - Quantity Alert", $body, $headers);	
						}						
					}	
				}
				else //standard product
				{	
					//update quantity 
					$wpdb->query("UPDATE " . WP_INVENTORY_TABLE . " SET inventory_quantity = (inventory_quantity - " . $QuantityPurchased . ") WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "' AND inventory_quantity != 'null'");
					
					if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0")
					{
						//check new quantity to see if we need to send an email
						$dt_item = $wpdb->get_row("SELECT * FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id = '" . mysql_escape_string($InventoryID) . "'");
						if($dt_item->inventory_quantity != null && $dt_item->inventory_quantity >= 0 && $dt_item->inventory_quantity < $QuantityAlertLevel)
						{
							//uh oh!
							$headers = "From: " . get_settings("admin_email") . "\r\n";
							$headers .= "Content-type: text/html\r\n"; 
							$body = $dt_item->inventory_name . " is running low, " . $dt_item->inventory_quantity . " remain.  Please check your inventory by logging into your WordPress dashboard.";
							mail(get_settings("admin_email"), get_bloginfo("name") . " - Quantity Alert", $body, $headers);	
						}
					}		
				}				
			}
			else
			{
				$output .= "Error: Invalid product $ProductCode $ProductName\n";
				continue;
			}
		}
		
		if(count($Downloads) > 0)
		{				
			$body = "Thank you for shopping in our store! The link(s) to download your product(s) are below. <br /><br />";
			foreach($Downloads as $d)
			{
				$body .= "<a href=\"" . $d . "\" target=\"_blank\">" . $d . "</a> <br /><br />";	
			}
			//email customer
			$headers = "From: " . get_settings("admin_email") . "\r\n";
			$headers .= "Content-type: text/html\r\n"; 
			mail($Email, get_bloginfo("name") . " - Digital Downloads", $body, $headers);			
		}		
	}
	else
	{
		$output .= "Error: not an object - check your key.\n";
	}
}
			
//check for errors
if ($output != '')
{
	//data feed issue
}
else
{
	$output = 'foxy';
}	
print "$output";
exit;
?>
