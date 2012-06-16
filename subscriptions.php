<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'foxypress_subscriptions_postback');

function foxypress_subscriptions_postback()
{
	global $wpdb;	
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "subscriptions")
	{
		if(isset($_POST['foxypress_btnSubscriptionSave']))
		{
			$sub_token = foxypress_FixGetVar('sub_token');
			$start_date = foxypress_FixPostVar('foxypress_sub_startdate');
			$next_transaction_date = foxypress_FixPostVar('foxypress_sub_nextdate');
			$end_date = foxypress_FixPostVar('foxypress_sub_enddate');
			$frequency = foxypress_FixPostVar('foxypress_sub_frequency');
			$past_due_amount = foxypress_FixPostVar('foxypress_sub_pastdue');
			$is_active = foxypress_FixPostVar('foxypress_sub_active');
			
			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "subscription_modify";
			$foxyData["sub_token"] = $sub_token;		
			$foxyData["start_date"] = $start_date;
			$foxyData["next_transaction_date"] = $next_transaction_date;
			$foxyData["end_date"] = $end_date;
			$foxyData["frequency"] = $frequency;
			$foxyData["past_due_amount"] = $past_due_amount;
			$foxyData["is_active"] = $is_active;
			$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
			$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);		
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=subscriptions&sub_token=" . $sub_token);
		}
	}
}

function foxypress_subscriptions_page_load()
{	
	global $wpdb;
	$sub_token = foxypress_FixGetVar('sub_token');
	if(!empty($sub_token))
	{
		$blog_id = foxypress_FixGetVar('b');
		if(foxypress_IsMultiSite() && ($blog_id != $wpdb->blogid && !foxypress_IsMainBlog()))
		{
			foxypress_subscriptions_list();			
		}
		else
		{
			foxypress_subscriptions_edit($sub_token);
		}
	}
	else
	{
		foxypress_subscriptions_list();
	}
}

function foxypress_subscriptions_edit($sub_token)
{
?>
	<link rel="stylesheet" href="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/css/smoothness/jquery-ui-1.8.11.custom.css"> 
    <script type="text/javascript" src="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/js/jquery-ui-1.8.11.custom.min.js"></script>
    <script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
		  //calendars		  
			jQuery("#foxypress_sub_startdate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#foxypress_sub_nextdate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#foxypress_sub_enddate").datepicker({ dateFormat: 'yy-mm-dd' });
		});	
	</script>
<?php
	$output = "";
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "subscription_get";
	$foxyData["sub_token"] = $sub_token;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	if($foxyXMLResponse->result == "SUCCESS")
	{
		$sub_token_url = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/cart?sub_token=" . $foxyXMLResponse->subscription->sub_token;		
		$output = "<div style=\"padding: 10px;\">
					<i>" . __('For help with subscriptions visit FoxyCart\'s', 'foxypress') . "<a href=\"http://wiki.foxycart.com/v/0.7.1/products/subscriptions\" target=\"_blank\">" . __('documentation', 'foxypress') . "</a> " . __('or', 'foxypress') . "<a href=\"http://wiki.foxycart.com/v/0.7.1/cheat_sheet#subscription_product_options\" target=\"_blank\">" . __('cheat sheet', 'foxypress') . "</a></i>
					</div>
					<form method=\"post\">
						<div class=\"postbox\">
							<table style=\"padding:5px;\">
								<tr>
									<td>Sub_Token</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->sub_token . "\" name=\"foxypress_sub_subtoken\" id=\"foxypress_sub_subtoken\" size=\"100\" readonly /></td>
								</tr>
								<tr>
									<td>" . __('Start Date', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->start_date . "\" name=\"foxypress_sub_startdate\" id=\"foxypress_sub_startdate\" /> <small><i>(yyyy-mm-dd format. ex: " . date("Y-m-d") . ")</i></small></td>
								</tr>
								<tr>
									<td>" . __('Next Date', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->next_transaction_date . "\" name=\"foxypress_sub_nextdate\" id=\"foxypress_sub_nextdate\" /> <small><i>(yyyy-mm-dd format. ex: " . date("Y-m-d") . ")</i></small></td>
								</tr>
								<tr>
									<td>" . __('End Date', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->end_date . "\" name=\"foxypress_sub_enddate\" id=\"foxypress_sub_enddate\" /> <small><i>(yyyy-mm-dd format. ex: " . date("Y-m-d") . ")</i></small></td>
								</tr>
								<tr>
									<td>" . __('Frequency', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->frequency . "\" name=\"foxypress_sub_frequency\" id=\"foxypress_sub_frequency\" /></td>
								</tr>
								<tr>
									<td>" . __('Past Due Amount', 'foxypress') . " &nbsp; &nbsp;</td>
									<td><input type=\"text\" value=\"" . $foxyXMLResponse->subscription->past_due_amount . "\" name=\"foxypress_sub_pastdue\" id=\"foxypress_sub_pastdue\" /></td>
								</tr>
								<tr>
									<td>" . __('Active', 'foxypress') . "</td>
									<td>
										<select name=\"foxypress_sub_active\" id=\"foxypress_sub_active\">
											<option value=\"1\" " . (($foxyXMLResponse->subscription->is_active == "1") ? "selected=\"selected\"" : "") . ">" . __('Yes', 'foxypress') . "</option>
											<option value=\"0\" " . (($foxyXMLResponse->subscription->is_active == "0") ? "selected=\"selected\"" : "") . ">" . __('No', 'foxypress') . "</option>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan=\"2\"><a href=\"" . $sub_token_url . "&sub_cancel=true&cart=checkout\">" . __('Cancel Subscription', 'foxypress') . "</a> &nbsp; <a href=\"" . $sub_token_url . "&cart=checkout\">" . __('Modify Subscription', 'foxypress') . "</a></td>
								</tr>
						   </table>
					   </div>
					   <input type=\"submit\" value=\"" . __('Save Changes', 'foxypress') . "\" name=\"foxypress_btnSubscriptionSave\" id=\"foxypress_btnSubscriptionSave\" class=\"button-primary\" />
  				</form>";
	}
	else
	{
		$output = __('Invalid Subscription', 'foxypress');
	}
	
	echo("<div class=\"wrap\">
	    	<h2>" . __('Subscriptions', 'foxypress') . "</h2>	
			<div>$output</div>
		  </div>");
}	

function foxypress_subscriptions_list()
{
	global $wpdb;
?>
	<link rel="stylesheet" href="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/css/smoothness/jquery-ui-1.8.11.custom.css"> 
    <script type="text/javascript" src="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/js/jquery-ui-1.8.11.custom.min.js"></script>
    <script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
		  //calendars		  
			jQuery("#start_date_filter_begin").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#start_date_filter_end").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#next_transaction_date_filter_begin").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#next_transaction_date_filter_end").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#end_date_filter_begin").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#end_date_filter_end").datepicker({ dateFormat: 'yy-mm-dd' });
		});	
	</script>
<?php
	$output = "";
	$FoxyCart_Version = get_option('foxycart_storeversion');
	$PageStart = foxypress_FixGetVar("fp_pn");
	if($PageStart == "")
	{
		$PageStart = foxypress_GetPaginationStart();	
	}
	$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  get_option('foxycart_apikey');
	$foxyData["api_action"] = "subscription_list";
	$foxyData["pagination_start"] = $PageStart;
	$foxyData["entries_per_page"] = "30";	
	if(foxypress_IsMultiSite() && !foxypress_IsMainBlog())
	{
		$foxyData["custom_field_name_filter"] = "blog_id";		
		$foxyData["custom_field_value_filter"] = $wpdb->blogid;
	}
	if(isset($_POST['foxypress_btnFilterSubscriptions']))
	{
		//add filters		
		$foxyData["is_active_filter"] = foxypress_FixPostVar("is_active_filter");
		$foxyData["frequency_filter"] = foxypress_FixPostVar("frequency_filter");
		$foxyData["past_due_amount_filter"] = foxypress_FixPostVar("past_due_amount_filter");
		$foxyData["start_date_filter_begin"] = foxypress_FixPostVar("start_date_filter_begin");
		$foxyData["start_date_filter_end"] = foxypress_FixPostVar("start_date_filter_end");
		$foxyData["next_transaction_date_filter_begin"] = foxypress_FixPostVar("next_transaction_date_filter_begin");
		$foxyData["next_transaction_date_filter_end"] = foxypress_FixPostVar("next_transaction_date_filter_end");
		$foxyData["end_date_filter_begin"] = foxypress_FixPostVar("end_date_filter_begin");
		$foxyData["end_date_filter_end"] = foxypress_FixPostVar("end_date_filter_end");
		$foxyData["third_party_id_filter"] = foxypress_FixPostVar("third_party_id_filter");		
		$foxyData["last_transaction_id_filter"] = foxypress_FixPostVar("last_transaction_id_filter");
		$foxyData["customer_id_filter"] = foxypress_FixPostVar("customer_id_filter");
		$foxyData["customer_email_filter"] = foxypress_FixPostVar("customer_email_filter");
		$foxyData["customer_first_name_filter"] = foxypress_FixPostVar("customer_first_name_filter");
		$foxyData["customer_last_name_filter"] = foxypress_FixPostVar("customer_last_name_filter");
		$foxyData["product_code_filter"] = foxypress_FixPostVar("product_code_filter");
		$foxyData["product_name_filter"] = foxypress_FixPostVar("product_name_filter");
		$foxyData["product_option_name_filter"] = foxypress_FixPostVar("product_option_name_filter");
		$foxyData["product_option_value_filter"] = foxypress_FixPostVar("product_option_value_filter");		
	}
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);	
	$output = "<form method=\"post\">
					<div class=\"postbox\">
						<table style=\"padding: 10px;\">
							<tr>
								<td>" . __('Active', 'foxypress') . "</td>
								<td>
									<select name=\"is_active_filter\" id=\"is_active_filter\">
										<option value=\"1\" " . (($foxyData["is_active_filter"] == "1") ? "selected=\"selected\"" : "" ) . ">" . __('Active', 'foxypress') . "</option>
										<option value=\"0\" " . (($foxyData["is_active_filter"] == "0") ? "selected=\"selected\"" : "" ) . ">" . __('Inactive', 'foxypress') . "</option>
										<option value=\"\" " . (($foxyData["is_active_filter"] == "") ? "selected=\"selected\"" : "" ) . ">" . __('Both', 'foxypress') . "</option>
									</select>
								</td>
								<td>" . __('Past Due Status', 'foxypress') . "</td>
								<td>
									<select name=\"past_due_amount_filter\" id=\"past_due_amount_filter\">
										<option value=\"\" " . (($foxyData["past_due_amount_filter"] == "") ? "selected=\"selected\"" : "" ) . ">" . __('Show All', 'foxypress') . "</option>
										<option value=\"1\" " . (($foxyData["past_due_amount_filter"] == "1") ? "selected=\"selected\"" : "" ) . ">" . __('Show Past Due Only', 'foxypress') . "</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>" . __('Start Date', 'foxypress') . "</td>
								<td>
									<input type=\"text\" name=\"start_date_filter_begin\" id=\"start_date_filter_begin\" value=\"" . $foxyData["start_date_filter_begin"] . "\" /> to
									<input type=\"text\" name=\"start_date_filter_end\" id=\"start_date_filter_end\" value=\"" . $foxyData["start_date_filter_end"] . "\" />
								</td>							
								<td>" . __('Third Party ID', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"third_party_id_filter\" id=\"third_party_id_filter\" value=\"" . $foxyData["third_party_id_filter"] . "\" /></td>
							</tr>
							<tr>
								<td>" . __('Next Transaction Date', 'foxypress') . "</td>
								<td>
									<input type=\"text\" name=\"next_transaction_date_filter_begin\" id=\"next_transaction_date_filter_begin\" value=\"" . $foxyData["next_transaction_date_filter_begin"] . "\" /> to
									<input type=\"text\" name=\"next_transaction_date_filter_end\" id=\"next_transaction_date_filter_end\" value=\"" . $foxyData["next_transaction_date_filter_end"] . "\" /> &nbsp; &nbsp;
								</td>							
								<td>" . __('Customer ID', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"customer_id_filter\" id=\"customer_id_filter\" value=\"" . $foxyData["customer_id_filter"] . "\" /></td>
							</tr>
							<tr>
								<td>" . __('End Date', 'foxypress') . "</td>
								<td>
									<input type=\"text\" name=\"end_date_filter_begin\" id=\"end_date_filter_begin\" value=\"" . $foxyData["end_date_filter_begin"] . "\" /> to
									<input type=\"text\" name=\"end_date_filter_end\" id=\"end_date_filter_end\" value=\"" . $foxyData["end_date_filter_end"] . "\" />
								</td>							
								<td>" . __('Customer Email', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"customer_email_filter\" id=\"customer_email_filter\" value=\"" . $foxyData["customer_email_filter"] . "\" /></td>
							</tr>
							<tr>
								<td>" . __('Product Code', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"product_code_filter\" id=\"product_code_filter\" value=\"" . $foxyData["product_code_filter"] . "\" /></td>
								<td>" . __('Customer First Name', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"customer_first_name_filter\" id=\"customer_first_name_filter\" value=\"" . $foxyData["customer_first_name_filter"] . "\" /></td>
							</tr>						
							
							<tr>
								<td>" . __('Product Name', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"product_name_filter\" id=\"product_name_filter\" value=\"" . $foxyData["product_name_filter"] . "\" /></td>
								<td>" . __('Customer Last Name', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"customer_last_name_filter\" id=\"customer_last_name_filter\" value=\"" . $foxyData["customer_last_name_filter"] . "\" /></td>
							</tr>
							<tr>							
								<td>" . __('Product Option Name', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"product_option_name_filter\" id=\"product_option_name_filter\" value=\"" . $foxyData["product_option_name_filter"] . "\" /></td>
								<td>" . __('Last Transaction ID', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"last_transaction_id_filter\" id=\"last_transaction_id_filter\" value=\"" . $foxyData["last_transaction_id_filter"] . "\" /></td>
							</tr>
							<tr>
								<td>" . __('Product Option Value', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"product_option_value_filter\" id=\"product_option_value_filter\" value=\"" . $foxyData["product_option_value_filter"] . "\" /></td>
								<td>" . __('Frequency', 'foxypress') . "</td>
								<td><input type=\"text\" name=\"frequency_filter\" id=\"frequency_filter\" value=\"" . $foxyData["frequency_filter"] . "\" /></td>							
							</tr>
					   </table>
					</div>
				   <input type=\"submit\" id=\"foxypress_btnFilterSubscriptions\" name=\"foxypress_btnFilterSubscriptions\" class=\"button-primary\" value=\"Filter Subscriptions\" />
				   </form><br /><br />
				<table class=\"widefat page fixed\" cellpadding=\"3\" cellspacing=\"3\" style=\"clear: both; width: 100%; margin-bottom: 15px;\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Customer', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Start Date', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Next Date', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('End Date', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Past Due', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Frequency', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Price', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Edit', 'foxypress') . "</th>
						</tr>
					</thead>
					<tbody>";
	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->subscriptions->subscription as $subscription) 
		{
			$sub_token = $subscription->sub_token;
			$customer_id = (string)$subscription->customer_id;
			$customer_first_name = $subscription->customer_first_name;
			$customer_last_name = $subscription->customer_last_name;
			$is_active = $subscription->is_active;
			if ($customer_first_name != "") 
			{
				$customer_name = $customer_last_name . ', ' . $customer_first_name;
			} 
			else 
			{
				$customer_name = $customer_id;
			}			
			foreach($subscription->transaction_template->transaction_details->transaction_detail as $transaction_detail) 
			{
				$product_price = foxypress_FormatCurrency($transaction_detail->product_price);
			}			
			$blogqs = "";
			if(foxypress_IsMultiSite())
			{
				if(foxypress_IsMainBlog())
				{
					$temp_blog_id = "";
					foreach($subscription->transaction_template->custom_fields->custom_field as $custom_field)
					{
						if($custom_field->custom_field_name == "blog_id")
						{
							$temp_blog_id = $custom_field->custom_field_value;	
						}
					}
					if($temp_blog_id != "")
					{
						$blogqs = "&b=" . $temp_blog_id;
					}					
				}
				else
				{
					$blogqs = "&b=" . $wpdb->blogid;		
				}
			}
			$output .= "<tr>" .
						 "	<td>" . $customer_name . "</a></td>" . 
						 "  <td>" . $subscription->start_date . "</td>" .
						 "  <td>" . $subscription->next_transaction_date . "</td>" .
						 "	<td>" . $subscription->end_date . "</td>" .
						 "	<td>" . foxypress_FormatCurrency($subscription->past_due_amount) . "</td>" .
						 "	<td>" . $subscription->frequency . "</td>" . 
						 "	<td>" . $product_price . "</td>" .						 
						 "	<td><a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=subscriptions&sub_token=" . $sub_token . $blogqs . "\">" . __('Edit', 'foxypress') . "</a></td>" . 
						 "</tr>";
		}
		$output .= "</tbody>
			  </table>";
			  
		$Total_Transactions = (int)$foxyXMLResponse->statistics->filtered_total;
		$Pagination_Start = (int)$foxyXMLResponse->statistics->pagination_start;
		$Pagination_End = (int)$foxyXMLResponse->statistics->pagination_end;		
		if($Total_Transactions > $Pagination_End) //foxy only lets us grab 300 at a time, if we have more, recurse.
		{
			$targetpage = get_admin_url() . "edit.php?post_type=foxypress_product&page=subscriptions";
			$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
			$pos = strrpos($targetpage, "?");
			if ($pos === false) {
				$targetpage .= "?";
			}
			$Pagination = foxypress_GetPagination($PageStart + 1, $Total_Transactions, $foxyData["entries_per_page"], $targetpage, 'fp_pn');
			$output .= "<br />" . $Pagination;
		}
	}
	else
	{
		$output .= "<tr><td colspan=\"8\">" . __('There are currently no subscriptions', 'foxypress') . "</td></tr>";
	}
	
	echo("<div class=\"wrap\">
	    	<h2>Subscriptions</h2>	
			<div>$output</div>
		  </div>");
}

?>