<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');

	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);

	function foxypress_templates_page_load()
	{
		//modes - cart, checkout, receipt, email
		global $wpdb, $isTwigSupported;
		$Page_URL = admin_url() . "edit.php";
		$PageName = foxypress_FixGetVar("page");
		$PageAction = foxypress_FixGetVar("action");
		$BlogID = foxypress_FixGetVar("b", "");
		$isSupported=true;
		if(get_option('foxycart_storeversion')!="0.7.2" && get_option('foxycart_storeversion')!="1.0"){
			$isSupported=false;
	?>	
		<div class="error" id="message">
        	<p><strong><?php _e('You need to switch to the 0.7.2 version of FoxyCart to use this functionality.', 'foxypress'); ?></strong></p>
        </div>
	<?php
		}
	?>
		<div class="wrap">
    		<h2><?php _e('FoxyCart Template Management','foxypress'); ?></h2>
			<p>
				<?php _e('With FoxyCart\'s additional API methods in 0.7.2 and above, you are now able to control your templates from within FoxyPress!', 'foxypress'); ?>
			</p>	
			<?php
				if($isSupported){
			?>
				<div>
					<a href="<?php echo($Page_URL); ?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?php echo($PageName); ?>&action=cart" class="template_selection <?php if($PageAction=="cart"){echo"selected";} ?>"><div class="template_contents"><?php _e("Cart<br /> Template", "foxypress"); ?></div></a>
					<a href="<?php echo($Page_URL); ?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?php echo($PageName); ?>&action=checkout" class="template_selection <?php if($PageAction=="checkout"){echo"selected";} ?>"><div class="template_contents"><?php _e('Checkout<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?php echo($Page_URL); ?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?php echo($PageName); ?>&action=receipt" class="template_selection <?php if($PageAction=="receipt"){echo"selected";} ?>"><div class="template_contents"><?php _e('Receipt<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?php echo($Page_URL); ?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?php echo($PageName); ?>&action=email" class="template_selection <?php if($PageAction=="email"){echo"selected";} ?>"><div class="template_contents"><?php _e('Email<br /> Template', 'foxypress'); ?></div></a>
					<a href="<?php echo($Page_URL); ?>?post_type=<?php echo(FOXYPRESS_CUSTOM_POST_TYPE) ?>&page=<?php echo($PageName); ?>&action=htmlemail" class="template_selection <?php if($PageAction=="htmlemail"){echo"selected";} ?>"><div class="template_contents"><?php _e('HTML Email<br /> Template', 'foxypress'); ?></div></a>
				</div>
			<?php
				}
			?>
			<div class="clearall"></div>		
		</div>
	<?php
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
				<?php }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?php echo($message);?></strong></p>
                </div>
			<?php }
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
				<?php }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?php echo($message); ?></strong></p>
                </div>
			<?php }
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
				<?php }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?php echo($message); ?></strong></p>
                </div>
			<?php }
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
				<?php }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?php echo($message); ?></strong></p>
                </div>
			<?php }
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
				<?php }
			}else{
				$message=$foxyXMLResponse->messages[0]->message; ?>
				<div class="error" id="message">
                    <p><strong><?php echo($message); ?></strong></p>
                </div>
			<?php }
		}
		$PageAction = foxypress_FixGetVar("action");
		$isTwigSupported=true;
		if(get_option('foxycart_storeversion')!="1.0"){$isTwigSupported=false;}
		if($PageAction == "cart")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpCartTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpCartTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Standard.html");
						}else if(newradio.value=="standard_twig"){
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Standard_Twig.html");
						}else if(newradio.value=="text"){
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Text.html");
						}else if(newradio.value=="text_twig"){
							jQuery("#foxypress_cart_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Cart_Text_Twig.html");
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

					<p><input type="radio" id="grpCartTemplate_Standard" name="grpCartTemplate" value="standard" <?php if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Standard.html")!==false){echo("checked");}?> /> <label for="grpCartTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpCartTemplate_Standard_Twig" name="grpCartTemplate" value="standard_twig" <?php if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Standard_Twig.html")!==false){echo("checked");}?> /> <label for="grpCartTemplate_Standard_Twig"><?php _e('FoxyCart Standard with Twig', 'foxypress'); ?></label></p><?php } ?>

					<p><input type="radio" id="grpCartTemplate_Text" name="grpCartTemplate" value="text" <?php if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Text.html")!==false){echo("checked");}?> /> <label for="grpCartTemplate_Text"><?php _e('FoxyCart Text', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpCartTemplate_Text_Twig" name="grpCartTemplate" value="text_twig" <?php if(strpos(get_option("foxypress_cart_cached_template_url"),"foxy_Cart_Text_Twig.html")!==false){echo("checked");}?> /> <label for="grpCartTemplate_Text_Twig"><?php _e('FoxyCart Text with Twig', 'foxypress'); ?></label></p> <?php } ?>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveCart" name="foxypress_btnTemplateSaveCart" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?php
		}else if($PageAction == "checkout")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpCheckoutTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpCheckoutTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Standard.html");
						}else if(newradio.value=="standard_twig"){
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Standard_Twig.html");
						}else if(newradio.value=="text"){
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Text.html");
						}else if(newradio.value=="text_twig"){
							jQuery("#foxypress_checkout_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Checkout_Text_Twig.html");
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
					
					<p><input type="radio" id="grpCheckoutTemplate_Standard" name="grpCheckoutTemplate" value="standard" <?php if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Standard.html")!==false){echo("checked");}?> /> <label for="grpCheckoutTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpCheckoutTemplate_Standard_Twig" name="grpCheckoutTemplate" value="standard_twig" <?php if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Standard_Twig.html")!==false){echo("checked");}?> /> <label for="grpCheckoutTemplate_Standard_Twig"><?php _e('FoxyCart Standard with Twig', 'foxypress'); ?></label></p><?php } ?>

					<p><input type="radio" id="grpCheckoutTemplate_Text" name="grpCheckoutTemplate" value="text" <?php if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Text.html")!==false){echo("checked");}?> /> <label for="grpCheckoutTemplate_Text"><?php _e('FoxyCart Text', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpCheckoutTemplate_Text_Twig" name="grpCheckoutTemplate" value="text_twig" <?php if(strpos(get_option("foxypress_checkout_cached_template_url"),"foxy_Checkout_Text_Twig.html")!==false){echo("checked");}?> /> <label for="grpCheckoutTemplate_Text_Twig"><?php _e('FoxyCart Text with Twig', 'foxypress'); ?></label></p> <?php } ?>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveCheckout" name="foxypress_btnTemplateSaveCheckout" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?php
		}else if($PageAction == "receipt")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpReceiptTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpReceiptTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Standard.html");
						}else if(newradio.value=="standard_twig"){
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Standard_Twig.html");
						}else if(newradio.value=="text"){
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Text.html");
						}else if(newradio.value=="text_twig"){
							jQuery("#foxypress_receipt_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Receipt_Text_Twig.html");
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
					
					<p><input type="radio" id="grpReceiptTemplate_Standard" name="grpReceiptTemplate" value="standard" <?php if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Standard.html")!==false){echo("checked");}?> /> <label for="grpReceiptTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpReceiptTemplate_Standard_Twig" name="grpReceiptTemplate" value="standard_twig" <?php if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Standard_Twig.html")!==false){echo("checked");}?> /> <label for="grpReceiptTemplate_Standard_Twig"><?php _e('FoxyCart Standard with Twig', 'foxypress'); ?></label></p><?php } ?>

					<p><input type="radio" id="grpReceiptTemplate_Text" name="grpReceiptTemplate" value="text" <? if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Text.html")!==false){echo("checked");}?> /> <label for="grpReceiptTemplate_Text"><?php _e('FoxyCart Text', 'foxypress'); ?></label></p>
					<?php if($isTwigSupported){ ?><p><input type="radio" id="grpReceiptTemplate_Text_Twig" name="grpReceiptTemplate" value="text_twig" <?php if(strpos(get_option("foxypress_receipt_cached_template_url"),"foxy_Receipt_Text_Twig.html")!==false){echo("checked");}?> /> <label for="grpReceiptTemplate_Text_Twig"><?php _e('FoxyCart Text with Twig', 'foxypress'); ?></label></p> <?php } ?>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveReceipt" name="foxypress_btnTemplateSaveReceipt" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?php
		}else if($PageAction == "email")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpTextEmailTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpTextEmailTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_text_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Text_Standard.html");
						}else if(newradio.value=="standard_twig"){
							jQuery("#foxypress_text_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_Text_Standard_Twig.html");
						}				    
					});
				});
			</script>
			<form method="post">
				<div class="template_holder">
					<h2><?php _e('Email Template', 'foxypress'); ?></h2>
					<p><?php _e('Enter the URL of your remote text email template then click the "cache your url" button to have your template parsed, cached and saved', 'foxypress'); ?>.</p>
					<?php _e('Your Text Email template url', 'foxypress'); ?>: <br /><input type="text" id="foxypress_text_email_cached_template_url" name="foxypress_text_email_cached_template_url" size="100" value="<?php echo(get_option("foxypress_text_email_cached_template_url")) ?>" />
					<br /><br />
					<?php _e('Your Text Email Subject', 'foxypress'); ?>: <i><?php _e('To turn off receipt emails, leave this field blank and receipt emails will not be sent', 'foxypress'); ?>.</i><br /><input type="text" id="foxypress_text_email_cached_template_subject" name="foxypress_text_email_cached_template_subject" size="100" value="<?php echo(get_option("foxypress_text_email_cached_template_subject")) ?>" />
					<br /><img class="template_separator" src="<?php echo(plugins_url())?>/foxypress/img/or.png" />
					<p><?php _e('Use a Default template', 'foxypress'); ?>: <i>(<?php _e('this will use a file located on your server that contains the default FoxyCart template contents', 'foxypress'); ?>)</i></p>
					
					<?php if($isTwigSupported){ ?>
						<p><input type="radio" id="grpTextEmailTemplate_Standard" name="grpTextEmailTemplate" value="standard" <?php if(strpos(get_option("foxypress_text_email_cached_template_url"),"foxy_Email_Text_Standard.html")!==false){echo("checked");}?> /> <label for="grpTextEmailTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label></p>
						<p><input type="radio" id="grpTextEmailTemplate_Standard_Twig" name="grpTextEmailTemplate" value="standard_twig" <? if(strpos(get_option("foxypress_text_email_cached_template_url"),"foxy_Email_Text_Standard_Twig.html")!==false){echo("checked");}?> /> <label for="grpTextEmailTemplate_Standard_Twig"><?php _e('FoxyCart Standard with Twig', 'foxypress'); ?></label></p>
					<?php }else{ ?>
						<p><input type="radio" id="grpTextEmailTemplate_Standard" name="grpTextEmailTemplate" value="standard" <?php if(strpos(get_option("foxypress_text_email_cached_template_url"),"foxy_Email_Text_Standard.html")!==false){echo("checked");}?> /> <label for="grpTextEmailTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label> </p>					
					<?php } ?>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveTextEmail" name="foxypress_btnTemplateSaveTextEmail" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?php
		}else if($PageAction == "htmlemail")
		{
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[name='grpHTMLEmailTemplate']").change(function(event) {
					    var newradio= jQuery("input[name='grpHTMLEmailTemplate']:checked")[0];
						if(newradio.value=="standard"){
							jQuery("#foxypress_html_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_HTML_Standard.html");
						}else if(newradio.value=="standard_twig"){
							jQuery("#foxypress_html_email_cached_template_url").val("<?php echo(plugins_url())?>/foxypress/templates/foxy_Email_HTML_Standard_Twig.html");
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
					
					<?php if($isTwigSupported){ ?>
						<p><input type="radio" id="grpHTMLEmailTemplate_Standard" name="grpHTMLEmailTemplate" value="standard" <?php if(strpos(get_option("foxypress_html_email_cached_template_url"),"foxy_Email_HTML_Standard.html")!==false){echo("checked");}?> /> <label for="grpHTMLEmailTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label></p>
						<p><input type="radio" id="grpHTMLEmailTemplate_Standard_Twig" name="grpHTMLEmailTemplate" value="standard_twig" <?php if(strpos(get_option("foxypress_html_email_cached_template_url"),"foxy_Email_HTML_Standard_Twig.html")!==false){echo("checked");}?> /> <label for="grpHTMLEmailTemplate_Standard_Twig"><?php _e('FoxyCart Standard with Twig', 'foxypress'); ?></label></p>
					<?php }else{ ?>
						<p><input type="radio" id="grpHTMLEmailTemplate_Standard" name="grpHTMLEmailTemplate" value="standard" <?php if(strpos(get_option("foxypress_html_email_cached_template_url"),"foxy_Email_HTML_Standard.html")!==false){echo("checked");}?> /> <label for="grpHTMLEmailTemplate_Standard"><?php _e('FoxyCart Standard', 'foxypress'); ?></label> </p>					
					<?php } ?>

					<div class="clearall"></div>
					<input type="submit" class="button bold" id="foxypress_btnTemplateSaveHTMLEmail" name="foxypress_btnTemplateSaveHTMLEmail" value="<?php _e('Cache Your Template', 'foxypress'); ?>" />
				</div>
			</form>
			<div class="clearall"></div>
		<?php
		}
	}
?>
