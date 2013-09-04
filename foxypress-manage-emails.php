<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

global $wpdb;

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
//add_action('admin_init', 'manage_emails_postback');

//page load
function foxypress_manage_emails_page_load() 
{
	global $wpdb;
	
	if(isset($_GET['mode']) && $_GET['mode']=='edit')
	{
		if(isset($_POST['foxy_em_save']))
		{
			$subject= filter($_POST['subject']);
			$templatename = filter($_POST['templatename']);
			$content=filter($_POST['content']);
			$from=filter($_POST['from']);		

			if($templatename!=''&& $subject!=''){
				if(!is_numeric(filter($_GET['id']))){
					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&&action=error',$_REQUEST['page'],'manage-emails');
					echo 'Invalid Template ID...';
					echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';
				}				
	                
	            $me = $wpdb->get_row("SELECT * from " . $wpdb->prefix ."foxypress_email_templates WHERE email_template_id='" . filter($_GET['id']) . "'");
				if(!empty($me)){
					$sql = "UPDATE  ". $wpdb->prefix . "foxypress_email_templates set foxy_email_template_name='".$templatename."', foxy_email_template_subject='".$subject."', foxy_email_template_email_body='".$content."', foxy_email_template_from='" . $from . "' WHERE email_template_id='" . filter($_GET['id']) . "'";
					$wpdb->query($sql);
					$sm_error = "<div class='updated' id='message'>" . __('Your email template has been successfully saved', 'foxypress') . "!</div>";
				}else{
					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&&action=error',$_REQUEST['page'],'manage-emails');
	                echo 'Invalid Template ID...';
	                echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';	
				}				
			}else{
				$sm_error = "<div class='error' id='message'>" . __('Your email template name and subject cannot be blank', 'foxypress') . ".</div>";
			}
			echo $sm_error;
		}
		$me = $wpdb->get_row("SELECT * from " . $wpdb->prefix ."foxypress_email_templates WHERE email_template_id='" . filter($_GET['id']) . "'");
		if(!is_numeric(filter($_GET['id']))){
			$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&&action=error',$_REQUEST['page'],'manage-emails');
			echo 'Invalid Template ID...';
			echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';
		}	
	?>
	        <div class="wrap">
	    		<h2><?php _e('Manage Emails', 'foxypress'); ?> <a href='<?php echo(admin_url()); ?>edit.php?post_type=foxypress_product&page=manage-emails&mode=new' class="add-new-h2"><?php _e('Add New', 'foxypress'); ?></a></h2>
	        	<table cellpadding="10" style="float:left; width:650px">
	        		<tr>
	        			<td width="180"><?php _e('Template ID', 'foxypress'); ?></td><td><?php _e(filter($_GET['id'])); ?></td>
	        		</tr>
                    <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
	        		<form method="POST" name="statusForm" id="statusForm"> 
	        		<tr>
	        			<td><?php _e('Template Name', 'foxypress'); ?></td>
						<td><input type="text" name="templatename" value="<?php _e($me->foxy_email_template_name); ?>" size="50" /></td>
	        		</tr>
	        		<tr>
						<td><?php _e('Subject', 'foxypress'); ?></td>
						<td><input type="text" name="subject" value="<?php _e($me->foxy_email_template_subject); ?>" size="50" /></td>
					</tr>
                    <tr>
                        <td><?php _e('From Email', 'foxypress'); ?></td> 
                        <td><input type="text" name="from" value="<?php _e($me->foxy_email_template_from); ?>" size="50" /></td>
                    </tr>
					<tr>
				        <td><?php _e('Content', 'foxypress'); ?></td>
						<td>
                        	<textarea name="content" cols="50" rows="5"><?php echo($me->foxy_email_template_email_body); ?></textarea>
                            <script type="text/javascript">
								CKEDITOR.replace( 'content', {width:"500"} );
							</script>
                        </td>
			        </tr>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="<?php _e('Save', 'foxypress'); ?>" /></td>
			        </tr>
					</form>
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
							<td><strong>{{tax_total}}</strong></td>
							<td><?php _e('Order Tax Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{shipping_total}}</strong></td>
							<td><?php _e('Order Shipping Total', 'foxypress'); ?></td>
						</tr>
						<tr>
							<td><strong>{{order_total}}</strong></td>
							<td><?php _e('Order Total', 'foxypress'); ?></td>
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
							<td><?php _e('Customer\'s Shipping City', 'foxypress'); ?></td>
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
	        <?php
		}else if(isset($_GET['mode']) && $_GET['mode']=='new'){
			if(isset($_POST['foxy_em_save'])){
				$subject=filter($_POST['subject']);
				$templatename = filter($_POST['templatename']);
				$content=filter($_POST['content']);
				$from=filter($_POST['from']);
				if($templatename!='' && $subject!=''){
					$sql = "INSERT INTO  ". $wpdb->prefix . "foxypress_email_templates VALUES(null,'$templatename','$subject','$content','$from')";
					$wpdb->query($sql);

					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&&action=updated',$_REQUEST['page'],'manage-emails');
	                echo 'Saving Template...';
	                echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';					
				}else{
					$sm_error = "<div class='error' id='message'>" . __('Your email template name and subject cannot be blank', 'foxypress') . ".</div>";
				}
				echo $sm_error;
				?>
    	        <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
				<div class="wrap">
					<h2><?php _e('Manage Emails', 'foxypress'); ?></h2>
					<table cellpadding="10" style="float:left; width:650px;">
						<form method="POST" name="statusForm" id="statusForm"> 
						<tr>
							<td><?php _e('Template Name', 'foxypress'); ?></td>
							<td><input type="text" name="templatename" value="" size="50" /></td>
						</tr>
						<tr>
							<td><?php _e('Subject', 'foxypress'); ?></td> 
							<td><input type="text" name="subject" value="" size="50" /></td>
						</tr>
                        <tr>
							<td><?php _e('From Email', 'foxypress'); ?></td> 
							<td><input type="text" name="from" value="" size="50" /></td>
						</tr>
						<tr>
							<td><?php _e('Content', 'foxypress'); ?></td>
							<td>
                            	<textarea name="content" cols="70" rows="10"></textarea>
                                <script type="text/javascript">
									CKEDITOR.replace( 'content', {width:"500"} );
								</script>
                            </td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="<?php _e('Save', 'foxypress'); ?>" /></td>
						</tr>
						</form>
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
								<td><strong>{{tax_total}}</strong></td>
								<td><?php _e('Order Tax Total', 'foxypress'); ?></td>
							</tr>
							<tr>
								<td><strong>{{shipping_total}}</strong></td>
								<td><?php _e('Order Shipping Total', 'foxypress'); ?></td>
							</tr>
							<tr>
								<td><strong>{{order_total}}</strong></td>
								<td><?php _e('Order Total', 'foxypress'); ?></td>
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
								<td><?php _e('Customer\'s Shipping City', 'foxypress'); ?>x</td>
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
				<?php
			}else{
			?>
		<div class="wrap">
			<h2><?php _e('Manage Emails', 'foxypress'); ?></h2>
			<table cellpadding="10" style="float:left; width:650px;">
               <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
				<form method="POST" name="statusForm" id="statusForm"> 
				<tr>
					<td><strong><?php _e('Template Name', 'foxypress'); ?>: </strong></td><td>  <input type="text" name="templatename" value="" size="50" /></td>
				</tr>
				<tr>
					<td><strong><?php _e('Subject', 'foxypress'); ?></strong></td> <td><input type="text" name="subject" value="" size="50" /></td>
				</tr>
                <tr>
					<td><strong><?php _e('From Email', 'foxypress'); ?></strong></td> <td><input type="text" name="from" value="" size="50" /></td>
				</tr>
				<tr>
					<td><strong><?php _e('Content', 'foxypress'); ?></strong></td>
					<td>
                    	<textarea name="content" cols="70" rows="10"></textarea>
						 <script type="text/javascript">
                                    CKEDITOR.replace( 'content', {width:"500"} );
							</script>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="<?php _e('Save', 'foxypress'); ?>" /></td>
				</tr>
				</form>
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
						<td><strong>{{tax_total}}</strong></td>
						<td><?php _e('Order Tax Total', 'foxypress'); ?></td>
					</tr>
					<tr>
						<td><strong>{{shipping_total}}</strong></td>
						<td><?php _e('Order Shipping Total', 'foxypress'); ?></td>
					</tr>
					<tr>
						<td><strong>{{order_total}}</strong></td>
						<td><?php _e('Order Total', 'foxypress'); ?></td>
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
						<td><?php _e('Customer\'s Shipping City', 'foxypress'); ?>x</td>
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
		<?php
		}
	}else{
		$action = foxypress_FixGetVar('action');
		if ($action === 'updated') { 
			echo("<div class='updated' id='message'>" . __('Your email template has been successfully saved', 'foxypress') . "!</div>");
		}else if ($action === 'delete') { 
			global $wpdb;
	        $data = "SELECT *
	                FROM " . $wpdb->prefix . "foxypress_email_templates
	                WHERE email_template_id = " . filter($_GET['id']);
	
	        $item = $wpdb->get_results($data);
	        
			if(!empty($item)){
				$sql = "delete from  " . $wpdb->prefix . "foxypress_email_templates WHERE email_template_id = '" . filter($_GET['id']) . "'";
				$wpdb->query($sql);
				echo("<div class='updated' id='message'>" . __('Your email template has been successfully deleted', 'foxypress') . "!</div>");
			}else{
				echo("<div class='updated' id='message'>" . __('Your email template ID is invalid. Please try again.', 'foxypress') . "!</div>");
			}				
		}else if ($action === 'error') { 
			echo("<div class='updated' id='message'>" . __('Your email template ID is invalid. Please try again.', 'foxypress') . "!</div>");
		}
		?>
		<div class="wrap">
	    	<h2><?php _e('Manage Emails', 'foxypress'); ?> <a href='<?php echo(admin_url()); ?>edit.php?post_type=foxypress_product&page=manage-emails&mode=new' class="add-new-h2"><?php _e('Add New', 'foxypress'); ?></a></h2>
	        <div><i><?php _e('Listed below you will your email templates for use with individual orders', 'foxypress'); ?></i></div>
	        <?php
				$Emails= $wpdb->get_results("SELECT * FROM " . $wpdb->prefix ."foxypress_email_templates");
				if (!empty($Emails)) {
			?>
	         <table class="widefat page fixed">
				<thead>
					<tr>
						<th class="manage-column" scope="col" width="80%"><?php _e('Template Name', 'foxypress'); ?></th>
	                    <th>&nbsp;</th>
					</tr>
				</thead>            
	        <?php
				foreach ($Emails as $e ) {
					echo("<tr>");
					echo("<td><a href='" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=manage-emails&id=" . $e->email_template_id."&mode=edit'>" . $e->foxy_email_template_name . "</a></td>");
					echo("<td><a href='" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=manage-emails&id=" . $e->email_template_id."&action=delete'>". __('Delete', 'foxypress') . "</a></td>");
					echo("</tr>");
			}
			echo "</table>";
		}
	?>	
	<?php
	}
}
?>