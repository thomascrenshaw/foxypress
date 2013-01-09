<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'status_management_postback');

function status_management_postback()
{
	global $wpdb;	
	$PageName = filter(foxypress_FixGetVar("page"));
	if($PageName == "status-management")
	{
		$StatusID = filter(foxypress_FixGetVar("status", ""));
		$Action = filter(foxypress_FixGetVar("action", ""));		
		if(isset($_POST['foxy_sm_new_status_submit']))
		{
			$NewDescription = foxypress_FixPostVar("foxy_sm_new_status", "");	
			if($NewDescription != "")
			{
				$sql = "insert into " . $wpdb->prefix . "foxypress_transaction_status(foxy_transaction_status_description) values ('$NewDescription')";
				$wpdb->query($sql);
			}
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management");
		}
		
		if($Action == "delete" && $StatusID != "" && $StatusID != "1")
		{
			//delete status
			$sql = "delete from  " . $wpdb->prefix . "foxypress_transaction_status WHERE foxy_transaction_status = '$StatusID'";
			$wpdb->query($sql);
			//update transactions in limbo to unprocessed
			$sql = "update " . $wpdb->prefix ."foxypress_transaction SET foxy_transaction_status = '1' WHERE foxy_transaction_status = '$StatusID'";
			$wpdb->query($sql);
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management");
		}
	}
}

function status_management_page_load()
{
	global $wpdb;	
	$StatusID = filter(foxypress_FixGetVar("status", ""));
	$Action = filter(foxypress_FixGetVar("action", ""));
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
                    	<td><?php _e('Status ID', 'foxypress'); ?> &nbsp;</td>
                        <td><?php echo($drStatus->foxy_transaction_status) ?></td>
                    </tr>
                    <tr>
                    	<td><?php _e('Status Description', 'foxypress'); ?> &nbsp;</td>
                        <td><input type="text" name="foxy_sm_status_description" id="foxy_sm_status_description" value="<?php echo(stripslashes($drStatus->foxy_transaction_status_description)) ?>" /></td>
                    </tr>
                    <?php if($StatusID != 1) { ?>
                    <tr>
                    	<td><?php _e('Email Customer', 'foxypress'); ?></td>
                        <td><input type="checkbox" value="1" <?php echo (($drStatus->foxy_transaction_status_email_flag == "1") ? "checked='checked'"  : "") ?> id="foxy_sm_status_email_flag" name="foxy_sm_status_email_flag" /> <small>If this is checked, the customer will be emailed when the status on their transaction is changed to this status</small></td>
                    </tr>
                    <tr>	
                    	<td><?php _e('Email Subject', 'foxypress'); ?></td>
                        <td><input type="text" value="<?php echo(stripslashes($drStatus->foxy_transaction_status_email_subject)) ?>" id="foxy_sm_status_email_subject" name="foxy_sm_status_email_subject" /></td>
                    </tr>
                    <tr>	
                    	<td><?php _e('Email Body', 'foxypress'); ?></td>
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
                    	<td colspan="2"><input type="submit" id="foxy_sm_status_submit" name="foxy_sm_status_submit" value="<?php _e('Save', 'foxypress'); ?>" /> <?php echo($sm_error) ?></td>
                    </tr>
            	</table>
            	<div style="float:left; width:400px; margin-left:20px">
					<p><i><?php _e('You can use the following legend to populate the email body', 'foxypress'); ?></i></p>
					<table style="margin:10px 0; display:block;">	
						<tr>
							<td width="200"><strong>{{order_id}}</strong></td>
							<td><?php _e('ID of Order', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{order_date}}</strong></td>
							<td><?php _e('Order Date', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{product_total}}</strong></td>
							<td><?php _e('Order Product Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{product_listing}}</strong></td>
							<td><?php _e('Product Item listing', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{tax_total}}</strong></td>
							<td><?php _e('Order Tax Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{shipping_total}}</strong></td>
							<td><?php _e('Order Shipping Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{shipping_method}}</strong></td>
							<td><?php _e('Order Shipping Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{order_total}}</strong></td>
							<td><?php _e('Order Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{discount_codes}}</strong></td>
							<td><?php _e('Discount Codes and Amounts Used', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{cc_type}}</strong></td>
							<td><?php _e('Credit Card Type Used', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_first_name}}</strong></td>
							<td><?php _e('Customer\'s First Name', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_last_name}}</strong></td>
							<td><?php _e('Customer\'s Last Name', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_email}}</strong></td>
							<td><?php _e('Customer\'s Email', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{tracking_number}}</strong></td>
							<td><?php _e('Tracking Number (if entered)', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_address1}}</strong></td>
							<td><?php _e('Customer\'s Billing Address 1', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_address2}}</strong></td>
							<td><?php _e('Customer\'s Billing Address 2', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_city}}</strong></td>
							<td><?php _e('Customer\'s Billing City', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_state}}</strong></td>
							<td><?php _e('Customer\'s Billing State', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_zip}}</strong></td>
							<td><?php _e('Customer\'s Billing Zip', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_billing_country}}</strong></td>
							<td><?php _e('Customer\'s Billing Country', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_first_name}}</strong></td>
							<td><?php _e('Customer\'s Shipping First Name', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_last_name}}</strong></td>
							<td><?php _e('Customer\'s Shipping Last Name', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_address1}}</strong></td>
							<td><?php _e('Customer\'s Shipping Address 1', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_address2}}</strong></td>
							<td><?php _e('Customer\'s Shipping Address 2', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_city}}</strong></td>
							<td><?php _e('Customer\'s Shipping City', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_state}}</strong></td>
							<td><?php _e('Customer\'s Shipping State', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_zip}}</strong></td>
							<td><?php _e('Customer\'s Shipping Zip', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{customer_shipping_country}}</strong></td>
							<td><?php _e('Customer\'s Shipping Country', 'foxypress'); ?></td>
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
								<a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management&action=edit&status=" . $ts->foxy_transaction_status . "\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>
							</td>
							<td>" . ($ts->foxy_transaction_status_email_flag == "1" ? "Y" : "N") . "</td>
							<td>" . ($ts->foxy_transaction_status_email_tracking == "1" ? "Y" : "N") . "</td>
							<td> " .
								(( $ts->foxy_transaction_status != "1" ) ? 
								"<a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=status-management&action=delete&status=" . $ts->foxy_transaction_status . "\" onclick=\"return confirm('" . __('Are you sure you want to delete this status? Any transaction tied to this status will be set back to Uncategorized.', 'foxypress') . "')\">" . __('Delete', 'foxypress') . "</a>"
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
                        <td><legend><?php _e('New Status', 'foxypress'); ?>: </legend></td>
                        <td><input type="text" name="foxy_sm_new_status" id="foxy_sm_new_status" class="input" size="30" maxlength="30" value="" /></td>
                        <td><input type="submit" name="foxy_sm_new_status_submit" id="foxy_sm_new_status_submit" class="button bold" value="<?php _e('Save','foxypress'); ?> &raquo;" /></td>
                    </tr>
                </table>
            </div>
            <div style="clear:both; height:1px;">&nbsp;</div>
        </div>
    </form>
        
	<table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">	
            <thead>
                <tr>
                    <th class="manage-column" scope="col"><?php _e('Status', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col"><?php _e('Email Customer', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col"><?php _e('Include Tracking', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
    	<?php echo($Statuses) ?>
    </table>
    <?php
}
?>