<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.foxy-press.com/
Description: FoxyPress provides a complete shopping cart and inventory management tool for use with FoxyCart’s e-commerce solution. Easily manage inventory, view and track orders, generate reports and much more.
Author: WebMovement, LLC
Version: 0.3.2
Author URI: http://www.webmovementllc.com/

**************************************************************************

Copyright (C) 2008-2011 WebMovement, LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************

In short, this plugin is free to use by anyone and everyone. You are
welcome to use it on a commercial site or whatever you want. However, I do
very much appreciate donations for all of my time and effort, although
they obviously aren't required for you to use this plugin.

If you sell this code (i.e. are a web developer selling features provided
via this plugin to clients), it would be very nice if you threw some of
your profits my way. After all, you are profiting off my hard work. ;)

Thanks and enjoy this plugin!

**************************************************************************/

include_once( 'settings.php' );
include_once( 'setup.php' );
global $foxypress_url;
$foxypress_url = get_option('foxycart_storeurl');
if ( !empty ( $foxypress_url ) ){
	// init process for button control
	add_action('init', 'foxypress_addbuttons');
	add_action('wp_head', 'foxypress_importFoxyScripts' );
}
add_shortcode('foxypress', 'foxypress_shortcode');
add_action( 'admin_menu', 'foxypress_add_menu' );
add_action('admin_init', 'foxypress_Install');
add_action( 'widgets_init', 'foxypress_load_minicart' );

$foxypress_locale = get_option('foxycart_currency_locale');
setlocale(LC_MONETARY, ($foxypress_locale != "") ? $foxypress_locale  : get_locale());
$foxypress_localesettings = localeconv();
if ($foxypress_localesettings['int_curr_symbol'] == "") setlocale(LC_MONETARY, 'en_US');

// foxypress constants
define('WP_TRANSACTION_TABLE', $wpdb->prefix . 'foxypress_transaction');
define('WP_TRANSACTION_NOTE_TABLE', $wpdb->prefix . 'foxypress_transaction_note');
define('WP_TRANSACTION_SYNC_TABLE', $wpdb->prefix . 'foxypress_transaction_sync');
define('WP_TRANSACTION_STATUS_TABLE', $wpdb->prefix . 'foxypress_transaction_status');
define('WP_FOXYPRESS_CONFIG_TABLE', $wpdb->prefix . 'foxypress_config');
define('WP_FOXYPRESS_INVENTORY_OPTIONS', $wpdb->prefix . 'foxypress_inventory_options');
define('WP_FOXYPRESS_INVENTORY_ATTRIBUTES', $wpdb->prefix . 'foxypress_inventory_attributes');
define('WP_FOXYPRESS_INVENTORY_OPTION_GROUP', $wpdb->prefix . 'foxypress_inventory_option_group');
define('WP_INVENTORY_TABLE', $wpdb->prefix . 'foxypress_inventory');
define('WP_INVENTORY_CATEGORIES_TABLE', $wpdb->prefix . 'foxypress_inventory_categories');
define('WP_INVENTORY_TO_CATEGORY_TABLE', $wpdb->prefix . 'foxypress_inventory_to_category');
define('WP_INVENTORY_IMAGES_TABLE', $wpdb->prefix . 'foxypress_inventory_images');
define('WP_INVENTORY_DOWNLOADABLES', $wpdb->prefix . 'foxypress_inventory_downloadables');
define('WP_DOWNLOADABLE_TRANSACTION', $wpdb->prefix . 'foxypress_downloadable_transaction');
define('WP_DOWNLOADABLE_DOWNLOAD', $wpdb->prefix . 'foxypress_downloadable_download');
define('WP_POSTS', $wpdb->prefix . 'posts');
define('INVENTORY_IMAGE_DIR', get_bloginfo("url") . "/wp-content/inventory_images");
define('INVENTORY_IMAGE_LOCAL_DIR', "wp-content/inventory_images/");
define('INVENTORY_DOWNLOADABLE_DIR', get_bloginfo("url") . "/wp-content/inventory_downloadables");
define('INVENTORY_DOWNLOADABLE_LOCAL_DIR', "wp-content/inventory_downloadables/");
define('INVENTORY_DEFAULT_IMAGE', "default-product-image.jpg");
define('WP_FOXYPRESS_CURRENT_VERSION', "0.3.2");

if ( !empty ( $foxypress_url ) ){
	// Include inventory settings and functionality \\
	include_once('inventory.php');
	include_once('inventory-category.php');
	include_once('inventory-option-groups.php');
	include_once('order-management.php');
	include_once('status-management.php');
	include_once('reports.php');
	include_once('import-export.php');
}

if(get_option("foxycart_show_dashboard_widget") == "1")
{
	add_action('wp_dashboard_setup', 'foxypress_DashboardSetup');
}

function foxypress_load_minicart()
{
	register_widget( 'FoxyPress_MiniCart' );
}

function foxypress_ShowDashboardStats()
{
	global $wpdb;
	$statsQuery = "select
					(select sum(foxy_transaction_order_total) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 30 DAY))) MonthTotal
					,(select count(*) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 30 DAY))) MonthOrders
					,(select sum(foxy_transaction_order_total) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 7 DAY))) WeekTotal
					,(select count(*) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 7 DAY))) WeekOrders
					,(select sum(foxy_transaction_order_total) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 1 DAY))) OneDayTotal
					,(select count(*) from " . WP_TRANSACTION_TABLE . " where (foxy_transaction_date <= now() and foxy_transaction_date >= (now() - INTERVAL 1 DAY))) DayOrders
					,(select sum(foxy_transaction_order_total) from " . WP_TRANSACTION_TABLE . ") OverallTotal
					,(select count(*) from " . WP_TRANSACTION_TABLE . ") OverallOrders
					,(select count(inventory_id) from " . WP_INVENTORY_TABLE . ") TotalProducts
					,(select count(category_id) from " . WP_INVENTORY_CATEGORIES_TABLE . ") TotalCategories";
	$dtStats = $wpdb->get_row($statsQuery);
	echo("<div style=\"float:left;\">
			<h4>Order History</h4>
			<p>
				1 Day: " . $dtStats->DayOrders . " order" . (($dtStats->DayOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OneDayTotal) . "<br />
				7 Days: " . $dtStats->WeekOrders . " order" . (($dtStats->WeekOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->WeekTotal) . "<br />
				30 Days: " . $dtStats->MonthOrders . " order" . (($dtStats->MonthOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->MonthTotal) . " <br />
				Overall: " . $dtStats->OverallOrders . " order" . (($dtStats->OverallOrders == 1) ? "" : "s")   . ", " . foxypress_FormatCurrency($dtStats->OverallTotal) .
			"</p>
			<h4>Product Summary</h4>
			<p>
				" . $dtStats->TotalProducts . " Product" . (($dtStats->TotalProducts == 1) ? "" : "s") . "<br />
				" . $dtStats->TotalCategories . (($dtStats->TotalCategories == 1) ? " Category" : " Categories") .
			"</p>
		  </div>
		  <div style=\"float:right;padding-right:10px;padding-top:10px;\"><img src=\"" . plugins_url(). "/foxypress/img/FoxyPressLogoSmall.png\" alt=\"FoxyPress\" /></div>
		  <div style=\"clear:both;\"></div>");
}

function foxypress_DashboardSetup()
{
	wp_add_dashboard_widget( 'foxypress_dashboard', __( 'FoxyPress Statistics' ), 'foxypress_ShowDashboardStats' );
}

function foxypress_addbuttons() {
   // Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
    	return;

   // Add only in Rich Editor mode
   	if ( get_user_option('rich_editing') == 'true') {
    	add_filter("mce_external_plugins", "foxypress_add_tinymce_plugin");
    	add_filter('mce_buttons_3', 'foxypress_register_plugin_button');
   	}
}

function foxypress_register_plugin_button($buttons) {
	array_push($buttons, "foxypress");
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function foxypress_add_tinymce_plugin($plugin_array) {
	$path = foxypress_GetEditorPluginURL("foxypress");
	$plugin_array['foxypress'] = $path;
	return $plugin_array;
}

// determine absolute url path of editor_plugin.js
function foxypress_GetEditorPluginURL($type) {
    //check if defined WordPress Plugins URL
	if (defined('WP_PLUGINS_URL'))  {
		return WP_PLUGINS_URL."/". $type ."/editor_plugin.js";
	}else{
	//if not assumme it is default location.
		return "../../../wp-content/plugins/". $type ."/editor_plugin.js";
	}
}

function foxypress_GetCurrenySymbol()
{
	return substr(foxypress_FormatCurrency(0), 0, 1);
}

function foxypress_FormatCurrency($price)
{
	$res = "";
	if (function_exists('money_format'))
	{
		$res = utf8_encode(money_format("%" . ".2n", (double)$price));
	}
	else
	{
		$currency = (get_option('foxycart_currency_locale') == "en_GB" ? "Â£" : "$");
		$res = utf8_encode($currency . number_format((double)$price,2,".",","));
	}
	return $res;
}

function foxypress_handle_tracking_module()
{
	$url = plugins_url() . "/foxypress/ajax.php";
	$trackingform = "<div> Enter your order number </div>
		 <div><input type=\"text\" id=\"foxypress_order_number\" name=\"foxypress_order_number\" value=\"\" /></div>
		 <div> Enter your last name </div>
		 <div><input type=\"text\" id=\"foxypress_order_name\" name=\"foxypress_order_name\" value=\"\" /></div>
		 <div><input type=\"button\" id=\"foxypress_tracking_button\"	name=\"foxypress_tracking_button\"	value=\"Find Tracking Number\" onclick=\"foxypress_find_tracking('" . $url . "');\" />
		 </div>
		 <div id=\"foxypress_find_tracking_return\"></div>
	 ";
	return $trackingform;
}

function foxypress_GetDownloadableAndMinMaxFormFields($DownloadableID, $min, $max, $qty)
{
	$IsDownloadable =  foxypress_IsDownloadable($DownloadableID);
	$MaxField = "";
	$MinField = "";
	//min
	if($min > 0)
	{
		if($qty != null && $qty < $min)
		{
			$MinField  = "<input type=\"hidden\" name=\"quantity_min\" value=\"" . stripslashes($qty) . "\" />";
		}
		else
		{
			$MinField  = "<input type=\"hidden\" name=\"quantity_min\" value=\"" . stripslashes($min) . "\" />";
		}
	}
	//max
	if($max > 0)
	{
		if($qty != null && $qty < $max)
		{
			$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($qty) . "\" />";
		}
		else
		{
			$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($max) . "\" />";
		}
	}
	else
	{
		$MaxField  = "<input type=\"hidden\" name=\"quantity_max\" value=\"" . stripslashes($qty) . "\" />";
	}

	if($IsDownloadable)
	{
		return "<input type=\"hidden\" name=\"Downloadable\" value=\"true\" />
				<input type=\"hidden\" name=\"quantity_max\" value=\"1\" />";
	}
	return "<input type=\"hidden\" name=\"Downloadable\" value=\"false\" />" .
			$MinField .
			$MaxField;
}

function foxypress_handle_shortcode_listing($CategoryID, $Limit=5, $ItemsPerRow=2, $DetailURL = '', $ShowMainImage=true, $ShowAddToCart=false, $ShowQuantityField=false)
{
	global $wpdb;
	$Output = "";
	$CssSuffix = ($ShowAddToCart) ? "_nodetail" : "";
	$targetpage = foxypress_RemoveQSValue(foxypress_GetFullURL(), "fp_pn");
	if (strrpos($targetpage, "?") === false) {
		$targetpage .= "?";
	}
	$drRows = $wpdb->get_row("SELECT count(i.inventory_id) as RowCount
								FROM " . WP_INVENTORY_TABLE . " as i
								INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id and
																							ic.category_id = '" .  $CategoryID . "'
								INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
								WHERE i.inventory_active = '1'
 									AND (coalesce(i.inventory_start_date, now()) <= now() AND coalesce(i.inventory_end_date, now()) >= now())
								");
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;
	//get all items within this category. format the result set somehow
	$items = $wpdb->get_results("SELECT i.*
									,c.category_name
									,(select inventory_images_id
										from " . WP_INVENTORY_IMAGES_TABLE . "
										where inventory_id = i.inventory_id
										order by image_order limit 0,1) as inventory_images_id
								   ,(select inventory_image
								   		from " . WP_INVENTORY_IMAGES_TABLE . "
										where inventory_id = i.inventory_id
										order by image_order limit 0,1) as inventory_image
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id and
																							ic.category_id = '" .  $CategoryID . "'
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							WHERE i.inventory_active = '1'
 									AND (coalesce(i.inventory_start_date, now()) <= now() AND coalesce(i.inventory_end_date, now()) >= now())
							ORDER BY ic.sort_order, i.inventory_code DESC
							LIMIT $start, $Limit");
	if(!empty($items))
	{
		$counter = 0;
		foreach($items as $item)
		{
			if($counter == 0)
			{
				$Output .= "<div class=\"foxypress_item_row\">";
			}
			$Output .= foxypress_handle_shortcode_item($item->inventory_id, $DetailURL, $ShowAddToCart, $ShowMainImage, $ShowQuantityField, $CssSuffix);
			$counter++;
			if($counter == $ItemsPerRow)
			{
				$Output .= "	<div class=\"foxypress_item_row_clear\">&nbsp;</div>
							</div>";
				$counter = 0;
			}
		}
		//close out the last div if we haven't
		if($counter != 0)
		{
			$Output .= "	<div class=\"foxypress_item_row_clear\"></div>
						</div>";
		}
		//pagination
		if($drRows->RowCount > $Limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $Limit, $targetpage, 'fp_pn');
			$Output .= "<Br>" . $Pagination;
		}
	}
	return $Output;
}

function foxypress_handle_shortcode_item($InventoryID, $DetailURL = '', $ShowAddToCart = true, $ShowMainImage = true, $ShowQuantityField = false, $CssSuffix = '')
{
	global $wpdb; global $foxypress_url;
	$UseLightbox = (get_option('foxycart_use_lightbox') == "1");
	$OutOfStockMessage = (get_option("foxypress_out_of_stock_message") != "") ? get_option("foxypress_out_of_stock_message") : "Sorry, we are out of stock for this item, please check back later.";
	$UnavailableMessage = (get_option("foxypress_inactive_message") != "") ? get_option("foxypress_inactive_message") : "Sorry, this item is no longer available.";
	$MoreDetail = "";
	$Output = "";
	$item = $wpdb->get_row("SELECT i.*
									,c.category_name
									,(select inventory_images_id
										from " . WP_INVENTORY_IMAGES_TABLE . "
										where inventory_id = i.inventory_id
										order by image_order limit 0,1) as inventory_images_id
								   ,(select inventory_image
								   		from " . WP_INVENTORY_IMAGES_TABLE . "
										where inventory_id = i.inventory_id
										order by image_order limit 0,1) as inventory_image
									,d.downloadable_id
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							LEFT JOIN " . WP_INVENTORY_DOWNLOADABLES . " as d on i.inventory_id = d.inventory_id and d.status = 1
							WHERE i.inventory_id = '" . $InventoryID . "'
								AND i.inventory_active = '1'
 								AND (coalesce(i.inventory_start_date, now()) <= now() AND coalesce(i.inventory_end_date, now()) >= now())
							ORDER BY i.inventory_code DESC");
	if(empty($item))
	{
		return $UnavailableMessage;
	}
	$ActualPrice = foxypress_GetActualPrice($item->inventory_price, $item->inventory_sale_price, $item->inventory_sale_start, $item->inventory_sale_end);
	$Multiship = (get_option('foxycart_enable_multiship') == "1") ?
				  "<div class=\"shipto_container_wrapper" . $CssSuffix . "\">
						<div class=\"shipto_select\" style=\"display:none\">
							<label>Ship this item to:</label><br />
							(you will be able to input shipping addresses during checkout)<br />
							<select name=\"x:shipto_name_select\">
							</select>
						</div>
						<div class=\"shipto_name\">
							<label>Enter the name of the recipient (or leave it empty to ship it to yourself):</label><br />
							<input type=\"text\" name=\"shipto\" value=\"\" />
						</div>
					</div>" : "";
	$QuantityField = ($ShowQuantityField)
					  ? "<div class=\"foxypress_item_quantity_wrapper" . $CssSuffix . "\">
								Quantity: <input type=\"text\" name=\"quantity\" value=\"1\" class=\"foxypress_item_quantity" . $CssSuffix . "\" />
							</div>"
					  : "";
	$CanAddToCart = foxypress_CanAddToCart($item->inventory_id, $item->inventory_quantity);
	$ItemOptions = foxypress_BuildOptionList($item->inventory_id, "foxypress_form", $item->inventory_quantity_max);
	//check to see if we need to link to a detail page
	if($DetailURL != "")
	{
		$BaseURL = get_option("foxypress_base_url");
		$FullURL =  get_bloginfo("url");
		if($BaseURL != "")
		{
			$FullURL .= "/" . $BaseURL;
		}
		$FullURL .= $DetailURL;
		$FullURL = foxypress_AddQSValue($FullURL, "id",  $item->inventory_id);
		$MoreDetail = "<div class=\"foxypress_item_readmore" . $CssSuffix . "\"><a href=\"" . $FullURL . "\">Read More</a></div>";
	}

	if($ShowAddToCart)
	{
		$FormID = "foxypress_form_" . foxypress_GenerateRandomString(8);
		$ItemImages =  $wpdb->get_results("SELECT *
											FROM " . WP_INVENTORY_IMAGES_TABLE . "
											WHERE inventory_id = '$InventoryID'
											ORDER BY image_order");
		if(!empty($ItemImages) && ($wpdb->num_rows > 1))
		{
			$ItemThumbs = "<ul class=\"foxypress_item_image_thumbs" . $CssSuffix . "\">";
			foreach($ItemImages as $ii)
			{
				$ItemThumbs .= "<li><a href=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" " . (($UseLightbox) ? "rel=\"lightbox[foxypress" . $item->inventory_id . "]\" title=\"" . stripslashes($item->inventory_name) . "\"" : "rel=\"colorbox\"") . "><img src=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" /></a></li>";
			}
			$ItemThumbs .= "</ul>";
		}
		if($ShowMainImage)
		{
			$ImageOutput = ($item->inventory_image != "") //if we have an image show it, else show default
							? ($ItemThumbs == "") //if we have no thumbs, make the main image clickable, else just <img>
								? "<a href=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" " . (($UseLightbox) ? "rel=\"lightbox[foxypress" . $item->inventory_id . "]\" title=\"" . stripslashes($item->inventory_name) . "\"" : "rel=\"colorbox\"") . "><img src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" /></a>"
								: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" />"
							: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" />";
		}
		$ImageOutput = "<div class=\"foxypress_item_image" . $CssSuffix . "\">" . $ImageOutput . $ItemThumbs . "</div>";
		//show item
		$Output = "<div class=\"foxy_item_wrapper" . $CssSuffix . "\">
				   		<div class=\"foxypress_item_content_wrapper" . $CssSuffix . "\">
							<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"foxypress_form\">
								<input type=\"hidden\" name=\"quantity\" value=\"1\" />
								<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->inventory_name) . "\" />
								<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($item->inventory_code) . "\" />
								<input type=\"hidden\" name=\"price\" value=\"" . $ActualPrice . "\" />
								<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
								<input type=\"hidden\" name=\"image\" value=\"" . INVENTORY_IMAGE_DIR . '/' . (($item->inventory_image != "") ? stripslashes($item->inventory_image) : INVENTORY_DEFAULT_IMAGE) . "\" />
								<input type=\"hidden\" name=\"weight\" value=\"" . stripslashes($item->inventory_weight) . "\" />
								<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />" .
								foxypress_GetDownloadableAndMinMaxFormFields($item->downloadable_id, $item->inventory_quantity_min, $item->inventory_quantity_max, $item->inventory_quantity) .
								"<input type=\"hidden\" name=\"Inventory_ID\" value=\"" . $item->inventory_id . "\" />
								 <input type=\"hidden\" name=\"discount_quantity_amount\" value=\"" . stripslashes($item->inventory_discount_quantity_amount) . "\" />
								 <input type=\"hidden\" name=\"discount_quantity_percentage\" value=\"" . stripslashes($item->inventory_discount_quantity_percentage) . "\" />
								 <input type=\"hidden\" name=\"discount_price_amount\" value=\"" . stripslashes($item->inventory_discount_price_amount) . "\" />
								 <input type=\"hidden\" name=\"discount_price_percentage\" value=\"" . stripslashes($item->inventory_discount_price_percentage) . "\" />"
								 .
									foxypress_BuildAttributeForm($InventoryID)
								 .
								 "<div class=\"foxypress_item_name" . $CssSuffix . "\">" . stripslashes($item->inventory_name) . "</div>
								 <div class=\"foxypress_item_price" . $CssSuffix . "\">"
								 .
									( ($ActualPrice == $item->inventory_price)
										? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
										: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($item->inventory_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
									)
								 .
								 "</div>
								 <div class=\"foxypress_item_description" . $CssSuffix . "\">" .  stripslashes($item->inventory_description) . "</div>"
								 .
									foxypress_BuildAttributeList($InventoryID, $CssSuffix)
								 .
									( ($ItemOptions != "")
										? "<div class=\"foxypress_item_options" . $CssSuffix . "\">" . $ItemOptions . "</div>"
										: ""
									)
								 .
									$MoreDetail
								 .
								 ( ($CanAddToCart)
									?   $QuantityField .
										$Multiship .
										"<div class=\"foxypress_item_submit_wrapper" . $CssSuffix . "\">
											<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit" . $CssSuffix . "\" />
										</div>"
									:  "<div class=\"foxypress_item_submit_wrapper" . $CssSuffix . "\">
											<span>" . $OutOfStockMessage . "</span>
										</div>"
								 )
								 .
							"</form>
						</div>"
						.
						$ImageOutput
						.
					"</div>";
	}
	else
	{
		$ImageSrc = ($item->inventory_image != "") ? INVENTORY_IMAGE_DIR . "/" . $item->inventory_image : INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;
		$Output = "<div class=\"foxy_item_wrapper\">
				   		<div class=\"foxypress_item_image\">"
						.
							( ($MoreDetail != "")
								? "<a href=\"" . $FullURL . "\"><img src=\"" . $ImageSrc . "\" /></a>"
								: "<img src=\"" . $ImageSrc . "\" />"
							)
						.
						"</div>
						<div class=\"foxypress_item_content_wrapper\">
							<div class=\"foxypress_item_name\">" . stripslashes($item->inventory_name) . "</div>
							<div class=\"foxypress_item_price\">"
							 .
							 	( ($ActualPrice == $item->inventory_price)
									? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
									: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($item->inventory_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
								)
							 .
							"</div>
							<div class=\"foxypress_item_description\">" . foxypress_TruncateString(stripslashes($item->inventory_description), 70) . "</div>"
							.
								foxypress_BuildAttributeList($InventoryID, "")
							.
								$MoreDetail
							.
						"</div>
					</div>";
	}
	return $Output;
}

function foxypress_handle_shortcode_detail($showMainImage, $showQuantityField)
{
	global $wpdb; global $foxypress_url;
	$inventory_id = foxypress_FixGetVar('id');
	$UseLightbox = (get_option('foxycart_use_lightbox') == "1");
	$OutOfStockMessage = (get_option("foxypress_out_of_stock_message") != "") ? get_option("foxypress_out_of_stock_message") : "Sorry, we are out of stock for this item, please check back later.";
	$UnavailableMessage = (get_option("foxypress_inactive_message") != "") ? get_option("foxypress_inactive_message") : "Sorry, this item is no longer available.";
	$Output = "";
	$ImageOutput = "";
	$item = $wpdb->get_row("SELECT i.*
									,c.category_name
									,im.inventory_images_id
									,im.inventory_image
									,d.downloadable_id
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							LEFT JOIN
									(select inventory_images_id, inventory_id, inventory_image
									 from " . WP_INVENTORY_IMAGES_TABLE . "
									 where inventory_id='$inventory_id'
									 order by image_order limit 0,1) as im ON i.inventory_id = im.inventory_id
							LEFT JOIN " . WP_INVENTORY_DOWNLOADABLES . " as d on i.inventory_id = d.inventory_id and d.status = 1
							WHERE i.inventory_id = '$inventory_id'
								AND i.inventory_active = '1'
 								AND (coalesce(i.inventory_start_date, now()) <= now() AND coalesce(i.inventory_end_date, now()) >= now())
							ORDER BY i.inventory_code DESC");
	if(empty($item)){ return $UnavailableMessage; }
	//else set up item
	$ActualPrice = foxypress_GetActualPrice($item->inventory_price, $item->inventory_sale_price, $item->inventory_sale_start, $item->inventory_sale_end);
	$Multiship = (get_option('foxycart_enable_multiship') == "1") ?
				  "<div class=\"shipto_container_wrapper_detail\">
						<div class=\"shipto_select\" style=\"display:none\">
							<label>Ship this item to:</label><br />
							(you will be able to input shipping addresses during checkout)<br />
							<select name=\"x:shipto_name_select\">
							</select>
						</div>
						<div class=\"shipto_name\">
							<label>Enter the name of the recipient (or leave it empty to ship it to yourself):</label><br />
							<input type=\"text\" name=\"shipto\" value=\"\" />
						</div>
					</div>" : "";
	$QuantityField = ($showQuantityField)
					  ? "<div class=\"foxypress_item_quantity_wrapper_detail\">
								Quantity: <input type=\"text\" name=\"quantity\" value=\"1\" class=\"foxypress_item_quantity_detail\" />
							</div>"
					  : "";
	$CanAddToCart = foxypress_CanAddToCart($item->inventory_id, $item->inventory_quantity);
	$ItemOptions = foxypress_BuildOptionList($item->inventory_id, "foxypress_form", $item->inventory_quantity_max);
	$ItemImages =  $wpdb->get_results("SELECT *
										FROM " . WP_INVENTORY_IMAGES_TABLE . "
										WHERE inventory_id = '$inventory_id'
										ORDER BY image_order");
	if(!empty($ItemImages) && ($wpdb->num_rows > 1))
	{
		$ItemThumbs = "<ul class=\"foxypress_item_image_thumbs_detail\">";
		foreach($ItemImages as $ii)
		{
			$ItemThumbs .= "<li><a href=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" " . (($UseLightbox) ? "rel=\"lightbox[foxypress" . $item->inventory_id . "]\" title=\"" . stripslashes($item->inventory_name) . "\"" : "rel=\"colorbox\"") . "><img src=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" /></a></li>";
		}
		$ItemThumbs .= "</ul>";
	}
	if($showMainImage)
	{
		$ImageOutput = ($item->inventory_image != "") //if we have an image show it, else show default
						? ($ItemThumbs == "") //if we have no thumbs, make the main image clickable, else just <img>
							? "<a href=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" " . (($UseLightbox) ? "rel=\"lightbox[foxypress" . $item->inventory_id . "]\" title=\"" . stripslashes($item->inventory_name) . "\"" : "rel=\"colorbox\"") . "><img src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" /></a>"
							: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" />"
						: "<img src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" />";
	}
	$ImageOutput = "<div class=\"foxypress_item_image_detail\">" . $ImageOutput . $ItemThumbs . "</div>";
	//show item
	$Output = "<div class=\"foxy_item_wrapper_detail\">
					<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"foxypress_form\">
						<input type=\"hidden\" name=\"quantity\" value=\"1\" />
						<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->inventory_name) . "\" />
						<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($item->inventory_code) . "\" />
						<input type=\"hidden\" name=\"price\" value=\"" . $ActualPrice . "\" />
						<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
						<input type=\"hidden\" name=\"image\" value=\"" . INVENTORY_IMAGE_DIR . '/' . (($item->inventory_image != "") ? stripslashes($item->inventory_image) : INVENTORY_DEFAULT_IMAGE) . "\" />
						<input type=\"hidden\" name=\"weight\" value=\"" . stripslashes($item->inventory_weight) . "\" />
						<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />" .
						foxypress_GetDownloadableAndMinMaxFormFields($item->downloadable_id, $item->inventory_quantity_min, $item->inventory_quantity_max, $item->inventory_quantity) .
						"<input type=\"hidden\" name=\"Inventory_ID\" value=\"" . $item->inventory_id . "\" />
						 <input type=\"hidden\" name=\"discount_quantity_amount\" value=\"" . stripslashes($item->inventory_discount_quantity_amount) . "\" />
						 <input type=\"hidden\" name=\"discount_quantity_percentage\" value=\"" . stripslashes($item->inventory_discount_quantity_percentage) . "\" />
						 <input type=\"hidden\" name=\"discount_price_amount\" value=\"" . stripslashes($item->inventory_discount_price_amount) . "\" />
						 <input type=\"hidden\" name=\"discount_price_percentage\" value=\"" . stripslashes($item->inventory_discount_price_percentage) . "\" />"
						 .
						 	foxypress_BuildAttributeForm($inventory_id)
						 .
						 "<div class=\"foxypress_item_name_detail\">" . stripslashes($item->inventory_name) . "</div>
						 <div class=\"foxypress_item_price_detail\">"
						 .
						 	( ($ActualPrice == $item->inventory_price)
								? "<span class=\"foxypress_item_regular_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
								: "<span class=\"foxypress_item_original_price" . $CssSuffix . "\" style=\"text-decoration:line-through;\">" . foxypress_FormatCurrency($item->inventory_price) . "</span> <span class=\"foxypress_item_sale_price" . $CssSuffix . "\">" . foxypress_FormatCurrency($ActualPrice) . "</span>"
							)
						 .
						 "</div>
						 <div class=\"foxypress_item_description_detail\">" .  stripslashes($item->inventory_description) . "</div>"
						 .
						 	foxypress_BuildAttributeList($inventory_id, "_detail")
						 .
						 	( ($ItemOptions != "")
								? "<div class=\"foxypress_item_options_detail\">" . $ItemOptions . "</div>"
								: ""
							)
						 .
						 ( ($CanAddToCart)
						    ?   $QuantityField .
								$Multiship .
								"<div class=\"foxypress_item_submit_wrapper_detail\">
									<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit_detail\" />
								</div>"
							:  "<div class=\"foxypress_item_submit_wrapper_detail\">
							    	<span>" . $OutOfStockMessage . "</span>
							    </div>"
						 )
						 .
				"</form>
			</div>"
			.
			$ImageOutput;
	return $Output;
}

function foxypress_IsDownloadable($DownloadableID)
{
	if($DownloadableID != null && $DownloadableID != "" && $DownloadableID != "0")
	{
		return true;
	}
	return false;
}

function foxypress_GetActualPrice($price, $saleprice, $startdate, $enddate)
{
	$ActualPrice = $price;
	if($saleprice != "" && $saleprice > 0)
	{
		$CanUseSalePrice = false;
		//check dates
		if($startdate == null && $enddate == null)
		{
			$CanUseSalePrice = true;
		}
		$Today = strtotime(date("Y-m-d"));
		if(!$CanUseSalePrice && strtotime($startdate) <= $Today && strtotime($enddate) >= $Today)
		{
			$CanUseSalePrice = true;
		}
		if($CanUseSalePrice)
		{
			$ActualPrice = $saleprice;
		}
	}
	return $ActualPrice;
}

function foxypress_CanAddToCart($inventory_id, $quantity)
{
	//check the options available, if any of the option lists have 0 items, then we cannot add to cart
	global $wpdb;
	if($quantity == "0")
	{
		return false;
	}
	//get option groups
	$itemOptionGroups = $wpdb->get_results("SELECT distinct option_group_id
										FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
										WHERE inventory_id = '" . $inventory_id . "'");
	if(!empty($itemOptionGroups))
	{

		foreach($itemOptionGroups as $foxyoptiongroup)
		{
			//get option info
			$itemOptions = $wpdb->get_row(" SELECT (SELECT count(*)
															FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
														) AS TotalOptions,
														( SELECT count(*)
															FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
															AND (option_active = '0'
																	OR (option_quantity='0' AND option_code != '')
															     )
														) AS InactiveOptions");
			if(!empty($itemOptions))
			{
				if($itemOptions->TotalOptions == $itemOptions->InactiveOptions)
				{
					return false;
				}
			}
		}
	}

	return true;
}

function foxypress_BuildAttributeForm($inventory_id)
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
	 											,a.attribute_value
	 									      FROM " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . "  a
											  inner join " . WP_INVENTORY_TABLE . " i on a.inventory_id = i.inventory_id
											  WHERE i.inventory_id = '" . $inventory_id . "'
											  order by a.attribute_text");

	$formAttributes = "";
	if(!empty($itemAttributes))
	{
		foreach($itemAttributes as $foxyatt)
		{
			$formAttributes .= "<input type=\"hidden\" name=\"" . stripslashes($foxyatt->attribute_text) . "\" value=\"" . stripslashes($foxyatt->attribute_value) . "\" />";
		}
	}
	return $formAttributes;
}

function foxypress_BuildAttributeList($inventory_id, $CssSuffix = '')
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
	 											,a.attribute_value
	 									      FROM " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . "  a
											  inner join " . WP_INVENTORY_TABLE . " i on a.inventory_id = i.inventory_id
											  WHERE i.inventory_id = '" . $inventory_id . "'
											  order by a.attribute_text");

	$foxyAttributes = "";
	if(!empty($itemAttributes))
	{
		$foxyAttributes = "<div class=\"foxypress_item_attributes" . $CssSuffix . "\">";
		foreach($itemAttributes as $foxyatt)
		{
			$foxyAttributes .= "<div>" . stripslashes($foxyatt->attribute_text) . ": " . stripslashes($foxyatt->attribute_value) .  "</div>";
		}
		$foxyAttributes .= "</div>";
	}
	return $foxyAttributes;
}

function foxypress_BuildOptionList($inventory_id, $formid, $defaultMaxQty)
{
	global $wpdb;
	$MasterList = "";
	//get distinct option groups so we loop through those individually to create dropdowns
	$optionGroups = $wpdb->get_results("select distinct option_group_id from " . WP_FOXYPRESS_INVENTORY_OPTIONS . " where inventory_id='" . $inventory_id . "'");
	if(!empty($optionGroups))
	{
		foreach($optionGroups as $optionGroup)
		{
			//get options
			$soldOutList = array();
			$listItems = "";
			$jsData = "";
			$groupName = "";
			$soldOutItems = "";
			$initialMaxValue = "";
			$itemOptions = $wpdb->get_results("SELECT o.*
												,og.option_group_name
											   FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " as o
											   INNER JOIN " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " as og on o.option_group_id = og.option_group_id
											   WHERE o.option_group_id = '" . $optionGroup->option_group_id . "'
											   	AND o.inventory_id='" . $inventory_id . "'
											   ORDER BY option_order");
			if(!empty($itemOptions))
			{
				foreach($itemOptions as $option)
				{
					if($groupName == "")
					{
						$groupName = $option->option_group_name;
					}
					if($option->option_active == "1" && $option->option_quantity != "0")
					{
						$extraattribute = "";
						$extraattributefriendly = "";
						if($option->option_extra_price != "" && $option->option_extra_price != 0)
						{
							$isNegative = ($option->option_extra_price < 0);
							$extraattribute = "p" . ($isNegative ? "" : "+") . number_format($option->option_extra_price, 2);
							$extraattributefriendly = ($isNegative ? " " : " +") . foxypress_FormatCurrency($option->option_extra_price)
							;
						}
						if($option->option_extra_weight != "" && $option->option_extra_weight != 0)
						{
							$isNegative = ($option->option_extra_weight < 0);
							$extraattribute .= ($extraattribute == "") ? "w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2) : "|w" . ($isNegative ? "" : "+") . number_format($option->option_extra_weight, 2);
						}
						if($option->option_code != "")
						{
							$extraattribute .= ($extraattribute == "") ? "c:" . $option->option_code : "|c:" . $option->option_code;
						}
						if($extraattribute != "")
						{
							$extraattribute = "{" . $extraattribute . "}";
						}
						$listItems  .= "<option value=\"" . htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "\">" . htmlspecialchars(stripslashes($option->option_text)) . $extraattributefriendly . "</option>";
						if($option->option_code != "")
						{
							$tempJsData = htmlspecialchars(stripslashes($option->option_value)) . $extraattribute . "~" . $option->option_quantity;
							$jsData .= ($jsData == "") ? $tempJsData : "," . $tempJsData;
							if($initialMaxValue == "")
							{
								if($defaultMaxQty > 0)
								{
									if($option->option_quantity != null && $option->option_quantity < $defaultMaxQty)
									{
										$initialMaxValue = $option->option_quantity;
									}
									else
									{
										$initialMaxValue = $defaultMaxQty;
									}
								}
								else
								{
									if($option->option_quantity != null && $option->option_quantity > 0)
									{
										$initialMaxValue = 	$option->option_quantity;
									}
								}
							}
						}
					}
					else
					{
						$soldOutList[] = $option->option_text;
					}
				}
				if(count($soldOutList) > 0)
				{
					foreach($soldOutList as $soldOutItem)
					{
						$soldOutItems .= ($soldOutItems == "") ? $soldOutItem : ", " . $soldOutItem;
					}
					$soldOutItems = "<div class=\"foxypress_item_otions_soldout\">Sold Out Options: " . $soldOutItems . "</div>";
				}
				$JsToAdd = "";
				if(count($optionGroups) == 1 && $jsData != "")
				{
					$JsToAdd = "onchange=\"foxypress_modify_max('" . $formid . "', '" . $jsData . "', this.value, " . $defaultMaxQty . ");\"";
					$SetDefaultJS = "<script type=\"text/javascript\" language=\"javascript\">
										jQuery(document).ready(function() {
											var maxfield" . $formid . " = jQuery(\"#" . $formid . "\").find('input[name=quantity_max]');
											maxfield" . $formid . ".val(" . $initialMaxValue . ");
										});
									 </script>";
				}
				$MasterList .= "<div class=\"foxypress_item_options\">" .
									 stripslashes($groupName) . ":
									<select name=\"" . stripslashes($groupName) . "\" " . $JsToAdd . ">"
										. $listItems .
									"</select>" .
									$SetDefaultJS .
									$soldOutItems .
							   "</div>";
				unset($soldOutList);
			}
		}
	}
	return $MasterList;
}
/*
function foxypress_buildoptionlist_old($inventory_id, $formid)
{
	global $wpdb;
	//check if we have any options, order by group name first so that we group items together correctly
	$itemOptions = $wpdb->get_results("SELECT o.option_text
											,o.option_value
											,og.option_group_name
											,o.option_extra_price
											,o.option_extra_weight
											,o.option_code
											,o.option_active
									   FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " as o
									   inner join " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " as og on o.option_group_id = og.option_group_id
									   inner join " . WP_INVENTORY_TABLE . " i on o.inventory_id = i.inventory_id
									   WHERE i.inventory_id = '$inventory_id'
									   order by og.option_group_name, option_order");
	//check to see if we have any options & build up the dropdown if we do
	$foxyOptionList = "";
	if(!empty($itemOptions))
	{
		$OptionsList = array();
		$templist = "";
		$previousgroupname = "";
		$soldoutlist = array();
		foreach($itemOptions as $foxyoption)
		{
			//new group (new select list)
			if($previousgroupname != $foxyoption->option_group_name)
			{
				if($templist != "")
				{
					$templist .= "</select></div> ";
					if(count($soldoutlist) > 0)
					{
						$templist .= "<div class=\"foxypress_item_otions_soldout\">sold out options: ";
						$soldout = "";
						foreach($soldoutlist as $soldoutoption)
						{
							$soldout .= ($soldout == "") ? $soldoutoption : ", " . $soldoutoption;
						}
						$templist .= $soldout . "</div>";
						unset($soldoutlist);
					}
					$OptionsList[] = $templist;
					$templist = "";
				}
				$templist = " <div class=\"foxypress_item_options\">". $foxyoption->option_group_name . ": <select name=\"" . stripslashes($foxyoption->option_group_name) . "\">";
				$previousgroupname = $foxyoption->option_group_name;
			}

			if($foxyoption->option_active == "1")
			{
				$extraattribute = "";
				$extraattributefriendly = "";
				if($foxyoption->option_extra_price != "" && $foxyoption->option_extra_price != 0)
				{
					$isNegative = ($foxyoption->option_extra_price < 0);
					$extraattribute = "p" . ($isNegative ? "" : "+") . number_format($foxyoption->option_extra_price, 2);
					$extraattributefriendly = ($isNegative ? " " : " +") . foxypress_FormatCurrency($foxyoption->option_extra_price)
					;
				}
				if($foxyoption->option_extra_weight != "" && $foxyoption->option_extra_weight != 0)
				{
					$isNegative = ($foxyoption->option_extra_weight < 0);
					$extraattribute .= ($extraattribute == "") ? "w" . ($isNegative ? "" : "+") . number_format($foxyoption->option_extra_weight, 2) : "|w" . ($isNegative ? "" : "+") . number_format($foxyoption->option_extra_weight, 2);
				}
				if($foxyoption->option_code != "")
				{
					$extraattribute .= ($extraattribute == "") ? "c:" . $foxyoption->option_code : "|c:" . $foxyoption->option_code;
				}
				if($extraattribute != "")
				{
					$extraattribute = "{" . $extraattribute . "}";
				}
				$templist  .= '<option value="' . htmlspecialchars(stripslashes($foxyoption->option_value)) . $extraattribute . '">' . htmlspecialchars(stripslashes($foxyoption->option_text)) . $extraattributefriendly . '</option>';
			}
			else
			{
				$soldoutlist[] = $foxyoption->option_text;
			}
		}
		//close out the last one we are looping through
		$templist .= "</select></div>";
		if(count($soldoutlist) > 0)
		{
			$templist .= "<div class=\"foxypress_item_otions_soldout\">sold out options: ";
			$soldout = "";
			foreach($soldoutlist as $soldoutoption)
			{
				$soldout .= ($soldout == "") ? $soldoutoption : ", " . $soldoutoption;
			}
			$templist .= $soldout . "</div>";
			unset($soldoutlist);
		}
		$OptionsList[] = $templist;
		foreach($OptionsList as $foxylist)
		{
			$foxyOptionList .= $foxylist;
		}
	}
	return $foxyOptionList;
}
*/

function foxypress_shortcode( $atts, $content = null) {
	global $wpdb;
	global $foxypress_url;
	$mode = trim($atts['mode']);
	$mode = ($mode == "") ? "single" : $mode;
	$showMainImage = (strtolower(trim($atts['show_main_image'])) == "false") ? false : true;
	$showAddToCart = (trim($atts['addtocart']) == "1") ? true : false;
	$showQuantity = (trim($atts['show_qty']) == "1") ? true : false;

	if(trim($atts['id']) != '' && $mode == 'single')
	{
		return foxypress_handle_shortcode_item(trim($atts['id']), '', true, $showMainImage, $showQuantity, "_single");
	}
	else if(trim($atts['categoryid']) != '' && $mode == 'list')
	{
		return foxypress_handle_shortcode_listing(trim($atts['categoryid']), trim($atts['items']), trim($atts['cols']), trim($atts['detailurl']), $showMainImage, $showAddToCart, $showQuantity);
	}
	else if($mode == 'detail')
	{
		return foxypress_handle_shortcode_detail($showMainImage, $showQuantity);
	}
	else if($mode == 'tracking')
	{
		return foxypress_handle_tracking_module();
	}
}

function foxypress_category_item_count($category_name)
{
	global $wpdb;
	$item = $wpdb->get_row("SELECT count(i.inventory_id) as ItemCount
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							WHERE c.category_name='" . $category_name . "'
								AND i.inventory_active = '1'
 								AND (coalesce(i.inventory_start_date, now()) <= now() AND coalesce(i.inventory_end_date, now()) >= now())");
	if(!empty($item))
	{
		return $item->ItemCount;
	}
	return "0";
}


function foxypress_request($name, $default=null) {
    if (!isset($_REQUEST[$name])) return $default;
    return stripslashes_deep($_REQUEST[$name]);
}

function foxypress_add_menu() {
    // Set admin as the only one who can use Inventory for security
    $allowed_group = 'manage_options';

      // Add the admin panel pages for Inventory. Use permissions pulled from above
    if ( function_exists( 'add_menu_page' ) ) {
       add_menu_page( __( 'Foxypress','foxypress' ), __( 'Foxypress','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
    if ( function_exists( 'add_submenu_page' ) ) {
       add_submenu_page( 'foxypress', __( 'Settings','foxypress' ), __( 'Manage Settings','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
}

function foxypress_FixGetVar($variable, $default = '')
{
	$value = $default;
	if(isset($_GET[$variable]))
	{
		$value = trim($_GET[$variable]);
		if(get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_FixPostVar($variable, $default = '')
{
	$value = $default;
	if(isset($_POST[$variable]))
	{
		$value = trim($_POST[$variable]);
		//$value = addslashes($value);
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_DeleteItem($fileloc)
{
	if (file_exists($fileloc))
	{
		unlink($fileloc);
	}
}

function foxypress_ParseFileExtension($filename)
{
	$filename = strtolower($filename);
	$exts = split("[/\\.]", $filename);
	$n = count($exts)-1;
	$exts = $exts[$n];
	return $exts;
}

function foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, $prefix)
{
	$newName = $prefix . foxypress_GenerateRandomString(10) . "_" . $inventory_id . "." . $fileExtension;
	$directory = $targetpath;
	$directory .= ($directory!="") ? "/" : "";
	if(file_exists($directory . $newName))
	{
		return foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, $prefix);
	}
	return $newName;
}

function foxypress_Encrypt($item)
{
	$key = get_option('foxypress_encryption_key');
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $item, MCRYPT_MODE_CBC, md5(md5($key))));
}

function foxypress_Decrypt($item)
{
	$key = get_option('foxypress_encryption_key');
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($item), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
}

function foxypress_curlPostRequest($url, $postData) {
	$ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($postData))
	{
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	}
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  	curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
  	curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
  	$response = curl_exec($ch);
  	$info = curl_getinfo($ch);
	if ($response === false || $info['http_code'] != 200) {
		$output = "No cURL data returned for $url [". $info['http_code']. "]";
	  	if (curl_error($ch))
		{
	 		$response .= "\n". curl_error($ch);
		}
	}
	curl_close($ch);
    return($response);
}

//gets the exact url as presented in the url bar
function foxypress_GetFullURL()
{
	$pageURL = 'http';
	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != "off") {$pageURL .= "s";}
	$pageURL .= "://" . $_SERVER["SERVER_NAME"] . $_SERVER['REQUEST_URI'];
	return $pageURL;
}

//uses the phsyical file vs. the rewrite name
function foxypress_GetCurrentPageURL($includeQS = false) {
	$pageURL = 'http';
	if (!empty($_SERVER['HTTPS'])  && strtolower($_SERVER['HTTPS']) != "off") {$pageURL .= "s";}
	$pageURL .= "://" . $_SERVER["SERVER_NAME"] . $_SERVER['PHP_SELF'];
	if($includeQS)
	{
		$pageURL .= "?" . $_SERVER['QUERY_STRING'];
	}
	return $pageURL;
}


function foxypress_RemoveQSValue($url,$remove) {
    $infos=parse_url($url);
    $str=$infos["query"];
    $op = array();
    $pairs = explode("&", $str);
    foreach ($pairs as $pair) {
       list($k, $v) = array_map("urldecode", explode("=", $pair));
        $op[$k] = $v;
    }
    if(isset($op[$remove])){
        unset($op[$remove]);
    }
    return str_replace($str,http_build_query($op),$url);
}

function foxypress_AddQSValue($url, $key, $value) {
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    if (strpos($url, '?') === false) {
        return ($url . '?' . $key . '=' . $value);
    } else {
        return ($url . '&' . $key . '=' . $value);
    }
}

function foxypress_GetPagination($page, $total_pages, $limit, $targetpage, $qspagename = 'pagenum')
{
	$adjacents = 3;
	if ($page == 0) $page = 1;
	$prev = $page - 1;
	$next = $page + 1;
	$lastpage = ceil($total_pages/$limit);
	$lpm1 = $lastpage - 1;

	$pagination = "";
	if($lastpage > 1)
	{
		$pagination .= "<div class=\"foxy_item_pagination\">";
		//previous button
		if ($page > 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$prev\"><< previous</a>";
		else
			$pagination.= "<span class=\"disabled\"><< previous</span>";

		//pages
		if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
		{
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $page)
					$pagination.= "<span class=\"current\">$counter</span>";
				else
					$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
			}
		}
		elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
		{
			//close to beginning; only hide later pages
			if($page < 1 + ($adjacents * 2))
			{
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage?" . $qspagename . "=$counter\">$counter</a>";
				}
			}
		}
		//next button
		if ($page < $counter - 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$next\">next >></a>";
		else
			$pagination.= "<span class=\"disabled\">next >></span>";
		$pagination.= "</div>\n";
	}
	return $pagination;
}

function foxypress_TruncateString($str, $length)
{
	if(strlen($str) > $length)
	{
		return substr($str, 0, $length) . "...";
	}
	return $str;
}

function foxypress_GenerateRandomString($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

function foxypress_importFoxyScripts(){
	$version = get_option('foxycart_storeversion');
	$includejq = get_option('foxycart_include_jquery');
	$enablemuliship = get_option('foxycart_enable_multiship');
	if(get_option('foxycart_storeurl')!=''){
		if($includejq)
		{
			echo("<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js\"></script>");
		}
		if($version=="0.7.1"){
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.3.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.16/style1_fc/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}else{
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.9/style1/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}
		if($enablemuliship == "1")
		{
			echo('<script src="' . plugins_url() . '/foxypress/js/multiship.jquery.js" type="text/javascript" charset="utf-8"></script>	');
		}
		if(get_option('foxycart_use_lightbox') == "1")
		{
			echo("<script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/prototype.js\"></script>
				  <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/scriptaculous.js?load=effects,builder\"></script>
				  <script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/lightbox.js\"></script>
				  <link rel=\"stylesheet\" href=\"". plugins_url() ."/foxypress/css/lightbox.css\" type=\"text/css\" media=\"screen\" />");
		}
		echo"
		<link rel=\"stylesheet\" href=\"" . plugins_url() ."/foxypress/style.css\">
		<script type=\"text/javascript\" src=\"" . plugins_url() ."/foxypress/js/jquery.qtip.js\"></script>
		<script type='text/javascript'>
			function foxypress_find_tracking(baseurl)
			{
				var ordernumber = jQuery('#foxypress_order_number').val();
				var lastname = jQuery('#foxypress_order_name').val();
				if(ordernumber != '' && lastname != '')
				{
					var url = baseurl + '?m=tracking&id=' + ordernumber + '&ln=' + lastname;
					//alert(url);
					jQuery.ajax(
						{
							url : url,
							type : \"GET\",
							datatype : \"json\",
							cache : \"false\",
							success : function (data) { foxypress_find_tracking_callback(data); }
						}
					);
				}
				else
				{
					alert('Please fill out both the order number and you last name');
				}
			}

			function foxypress_find_tracking_callback(data)
			{
				var res = '';
				if(data.ajax_status == 'ok')
				{
					res =  '<div><div id=\"foxy_order_details\">Order Details</div><div id=\"foxy_order_details_name\">' + data.name + '</div><div id=\"foxy_order_details_address\">' + data.shipping_address + '</div><div id=\"foxy_order_details_status\">Status: ' + data.current_status + '</div><div id=\"foxy_order_details_tracking\">Tracking Number: ' + ((data.tracking_number != '') ? data.tracking_number : 'n/a') + '</div></div>';
				}
				else
				{
					res = 'We could not find that order number in our system, please try again or check back later.';
				}
				jQuery('#foxypress_find_tracking_return').html(res);

			}

			jQuery(document).ready(function() {
				jQuery(\"a[rel='colorbox']\").colorbox();
			});

			function foxypress_modify_max(formid, data, selectedvalue, defaultmax)
			{
				var options = data.split(\",\");
				var maxfield = jQuery(\"#\" + formid).find('input[name=quantity_max]');
				maxfield.val(defaultmax);
				for(i = 0; i < options.length; i++)
				{
					var optionData = options[i].split(\"~\");
					var OptionValue = optionData[0];
					var OptionQuantity = optionData[1];

					if(OptionValue == selectedvalue)
					{
						if(OptionQuantity != null && OptionQuantity != '')
						{
							if(defaultmax != '' && defaultmax > OptionQuantity)
							{
								maxfield.val(OptionQuantity);
							}
							else if(defaultmax == '' || defaultmax == '0')
							{
								maxfield.val(OptionQuantity);
							}
						}
					}
				}
			}

		</script>";
	}
}

class FoxyPress_MiniCart extends WP_Widget {

	function FoxyPress_MiniCart() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'min', 'description' => __('A widget that will display the FoxyCart cart as a dropdown or in your website\'s sidebar.', 'example') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'mini-cart-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'mini-cart-widget', __('FoxyPress Mini-Cart', 'example'), $widget_ops, $control_ops );
	}

	//Display widget on frontend
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		$hideonzero = apply_filters('widget_title', $instance['hideonzero'] );
		$dropdowndisplay = apply_filters('widget_title', $instance['dropdowndisplay'] );
		echo $before_widget;
		if ( $title )
		{
			echo $before_title . $title . $after_title;
		}
		if ( $dropdowndisplay == "" )
		{
			if ( $hideonzero == "1" )
			{
				?> <div id="fc_minicart"> <?php
			}
			?>
				<span id="fc_quantity">0</span> items.<br />
				<span id="fc_total_price">0.00</span>
				<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?cart=view" class="foxycart">View Cart</a>
			<?php
			if ( $hideonzero == "1" )
			{
				?> </div> <?php
			}
		}
		else
		{
			?>
			<a href="#" class="fc_link"><strong>Your Cart</strong></a>
			<div id="fc_cart">
				<img src="<?php echo(plugins_url()) ?>/foxypress/img/cart.png" alt="your cart"/>
				<h2>Your Cart</h2>
				<div class="fc_clear"></div>
				<table>
					<thead>
					<th>item</th>
					<th>qty</th>
					<th>price</th>
					</thead>
					<tbody id="cart_content">
					</tbody>
				</table>
				<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?checkout" id="fc_checkout_link">Check Out</a>
				<div class="fc_clear"></div>
			</div>
			<script type="text/javascript" charset="utf-8">
				var StoreURL = '<?php echo(get_option('foxycart_storeurl')) ?>';
				var FoxyDomain = StoreURL + ".foxycart.com/";
				var timer = 0;
				// this function hides the cart in a very nice way
				function json_cart_fade_out(){
				     if (timer != 0){
				          clearTimeout(timer);
				          timer = 0;
				     }
				     $("#fc_cart").animate({
						  top: 0 - $("#fc_cart").height(),
						  opacity: 0
				      }, 1000);
				}
				$(document).ready(function(){
					fcc.events.cart.postprocess.add(function(){
						fcc.cart_update.call(fcc);
						jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
							console.info(cart.product_count);
							console.info(cart.total_price);
							console.info(cart.total_discount);
							var total_price = cart.total_price - cart.total_discount;
							fc_FoxyCart = "";
					        for (i=0;i<cart.products.length;i++) {
					                fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
					        }
					        // fc_FoxyCart is a javascript variable that now holds your shopping cart data
					        // if you have some products in your cart, why not display it?
					        if (cart.products.length > 0) {
					                $("#fc_cart #cart_content").html(fc_FoxyCart);
					        } else {
					                $("#fc_cart #cart_content").html("");
					        }
						});
					});
					jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
						console.info(cart.product_count);
						console.info(cart.total_price);
						console.info(cart.total_discount);
						var total_price = cart.total_price - cart.total_discount;
						fc_FoxyCart = "";
				        for (i=0;i<cart.products.length;i++) {
				                fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
				        }
				        // fc_FoxyCart is a javascript variable that now holds your shopping cart data
				        // if you have some products in your cart, why not display it?
				        if (cart.products.length > 0) {
				                $("#fc_cart #cart_content").html(fc_FoxyCart);
				        } else {
				                $("#fc_cart #cart_content").html("");
				        }
					});
				   // shows the cart when the mouse is positioned on an specific link
				   $(".fc_link").mouseover(function(){

					   if (timer!= 0){
					        clearTimeout(timer);
					        timer = 0;
					   }

					   $("#fc_cart").animate({
					             opacity: '.99',
					             top: '0px'
					       }, 1000);
					   timer = setTimeout(function(){
					      json_cart_fade_out();
					   }, 2500);
					   // if the user is looking/using the cart don't hide it
					   $("#fc_cart").hover(function(){
					      clearTimeout(timer);
					      timer = 0;
					   }, function(){
					      timer = setTimeout(function(){
					        json_cart_fade_out();
					      }, 1000);
					   });

				   });

				});

				function fc_BuildFoxyCartRow(fc_name,fc_code,fc_options,fc_quantity,fc_price_each,fc_price) {
				        fc_FoxyCart += "<tr>";
				        fc_FoxyCart += "<td>" + fc_name + "</td>";
				//      fc_FoxyCart += "<td>" + fc_options + "</td>";
				//      fc_FoxyCart += "<td>" + fc_code + "</td>";
				        fc_FoxyCart += "<td class=\"right-align\">" + fc_quantity + "</td>";
				//      fc_FoxyCart += "<td>" + fc_price_each + "</td>";
				        fc_FoxyCart += "<td class=\"right-align\">" + fc_price.toFixed(2) + "</td>";
				        fc_FoxyCart += "</tr>";
				}
			</script>
			<?php
		}
		echo $after_widget;
	}

	//update widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['hideonzero'] = strip_tags( $new_instance['hideonzero'] );
		$instance['dropdowndisplay'] = strip_tags( $new_instance['dropdowndisplay'] );
		return $instance;
	}

	//displays the widget settings
	function form( $instance ) {
		//default settings
		$defaults = array( 'title' => __('Your Cart', 'example'), 'hideonzero' => __('0', 'example'), 'dropdowndisplay' => __('0', 'example'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label><br />
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" type="text" />
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'hideonzero' ); ?>" name="<?php echo $this->get_field_name( 'hideonzero' ); ?>" value="1" <?php echo(($instance['hideonzero'] == "1") ? "checked=\"checked\"" : "") ?>  type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'hideonzero' ); ?>"><?php _e('Hide Cart with 0 Items', 'hybrid'); ?></label>
		</p>
		<p>
        	<input id="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>" name="<?php echo $this->get_field_name( 'dropdowndisplay' ); ?>" value="1" <?php echo(($instance['dropdowndisplay'] == "1") ? "checked=\"checked\"" : "") ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'dropdowndisplay' ); ?>"><?php _e('Drop Down Display', 'hybrid'); ?></label>
		</p>
	<?php
	}
}
?>