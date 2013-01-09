<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

//add_action('admin_init', 'foxypress_reports_postback');
function foxypress_reports_postback()
{
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
}

function foxypress_reports_page_load()
{
	global $wpdb;	
	$report = foxypress_FixGetVar("report");
	if($report == "1")
	{
		foxypress_view_totals_report();
	}
	else if($report == "2")
	{
		foxyprses_view_ordersByCode_report();	
	}
	else if ($report == "3")
	{
		foxyprses_view_coupon_ordersByCode_report();
	}
	else
	{
		foxypress_view_reports_list();	
	}
}

function foxypress_view_reports_list()
{
?>
	<div class="wrap">
    	<h2><?php _e('Reports','foxypress'); ?></h2><br>
		<div class='reports first'>

		</div>
		<div class='reports second'>
			<a href="<?php echo(get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=reports&report=1"); ?>"><?php _e('View Daily/Weekly/Monthly Order Totals', 'foxypress'); ?></a>			
		</div>
		<div class='reports second'>
        	<a href="<?php echo(get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=reports&report=2"); ?>"><?php _e('View Orders By Product Code', 'foxypress'); ?></a>
		</div>
		<div class='reports second'>
        	<a href="<?php echo(get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=reports&report=3"); ?>"><?php _e('View Coupon Orders By Product Code', 'foxypress'); ?></a>
		</div>
	</div>
<?php
}

function foxyprses_view_ordersByCode_report()
{
	global $wpdb;
	
	if(isset($_POST['ddlStartHour'])){$ddlStartHour = filter($_POST['ddlStartHour']);}else{$ddlStartHour = "";}
	if(isset($_POST['ddlStartMinute'])){$ddlStartMinute = filter($_POST['ddlStartMinute']);}else{$ddlStartMinute = "";}
	if(isset($_POST['ddlStartSuffix'])){$ddlStartSuffix = filter($_POST['ddlStartSuffix']);}else{$ddlStartSuffix = "";}
	if(isset($_POST['ddlEndHour'])){$ddlEndHour = filter($_POST['ddlEndHour']);}else{$ddlEndHour = "";}
	if(isset($_POST['ddlEndMinute'])){$ddlEndMinute = filter($_POST['ddlEndMinute']);}else{$ddlEndMinute = "";}
	if(isset($_POST['ddlEndSuffix'])){$ddlEndSuffix = filter($_POST['ddlEndSuffix']);}else{$ddlEndSuffix = "";}
	
	if(isset($_POST['txtStartDate'])){$txtStartDate = filter($_POST['txtStartDate']);}else{$txtStartDate = "";}
	if(isset($_POST['txtEndDate'])){$txtEndDate = filter($_POST['txtEndDate']);}else{$txtEndDate = "";}
	
	if(isset($_POST['txtProductCode'])){$txtProductCode = filter($_POST['txtProductCode']);}else{$txtProductCode = "";}
	?>
    <script> 
		jQuery(function() {
			jQuery("#txtStartDate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#txtEndDate").datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script> 
	<div class="wrap">
        <?php foxypress_view_reports_list(); ?>
		<div class="clearall"></div>
        <div>
        	<form id="frmOrders" name="frmOrders" method="POST">                 
            	<table>
            		<tr>
                    	<td><?php _e('Start Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtStartDate" name="txtStartDate" value="<?php echo(($txtStartDate != "") ? $txtStartDate : date("Y-m-d"))  ?>" /> 
                            <select id="ddlStartHour" name="ddlStartHour">
                                <option value="1" <?php echo ($ddlStartHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlStartHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlStartHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlStartHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlStartHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlStartHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlStartHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlStartHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlStartHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlStartHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlStartHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlStartHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlStartMinute" name="ddlStartMinute">
                                <option value="00" <?php echo ($ddlStartMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlStartMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlStartMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlStartMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlStartSuffix" name="ddlStartSuffix">
                                <option value="AM" <?php echo ($ddlStartSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlStartSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>
                            </select>
						</td>
					</tr>
                    <tr>
                    	<td><?php _e('End Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtEndDate" name="txtEndDate"  value="<?php echo(($txtEndDate != "") ? $txtEndDate : date("Y-m-d")); ?>" />  
                            <select id="ddlEndHour" name="ddlEndHour">
                                <option value="1" <?php echo ($ddlEndHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlEndHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlEndHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlEndHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlEndHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlEndHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlEndHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlEndHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlEndHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlEndHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlEndHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlEndHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlEndMinute" name="ddlEndMinute">
                                <option value="00" <?php echo ($ddlEndMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlEndMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlEndMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlEndMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlEndSuffix" name="ddlEndSuffix">
                                <option value="AM" <?php echo ($ddlEndSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlEndSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>                            
							</select>
                        </td>
                    </tr>
                    <tr>
                    	<td><?php _e('Product Code', 'foxypress'); ?>: </td>
                        <td><input id="txtProductCode" name="txtProductCode" type="text" value="<?php echo($txtProductCode);?>" /></td>
                    </tr>
                    <tr>
                    	<td><?php _e('Transaction Status', 'foxypress'); ?>: </td>
                        <td>
                        	<select name="ddlStatus" id="ddlStatus">
                            	<?php
                                $TransactionStatuses = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_transaction_status");
                                if( !empty($TransactionStatuses) )
                                {
                                    foreach ( $TransactionStatuses as $ts )
                                    {
                                       echo("<option value=\"" . $ts->foxy_transaction_status . "\"" . (($_POST['ddlStatus'] == $ts->foxy_transaction_status) ? " selected='selected'" : "") . ">" . stripslashes($ts->foxy_transaction_status_description) . "</option>");
                                    }
                                }
								?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<input type="submit" id="btnSubmit" name="btnSubmit" value="<?php _e('Run Report', 'foxypress'); ?>" /> 
                            <small><i><?php _e('(Start and End Dates need to be in yyyy-mm-dd format. ex', 'foxypress'); ?>: <?php echo(date("Y-m-d"))?>)</i></small>
						</td>
                    </tr>
				</table>
			</form>                   
		</div>
        <?php
		if(isset($_POST['btnSubmit']))
		{
			$TransactionStatus = foxypress_FixPostVar('ddlStatus');
			$ProductCode = foxypress_FixPostVar('txtProductCode');
			$StartDate = foxypress_FixPostVar('txtStartDate');
			$StartHour = foxypress_FixPostVar('ddlStartHour');
			$StartMinute = foxypress_FixPostVar('ddlStartMinute');
			$StartSuffix = foxypress_FixPostVar('ddlStartSuffix');
			if($StartSuffix == "PM")
			{	
				$StartHour = $StartHour	+ 12;
			}		
			$StartDate = $StartDate . " " . $StartHour . ":" . $StartMinute . ":00";
			
			$EndDate = foxypress_FixPostVar('txtEndDate');
			$EndHour = foxypress_FixPostVar('ddlEndHour');
			$EndMinute = foxypress_FixPostVar('ddlEndMinute');
			$EndSuffix = foxypress_FixPostVar('ddlEndSuffix');
			if($EndSuffix == "PM")
			{	
				$EndHour = $EndHour + 12;
			}		
			$EndDate = $EndDate . " " . $EndHour . ":" . $EndMinute . ":00";
			//get orders
			$dtOrders = $wpdb->get_results("SELECT *
											FROM " . $wpdb->prefix ."foxypress_transaction" . " 
											WHERE foxy_transaction_date >= '$StartDate' 
												and foxy_transaction_date <= '$EndDate' 
												and foxy_transaction_status = '$TransactionStatus'
												and foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id"));
			$Orders = array();
			if(!empty($dtOrders))
			{
				foreach($dtOrders as $ord)
				{
					$Orders[] = $ord->foxy_transaction_id;	
				}
			}
			//get orders from the api and filter down with our status
			$Results = "";			
			$FilteredOrders = foxypress_filter_orders($StartDate, $EndDate, $ProductCode, $Orders, null, foxypress_GetPaginationStart());			
			if(!empty($FilteredOrders))
			{
				foreach($FilteredOrders as $fo)
				{
					foreach($fo->transaction_details->transaction_detail as $td)
					{
						if($ProductCode == "" || $ProductCode == $td->product_code)
						{
							$Results .= "<tr>
											<td><a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $fo->id . "&b=" . $fo->foxy_blog_id . "&mode=detail\" target=\"_blank\">" . $fo->id . "</a></td>
											<td>" . $td->product_code . "</td>
											<td>" . $td->product_quantity . "</td>
											<td>" . $fo->transaction_date . "</td>
										</tr>";
						}
					}
				}	
			}
			else
			{
				$Results = "<tr><td colspan=\"4\">" . __('There are currently no transactions', 'foxypress') . "</td></tr>";
			}
			echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
			 		<thead>
                		<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Number', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Product Code', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Quantity', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Date', 'foxypress') . "</td>
						</tr>
					</thead>
					<tbody>
						$Results
					</tbody>
				</table>");
		}		
		?>        
	</div>
<?php
}

function foxyprses_view_coupon_ordersByCode_report()
{
	global $wpdb;
	if(isset($_POST['ddlStartHour'])){$ddlStartHour = filter($_POST['ddlStartHour']);}else{$ddlStartHour = "";}
	if(isset($_POST['ddlStartMinute'])){$ddlStartMinute = filter($_POST['ddlStartMinute']);}else{$ddlStartMinute = "";}
	if(isset($_POST['ddlStartSuffix'])){$ddlStartSuffix = filter($_POST['ddlStartSuffix']);}else{$ddlStartSuffix = "";}
	if(isset($_POST['ddlEndHour'])){$ddlEndHour = filter($_POST['ddlEndHour']);}else{$ddlEndHour = "";}
	if(isset($_POST['ddlEndMinute'])){$ddlEndMinute = filter($_POST['ddlEndMinute']);}else{$ddlEndMinute = "";}
	if(isset($_POST['ddlEndSuffix'])){$ddlEndSuffix = filter($_POST['ddlEndSuffix']);}else{$ddlEndSuffix = "";}
	
	if(isset($_POST['txtStartDate'])){$txtStartDate = filter($_POST['txtStartDate']);}else{$txtStartDate = "";}
	if(isset($_POST['txtEndDate'])){$txtEndDate = filter($_POST['txtEndDate']);}else{$txtEndDate = "";}
	
	if(isset($_POST['txtProductCode'])){$txtProductCode = filter($_POST['txtProductCode']);}else{$txtProductCode = "";}
	?>
    <script> 
		jQuery(function() {
			jQuery("#txtStartDate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#txtEndDate").datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script> 
	<div class="wrap">
        <?php foxypress_view_reports_list(); ?>
		<div class="clearall"></div>
        <div>
        	<form id="frmOrders" name="frmOrders" method="POST">                 
            	<table>
            		<tr>
                    	<td><?php _e('Start Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtStartDate" name="txtStartDate" value="<?php echo(($txtStartDate != "") ? $txtStartDate : date("Y-m-d"))  ?>" /> 
                            <select id="ddlStartHour" name="ddlStartHour">
                                <option value="1" <?php echo ($ddlStartHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlStartHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlStartHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlStartHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlStartHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlStartHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlStartHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlStartHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlStartHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlStartHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlStartHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlStartHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlStartMinute" name="ddlStartMinute">
                                <option value="00" <?php echo ($ddlStartMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlStartMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlStartMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlStartMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlStartSuffix" name="ddlStartSuffix">
                                <option value="AM" <?php echo ($ddlStartSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlStartSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>
                            </select>
						</td>
					</tr>
                    <tr>
                    	<td><?php _e('End Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtEndDate" name="txtEndDate"  value="<?php echo(($txtEndDate != "") ? $txtEndDate : date("Y-m-d")); ?>" />  
                            <select id="ddlEndHour" name="ddlEndHour">
                                <option value="1" <?php echo ($ddlEndHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlEndHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlEndHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlEndHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlEndHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlEndHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlEndHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlEndHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlEndHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlEndHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlEndHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlEndHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlEndMinute" name="ddlEndMinute">
                                <option value="00" <?php echo ($ddlEndMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlEndMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlEndMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlEndMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlEndSuffix" name="ddlEndSuffix">
                                <option value="AM" <?php echo ($ddlEndSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlEndSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>                            
							</select>
                        </td>
                    </tr>
                    <tr>
                    	<td><?php _e('Product Code', 'foxypress'); ?>: </td>
                        <td><input id="txtProductCode" name="txtProductCode" type="text" value="<?php echo($txtProductCode);?>" /></td>
                    </tr>
                    <tr>
                    	<td><?php _e('Transaction Status', 'foxypress'); ?>: </td>
                        <td>
                        	<select name="ddlStatus" id="ddlStatus">
                            	<?php
                                $TransactionStatuses = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_transaction_status");
                                if( !empty($TransactionStatuses) )
                                {
                                    foreach ( $TransactionStatuses as $ts )
                                    {
                                       echo("<option value=\"" . $ts->foxy_transaction_status . "\"" . (($_POST['ddlStatus'] == $ts->foxy_transaction_status) ? " selected='selected'" : "") . ">" . stripslashes($ts->foxy_transaction_status_description) . "</option>");
                                    }
                                }
								?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<input type="submit" id="btnSubmit" name="btnSubmit" value="<?php _e('Run Report', 'foxypress'); ?>" />
                        	<input type="submit" id="export_submit" name="export_submit" value="<?php _e('Export Report', 'foxypress'); ?>" />
                            <small><i><?php _e('(Start and End Dates need to be in yyyy-mm-dd format. ex', 'foxypress'); ?>: <?php echo(date("Y-m-d"))?>)</i></small>
						</td>
                    </tr>
				</table>
			</form>                   
		</div>
        <?php
		if(isset($_POST['btnSubmit']))
		{
			$TransactionStatus = foxypress_FixPostVar('ddlStatus');
			$ProductCode = foxypress_FixPostVar('txtProductCode');
			$StartDate = foxypress_FixPostVar('txtStartDate');
			$StartHour = foxypress_FixPostVar('ddlStartHour');
			$StartMinute = foxypress_FixPostVar('ddlStartMinute');
			$StartSuffix = foxypress_FixPostVar('ddlStartSuffix');
			if($StartSuffix == "PM")
			{	
				$StartHour = $StartHour	+ 12;
			}		
			$StartDate = $StartDate . " " . $StartHour . ":" . $StartMinute . ":00";
			
			$EndDate = foxypress_FixPostVar('txtEndDate');
			$EndHour = foxypress_FixPostVar('ddlEndHour');
			$EndMinute = foxypress_FixPostVar('ddlEndMinute');
			$EndSuffix = foxypress_FixPostVar('ddlEndSuffix');
			if($EndSuffix == "PM")
			{	
				$EndHour = $EndHour + 12;
			}		
			$EndDate = $EndDate . " " . $EndHour . ":" . $EndMinute . ":00";
			//get orders
			$dtOrders = $wpdb->get_results("SELECT *
											FROM " . $wpdb->prefix ."foxypress_transaction" . " 
											WHERE foxy_transaction_date >= '$StartDate' 
												and foxy_transaction_date <= '$EndDate' 
												and foxy_transaction_status = '$TransactionStatus'
												and foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id"));
			$Orders = array();
			if(!empty($dtOrders))
			{
				foreach($dtOrders as $ord)
				{
					$Orders[] = $ord->foxy_transaction_id;	
				}
			}
			//get orders from the api and filter down with our status
			$Results = "";			
			$FilteredOrders = foxypress_filter_orders($StartDate, $EndDate, $ProductCode, $Orders, null, foxypress_GetPaginationStart(), TRUE);			
			if(!empty($FilteredOrders))
			{
				foreach($FilteredOrders as $fo)
				{

					foreach($fo->transaction_details->transaction_detail as $td)
					{
						
						foreach ($td->transaction_detail_options->transaction_detail_option as $option)
						{
							if (strtolower($option->product_option_name) == "coupon_code")
							{
								$coupon = $option->product_option_value;
							}
						}

						if($ProductCode == "" || $ProductCode == $td->product_code)
						{
							$Results .= "<tr>
											<td><a href=\"" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=order-management&transaction=" . $fo->id . "&b=" . $fo->foxy_blog_id . "&mode=detail\" target=\"_blank\">" . $fo->id . "</a></td>
											<td>" . $td->product_code . "</td>
											<td>" . $fo->customer_first_name . "</td>
											<td>" . $fo->customer_last_name . "</td>
											<td>" . $coupon . "</td>
											<td>" . $fo->transaction_date . "</td>
										</tr>";
						}
					}
				}	
			}
			else
			{
				$Results = "<tr><td colspan=\"4\">" . __('There are currently no transactions', 'foxypress') . "</td></tr>";
			}
			echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
			 		<thead>
                		<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Number', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Product Code', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('First Name', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Last Name', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Coupon Code', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Date', 'foxypress') . "</td>
						</tr>
					</thead>
					<tbody>
						$Results
					</tbody>
				</table>");
		}
		else if(isset($_POST['export_submit'])) //start export
		{	
			$TransactionStatus = foxypress_FixPostVar('ddlStatus');
			$ProductCode = foxypress_FixPostVar('txtProductCode');
			$StartDate = foxypress_FixPostVar('txtStartDate');
			$StartHour = foxypress_FixPostVar('ddlStartHour');
			$StartMinute = foxypress_FixPostVar('ddlStartMinute');
			$StartSuffix = foxypress_FixPostVar('ddlStartSuffix');
			if($StartSuffix == "PM")
			{	
				$StartHour = $StartHour	+ 12;
			}		
			$StartDate = $StartDate . " " . $StartHour . ":" . $StartMinute . ":00";
			
			$EndDate = foxypress_FixPostVar('txtEndDate');
			$EndHour = foxypress_FixPostVar('ddlEndHour');
			$EndMinute = foxypress_FixPostVar('ddlEndMinute');
			$EndSuffix = foxypress_FixPostVar('ddlEndSuffix');
			if($EndSuffix == "PM")
			{	
				$EndHour = $EndHour + 12;
			}		
			$EndDate = $EndDate . " " . $EndHour . ":" . $EndMinute . ":00";

			$dtOrders = $wpdb->get_results("SELECT *
											FROM " . $wpdb->prefix ."foxypress_transaction" . " 
											WHERE foxy_transaction_date >= '$StartDate' 
												and foxy_transaction_date <= '$EndDate' 
												and foxy_transaction_status = '$TransactionStatus'
												and foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id"));
			
			if(!empty($dtOrders))
			{
				foreach($dtOrders as $ord)
				{
					$Orders[] = $ord->foxy_transaction_id;	
				}
			}

			$list = array();
			$data = "";
			$row = array();
			$row[] = 'Order Number';
			$row[] = 'Product Code';
			$row[] = 'First Name';
			$row[] = 'Last Name';
			$row[] = 'Coupon Code';
			$row[] = 'Order Date';
			$list[] = $row;

			//get orders from the api and filter down with our status
			$Results = "";			
			if(empty($Orders)){$Orders=array();}
			$FilteredOrders = foxypress_filter_orders($StartDate, $EndDate, $ProductCode, $Orders, null, foxypress_GetPaginationStart(), TRUE);		
			if(!empty($FilteredOrders))
			{
				foreach($FilteredOrders as $fo)
				{

					foreach($fo->transaction_details->transaction_detail as $td)
					{

						foreach ($td->transaction_detail_options->transaction_detail_option as $option)
						{
							if (strtolower($option->product_option_name) == "coupon_code")
							{
								$coupon = $option->product_option_value;
							}
						}
						
						if($ProductCode == "" || $ProductCode == $td->product_code)
						{
							$row = array(); //clear previous items
							$row[] = $fo->id;
							$row[] = $td->product_code;
							$row[] = $fo->customer_first_name;
							$row[] = $fo->customer_last_name;
							$row[] = $coupon;
							$row[] = $fo->transaction_date;
			
							$list[] = $row;
						}
					}
				}

				if (file_exists(WP_PLUGIN_DIR . "/foxypress/Coupons.csv")) 
				{
					unlink(WP_PLUGIN_DIR . "/foxypress/Coupons.csv");
				}
				$f = fopen(WP_PLUGIN_DIR . "/foxypress/Coupons.csv", "x+");
				//fwrite($f,$data);		
				foreach ($list as $line)
				{
					fputcsv($f, $line);
					fseek($f, -1, SEEK_CUR);
					fwrite($f, "\r\n"); 
				}
				fclose($f);
				echo "<a href=\"" . plugins_url() . "/foxypress/Coupons.csv\" target=\"_blank\">" . __('Download Coupon Orders</a> <small><i>(Right Click, Save As)</i></small>', 'foxypress') . "";
			}
			else
			{
				_e("There are currently no transactions", "foxypress");
			}

		}//end if export		
		?>        
	</div>
<?php
}

function foxypress_filter_orders($StartDate, $EndDate, $ProductCode, $OrdersWithStatus, $Orders, $PageStart, $Coupon = FALSE)
{
	$foxyStoreURL = get_option('foxycart_storeurl');
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_list";
	$foxyData["transaction_date_filter_begin"] = $StartDate;
	$foxyData["transaction_date_filter_end"] = $EndDate;
	$foxyData["hide_transaction_filter"] = "";
	$foxyData["is_test_filter"] = "";	
	$foxyData["pagination_start"] = $PageStart;
	if ($Coupon) {
		$foxyData["product_option_name_filter"] = "coupon_code";
	}
	if($ProductCode != "")
	{
		$foxyData["product_code_filter"] = $ProductCode;
	}
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	if($Orders == null)
	{
		$Orders = array();	
	}
	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->transactions->transaction as $t)
		{
			if(in_array($t->id, $OrdersWithStatus))
			{
				$Orders[] = $t;	
			}
		}
	}
	//recurse
	$Total_Transactions = (int)$foxyXMLResponse->statistics->filtered_total;
	$Pagination_Start = (int)$foxyXMLResponse->statistics->pagination_start;
	$Pagination_End = (int)$foxyXMLResponse->statistics->pagination_end;		
	if($Total_Transactions > $Pagination_End) //foxy only lets us grab 300 at a time, if we have more, recurse.
	{
		$NextStart = $Pagination_End;		
		foxypress_filter_orders($StartDate, $EndDate, $ProductCode, $OrdersWithStatus, $Orders, $NextStart);
	}
	
	if($PageStart == foxypress_GetPaginationStart())
	{
		return $Orders;
	}
}

function foxypress_view_totals_report()
{
	global $wpdb;
	if(isset($_POST['ddlStartHour'])){$ddlStartHour = filter($_POST['ddlStartHour']);}else{$ddlStartHour = "";}
	if(isset($_POST['ddlStartMinute'])){$ddlStartMinute = filter($_POST['ddlStartMinute']);}else{$ddlStartMinute = "";}
	if(isset($_POST['ddlStartSuffix'])){$ddlStartSuffix = filter($_POST['ddlStartSuffix']);}else{$ddlStartSuffix = "";}
	if(isset($_POST['ddlEndHour'])){$ddlEndHour = filter($_POST['ddlEndHour']);}else{$ddlEndHour = "";}
	if(isset($_POST['ddlEndMinute'])){$ddlEndMinute = filter($_POST['ddlEndMinute']);}else{$ddlEndMinute = "";}
	if(isset($_POST['ddlEndSuffix'])){$ddlEndSuffix = filter($_POST['ddlEndSuffix']);}else{$ddlEndSuffix = "";}
	
	if(isset($_POST['txtStartDate'])){$txtStartDate = filter($_POST['txtStartDate']);}else{$txtStartDate = "";}
	if(isset($_POST['txtEndDate'])){$txtEndDate = filter($_POST['txtEndDate']);}else{$txtEndDate = "";}
	
	if(isset($_POST['txtProductCode'])){$txtProductCode = filter($_POST['txtProductCode']);}else{$txtProductCode = "";}
	if(isset($_POST['foxy_transaction_type'])){$foxy_transaction_type = filter($_POST['foxy_transaction_type']);}else{$foxy_transaction_type = "";}
	
	?>
    <script> 
		jQuery(function() {
			jQuery("#txtStartDate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#txtEndDate").datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script> 
	<div class="wrap">
    	<?php foxypress_view_reports_list(); ?>
		<div class="clearall"></div>
        <div>
        	<form id="frmOrders" name="frmOrders" method="POST">                 
            	<table>
            		<tr>
                    	<td><?php _e('Start Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtStartDate" name="txtStartDate" value="<?php echo(($txtStartDate != "") ? $txtStartDate : date("Y-m-d"))  ?>" /> 
                            <select id="ddlStartHour" name="ddlStartHour">
                                <option value="1" <?php echo ($ddlStartHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlStartHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlStartHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlStartHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlStartHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlStartHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlStartHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlStartHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlStartHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlStartHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlStartHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlStartHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlStartMinute" name="ddlStartMinute">
                                <option value="00" <?php echo ($ddlStartMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlStartMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlStartMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlStartMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlStartSuffix" name="ddlStartSuffix">
                                <option value="AM" <?php echo ($ddlStartSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlStartSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>
                            </select>
						</td>
					</tr>
                    <tr>
                    	<td><?php _e('End Date', 'foxypress'); ?>: </td>
                        <td>
                            <input type="text" id="txtEndDate" name="txtEndDate"  value="<?php echo(($txtEndDate != "") ? $txtEndDate : date("Y-m-d")); ?>" />  
                            <select id="ddlEndHour" name="ddlEndHour">
                                <option value="1" <?php echo ($ddlEndHour == "1") ? "selected=\"selected\"" : "" ?>><?php _e('1', 'foxypress'); ?></option>
                                <option value="2" <?php echo ($ddlEndHour == "2") ? "selected=\"selected\"" : "" ?>><?php _e('2', 'foxypress'); ?></option>
                                <option value="3" <?php echo ($ddlEndHour == "3") ? "selected=\"selected\"" : "" ?>><?php _e('3', 'foxypress'); ?></option>
                                <option value="4" <?php echo ($ddlEndHour == "4") ? "selected=\"selected\"" : "" ?>><?php _e('4', 'foxypress'); ?></option>
                                <option value="5" <?php echo ($ddlEndHour == "5") ? "selected=\"selected\"" : "" ?>><?php _e('5', 'foxypress'); ?></option>
                                <option value="6" <?php echo ($ddlEndHour == "6") ? "selected=\"selected\"" : "" ?>><?php _e('6', 'foxypress'); ?></option>
                                <option value="7" <?php echo ($ddlEndHour == "7") ? "selected=\"selected\"" : "" ?>><?php _e('7', 'foxypress'); ?></option>
                                <option value="8" <?php echo ($ddlEndHour == "8") ? "selected=\"selected\"" : "" ?>><?php _e('8', 'foxypress'); ?></option>
                                <option value="9" <?php echo ($ddlEndHour == "9") ? "selected=\"selected\"" : "" ?>><?php _e('9', 'foxypress'); ?></option>
                                <option value="10" <?php echo ($ddlEndHour == "10") ? "selected=\"selected\"" : "" ?>><?php _e('10', 'foxypress'); ?></option>
                                <option value="11" <?php echo ($ddlEndHour == "11") ? "selected=\"selected\"" : "" ?>><?php _e('11', 'foxypress'); ?></option>
                                <option value="0" <?php echo ($ddlEndHour == "0") ? "selected=\"selected\"" : "" ?>><?php _e('12', 'foxypress'); ?></option>
                            </select> : 
                            <select id="ddlEndMinute" name="ddlEndMinute">
                                <option value="00" <?php echo ($ddlEndMinute == "00") ? "selected=\"selected\"" : "" ?>><?php _e('00', 'foxypress'); ?></option>
                                <option value="15" <?php echo ($ddlEndMinute == "15") ? "selected=\"selected\"" : "" ?>><?php _e('15', 'foxypress'); ?></option>
                                <option value="30" <?php echo ($ddlEndMinute == "30") ? "selected=\"selected\"" : "" ?>><?php _e('30', 'foxypress'); ?></option>
                                <option value="45" <?php echo ($ddlEndMinute == "45") ? "selected=\"selected\"" : "" ?>><?php _e('45', 'foxypress'); ?></option>
                            </select> 
                            <select id="ddlEndSuffix" name="ddlEndSuffix">
                                <option value="AM" <?php echo ($ddlEndSuffix == "AM") ? "selected=\"selected\"" : "" ?>><?php _e('AM', 'foxypress'); ?></option>
                                <option value="PM" <?php echo ($ddlEndSuffix == "PM") ? "selected=\"selected\"" : "" ?>><?php _e('PM', 'foxypress'); ?></option>                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td><?php _e('Transaction Type', 'foxypress'); ?></td>
                        <td>
                        	<select id="foxy_transaction_type" name="foxy_transaction_type">
								<option value="" <?php echo ($foxy_transaction_type == "") ? "selected=\"selected\"" : "" ?>><?php _e('All Transactions', 'foxypress'); ?></option>
								<option value="0" <?php echo ($foxy_transaction_type == "0" || !(isset($_POST['btnSubmit']))) ? "selected=\"selected\"" : "" ?>><?php _e('Live Transactions', 'foxypress'); ?></option>
								<option value="1" <?php echo ($foxy_transaction_type == "1") ? "selected=\"selected\"" : "" ?>><?php _e('Test Transactions', 'foxypress'); ?></option>
				 			</select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<input type="submit" id="btnSubmit" name="btnSubmit" value="<?php _e('Run Report', 'foxypress'); ?>" /> 
                            <small><i><?php _e('(Start and End Dates need to be in yyyy-mm-dd format. ex', 'foxypress'); ?>: <?php echo(date("Y-m-d"))?>)</i></small>
						</td>
                    </tr>
				</table>
			</form>    
		</div> <br>    	
    <?php
	if(isset($_POST['btnSubmit']))
	{
		$StartDate = foxypress_FixPostVar('txtStartDate');
		$StartHour = foxypress_FixPostVar('ddlStartHour');
		$StartMinute = foxypress_FixPostVar('ddlStartMinute');
		$StartSuffix = foxypress_FixPostVar('ddlStartSuffix');
		if($StartSuffix == "PM")
		{	
			$StartHour = $StartHour	+ 12;
		}		
		$StartDate = $StartDate . " " . $StartHour . ":" . $StartMinute . ":00";
		
		$EndDate = foxypress_FixPostVar('txtEndDate');
		$EndHour = foxypress_FixPostVar('ddlEndHour');
		$EndMinute = foxypress_FixPostVar('ddlEndMinute');
		$EndSuffix = foxypress_FixPostVar('ddlEndSuffix');
		if($EndSuffix == "PM")
		{	
			$EndHour = $EndHour + 12;
		}		
		$EndDate = $EndDate . " " . $EndHour . ":" . $EndMinute . ":00";
		$TransactionType = foxypress_FixPostVar('foxy_transaction_type');
		
		$sql = "SELECT count(foxy_transaction_id) as TransactionCount 
						,coalesce(sum(foxy_transaction_product_total), 0) as ProductTotal
						,coalesce(sum(foxy_transaction_tax_total), 0) as TaxTotal
						,coalesce(sum(foxy_transaction_shipping_total), 0) as ShippingTotal
						,coalesce(sum(foxy_transaction_order_total), 0) as OrderTotal
				FROM " . $wpdb->prefix ."foxypress_transaction" . " 
				WHERE foxy_transaction_date >= '$StartDate' 
					and foxy_transaction_date <= '$EndDate' 
					and foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id")			
				. 
				(  
					($TransactionType == "1") ? " and foxy_transaction_is_test = '1'" :
						(($TransactionType == "0") ? " and foxy_transaction_is_test = '0'" : "")
				);            
		$OrderTotals = $wpdb->get_row($sql);
		if(!empty($OrderTotals))
		{
			echo("<div><b>Orders</b></div>");
			echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
			 		<thead>
                		<tr>
							<th class=\"manage-column\" scope=\"col\">" . __('Total Transactions', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Product Total', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Tax Total', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Shipping Total', 'foxypress') . "</td>
							<th class=\"manage-column\" scope=\"col\">" . __('Order Total', 'foxypress') . "</td>
						</tr>
					<tr>
						<td>" . $OrderTotals->TransactionCount . "</td>
						<td>$" . $OrderTotals->ProductTotal . "</td>
						<td>$" . $OrderTotals->TaxTotal . "</td>
						<td>$" . $OrderTotals->ShippingTotal . "</td>
						<td>$" . $OrderTotals->OrderTotal . "</td>
					</tr>
				  </table>");
			$sql = "SELECT foxy_transaction_cc_type as TypeOfCard
							,count(foxy_transaction_ID) as TypeCount
							,sum(foxy_transaction_order_total) as TypeTotal
				FROM " . $wpdb->prefix ."foxypress_transaction" . " 
				WHERE foxy_transaction_date >= '$StartDate'
				 and foxy_transaction_date <= '$EndDate' 
				 and foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id")			
				. 
				(  
					($TransactionType == "1") ? " and foxy_transaction_is_test = '1'" :
						(($TransactionType == "0") ? " and foxy_transaction_is_test = '0'" : "")
				) 
				.
				" group by TRIM(LOWER(foxy_transaction_cc_type))";
			$CreditCards = $wpdb->get_results($sql);         
			if(!empty($CreditCards))
			{
				_e("<br><div><b>Credit Cards</b></div>", "foxypress");
				echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
						<thead>
							<tr>
								<th class=\"manage-column\" scope=\"col\">" . __('Credit Card Type', 'foxypress') . "</td>
								<th class=\"manage-column\" scope=\"col\">" . __('Total Transactions', 'foxypress') . "</td>
								<th class=\"manage-column\" scope=\"col\">" . __('Total Order Amount', 'foxypress') . "</td>
							</tr>
						</thead>");
				foreach($CreditCards as $Card)
				{
					echo("<tr>
							<td>" . $Card->TypeOfCard . "</td>
							<td>" . $Card->TypeCount . "</td>
							<td>$" . $Card->TypeTotal . "</td>
						  </tr>");
				}
				echo("</table>");
			}
		}
		else
		{
			_e("<div>There are currently no transactions for that date range</div>", "foxypress");
		}			
	}	
	?>
    </div>    
    <?php
}
?>