<?
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_menu', 'status_management_menu');
add_action('admin_init', 'status_management_postback');

function status_management_menu()  {
	global $wpdb;
	$allowed_group = 'manage_options';	
	if (function_exists('add_submenu_page')) 
	 {
	   add_submenu_page('foxypress', __('Status Management','foxypress'), __('Status Management','foxypress'), $allowed_group, 'status-management', 'status_management_page_load');
	 }
}

function status_management_postback()
{
	global $wpdb;	
	$StatusID = foxypress_FixGetVar("status", "");
	$Action = foxypress_FixGetVar("action", "");
	
	if(isset($_POST['foxy_sm_new_status_submit']))
	{
		$NewDescription = foxypress_FixPostVar("foxy_sm_new_status", "");	
		if($NewDescription != "")
		{
			$sql = "insert into " . WP_TRANSACTION_STATUS_TABLE . " (foxy_transaction_status_description) values ('$NewDescription')";
			$wpdb->query($sql);
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=status-management");
	}
	
	if($Action == "delete" && $StatusID != "" && $StatusID != "1")
	{
		//delete status
		$sql = "delete from  " . WP_TRANSACTION_STATUS_TABLE . " WHERE foxy_transaction_status = '$StatusID'";
		$wpdb->query($sql);
		//update transactions in limbo to unprocessed
		$sql = "update " . WP_TRANSACTION_TABLE . " SET foxy_transaction_status = '1' WHERE foxy_transaction_status = '$StatusID'";
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=status-management");
	}
}

function status_management_page_load()
{
	global $wpdb;	
	$StatusID = foxypress_FixGetVar("status", "");
	$Action = foxypress_FixGetVar("action", "");
	$sm_error = "";
	Begin_Status_Management();		
	if($Action == "edit" && $StatusID != "" && $StatusID != "1")
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
				$sql = "UPDATE " . WP_TRANSACTION_STATUS_TABLE . " SET foxy_transaction_status_description='$status_description', foxy_transaction_status_email_flag='$status_email_flag', foxy_transaction_status_email_subject='$status_email_subject', foxy_transaction_status_email_body='$status_email_body', foxy_transaction_status_email_tracking = '$status_email_tracking'   WHERE foxy_transaction_status = '$StatusID'";
				$wpdb->query($sql);
				$sm_error = "<span class=\"error\">Successfully Saved</span>";
			}
		}		
		//edit status
		$drStatus = $wpdb->get_row("SELECT * FROM " . WP_TRANSACTION_STATUS_TABLE . " WHERE foxy_transaction_status = '$StatusID'");		
		if(!empty($drStatus))
		{
			?>
            <form id="statusForm" name="statusForm" method="POST">
            	<table>
                	<tr>
                    	<td>Status ID &nbsp;</td>
                        <td><?=$drStatus->foxy_transaction_status?>
                    </tr>
                    <tr>
                    	<td>Status Description &nbsp;</td>
                        <td><input type="text" name="foxy_sm_status_description" id="foxy_sm_status_description" value="<?=stripslashes($drStatus->foxy_transaction_status_description)?>" /></td>
                    </tr>
                    <tr>
                    	<td>Email Customer</td>
                        <td><input type="checkbox" value="1" <?= ($drStatus->foxy_transaction_status_email_flag == "1") ? "checked='checked'"  : "" ?> id="foxy_sm_status_email_flag" name="foxy_sm_status_email_flag" /> <small>If this is checked, the customer will be emailed when the status on their transaction is changed to this status</small>
                    </tr>
                    <tr>	
                    	<td>Email Subject</td>
                        <td><input type="text" value="<?=stripslashes($drStatus->foxy_transaction_status_email_subject)?>" id="foxy_sm_status_email_subject" name="foxy_sm_status_email_subject" /></td>
                    </tr>
                    <tr>	
                    	<td>Email Body</td>
                        <td><textarea  id="foxy_sm_status_email_body" name="foxy_sm_status_email_body" cols="50" rows="5"><?=stripslashes($drStatus->foxy_transaction_status_email_body)?></textarea></td>
                    </tr>
                    <tr>
                    	<td>Include Tracking</td>
                        <td><input type="checkbox" value="1" <?= ($drStatus->foxy_transaction_status_email_tracking == "1") ? "checked='checked'"  : "" ?> id="foxy_sm_status_email_tracking" name="foxy_sm_status_email_tracking" />
                    </tr>
                    <tr>
                    	<td colspan="2"><input type="submit" id="foxy_sm_status_submit" name="foxy_sm_status_submit" value="Save" /> <?=$sm_error?></td>
                    </tr>
            	</table>
            </form>
            <?
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
	End_Status_Management();
}

function GetStatuses()
{
	global $wpdb;	
	//show all statuses
	$sql = "SELECT * FROM " . WP_TRANSACTION_STATUS_TABLE . "";            
	$TransactionStatuses = $wpdb->get_results($sql);
	$Statuses = "";
	if( !empty($TransactionStatuses) )
	{
		foreach ( $TransactionStatuses as $ts ) 
		{
			$Statuses .= "<tr>
							<td> " . 
								(( $ts->foxy_transaction_status != "1" ) ? 
								"<a href=\"" . $_SERVER['PHP_SELF'] . "?page=status-management&action=edit&status=" . $ts->foxy_transaction_status . "\">" . stripslashes($ts->foxy_transaction_status_description) . "</a>" : stripslashes($ts->foxy_transaction_status_description)) . 
							"</td>
							<td>" . ($ts->foxy_transaction_status_email_flag == "1" ? "Y" : "N") . "</td>
							<td>" . ($ts->foxy_transaction_status_email_tracking == "1" ? "Y" : "N") . "</td>
							<td> " .
								(( $ts->foxy_transaction_status != "1" ) ? 
								"<a href=\"" . $_SERVER['PHP_SELF'] . "?page=status-management&action=delete&status=" . $ts->foxy_transaction_status . "\" onclick=\"return confirm('Are you sure you want to delete this status? Any transaction tied to this status will be set back to Uncategorized.')\">Delete</a>"
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
    	<?=$Statuses?>
    </table>
    <?
}

function End_Status_Management()
{
	?>
    </div>    
    <?
}

function Begin_Status_Management()
{
	?>
	<div class="wrap">
    	<h2><?php _e('Status Management','status-management'); ?></h2>		
    <?
}
?>