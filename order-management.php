<?
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_menu', 'order_management_menu');
add_action('admin_init', 'order_management_postback');

function order_management_postback()
{
	global $wpdb;
	$TransactionID = foxypress_FixGetVar('transaction');
	$Page_Action = foxypress_FixGetVar("action", "");
	//save note
	if(isset($_POST['foxy_om_note_submit']))
	{
		$current_user = wp_get_current_user();
		$NoteText = foxypress_FixPostVar("foxy_om_note");
		$sql = "insert into " . WP_TRANSACTION_NOTE_TABLE . " (foxy_transaction_id, foxy_transaction_note, foxy_transaction_entered_by, foxy_transaction_date_entered) values ('$TransactionID', '$NoteText', '$current_user->user_login', CURDATE())";
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID. "&mode=detail");
	}
	//delete note
	else if($Page_Action == "deletenote" && foxypress_FixGetVar("note", "") != "")
	{
		$NoteID = foxypress_FixGetVar("note", "");
		$sql = "delete from  " . WP_TRANSACTION_NOTE_TABLE . " WHERE foxy_transaction_id = '$TransactionID' and foxy_transaction_note_id='$NoteID'";
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID . "&mode=detail");
	}
	else if(isset($_POST['foxy_om_submit_Address']))
	{
		$BillingAddress1 = foxypress_FixPostVar("foxy_om_txtBillingAddress1");
		$BillingAddress2 = foxypress_FixPostVar("foxy_om_txtBillingAddress2");
		$BillingCity = foxypress_FixPostVar("foxy_om_txtBillingCity");
		$BillingState = foxypress_FixPostVar("foxy_om_txtBillingState");
		$BillingZip = foxypress_FixPostVar("foxy_om_txtBillingZip");
		$ShippingAddress1 = foxypress_FixPostVar("foxy_om_txtShippingAddress1");
		$ShippingAddress2 = foxypress_FixPostVar("foxy_om_txtShippingAddress2");
		$ShippingCity = foxypress_FixPostVar("foxy_om_txtShippingCity");
		$ShippingState = foxypress_FixPostVar("foxy_om_txtShippingState");
		$ShippingZip = foxypress_FixPostVar("foxy_om_txtShippingZip");
		$updateSQL = "update " . WP_TRANSACTION_TABLE . "
					set foxy_transaction_billing_address1 = '$BillingAddress1'
						,foxy_transaction_billing_address2 = '$BillingAddress2'
						,foxy_transaction_billing_city = '$BillingCity'
						,foxy_transaction_billing_state = '$BillingState'
						,foxy_transaction_billing_zip = '$BillingZip'
						,foxy_transaction_shipping_address1 = '$ShippingAddress1'
						,foxy_transaction_shipping_address2 = '$ShippingAddress2'
						,foxy_transaction_shipping_city = '$ShippingCity'
						,foxy_transaction_shipping_state = '$ShippingState'
						,foxy_transaction_shipping_zip = '$ShippingZip'
					where foxy_transaction_id = '$TransactionID'";
		$wpdb->query($updateSQL);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID. "&mode=detail");
	}
	else if(isset($_POST['foxy_om_transaction_submit']))
	{
		$NewStatus = foxypress_FixPostVar("foxy_om_ddl_status");
		$TrackingNumber = foxypress_FixPostVar("foxy_om_tracking_number");
		//get transaction details & current status
		$tRow = $wpdb->get_row("select * from " . WP_TRANSACTION_TABLE . " where foxy_transaction_id = '$TransactionID'");
		//if it's different check the table to see if we need to send an email
		if($tRow->foxy_transaction_status != $NewStatus)
		{
			$statusEmail = $wpdb->get_row("select * from " . WP_TRANSACTION_STATUS_TABLE . " where foxy_transaction_status = '$NewStatus'");
			if($statusEmail->foxy_transaction_status_email_flag == "1")
			{
				if($tRow->foxy_transaction_email != "")
				{
					$EmailBody = $statusEmail->foxy_transaction_status_email_body;
					if($statusEmail->foxy_transaction_status_email_tracking == "1" && $TrackingNumber != "")
					{
						$EmailBody .= "<br /> Tracking Number: " . $TrackingNumber;
					}
					//send email to customer
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
					$headers .= 'From: <' . get_settings("admin_email ") . '>' . "\r\n";
					mail($tRow->foxy_transaction_email,$statusEmail->foxy_transaction_status_email_subject,$EmailBody,$headers);
				}
			}
		}
		//save transaction status
		$sql = "update " . WP_TRANSACTION_TABLE . " SET foxy_transaction_status = '$NewStatus', foxy_transaction_trackingnumber = '$TrackingNumber' WHERE foxy_transaction_id = '$TransactionID'";
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID. "&mode=detail");
	}
}

function order_management_menu()  {
	global $wpdb;
	$allowed_group = 'manage_options';
	if (function_exists('add_submenu_page'))
	 {
	   add_submenu_page('foxypress', __('Order Management','foxypress'), __('Order Management','foxypress'), $allowed_group, 'order-management', 'order_management_page_load');
	 }
}

function order_management_page_load()
{
	//modes - list, detail, search
	global $wpdb;
	//check the post first, if we have nothing check the query string, if nothing is there just default to list view
	$Page_Mode = (foxypress_FixPostVar("foxy_om_mode", "") != "") ? foxypress_FixPostVar("foxy_om_mode", "") : foxypress_FixGetVar("mode", "list");
	$Page_Action = foxypress_FixGetVar("action", "");
	$Page_URL = foxypress_GetCurrentPageURL();
	if($Page_Action == "sync")
	{
		SyncTransactions(false, "0");
		exit;
	}
	else if($Page_Action == "syncall")
	{
		SyncTransactions(true, "0");
		exit;
	}
	Begin_Foxy_Order_Management();
	if($Page_Mode == "list")
	{
		$List_Status = foxypress_FixGetVar("status", "");
		if($List_Status == "") //general view, list all of the statuses
		{
			//get counts
			$sql = "SELECT ts.foxy_transaction_status, ts.foxy_transaction_status_description, coalesce(lj.StatusCount, 0) as Count
					FROM " . WP_TRANSACTION_STATUS_TABLE . " ts
					left join (select foxy_transaction_status as StatusID, count(*) as StatusCount from " . WP_TRANSACTION_TABLE . " group by foxy_transaction_status )
						lj on ts.foxy_transaction_status = lj.StatusID";
            $TransactionStatuses = $wpdb->get_results($sql);
            _e('<h3>View your orders by status </h3>');
			echo("<table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">Order Status</th>
							<th class=\"manage-column\" scope=\"col\">Status Quantity</th>
						</tr>
					</thead>");
			if( !empty($TransactionStatuses) )
			{
				foreach ( $TransactionStatuses as $ts )
				{
					echo("<tr>
							<td>
								<a href=\"" . $_SERVER['PHP_SELF'] . "?page=order-management&status=" . $ts->foxy_transaction_status . "&mode=list\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>
							</td>
							<td>" . $ts->Count . "</td>
						  </tr>");
				}
			}
			else
			{
				echo("<tr><td colspan=\"2\">You do not have any statuses set up yet. Use the <a href=\"http://www.google.com\">Status Management</a> tool to create new statues.</td></tr>");
			}
			echo("</table>");
			//get last sync date
			$drSync = $wpdb->get_row("select foxy_transaction_sync_timestamp from " . WP_TRANSACTION_SYNC_TABLE);
			_e('<h3>Sync Transactions </h3>');
			echo("Please click the button below to sync your latest transactions from FoxyCart.<br>
				   <form id=\"syncForm\" name=\"syncForm\" method=\"POST\">
					<div>
						<input type=\"button\" id=\"foxy_om_sync_now\"  name=\"foxy_om_sync_now\" value=\"Sync Latest Transactions\" onclick=\"SyncTransactionsJS('" . $Page_URL . "?page=order-management', '" . $Page_URL . "?page=order-management&action=sync', '../wp-content/plugins/foxypress/img/ajax-loader.gif', true);\" /> 
						<input type=\"button\" id=\"foxy_om_sync_all\"  name=\"foxy_om_sync_all\" value=\"Sync All Transactions\" onclick=\"SyncTransactionsJS('" . $Page_URL . "?page=order-management', '" . $Page_URL . "?page=order-management&action=syncall', '../wp-content/plugins/foxypress/img/ajax-loader.gif', true);\" />
						<span id=\"foxy_om_sync\"></span>
						<br><i>Last Synchronized: " . $drSync->foxy_transaction_sync_timestamp . "</i>
					</div>
				  </form>");
		}
		else
		{
			$Transaction_Type = foxypress_FixGetVar("transactiontype", "");
			$basePage =  $Page_URL . "?page=order-management&status=" . $List_Status . "&mode=list";
			$TransactionFilter = ($Transaction_Type == "1") ? " and foxy_transaction_is_test='1'" : (($Transaction_Type == "0") ? " and foxy_transaction_is_test='0'" : "");
			$Status = $wpdb->get_row("SELECT * FROM " . WP_TRANSACTION_STATUS_TABLE . " WHERE foxy_transaction_status = '$List_Status'");
			if ( !empty($Status) ) {
				_e('<h3>View your ' . $Status->foxy_transaction_status_description . ' orders:</h3>');				
			}
			
			echo("<form id=\"foxy_om_filter_form\" name=\"foxy_om_filter_form\" method=\"POST\" \">
				 	<select id=\"foxy_om_transaction_type_filter\" name=\"foxy_om_transaction_type_filter\" onChange=\"RedirectFilter()\">
						<option value=\"" . $basePage  . "\"" . (($Transaction_Type == "") ? "selected='selected'" : "") . ">All Transactions</option>
						<option value=\"". $basePage . "&transactiontype=0" . "\"" . (($Transaction_Type == "0") ? "selected='selected'" : "") . ">Live Transactions</option>
						<option value=\"". $basePage . "&transactiontype=1" ."\"" . (($Transaction_Type == "1") ? "selected='selected'" : ""). ">Test Transactions</option>
				 	</select>
				 </form> <br>");
			$targetpage = $basePage . "&transactiontype=" . $Transaction_Type;
			$drRows = $wpdb->get_row("SELECT COUNT(*) as RowCount FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_status = '$List_Status' $TransactionFilter");
			$limit = 25;
			$pageNumber = foxypress_FixGetVar('pagenum');
			$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;
			$Transactions = $wpdb->get_results("SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_status = '$List_Status' $TransactionFilter order by foxy_transaction_id desc LIMIT $start, $limit");
			echo("<table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">Transaction ID</th>
							<th class=\"manage-column\" scope=\"col\">Date of Order</th>
							<th class=\"manage-column\" scope=\"col\">Name</th>
							<th class=\"manage-column\" scope=\"col\">Email</th>
							<th class=\"manage-column\" scope=\"col\">Tracking</th>
						</tr>
					</thead>");
			if ( !empty($Transactions) ) {
				foreach ( $Transactions as $t ) {
					echo("<tr>
							<td>
								<a href=\"" . $Page_URL . "?page=order-management&transaction=" . $t->foxy_transaction_id . "&mode=detail\">" . $t->foxy_transaction_id . "</a>
							</td>
							<td>" . $t->foxy_transaction_date . "</td>
							<td>" . $t->foxy_transaction_last_name . ", " . $t->foxy_transaction_first_name . "</td>
							<td>" . $t->foxy_transaction_email . "</td>
							<td>" . $t->foxy_transaction_trackingnumber . "</td>
						  </tr>");
				}				
			}
			else
			{
				echo("<tr><td colspan=\"4\">There are currently no orders with this transaction status</td></tr>");
			}
			echo("</table>");
			if($drRows->RowCount > $limit)
			{
				$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage);
				echo("<Br>" . $Pagination);
			}
		}
	}
	else if($Page_Mode == "detail")
	{
		$TransactionID = foxypress_FixGetVar("transaction", "");
		if($TransactionID == "")
		{
			echo("Invalid Transaction ID");
		}
		else
		{
			//get dater from Foxy
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
				//get transaction details
				$tRow = $wpdb->get_row("select * from " . WP_TRANSACTION_TABLE . " where foxy_transaction_id = '$TransactionID'");
				//get statuses for dropdown
				$StatusList = "";
				$TransactionStatuses = $wpdb->get_results("select * from " . WP_TRANSACTION_STATUS_TABLE);
				if( !empty($TransactionStatuses) )
				{
					foreach ( $TransactionStatuses as $ts )
					{
						$StatusList .= "<option value=\"" . $ts->foxy_transaction_status . "\"" . (($tRow->foxy_transaction_status == $ts->foxy_transaction_status) ? " selected='selected'" : "") . ">" . stripslashes($ts->foxy_transaction_status_description) . "</option>";
					}
				}
				$HasSameBillingAndShipping = ($tRow->foxy_transaction_shipping_address1 == "");
				//show general info
                echo("<h3>Transaction Details</h3>
					<div>
						Transaction ID: " . $foxyXMLResponse->transaction->id . " &nbsp; Date: " . $foxyXMLResponse->transaction->transaction_date . "
					</div> <br>
					<div>
						<form method=\"POST\" name=\"statusForm\" id=\"statusForm\">
							Status:
							<select id=\"foxy_om_ddl_status\" name=\"foxy_om_ddl_status\">"
							. $StatusList .
							"</select> &nbsp; Tracking Number: <input type=\"text\" name=\"foxy_om_tracking_number\" id=\"foxy_om_tracking_number\" value=\"" .  $tRow->foxy_transaction_trackingnumber . "\" />
							<input type=\"submit\" id=\"foxy_om_transaction_submit\" name=\"foxy_om_transaction_submit\" value=\"Save\" />
						</form>
					</div>
					<h3>Customer Details</h3>
                    <div>
                    	Name: " . $foxyXMLResponse->transaction->customer_last_name  . ", " . $foxyXMLResponse->transaction->customer_first_name .
						"<br> Email: " . $foxyXMLResponse->transaction->customer_email .
						"<br> Phone: " . $foxyXMLResponse->transaction->customer_phone . 	
                    "</div> <br><br>
					<div id=\"divViewAddress\">
						<table>
							<tr>
								<td valign=\"top\" style=\"padding-right:30px;\">
								<div>
									<b>Billing Address</b> <a href=\"javascript:ToggleEdit();\">(edit)</a> <br />" .
									$foxyXMLResponse->transaction->customer_last_name . " " . $foxyXMLResponse->transaction->customer_first_name . "<br />" .
									$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
									$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip . " " . $tRow->foxy_transaction_billing_country .
								"</div>
								</td>
								<td valign=\"top\">
								<div>
									<b>Shipping Address</b> <a href=\"javascript:ToggleEdit();\">(edit)</a><br />" .
									$foxyXMLResponse->transaction->customer_last_name . " " .  $foxyXMLResponse->transaction->customer_first_name . "<br />" .
									(
										($HasSameBillingAndShipping) ?
										$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
										$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip . " " . $tRow->foxy_transaction_billing_country
										:
										$tRow->foxy_transaction_shipping_address1 . " " .  $tRow->foxy_transaction_shipping_address2 . "<br />" .
										$tRow->foxy_transaction_shipping_city . ", " . $tRow->foxy_transaction_shipping_state . " " . $tRow->foxy_transaction_shipping_zip . " " . $tRow->foxy_transaction_shipping_country
									) .
								"</div>
								</td>
							</tr>
						</table>
					</div>
					<div id=\"divEditAddress\" class=\"Hide\">
						<table>
													<tr>
								<td valign=\"top\" style=\"padding-right:30px;\">
						<form name=\"AddressForm\" id=\"AddressForm\" method=\"POST\">
							<div><b>Billing Address</b> <a href=\"javascript:ToggleEdit();\">(cancel)</a></div>
							<table>
								<tr>
									<td>Address 1</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_address1 . "\" id=\"foxy_om_txtBillingAddress1\" name=\"foxy_om_txtBillingAddress1\" /></td>
								</tr>
								<tr>
									<td>Address 2</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_address2 . "\" id=\"foxy_om_txtBillingAddress2\" name=\"foxy_om_txtBillingAddress2\" /></td>
								</tr>
								<tr>
									<td>City, State, Zip</td>
									<td>
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_city . "\" id=\"foxy_om_txtBillingCity\" name=\"foxy_om_txtBillingCity\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_state . "\" id=\"foxy_om_txtBillingState\" name=\"foxy_om_txtBillingState\" maxlength=\"2\" size=\"4\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_zip . "\" id=\"foxy_om_txtBillingZip\" name=\"foxy_om_txtBillingZip\" maxlength=\"10\" size=\"10\" />
									</td>
								</tr>
							</table><br>
							</td>
								<td valign=\"top\">
							<div><b>Shipping Address</b> <a href=\"javascript:ToggleEdit();\">(cancel)</a></div>
							<table>
								<tr>
									<td>Address 1</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_address1 . "\" id=\"foxy_om_txtShippingAddress1\" name=\"foxy_om_txtShippingAddress1\" /></td>
								</tr>
								<tr>
									<td>Address 2</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_address2 . "\" id=\"foxy_om_txtShippingAddress2\" name=\"foxy_om_txtShippingAddress2\" /></td>
								</tr>
								<tr>
									<td>City, State, Zip</td>
									<td>
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_city . "\" id=\"foxy_om_txtShippingCity\" name=\"foxy_om_txtShippingCity\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_state . "\" id=\"foxy_om_txtShippingState\" name=\"foxy_om_txtShippingState\" maxlength=\"2\" size=\"4\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_zip . "\" id=\"foxy_om_txtShippingZip\" name=\"foxy_om_txtShippingZip\" maxlength=\"10\" size=\"10\" />
									</td>
								</tr>
							</table>
							<div><input type=\"submit\" id=\"foxy_om_submit_Address\" name=\"foxy_om_submit_Address\" value=\"Save Address Information\" /></div>
						</form>
						</td>
													</tr>
						</table>
					</div>
					<br>");

				//show transaction info
				echo("<div><b>Transaction Details</b></div>
					   <table>
						<tr>");
				$i=1;
				foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
				{
					$options = "";
					foreach($td->transaction_detail_options->transaction_detail_option as $opt)
					{
						$options .=  $opt->product_option_name . ": " . $opt->product_option_value . "<br>";
					}
					$ProductCode = $td->product_code;
					$ProductImage = "";
					if($ProductCode != "")
					{
						//get product image based on code
						$pi = $wpdb->get_row("SELECT ii.inventory_image
												FROM " . WP_INVENTORY_TABLE . " as i
												inner join " . WP_INVENTORY_IMAGES_TABLE . " as ii on i.inventory_id = ii.inventory_id
												where i.inventory_code = '" . $ProductCode . "'
												order by ii.image_order, ii.inventory_images_id
												LIMIT 0, 1");
						if(!empty($pi))
						{
							$ProductImage = "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . $pi->inventory_image . "\" style=\"width: 100px; \"/><br />";
						}
					}
					echo("<td style='padding-right:45px;' valign='top'>"
								. $ProductImage . 
								"Product: " . $td->product_name . "<br>" .
								"Price: " . $td->product_price . "<br>" .
								"Quantity: " . $td->product_quantity . "<br>" .
								"Weight: " . $td->product_weight . "<br>" . 
								(($ProductCode != "") ? "Code: " . $ProductCode . "<br>" : "") .
								$options .
						 	"</div><br />
						  </td>");
					if ($i % 2){ } 
					else 
					{
						echo"</tr><tr>";
					}
					$i+=1;
				}
				echo("	</tr>
					</table>");
					
				//show hidden fields				
				foreach($foxyXMLResponse->transaction->custom_fields->custom_field as $cf)
				{
					$HiddenFields .= "<div>" . $cf->custom_field_name . ": " . $cf->custom_field_value . "</div>";
				}
				if($HiddenFields != "")
				{
					_e("<div><b>Hidden Fields</b></div>");
					_e($HiddenFields);
					_e("<br>");
				}
				
				_e("<div><b>Transaction Totals</b></div>" . 
				   "<div>Product Total: $" . $foxyXMLResponse->transaction->product_total . "</div>" .
				   "<div>Tax Total: $" . $foxyXMLResponse->transaction->tax_total . "</div>" .
				   "<div>Shipping Total: $" . $foxyXMLResponse->transaction->shipping_total . "</div>" .
				   "<div>Order Total: $" . $foxyXMLResponse->transaction->order_total . "</div>");
					
				//show notes
				echo("<div><h3>Notes</h3></div>");
				echo("<table class=\"widefat page fixed\">
						<thead>
							<tr>
								<th class=\"manage-column\" scope=\"col\">Note</th>
								<th class=\"manage-column\" scope=\"col\">Posted By</th>
								<th class=\"manage-column\" scope=\"col\">Date</th>
								<th class=\"manage-column\" scope=\"col\">&nbsp;</th>
							</tr>
						</thead>");				
				$Notes = $wpdb->get_results("SELECT * FROM " . WP_TRANSACTION_NOTE_TABLE . " WHERE foxy_transaction_id = '$TransactionID'");
				if(!empty($Notes))
				{
					foreach ( $Notes as $n ) {
						echo("<tr>
								<td>" . 
										( (strlen(stripslashes($n->foxy_transaction_note)) > 50) 
										  ? "<span id=\"foxy_note_" . $n->foxy_transaction_note_id . "\">" . foxypress_TruncateString(stripslashes($n->foxy_transaction_note), 50) . "</span> <script type=\"text/javascript\"> jQuery('#foxy_note_" . $n->foxy_transaction_note_id . "').qtip({ content: '" . $n->foxy_transaction_note . "', show: 'mouseover', hide: 'mouseout', style : { name: 'dark', tip: 'bottomLeft' }, position : { corner: { target: 'topRight', tooltip: 'bottomLeft'} } }); </script>"
										  : stripslashes($n->foxy_transaction_note)
										 )										
									  . 
							   "</td>
								<td>" . $n->foxy_transaction_entered_by . "</td>
								<td>" . $n->foxy_transaction_date_entered . "</td>
								<td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID . "&mode=detail&action=deletenote&note=" . $n->foxy_transaction_note_id . "\" onclick=\"return confirm('Are you sure you want to delete this note?');\">Delete</a></td>
							  </tr>");
					}
				}
				else
				{
					echo("<tr><td colspan=\"4\">There are currently no notes</td></tr>");
				}
				echo("</table>");			
				echo("<h3>New Note</h3>");
				//show form for new notes
				echo("<form name=\"noteForm\" id=\"noteform\" method=\"POST\">
					   	<textarea id=\"foxy_om_note\" name=\"foxy_om_note\" cols=\"50\" rows=\"3\"></textarea> <br>
						<input type=\"submit\" name=\"foxy_om_note_submit\" id=\"foxy_om_note_submit\" value=\"Add Note\" />
					  </form>");
			}//end check for success
			else
			{
				echo("Invalid Transaction ID");
			}
		}
	}
	else if($Page_Mode == "search")
	{
		$SearchValue = foxypress_FixPostVar("foxy_om_search", "");
		ProcessSearch($SearchValue);
	}
	//check to see how much time has passed since the last time we synched. If it has been more than 10 minutes, sync it up.
	$NeedsSync = false;
	$sql = "select ( (unix_timestamp(now()) - unix_timestamp(foxy_transaction_sync_timestamp)) / 60 ) as Minutes FROM " . WP_TRANSACTION_SYNC_TABLE;
	$drSync = $wpdb->get_row($sql);
	if(!empty($drSync))
	{
		if(	$drSync->Minutes >= 10 )
		{
			$NeedsSync = true;
		}
	}
	End_Foxy_Order_Management($NeedsSync);
}

function ProcessSearch($SearchValue)
{
	global $wpdb;
	if($SearchValue == "")
	{
		echo("Invalid search term, please try again. You can search by First Name, Last Name, or Transaction ID.");
	}
	else
	{
		//search for exact matches, then like matches (union)
		$SearchSQL = "SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_id = '$SearchValue'
					  UNION
					  SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_first_name = '$SearchValue' or foxy_transaction_last_name = '$SearchValue'
					  UNION
					  SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_id like '%" . $SearchValue. "%'
					  UNION
					  SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_first_name like '%" . $SearchValue . "%' or foxy_transaction_last_name = '%" . $SearchValue . "%'";
		$Transactions = $wpdb->get_results($SearchSQL);
		
		echo("<br><table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">Transaction ID</th>
							<th class=\"manage-column\" scope=\"col\">Date of Order</th>
							<th class=\"manage-column\" scope=\"col\">Name</th>
							<th class=\"manage-column\" scope=\"col\">Email</th>
							<th class=\"manage-column\" scope=\"col\">Tracking</th>
						</tr>
					</thead>");
		if( !empty($Transactions) )
		{
			foreach($Transactions as $t)
			{
				echo("<tr>
							<td>
								<a href=\"" . $Page_URL . "?page=order-management&transaction=" . $t->foxy_transaction_id . "&mode=detail\">" . $t->foxy_transaction_id . "</a>
							</td>
							<td>" . $t->foxy_transaction_date . "</td>
							<td>" . $t->foxy_transaction_last_name . ", " . $t->foxy_transaction_first_name . "</td>
							<td>" . $t->foxy_transaction_email . "</td>
							<td>" . $t->foxy_transaction_trackingnumber . "</td>
						  </tr>");
			}
		}
		else
		{
			echo("<tr><td colspan=\"4\">Your search did not return any results, please try again</td></tr>");
		}
		echo("</table>");
	}
}

function Begin_Foxy_Order_Management()
{
	?>
    <script type="text/javascript" src="<?=get_bloginfo("url")?>/wp-content/plugins/foxypress/js/jquery.qtip.js"></script>
    <style type="text/css">
		.Hide { display:none; }
	  	div.foxy_item_pagination {
			padding: 3px;
			margin: 3px;
		}		
		div.foxy_item_pagination a {
			padding: 2px 5px 2px 5px;
			margin: 2px;
			border: 1px solid #AAAADD;	
			text-decoration: none; /* no underline */
			/*color: #000099;*/
		}
		div.foxy_item_pagination a:hover, div.foxy_item_pagination a:active {
			border: 1px solid #000099;
			color: #000;
		}
		div.foxy_item_pagination span.current {
			padding: 2px 5px 2px 5px;
			margin: 2px;
			border: 1px solid #666666;	
			font-weight: bold;
/*			background-color: #000099;*/
			color: #666666;
		}
		div.foxy_item_pagination span.disabled {
			padding: 2px 5px 2px 5px;
			margin: 2px;
			border: 1px solid #EEE;
			color: #ccc;
		}
	</style>
	<div class="wrap">
    	<h2><?php _e('Order Management','order-management'); ?></h2>
        <div>
            <div><i>Search by First Name, Last Name, or Transaction ID</i></div>            
            <form name="foxy_om_search" id="foxy_om_search" class="wrap" method="post">
            	<input type="hidden" name="foxy_om_mode" id="foxy_om_mode" value="search" />
                <div id="linkadvanceddiv" class="postbox">
                    <div style="float: left; width: 98%; clear: both;" class="inside">
                        <table cellspacing="5" cellpadding="5">
                            <tr>
                                <td><input type="text" name="foxy_om_search" id="foxy_om_search" value="<?=foxypress_FixPostVar("foxy_om_search", "")?>" /> </td>
                                <td><input type="submit" id="foxy_om_search_submit" class="button bold" name="foxy_om_search_submit" value="Search &raquo;" /></td>
                            </tr>
                        </table>
                    </div>
                    <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
            </form>
        </div>
	<?
}

function End_Foxy_Order_Management($NeedsSync)
{
	$Page_URL = foxypress_GetCurrentPageURL();
	?>
		</div>
        <script type="text/javascript" language="javascript">
			function ToggleEdit()
			{
				var show = jQuery('#divEditAddress').hasClass("Hide");
				if(show)
				{
					jQuery('#divEditAddress').removeClass("Hide");
					jQuery('#divViewAddress').addClass("Hide");
				}
				else
				{
					jQuery('#divEditAddress').addClass("Hide");
					jQuery('#divViewAddress').removeClass("Hide");
				}
			}
			
			function RedirectFilter()
			{
				window.location.href = jQuery('#foxy_om_transaction_type_filter').val();
			}

			function SyncTransactionsJS(baseurl, fullurl, loadingimage, showProgress)
			{
				if(showProgress)
				{
					jQuery('#foxy_om_sync').html('<img src="' + loadingimage + '" />');
					jQuery.get(fullurl, function(data) {
					  jQuery('#foxy_om_sync').html('Synchronization Complete <a href="' + baseurl + '">Refresh Page</a>');
					});
				}
				else
				{
					jQuery.get(fullurl);
				}
			}
			<?
				if($NeedsSync)
				{
					echo("SyncTransactionsJS('" . $Page_URL . "?page=order-management', '" . $Page_URL . "?page=order-management&action=sync', '../wp-content/plugins/foxypress/img/ajax-loader.gif', false)");
				}
			?>
	  	</script>
	<?
}

function SyncTransactions($SyncAll, $PageStart)
{
	global $wpdb;
	//get last date we synced, if it's a new sync we start at 1900, if it's a current running system then we take the last date and subtract a day
	$sql = "SELECT CASE foxy_transaction_sync_date WHEN '1900-01-01' THEN '1900-01-01' ELSE DATE_SUB(foxy_transaction_sync_date, INTERVAL 1 DAY) END as LastSync, DATE_FORMAT(NOW(), '%Y-%m-%d') as CurrentDate FROM " . WP_TRANSACTION_SYNC_TABLE;
	$dr = $wpdb->get_row($sql);
	$LastSync = $dr->LastSync;
	if($SyncAll)
	{
		$LastSync = "1900-01-01";
	}
	//use that date to query for new transactions that have happened since then
	$foxyStoreURL = get_option('foxycart_storeurl');
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_list";
	$foxyData["transaction_date_filter_begin"] = $LastSync;
	$foxyData["transaction_date_filter_end"] = $dr->CurrentDate;
	$foxyData["hide_transaction_filter"] = "";
	$foxyData["is_test_filter"] = "";	
	$foxyData["pagination_start"] = $PageStart;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	print_r($foxyXMLResponse);
	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->transactions->transaction as $t)
		{
			//insert new transactions into our db, ignore existing transactions
			$sql = "INSERT IGNORE INTO " . WP_TRANSACTION_TABLE .
				  " SET foxy_transaction_id = '" . $t->id . "'" .
				  ", foxy_transaction_status = '1'" .
				  ", foxy_transaction_first_name='" . $t->customer_first_name . "'" .
				  ", foxy_transaction_last_name='" . $t->customer_last_name . "'" .
				  ", foxy_transaction_email='" . $t->customer_email . "'" .
				  ", foxy_transaction_is_test='" . $t->is_test . "'" . 
				  ", foxy_transaction_date = '" . $t->transaction_date . "'" .
				  ", foxy_transaction_product_total = '" . $t->product_total . "'" .
				  ", foxy_transaction_tax_total = '" . $t->tax_total . "'" .
				  ", foxy_transaction_shipping_total = '" . $t->shipping_total . "'" .
				  ", foxy_transaction_order_total = '" . $t->order_total . "'" .
				  ", foxy_transaction_cc_type = '" . $t->cc_type . "'";

				  if($t->shipping_address1 == "")
				  {
					  //use billing for both
					  $sql .= ", foxy_transaction_billing_address1 = '" . $t->customer_address1 . "'" .
					  		  ", foxy_transaction_billing_address2 = '" . $t->customer_address2 . "'" .
							  ", foxy_transaction_billing_city = '" . $t->customer_city . "'" .
							  ", foxy_transaction_billing_state = '" . $t->customer_state . "'" .
							  ", foxy_transaction_billing_zip = '" . $t->customer_postal_code . "'" .
							  ", foxy_transaction_billing_country = '" . $t->customer_country . "'" .
							  ", foxy_transaction_shipping_address1 = '" . $t->customer_address1. "'" .
							  ", foxy_transaction_shipping_address2 = '" . $t->customer_address2. "'" .
							  ", foxy_transaction_shipping_city = '" . $t->customer_city. "'" .
							  ", foxy_transaction_shipping_state = '" . $t->customer_state. "'" .
							  ", foxy_transaction_shipping_zip = '" . $t->customer_postal_code. "'" .
							  ", foxy_transaction_shipping_country = '" . $t->customer_country. "'";
				  }
				  else
				  {
					  $sql .= ", foxy_transaction_billing_address1 = '" . $t->customer_address1 . "'" .
					  		  ", foxy_transaction_billing_address2 = '" . $t->customer_address2 . "'" .
							  ", foxy_transaction_billing_city = '" . $t->customer_city . "'" .
							  ", foxy_transaction_billing_state = '" . $t->customer_state . "'" .
							  ", foxy_transaction_billing_zip = '" . $t->customer_postal_code . "'" .
							  ", foxy_transaction_billing_country = '" . $t->customer_country . "'" .
							  ", foxy_transaction_shipping_address1 = '" . $t->shipping_address1. "'" .
							  ", foxy_transaction_shipping_address2 = '" . $t->shipping_address2. "'" .
							  ", foxy_transaction_shipping_city = '" . $t->shipping_city. "'" .
							  ", foxy_transaction_shipping_state = '" . $t->shipping_state. "'" .
							  ", foxy_transaction_shipping_zip = '" . $t->shipping_postal_code. "'" .
							  ", foxy_transaction_shipping_country = '" . $t->shipping_country. "'";
				  }
			$wpdb->query($sql);
			
			//if our insert ignores it and they have an old version we may have missed the ...
			//	- version 0.1.9 - foxy_transaction_is_test
			//  - version 0.2.5 - _date, _product_total, tax_total, shipping_total, order_total, cc_type
			//so update accordingly
			$sql = "UPDATE " . WP_TRANSACTION_TABLE .  " 
					SET  foxy_transaction_is_test='" . $t->is_test . "'" . 
					  ", foxy_transaction_date = '" . $t->transaction_date . "'" .
					  ", foxy_transaction_product_total = '" . $t->product_total . "'" .
					  ", foxy_transaction_tax_total = '" . $t->tax_total . "'" .
					  ", foxy_transaction_shipping_total = '" . $t->shipping_total . "'" .
					  ", foxy_transaction_order_total = '" . $t->order_total . "'" .
					  ", foxy_transaction_cc_type = '" . $t->cc_type . "'
					WHERE foxy_transaction_id = '" . $t->id . "'";
			$wpdb->query($sql);
		}
		
		$Total_Transactions = (int)$foxyXMLResponse->statistics->filtered_total;
		$Pagination_Start = (int)$foxyXMLResponse->statistics->pagination_start;
		$Pagination_End = (int)$foxyXMLResponse->statistics->pagination_end;		
		if($Total_Transactions > $Pagination_End) //foxy only lets us grab 300 at a time, if we have more, recurse.
		{
			$NextStart = $Pagination_End;		
			SyncTransactions($SyncAll, $NextStart);
		}
	}
	
	if($PageStart == "0") //update after all recursion is done
	{
		//update our last sync timestamp(s)
		$sql = "UPDATE " . WP_TRANSACTION_SYNC_TABLE . " SET foxy_transaction_sync_date = '" . $dr->CurrentDate . "', foxy_transaction_sync_timestamp = now()";
		$wpdb->query($sql);
	}
}


?>