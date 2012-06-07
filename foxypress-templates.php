<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);

	function foxypress_templates_page_load()
	{
		//modes - cart, checkout, receipt, email
		global $wpdb;
		$Page_URL = foxypress_GetCurrentPageURL(false);
		$PageName = foxypress_FixGetVar("page");
		$PageAction = foxypress_FixGetVar("action");
		$BlogID = foxypress_FixGetVar("b", "");
		$isSupported=true;
		if(get_option('foxycart_storeversion')!="0.7.2"){
			$isSupported=false;
	?>	
		<div class="error" id="message">
        	<p><strong><?php _e('You need to switch to the 0.7.2 version of FoxyCart to use this functionality.', 'foxypress'); ?></strong></p>
        </div>
	<?
		}
	?>
		<div class="wrap">
    		<h2><?php _e('FoxyCart Template Management','foxypress'); ?></h2>
			<p>
				<?php _e('With FoxyCart\'s additional API methods in 0.7.2, you are now able to control your templates from within FoxyPress!', 'foxypress'); ?>
			</p>	
			<?
				if($isSupported){
			?>
				<div>
					<a href="<?=$Page_URL?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?=$PageName?>&action=cart" class="template_selection <? if($PageAction=="cart"){echo"selected";} ?>"><div class="template_contents"><?php _e("Cart<br /> Template", "foxypress"); ?></div></a>
					<a href="<?=$Page_URL?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?=$PageName?>&action=checkout" class="template_selection <? if($PageAction=="checkout"){echo"selected";} ?>"><div class="template_contents"><?php _e('Checkout<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?=$Page_URL?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?=$PageName?>&action=receipt" class="template_selection <? if($PageAction=="receipt"){echo"selected";} ?>"><div class="template_contents"><?php _e('Receipt<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?=$Page_URL?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?=$PageName?>&action=email" class="template_selection <? if($PageAction=="email"){echo"selected";} ?>"><div class="template_contents"><?php _e('Email<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?=$Page_URL?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?=$PageName?>&action=htmlemail" class="template_selection <? if($PageAction=="htmlemail"){echo"selected";} ?>"><div class="template_contents"><?php _e('HTML Email<br /> Template', 'foxypress'); ?></div></a>
				</div>
			<?
				}
			?>
			<div class="clearall"></div>		
		</div>
	<?
		template_postback();
		_e("<br /><br /><i>Cart template caching is courtesy of FoxyCart and their API.</i>", "foxypress");
	}

	function template_postback()
	{
		if(isset($_POST['foxypress_btnTemplateSaveCart'])){			
			//$is_active = foxypress_FixPostVar('foxypress_sub_active');
			$templateType= "cart";

			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "store_template_cache";
			$foxyData["template_type"] = $templateType;	
			$foxyData["template_url"] = $_POST['foxypress_cart_cached_template_url'];		

			$cacheResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);

			$foxyXMLResponse = simplexml_load_string($cacheResults, NULL, LIBXML_NOCDATA);	
			if($foxyXMLResponse->result == "SUCCESS"){
				update_option("foxypress_cart_cached_template_url", foxypress_FixPostVar('foxypress_cart_cached_template_url'));
				$message=$foxyXMLResponse->messages[0]->message;
				if($message=="cart Template Updated"){ ?>
					<div class="updated" id="message">
	                    <p><strong><?php _e('Great job! You have successfully cached your cart template into FoxyCart!', 'foxypress'); ?></strong></p>
	                </div>
				<? }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?=$message;?></strong></p>
                </div>
			<?}
		}else if(isset($_POST['foxypress_btnTemplateSaveCheckout'])){			
			//$is_active = foxypress_FixPostVar('foxypress_sub_active');
			$templateType= "checkout";

			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "store_template_cache";
			$foxyData["template_type"] = $templateType;	
			$foxyData["template_url"] = $_POST['foxypress_checkout_cached_template_url'];		

			$cacheResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);

			$foxyXMLResponse = simplexml_load_string($cacheResults, NULL, LIBXML_NOCDATA);	
			if($foxyXMLResponse->result == "SUCCESS"){
				update_option("foxypress_checkout_cached_template_url", foxypress_FixPostVar('foxypress_checkout_cached_template_url'));
				$message=$foxyXMLResponse->messages[0]->message;
				if($message=="checkout Template Updated"){ ?>
					<div class="updated" id="message">
	                    <p><strong><?php _e('Great job! You have successfully cached your checkout template into FoxyCart!', 'foxypress'); ?></strong></p>
	                </div>
				<? }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?=$message;?></strong></p>
                </div>
			<?}
		}else if(isset($_POST['foxypress_btnTemplateSaveReceipt'])){			
			//$is_active = foxypress_FixPostVar('foxypress_sub_active');
			$templateType= "receipt";

			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "store_template_cache";
			$foxyData["template_type"] = $templateType;	
			$foxyData["template_url"] = $_POST['foxypress_receipt_cached_template_url'];		

			$cacheResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);

			$foxyXMLResponse = simplexml_load_string($cacheResults, NULL, LIBXML_NOCDATA);	
			if($foxyXMLResponse->result == "SUCCESS"){
				update_option("foxypress_receipt_cached_template_url", foxypress_FixPostVar('foxypress_receipt_cached_template_url'));
				$message=$foxyXMLResponse->messages[0]->message;
				if($message=="receipt Template Updated"){ ?>
					<div class="updated" id="message">
	                    <p><strong><?php _e('Great job! You have successfully cached your checkout template into FoxyCart!', 'foxypress'); ?></strong></p>
	                </div>
				<? }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?=$message;?></strong></p>
                </div>
			<?}
		}else if(isset($_POST['foxypress_btnTemplateSaveTextEmail'])){			
			//$is_active = foxypress_FixPostVar('foxypress_sub_active');
			$templateType= "email";

			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "store_template_cache";
			$foxyData["template_type"] = $templateType;	
			$foxyData["template_url"] = $_POST['foxypress_text_email_cached_template_url'];	
			$foxyData["email_subject"] = $_POST['foxypress_text_email_cached_template_subject'];	

			$cacheResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);

			$foxyXMLResponse = simplexml_load_string($cacheResults, NULL, LIBXML_NOCDATA);	
			if($foxyXMLResponse->result == "SUCCESS"){
				update_option("foxypress_text_email_cached_template_url", foxypress_FixPostVar('foxypress_text_email_cached_template_url'));
				update_option("foxypress_text_email_cached_template_subject", foxypress_FixPostVar('foxypress_text_email_cached_template_subject'));
				$message=$foxyXMLResponse->messages[0]->message;
				if($message=="email Template Updated"){ ?>
					<div class="updated" id="message">
	                    <p><strong><?php _e('Great job! You have successfully cached your checkout template into FoxyCart!', 'foxypress'); ?></strong></p>
	                </div>
				<? }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?=$message;?></strong></p>
                </div>
			<?}
		}else if(isset($_POST['foxypress_btnTemplateSaveHTMLEmail'])){			
			//$is_active = foxypress_FixPostVar('foxypress_sub_active');
			$templateType= "html_email";

			$foxyAPIURL = "https://" . get_option('foxycart_storeurl') . ".foxycart.com/api";
			$foxyData = array();
			$foxyData["api_token"] =  get_option('foxycart_apikey');
			$foxyData["api_action"] = "store_template_cache";
			$foxyData["template_type"] = $templateType;	
			$foxyData["template_url"] = $_POST['foxypress_html_email_cached_template_url'];	
			$foxyData["email_subject"] = $_POST['foxypress_html_email_cached_template_subject'];	

			$cacheResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
			$foxyXMLResponse = simplexml_load_string($cacheResults, NULL, LIBXML_NOCDATA);	
			if($foxyXMLResponse->result == "SUCCESS"){
				update_option("foxypress_html_email_cached_template_url", foxypress_FixPostVar('foxypress_html_email_cached_template_url'));
				update_option("foxypress_html_email_cached_template_subject", foxypress_FixPostVar('foxypress_html_email_cached_template_subject'));
				$message=$foxyXMLResponse->messages[0]->message;
				if($message=="html_email Template Updated"){ ?>
					<div class="updated" id="message">
	                    <p><strong><?php _e('Great job! You have successfully cached your checkout template into FoxyCart!', 'foxypress'); ?></strong></p>
	                </div>
				<? }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?=$message;?></strong></p>
                </div>
			<?}
		}
		$PageAction = foxypress_FixGetVar("action");
		if($PageAction == "cart")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpCartTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpCartTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Standard.html");
						}else{
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Text.html");
						}					    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('Cart Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote cart template then click the "cache your url" button to have your template parsed, cached and saved.', 'foxypress'); ?></p>
					<?php _e('Your Cart template url', 'foxypress'); ?>: <input type="text" id="foxypress_cart_cached_template_url" name="foxypress_cart_cached_template_url" size="100" value="<?php echo(get_option("foxypress_cart_cached_template_url")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>

					<p><input type="radio" name="grpCartTemplate" value="standard" <? if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Standard.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Standard', 'foxypress'); ?></p>
					<p><input type="radio" name="grpCartTemplate" value="text" <? if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Text.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Text', 'foxypress'); ?></p>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveCart" name="foxypress_btnTemplateSaveCart" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?
		}else if($PageAction == "checkout")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpCheckoutTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpCheckoutTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Standard.html");
						}else{
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Text.html");
						}					    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('Checkout Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote checkout template then click the "cache your url" button to have your template parsed, cached and saved.', 'foxypress'); ?></p>
					<?php _e('Your Checkout template url', 'foxypress'); ?>: <input type="text" id="foxypress_checkout_cached_template_url" name="foxypress_checkout_cached_template_url" size="100" value="<?php echo(get_option("foxypress_checkout_cached_template_url")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>
					<p><input type="radio" name="grpCheckoutTemplate" value="standard" <? if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Standard.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Standard', 'foxypress'); ?> </p>					
					<p><input type="radio" name="grpCheckoutTemplate" value="text Text" <? if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Text.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Text', 'foxypress'); ?></p> 
					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveCheckout" name="foxypress_btnTemplateSaveCheckout" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?
		}else if($PageAction == "receipt")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpReceiptTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpReceiptTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Standard.html");
						}else{
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Text.html");
						}					    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('Receipt Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote receipt template then click the "cache your url" button to have your template parsed, cached and saved.', 'foxypress'); ?></p>
					<?php _e('Your Receipt template url', 'foxypress'); ?>: <input type="text" id="foxypress_receipt_cached_template_url" name="foxypress_receipt_cached_template_url" size="100" value="<?php echo(get_option("foxypress_receipt_cached_template_url")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>
					<p><input type="radio" name="grpReceiptTemplate" value="standard" <? if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Standard.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Standard', 'foxypress'); ?> </p>					
					<p><input type="radio" name="grpReceiptTemplate" value="text Text" <? if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Text.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Text', 'foxypress'); ?></p> 
					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveReceipt" name="foxypress_btnTemplateSaveReceipt" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?
		}else if($PageAction == "email")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpTextEmailTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpTextEmailTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_text_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Standard_Text.html");
						}else{
							jQuery("#foxypress_text_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Text_Text.html");
						}					    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('Email Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote text email template then click the "cache your url" button to have your template parsed, cached and saved', 'foxypress'); ?>.</p>
					<?php _e('texthere', 'Your Text Email template url'); ?>: <br /><input type="text" id="foxypress_text_email_cached_template_url" name="foxypress_text_email_cached_template_url" size="100" value="<?php echo(get_option("foxypress_text_email_cached_template_url")) ?>" />
					<br /><br />
					<?php _e('texthere', 'Your Text Email Subject'); ?>: <i><?php _e('To turn off receipt emails, leave this field blank and receipt emails will not be sent', 'foxypress'); ?>.</i><br /><input type="text" id="foxypress_text_email_cached_template_subject" name="foxypress_text_email_cached_template_subject" size="100" value="<?php echo(get_option("foxypress_text_email_cached_template_subject")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>
					<p><input type="radio" name="grpTextEmailTemplate" value="standard" <? if(strpos(get_option("foxypress_text_email_cached_template_url"),"foxy_Email_Standard_Text.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Standard', 'foxypress'); ?> </p>					
					<p><input type="radio" name="grpTextEmailTemplate" value="text Text" <? if(strpos(get_option("foxypress_text_email_cached_template_url"),"foxy_Email_Text_Text.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Text', 'foxypress'); ?></p> 
					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveTextEmail" name="foxypress_btnTemplateSaveTextEmail" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?
		}else if($PageAction == "htmlemail")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpHTMLEmailTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpHTMLEmailTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_html_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Standard_HTML.html");
						}else{
							jQuery("#foxypress_html_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Text_HTML.html");
						}					    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('HTML Email Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote HTML email template then click the "cache your url" button to have your template parsed, cached and saved', 'foxypress'); ?>.</p>
					<?php _e('Your HTML Email template url', 'foxypress'); ?>: <br /><input type="text" id="foxypress_html_email_cached_template_url" name="foxypress_html_email_cached_template_url" size="100" value="<?php echo(get_option("foxypress_html_email_cached_template_url")) ?>" />
					<br /><br />
					<?php _e('Your HTML Email Subject', 'foxypress'); ?>: <i><?php _e('To turn off receipt emails, leave this field blank and receipt emails will not be sent', 'foxypress'); ?>.</i><br /><input type="text" id="foxypress_html_email_cached_template_subject" name="foxypress_html_email_cached_template_subject" size="100" value="<?php echo(get_option("foxypress_html_email_cached_template_subject")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>
					<p><input type="radio" name="grpHTMLEmailTemplate" value="standard" <? if(strpos(get_option("foxypress_html_email_cached_template_url"),"foxy_Email_Standard_HTML.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Standard', 'foxypress'); ?> </p>					
					<p><input type="radio" name="grpHTMLEmailTemplate" value="text Text" <? if(strpos(get_option("foxypress_html_email_cached_template_url"),"foxy_Email_Text_HTML.html")!==false){echo("checked");}?> /> <?php _e('FoxyCart Text', 'foxypress'); ?></p> 
					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveHTMLEmail" name="foxypress_btnTemplateSaveHTMLEmail" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?
		}
	}
?>
