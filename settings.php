<?php
/*include_once("cart_validation.php");
$foxyClass = new FoxyCart_Helper;*/

$foxycart_options = get_option('foxycart');
function foxypress_options()
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
    ?>
    <div class="wrap" style="text-align:center;">
    <img src="../wp-content/plugins/foxypress/img/foxycart_logo.png" />
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
                            <td align="right" nowrap>FoxyCart Store Subdomain URL</td>
                            <td align="left">
                                <input type="text" name="foxycart_storeurl" value="<?php echo get_option('foxycart_storeurl'); ?>" size="50" />
                                <br />
                                *A store url is required in order to use Foxypress. <br />
                                <i>ex. if your store url is websevenpointo.foxycart.com, enter just 'websevenpointo'.</i>
                            </td>
                        </tr>
                        <tr valign="top">
                            <td align="right" nowrap>FoxyCart API Key</td>
                            <td align="left">
                              <?php
                                if(get_option('foxycart_apikey')==''){
                                    echo'<input type="text" name="foxycart_apikey" value="'  . $apikey .  '" size="50" readonly />';
                                }else{
                                    echo'<input type="text" name="foxycart_apikey" value="' . get_option("foxycart_apikey")  . '" size="50" readonly />';
                                }
                              ?>
                                <br />
                                *Please copy this into your FoxyCart settings for your Datafeed/API Key
                            </td>
                        </tr>
                        <tr valign="top">
                            <td align="right" nowrap>FoxyCart Store Version</td>
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
                        	<td align="right" nowrap>Include jQuery</td>
                            <td alig="left">
                            <?
								$includejq = get_option('foxycart_include_jquery');
							?>
                            	<input type="checkbox" name="foxycart_include_jquery" value="1" <?=(($includejq == "1") ? "checked=\"checked\"" : "") ?> /> *We will automatically include a reference to jQuery
                            </td>
                        </tr>
                        <tr valign="top">
                          <td align="right" nowrap>Product Feed</td>
                          <td alig="left">
                          	<input type="text" name="foxycart_product_feed" id="foxycart_product_feed" value="<?=get_bloginfo("url") . "/wp-content/plugins/foxypress/productfeed.php"?>" size="50" readonly /> <br />
                            *RSS Feed of your Products compatible with Google Products
                          </td>
                        </tr>
                    </table>
            	</td>
            </tr>
            <tr>
            	<td align="center">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="page_options" value="foxycart_storeurl,foxycart_apikey,foxycart_storeversion,foxycart_include_jquery" />
              		<p class="submit">
              			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
              		</p>
            	</td>
            </tr>
        </table>
  </form>
  <img src="../wp-content/plugins/foxypress/img/footer.png" />
  <p style="text-align:center;">
  	Please visit our forum for info and help for all your needs.<br />
  	<a href="http://www.foxy-press.com/forum" target="_blank">http://www.foxy-press.com/forum</a><br /><br />
    Need a FoxyCart account?  Go to <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">FoxyCart</a> today and sign up!
  </p>
  </div>
<?
}
?>