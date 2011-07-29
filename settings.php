<?php
//$foxycart_options = get_option('foxycart');
function foxypress_options()
{
    ?>
    <div class="wrap" style="text-align:center;">
    <img src="<?php echo(plugins_url())?>/foxypress/img/foxycart_logo.png" />
    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
        <table class="form-table">
            <tr>
            	<td align="center">
                	<p>The FoxyPress Plugin was created to provide users a way to harness the easy to use e-commerce functionality of FoxyCart along with the power of the WordPress content management system.</p>
              </td>
            </tr>
            <tr>
            	<td align="center">
                	<table>
                        <tr valign="top">
                            <td align="right" valign="top" nowrap>FoxyCart Store Subdomain URL</td>
                            <td align="left">
                                <input type="text" name="foxycart_storeurl" value="<?php echo get_option('foxycart_storeurl'); ?>" size="50" />
                                <br />
                                *A store url is required in order to use Foxypress. <br />
                                <i>ex. if your store url is websevenpointo.foxycart.com, enter just 'websevenpointo'.</i>
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
                                <select name="foxycart_storeversion" width="300" style="width: 300px">
                              <?php
                                $version = get_option('foxycart_storeversion');
                                if($version=="0.7.1"){
                                    echo("<option value='0.7.1' selected>0.7.1</option>");
                                    echo("<option value='0.7.0'>0.7.0</option>");
                                }else{
                                    echo("<option value='0.7.1'>0.7.1</option>");
                                    echo("<option value='0.7.0' selected>0.7.0</option>");
                                }
                              ?>
                                </select>
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
                        <tr valign="top">
                        	<td align="right" valign="top" nowrap>Include jQuery</td>
                            <td align="left">
                            <?php
								$includejq = get_option('foxycart_include_jquery');
							?>
                            	<input type="checkbox" name="foxycart_include_jquery" value="1" <?php echo((($includejq == "1") ? "checked=\"checked\"" : "")) ?> /> *We will automatically include a reference to jQuery
                            </td>
                        </tr>
                        <tr valign="top">
                        	<td align="right" valign="top" nowrap>Enable Multi-Ship</td>
                            <td align="left">
                            <?php
								$enablems = get_option('foxycart_enable_multiship');
							?>
                            	<input type="checkbox" name="foxycart_enable_multiship" value="1" <?php echo((($enablems == "1") ? "checked=\"checked\"" : "")) ?> /> *Allows customers to ship to multiple addresses
                            </td>
                        </tr>
                        <tr valign="top">
                          <td align="right" valign="top" nowrap>Product Feed</td>
                          <td align="left">
                          	<input type="text" name="foxycart_product_feed" id="foxycart_product_feed" value="<?php echo(plugins_url() . "/foxypress/productfeed.php") ?>" size="125" readonly /> <br />
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
                            	*If you have additional datafeeds that need to be hit, enter the full url for your datafeed. Separate multiple datafeeds with a comma.
                          </td>
                        </tr>
                        
                        
                        <tr>
                        	<td align="right" valign="top" nowrap>Product Detail Base URL</td>
                            <td align="left">
                            	<input type="text" name="foxypress_base_url" value="<?php echo(get_option("foxypress_base_url")) ?>" /><br />
								*You can leave this field blank if you have permalinks turned on. ex: <?php echo(get_bloginfo("url")) ?>/my-page <br />
                                If permalinks do not work on your version of wordpress, please enter the base url. <br />
                                If your pages look like <b><?php echo(get_bloginfo("url")) ?>/index.php/my-page</b> you should enter index.php as the base url.
                            <td>
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
                    </table>
            	</td>
            </tr>
            <tr>
            	<td align="center">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="page_options" value="foxycart_storeurl,foxycart_apikey,foxycart_storeversion,foxycart_include_jquery, foxypress_base_url, foxycart_enable_multiship,foxypress_max_downloads,foxypress_qty_alert,foxycart_datafeeds,foxycart_currency_locale" />
              		<p class="submit">
              			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
              		</p>
            	</td>
            </tr>
        </table>
  </form>
  <img src="<?php echo(plugins_url())?>/foxypress/img/footer.png" />
  <p style="text-align:center;">
  	Please visit our forum for info and help for all your needs.<br />
  	<a href="http://www.foxy-press.com/forum" target="_blank">http://www.foxy-press.com/forum</a><br /><br />
    Need a FoxyCart account?  Go to <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">FoxyCart</a> today and sign up!
  </p>
  </div>
<?php
}
?>