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
	if ( ! defined( 'ABSPATH' ) ){
		die( 'Direct access not permitted.' );
	}
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
	else if($mode == "add_downloadable")
	{
		echo json_encode(AddDigitalDownload());
	}
	else if($mode == "remove_downloadable")
	{
		echo json_encode(RemoveDigitalDownload());
	}
	else if($mode == "update_downloadable")
	{
		echo json_encode(UpdateDigitalDownload());
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
	else if($mode == "update-images")
	{
		$result = array();
		
		$session_id = foxypress_FixGetVar('sid');
		if($session_id == session_id())
		{
			$product_id = intval(foxypress_FixGetVar('product-id'));
			$imageorder = foxypress_FixGetVar('order');
			$images = explode(",", $imageorder);
			
			// Remove all product images associated with this product
			$query = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_images WHERE inventory_id='" . $wpdb->escape($product_id) . "'";		
			$result['delete'] = $wpdb->query($query);
			
			if ($result['delete'] === false) {
				// Delete query had some problems...
				$result['failed_query'] = $query;
			} else {
				// Delete was successful
				
				// Loop through images, adding each to the foxypress_inventory_images table
				$numImages = count($images);
				for ($i = 0; $i < $numImages; $i++) {
					// Make sure this isn't an empty string
					if (strlen($images[$i]) > 0) {
					
						// Get image ID
						$imageExploded = explode("-", $images[$i]);
						$imageid = intval($imageExploded[count($imageExploded) - 1]);
						
						// Insert into database
						$result['insert-' . $i] = $wpdb->insert( 
							$wpdb->prefix . "foxypress_inventory_images", 
							array( 
								'inventory_id' => $product_id, 
								'attached_image_id' => $imageid,
								'image_order' => $i
							), 
							array( 
								'%d', 
								'%d',
								'%d'
							) 
						);
						
						// Return some extra data in the result array if the insert was unsuccessful
						if ($result['insert-' . $i] === false) {
							$result['insert-' . $i . '-error'] = "inventoryId: $product_id; attached_image_id: $imageid; image_order: $i";
						}
					}
				}
			}
			
			// Return result as a JSON object
			echo json_encode($result);
		} else {
			echo(GetErrorJSON());
		}
	}
	else if($mode == "transaction_submit")
	{
		$session_id = foxypress_FixGetVar('sid');
		if($session_id == session_id())
		{

			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
			$TransactionID = foxypress_FixGetVar("transaction_id");
			$NewStatus = foxypress_FixGetVar("status");
			$TrackingNumber = foxypress_FixGetVar("tracking_num");
			$RMANumber = foxypress_FixGetVar("rma_num");
			
				//get transaction details & current status
				$tRow = $wpdb->get_row("select * from " . $wpdb->prefix ."foxypress_transaction where foxy_transaction_id = '$TransactionID'");
				//if it's different check the table to see if we need to send an email
				if($tRow->foxy_transaction_status != $NewStatus)
				{
					$statusEmail = $wpdb->get_row("select * from " . $wpdb->prefix . "foxypress_transaction_status where foxy_transaction_status = '$NewStatus'");
					if($statusEmail->foxy_transaction_status_email_flag == "1")
					{
						if($tRow->foxy_transaction_email != "")
						{
							$EmailBody = stripslashes($statusEmail->foxy_transaction_status_email_body);
							$EmailSubject = $statusEmail->foxy_transaction_status_email_subject;

							$foxyStoreURL = get_option('foxycart_storeurl');
							$foxyAPIKey =  get_option('foxycart_apikey');
							$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
							$foxyData = array();
							$foxyData["api_token"] =  $foxyAPIKey;
							$foxyData["api_action"] = "transaction_get";
							$foxyData["transaction_id"] = $TransactionID;
							$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
							$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
							if($foxyXMLResponse->result == "SUCCESS")
							{
								$shipping_method = $foxyXMLResponse->transaction->shipto_shipping_service_description;
								$shipping_first_name = $foxyXMLResponse->transaction->shipping_first_name;
								$shipping_last_name = $foxyXMLResponse->transaction->shipping_last_name;

								//get discount code and amount
								$discounts = "";
								$d = 1;
								foreach($foxyXMLResponse->transaction->discounts->discount as $discount)
								{
									if ($d == 1) 
									{
										$discounts .= $discount->code . ": " . number_format($discount->amount, 2, '.', ',');
									}
									else
									{
										$discounts .= "<br />" . $discount->code . ": " . number_format($discount->amount, 2, '.', ',');
									}
									$d += 1;
								}

								$product_listing = "<table class='product_listing' width='600'>\r\n<tbody>\r\n<tr>\r\n<td height='20' valign='middle' align='center' bgcolor='#cccccc'>Item</td>\r\n<td height='20' valign='middle' align='center' bgcolor='#cccccc'>Quantity</td>\r\n<td height='20' valign='middle' align='center' bgcolor='#cccccc'>Price</td>\r\n</tr>\r\n";
								$total_product_price = 0;
								foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
								{
									$options = "";
									$Downloadable = false;
									$Inventory_ID = "";
									foreach($td->transaction_detail_options->transaction_detail_option as $opt)
									{
										if(strtolower($opt->product_option_name) == "inventory_id")
										{
											$Inventory_ID = $opt->product_option_value;
										}
										else
										{
											$options .=  $opt->product_option_name . ": " . $opt->product_option_value . "<br>";
										}
									}								
																		
									//check if the item is a downloadable
									$dt_downloadable = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_downloadables WHERE inventory_id = '" . mysql_escape_string($Inventory_ID) . "' and status = '1'");
									if(!empty($dt_downloadable) && count($dt_downloadable) > 0)
									{
										$Downloadable = true;
									}	
											
									//check to see if we need to show downloadable information
									if($Downloadable && $Inventory_ID != "")
									{
										$dt = $wpdb->get_row("SELECT dt.* 
															  FROM " . $wpdb->prefix . "foxypress_inventory_downloadables as d 
															  INNER JOIN " . $wpdb->prefix . "foxypress_downloadable_transaction as dt on dt.downloadable_id = d.downloadable_id
																								and dt.foxy_transaction_id = '" . $foxyXMLResponse->transaction->id . "'
															  WHERE d.inventory_id = '" . mysql_escape_string($Inventory_ID) . "'");
										//generate url
										$DownloadURL = plugins_url() . "/foxypress/download.php?d=" . urlencode(foxypress_Encrypt($dt->downloadable_id)) . "&t=" . urlencode(foxypress_Encrypt($dt->download_transaction_id)) . "&b=" . urlencode(foxypress_Encrypt($BlogID));
										$options .= "<a href=\"" . $DownloadURL . "\">Download</a><br />";	
									}			
									
									$ProductCode = $td->product_code;

									// Check for image custom field
									$third_party_image = "";
									if (get_option('foxypress_third_party_products') == "1" && $td->image != "")
									{
										$third_party_image = $td->image;
									}

									$ProductImage = "";
									if($third_party_image != "")
									{
										$ProductImage = "<img src=\"" . $third_party_image . "\" style=\"width: 100px; \" width=\"100\"/>";
									}
									else if($Inventory_ID != "")
									{						
										$ProductImage = "<img src=\"" . foxypress_GetMainInventoryImage($Inventory_ID) . "\" style=\"width: 100px; \" width=\"100\"/>";					
									}
									$total_product_price = (double)$td->product_price * $td->product_quantity;
									$product_listing .="<tr>\r\n<td valign='top' align='left'>\r\n<table>\r\n<tbody>\r\n<tr>\r\n" .
														"<td valign='top' align='left' width='120'>" . $ProductImage . "</td>\r\n" .
														"<td valign='top' align='left'>Product: " . $td->product_name . "<br />" .
														(($ProductCode != "") ? "Code: " . $ProductCode . "<br />" : "") .
														"Weight: " . $td->product_weight . "<br />" .
														$options .
														"</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n</td>\r\n" .
														"<td valign='top' align='center'>" . $td->product_quantity . "</td>\r\n" .
														"<td valign='top' align='center'>" . foxypress_FormatCurrency($total_product_price) . "</td>\r\n</tr>\r\n";
									$all_products_price =  $all_products_price + $total_product_price;				
								}
								$product_listing .= "</tbody>\r\n</table>\r\n";
							  
								//replace fields
								$EmailSubject = str_replace("{{order_id}}", $tRow->foxy_transaction_id, $EmailSubject);

								$EmailBody = str_replace("{{order_id}}", $tRow->foxy_transaction_id, $EmailBody);
								$EmailBody = str_replace("{{customer_first_name}}", $tRow->foxy_transaction_first_name, $EmailBody);
								$EmailBody = str_replace("{{customer_last_name}}", $tRow->foxy_transaction_last_name, $EmailBody);
								$EmailBody = str_replace("{{customer_email}}", $tRow->foxy_transaction_email, $EmailBody);	
								$EmailBody = str_replace("{{tracking_number}}", $TrackingNumber, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_address1}}", $tRow->foxy_transaction_billing_address1, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_address2}}", $tRow->foxy_transaction_billing_address2, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_city}}", $tRow->foxy_transaction_billing_city, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_state}}", $tRow->foxy_transaction_billing_state, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_zip}}", $tRow->foxy_transaction_billing_zip, $EmailBody);
								$EmailBody = str_replace("{{customer_billing_country}}", $tRow->foxy_transaction_billing_country, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_first_name}}", $shipping_first_name, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_last_name}}", $shipping_last_name, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_address1}}", $tRow->foxy_transaction_shipping_address1, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_address2}}", $tRow->foxy_transaction_shipping_address2, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_city}}", $tRow->foxy_transaction_shipping_city, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_state}}", $tRow->foxy_transaction_shipping_state, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_zip}}", $tRow->foxy_transaction_shipping_zip, $EmailBody);
								$EmailBody = str_replace("{{customer_shipping_country}}", $tRow->foxy_transaction_shipping_country, $EmailBody);
								$EmailBody = str_replace("{{order_date}}", $tRow->foxy_transaction_date, $EmailBody);
								$EmailBody = str_replace("{{product_total}}", foxypress_FormatCurrency($all_products_price), $EmailBody);
								$EmailBody = str_replace("{{tax_total}}", $tRow->foxy_transaction_tax_total, $EmailBody);
								$EmailBody = str_replace("{{shipping_total}}", $tRow->foxy_transaction_shipping_total, $EmailBody);
								$EmailBody = str_replace("{{shipping_method}}", $shipping_method, $EmailBody);
								$EmailBody = str_replace("{{order_total}}", $tRow->foxy_transaction_order_total, $EmailBody);
								$EmailBody = str_replace("{{cc_type}}", $tRow->foxy_transaction_cc_type, $EmailBody);
								$EmailBody = str_replace("{{product_listing}}", $product_listing, $EmailBody);
								$EmailBody = str_replace("{{discount_codes}}", str_replace('-', '-$', $discounts), $EmailBody);

								
								if(preg_match_all("/{{custom_field_.*?}}/", $EmailBody, $matches))
								{
									foreach($matches[0] as $match=>$custom_tag)
									{			
										$EmailBody = str_replace($custom_tag, foxypress_FixPostVar("foxy_om_" . $custom_tag), $EmailBody);
									}
								}	
								
								//check if user decided to fill out SMTP form
								foxypress_Mail($tRow->foxy_transaction_email, $EmailSubject, $EmailBody);

							}//end check for success
							else
							{
								echo "{\"ajax_status\":\"FoxyCart Connection Error. Email was not sent.\"}";
							}
						}
					}
				}
				//save transaction status
				$sql = "update " . $wpdb->prefix ."foxypress_transaction SET foxy_transaction_status = '$NewStatus', foxy_transaction_trackingnumber = '$TrackingNumber', foxy_transaction_rmanumber = '$RMANumber' WHERE foxy_transaction_id = '$TransactionID'";
				$wpdb->query($sql);

				if($switched_blog) { restore_current_blog(); }

				echo "{\"ajax_status\":\"ok\"}";
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
	
	/**
	 * Adds given attachment ID as a digital download for the 
	 * specified product
	 * 
	 * @since 0.4.3.4
	 * 
	 * @return array Response object as an array
	 */
	function AddDigitalDownload() {
		// Create result array for the AJAX response object
		$result = array();
		
		$session_id = foxypress_FixGetVar('sid');
		// Verify session ID matches
		if($session_id != session_id())
		{
			$result['ajax_status'] = "error";
			$result['error_text'] = "Session id does not match";	
			return $result;
		}
		
		$post_id = foxypress_FixGetVar('pid');
		// Verify Post ID is an int
		if (!is_numeric($post_id)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Invalid post id";	
			return $result;
		}
		// Verify Post ID refers to a foxypress product
		if (get_post_type($post_id) != "foxypress_product") {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Post id does not refer to a foxypress product";	
			return $result;
		}
		
		$attachment_id = foxypress_FixGetVar('aid');	
		// Verify Attachment ID is an int
		if (!is_numeric($attachment_id)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Invalid attachment id";	
			return $result;
		}
		// Verify Post ID refers to a foxypress product
		if (get_post_type($attachment_id) != "attachment") {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Attachment id does not refer to an attachment";	
			return $result;
		}
		
		$attachment_url = wp_get_attachment_url($attachment_id);
		// Check allowed file types
		$attachment_extension = foxypress_ParseFileExtension($attachment_url);
		$allowedFileTypes = array('jpg','jpeg','gif','png','zip');
		if (!in_array($attachment_extension,$allowedFileTypes)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Attachment type '$attachment_extension' not allowed. Digital downloads file types limited to " . implode(", ", $allowedFileTypes);
			return $result;
		}
		
		// Generate new filename
		$target_path = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR;
		$attachment_prefix = get_the_title($attachment_id);
		$attachment_prefix = str_replace(" ", "_", $attachment_prefix);
		$attachment_prefix .= "_";
		$attachment_new_filename = foxypress_GenerateNewFileName($attachment_extension, $post_id, $target_path, $attachment_prefix);
		$attachment_new_url = $target_path . $attachment_new_filename;
		
		// Copy attachment to new location
		$copy_result = copy($attachment_url, $attachment_new_url);
		if (!$copy_result) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Unable to copy attachment to download location";	
			return $result;
		}
		
		$max_downloads = 5;
		global $wpdb;
		$insert_result = $wpdb->insert( 
			$wpdb->prefix . "foxypress_inventory_downloadables", 
			array( 
				'inventory_id' => $post_id, 
				'filename' => mysql_escape_string($attachment_new_filename),
				'maxdownloads' => $max_downloads,
				'status' => 1
			), 
			array( 
				'%d',
				'%s',
				'%d',
				'%d'
			) 
		);
		$downloadable_id = $wpdb->insert_id;
		
		// Check to make sure insert didn't return false
		if (!$insert_result) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Unable to add attachment to digital download database table";	
			return $result;
		}
		
		$result['ajax_status'] = "ok";
		$result['filename'] = $attachment_new_filename;
		$result['maxdownloads'] = $max_downloads;
		$result['downloadable_id'] = $downloadable_id;
		return $result;
	}
	
	/**
	 * Removes digital download from the specified product 
	 * 
	 * @since 0.4.3.4
	 * 
	 * @return array Response object as an array
	 */
	function RemoveDigitalDownload() {
		// Create result array for the AJAX response object
		$result = array();
		
		$session_id = foxypress_FixGetVar('sid');
		// Verify session ID matches
		if($session_id != session_id())
		{
			$result['ajax_status'] = "error";
			$result['error_text'] = "Session id does not match";	
			return $result;
		}
		
		$downloadable_id = foxypress_FixGetVar('did');
		// Verify download ID is an int
		if (!is_numeric($downloadable_id)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Invalid download id";	
			return $result;
		}
		
		global $wpdb;
		$rows_updated = $wpdb->update( 
			$wpdb->prefix . 'foxypress_inventory_downloadables', 
			array( 'status' => 0 ), 
			array( 'downloadable_id' => $downloadable_id ), 
			array( '%d' ), 
			array( '%d' ) 
		);
		
		// Make sure that there were some rows updated
		if ($rows_updated == false) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Unable to remove downloadable";	
			return $result;
		}
		
		$result['ajax_status'] = "ok";
		$result['rows_updated'] = $rows_updated;
		return $result;
	}
	
	/**
	 * Updates max downloads for the specified product 
	 * 
	 * @since 0.4.3.4
	 * 
	 * @return array Response object as an array
	 */
	function UpdateDigitalDownload() {
		// Create result array for the AJAX response object
		$result = array();
		
		$session_id = foxypress_FixGetVar('sid');
		// Verify session ID matches
		if($session_id != session_id())
		{
			$result['ajax_status'] = "error";
			$result['error_text'] = "Session id does not match";	
			return $result;
		}
		
		$downloadable_id = foxypress_FixGetVar('did');
		// Verify download ID is an int
		if (!is_numeric($downloadable_id)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Invalid download id";	
			return $result;
		}
		
		$max_downloads = foxypress_FixGetVar('maxdl');
		// Verify download ID is an int
		if (!is_numeric($max_downloads)) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Max downloads must be numeric";	
			return $result;
		}
		
		global $wpdb;
		$rows_updated = $wpdb->update( 
			$wpdb->prefix . 'foxypress_inventory_downloadables', 
			array( 'maxdownloads' => $max_downloads ), 
			array( 'downloadable_id' => $downloadable_id ), 
			array( '%d' ), 
			array( '%d' ) 
		);
		
		// Make sure that there were some rows updated
		if ($rows_updated == false) {
			$result['ajax_status'] = "error";
			$result['error_text'] = "Unable to update downloadable";	
			return $result;
		}
		
		$result['ajax_status'] = "ok";
		$result['rows_updated'] = $rows_updated;
		return $result;
	}
	
	exit;
?>