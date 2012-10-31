<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'order_management_postback');

function order_management_postback()
{
	global $wpdb;
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "order-management")
	{
		$TransactionID = foxypress_FixGetVar('transaction');
		$Page_Action = foxypress_FixGetVar("action", "");
		$BlogID = foxypress_FixGetVar("b", "");
		$switched_blog = false;
		//security check
		if(foxypress_IsMultiSite())
		{
			if($BlogID != "" && $BlogID != "0" && $BlogID != $wpdb->blogid && !foxypress_IsMainBlog())
			{
				header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=order-management");
			}
		}
		//save note
		if(isset($_POST['foxy_om_note_submit']))
		{
			$switched_blog = false;
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
			$current_user = wp_get_current_user();
			$NoteText = foxypress_FixPostVar("foxy_om_note");
			$sql = "insert into " . $wpdb->prefix . "foxypress_transaction_note (foxy_transaction_id, foxy_transaction_note, foxy_transaction_entered_by, foxy_transaction_date_entered) values ('$TransactionID', '$NoteText', '$current_user->user_login', CURDATE())";
			$wpdb->query($sql);
			if($switched_blog) { restore_current_blog(); }
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID. "&mode=detail&b=" .$BlogID);
		}
		//delete note
		else if($Page_Action == "deletenote" && foxypress_FixGetVar("note", "") != "")
		{
			$switched_blog = false;
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
			$NoteID = foxypress_FixGetVar("note", "");
			$sql = "delete from  " . $wpdb->prefix . "foxypress_transaction_note WHERE foxy_transaction_id = '$TransactionID' and foxy_transaction_note_id='$NoteID'";
			$wpdb->query($sql);
			if($switched_blog) { restore_current_blog(); }
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=order-management&transaction=" . $TransactionID . "&mode=detail&b=" .$BlogID);
		}
		else if(isset($_POST['foxy_om_submit_Address']))
		{
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
			$BillingAddress1 = foxypress_FixPostVar("foxy_om_txtBillingAddress1");
			$BillingAddress2 = foxypress_FixPostVar("foxy_om_txtBillingAddress2");
			$BillingCity = foxypress_FixPostVar("foxy_om_txtBillingCity");
			$BillingState = foxypress_FixPostVar("foxy_om_txtBillingState");
			$BillingZip = foxypress_FixPostVar("foxy_om_txtBillingZip");
			$BillingCompany = foxypress_FixPostVar("foxy_om_txtBillingCompany");
			$ShippingAddress1 = foxypress_FixPostVar("foxy_om_txtShippingAddress1");
			$ShippingAddress2 = foxypress_FixPostVar("foxy_om_txtShippingAddress2");
			$ShippingCity = foxypress_FixPostVar("foxy_om_txtShippingCity");
			$ShippingState = foxypress_FixPostVar("foxy_om_txtShippingState");
			$ShippingZip = foxypress_FixPostVar("foxy_om_txtShippingZip");
			$ShippingCompany = foxypress_FixPostVar("foxy_om_txtShippingCompany");
			$updateSQL = "update " . $wpdb->prefix ."foxypress_transaction" . "
						set foxy_transaction_billing_address1 = '$BillingAddress1'
							,foxy_transaction_billing_address2 = '$BillingAddress2'
							,foxy_transaction_billing_city = '$BillingCity'
							,foxy_transaction_billing_state = '$BillingState'
							,foxy_transaction_billing_zip = '$BillingZip'
							,foxy_transaction_billing_company = '$BillingCompany'
							,foxy_transaction_shipping_address1 = '$ShippingAddress1'
							,foxy_transaction_shipping_address2 = '$ShippingAddress2'
							,foxy_transaction_shipping_city = '$ShippingCity'
							,foxy_transaction_shipping_state = '$ShippingState'
							,foxy_transaction_shipping_zip = '$ShippingZip'
							,foxy_transaction_shipping_company = '$ShippingCompany'
						where foxy_transaction_id = '$TransactionID'";
			$wpdb->query($updateSQL);
			if($switched_blog) { restore_current_blog(); }
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID. "&mode=detail&b=" .$BlogID);
		}
		else if(isset($_POST['foxy_om_submit_customer']))
		{
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
			$Cust_LastName = foxypress_FixPostVar("foxy_om_cust_lastname");
			$Cust_FirstName = foxypress_FixPostVar("foxy_om_cust_firstname");
			$Cust_Email = foxypress_FixPostVar("foxy_om_cust_email");
			$updateSQL = "update " . $wpdb->prefix ."foxypress_transaction" . "
						set foxy_transaction_last_name = '$Cust_LastName'
							,foxy_transaction_first_name = '$Cust_FirstName'
							,foxy_transaction_email = '$Cust_Email'
						where foxy_transaction_id = '$TransactionID'";
			$wpdb->query($updateSQL);
			if($switched_blog) { restore_current_blog(); }
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID. "&mode=detail&b=" .$BlogID);
		}
		else if($Page_Action == "previewslip")
		{
			foxypress_PrintPackingSlip(true, false);
		}
		else if($Page_Action == "printpartialslip")
		{
			foxypress_PrintPackingSlip(true, true);
		}
		else if($Page_Action == "printslip")
		{
			foxypress_PrintPackingSlip(false, true);
		}
		else if(isset($_POST['foxy_om_email_template_submit']))
		{
			$templateChosen = foxypress_FixPostVar("foxy_om_ddl_email_template");
			$TransactionID = foxypress_FixGetVar("transaction", "");
			foxypress_SendEmailTemplate($templateChosen, $TransactionID);
		}
	}
}

function order_management_page_load()
{
	//modes - list, detail, search
	global $wpdb;
	//check the post first, if we have nothing check the query string, if nothing is there just default to list view
	$Page_Mode = (foxypress_FixPostVar("foxy_om_mode", "") != "") ? foxypress_FixPostVar("foxy_om_mode", "") : foxypress_FixGetVar("mode", "list");
	$Page_Action = foxypress_FixGetVar("action", "");
	$Page_URL = get_admin_url() . "edit.php";
	$BlogID = foxypress_FixGetVar("b", "");
	$PageStart = foxypress_GetPaginationStart();
	if($Page_Action == "sync")
	{
		SyncTransactions(false, $PageStart);
		exit;
	}
	else if($Page_Action == "syncall")
	{
		SyncTransactions(true, $PageStart);
		exit;
	}
	Begin_Foxy_Order_Management();
	if($Page_Mode == "list")
	{
		$List_Status = foxypress_FixGetVar("status", "");
		if($List_Status == "") //general view, list all of the statuses
		{
			$sql = "SELECT ts.foxy_transaction_status
						  ,ts.foxy_transaction_status_description
						  ,coalesce(lj.StatusCount, 0) as Count
					FROM " . $wpdb->prefix . "foxypress_transaction_status ts
					left join (select foxy_transaction_status as StatusID
									 , count(*) as StatusCount
							   from " . $wpdb->prefix ."foxypress_transaction
							   where foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id") . "
							   group by foxy_transaction_status )
						lj on ts.foxy_transaction_status = lj.StatusID";
			$TransactionStatuses = $wpdb->get_results($sql);
			_e('<h3>View Orders</h3>', 'foxypress');
			echo("<table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Status', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Status Quantity', 'foxypress') . "</th>
						</tr>
					</thead>");
			if( !empty($TransactionStatuses) )
			{
				foreach ( $TransactionStatuses as $ts )
				{
					echo("<tr>
							<td>
								<a href=\"" . get_admin_url(false) . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&status=" . $ts->foxy_transaction_status . "&mode=list&b=" .  $wpdb->blogid . "\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>
							</td>
							<td>" . $ts->Count . "</td>
						  </tr>");
				}
			}
			echo("</table>");

			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				$MainBlogID = $wpdb->blogid;
				$switched_blog = false;
				//paging
				$drRows = $wpdb->get_row("SELECT count(blog_id) as RowCount FROM $wpdb->blogs WHERE blog_id != '$MainBlogID'");
				$limit = 10;
				//$targetpage = foxypress_GetCurrentPageURL(true);
				$targetpage = get_admin_url() . "edit.php";
				$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
				$pos = strrpos($targetpage, "?");
				if ($pos === false) {
					$targetpage .= "?";
				}
				$pageNumber = foxypress_FixGetVar('fp_pn');
				$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;
				//get blogs
				$blogs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE blog_id != '$MainBlogID' LIMIT $start, $limit"));
				foreach ($blogs as $blog)
				{
					if ( $blog->blog_id != $wpdb->blogid )
					{
						switch_to_blog($blog->blog_id);
						$switched_blog = true;
					}
					//get transactions
					$sql = "SELECT ts.foxy_transaction_status
						  ,ts.foxy_transaction_status_description
						  ,coalesce(lj.StatusCount, 0) as Count
					FROM " . $wpdb->prefix . "foxypress_transaction_status ts
					left join (select foxy_transaction_status as StatusID
									 , count(*) as StatusCount
							   from " . $wpdb->prefix ."foxypress_transaction
							   where foxy_blog_id = '" . $blog->blog_id . "'
							   group by foxy_transaction_status )
						lj on ts.foxy_transaction_status = lj.StatusID";
					$TransactionStatuses = $wpdb->get_results($sql);
					_e("<h3><a href=\"javascript:ToggleSubSiteOrder(" . $blog->blog_id . ");\" id=\"view_sub_order_" . $blog->blog_id . "\" class=\"noDecoration\">+</a> View Sub Site Orders - " .  $blog->path . "</h3>");
					echo("<div id=\"sub_order_" . $blog->blog_id . "\" class=\"Hide\">
							  <table class=\"widefat page fixed\">
								<thead>
									<tr>
										<th class=\"manage-column\" scope=\"col\">" . __('Order Status', 'foxypress') . "</th>
										<th class=\"manage-column\" scope=\"col\">" . __('Status Quantity', 'foxypress') . "</th>
									</tr>
								</thead>");
					if( !empty($TransactionStatuses) )
					{
						foreach ( $TransactionStatuses as $ts )
						{
							echo("<tr>
									<td>
										<a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&status=" . $ts->foxy_transaction_status . "&mode=list&b=" .  $blog->blog_id . "\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>
									</td>
									<td>" . $ts->Count . "</td>
								  </tr>");
						}
					}
					echo("	</table> <br />
					      </div>");
				}//end loop through blogs
				if($switched_blog) { switch_to_blog($MainBlogID); }
				//pagination
				if($drRows->RowCount > $limit)
				{
					$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
					echo ($Pagination);
				}
				echo('<div class="separator"></div>');
			} //end if multisite

			//get last sync date
		$foxypress_transaction_sync_timestamp = get_option("foxypress_transaction_sync_timestamp");
			_e('<h3>Sync Transactions </h3>', 'foxypress');
			echo(__('Please click the button below to sync your latest transactions from FoxyCart.', 'foxypress') . "<br />
				   <form id=\"syncForm\" name=\"syncForm\" method=\"POST\">
					<div>
						<input type=\"button\" id=\"foxy_om_sync_now\"  name=\"foxy_om_sync_now\" value=\"" . __('Sync Latest Transactions', 'foxypress') . "\" onclick=\"SyncTransactionsJS('" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=order-management', '" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&action=sync', '" . plugins_url() . "/foxypress/img/ajax-loader.gif', true);\" />
						<input type=\"button\" id=\"foxy_om_sync_all\"  name=\"foxy_om_sync_all\" value=\"" . __('Sync All Transactions', 'foxypress') . "\" onclick=\"SyncTransactionsJS('" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management', '" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&action=syncall', '" . plugins_url() . "/foxypress/img/ajax-loader.gif', true);\" />
						<span id=\"foxy_om_sync\"></span>
						<br><i>" . __('Last Synchronized', 'foxypress') . ": " . $foxypress_transaction_sync_timestamp . "</i>
					</div>
				  </form>");
		}
		else
		{
			$switched_blog = false;
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if ( $BlogID != "0" && $BlogID != $wpdb->blogid )
				{
					switch_to_blog($BlogID);
					$switched_blog = true;
				}
			}

			$Transaction_Type = foxypress_FixGetVar("transactiontype", "");
			$basePage =  $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&status=" . $List_Status . "&mode=list";
			$TransactionFilter = ($Transaction_Type == "1") ? " and foxy_transaction_is_test='1'" : (($Transaction_Type == "0") ? " and foxy_transaction_is_test='0'" : "");
			$Status = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_transaction_status WHERE foxy_transaction_status = '$List_Status'");
			if ( !empty($Status) ) {
				_e('<h3>View your ') . $Status->foxy_transaction_status_description . _e(' orders:</h3>', 'foxypress');
			}

			echo("<form id=\"foxy_om_filter_form\" name=\"foxy_om_filter_form\" method=\"POST\" \">
				 	<select id=\"foxy_om_transaction_type_filter\" name=\"foxy_om_transaction_type_filter\" onChange=\"RedirectFilter()\">
						<option value=\"" . $basePage  . "\"" . (($Transaction_Type == "") ? "selected='selected'" : "") . ">" . __('All Transactions', 'foxypress') . "</option>
						<option value=\"". $basePage . "&transactiontype=0" . "\"" . (($Transaction_Type == "0") ? "selected='selected'" : "") . ">" . __('Live Transactions', 'foxypress') . "</option>
						<option value=\"". $basePage . "&transactiontype=1" ."\"" . (($Transaction_Type == "1") ? "selected='selected'" : ""). ">" . __('Test Transactions', 'foxypress') . "</option>
				 	</select>
				 </form> <br>");
			$targetpage = $basePage . "&transactiontype=" . $Transaction_Type;
			$drRows = $wpdb->get_row("SELECT COUNT(*) as RowCount
									 FROM " . $wpdb->prefix ."foxypress_transaction
									 WHERE foxy_transaction_status = '$List_Status'
									 AND foxy_blog_id = " . ((foxypress_IsMultiSite()) ? "'" . $wpdb->blogid . "'" : "foxy_blog_id") . "
									 $TransactionFilter");
			$limit = 25;
			$pageNumber = foxypress_FixGetVar('pagenum');
			$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;
			$Transactions = $wpdb->get_results("SELECT *
												FROM " . $wpdb->prefix ."foxypress_transaction
												WHERE foxy_transaction_status = '$List_Status'
												AND foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id") . "
												$TransactionFilter
												order by foxy_transaction_id desc
												LIMIT $start, $limit");
			echo("<select onchange=\"HandleBulkAction(this.value, '" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&b=" . $wpdb->blogid . "&action=printslip')\" id=\"foxypress_bulk_select\">
					<option value=\"\">" . __('Bulk Actions', 'foxypress') . "</option>
					<option value=\"bulk_print\">" . __('Print Packing Slip(s)', 'foxypress') . "</option>
				</select><br /><br />");
			echo("<table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"small-column\" scope=\"col\"><input type=\"checkbox\" name=\"foxypress_bulk_all\" onclick=\"BulkSelectAll(this.checked);\" /></th>
							<th class=\"manage-column\" scope=\"col\">" . __('Transaction ID', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Date of Order', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Name', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Email', 'foxypress') . "</th>
							<th class=\"medium-column\" scope=\"col\">" . __('Packing Slip', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Tracking', 'foxypress') . "</th>
						</tr>
					</thead>");
			if ( !empty($Transactions) ) {
				foreach ( $Transactions as $t ) {
					echo("<tr>
							<td><input type=\"checkbox\" name=\"foxypress_bulk_check[]\" id=\"foxypress_bulk_check\" value=\"" . $t->foxy_transaction_id . "\" /></td>
							<td>
								<a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $t->foxy_transaction_id . "&b=" . $t->foxy_blog_id . "&mode=detail\">" . $t->foxy_transaction_id . "</a>
							</td>
							<td>" . $t->foxy_transaction_date . "</td>
							<td>" . $t->foxy_transaction_last_name . ", " . $t->foxy_transaction_first_name . "</td>
							<td>" . $t->foxy_transaction_email . "</td>
							<td><a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $t->foxy_transaction_id . "&b=" . $t->foxy_blog_id . "&mode=createslip\">Create Slip</a></td>
							<td>" . $t->foxy_transaction_trackingnumber . "</td>
						  </tr>");
				}
			}
			else
			{
				_e("<tr><td colspan=\"4\">There are currently no orders with this transaction status</td></tr>", "foxypress");
			}
			echo("</table>");
			if($drRows->RowCount > $limit)
			{
				$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage);
				echo("<br />" . $Pagination);
			}


			if($switched_blog) { restore_current_blog(); }
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
			$switched_blog = false;
			if(foxypress_IsMultiSite() && foxypress_IsMainBlog())
			{
				if($wpdb->blogid != $BlogID)
				{
					$switched_blog = true;
					switch_to_blog($BlogID);
				}
			}
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
				$tRow = $wpdb->get_row("select * from " . $wpdb->prefix ."foxypress_transaction where foxy_transaction_id = '$TransactionID'");

				//get statuses for dropdown
				$StatusList = "";
				$TransactionStatuses = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_transaction_status");
				if( !empty($TransactionStatuses) )
				{
					foreach ( $TransactionStatuses as $ts )
					{
						if ($tRow->foxy_transaction_status == $ts->foxy_transaction_status)
						{
							$trans_status_desc = stripslashes($ts->foxy_transaction_status_description);
						}

						$StatusList .= "<option value=\"" . $ts->foxy_transaction_status . "\"" . (($tRow->foxy_transaction_status == $ts->foxy_transaction_status) ? " selected='selected'" : "") . ">" . stripslashes($ts->foxy_transaction_status_description) . "</option>";
					}
				}
				$HasSameBillingAndShipping = ($tRow->foxy_transaction_shipping_address1 == "");
				echo("<ul class=\"subsubsub\">
						<li class=\"order-management\"><a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management\">Order Management</a> |</li>
						<li class=\"" . $trans_status_desc . "\"><a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&status=" . $tRow->foxy_transaction_status . "&mode=list&b=" . $tRow->foxy_blog_id . "\">" . $trans_status_desc . "</a> |</li>
						<li class=\"order-number\">" . $foxyXMLResponse->transaction->id . "</li>
					</ul>
					<br><br><h3>" . __('Transaction Details', 'foxypress') . "</h3>
					<div>
						Transaction ID: " . $foxyXMLResponse->transaction->id . " &nbsp; Date: " . $foxyXMLResponse->transaction->transaction_date . "
					</div> <br>
					<div>
						<form method=\"POST\" name=\"statusForm\" id=\"statusForm\">
							<table>
								<tr>
									<td align=\"right\">" . __('Status', 'foxypress') . "</td>
									<td>
										<select id=\"foxy_om_ddl_status\" name=\"foxy_om_ddl_status\">"
										. $StatusList .
										"</select>
									</td>
								</tr>
								<tr>
									<td align=\"right\">" . __('Tracking Number', 'foxypress') . "</td>
									<td><input type=\"text\" name=\"foxy_om_tracking_number\" id=\"foxy_om_tracking_number\" value=\"" .  $tRow->foxy_transaction_trackingnumber . "\" /></td>
								</tr>
								<tr>
									<td align=\"right\">" . __('RMA Number', 'foxypress') . "</td>
									<td><input type=\"text\" name=\"foxy_om_rma_number\" id=\"foxy_om_rma_number\" value=\"" .  $tRow->foxy_transaction_rmanumber . "\" /></td>
								</tr>
								<tr>
									<td><input type=\"hidden\" name=\"foxy_user_session_id\" id=\"foxy_user_session_id\" value=\"" . session_id() . "\" /><input type=\"hidden\" name=\"foxy_baseurl\" id=\"foxy_baseurl\" value=\"" . plugins_url() . "/foxypress/ajax.php" . "\" /><input type=\"hidden\" name=\"foxy_transaction_submit_id\" id=\"foxy_transaction_submit_id\" value=\"" . $TransactionID . "\" /></td>
									<td><input type=\"submit\" id=\"foxy_om_transaction_submit\" name=\"foxy_om_transaction_submit\" value=\"Save\" /> <img src=\"" . plugins_url() . "/foxypress/img/ajax-loader.gif\" id=\"foxypress_transaction_submit_loading\" name=\"foxypress_transaction_submit_loading\" style=\"display:none;\" /><span id=\"transaction_submit_msg\"></span></td>
								</tr>
							</table>
						</form>
					</div>
					<div id=\"divViewCustomer\">
						<h3>" . __('Customer Details', 'foxypress') . " <a style=\"font-weight:normal;font-size:12px;\" href=\"javascript:ToggleEditCustomer();\">" . __('(edit)', 'foxypress') . "</a></h3>
                    	" . __('Name', 'foxypress') . ": " . $tRow->foxy_transaction_last_name  . ", " . $tRow->foxy_transaction_first_name .
						"<br> " . __('Email', 'foxypress') . ": " . $tRow->foxy_transaction_email .
						"<br> " . __('Phone', 'foxypress') . ": " . $foxyXMLResponse->transaction->customer_phone .
                    "</div>
                    <div id=\"divEditCustomer\" class=\"Hide\">
						<form name=\"CustomerForm\" id=\"CustomerForm\" method=\"POST\">
							<div><h3>" . __('Customer Details', 'foxypress') . " <a style=\"font-weight:normal;font-size:12px;\" href=\"javascript:ToggleEditCustomer();\">" . __('(cancel)', 'foxypress') . "</a></h3></div>
							<table>
								<tr>
									<td>" . __('Name', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_last_name . "\" id=\"foxy_om_cust_lastname\" name=\"foxy_om_cust_lastname\" /> <input type=\"text\" value=\"" . $tRow->foxy_transaction_first_name . "\" id=\"foxy_om_cust_firstname\" name=\"foxy_om_cust_firstname\" /></td>
								</tr>
								<tr>
									<td>" . __('Email', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_email . "\" id=\"foxy_om_cust_email\" name=\"foxy_om_cust_email\" style=\"width:200px;\" /></td>
								</tr>
							</table>
							<div><input type=\"submit\" id=\"foxy_om_submit_customer\" name=\"foxy_om_submit_customer\" value=\"" . __('Save Customer Information', 'foxypress') . "\" /></div>
						</form>
						</td>
						</tr>
						</table>
					</div><br><br>
					<div id=\"divViewAddress\">
						<table>
							<tr>
								<td valign=\"top\" style=\"padding-right:30px;\">
								<div>
									<b>" . __('Billing Address', 'foxypress') . "</b> <a href=\"javascript:ToggleEdit();\">" . __('(edit)', 'foxypress') . "</a> <br />" .
									$foxyXMLResponse->transaction->customer_last_name . " " . $foxyXMLResponse->transaction->customer_first_name . "<br />" .
									$tRow->foxy_transaction_billing_company . "<br />" .
									$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
									$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip . " " . $tRow->foxy_transaction_billing_country .
								"</div>
								</td>
								<td valign=\"top\">
								<div>
									<b>" . __('Shipping Address', 'foxypress') . "</b> <a href=\"javascript:ToggleEdit();\">" . __('(edit)', 'foxypress') . "</a><br />" .
									(($foxyXMLResponse->transaction->shipping_last_name!="") ? $foxyXMLResponse->transaction->shipping_last_name : $foxyXMLResponse->transaction->customer_last_name ) . " " .  (($foxyXMLResponse->transaction->shipping_first_name!="") ? $foxyXMLResponse->transaction->shipping_first_name : $foxyXMLResponse->transaction->customer_first_name ) . "<br />" .
									(($tRow->foxy_transaction_shipping_company!="") ? $tRow->foxy_transaction_shipping_company : $tRow->foxy_transaction_billing_company) . "<br />" .
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
							<div><b>" . __('Billing Address', 'foxypress') . "</b> <a href=\"javascript:ToggleEdit();\">" . __('(cancel)', 'foxypress') . "</a></div>
							<table>
								<tr>
									<td>" . __('Billing Company', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_company . "\" id=\"foxy_om_txtBillingCompany\" name=\"foxy_om_txtBillingCompany\" /></td>
								</tr>
								<tr>
									<td>" . __('Address 1', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_address1 . "\" id=\"foxy_om_txtBillingAddress1\" name=\"foxy_om_txtBillingAddress1\" /></td>
								</tr>
								<tr>
									<td>" . __('Address 2', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_address2 . "\" id=\"foxy_om_txtBillingAddress2\" name=\"foxy_om_txtBillingAddress2\" /></td>
								</tr>
								<tr>
									<td>" . __('City, State, Zip', 'foxypress') . "</td>
									<td>
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_city . "\" id=\"foxy_om_txtBillingCity\" name=\"foxy_om_txtBillingCity\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_state . "\" id=\"foxy_om_txtBillingState\" name=\"foxy_om_txtBillingState\" maxlength=\"2\" size=\"4\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_billing_zip . "\" id=\"foxy_om_txtBillingZip\" name=\"foxy_om_txtBillingZip\" maxlength=\"10\" size=\"10\" />
									</td>
								</tr>
							</table><br>
							</td>
								<td valign=\"top\">
							<div><b>" . __('Shipping Address', 'foxypress') . "</b> <a href=\"javascript:ToggleEdit();\">" . __('(cancel)', 'foxypress') . "</a></div>
							<table>
								<tr>
									<td>" . __('Shipping Company', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_company . "\" id=\"foxy_om_txtShippingCompany\" name=\"foxy_om_txtShippingCompany\" /></td>
								</tr>
								<tr>
									<td>" . __('Address 1', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_address1 . "\" id=\"foxy_om_txtShippingAddress1\" name=\"foxy_om_txtShippingAddress1\" /></td>
								</tr>
								<tr>
									<td>" . __('Address 2', 'foxypress') . "</td>
									<td><input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_address2 . "\" id=\"foxy_om_txtShippingAddress2\" name=\"foxy_om_txtShippingAddress2\" /></td>
								</tr>
								<tr>
									<td>" . __('City, State, Zip', 'foxypress') . "</td>
									<td>
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_city . "\" id=\"foxy_om_txtShippingCity\" name=\"foxy_om_txtShippingCity\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_state . "\" id=\"foxy_om_txtShippingState\" name=\"foxy_om_txtShippingState\" maxlength=\"2\" size=\"4\" />
										<input type=\"text\" value=\"" . $tRow->foxy_transaction_shipping_zip . "\" id=\"foxy_om_txtShippingZip\" name=\"foxy_om_txtShippingZip\" maxlength=\"10\" size=\"10\" />
									</td>
								</tr>
							</table>
							<div><input type=\"submit\" id=\"foxy_om_submit_Address\" name=\"foxy_om_submit_Address\" value=\"" . __('Save Address Information', 'foxypress') . "\" /></div>
						</form>
						</td>
						</tr>
						</table>
					</div>
					<br>");

				//show transaction info
				echo("<div><b>" . __('Transaction Details', 'foxypress') . "</b></div>
					   <table>
						<tr>");
				$i=1;
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
						$options .= "<a href=\"" . $DownloadURL . "\">" . __('Downloadable Link', 'foxypress') . "</a><br />";
						$options .= "Download Count: <span id=\"foxypress_downloadable_count\">" . $dt->download_count . "</span> <a href=\"javascript:ResetDownloadCount('" . plugins_url() . "/foxypress/ajax.php" . "', '" . session_id() . "', '" . $dt->downloadable_id . "', '" . $dt->download_transaction_id . "');\">Reset</a> <img src=\"" . plugins_url() . "/foxypress/img/ajax-loader.gif\" id=\"foxypress_downloadable_loading\" name=\"foxypress_downloadable_loading\" style=\"display:none;\" /><br />";
					}

					$ProductCode = $td->product_code;
					$ProductImage = "";

					if($Inventory_ID != "")
					{
						$ProductImage = "<img src=\"" . foxypress_GetMainInventoryImage($Inventory_ID) . "\" style=\"width: 100px; \"/><br />";
					}
					echo("<td style='padding-right:45px;' valign='top'>"
								. $ProductImage .
								"Product: " . $td->product_name . "<br>" .
								"Price: " . foxypress_FormatCurrency($td->product_price) . "<br>" .
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
					if($cf->custom_field_name != "blog_id" && $cf->custom_field_name != "m_id" && $cf->custom_field_name != "affiliate_id")
					{
						$HiddenFields .= "<div>" . $cf->custom_field_name . ": " . $cf->custom_field_value . "</div>";
					}
				}
				if($HiddenFields != "")
				{
					_e("<div><b>Hidden Fields</b></div>", "foxypress");
					_e($HiddenFields);
					_e("<br>");
				}

				_e("<div><b>Transaction Totals</b></div>" .
				   "<div>Product Total: " . foxypress_FormatCurrency($foxyXMLResponse->transaction->product_total) . "</div>" .
				   "<div>Tax Total: " . foxypress_FormatCurrency($foxyXMLResponse->transaction->tax_total) . "</div>" .
				   "<div>Shipping Total: " . foxypress_FormatCurrency($foxyXMLResponse->transaction->shipping_total) . "</div>" .
				   "<div>Order Total: " . foxypress_FormatCurrency($foxyXMLResponse->transaction->order_total) . "</div>" .
				   "<div>Credit Card Type: " . $tRow->foxy_transaction_cc_type . "</div>", "foxypress");

				//show notes
				_e("<div><h3>Notes</h3></div>", "foxypress");
				echo("<table class=\"widefat page fixed\">
						<thead>
							<tr>
								<th class=\"manage-column\" scope=\"col\">" . __('Note', 'foxypress') . "</th>
								<th class=\"manage-column\" scope=\"col\">" . __('Posted By', 'foxypress') . "</th>
								<th class=\"manage-column\" scope=\"col\">" . __('Date', 'foxypress') . "</th>
								<th class=\"manage-column\" scope=\"col\">&nbsp;</th>
							</tr>
						</thead>");
				$Notes = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'foxypress_transaction_note' . " WHERE foxy_transaction_id = '$TransactionID'");
				if(!empty($Notes))
				{
					foreach ( $Notes as $n ) {
						echo("<tr>
								<td>" .
										( (strlen(stripslashes($n->foxy_transaction_note)) > 50)
										  ? "<span id=\"foxy_note_" . $n->foxy_transaction_note_id . "\">" . foxypress_TruncateString(stripslashes($n->foxy_transaction_note), 50) . "</span> <script type=\"text/javascript\"> jQuery('#foxy_note_" . $n->foxy_transaction_note_id . "').qtip({ content: '" . str_replace(array("\r", "\r\n", "\n"), '<br />', $n->foxy_transaction_note) . "', show: 'mouseover', hide: 'mouseout', style : { name: 'dark', tip: 'bottomLeft' }, position : { corner: { target: 'topRight', tooltip: 'bottomLeft'} } }); </script>"
										  : stripslashes($n->foxy_transaction_note)
										 )
									  .
							   "</td>
								<td>" . $n->foxy_transaction_entered_by . "</td>
								<td>" . $n->foxy_transaction_date_entered . "</td>
								<td><a href=\"" . get_admin_url() . "edit.php?page=order-management&transaction=" . $TransactionID . "&mode=detail&action=deletenote&note=" . $n->foxy_transaction_note_id . "\" onclick=\"return confirm('Are you sure you want to delete this note?');\">Delete</a></td>
							  </tr>");
					}
				}
				else
				{
					echo("<tr><td colspan=\"4\">There are currently no notes</td></tr>");
				}
				echo("</table>");
				_e("<h3>New Note</h3>", "foxypress");
				//show form for new notes
				echo("<form name=\"noteForm\" id=\"noteform\" method=\"POST\">
					   	<textarea id=\"foxy_om_note\" name=\"foxy_om_note\" cols=\"50\" rows=\"3\"></textarea> <br>
						<input type=\"submit\" name=\"foxy_om_note_submit\" id=\"foxy_om_note_submit\" value=\"" . __('Add Note', 'foxypress') . "\" />
					  </form>");

				//email
				//show general info
                _e("<h3>Send Email</h3>", "foxypress");
				$t_options=$wpdb->get_results("SELECT * FROM " . $wpdb->prefix ."foxypress_email_templates");
				if(count($t_options)==0){
					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s','manage-emails', 'new');
					_e("You do not have any email templates defined.  Add one <a href='" . $destination_url . "'>here</a>.", "foxypress");
				}else{
					$PostURL = get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID . "&b=" . $BlogID . "&mode=detail#email";
					if(!isset($_POST['foxy_om_email_template_submit'])) { echo "<a name=\"email\"></a>"; }
					echo "<form method=\"POST\" name=\"sendEmailForm\" id=\"sendEmailForm\" action=\"" . $PostURL . "\">
							" . __('Select Template', 'foxypress') . ":
							<select name='foxy_om_ddl_email_template' id='foxy_om_ddl_email_template'>";
					foreach ( $t_options as $te )
					{
						echo "<option value='".$te->email_template_id."' " . ( (foxypress_FixPostVar("foxy_om_ddl_email_template") == $te->email_template_id) ? "selected=\"selected\"" : ""  ) . ">".$te->foxy_email_template_name."</option>";
					}
					echo "	</select>" .
						 "	<input type=\"submit\" id=\"foxy_om_email_template_\" name=\"foxy_om_email_template_\" value=\"Ok\" />";
				}
				if(isset($_POST['foxy_om_email_template_']))
				{
					$templateDetails=$wpdb->get_row("SELECT * FROM " . $wpdb->prefix ."foxypress_email_templates where email_template_id = '" . foxypress_FixPostVar("foxy_om_ddl_email_template") . "'");
					echo "<br /><br /><div>" . $templateDetails->foxy_email_template_email_body . "</div>";
					if(preg_match_all("/{{custom_field_.*?}}/", $templateDetails->foxy_email_template_email_body, $matches))
					{
						echo "<table>";
						foreach($matches[0] as $match=>$custom_tag)
						{
							echo "<tr><td>" . $custom_tag . "</td><td><input type=\"text\" id=\"foxy_om_" . $custom_tag . "\" name =\"foxy_om_" . $custom_tag . "\" value=\"\" /></td></tr>";
						}
						echo "</table>";
					}
					echo "<br /><div><input type=\"submit\" id=\"foxy_om_email_template_submit\" name=\"foxy_om_email_template_submit\" value=\"" . __('Send Email', 'foxypress') . "\" /></div>";
				}
				echo "</form>";

			}//end check for success
			else
			{
				_e("Invalid Transaction ID", "foxypress");
			}

			//restore blog in case we are on the main blog viewing sub site orders
			if($switched_blog) { restore_current_blog(); }
		}
	}
	else if($Page_Mode == "search")
	{
		$SearchValue = foxypress_FixPostVar("foxy_om_search", "");
		ProcessSearch($SearchValue);
	}
	else if($Page_Mode == "createslip")
	{
		$TransactionID = foxypress_FixGetVar("transaction", "");
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
			$productList = "";
			$temp_inventory_id = "";
			foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
			{
				foreach($td->transaction_detail_options->transaction_detail_option as $opt)
				{
					if(strtolower($opt->product_option_name) == "inventory_id")
					{
						$temp_inventory_id = $opt->product_option_value;
					}
				}
				$productList .= "<li><input type=\"checkbox\" name=\"foxypress_packing_products[]\" value=\"" . $temp_inventory_id . "|" . $td->product_code . "\" checked=\"checked\" /> " . $td->product_name . " (" . $td->product_code . ")</li>";
			}
			_e('<h3>Create a Partial Packing slip</h3>', 'foxypress');
			_e('<p>Below is a wizard that will guide you through creating a packing slip.  You may not want to include all of your items, for a partial order perhaps, so feel free to modify your slip and preview it until it looks correct.</p>', 'foxypress');
	?>
		<form method="POST">
		     <div id="packing_wizard_container">
		        <ul class="packing_wizard_menu">
		            <li id="step-one" class="active"><?php _e('Step 1', 'foxypress'); ?></li>
		            <li id="step-two"><?php _e('Step 2', 'foxypress'); ?></li>
		            <li id="step-three"><?php _e('Step 3', 'foxypress'); ?></li>
		        </ul>

		        <span class="wizard_clear"></span>
		        <div class="wizard_tab_content step-one">
					<p><?php _e('You\'ve chosen to create a packing slip for', 'foxypress'); ?> <?php echo($TransactionID); ?>.</p>
					<p><?php _e('Please choose which products you\'d like to include on this packing slip, then click the next arrow below.', 'foxypress'); ?></p>
		            <ul>
						<?php echo($productList); ?>
					</ul>
					<img id="step-two-nav" class="wizard_nav next" src="<?php echo(plugins_url())?>/foxypress/img/next.png" />
		        </div>

		        <div class="wizard_tab_content step-two">
		            <p><?php _e('Now that you\'ve selected your products, please enter your custom message that will appear on the packing slip.', 'foxypress'); ?></p>
					<p class="label"><?php _e('Message/Notes', 'foxypress'); ?></p>
					<textarea class="message_notes" id="foxypress_packing_notes" name="foxypress_packing_notes"><?php echo(get_option('foxypress_packing_slip_footer_message')); ?></textarea>
					<img id="step-one-nav" class="wizard_nav prev" src="<?php echo(plugins_url())?>/foxypress/img/prev.png" />
					<img id="step-three-nav" class="wizard_nav next" src="<?php echo(plugins_url())?>/foxypress/img/next.png" />
		        </div>
		        <div class="wizard_tab_content step-three">
					<p><?php _e('All of your information has been collected and now you may preview your packing slip.  If something looks wrong, feel free to go backwards through the wizard and modify it.', 'foxypress'); ?></p>
					<?php echo("<a href=\"javascript:PrintSlip('" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID . "&b=" . $wpdb->blogid . "&action=previewslip');\">Preview Packing Slip</a>");?>
					<p><?php _e('Everything look ok?', 'foxypress'); ?></p>
					<p class="submit">
                    <input type="button" class="button-primary" id="btnFoxyPressPackingSlipWizardPrint" name="btnFoxyPressPackingSlipWizardPrint" value="<?php _e('Print Packing Slip') ?>" onclick="<?php echo("javascript:PrintSlip('" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $TransactionID . "&b=" . $wpdb->blogid . "&action=printpartialslip');return false;");?>" />
				</p>
					<img id="step-two-nav" class="wizard_nav prev" src="<?php echo(plugins_url())?>/foxypress/img/prev.png" />
		        </div>
		    </div>
		</form>
		  <script type="text/javascript" language="javascript">
			jQuery(document).ready(function(){
				jQuery(".packing_wizard_menu > li").click(function(e){
					switch(e.target.id){
						case "step-one":
							//change status & style menu
							jQuery("#step-one").addClass("active");
							jQuery("#step-two").removeClass("active");
							jQuery("#step-three").removeClass("active");
							//display selected division, hide others
							jQuery("div.step-one").fadeIn();
							jQuery("div.step-two").css("display", "none");
							jQuery("div.step-three").css("display", "none");
						break;
						case "step-two":
							//change status & style menu
							jQuery("#step-one").removeClass("active");
							jQuery("#step-two").addClass("active");
							jQuery("#step-three").removeClass("active");
							//display selected division, hide others
							jQuery("div.step-two").fadeIn();
							jQuery("div.step-one").css("display", "none");
							jQuery("div.step-three").css("display", "none");
						break;
						case "step-three":
							//change status & style menu
							jQuery("#step-one").removeClass("active");
							jQuery("#step-two").removeClass("active");
							jQuery("#step-three").addClass("active");
							//display selected division, hide others
							jQuery("div.step-three").fadeIn();
							jQuery("div.step-one").css("display", "none");
							jQuery("div.step-two").css("display", "none");
						break;
					}
					return false;
				});
				jQuery(".wizard_nav").click(function(e){
					switch(e.target.id){
						case "step-one-nav":
							//change status & style menu
							jQuery("#step-one").addClass("active");
							jQuery("#step-two").removeClass("active");
							jQuery("#step-three").removeClass("active");
							//display selected division, hide others
							jQuery("div.step-one").fadeIn();
							jQuery("div.step-two").css("display", "none");
							jQuery("div.step-three").css("display", "none");
						break;
						case "step-two-nav":
							//change status & style menu
							jQuery("#step-one").removeClass("active");
							jQuery("#step-two").addClass("active");
							jQuery("#step-three").removeClass("active");
							//display selected division, hide others
							jQuery("div.step-two").fadeIn();
							jQuery("div.step-one").css("display", "none");
							jQuery("div.step-three").css("display", "none");
						break;
						case "step-three-nav":
							//change status & style menu
							jQuery("#step-one").removeClass("active");
							jQuery("#step-two").removeClass("active");
							jQuery("#step-three").addClass("active");
							//display selected division, hide others
							jQuery("div.step-three").fadeIn();
							jQuery("div.step-one").css("display", "none");
							jQuery("div.step-two").css("display", "none");
						break;
					}
					//alert(e.target.id);
					return false;
				});
			});

			function PrintSlip(baseURL)
			{
				var values = new Array();
				jQuery.each(jQuery("input[name='foxypress_packing_products[]']:checked"), function() {
				  values.push(jQuery(this).val());
				});
				var customMessage = jQuery("#foxypress_packing_notes").val();
				var fullURL = baseURL + "&products=" + values + "&message=" + customMessage;
				window.open(fullURL);
			}
			</script>
		<?php
		}
		else
		{
			_e("Invalid Transaction ID", "foxypress");
		}
	}
	//check to see how much time has passed since the last time we synched. If it has been more than 10 minutes, sync it up.
	$NeedsSync = false;
	$sql = "select ( (unix_timestamp(now()) - unix_timestamp(option_value)) / 60 ) as Minutes FROM " . $wpdb->prefix . "options where option_name='foxypress_transaction_sync_timestamp'";
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

function foxypress_SendEmailTemplate($templateID, $transactionID)
{
	global $wpdb;
	$mailTemplate = $wpdb->get_row("select * from  " . $wpdb->prefix ."foxypress_email_templates where email_template_id='" . $templateID . "'");
	$transactionDetail = $wpdb->get_row("select * from " . $wpdb->prefix ."foxypress_transaction where foxy_transaction_id = '" . $transactionID . "'");
	//set up mail objects
	$mail_to = $transactionDetail->foxy_transaction_email;
	$mail_subject = $mailTemplate->foxy_email_template_subject;
    $mail_body = $mailTemplate->foxy_email_template_email_body;
	$mail_from = $mailTemplate->foxy_email_template_from;
	//replace fields
	$mail_body = str_replace("{{order_id}}", $transactionDetail->foxy_transaction_id, $mail_body);
	$mail_body = str_replace("{{customer_first_name}}", $transactionDetail->foxy_transaction_first_name, $mail_body);
	$mail_body = str_replace("{{customer_last_name}}", $transactionDetail->foxy_transaction_last_name, $mail_body);
	$mail_body = str_replace("{{customer_email}}", $transactionDetail->foxy_transaction_email, $mail_body);
	$mail_body = str_replace("{{tracking_number}}", $transactionDetail->foxy_transaction_trackingnumber, $mail_body);
	$mail_body = str_replace("{{customer_billing_address1}}", $transactionDetail->foxy_transaction_billing_address1, $mail_body);
	$mail_body = str_replace("{{customer_billing_address2}}", $transactionDetail->foxy_transaction_billing_address2, $mail_body);
	$mail_body = str_replace("{{customer_billing_city}}", $transactionDetail->foxy_transaction_billing_city, $mail_body);
	$mail_body = str_replace("{{customer_billing_state}}", $transactionDetail->foxy_transaction_billing_state, $mail_body);
	$mail_body = str_replace("{{customer_billing_zip}}", $transactionDetail->foxy_transaction_billing_zip, $mail_body);
	$mail_body = str_replace("{{customer_billing_country}}", $transactionDetail->foxy_transaction_billing_country, $mail_body);
	$mail_body = str_replace("{{customer_shipping_address1}}", $transactionDetail->foxy_transaction_shipping_address1, $mail_body);
	$mail_body = str_replace("{{customer_shipping_address2}}", $transactionDetail->foxy_transaction_shipping_address2, $mail_body);
	$mail_body = str_replace("{{customer_shipping_city}}", $transactionDetail->foxy_transaction_shipping_city, $mail_body);
	$mail_body = str_replace("{{customer_shipping_state}}", $transactionDetail->foxy_transaction_shipping_state, $mail_body);
	$mail_body = str_replace("{{customer_shipping_zip}}", $transactionDetail->foxy_transaction_shipping_zip, $mail_body);
	$mail_body = str_replace("{{customer_shipping_country}}", $transactionDetail->foxy_transaction_shipping_country, $mail_body);
	$mail_body = str_replace("{{order_date}}", $transactionDetail->foxy_transaction_date, $mail_body);
	$mail_body = str_replace("{{product_total}}", $transactionDetail->foxy_transaction_product_total, $mail_body);
	$mail_body = str_replace("{{tax_total}}", $transactionDetail->foxy_transaction_tax_total, $mail_body);
	$mail_body = str_replace("{{shipping_total}}", $transactionDetail->foxy_transaction_shipping_total, $mail_body);
	$mail_body = str_replace("{{order_total}}", $transactionDetail->foxy_transaction_order_total, $mail_body);
	$mail_body = str_replace("{{cc_type}}", $transactionDetail->foxy_transaction_cc_type, $mail_body);

	if(preg_match_all("/{{custom_field_.*?}}/", $mail_body, $matches))
	{
		foreach($matches[0] as $match=>$custom_tag)
		{
			$mail_body = str_replace($custom_tag, foxypress_FixPostVar("foxy_om_" . $custom_tag), $mail_body);
		}
	}

    foxypress_Mail($mail_to, $mail_subject, $mail_body, $mail_from);
	echo("<div class='updated' id='message'>" . __('Your email message has been successfully sent!', 'foxypress') . "</div>");
}

function foxypress_PrintPackingSlip($partialSlip, $printPage)
{
	global $wpdb;
	$TransactionID = foxypress_FixGetVar('transaction');
	$Products = explode(",", foxypress_FixGetVar('products'));
	if(foxypress_FixGetVar('message')!=""){
		$CustomMessage = foxypress_FixGetVar('message');
	}else{
		$CustomMessage = get_option('foxypress_packing_slip_footer_message');
	}

	if(!$printPage)
	{
		_e('<h3>Preview Your Packing Slip</h3>', 'foxypress');
		_e('<p>This is what your packing slip will look like:</p>', 'foxypress');
	}
	//get transaction
	$foxyStoreURL = get_option('foxycart_storeurl');
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_get";
	$foxyData["transaction_id"] = $TransactionID;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	if($foxyXMLResponse->result != "SUCCESS")
	{
		echo("Invalid Transaction ID");
	}
	else
	{
		$tRow = $wpdb->get_row("select * from " . $wpdb->prefix ."foxypress_transaction where foxy_transaction_id = '$TransactionID'");
		$HasSameBillingAndShipping = ($tRow->foxy_transaction_shipping_address1 == "");
		$HeaderImage = get_option('foxypress_packing_slip_header');
		$productList = "";
		$temp_inventory_id = "";

		foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
		{
			if($partialSlip)
			{
				foreach($td->transaction_detail_options->transaction_detail_option as $opt)
				{
					if(strtolower($opt->product_option_name) == "inventory_id")
					{
						$temp_inventory_id = $opt->product_option_value;
					}
					if(strtolower($opt->product_option_name) != "downloadable" && strtolower($opt->product_option_name) != "inventory_id")
					{
						$temp_inventory_option_name .= $opt->product_option_name . "|";
						$temp_inventory_option_value .= $opt->product_option_value . "|";
					}
				}
				foreach($Products as $product)
				{
					$temp_exploded_option_names = explode("|", $temp_inventory_option_name);
					$temp_exploded_option_values = explode("|", $temp_inventory_option_value);

					$temp_inventory_option_name = "";
					$temp_inventory_option_value = "";

					$options="";
					for($i = 0; $i < sizeof($temp_exploded_option_names)-1; $i++){
						$options .= $temp_exploded_option_names[$i] . " : " . $temp_exploded_option_values[$i] . "<br />";
					}

					$temp_exploded = explode("|", $product);
					if($temp_exploded[0] == $temp_inventory_id || $temp_exploded[1] == $td->product_code)
					{

						$productList .= "<div class=\"clearall\"></div>
								<div class=\"product\">
									<div class=\"sku\">" . $td->product_code . "</div>
									<div class=\"qty\">" .  $td->product_quantity . "</div>
									<div class=\"name\">" .  $td->product_name . " <br />" . $options . "</div>
								</div>";
						break;
					}
				}
			}
			else
			{
				foreach($td->transaction_detail_options->transaction_detail_option as $opt)
				{
					if(strtolower($opt->product_option_name) != "downloadable" && strtolower($opt->product_option_name) != "inventory_id")
					{
						$temp_inventory_option_name .= $opt->product_option_name . "|";
						$temp_inventory_option_value .= $opt->product_option_value . "|";
					}
				}
				$temp_exploded_option_names = explode("|", $temp_inventory_option_name);
				$temp_exploded_option_values = explode("|", $temp_inventory_option_value);

				$temp_inventory_option_name = "";
				$temp_inventory_option_value = "";

				$options="";
				for($i = 0; $i < sizeof($temp_exploded_option_names)-1; $i++){
					$options .= $temp_exploded_option_names[$i] . " : " . $temp_exploded_option_values[$i] . "<br />";
				}
				$productList .= "<div class=\"clearall\"></div>
							<div class=\"product\">
								<div class=\"sku\">" . $td->product_code . "</div>
								<div class=\"qty\">" .  $td->product_quantity . "</div>
								<div class=\"name\">" .  $td->product_name . " <br />" . $options . "</div>
							</div>";
			}
		}
	?>
	<style>
		@media print{
			body{ background-color:#FFFFFF; background-image:none; color:#000000 }
			.wrapper{width:600px;margin:0 auto;}
			.header{text-align:center;}
			.order_details{margin-top:10px;}
				.order_details span{font-weight:bold;}
			.customer_information{margin-top:30px;}
				.customer_information .bill_to{float:left;width:280px;margin-right:20px;}
				.customer_information .ship_to{float:left;width:280px;}

			.product_headers{}
				.product_headers div{font-weight:bold;float:left;margin-right:10px;}
				.product_headers div.sku{width:100px;text-align:center;}
				.product_headers div.qty{width:50px;text-align:center;}
				.product_headers div.name{width:250px;}
			.product{}
				.product div{float:left;margin-right:10px;}
				.product div.sku{width:100px;text-align:center;}
				.product div.qty{width:50px;text-align:center;}
				.product div.name{width:250px;}

			.custom_message span{font-weight:bold;}

			.breaker{margin-top:20px;margin-bottom:20px;}

			.clearall{clear:both;}
		}
		.wrapper{width:600px;margin:0 auto;}
		.header{text-align:center;}
		.order_details{margin-top:10px;}
		.order_details span{font-weight:bold;}
		.customer_information{margin-top:30px;}
			.customer_information .bill_to{float:left;width:280px;margin-right:20px;}
			.customer_information .ship_to{float:left;width:280px;}

		.product_headers{}
			.product_headers div{font-weight:bold;float:left;margin-right:10px;}
			.product_headers div.sku{width:100px;text-align:center;}
			.product_headers div.qty{width:50px;text-align:center;}
			.product_headers div.name{width:250px;}
		.product{}
			.product div{float:left;margin-right:10px;}
			.product div.sku{width:100px;text-align:center;}
			.product div.qty{width:50px;text-align:center;}
			.product div.name{width:250px;}

		.custom_message span{font-weight:bold;}

		.breaker{margin-top:20px;margin-bottom:20px;}

		.clearall{clear:both;}
	</style>
	<div class="wrapper">
        <?php if($HeaderImage != "") { echo("<div class=\"header\"><img src=\"" . $HeaderImage . "\" /></div>"); } ?>
		<div class="order_details">
			<span><?php _e('Order Date', 'foxypress'); ?></span> <?php echo($foxyXMLResponse->transaction->transaction_date); ?>
		</div>
		<div class="order_details">
			<span><?php _e('Order Number', 'foxypress'); ?></span> <?php echo($TransactionID); ?>
		</div>
		<div class="customer_information">
			<div class="bill_to">
            	<?php
				echo($foxyXMLResponse->transaction->customer_first_name . " " . $foxyXMLResponse->transaction->customer_last_name . "<br />" .
					  $tRow->foxy_transaction_billing_company . "<br />" .
					  $tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
					  $tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip . " " . $tRow->foxy_transaction_billing_country . "<br />" .
					  $foxyXMLResponse->transaction->customer_phone);
				?>
			</div>
			<div class="ship_to">
				<?php
				echo($foxyXMLResponse->transaction->shipping_first_name . " " .  $foxyXMLResponse->transaction->shipping_last_name . "<br />" .
					(($tRow->foxy_transaction_shipping_company!="") ? $tRow->foxy_transaction_shipping_company : $tRow->foxy_transaction_billing_company) . "<br />" .
						(
							($HasSameBillingAndShipping) ?
							$tRow->foxy_transaction_billing_address1 . " " .  $tRow->foxy_transaction_billing_address2 . "<br />" .
							$tRow->foxy_transaction_billing_city . ", " . $tRow->foxy_transaction_billing_state . " " . $tRow->foxy_transaction_billing_zip . " " . $tRow->foxy_transaction_billing_country
							:
							$tRow->foxy_transaction_shipping_address1 . " " .  $tRow->foxy_transaction_shipping_address2 . "<br />" .
							$tRow->foxy_transaction_shipping_city . ", " . $tRow->foxy_transaction_shipping_state . " " . $tRow->foxy_transaction_shipping_zip . " " . $tRow->foxy_transaction_shipping_country
						)
					);
				?>
			</div>
			<div class="clearall"></div><br />
			<? if($foxyXMLResponse->transaction->shipto_shipping_service_description!=""){
				_e("<div class='shipped_via'>Shipped via: " . $foxyXMLResponse->transaction->shipto_shipping_service_description . "</div>", "foxypress");
			}?>
			<div class="clearall"></div>
		</div>
		<hr class="breaker" />
		<div class="product_information">
			<div class="product_headers">
				<div class="sku"><?php _e('SKU', 'foxypress'); ?></div>
				<div class="qty"><?php _e('QTY', 'foxypress'); ?></div>
				<div class="name"><?php _e('PRODUCT', 'foxypress'); ?></div>
			</div>
            <?php echo($productList); ?>
			<div class="clearall"></div>
		</div>
		<hr class="breaker" />
		<div class="custom_message">
			<p>
				<?php echo($CustomMessage); ?>
			</p>
		</div>
	</div>
    <?php if($printPage) { ?>
		<script type="text/javascript">
         	window.print();
        </script>
	<?php
		}
	}
	exit;
}

function ProcessSearch($SearchValue)
{
	global $wpdb;
	if($SearchValue == "")
	{
		_e("Invalid search term, please try again. You can search by First Name, Last Name, or Transaction ID.", "foxypress");
	}
	else
	{
		//search for exact matches, then like matches (union)
		$SearchSQL = "SELECT *
						FROM " . $wpdb->prefix ."foxypress_transaction
						WHERE foxy_transaction_id = '$SearchValue'
						AND foxy_blog_id = " . ((foxypress_IsMainBlog()) ? "foxy_blog_id" : "'" . $wpdb->blogid . "'") . "
					  UNION
					  SELECT *
					  	FROM " . $wpdb->prefix ."foxypress_transaction
						WHERE (foxy_transaction_first_name = '$SearchValue'
								OR foxy_transaction_last_name = '$SearchValue')
						AND foxy_blog_id = " . ((foxypress_IsMainBlog()) ? "foxy_blog_id" : "'" . $wpdb->blogid . "'") . "
					  UNION
					  SELECT *
					  	FROM " . $wpdb->prefix ."foxypress_transaction
						WHERE foxy_transaction_id like '%" . $SearchValue. "%'
						AND foxy_blog_id = " . ((foxypress_IsMainBlog()) ? "foxy_blog_id" : "'" . $wpdb->blogid . "'") . "
					  UNION
					  SELECT *
					  	FROM " . $wpdb->prefix ."foxypress_transaction
						WHERE (foxy_transaction_first_name like '%" . $SearchValue . "%'
								or foxy_transaction_last_name = '%" . $SearchValue . "%')
							AND foxy_blog_id = " . ((foxypress_IsMainBlog()) ? "foxy_blog_id" : "'" . $wpdb->blogid . "'");
		$Transactions = $wpdb->get_results($SearchSQL);

		echo("<br><table class=\"widefat page fixed\">
					<thead>
						<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Transaction ID', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Date of Order', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Name', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Email', 'foxypress') . "</th>
							<th class=\"manage-column\" scope=\"col\">" . __('Tracking', 'foxypress') . "</th>
						</tr>
					</thead>");
		if( !empty($Transactions) )
		{
			foreach($Transactions as $t)
			{
				echo("<tr>
							<td>
								<a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $t->foxy_transaction_id . "&mode=detail\">" . $t->foxy_transaction_id . "</a>
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
			_e("<tr><td colspan=\"4\">Your search did not return any results, please try again</td></tr>", "foxypress");
		}
		echo("</table>");
	}
}

function Begin_Foxy_Order_Management()
{
	?>
    <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/jquery.qtip.js"></script>
    <script type="text/javascript">
    	jQuery(document).ready(function() {
    		jQuery('#foxy_om_transaction_submit').click(function(){
				transaction_id = jQuery('#foxy_transaction_submit_id').val();
				baseurl = jQuery('#foxy_baseurl').val();
				sid = jQuery('#foxy_user_session_id').val();
				status_id = jQuery('#foxy_om_ddl_status').val();
				tracking_num = jQuery('#foxy_om_tracking_number').val();
				rma_num = jQuery('#foxy_om_rma_number').val();

				foxypress_transaction_submit(baseurl, sid, status_id, tracking_num, rma_num)

				return false;
			});

			function foxypress_transaction_submit(baseurl, sid, status_id, tracking_num, rma_num)
			{
				jQuery('#foxypress_transaction_submit_loading').show();
				jQuery('#transaction_submit_msg').html("");
				var url = baseurl + "?m=transaction_submit&sid=" + sid + "&transaction_id=" + transaction_id + "&status=" + status_id + "&tracking_num=" + tracking_num + "&rma_num=" + rma_num;
				jQuery.ajax({
								url : url,
								type : "GET",
								datatype : "json",
								cache : "false",
								success : function(data){
									if (data.ajax_status !== 'ok') {
										alert(data.ajax_status);
									}

									jQuery('#foxypress_transaction_submit_loading').hide();
									jQuery('#transaction_submit_msg').html("Status Updated Successfully");
								}
							});
			}
		});
	</script>
	<div class="wrap">
    	<h2><?php _e('Order Management','foxypress'); ?></h2>
        <div>
            <div><i><?php _e('Search by First Name, Last Name, or Transaction ID', 'foxypress'); ?></i></div>
            <form name="foxy_om_search" id="foxy_om_search" class="wrap" method="post">
            	<input type="hidden" name="foxy_om_mode" id="foxy_om_mode" value="search" />
                <div id="linkadvanceddiv" class="postbox">
                    <div style="float: left; width: 98%; clear: both;" class="inside">
                        <table cellspacing="5" cellpadding="5">
                            <tr>
                                <td><input type="text" name="foxy_om_search" id="foxy_om_search" value="<?php echo(foxypress_FixPostVar("foxy_om_search", ""))?>" /> </td>
                                <td><input type="submit" id="foxy_om_search_submit" class="button bold" name="foxy_om_search_submit" value="Search &raquo;" /></td>
                            </tr>
                        </table>
                    </div>
                    <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
            </form>
        </div>
	<?php
}

function End_Foxy_Order_Management($NeedsSync)
{
	$Page_URL = get_admin_url() . "edit.php";
	?>
		</div>
        <script type="text/javascript" language="javascript">
			function ToggleSubSiteOrder(BlogID)
			{
				var show = jQuery('#sub_order_' + BlogID).hasClass("Hide");
				if(show)
				{
					jQuery('#sub_order_' + BlogID).removeClass("Hide");
					jQuery('#view_sub_order_' + BlogID).html("-");
				}
				else
				{
					jQuery('#sub_order_' + BlogID).addClass("Hide");
					jQuery('#view_sub_order_' + BlogID).html("+");
				}
			}

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

			function ToggleEditCustomer()
			{
				var show = jQuery('#divEditCustomer').hasClass("Hide");
				if(show)
				{
					jQuery('#divEditCustomer').removeClass("Hide");
					jQuery('#divViewCustomer').addClass("Hide");
				}
				else
				{
					jQuery('#divEditCustomer').addClass("Hide");
					jQuery('#divViewCustomer').removeClass("Hide");
				}
			}

			function RedirectFilter()
			{
				window.location.href = jQuery('#foxy_om_transaction_type_filter').val();
			}

			function ResetDownloadCount(baseurl, sid, downloadable_id, downloadable_transaction_id)
			{
				jQuery('#foxypress_downloadable_loading').show();
				var url = baseurl + "?m=resetdownloadcount&sid=" + sid + "&downloadableid=" + downloadable_id + "&downloadtransactionid=" + downloadable_transaction_id;
				jQuery.ajax(
							{
								url : url,
								type : "GET",
								datatype : "json",
								cache : "false",
								success : function(){
									jQuery('#foxypress_downloadable_loading').hide();
									jQuery('#foxypress_downloadable_count').html("0");
								}
							}
						);
			}

			function SyncTransactionsJS(baseurl, fullurl, loadingimage, showProgress)
			{
				if(showProgress)
				{
					jQuery('#foxy_om_sync').html('<img src="' + loadingimage + '" />');
					jQuery.get(fullurl, function(data) {
					  jQuery('#foxy_om_sync').html('<?php _e('Synchronization Complete', 'foxypress'); ?> <a href="' + baseurl + '"><?php _e('Refresh Page', 'foxypress'); ?></a>');
					});
				}
				else
				{
					jQuery.get(fullurl);
				}
			}

			function BulkSelectAll(isChecked)
			{
				 jQuery("input[name='foxypress_bulk_check[]']").attr('checked', isChecked);
			}

			function HandleBulkAction(action, baseURL)
			{
				if(action == "bulk_print")
				{
					var TransactionsToPrint = new Array();
					jQuery.each(jQuery("input[name='foxypress_bulk_check[]']:checked"), function() {
					  TransactionsToPrint.push(jQuery(this).val());
					});
					if(TransactionsToPrint != null && TransactionsToPrint.length > 0)
					{
						if(confirm("You are about to print " + TransactionsToPrint.length + " packing slip(s). Are you sure you would like to continue?"))
						{
							for(var i=0; i < TransactionsToPrint.length; i++)
							{
								var fullURL = baseURL + "&transaction=" + TransactionsToPrint[i];
								window.open(fullURL);
							}
						}
						//reset dropdown
						jQuery("#foxypress_bulk_select").val("");
					}
				}
			}

			<?php
				if($NeedsSync)
				{
					echo("SyncTransactionsJS('" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management', '" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&action=sync', '" . plugins_url() . "/foxypress/img/ajax-loader.gif', false)");
				}
			?>
	  	</script>
	<?php
}

function SyncTransactions($SyncAll, $PageStart)
{
	global $wpdb;
	//get last date we synced, if it's a new sync we start at 1900, if it's a current running system then we take the last date and subtract a day
	$sql = "SELECT CASE option_value
					WHEN '1900-01-01' THEN '1900-01-01'
					ELSE DATE_SUB(option_value, INTERVAL 1 DAY) END as LastSync, DATE_FORMAT(NOW(), '%Y-%m-%d') as CurrentDate
			FROM " . $wpdb->prefix . "options
			WHERE option_name='foxypress_transaction_sync_date'";
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

	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->transactions->transaction as $t)
		{
			//get blog id
			$blog_id = "";
			foreach ($t->custom_fields->custom_field as $customfield)
			{
				if($blog_id == "" && strtolower($customfield->custom_field_name) == "blog_id")
				{
					$blog_id = $customfield->custom_field_value;
				}

				if (strtolower($customfield->custom_field_name) == "affiliate_id")
				{
					$affiliate_id = $customfield->custom_field_value;
				}
			}

			//insert new transactions into our db, ignore existing transactions
			$sql = "INSERT IGNORE INTO " . $wpdb->prefix ."foxypress_transaction" .
				  " SET foxy_transaction_id = '" . mysql_escape_string($t->id) . "'" .
				  ", foxy_transaction_status = '1'" .
				  ", foxy_transaction_first_name='" . mysql_escape_string($t->customer_first_name) . "'" .
				  ", foxy_transaction_last_name='" . mysql_escape_string($t->customer_last_name) . "'" .
				  ", foxy_transaction_email='" . mysql_escape_string($t->customer_email) . "'" .
				  ", foxy_transaction_is_test='" . mysql_escape_string($t->is_test) . "'" .
				  ", foxy_transaction_date = '" .mysql_escape_string( $t->transaction_date) . "'" .
				  ", foxy_transaction_product_total = '" . mysql_escape_string($t->product_total) . "'" .
				  ", foxy_transaction_tax_total = '" . mysql_escape_string($t->tax_total) . "'" .
				  ", foxy_transaction_shipping_total = '" . mysql_escape_string($t->shipping_total) . "'" .
				  ", foxy_transaction_order_total = '" . mysql_escape_string($t->order_total) . "'" .
				  ", foxy_transaction_cc_type = '" . mysql_escape_string($t->cc_type) . "'" .
				  ", foxy_blog_id = '" . mysql_escape_string($blog_id) . "'" .
				  ", foxy_affiliate_id = '" . mysql_escape_string($affiliate_id) . "'";

				  if($t->shipping_address1 == "")
				  {
					  //use billing for both
					  $sql .= ", foxy_transaction_billing_address1 = '" . mysql_escape_string($t->customer_address1) . "'" .
					  		  ", foxy_transaction_billing_address2 = '" . mysql_escape_string($t->customer_address2) . "'" .
							  ", foxy_transaction_billing_city = '" . mysql_escape_string($t->customer_city) . "'" .
							  ", foxy_transaction_billing_state = '" . mysql_escape_string($t->customer_state) . "'" .
							  ", foxy_transaction_billing_zip = '" . mysql_escape_string($t->customer_postal_code) . "'" .
							  ", foxy_transaction_billing_country = '" . mysql_escape_string($t->customer_country) . "'" .
							  ", foxy_transaction_billing_company = '" . mysql_escape_string($t->customer_company) . "'" .
							  ", foxy_transaction_shipping_address1 = '" . mysql_escape_string($t->customer_address1) . "'" .
							  ", foxy_transaction_shipping_address2 = '" . mysql_escape_string($t->customer_address2) . "'" .
							  ", foxy_transaction_shipping_city = '" . mysql_escape_string($t->customer_city) . "'" .
							  ", foxy_transaction_shipping_state = '" . mysql_escape_string($t->customer_state) . "'" .
							  ", foxy_transaction_shipping_zip = '" . mysql_escape_string($t->customer_postal_code) . "'" .
							  ", foxy_transaction_shipping_company = '" . mysql_escape_string($t->customer_company) . "'" .
							  ", foxy_transaction_shipping_country = '" . mysql_escape_string($t->customer_country) . "'";
				  }
				  else
				  {
					  $sql .= ", foxy_transaction_billing_address1 = '" . mysql_escape_string($t->customer_address1) . "'" .
					  		  ", foxy_transaction_billing_address2 = '" . mysql_escape_string($t->customer_address2) . "'" .
							  ", foxy_transaction_billing_city = '" . mysql_escape_string($t->customer_city) . "'" .
							  ", foxy_transaction_billing_state = '" . mysql_escape_string($t->customer_state) . "'" .
							  ", foxy_transaction_billing_zip = '" . mysql_escape_string($t->customer_postal_code) . "'" .
							  ", foxy_transaction_billing_country = '" . mysql_escape_string($t->customer_country) . "'" .
							  ", foxy_transaction_billing_company = '" . mysql_escape_string($t->customer_company) . "'" .
							  ", foxy_transaction_shipping_address1 = '" . mysql_escape_string($t->shipping_address1) . "'" .
							  ", foxy_transaction_shipping_address2 = '" . mysql_escape_string($t->shipping_address2) . "'" .
							  ", foxy_transaction_shipping_city = '" . mysql_escape_string($t->shipping_city) . "'" .
							  ", foxy_transaction_shipping_state = '" . mysql_escape_string($t->shipping_state) . "'" .
							  ", foxy_transaction_shipping_zip = '" . mysql_escape_string($t->shipping_postal_code) . "'" .
							  ", foxy_transaction_shipping_company = '" . mysql_escape_string($t->shipping_company) . "'" .
							  ", foxy_transaction_shipping_country = '" . mysql_escape_string($t->shipping_country) . "'";
				  }
			$wpdb->query($sql);
			//if our insert ignores it and they have an old version we may have missed the ...
			//	- version 0.1.9 - foxy_transaction_is_test
			//  - version 0.2.5 - _date, _product_total, tax_total, shipping_total, order_total, cc_type
			//  - version 0.3.3 - foxy_blog_id for multisites
			//so update accordingly
			$sql = "UPDATE " . $wpdb->prefix ."foxypress_transaction
					SET  foxy_transaction_is_test='" . mysql_escape_string($t->is_test) . "'" .
					  ", foxy_transaction_date = '" . mysql_escape_string($t->transaction_date) . "'" .
					  ", foxy_transaction_product_total = '" . mysql_escape_string($t->product_total) . "'" .
					  ", foxy_transaction_tax_total = '" . mysql_escape_string($t->tax_total) . "'" .
					  ", foxy_transaction_shipping_total = '" . mysql_escape_string($t->shipping_total) . "'" .
					  ", foxy_transaction_order_total = '" . mysql_escape_string($t->order_total) . "'" .
					  ", foxy_transaction_cc_type = '" . mysql_escape_string($t->cc_type) . "'" .
					  ", foxy_blog_id = coalesce(foxy_blog_id, '" . mysql_escape_string($blog_id) . "')
					WHERE foxy_transaction_id = '" . mysql_escape_string($t->id) . "'";
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

	if($PageStart == foxypress_GetPaginationStart()) //update after all recursion is done
	{
		//update sync date & timestamp
		update_option("foxypress_transaction_sync_date", $dr->CurrentDate);
		update_option("foxypress_transaction_sync_timestamp", date("Y-m-d H:i:s"));
	}
}


?>