<?php
add_action('admin_init', 'foxypress_settings_postback');

function foxypress_settings_postback()
{	
	global $wpdb;
	if(isset($_POST['btnFoxyPressSettingsSaveWizard']))
	{
		update_option("foxycart_storeurl", foxypress_FixPostVar('foxycart_storeurl_wizard'));	
		update_option("foxycart_storeversion", foxypress_FixPostVar('foxycart_storeversion_wizard'));
		update_option("foxycart_include_jquery", foxypress_FixPostVar('foxycart_include_jquery_wizard'));
		update_option("foxypress_image_mode", foxypress_FixPostVar('foxypress_image_mode_wizard'));
		
		update_option("foxypress_skip_settings_wizard", "1");
		header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=foxypress-settings");
	}
	else if(isset($_POST['btnFoxyPressSettingsSave']))
	{	
		update_option("foxycart_storeurl", foxypress_FixPostVar('foxycart_storeurl'));	
		update_option("foxycart_apikey", foxypress_FixPostVar('foxycart_apikey'));
		update_option("foxycart_storeversion", foxypress_FixPostVar('foxycart_storeversion'));
		update_option("foxycart_include_jquery", foxypress_FixPostVar('foxycart_include_jquery'));
		update_option("foxypress_include_default_stylesheet", foxypress_FixPostVar('foxypress_include_default_stylesheet'));		
		update_option("foxypress_image_mode", foxypress_FixPostVar('foxypress_image_mode'));		
		update_option("foxypress_uninstall_keep_products", foxypress_FixPostVar('foxypress_uninstall_keep_products'));
		update_option("foxycart_hmac", foxypress_FixPostVar('foxycart_hmac'));
		update_option("foxycart_enable_multiship", foxypress_FixPostVar('foxycart_enable_multiship'));
		update_option("foxycart_show_dashboard_widget", foxypress_FixPostVar('foxycart_show_dashboard_widget'));
		update_option("foxypress_max_downloads", foxypress_FixPostVar('foxypress_max_downloads'));
		update_option("foxypress_qty_alert", foxypress_FixPostVar('foxypress_qty_alert'));
		update_option("foxycart_datafeeds", foxypress_FixPostVar('foxycart_datafeeds'));
		update_option("foxycart_currency_locale", foxypress_FixPostVar('foxycart_currency_locale'));
		update_option("foxypress_inactive_message", foxypress_FixPostVar('foxypress_inactive_message'));
		update_option("foxypress_out_of_stock_message", foxypress_FixPostVar('foxypress_out_of_stock_message'));
		update_option("foxypress_packing_slip_header", foxypress_FixPostVar('foxypress_packing_slip_header'));
		update_option("foxypress_packing_slip_footer_message", foxypress_FixPostVar('foxypress_packing_slip_footer_message'));
		update_option("foxypress_smtp_host", foxypress_FixPostVar('foxypress_smtp_host'));
		update_option("foxypress_secure_port", foxypress_FixPostVar('foxypress_secure_port'));
		update_option("foxypress_email_username", foxypress_FixPostVar('foxypress_email_username'));
		update_option("foxypress_email_password", foxypress_FixPostVar('foxypress_email_password'));
		

		if(function_exists('is_multisite') && is_multisite())
		{
			$OriginalBlog = $wpdb->blogid;
			$is_main_blog = foxypress_FixPostVar('foxypress_main_blog');
			if($is_main_blog != foxypress_FixPostVar('foxypress_main_blog_previous'))
			{
				//if we mark this as the main site and it wasn't already marked, we need to update in this database 
				//and then update the other db's if it's now a 1
				update_option("foxypress_main_blog", $is_main_blog);
				if($is_main_blog == "1")
				{
					$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE blog_id != '" . $wpdb->blogid . "'"));
					foreach ($blogids as $blog_id) 
					{	
						switch_to_blog($blog_id);
						update_option("foxypress_main_blog", "0");						
					}
					switch_to_blog($OriginalBlog);
				}
			}
			$enable_sso = foxypress_FixPostVar('foxycart_enable_sso');
			if($enable_sso != foxypress_FixPostVar("foxycart_enable_sso_previous"))
			{
				//this needs to be updated in all the db's
				update_option("foxycart_enable_sso", $enable_sso);
				$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE blog_id != '" . $wpdb->blogid . "'"));
				foreach ($blogids as $blog_id) 
				{
					switch_to_blog($blog_id);
					update_option("foxycart_enable_sso", $enable_sso);	
				}
				switch_to_blog($OriginalBlog);
			}
		}
		header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=foxypress-settings");
	}	
}

function foxypress_settings_page_load()
{
	global $wpdb;		
	
	if(get_option("foxypress_skip_settings_wizard") == "1")
	{	
    ?>
<form method="POST">
    <div id="" class="settings_widefat">
        <div class="settings_head main">
            FoxyPress <?php echo(WP_FOXYPRESS_CURRENT_VERSION) ?>
        </div>		
        <div class="settings_inside">
            <img src="<?php echo(plugins_url())?>/foxypress/img/logo.png" />
            <p>The FoxyPress Plugin was created to provide users a way to harness the easy to use e-commerce functionality of FoxyCart along with the power of the WordPress Content Management System.</p>		
            <p>View some of our resources below.</p>
            <a class="button bold" href="http://www.facebook.com/foxypress" target="_blank">Facebook Fan Page</a>
            <a class="button bold" href="http://www.foxy-press.com/faq/" target="_blank">FoxyPress FAQ</a>
            <a class="button bold" href="http://www.foxy-press.com/getting-started/" target="_blank">FoxyPress Getting Started (Documentation)</a>
            <a class="button bold" href="http://www.foxy-press.com/forum/" target="_blank">FoxyPress Forum</a>
            <a class="button bold" href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">Need a FoxyCart account?</a>
        </div>
    </div>
    <div id="" class="settings_widefat">
        <div class="settings_head settings">
            Main Settings
        </div>		
        <div class="settings_inside">
            <table>
                <tr valign="top">
                    <td align="right" valign="top" nowrap class="title">FoxyCart Store Subdomain URL</td>
                    <td align="left">
                        <input type="text" name="foxycart_storeurl" id="foxycart_storeurl" value="<?php echo get_option('foxycart_storeurl'); ?>" size="50" />
                        <br />
                        *A store url is required in order to use Foxypress. <br />
                        <i>ex. if your store url is foxypress.foxycart.com, enter just 'foxypress'.</i>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>FoxyCart API Key</td>
                    <td align="left">
                      <?php
                        if(get_option('foxycart_apikey')=='')
                        {
                            $len = 16;
                            $base='ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
                            $max=strlen($base)-1;
                            $activatecode='';
                            mt_srand((double)microtime()*1000000);
                            while (strlen($activatecode)<$len+1){
                                $activatecode.=$base{mt_rand(0,$max)};
                            }
                            $today = getdate();
                            $apikey = "wmm" . $today['mon'] . $today['mday'] . $today['year'] . $today['seconds'] . $activatecode;
                            echo'<input type="text" name="foxycart_apikey" value="'  . $apikey .  '" size="50" readonly />';
                        }
                        else
                        {
                            echo'<input type="text" name="foxycart_apikey" value="' . get_option("foxycart_apikey")  . '" size="50" readonly />';
                        }
                      ?>
                        <br />
                        *Please copy this into your FoxyCart settings for your Datafeed/API Key
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>FoxyCart Store Version</td>
                    <td align="left">
                         <?php $version = get_option('foxycart_storeversion'); ?>
                        <select name="foxycart_storeversion" width="300" style="width: 300px">
                            <option value="0.7.1" <?php echo( ($version == "0.7.1") ? "selected=\"selected\"" : "" ); ?>>0.7.1</option>
                            <option value="0.7.0" <?php echo( ($version == "0.7.0") ? "selected=\"selected\"" : "" ); ?>>0.7.0</option>                             </select>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Currency Locale Code</td>
                    <td align="left">
                        <input type="text" name="foxycart_currency_locale" value="<?php echo get_option('foxycart_currency_locale'); ?>" size="50" /><br />                              
                        <?php 
                        if (!function_exists('money_format'))
                        {
                            echo("Attention, you are using Windows which does not support internationalization. You will be limited to $ or £.");
                        }
                        else
                        {								
                            echo("If you would like to use something other than $ for your currency, enter your locale code. <br />
                                 <a href=\"http://www.roseindia.net/tutorials/I18N/locales-list.shtml\" target=\"_blank\">View the full list of 
                                 locale codes.</a><br />
                                 You must also change your locale setting in FoxyCart by going to Templates -> Language and updating \"store locale\"");                              
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div id="" class="settings_widefat">
        <div class="settings_head advanced">
            Advanced Options
        </div>		
        <div class="settings_inside">
            <table>
                <tr valign="top">
                    <td align="right" valign="top" nowrap  class="title">Include jQuery</td>
                    <td align="left">
                        <input type="checkbox" name="foxycart_include_jquery" value="1" <?php echo(((get_option('foxycart_include_jquery') == "1") ? "checked=\"checked\"" : "")) ?> /> *We will automatically include a reference to jQuery
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap  class="title">Include Default Style Sheet</td>
                    <td align="left">
                        <input type="checkbox" name="foxypress_include_default_stylesheet" value="1" <?php echo(((get_option('foxypress_include_default_stylesheet') == "1") ? "checked=\"checked\"" : "")) ?> /> *We will automatically include a reference to the default FoxyPress stylesheet
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Item Image Mode</td>
                    <td align="left">
                    <?php
                        $image_mode = get_option('foxypress_image_mode');
                    ?>
                        <select name="foxypress_image_mode" id="foxypress_image_mode">
                            <option value="" <?php if($image_mode == "") { echo("selected=\"selected\""); } ?>>Neither</option>
                            <option value="<?php echo(FOXYPRESS_USE_COLORBOX); ?>" <?php if($image_mode == FOXYPRESS_USE_COLORBOX) { echo("selected=\"selected\""); } ?>>Use Colorbox</option>
                            <option value="<?php echo(FOXYPRESS_USE_LIGHTBOX); ?>" <?php if($image_mode == FOXYPRESS_USE_LIGHTBOX) { echo("selected=\"selected\""); } ?>>Use Lightbox</option>
                        </select><br />
                         *If you choose neither, Foxypress will swap the main image with the thumbnail clicked.
                    </td>
                </tr>
                <tr>
                    <td align="right" valign="top" nowrap>Max Downloads</td>
                    <td align="left">
                        <input type="text" name="foxypress_max_downloads" value="<?php echo(get_option("foxypress_max_downloads")) ?>" /><br />
                        *Sets the maximum number of downloads allowed for a downloadable product. <br />
                        You can specify this at a global level here and also at a product level.
                    </td>
                </tr>  
                <tr>
                    <td align="right" valign="top" nowrap>Quantity Alert Level</td>
                    <td align="left">
                        <input type="text" name="foxypress_qty_alert" value="<?php echo(get_option("foxypress_qty_alert")) ?>" /><br />
                        *You will be notified via email when the quantity of an item goes below this threshold. <br />
                        You can set this to 0 or blank if you do not want any notifications.
                    </td>
                </tr>    
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Keep Products On Uninstall</td>
                    <td align="left">
                        <input type="checkbox" name="foxypress_uninstall_keep_products" value="1" <?php echo(((get_option('foxypress_uninstall_keep_products') == "1") ? "checked=\"checked\"" : "")) ?> /> *We will not delete your products on deletion of FoxyPress if this is checked.
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div id="" class="settings_widefat">
        <div class="settings_head store">
            Store Options
        </div>		
        <div class="settings_inside">
            <table>
            	<tr valign="top">
                    <td align="right" valign="top" nowrap class="title">Enable Cart Validation</td>
                    <td align="left">                                
                        <input type="checkbox" name="foxycart_hmac" value="1" <?php echo(((get_option('foxycart_hmac') == "1") ? "checked=\"checked\"" : "")) ?> /> *If you want to take advantage of cart validation, you must enable the cart validation feature in the FoxyCart admin panel under Store->Advanced.
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap class="title">Enable Multi-Ship</td>
                    <td align="left">                                
                        <input type="checkbox" name="foxycart_enable_multiship" value="1" <?php echo(((get_option('foxycart_enable_multiship') == "1") ? "checked=\"checked\"" : "")) ?> /> *Allows customers to ship to multiple addresses
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Enable SSO</td>
                    <td align="left">
	                    <input type="hidden" name="foxycart_enable_sso_previous" value="<?php echo(get_option('foxycart_enable_sso')); ?>" />
	                    <input type="checkbox" name="foxycart_enable_sso" value="1" <?php echo(((get_option('foxycart_enable_sso') == "1") ? "checked=\"checked\"" : "")) ?> /> *Enables Single Sign On. FoxyPress can automatically sync your WordPress and FoxyCart users. <br />
	                    <p>If you want to take advantage of this feature, copy the SSO Endpoint URL below and enable the Single Sign On feature in the FoxyCart admin panel. Also, be sure to set the 'Customer Password Hash Type' to phpass, portable mode and 'Customer Password Hash Config' to 8 </p>
                    </td>
                </tr>
                <tr valign="top">
                  <td align="right" valign="top" nowrap>SSO Endpoint</td>
                  <td align="left">
                    <input type="text" name="foxycart_sso_endpoint" id="foxycart_sso_endpoint" value="<?php echo(plugins_url() . "/foxypress/foxysso.php") ?>" size="125" readonly="readonly" /><br />
                    <p>*FoxyPress can automatically sync your WordPress and FoxyCart users. If you want to take advantage of this feature, copy this url and enable the Single Sign On feature in the FoxyCart admin panel and above in the FoxyPress Settings. Also, be sure to set the 'Customer Password Hash Type' to phpass, portable mode and 'Customer Password Hash Config' to 8 
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Product Feed</td>
                    <td align="left">
                    <input type="text" name="foxycart_product_feed" id="foxycart_product_feed" value="<?php echo(plugins_url() . "/foxypress/productfeed.php?b=" . $wpdb->blogid . "") ?>" size="125" readonly /> <br />
                    *RSS Feed of your Products compatible with Google Products
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>DataFeed</td>
                    <td align="left">
                    <input type="text" name="foxycart_data_feed" id="foxycart_data_feed" value="<?php echo(plugins_url() . "/foxypress/foxydatafeed.php") ?>" size="125" readonly /> <br />
                    *If you are using digital downloads, use this URL as your data feed url in FoxyCart.
                    </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Additional DataFeed(s)</td>
                    <td align="left">
                        <input type="text" name="foxycart_datafeeds" id="foxycart_datafeeds" value="<?php echo(get_option('foxycart_datafeeds')) ?>" size="125" /> <br />
                        *If you have additional datafeeds that need to be hit, enter the full url for your datafeed. <br />
                    Separate multiple datafeeds with a comma. </td>
                </tr>
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Show Dashboard Widget</td>
                    <td align="left">
                        <input type="checkbox" name="foxycart_show_dashboard_widget" value="1" <?php echo(((get_option('foxycart_show_dashboard_widget') == "1") ? "checked=\"checked\"" : "")) ?> /> *Shows/Hides the FoxyPress dashboard widget
                    </td>
                </tr>
                <?php if( foxypress_IsMultiSite() && (foxypress_IsMainBlog() || !foxypress_HasMainBlog()) ) { ?>
                 <tr valign="top">
                    <td align="right" valign="top" nowrap>Main Site</td>
                    <td align="left">
                        <input type="hidden" name="foxypress_main_blog_previous" value="<?php echo(get_option('foxypress_main_blog')); ?>" />
                        <input type="checkbox" name="foxypress_main_blog" value="1" <?php echo(((get_option('foxypress_main_blog') == "1") ? "checked=\"checked\"" : "")) ?> /> *If you mark this as your main site, you will be able to see orders from all of your sub-sites.
                    </td>
                </tr>
                <? } ?>                
            </table>
        </div>
    </div>
     
	<div id="" class="settings_widefat">
        <div class="settings_head custom">
            Packing Slip Wizard Settings
        </div>		
        <div class="settings_inside">
            <table>  
                <tr valign="top">
                    <td align="right" valign="top" nowrap>Header Image</td>
                    <td align="left">
                        <input type="text" name="foxypress_packing_slip_header" size="125" value="<?php echo(get_option('foxypress_packing_slip_header')); ?>" /> <br />
                        *If you would like a custom header image on your packing slip, use the media library to upload an image and set the url here.
                    </td>
                </tr>   
				<tr valign="top">
                    <td align="right" valign="top" nowrap>Default Footer Message</td>
                    <td align="left">
                        <textarea name="foxypress_packing_slip_footer_message" cols="75" rows="3"><?php echo(get_option('foxypress_packing_slip_footer_message')); ?></textarea> <br />
                        *A custom message can be generated for the footer of packing slips.  Set a default here so you don't have to type it each time if you'd prefer.
                    </td>
                </tr>                
            </table>
        </div>
    </div>

    <div id="" class="settings_widefat">
        <div class="settings_head custom">
            Custom Instructions
        </div>		
        <div class="settings_inside">
            <table>  
                <tr>
                    <td align="right" valign="top" nowrap class="title">Item Out Of Stock Message</td>
                    <td align="left">
                        <input type="text" name="foxypress_out_of_stock_message" value="<?php echo(get_option("foxypress_out_of_stock_message")) ?>"  size="125" /><br />
                        *Foxypress will show this message instead of the default out of stock message
                    </td>
                </tr>  
                 <tr>
                    <td align="right" valign="top" nowrap>Item Unavailable/Inactive Message</td>
                    <td align="left">
                        <input type="text" name="foxypress_inactive_message" value="<?php echo(get_option("foxypress_inactive_message")) ?>" size="125" /><br />
                        *Foxypress will show this message instead of the default unavailable message
                    </td>
                </tr>                   
            </table>
        </div>
    </div>

	<div id="" class="settings_widefat">
        <div class="settings_head custom">
            SMTP Mail Settings
        </div>		
        <div class="settings_inside">
			<p>Should you need to configure SMTP settings for secure mail through your webhost, we've allowed you to define these values below.  Keep in mind that these settings are only for mail going out of FoxyPress <i>(order management)</i>.  It will not change your overall WordPress mail() functionality.</p>
            <table>  
                <tr>
                    <td align="right" valign="top" nowrap class="title">SMTP Host</td>
                    <td align="left">
                        <input type="text" name="foxypress_smtp_host" value="<?php echo(get_option("foxypress_smtp_host")) ?>"  size="50" /><br />
                        <i>*your smtp host here</i>
                    </td>
                </tr>  
                 <tr>
                    <td align="right" valign="top" nowrap>Secure Port (optional)</td>
                    <td align="left">
                        <input type="text" name="foxypress_secure_port" value="<?php echo(get_option("foxypress_secure_port")) ?>" size="50" /><br />
                        <i>*465</i>
                    </td>
                </tr>       
			 	<tr>
                    <td align="right" valign="top" nowrap>Email Username</td>
                    <td align="left">
                        <input type="text" name="foxypress_email_username" value="<?php echo(get_option("foxypress_email_username")) ?>" size="50" /><br />
                        <i>*your full email here</i>
                    </td>
                </tr>    
				 <tr>
                    <td align="right" valign="top" nowrap>Email Password</td>
                    <td align="left">
                        <input type="text" name="foxypress_email_password" value="<?php echo(get_option("foxypress_email_password")) ?>" size="50" /><br />
                        <i>*your email password here</i>
                    </td>
                </tr>                
            </table>
        </div>
    </div>
    
     <p class="submit"><input type="submit" class="button-primary" id="btnFoxyPressSettingsSave" name="btnFoxyPressSettingsSave" value="<?php _e('Save Changes') ?>" /></p>
 </form>
 <?php 
 } else { ?>
 <form method="POST">
     <div id="wizard_container">
        <ul class="wizard_menu">
            <li id="step-one" class="active">Step 1</li>
            <li id="step-two">Step 2</li>
            <li id="step-three">Step 3</li>
        </ul>

        <span class="wizard_clear"></span>
        <div class="wizard_tab_content step-one">
            <img src="<?php echo(plugins_url())?>/foxypress/img/logo.png" />
            <p>Thanks for installing FoxyPress! Lets get a few things taken care of quickly to get you on your way to selling!</p>
            <p>What is your FoxyCart Store Domain?</p>
            <input type="text" name="foxycart_storeurl_wizard" id="foxycart_storeurl_wizard" /><br />
             <i>ex. if your store url is foxypress.foxycart.com, enter just 'foxypress'.</i>
            <p>What version is your FoxyCart store?</p>
            <select name="foxycart_storeversion_wizard" width="300" style="width: 300px">
                <option value="0.7.1">0.7.1</option>
                <option value="0.7.0">0.7.0</option>             
            </select>
			<img id="step-two-nav" class="wizard_nav next" src="<?php echo(plugins_url())?>/foxypress/img/next.png" />
        </div>

        <div class="wizard_tab_content step-two">
            <img src="<?php echo(plugins_url())?>/foxypress/img/logo.png" />
            <p>Need a reference to jQuery?  You can change this later if you want.</p>
            <select name="foxycart_include_jquery_wizard" width="300" style="width: 300px">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            <p>By default, FoxyPress comes loaded with the ability to choose different ways to display your photos.  Which will you choose?</p>
            <select name="foxypress_image_mode_wizard" id="foxypress_image_mode_wizard" style="width: 300px">
            	<option value="<?php echo(FOXYPRESS_USE_COLORBOX); ?>">Use Colorbox</option>
                <option value="<?php echo(FOXYPRESS_USE_LIGHTBOX); ?>">Use Lightbox</option>
                <option value="">Neither</option>                
            </select>
			<img id="step-one-nav" class="wizard_nav prev" src="<?php echo(plugins_url())?>/foxypress/img/prev.png" />
			<img id="step-three-nav" class="wizard_nav next" src="<?php echo(plugins_url())?>/foxypress/img/next.png" />
        </div>
        <div class="wizard_tab_content step-three">
            <img src="<?php echo(plugins_url())?>/foxypress/img/logo.png" />
			<div id="wizard_success">
                <p class="submit">
                    <input type="submit" class="button-primary" id="btnFoxyPressSettingsSaveWizard" name="btnFoxyPressSettingsSaveWizard" value="<?php _e('Save Settings') ?>" />
				</p>
                 <p>That is all! We have additional settings available, but these are the core things you should have setup before doing anything else.</p>
                 <p>Now lets get Foxy!</p>
            </div>
            <div id="wizard_error">
                 <p>Please make sure you enter information into all of the fields on this wizard before continuing.</p>
				<p>You are missing:</p>
                 <ul><li style="color:Red";>Store Domain</li></ul>
            </div>
			<img id="step-two-nav" class="wizard_nav prev" src="<?php echo(plugins_url())?>/foxypress/img/prev.png" />
        </div>
    </div>
</form>
  <script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(".wizard_menu > li").click(function(e){
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
						CheckWizardInputs();
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
						CheckWizardInputs();
					break;
				}
				//alert(e.target.id);
				return false;
			});
		});
		
		function CheckWizardInputs()
		{
			var storedomain = jQuery('#foxycart_storeurl_wizard').val();
			if(storedomain == "")
			{
				jQuery('#wizard_success').hide();
				jQuery('#wizard_error').show();
			}
			else
			{
				jQuery('#wizard_success').show();
				jQuery('#wizard_error').hide();
			}
		}
	</script>
<?php
	}
}
?>