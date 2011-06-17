<?php
	session_start();
	require_once('../../../wp-includes/wp-db.php');	
	require_once('../../../wp-config.php');	
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
		//get data
		$FoxyData_encrypted = urldecode($_POST['FoxyData']);
		$FoxyData_decrypted = rc4crypt::decrypt($xmlkey,$FoxyData_encrypted);
		$data = new XMLParser($FoxyData_decrypted);
		$data->Parse();
		if (is_object($data)){
			$x = 1;
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
						$Downloads[] = get_bloginfo("url") . "/wp-content/plugins/foxypress/download.php?d=" . urlencode(foxypress_Encrypt($dt_downloadable->downloadable_id)) . "&t=" . urlencode(foxypress_Encrypt($download_transaction_id));					
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
