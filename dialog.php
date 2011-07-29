<?php
	/** Load WordPress Administration Bootstrap */
	require_once('../../../wp-admin/admin.php');
	// Enable the ability for the inventory to be loaded from pages
	add_filter('the_content','inventory_insert');
	$ShowSearchResults = false;

	//handle postbacks
	if(isset($_POST['foxy_search_button']))
	{
		$searchterm =  foxypress_FixPostVar('foxy_search');
		$searchitems = $wpdb->get_results("SELECT i.*
										,c.category_name
										,im.inventory_images_id
											,im.inventory_image
									FROM " . WP_INVENTORY_TABLE . " as i
									INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
									INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
									LEFT JOIN
										(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
										from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
									WHERE i.inventory_code = '" . $searchterm . "'
									UNION
									SELECT i.*
										,c.category_name
										,im.inventory_images_id
											,im.inventory_image
									FROM " . WP_INVENTORY_TABLE . " as i
									INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
									INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
									LEFT JOIN
										(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
										from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
									WHERE i.inventory_code LIKE '%" . $searchterm . "%'
									UNION
									SELECT i.*
										,c.category_name
										,im.inventory_images_id
											,im.inventory_image
									FROM " . WP_INVENTORY_TABLE . " as i
									INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
									INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
									LEFT JOIN
										(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
										from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
									WHERE i.inventory_name = '" . $searchterm . "'
									UNION
									SELECT i.*
										,c.category_name
										,im.inventory_images_id
											,im.inventory_image
									FROM " . WP_INVENTORY_TABLE . " as i
									INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
									INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
									LEFT JOIN
										(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
										from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
									WHERE i.inventory_name LIKE '%" . $searchterm . "%'");
		$ShowSearchResults = true;
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>FoxyPress Plugin</title>
<script type="text/javascript" src="../../../wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<script type="text/javascript" src="js/dialog.js"></script>
<script type="text/javascript" language="javascript">
	function InsertItem(item_id)
	{
		FoxyPressDialog.InsertInventoryItem(item_id);
	}
	function InsertCategory()
	{
		FoxyPressDialog.InsertCategoryListing(document.getElementById('foxy_category_listing').value, document.getElementById('foxy_show_addtocart').value, document.getElementById('foxy_paging_items').value, document.getElementById('foxy_paging_itemsperrow').value, document.getElementById('foxy_paging_itemdetailurl').value);
	}
</script>
<link href="css/style.css" rel="stylesheet" type="text/css" />
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
                	 <span class="DialogHeading">Show Items From:</span>
                </td>
                <td>
                	<select id="foxy_category_listing" name="foxy_category_listing">
						<?php
                        $cats = $wpdb->get_results( "SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE );
                        foreach( $cats as $cat ) {
                            echo("<option value=\"" . $cat->category_id . "\">" . $cat->category_name . "</option>");
                        }
                        ?>
                    </select>
                </td>
			</tr>
               <tr>
            	<td><span class="DialogHeading">Show Add To Cart:</span> </td>
            	<td>
                	<select id="foxy_show_addtocart" name="foxy_show_addtocart">
                    	<option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </td>
            </tr>
            <tr>
            	<td><span class="DialogHeading">Items Per Page:</span> </td>
            	<td><input type="text" id="foxy_paging_items" name="foxy_paging_items" value="10" maxlength="3" /></td>
            </tr>
            <tr>
            	<td><span class="DialogHeading">Items Per Row:</span> </td>
            	<td><input type="text" id="foxy_paging_itemsperrow" name="foxy_paging_itemsperrow" value="2" maxlength="3" /></td>
            </tr>
            <tr>
            	<td><span class="DialogHeading">Item Detail URL:</span> </td>
            	<td><input type="text" id="foxy_paging_itemdetailurl" name="foxy_paging_itemdetailurl" value="/foxy-product-detail"  /></td>
            </tr>
            <tr>
            	<td colspan="2">*Leave the url blank if the items do not need to link to a detail page</td>
            </tr>
            <tr>
            	<td>&nbsp;</td>
            	<td><input type="button" onclick="InsertCategory();" value="Go" name="foxy_lsting_button" id="foxy_lsting_button" /></td>
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
    	<span class="DialogHeading">Search Results</span>
    </div>
    <form onsubmit="FoxyPressDialog.insert();return false;" id="foxypress-search-insert" action="#">
        <div class="DialogHeading">Add Item From Inventory</div>
        <table class="widefat page fixed" cellpadding="3" cellspacing="0" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
                <tr class="inventory-head-row">
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Select','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Image','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Code','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Name','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Price','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Category','inventory') ?></th>
                <?php
                $class = '';
                foreach ( $searchitems as $item ) {
                    $class = ($class == 'alternate') ? '' : 'alternate';
                ?>
                    <tr class="<?php echo $class; ?>">
                        <td><input type="button" name="select" value="Select" onclick="InsertItem('<?=stripslashes($item->inventory_id)?>');" /></td>
                        <td><img src="<?php echo INVENTORY_IMAGE_DIR . '/' . stripslashes($item->inventory_image); ?>" width="35px" /></td>
                        <td><label for="inventory_code" class="inventory-label"><?php echo stripslashes($item->inventory_code); ?></label></td>
                        <td><label for="inventory_name"><?php echo stripslashes($item->inventory_name); ?></label></td>
                        <td><label for="inventory_price"><?php echo "$" . number_format($item->inventory_price, 2); ?></label></td>                        <td><label for="category_name"><?php echo stripslashes($item->category_name); ?></label></td>
                   </tr>
                <?php }  ?>
        </table>
	</form>
    <?php
  	}
 	else
  	{
		?>
		<div class="centertext"><?php _e("There are no items matching your search",'inventory')  ?></div>
        <div class="centertext"><a href="<?=foxypress_GetCurrentPageURL()?>">Full Inventory List</a></div>
    <?
  }
}

function ShowSearch()
{
	?>
    <div class="centertext">
        <form method="POST" name="foxy_inventory_search_frm" id="foxy_inventory_search_frm">
            <span class="DialogHeading">Search Inventory:</span> <input type="text" id="foxy_search" name="foxy_search" /> <input type="submit" value="Go" name="foxy_search_button" id="foxy_search_button" />
        </form>
    </div><Br>
    <hr /><br />
    <?
}

function ShowInventory(){
	global $wpdb;

	//set up paging
	$limit = 10;
	$targetpage = foxypress_GetFullURL();
	$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
	$pos = strrpos($targetpage, "?");
	if ($pos === false) {
		$targetpage .= "?";
	}

	$drRows = $wpdb->get_row("SELECT count(i.inventory_id) as RowCount
								FROM " . WP_INVENTORY_TABLE . " as i
								INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
								INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
								LEFT JOIN
									(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
									from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
								ORDER BY i.inventory_code DESC");

	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;

	$items = $wpdb->get_results("SELECT i.*
										,c.category_name
										,im.inventory_images_id
										,im.inventory_image
								FROM " . WP_INVENTORY_TABLE . " as i
								INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . "
											GROUP BY inventory_id) as ic on i.inventory_id = ic.inventory_id
								INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
								LEFT JOIN
									(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
									from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
								ORDER BY i.inventory_code DESC
								LIMIT $start, $limit");

?>
<?php
  if ( !empty($items) ) {
    ?>
    <div style="width:100%; text-align:center;">
    	<span class="DialogHeading">Add Item From Inventory</span>
    </div>
    <form onsubmit="FoxyPressDialog.insert();return false;" id="foxypress-insert" action="#">
    	<input id="code" name="code" type="hidden" class="text" size="30" value="" />
        <table class="widefat page fixed" cellpadding="3" cellspacing="0" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
                <tr class="inventory-head-row">
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Select','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Image','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Code','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Name','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Price','inventory') ?></th>
                    <th class="manage-column inventory-heading" scope="col"><?php _e('Category','inventory') ?></th>
                <?php
                $class = '';
                foreach ( $items as $item ) {
                    $class = ($class == 'alternate') ? '' : 'alternate';
                ?>
                    <tr class="<?php echo $class; ?>">
                        <td><input type="button" name="select" value="Select" onclick="InsertItem('<?=stripslashes($item->inventory_id)?>');" /></td>
                        <td><img src="<?php echo INVENTORY_IMAGE_DIR . '/' . stripslashes($item->inventory_image); ?>" width="35px" /></td>
                        <td><label for="inventory_code" class="inventory-label"><?php echo stripslashes($item->inventory_code); ?></label></td>
                        <td><label for="inventory_name"><?php echo stripslashes($item->inventory_name); ?></label></td>
                        <td><label for="inventory_price"><?php echo "$" . number_format($item->inventory_price, 2); ?></label></td>                        <td><label for="category_name"><?php echo stripslashes($item->category_name); ?></label></td>
                   </tr>
                <?php }  ?>
        </table>
        <?
		//pagination
		if($drRows->RowCount > $limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
			echo ("<Br>" . $Pagination);
		}
		?>
	</form>
    <?php
  	}
 	else
  	{
		?>
		<div class="centertext"><?php _e("There are no inventory items in the database!",'inventory')  ?></div>
		<div class="centertext"><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=add&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'>Add New Item</a></div>
    <?
  }
}

?>
