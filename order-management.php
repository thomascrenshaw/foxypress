<?
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);

define('WP_TRANSACTION_TABLE', $table_prefix . 'foxypress_transaction');
define('WP_TRANSACTION_NOTE_TABLE', $table_prefix . 'foxypress_transaction_note');
define('WP_TRANSACTION_SYNC_TABLE', $table_prefix . 'foxypress_transaction_sync');
define('WP_TRANSACTION_STATUS_TABLE', $table_prefix . 'foxypress_transaction_status');

add_action('admin_menu', 'order_management_menu');


function order_management_menu()  {
	global $wpdb;
	ManageTables();
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
	$Page_Mode = (FixPostVar("foxy_om_mode", "") != "") ? FixPostVar("foxy_om_mode", "") : FixGetVar("mode", "list");
	$Page_Action = FixGetVar("action", "");
	$Page_URL = GetCurrentPageURL();
	if($Page_Action == "sync")
	{
		SyncTransactions();
		exit;
	}

	Begin_Foxy_Order_Management();
	if($Page_Mode == "list")
	{
		$List_Status = FixGetVar("status", "");
		if($List_Status == "") //general view, list all of the statuses
		{
			//get counts
			$sql = "SELECT ts.foxy_transaction_status, ts.foxy_transaction_status_description, coalesce(lj.StatusCount, 0) as Count
					FROM " . WP_TRANSACTION_STATUS_TABLE . " ts
					left join (select foxy_transaction_status as StatusID, count(*) as StatusCount from " . WP_TRANSACTION_TABLE . " group by foxy_transaction_status )
						lj on ts.foxy_transaction_status = lj.StatusID";
            $TransactionStatuses = $wpdb->get_results($sql);
            _e('<h3>View your orders by status </h3>');
			if( !empty($TransactionStatuses) )
			{
				foreach ( $TransactionStatuses as $ts )
				{
					echo("<div><a href=\"" . $_SERVER['PHP_SELF'] . "?page=order-management&status=" . $ts->foxy_transaction_status . "&mode=list\">" . stripslashes($ts->foxy_transaction_status_description) . " (" . $ts->Count . ")</a></div>");
				}
			}
			else
			{
				echo("<div>You do not have any statuses set up yet. Use the <a href=\"http://www.google.com\">Status Management</a> tool to create new statues.</div>");
			}

			//get last sync date
			$drSync = $wpdb->get_row("select foxy_transaction_sync_timestamp from " . WP_TRANSACTION_SYNC_TABLE);
			_e('<h3>Sync Transactions </h3>');
			echo("Please click the button below to sync your latest transactions from FoxyCart.<br>
				   <form id=\"syncForm\" name=\"syncForm\" method=\"POST\">
					<div>
						<input type=\"button\" id=\"foxy_om_sync_now\"  name=\"foxy_om_sync_now\" value=\"Sync Transactions\" onclick=\"SyncTransactionsJS('" . $Page_URL . "?page=order-management', '" . $Page_URL . "?page=order-management&action=sync', '../wp-content/plugins/foxypress/img/ajax-loader.gif', true);\" />
						<span id=\"foxy_om_sync\"></span>
						<br><i>Last Synchronized: " . $drSync->foxy_transaction_sync_timestamp . "</i>
					</div>
				  </form>");
		}
		else
		{
			$Status = $wpdb->get_results("SELECT * FROM " . WP_TRANSACTION_STATUS_TABLE . " WHERE foxy_transaction_status = '$List_Status'");
			if ( !empty($Status) ) {
				foreach ( $Status as $s ) {
					_e('<h3>View your ' . $s->foxy_transaction_status_description . ' orders:</h3>');
				}
			}

			$Transactions = $wpdb->get_results("SELECT * FROM " . WP_TRANSACTION_TABLE . " WHERE foxy_transaction_status = '$List_Status' order by foxy_transaction_id desc");
			if ( !empty($Transactions) ) {
				foreach ( $Transactions as $t ) {
					echo("<div><a href=\"" .$_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $t->foxy_transaction_id . "&mode=detail\">" . $t->foxy_transaction_id . " - " . $t->foxy_transaction_last_name . ", " . $t->foxy_transaction_first_name . "</div>");
				}
			}
			else
			{
				echo("There are currently no orders with this transaction status");
			}
		}
	}
	else if($Page_Mode == "detail")
	{
		$TransactionID = FixGetVar("transaction", "");
		if($TransactionID == "")
		{
			echo("Invalid Transaction ID");
		}
		else
		{
			//handle our postbacks & actions
			if(isset($_POST['foxy_om_note_submit']))
			{
				//save note
				$current_user = wp_get_current_user();
				$NoteText = FixPostVar("foxy_om_note");
				$sql = "insert into " . WP_TRANSACTION_NOTE_TABLE . " (foxy_transaction_id, foxy_transaction_note, foxy_transaction_entered_by, foxy_transaction_date_entered) values ('$TransactionID', '$NoteText', '$current_user->user_login', CURDATE())";
				$wpdb->query($sql);
			}
			else if($Page_Action == "deletenote" && FixGetVar("note", "") != "")
			{
				//delete note
				$NoteID = FixGetVar("note", "");
				$sql = "delete from  " . WP_TRANSACTION_NOTE_TABLE . " WHERE foxy_transaction_id = '$TransactionID' and foxy_transaction_note_id='$NoteID'";
				$wpdb->query($sql);
			}
			else if(isset($_POST['foxy_om_submit_Address']))
			{
				$BillingAddress1 = FixPostVar("foxy_om_txtBillingAddress1");
				$BillingAddress2 = FixPostVar("foxy_om_txtBillingAddress2");
				$BillingCity = FixPostVar("foxy_om_txtBillingCity");
				$BillingState = FixPostVar("foxy_om_txtBillingState");
				$BillingZip = FixPostVar("foxy_om_txtBillingZip");
				$ShippingAddress1 = FixPostVar("foxy_om_txtShippingAddress1");
				$ShippingAddress2 = FixPostVar("foxy_om_txtShippingAddress2");
				$ShippingCity = FixPostVar("foxy_om_txtShippingCity");
				$ShippingState = FixPostVar("foxy_om_txtShippingState");
				$ShippingZip = FixPostVar("foxy_om_txtShippingZip");
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
			}
			else if(isset($_POST['foxy_om_transaction_submit']))
			{
				$NewStatus = FixPostVar("foxy_om_ddl_status");
				$TrackingNumber = FixPostVar("foxy_om_tracking_number");
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
			}

			//get dater from Foxy
			$foxyStoreURL = get_option('foxycart_storeurl');
			$foxyAPIKey =  get_option('foxycart_apikey');
			$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  $foxyAPIKey;
			$foxyData["api_action"] = "transaction_get";
			$foxyData["transaction_id"] = $TransactionID;
			$SearchResults = curlPostRequest($foxyAPIURL, $foxyData);
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
                    	Name: " . $foxyXMLResponse->transaction->customer_last_name  . ", " . $foxyXMLResponse->transaction->customer_first_name . "
                    </div> <br><br>
					<div id=\"divViewAddress\">
						<table>
							<tr>
								<td valign=\"top\" style=\"padding-right:30px;\">
								<div>
									<b>Billing Address</b> <a href=\"javascript:ToggleEdit();\">(edit)</a> <br />" .
									$foxyXMLResponse->transaction->customer_last_name . " " . $foxyXMLResponse->transaction->customer_first_name . "<br />" .
									$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
									$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip .
								"</div>
								</td>
								<td valign=\"top\">
								<div>
									<b>Shipping Address</b> <a href=\"javascript:ToggleEdit();\">(edit)</a><br />" .
									$foxyXMLResponse->transaction->customer_last_name . " " .  $foxyXMLResponse->transaction->customer_first_name . "<br />" .
									(
										($HasSameBillingAndShipping) ?
										$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
										$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip
										:
										$tRow->foxy_transaction_shipping_address1 . " " .  $tRow->foxy_transaction_shipping_address2 . "<br />" .
										$tRow->foxy_transaction_shipping_city . ", " . $tRow->foxy_transaction_shipping_state . " " . $tRow->foxy_transaction_shipping_zip
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
				echo("<div><b>Transaction Details</b></div>");
				echo("<table>
						<tr>");
				$i=1;
				foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
				{

					echo("<td style='padding-right:45px;'><div>" .
							"Product: " . $td->product_name . "<br>" .
							"Price: " . $td->product_price . "<br>" .
							"Quantity: " . $td->product_quantity . "<br>" .
							"Weight: " . $td->product_weight . "<br>" .
						 "</div> <br></td>");
						 if ($i % 2) {
							//echo "This number is not even.";
						} else {
							//echo "This number is even.";
							echo"</tr>";
							echo"<tr>";
					}
					$i+=1;
				}
				echo("	</tr>
					</table>");
				//show notes
				echo("<div><h3>Notes</h3></div>");
				$Notes = $wpdb->get_results("SELECT * FROM " . WP_TRANSACTION_NOTE_TABLE . " WHERE foxy_transaction_id = '$TransactionID'");
				if(!empty($Notes))
				{
					foreach ( $Notes as $n ) {
						echo("<div>" . stripslashes($n->foxy_transaction_note) . " <br> <i>Noted by " . $n->foxy_transaction_entered_by . " on " . $n->foxy_transaction_date_entered . "</i> <a href=\"" . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $TransactionID . "&mode=detail&action=deletenote&note=" . $n->foxy_transaction_note_id . "\">[Delete Note]</a></div><br>");
					}
				}
				else
				{
					echo("<div>There are currently no notes</div>");
				}
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
		$SearchValue = FixPostVar("foxy_om_search", "");
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
		if( !empty($Transactions) )
		{
			foreach($Transactions as $t)
			{
				echo("<a href=\"" . $_SERVER['PHP_SELF'] . "?page=order-management&transaction=" . $t->foxy_transaction_id . "&mode=detail\">" . $t->foxy_transaction_id . " " . $t->foxy_transaction_last_name . ", " . $t->foxy_transaction_first_name . "</a> <br><br>");
			}
		}
		else
		{
			echo("Your search did not return any results, please try again");
		}
	}
}

function Begin_Foxy_Order_Management()
{
	?>
    <style type="text/css">
	  .Hide { display:none; }
	</style>
	<div class="wrap">
    	<h2><?php _e('Order Management','order-management'); ?></h2>
        <div style="">
        	<h3><?php _e('Search'); ?></h3>
        	<?php _e('Search by first name, last name, or transaction id'); ?>
        	<form name="omsearchForm" id="omsearchForm" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=order-management">
            	<input type="hidden" name="foxy_om_mode" id="foxy_om_mode" value="search" />
        		<input type="text" name="foxy_om_search" id="foxy_om_search" value="<?=FixPostVar("foxy_om_search", "")?>" /> <input type="submit" id="foxy_om_search_submit" name="foxy_om_search_submit" value="Search" />

            </form>
        </div>

	<?
}

function End_Foxy_Order_Management($NeedsSync)
{
	$Page_URL = GetCurrentPageURL();
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

function ManageTables()
{
	global $wpdb;
	$TablesExist = false;
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table ) {
		foreach ( $table as $value ) {
			if($value == WP_TRANSACTION_TABLE)
			{
				$TablesExist = true;
				break;
			}
	  	}
	}

	if(!$TablesExist)
	{
		//create main transaction table to hold data that gets synched up.
		$sql = "CREATE TABLE " . WP_TRANSACTION_TABLE . " (
				foxy_transaction_id INT(11) NOT NULL PRIMARY KEY,
				foxy_transaction_status VARCHAR(30) NOT NULL,
				foxy_transaction_first_name VARCHAR(50) NULL,
				foxy_transaction_last_name VARCHAR(50) NULL,
				foxy_transaction_email VARCHAR(50) NULL,
				foxy_transaction_trackingnumber VARCHAR(100) NULL,
				foxy_transaction_billing_address1 VARCHAR(50) NULL,
				foxy_transaction_billing_address2 VARCHAR(50) NULL,
				foxy_transaction_billing_city VARCHAR(50) NULL,
				foxy_transaction_billing_state VARCHAR(2) NULL,
				foxy_transaction_billing_zip VARCHAR(10) NULL,
				foxy_transaction_billing_country VARCHAR(50) NULL,
				foxy_transaction_shipping_address1 VARCHAR(50) NULL,
				foxy_transaction_shipping_address2 VARCHAR(50) NULL,
				foxy_transaction_shipping_city VARCHAR(50) NULL,
				foxy_transaction_shipping_state VARCHAR(2) NULL,
				foxy_transaction_shipping_zip VARCHAR(10) NULL,
				foxy_transaction_shipping_country VARCHAR(50) NULL
			)";
		$wpdb->query($sql);

		//create custom status table
		$sql = "CREATE TABLE " . WP_TRANSACTION_STATUS_TABLE . " (
				foxy_transaction_status INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_transaction_status_description VARCHAR(50) NULL,
				foxy_transaction_status_email_flag tinyint(1) NOT NULL DEFAULT '0',
				foxy_transaction_status_email_subject TEXT NULL,
				foxy_transaction_status_email_body TEXT NULL,
				foxy_transaction_status_email_tracking tinyint(1) NOT NULL DEFAULT '0'
			)";
		$wpdb->query($sql);
		//insert the default category
		$sql = "INSERT INTO " . WP_TRANSACTION_STATUS_TABLE . " (foxy_transaction_status, foxy_transaction_status_description) values ('1', 'Uncategorized')";
		$wpdb->query($sql);

		//create transaction note table
		$sql = "CREATE TABLE " . WP_TRANSACTION_NOTE_TABLE . " (
					foxy_transaction_note_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					foxy_transaction_id INT(11) NOT NULL,
					foxy_transaction_note TEXT NOT NULL,
					foxy_transaction_entered_by VARCHAR(30),
					foxy_transaction_date_entered DATE
				)";
		$wpdb->query($sql);

		//create sync table to keep track of when the last time we synched
		$sql = "CREATE TABLE " . WP_TRANSACTION_SYNC_TABLE . " (
				foxy_transaction_sync_date DATE,
				foxy_transaction_sync_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			)";
		$wpdb->query($sql);
		//insert default value
		$sql = "INSERT INTO " . WP_TRANSACTION_SYNC_TABLE . " (foxy_transaction_sync_date, foxy_transaction_sync_timestamp ) values ('1900-01-01', now())";
		$wpdb->query($sql);
	}
}

function SyncTransactions()
{
	global $wpdb;
	//get last date we synced, if it's a new sync we start at 1900, if it's a current running system then we take the last date and subtract a day
	$sql = "SELECT CASE foxy_transaction_sync_date WHEN '1900-01-01' THEN '1900-01-01' ELSE DATE_SUB(foxy_transaction_sync_date, INTERVAL 1 DAY) END as LastSync, DATE_FORMAT(NOW(), '%Y-%m-%d') as CurrentDate FROM " . WP_TRANSACTION_SYNC_TABLE;
	$dr = $wpdb->get_row($sql);

	//use that date to query for new transactions that have happened since then
	$foxyStoreURL = get_option('foxycart_storeurl');
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_list";
	$foxyData["transaction_date_filter_begin"] = $dr->LastSync;
	$foxyData["transaction_date_filter_end"] = $dr->CurrentDate;
	$SearchResults = curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	//print_r($foxyXMLResponse);
	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->transactions->transaction as $t)
		{
			//check if they exist in our db already, if not insert them with a unprocessed status
			$sql = "INSERT IGNORE INTO " . WP_TRANSACTION_TABLE .
				  " SET foxy_transaction_id = '" . $t->id . "'" .
				  ", foxy_transaction_status = '1'" .
				  ", foxy_transaction_first_name='" . $t->customer_first_name . "'" .
				  ", foxy_transaction_last_name='" . $t->customer_last_name . "'" .
				  ", foxy_transaction_email='" . $t->customer_email . "'";

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
		}
	}
	//update our last sync timestamp(s)
	$sql = "UPDATE " . WP_TRANSACTION_SYNC_TABLE . " SET foxy_transaction_sync_date = '" . $dr->CurrentDate . "', foxy_transaction_sync_timestamp = now()";
	$wpdb->query($sql);
}


?>