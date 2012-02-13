<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
//add_action('admin_init', 'manage_emails_postback');

global $wpdb;
//page load
function foxypress_manage_emails_page_load() 
{
	global $wpdb;
	
	if($_GET['mode']=='edit')
	{
		if(isset($_POST['foxy_em_save']))
		{
			$subject=$_POST['subject'];
			$templatename = $_POST['templatename'];
			$content=$_POST['content'];
			$from=$_POST['from'];		

			if($templatename!=''&& $subject!=''){
				$sql = "UPDATE  ". $wpdb->prefix . "foxypress_email_templates set foxy_email_template_name='".$templatename."', foxy_email_template_subject='".$subject."', foxy_email_template_email_body='".$content."', foxy_email_template_from='" . $from . "' WHERE email_template_id=".$_GET[id];
				$wpdb->query($sql);
				$sm_error = "<div class='updated' id='message'>Your email template has been successfully saved!</div>";
			}else{
				$sm_error = "<div class='error' id='message'>Your email template name and subject cannot be blank.</div>";
			}
			echo $sm_error;
		}
		$me = $wpdb->get_row("SELECT * from " . $wpdb->prefix ."foxypress_email_templates 	WHERE email_template_id='".$_GET[id]."'");
	?>
	        <div class="wrap">
	    		<h2><?php _e('Manage Emails'); ?> <a href='<?php bloginfo('url') ?>/wp-admin/edit.php?post_type=foxypress_product&page=manage-emails&mode=new' class="add-new-h2">Add New</a></h2>
	        	<table cellpadding="10" style="float:left; width:650px">
	        		<tr>
	        			<td width="180">Template ID</td><td><?php _e($_GET['id']); ?></td>
	        		</tr>
                    <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
	        		<form method="POST" name="statusForm" id="statusForm"> 
	        		<tr>
	        			<td>Template Name</td>
						<td><input type="text" name="templatename" value="<?php _e($me->foxy_email_template_name); ?>" size="50" /></td>
	        		</tr>
	        		<tr>
						<td>Subject</td>
						<td><input type="text" name="subject" value="<?php _e($me->foxy_email_template_subject); ?>" size="50" /></td>
					</tr>
                    <tr>
                        <td>From Email</td> 
                        <td><input type="text" name="from" value="<?php _e($me->foxy_email_template_from); ?>" size="50" /></td>
                    </tr>
					<tr>
				        <td>Content</td>
						<td>
                        	<textarea name="content" cols="50" rows="5"><?php echo($me->foxy_email_template_email_body); ?></textarea>
                            <script type="text/javascript">
								CKEDITOR.replace( 'content', {width:"500"} );
							</script>
                        </td>
			        </tr>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="Save" /></td>
			        </tr>
					</form>
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
							<td><strong>{{tax_total}}</strong></td>
							<td>Order Tax Total</td>
						</tr>
						<tr>
							<td><strong>{{shipping_total}}</strong></td>
							<td>Order Shipping Total</td>
						</tr>
						<tr>
							<td><strong>{{order_total}}</strong></td>
							<td>Order Total</td>
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
	        <?php
		}else if($_GET['mode']=='new'){
			if(isset($_POST['foxy_em_save'])){
				$subject=$_POST['subject'];
				$templatename = $_POST['templatename'];
				$content=$_POST['content'];
				$from=$_POST['from'];
				if($templatename!='' && $subject!=''){
					$sql = "INSERT INTO  ". $wpdb->prefix . "foxypress_email_templates VALUES(null,'$templatename','$subject','$content','$from')";
					$wpdb->query($sql);

					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&&action=updated',$_REQUEST['page'],'manage-emails');
	                echo 'Saving Template...';
	                echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';					
				}else{
					$sm_error = "<div class='error' id='message'>Your email template name and subject cannot be blank.</div>";
				}
				echo $sm_error;
				?>
    	        <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
				<div class="wrap">
					<h2><?php _e('Manage Emails'); ?></h2>
					<table cellpadding="10" style="float:left; width:650px;">
						<form method="POST" name="statusForm" id="statusForm"> 
						<tr>
							<td>Template Name</td>
							<td><input type="text" name="templatename" value="" size="50" /></td>
						</tr>
						<tr>
							<td>Subject</td> 
							<td><input type="text" name="subject" value="" size="50" /></td>
						</tr>
                        <tr>
							<td>From Email</td> 
							<td><input type="text" name="from" value="" size="50" /></td>
						</tr>
						<tr>
							<td>Content</td>
							<td>
                            	<textarea name="content" cols="70" rows="10"></textarea>
                                <script type="text/javascript">
									CKEDITOR.replace( 'content', {width:"500"} );
								</script>
                            </td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="Save" /></td>
						</tr>
						</form>
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
								<td><strong>{{tax_total}}</strong></td>
								<td>Order Tax Total</td>
							</tr>
							<tr>
								<td><strong>{{shipping_total}}</strong></td>
								<td>Order Shipping Total</td>
							</tr>
							<tr>
								<td><strong>{{order_total}}</strong></td>
								<td>Order Total</td>
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
				<?php
			}else{
			?>
		<div class="wrap">
			<h2><?php _e('Manage Emails'); ?></h2>
			<table cellpadding="10" style="float:left; width:650px;">
               <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
				<form method="POST" name="statusForm" id="statusForm"> 
				<tr>
					<td><strong>Template Name: </strong></td><td>  <input type="text" name="templatename" value="" size="50" /></td>
				</tr>
				<tr>
					<td><strong>Subject</strong></td> <td><input type="text" name="subject" value="" size="50" /></td>
				</tr>
                <tr>
					<td><strong>From Email</strong></td> <td><input type="text" name="from" value="" size="50" /></td>
				</tr>
				<tr>
					<td><strong>Content</strong></td>
					<td>
                    	<textarea name="content" cols="70" rows="10"></textarea>
						 <script type="text/javascript">
                                    CKEDITOR.replace( 'content', {width:"500"} );
							</script>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" name="foxy_em_save" id="foxy_em_save" value="Save" /></td>
				</tr>
				</form>
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
						<td><strong>{{tax_total}}</strong></td>
						<td>Order Tax Total</td>
					</tr>
					<tr>
						<td><strong>{{shipping_total}}</strong></td>
						<td>Order Shipping Total</td>
					</tr>
					<tr>
						<td><strong>{{order_total}}</strong></td>
						<td>Order Total</td>
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
		<?php
		}
	}else{
		$action = foxypress_FixGetVar('action');
		if ($action === 'updated') { 
			echo("<div class='updated' id='message'>Your email template has been successfully saved!</div>");
		}else if ($action === 'delete') { 
			$sql = "delete from  " . $wpdb->prefix . "foxypress_email_templates WHERE email_template_id = '".$_GET['id']."'";
			$wpdb->query($sql);
			echo("<div class='updated' id='message'>Your email template has been successfully deleted!</div>");
		}
		?>
		<div class="wrap">
	    	<h2><?php _e('Manage Emails'); ?> <a href='<?php bloginfo('url') ?>/wp-admin/edit.php?post_type=foxypress_product&page=manage-emails&mode=new' class="add-new-h2">Add New</a></h2>
	        <div><i>Listed below you will your email templates for use with individual orders</i></div>
	        <?php
				$Emails= $wpdb->get_results("SELECT * FROM " . $wpdb->prefix ."foxypress_email_templates");
				if (!empty($Emails)) {
			?>
	         <table class="widefat page fixed">
				<thead>
					<tr>
						<th class="manage-column" scope="col" width="80%">Template Name</th>
	                    <th>&nbsp;</th>
					</tr>
				</thead>            
	        <?php
				foreach ($Emails as $e ) {
					echo("<tr>");
					echo("<td><a href='" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=manage-emails&id=" . $e->email_template_id."&mode=edit'>" . $e->foxy_email_template_name . "</a></td>");
					echo("<td><a href='" . $Page_URL . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=manage-emails&id=" . $e->email_template_id."&action=delete'>Delete</a></td>");
					echo("</tr>");
			}
			echo "</table>";
		}
	?>	
	<?php
	}
}
?>