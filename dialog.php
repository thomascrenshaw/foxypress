<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');

	/** Load WordPress Administration Bootstrap */
	require_once( ABSPATH . 'wp-admin/admin.php' );

	// Enable the ability for the inventory to be loaded from pages
	add_filter('the_content','inventory_insert');
	$ShowSearchResults = false;

	//handle postbacks
	if(isset($_POST['foxy_search_button']))
	{
		$searchterm =  foxypress_FixPostVar('foxy_search');
		$searchSQL = "SELECT i.* FROM " . $wpdb->prefix ."posts as i
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																	and pm_active.meta_key = '_item_active'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																				and pm_start_date.meta_key = '_item_start_date'	
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																				and pm_end_date.meta_key = '_item_end_date'	
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																				and pm_code.meta_key = '_code'													
						WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
							AND i.post_status = 'publish'
							AND pm_active.meta_value = '1'
							AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							AND (
									pm_code.meta_value = '" . mysql_escape_string($searchterm) . "'
										or i.post_title = '" . mysql_escape_string($searchterm) . "'
										or i.post_content = '" . mysql_escape_string($searchterm) . "'
								)
					  UNION
					  SELECT i.* FROM " . $wpdb->prefix ."posts as i
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																	and pm_active.meta_key = '_item_active'
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																				and pm_start_date.meta_key = '_item_start_date'	
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																				and pm_end_date.meta_key = '_item_end_date'	
						LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																				and pm_code.meta_key = '_code'													
						WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
							AND i.post_status = 'publish'
							AND pm_active.meta_value = '1'
							AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							AND (
									pm_code.meta_value LIKE '%" . mysql_escape_string($searchterm) . "%'
										or i.post_title LIKE '%" . mysql_escape_string($searchterm) . "%'
										or i.post_content LIKE '%" . mysql_escape_string($searchterm) . "%'
								)";
		$searchitems = $wpdb->get_results($searchSQL);
		$ShowSearchResults = true;
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php _e('FoxyPress Plugin', 'foxypress'); ?></title>
<script type="text/javascript" src="../../../wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<script type="text/javascript" src="js/dialog.js?ver=2"></script>
<script type="text/javascript" language="javascript">
	function InsertItem(item_id)
	{
		FoxyPressDialog.InsertInventoryItem(item_id);
	}
	function InsertCategory()
	{
		FoxyPressDialog.InsertCategoryListing(document.getElementById('foxy_category_listing').value, document.getElementById('foxy_show_addtocart').value, document.getElementById('foxy_paging_items').value, document.getElementById('foxy_paging_itemsperrow').value, document.getElementById('foxy_show_moredetail').value);
	}
	function RelatedItems(item_id)
	{
		FoxyPressDialog.InsertRelatedItems(item_id);
	}
</script>
<style type="text/css">
	body {
		margin:0px;
		padding:0px;
		color: #666;
		font-family: "Lucida Grande", Verdana, sans-serif;
		font-size: 10pt;
		line-height: 20px;
		background-image:url(img/gradient.jpg);
		background-repeat:repeat-x;
		background-color:#dfdfdf;
	}
	
	a {text-decoration: none; color:#e11f26;}
	a:visited {text-decoration: none; color:#000000;}
	a:active {text-decoration: none; color:#000000;}
	a:hover {text-decoration: none; color:#000000;}
	
	h1 {
		color: #333;
		font-family: "Lucida Grande", Verdana;
		font-size: 10pt;
		font-variant: normal;
		font-weight: bold;
		line-height: 16px;
	}
	
	
	h2 {
		color: #999;
		font-family: "Lucida Grande", Verdana;
		font-size: 8pt;
		font-variant: normal;
		font-weight: normal;
		line-height: 12px;
	}
	
	
	hr {
	  color: #fff; 
	  background-color: #fff; 
	  border:2px dotted #ccc; 
	  border-style: none none dotted; 
	}
	
	.inventory-heading{
	  font-size: 9pt;
	  border-bottom: solid;
	}
	
	.inventory-label{
	  text-align: center;
	}
	
	#product-form{
	  min-height: 315px;
	}
	
	.DialogHeading
	{
		font-size: 11pt;
		font-weight:bold;
	}
	
	.center
	{
		margin-left:auto;
		margin-right:auto;
	}
	
	.centertext
	{
		text-align:center;	
	}
</style>
</head>
<body>
    <div style="margin-left:auto;margin-right:auto;background-image:url(img/top.jpg);height:19px;"></div>
    <div style="margin-left:auto;margin-right:auto;text-align:left;width:100%;min-height:597px;">
        <div style="text-align:center;"><img src="img/foxycart_logo.png" /></div>
        <?php
			ShowCategoryListing();
            ShowSearch();
            if($ShowSearchResults)
            {
                SearchResults($searchitems);
            }
            else
            {
                ShowInventory();
            }
        ?>
        <div style="text-align:center;"><img src="img/footer.png" /></div>
        <p style="text-align:center;">Please visit our forum for info and help for all your needs.
        <br />
        <a href="http://www.foxy-press.com/forum" target="_blank">http://www.foxy-press.com/forum</a>
        </p>
    </div>
    <div style="margin-left:auto;margin-right:auto;background-image:url(img/bottom.jpg);height:19px;"></div>
</body>
</html>

<?php

function ShowCategoryListing()
{
	global $wpdb;
	?>
    <div class="center">
    <form method="POST" name="foxy_inventory_listing_frm" id="foxy_inventory_listing_frm">
    	<table class="center">
        	<tr>
            	<td>
                	 <span class="DialogHeading"><?php _e('Show Items From', 'foxypress'); ?>:</span>
                </td>
                <td>
                	<select id="foxy_category_listing" name="foxy_category_listing">
						<?php
                        $cats = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories" );
                        foreach( $cats as $cat ) {
                            echo("<option value=\"" . $cat->category_id . "\">" . $cat->category_name . "</option>");
                        }
                        ?>
                    </select>
                </td>
			</tr>
            <tr>
            	<td><span class="DialogHeading"><?php _e('Show Add To Cart', 'foxypress'); ?>:</span> </td>
            	<td>
                	<select id="foxy_show_addtocart" name="foxy_show_addtocart">
                    	<option value="0"><?php _e('No', 'foxypress'); ?></option>
                        <option value="1"><?php _e('Yes', 'foxypress'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
            	<td><span class="DialogHeading"><?php _e('Items Per Page', 'foxypress'); ?>:</span> </td>
            	<td><input type="text" id="foxy_paging_items" name="foxy_paging_items" value="10" maxlength="3" /></td>
            </tr>
            <tr>
            	<td><span class="DialogHeading"><?php _e('Items Per Row', 'foxypress'); ?>:</span> </td>
            	<td><input type="text" id="foxy_paging_itemsperrow" name="foxy_paging_itemsperrow" value="2" maxlength="3" /></td>
            </tr>
            <td><span class="DialogHeading"><?php _e('Show More Detail Link', 'foxypress'); ?>:</span> </td>
            	<td>
                	<select id="foxy_show_moredetail" name="foxy_show_moredetail">
                    	<option value="0"><?php _e('No', 'foxypress'); ?></option>
                        <option value="1" selected="selected"><?php _e('Yes', 'foxypress'); ?></option>
                    </select>
                </td>
            <tr>
            	<td>&nbsp;</td>
            	<td><input type="button" onclick="InsertCategory();" value="<?php _e('Go', 'foxypress'); ?>" name="foxy_lsting_button" id="foxy_lsting_button" /></td>
            </tr>
        </table>
    </form>
    </div>
    <br /><hr /><br />
    <?php
}


function SearchResults($searchitems)
{
	if ( !empty($searchitems) ) {
    ?>
    <div style="width:100%; text-align:center;">
    	<span class="DialogHeading"><?php _e('texthere', 'foxypress'); ?>Search Results</span>
    </div>
    <form onsubmit="FoxyPressDialog.insert();return false;" id="foxypress-search-insert" action="#">
        <div class="DialogHeading"><?php _e('texthere', 'foxypress'); ?>Add Item From Inventory</div>
        <table class="widefat page fixed" cellpadding="3" cellspacing="0" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
                <tr class="inventory-head-row">
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Select','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Image','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Code','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Name','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Price','foxypress') ?></th>
                <?php
                $class = '';
                foreach ( $searchitems as $item ) {
                    $class = ($class == 'alternate') ? '' : 'alternate';
					$featuredImageID = (has_post_thumbnail($item->ID) ? get_post_thumbnail_id($item->ID) : 0);
					$imageNumber = 0;
					$src = "";
					$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $item->ID, 'order' => 'ASC','orderby' => 'menu_order'));
					foreach ($attachments as $attachment) 
					{
						$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
						if ($featuredImageID == $attachment->ID || ($featuredImageID == 0 && $imageNumber == 0)) $src = $thumbnailSRC[0];
						$imageNumber++;
					}
					if (!$src) $src = INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;	
					$price = foxypress_GetActualPrice(get_post_meta($item->ID, "_price", true), get_post_meta($item->ID, "_saleprice", true), get_post_meta($item->ID, "_salestartdate", true), get_post_meta($item->ID, "_saleenddate", true));
                ?>
                    <tr class="<?php echo $class; ?>">
                        <td><input type="button" name="select" value="Select" onclick="InsertItem('<?php echo(stripslashes($item->ID)); ?>');" /></td>
                        <td><img src="<?php echo($src); ?>" width="35px" /></td>
                        <td><label for="inventory_code" class="inventory-label"><?php echo get_post_meta($item->ID, "_code", true); ?></label></td>
                        <td><label for="inventory_name"><?php echo stripslashes($item->post_title); ?></label></td>
                        <td><label for="inventory_price"><?php echo foxypress_FormatCurrency($price); ?></label></td>
                   </tr>
                <?php }  ?>
        </table>
	</form>
    <?php
  	}
 	else
  	{
		?>
		<div class="centertext"><?php _e("There are no items matching your search",'foxypress')  ?></div>
        <div class="centertext"><a href="<?php echo(foxypress_GetCurrentPageURL()); ?>"><?php _e('Full Inventory List', 'foxypress'); ?></a></div>
    <?php
  }
}

function ShowSearch()
{
	?>
    <div class="centertext">
        <form method="POST" name="foxy_inventory_search_frm" id="foxy_inventory_search_frm">
            <span class="DialogHeading"><?php _e('Search Inventory', 'foxypress'); ?>:</span> <input type="text" id="foxy_search" name="foxy_search" /> 
			<input type="submit" value="<?php _e('Go', 'foxypress'); ?>" name="foxy_search_button" id="foxy_search_button" />
        </form>
    </div><Br>
    <hr /><br />
    <?php
}

function ShowInventory()
{
	global $wpdb, $post;
	//set up paging
	$limit = 10;
	$targetpage = foxypress_GetCurrentPageURL();
	$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
	$pos = strrpos($targetpage, "?");
	if ($pos === false) {
		$targetpage .= "?";
	}

	$drRows = $wpdb->get_row("SELECT count(i.ID) as RowCount
								FROM " . $wpdb->prefix ."posts as i								
								WHERE i.post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status='publish'
								ORDER BY i.ID DESC");
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;
	$items = $wpdb->get_results("SELECT i.*
								FROM " . $wpdb->prefix ."posts as i								
								WHERE i.post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status='publish'
								ORDER BY i.ID DESC
								LIMIT $start, $limit");
?>
<?php
  if ( !empty($items) ) {
    ?>
    <div style="width:100%; text-align:center;">
    	<span class="DialogHeading"><?php _e('Add Item From Inventory', 'foxypress'); ?></span>
    </div>
    <form onsubmit="FoxyPressDialog.insert();return false;" id="foxypress-insert" action="#">
    	<input id="code" name="code" type="hidden" class="text" size="30" value="" />
        <table class="widefat page fixed" cellpadding="3" cellspacing="0" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
                <tr class="inventory-head-row">
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Select','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Image','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Code','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Name','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Price','foxypress') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Related Items','foxypress') ?></th>
                <?php
                $class = '';
                foreach ( $items as $item ) {
                    $class = ($class == 'alternate') ? '' : 'alternate';
					$featuredImageID = (has_post_thumbnail($item->ID) ? get_post_thumbnail_id($item->ID) : 0);
					$imageNumber = 0;
					$src = "";
					$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $item->ID, 'order' => 'ASC','orderby' => 'menu_order'));
					foreach ($attachments as $attachment) 
					{
						$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
						if ($featuredImageID == $attachment->ID || ($featuredImageID == 0 && $imageNumber == 0)) $src = $thumbnailSRC[0];
						$imageNumber++;
					}
					if (!$src) $src = INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;	
					$price = foxypress_GetActualPrice(get_post_meta($item->ID, "_price", true), get_post_meta($item->ID, "_saleprice", true), get_post_meta($item->ID, "_salestartdate", true), get_post_meta($item->ID, "_saleenddate", true));
                ?>
                    <tr class="<?php echo $class; ?>">
                        <td><input type="button" name="select" value="Select" onclick="InsertItem('<?php echo(stripslashes($item->ID)); ?>');" /></td>
                        <td><img src="<?php echo($src); ?>" width="35px" /></td>
                        <td><label for="inventory_code" class="inventory-label"><?php echo get_post_meta($item->ID, "_code", true); ?></label></td>
                        <td><label for="inventory_name"><?php echo stripslashes($item->post_title); ?></label></td>
                        <td><label for="inventory_price"><?php echo foxypress_FormatCurrency($price); ?></label></td>
                        <td><input type="button" name="related" value="Insert Related Items" onclick="RelatedItems('<?php echo(stripslashes($item->ID)); ?>');" /></td>
                   </tr>
                <?php }  ?>
        </table>
        <?
		//pagination
		if($drRows->RowCount > $limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
			echo ("<br />" . $Pagination);
		}
		?>
	</form>
    <?php
  	}
 	else
  	{
		?>
		<div class="centertext"><?php _e("There are no inventory items in the database!",'foxypress')  ?></div>
    <?php
  }
}

?>
