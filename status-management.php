<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'status_management_postback');

function status_management_postback()
{
	global $wpdb;	
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "status-management")
	{
		$StatusID = foxypress_FixGetVar("status", "");
		$Action = foxypress_FixGetVar("action", "");		
		if(isset($_POST['foxy_sm_new_status_submit']))
		{
			$NewDescription = foxypress_FixPostVar("foxy_sm_new_status", "");	
			if($NewDescription != "")
			{
				$sql = "insert into " . $wpdb->prefix . "foxypress_transaction_status(foxy_transaction_status_description) values ('$NewDescription')";
				$wpdb->query($sql);
			}
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management");
		}
		
		if($Action == "delete" && $StatusID != "" && $StatusID != "1")
		{
			//delete status
			$sql = "delete from  " . $wpdb->prefix . "foxypress_transaction_status WHERE foxy_transaction_status = '$StatusID'";
			$wpdb->query($sql);
			//update transactions in limbo to unprocessed
			$sql = "update " . $wpdb->prefix ."foxypress_transaction SET foxy_transaction_status = '1' WHERE foxy_transaction_status = '$StatusID'";
			$wpdb->query($sql);
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management");
		}
	}
}

function status_management_page_load()
{
	global $wpdb;	
	$StatusID = foxypress_FixGetVar("status", "");
	$Action = foxypress_FixGetVar("action", "");
	$sm_error = "";
	?>
    <div class="wrap">
    	<h2><?php _e('Status Management','status-management'); ?></h2>	
   	<?php	
	if($Action == "edit" && $StatusID != "" && $StatusID != "0")
	{
		if(isset($_POST['foxy_sm_status_submit']))
		{
			$status_description = foxypress_FixPostVar("foxy_sm_status_description");	
			$status_email_flag = (foxypress_FixPostVar("foxy_sm_status_email_flag") == "1") ? "1" : "0";	
			$status_email_subject = foxypress_FixPostVar("foxy_sm_status_email_subject");	
			$status_email_body = foxypress_FixPostVar("foxy_sm_status_email_body");	
			$status_email_tracking = (foxypress_FixPostVar("foxy_sm_status_email_tracking") == "1") ? "1" : "0";
			if($status_description == "")
			{
				$sm_error = "<span class=\"error\">Description cannot be blank</span>";
			}
			else
			{
				//proceed with update
				$sql = "UPDATE " . $wpdb->prefix . "foxypress_transaction_status 
						SET foxy_transaction_status_description='$status_description'
							,foxy_transaction_status_email_flag='$status_email_flag'
							,foxy_transaction_status_email_subject='$status_email_subject'
							,foxy_transaction_status_email_body='$status_email_body'
							,foxy_transaction_status_email_tracking = '$status_email_tracking'   
						WHERE foxy_transaction_status = '$StatusID'";
				$wpdb->query($sql);
				$sm_error = "<span class=\"required\">*Successfully Saved</span>";
			}
		}		
		//edit status
		$drStatus = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_transaction_status WHERE foxy_transaction_status = '$StatusID'");		
		if(!empty($drStatus))
		{
			?>
            <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
            <form id="statusForm" name="statusForm" method="POST">
            	<table style="float:left; width:650px">
                	<tr>
                    	<td>Status ID &nbsp;</td>
                        <td><?php echo($drStatus->foxy_transaction_status) ?></td>
                    </tr>
                    <tr>
                    	<td>Status Description &nbsp;</td>
                        <td><input type="text" name="foxy_sm_status_description" id="foxy_sm_status_description" value="<?php echo(stripslashes($drStatus->foxy_transaction_status_description)) ?>" /></td>
                    </tr>
                    <?php if($StatusID != 1) { ?>
                    <tr>
                    	<td>Email Customer</td>
                        <td><input type="checkbox" value="1" <?php echo (($drStatus->foxy_transaction_status_email_flag == "1") ? "checked='checked'"  : "") ?> id="foxy_sm_status_email_flag" name="foxy_sm_status_email_flag" /> <small>If this is checked, the customer will be emailed when the status on their transaction is changed to this status</small></td>
                    </tr>
                    <tr>	
                    	<td>Email Subject</td>
                        <td><input type="text" value="<?php echo(stripslashes($drStatus->foxy_transaction_status_email_subject)) ?>" id="foxy_sm_status_email_subject" name="foxy_sm_status_email_subject" /></td>
                    </tr>
                    <tr>	
                    	<td>Email Body</td>
                        <td>
                        	<textarea id="foxy_sm_status_email_body" name="foxy_sm_status_email_body" cols="50" rows="5"><?php echo(stripslashes($drStatus->foxy_transaction_status_email_body)) ?></textarea>
                            <script type="text/javascript">
								CKEDITOR.replace( 'foxy_sm_status_email_body' );
							</script>
                        </td>
                    </tr>
                    <!--<tr>
                    	<td>Include Tracking</td>
                        <td><input type="checkbox" value="1" <?php //echo(($drStatus->foxy_transaction_status_email_tracking == "1") ? "checked='checked'"  : "") ?> id="foxy_sm_status_email_tracking" name="foxy_sm_status_email_tracking" /></td>
                    </tr>-->
                    <?php } ?>
                     <tr>
                    	<td colspan="2"><input type="submit" id="foxy_sm_status_submit" name="foxy_sm_status_submit" value="Save" /> <?php echo($sm_error) ?></td>
                    </tr>
            	</table>
            	<div style="float:left; width:400px; margin-left:20px">
					<p><i> You can use the following legend to populate the email body</i></p>
					<table style="margin:10px 0; display:block;">	
						<tr>
							<td width="200"><strong>{{order_id}}</strong></td>
							<td>ID of Order</td>
						</tr>
						<tr>
							<td><strong>{{order_date}}</strong></td>
							<td>Order Date</td>
						</tr>
						<tr>
							<td><strong>{{product_total}}</strong></td>
							<td>Order Product Total</td>
						</tr>
						<tr>
							<td><strong>{{product_listing}}</strong></td>
							<td>Product Item listing</td>
						</tr>
						<tr>
							<td><strong>{{tax_total}}</strong></td>
							<td>Order Tax Total</td>
						</tr>
						<tr>
							<td><strong>{{shipping_total}}</strong></td>
							<td>Order Shipping Total</td>
						</tr>
						<tr>
							<td><strong>{{shipping_method}}</strong></td>
							<td>Order Shipping Total</td>
						</tr>
						<tr>
							<td><strong>{{order_total}}</strong></td>
							<td>Order Total</td>
						</tr>
						<tr>
							<td><strong>{{discount_codes}}</strong></td>
							<td>Discount Codes and Amounts Used</td>
						</tr>
						<tr>
							<td><strong>{{cc_type}}</strong></td>
							<td>Credit Card Type Used</td>
						</tr>
						<tr>
							<td><strong>{{customer_first_name}}</strong></td>
							<td>Customer's First Name</td>
						</tr>
						<tr>
							<td><strong>{{customer_last_name}}</strong></td>
							<td>Customers' Last Name</td>
						</tr>
						<tr>
							<td><strong>{{customer_email}}</strong></td>
							<td>Customer's Email</td>
						</tr>
						<tr>
							<td><strong>{{tracking_number}}</strong></td>
							<td>Tracking Number (if entered)</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_address1}}</strong></td>
							<td>Customer's Billing Address 1</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_address2}}</strong></td>
							<td>Customer's Billing Address 2</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_city}}</strong></td>
							<td>Customer's Billing City</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_state}}</strong></td>
							<td>Customer's Billing State</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_zip}}</strong></td>
							<td>Customer's Billing Zip</td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_country}}</strong></td>
							<td>Customer's Billing Country</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_first_name}}</strong></td>
							<td>Customer's Shipping First Name</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_last_name}}</strong></td>
							<td>Customer's Shipping Last Name</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_address1}}</strong></td>
							<td>Customer's Shipping Address 1</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_address2}}</strong></td>
							<td>Customer's Shipping Address 2</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_city}}</strong></td>
							<td>Customer's Shipping City</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_state}}</strong></td>
							<td>Customer's Shipping State</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_zip}}</strong></td>
							<td>Customer's Shipping Zip</td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_country}}</strong></td>
							<td>Customer's Shipping Country</td>
						</tr>
	            	</table>
	        	</div>
            </form>
            <?php
		}
		else
		{
			GetStatuses();	
		}
	}
	else
	{
		GetStatuses();			
	}
	?>
    </div>
    <?php
}

function GetStatuses()
{
	global $wpdb;	
	//show all statuses
	$sql = "SELECT * FROM " . $wpdb->prefix . "foxypress_transaction_status";            
	$TransactionStatuses = $wpdb->get_results($sql);
	$Statuses = "";
	if( !empty($TransactionStatuses) )
	{
		foreach ( $TransactionStatuses as $ts ) 
		{
			$Statuses .= "<tr>
							<td> 
								<a href=\"" . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management&action=edit&status=" . $ts->foxy_transaction_status . "\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>
							</td>
							<td>" . ($ts->foxy_transaction_status_email_flag == "1" ? "Y" : "N") . "</td>
							<td>" . ($ts->foxy_transaction_status_email_tracking == "1" ? "Y" : "N") . "</td>
							<td> " .
								(( $ts->foxy_transaction_status != "1" ) ? 
								"<a href=\"" . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management&action=delete&status=" . $ts->foxy_transaction_status . "\" onclick=\"return confirm('Are you sure you want to delete this status? Any transaction tied to this status will be set back to Uncategorized.')\">Delete</a>"
								: "") . 
						"   </td>
						</tr>";
		}        
	}
	?>
    <form name="foxy_add_status" id="foxy_add_status" class="wrap" method="post">
        <div id="linkadvanceddiv" class="postbox">
            <div style="float: left; width: 98%; clear: both;" class="inside">
                <table cellspacing="5" cellpadding="5">
                    <tr>
                        <td><legend>New Status: </legend></td>
                        <td><input type="text" name="foxy_sm_new_status" id="foxy_sm_new_status" class="input" size="30" maxlength="30" value="" /></td>
                        <td><input type="submit" name="foxy_sm_new_status_submit" id="foxy_sm_new_status_submit" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" /></td>
                    </tr>
                </table>
            </div>
            <div style="clear:both; height:1px;">&nbsp;</div>
        </div>
    </form>
        
	<table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">	
            <thead>
                <tr>
                    <th class="manage-column" scope="col">Status</th>
                    <th class="manage-column" scope="col">Email Customer</th>
                    <th class="manage-column" scope="col">Include Tracking</th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
    	<?php echo($Statuses) ?>
    </table>
    <?php
}
?>