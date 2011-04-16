<?
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_menu', 'reports_menu');
//add_action('admin_init', 'reports_postback');

function reports_menu()  {
	global $wpdb;
	$allowed_group = 'manage_options';	
	if (function_exists('add_submenu_page')) 
	 {
	   add_submenu_page('foxypress', __('Reports','foxypress'), __('Reports','foxypress'), $allowed_group, 'reports', 'reports_page_load');
	 }
}

function reports_page_load()
{
	global $wpdb;		
	?>
    <link rel="stylesheet" href="<?=get_bloginfo("url")?>/wp-content/plugins/foxypress/css/smoothness/jquery-ui-1.8.11.custom.css"> 
    <script type="text/javascript" src="<?=get_bloginfo("url")?>/wp-content/plugins/foxypress/js/jquery-ui-1.8.11.custom.min.js"></script>
    <script> 
		jQuery(function() {
			jQuery("#txtStartDate").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#txtEndDate").datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script> 
	<div class="wrap">
    	<h2><?php _e('Reports','reports'); ?></h2><br>
        <div><b>Daily/Weekly/Monthly Order Totals</b></div>
        <div>
        	<form id="frmOrders" name="frmOrders" method="POST">                 
            	<table>
            		<tr>
                    	<td>Start Date: </td>
                        <td>
                            <input type="text" id="txtStartDate" name="txtStartDate" value="<?= ($_POST['txtStartDate'] != "") ? $_POST['txtStartDate'] : date("Y-m-d")  ?>" /> 
                            <select id="ddlStartHour" name="ddlStartHour">
                                <option value="1" <?= ($_POST['ddlStartHour'] == "1") ? "selected=\"selected\"" : "" ?>>1</option>
                                <option value="2" <?= ($_POST['ddlStartHour'] == "2") ? "selected=\"selected\"" : "" ?>>2</option>
                                <option value="3" <?= ($_POST['ddlStartHour'] == "3") ? "selected=\"selected\"" : "" ?>>3</option>
                                <option value="4" <?= ($_POST['ddlStartHour'] == "4") ? "selected=\"selected\"" : "" ?>>4</option>
                                <option value="5" <?= ($_POST['ddlStartHour'] == "5") ? "selected=\"selected\"" : "" ?>>5</option>
                                <option value="6" <?= ($_POST['ddlStartHour'] == "6") ? "selected=\"selected\"" : "" ?>>6</option>
                                <option value="7" <?= ($_POST['ddlStartHour'] == "7") ? "selected=\"selected\"" : "" ?>>7</option>
                                <option value="8" <?= ($_POST['ddlStartHour'] == "8") ? "selected=\"selected\"" : "" ?>>8</option>
                                <option value="9" <?= ($_POST['ddlStartHour'] == "9") ? "selected=\"selected\"" : "" ?>>9</option>
                                <option value="10" <?= ($_POST['ddlStartHour'] == "10") ? "selected=\"selected\"" : "" ?>>10</option>
                                <option value="11" <?= ($_POST['ddlStartHour'] == "11") ? "selected=\"selected\"" : "" ?>>11</option>
                                <option value="0" <?= ($_POST['ddlStartHour'] == "0") ? "selected=\"selected\"" : "" ?>>12</option>
                            </select> : 
                            <select id="ddlStartMinute" name="ddlStartMinute">
                                <option value="00" <?= ($_POST['ddlStartMinute'] == "00") ? "selected=\"selected\"" : "" ?>>00</option>
                                <option value="15" <?= ($_POST['ddlStartMinute'] == "15") ? "selected=\"selected\"" : "" ?>>15</option>
                                <option value="30" <?= ($_POST['ddlStartMinute'] == "30") ? "selected=\"selected\"" : "" ?>>30</option>
                                <option value="45" <?= ($_POST['ddlStartMinute'] == "45") ? "selected=\"selected\"" : "" ?>>45</option>
                            </select> 
                            <select id="ddlStartSuffix" name="ddlStartSuffix">
                                <option value="AM" <?= ($_POST['ddlStartSuffix'] == "AM") ? "selected=\"selected\"" : "" ?>>AM</option>
                                <option value="PM" <?= ($_POST['ddlStartSuffix'] == "PM") ? "selected=\"selected\"" : "" ?>>PM</option>
                            </select>
						</td>
					</tr>
                    <tr>
                    	<td>End Date: </td>
                        <td>
                            <input type="text" id="txtEndDate" name="txtEndDate"  value="<?= ($_POST['txtEndDate'] != "") ? $_POST['txtEndDate'] : date("Y-m-d")  ?>" />  
                            <select id="ddlEndHour" name="ddlEndHour">
                                <option value="1" <?= ($_POST['ddlEndHour'] == "1") ? "selected=\"selected\"" : "" ?>>1</option>
                                <option value="2" <?= ($_POST['ddlEndHour'] == "2") ? "selected=\"selected\"" : "" ?>>2</option>
                                <option value="3" <?= ($_POST['ddlEndHour'] == "3") ? "selected=\"selected\"" : "" ?>>3</option>
                                <option value="4" <?= ($_POST['ddlEndHour'] == "4") ? "selected=\"selected\"" : "" ?>>4</option>
                                <option value="5" <?= ($_POST['ddlEndHour'] == "5") ? "selected=\"selected\"" : "" ?>>5</option>
                                <option value="6" <?= ($_POST['ddlEndHour'] == "6") ? "selected=\"selected\"" : "" ?>>6</option>
                                <option value="7" <?= ($_POST['ddlEndHour'] == "7") ? "selected=\"selected\"" : "" ?>>7</option>
                                <option value="8" <?= ($_POST['ddlEndHour'] == "8") ? "selected=\"selected\"" : "" ?>>8</option>
                                <option value="9" <?= ($_POST['ddlEndHour'] == "9") ? "selected=\"selected\"" : "" ?>>9</option>
                                <option value="10" <?= ($_POST['ddlEndHour'] == "10") ? "selected=\"selected\"" : "" ?>>10</option>
                                <option value="11" <?= ($_POST['ddlEndHour'] == "11") ? "selected=\"selected\"" : "" ?>>11</option>
                                <option value="0" <?= ($_POST['ddlEndHour'] == "0") ? "selected=\"selected\"" : "" ?>>12</option>
                            </select> : 
                            <select id="ddlEndMinute" name="ddlEndMinute">
                                <option value="00" <?= ($_POST['ddlEndMinute'] == "00") ? "selected=\"selected\"" : "" ?>>00</option>
                                <option value="15" <?= ($_POST['ddlEndMinute'] == "15") ? "selected=\"selected\"" : "" ?>>15</option>
                                <option value="30" <?= ($_POST['ddlEndMinute'] == "30") ? "selected=\"selected\"" : "" ?>>30</option>
                                <option value="45" <?= ($_POST['ddlEndMinute'] == "45") ? "selected=\"selected\"" : "" ?>>45</option>
                            </select> 
                            <select id="ddlEndSuffix" name="ddlEndSuffix">
                                <option value="AM" <?= ($_POST['ddlEndSuffix'] == "AM") ? "selected=\"selected\"" : "" ?>>AM</option>
                                <option value="PM" <?= ($_POST['ddlEndSuffix'] == "PM") ? "selected=\"selected\"" : "" ?>>PM</option>                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                        	<input type="submit" id="btnSubmit" name="btnSubmit" value="Run Report" /> 
                            <small><i>(Start and End Dates need to be in yyyy-mm-dd format. ex: <?=date("Y-m-d")?>)</i></small>
						</td>
                    </tr>
				</table>
			</form>    
		</div> <br>    
    <?
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
		
		$sql = "SELECT count(foxy_transaction_id) as TransactionCount 
						,coalesce(sum(foxy_transaction_product_total), 0) as ProductTotal
						,coalesce(sum(foxy_transaction_tax_total), 0) as TaxTotal
						,coalesce(sum(foxy_transaction_shipping_total), 0) as ShippingTotal
						,coalesce(sum(foxy_transaction_order_total), 0) as OrderTotal
				FROM " . WP_TRANSACTION_TABLE . " 
				WHERE foxy_transaction_date >= '$StartDate' and foxy_transaction_date <= '$EndDate'";            
		$OrderTotals = $wpdb->get_row($sql);
		if(!empty($OrderTotals))
		{
			echo("<div><b>Orders</b></div>");
			echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
			 		<thead>
                		<tr>
							<th class=\"manage-column\" scope=\"col\">Total Transactions</td>
							<th class=\"manage-column\" scope=\"col\">Product Total</td>
							<th class=\"manage-column\" scope=\"col\">Tax Total</td>
							<th class=\"manage-column\" scope=\"col\">Shipping Total</td>
							<th class=\"manage-column\" scope=\"col\">Order Total</td>
						</tr>
					<tr>
						<td>" . $OrderTotals->TransactionCount . "</td>
						<td>$" . $OrderTotals->ProductTotal . "</td>
						<td>$" . $OrderTotals->TaxTotal . "</td>
						<td>$" . $OrderTotals->ShippingTotal . "</td>
						<td>$" . $OrderTotals->OrderTotal . "</td>
					</tr>
				  </table>");
			$sql = "SELECT foxy_transaction_cc_type as TypeOfCard, count(foxy_transaction_ID) as TypeCount
				FROM " . WP_TRANSACTION_TABLE . " 
				WHERE foxy_transaction_date >= '$StartDate' and foxy_transaction_date <= '$EndDate'
				group by TRIM(LOWER(foxy_transaction_cc_type))";
			$CreditCards = $wpdb->get_results($sql);         
			if(!empty($CreditCards))
			{
				echo("<br><div><b>Credit Cards</b></div>");
				echo("<table class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
						<thead>
							<tr>
								<th class=\"manage-column\" scope=\"col\">Credit Card Type</td>
								<th class=\"manage-column\" scope=\"col\">Credit Card Total</td>
							</tr>
						</thead>");
				foreach($CreditCards as $Card)
				{
					echo("<tr>
							<td>" . $Card->TypeOfCard . "</td>
							<td>" . $Card->TypeCount . "</td>
						  </tr>");
				}
				echo("</table>");
			}
		}
		else
		{
			echo("<div>There are currently no transactions for that date range</div>");
		}			
	}	
	?>
    </div>    
    <?
}
?>