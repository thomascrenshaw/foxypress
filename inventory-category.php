<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'foxypress_inventory_category_postback');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-sortable');


function foxypress_inventory_category_postback()
{
	global $wpdb;
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "inventory-category")
	{
		$mode = foxypress_FixGetVar('mode');
		if(isset($_POST['foxypress_new_category_save']))
		{
			$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_categories SET category_name='" . foxypress_FixPostVar('foxypress_new_category') . "'";
			$wpdb->query($sql);
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if(isset($_POST['foxy_cat_save'])) //updating
		{
			$Category_Name = foxypress_FixPostVar('foxy_cat_name');
			$Category_ID = foxypress_FixPostVar('foxy_cat_id');
			$sql = "UPDATE " . $wpdb->prefix . "foxypress_inventory_categories SET category_name='" . $Category_Name . "' WHERE category_id=" . $Category_ID;
			$wpdb->query($sql);
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if($mode == "delete" &&  foxypress_FixGetVar('category_id') != "") //deleting
		{
			$category_id = foxypress_FixGetVar('category_id');
			//before we delete, we need to default items to the general category unless they are already apart of that category
			$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_to_category (inventory_id, category_id)
					SELECT i.inventory_id, '1'
					FROM " . $wpdb->prefix . "posts as i
					INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as itc on i.ID = itc.inventory_id
							AND itc.category_id = '" . foxypress_FixGetVar('category_id') . "'
					LEFT JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ltc on i.ID = ltc.inventory_id and ltc.category_id = '1'
					WHERE ltc.itc_id is null
						AND i.post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "'";
			$wpdb->query($sql);

			//delete image associated
			$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
			$data = $wpdb->get_row("select category_image from " . $wpdb->prefix . "foxypress_inventory_categories where category_id = '" . mysql_escape_string($category_id) . "'");
			if (!empty($data))
			{
				foxypress_DeleteItem($directory . $data->category_image);
			}

			//delete from categories
			$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_categories WHERE category_id=" . $category_id;
			$wpdb->query($sql);

			//delete  from inventory to categories
			$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_to_category WHERE category_id=" . $category_id;
			$wpdb->query($sql);
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-category");
		}
		else if(isset($_POST['foxy_order_items_save']))
		{
			$categoryID = foxypress_FixGetVar('categoryid');
			$OrderArray = explode(",", foxypress_FixPostVar('hdn_foxy_items_order'));
			$counter = 1;
			foreach ($OrderArray as $itc_id)
			{
				$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_to_category SET sort_order = '$counter' WHERE itc_id='" . $itc_id . "'");
				$counter++;
			}
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category&view=sort&categoryid=" . $categoryID);
		}
		else if(isset($_POST['foxy_cat_image_submit']))
		{
			//attempt to upload the image
			$category_id = foxypress_FixPostVar('hdn_category_id');
			if(!empty($_FILES["fp_cat_image"]))
			{
				$image = isset($_FILES["fp_cat_image"]["name"]) ? $_FILES["fp_cat_image"]["name"] : "";
				if ($image)
				{
					$imgname = foxypress_UploadImage("fp_cat_image", $category_id);
				}
				//if the upload succeeded, insert into database
				if ($imgname)
				{
					$imgquery = "UPDATE " . $wpdb->prefix . "foxypress_inventory_categories SET category_image = '" . mysql_escape_string($imgname) . "' WHERE category_id = '" . $category_id . "'";
					$wpdb->query($imgquery);
				}
			}
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if($mode == "delete_image" && foxypress_FixGetVar('category_id') != "")
		{
			$category_id = foxypress_FixGetVar('category_id');
			$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
			$data = $wpdb->get_row("select category_image from " . $wpdb->prefix . "foxypress_inventory_categories where category_id = '" . mysql_escape_string($category_id) . "'");
			if (!empty($data))
			{
				foxypress_DeleteItem($directory . $data->category_image);
				$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_categories SET category_image = NULL where category_id = '" . mysql_escape_string($category_id) . "'");
			}
			header("location: " . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
	}
}

//page load
function foxypress_inventory_category_page_load()
{
	$page_view = foxypress_FixGetVar('view');
	if($page_view == "sort")
	{
		foxypress_inventory_category_sort();
	}
	else
	{
		foxypress_inventory_category_view_categories();
	}
}

//sort items in specific categories
function foxypress_inventory_category_sort()
{
	global $wpdb;
	$category_id =  foxypress_FixGetVar('categoryid');
?>
	 <script type="text/javascript">
		jQuery(document).ready(function() {
			//sorting
		  jQuery( "#foxypress_inventory_category_order tbody" ).sortable(
				{
					update: function(event, ui) { jQuery('#hdn_foxy_items_order').val(jQuery( "#foxypress_inventory_category_order tbody" ).sortable("toArray")); }
				}
			);
		});
	</script>
	<div class="wrap">
        <h2><?php _e('Sort Category Items','inventory'); ?></h2>
        <div>
        	<?php
				//get all inventory items for thie specific category
				$items = $wpdb->get_results("SELECT i.*, itc.itc_id
											FROM " . $wpdb->prefix . "posts as i
											INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category AS itc on i.ID = itc.inventory_id
											WHERE post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "'
												and post_status='publish'
												and itc.category_id = '" . $category_id . "'
											ORDER BY itc.sort_order");
				if(!empty($items))
				{
					echo("<table id=\"foxypress_inventory_category_order\" class=\"widefat page fixed\" width=\"50%\" cellpadding=\"3\" cellspacing=\"3\">
							<thead>
								<tr>
									<th class=\"manage-column\" scope=\"col\">Sort</th>
									<th class=\"manage-column\" scope=\"col\">Item Code</th>
									<th class=\"manage-column\" scope=\"col\">Item Name</th>
								</tr>
							</thead>
							<tbody>");
					foreach($items as $i)
					{
						$current_item_order .= ($current_item_order == "") ? $i->itc_id : "," . $i->itc_id;
						echo("<tr id=\"" . $i->itc_id . "\">
								<td style=\"cursor:pointer;\"><img src=\"" . plugins_url() . "/foxypress/img/sort.png\" style=\"padding-top:3px;\" /></td>
								<td>" .  get_post_meta($i->ID, "_code", true) . "&nbsp;&nbsp;</td>
								<td>" . $i->post_title . "</td>
							  </tr>");
					}
					echo("	</tbody>
						</table>");
					?>
                    <br />
					<form id="foxy_order_items" name="foxy_order_items" method="POST">
						<input type="submit" id="foxy_order_items_save" name="foxy_order_items_save" value="<?php _e('Update Order'); ?> &raquo;"  class="button bold" />
						<input type="hidden" id="hdn_foxy_items_order" name="hdn_foxy_items_order" value="<?php echo($current_item_order) ?>" />
					</form>
                    <?php
				}
				else
				{
					echo("There are currently no inventory items in this category.");
				}
			?>
        </div>
    </div>
<?php
}

//manage categories
function foxypress_inventory_category_view_categories()
{
	global $wpdb;
 ?>
    <div class="wrap">
        <h2><?php _e('Manage Categories','inventory'); ?></h2>
        <form name="foxypress_add_category" id="foxypress_add_category" class="wrap" method="post">
            <div id="linkadvanceddiv" class="postbox">
                <div style="float: left; width: 98%; clear: both;" class="inside">
                	<p>When creating your categories, please know that you need to match them with your FoxyCart.com categories. <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182&url=https://admin.foxycart.com/admin.php?ThisAction=ManageProductCategories" target="_blank">Click here</a> to view our FoxyCart.com category list. </p>
                    <table cellspacing="5" cellpadding="5">
                        <tr>
                            <td><legend><?php _e('New Category Name','inventory'); ?>:</legend></td>
                            <td><input type="text" name="foxypress_new_category" class="input" size="30" maxlength="30" value="" /></td>
                            <td><input type="submit" name="foxypress_new_category_save" id="foxypress_new_category_save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" /></td>
                        </tr>
                    </table>
                </div>
                <div style="clear:both; height:1px;">&nbsp;</div>
            </div>
        </form>

    <?php

	//set up paging
	$limit = 10;
	$targetpage = foxypress_GetCurrentPageURL();
	$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
	$pos = strrpos($targetpage, "?");
	if ($pos === false) {
		$targetpage .= "?";
	}
	$drRows = $wpdb->get_row("select count(category_id) as RowCount from " . $wpdb->prefix . "foxypress_inventory_categories");
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;

    $categories = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories ORDER BY category_id ASC LIMIT $start, $limit");
     if ( !empty($categories) )
     {
         ?>
         <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
           <thead>
             <tr>
               <th class="manage-column" scope="col"><?php _e('ID','inventory') ?></th>
               <th class="manage-column" scope="col"><?php _e('Category Name','inventory') ?></th>
               <th class="manage-column" scope="col"><?php _e('Category Image','inventory') ?></th>
               <th class="manage-column" scope="col"><?php _e('Sort','inventory') ?></th>
               <th class="manage-column" scope="col"><?php _e('Delete','inventory') ?></th>
             </tr>
           </thead>
           <?php
           		$class = '';
               	foreach ( $categories as $category ) {
                	$class = ($class == 'alternate') ? '' : 'alternate';
                 	 echo "<tr class=\"" .  $class . "\">
                  		   		<td scope=\"row\">" . $category->category_id . "</td>";
					  if ($category->category_id == 1)
					  {
							echo "<td>" . stripslashes($category->category_name) . "</td>";
					  }
					  else
					  { 	echo "<td>
									<form id=\"foxy_cat_edit\" name=\"foxy_cat_edit\" method=\"post\">
										<input type=\"text\" name=\"foxy_cat_name\" id=\"foxy_cat_name\" value=\"" . stripslashes($category->category_name) . "\" />
										<input type=\"hidden\" name=\"foxy_cat_id\" id=\"foxy_cat_id\" value=\"$category->category_id\" />
										<input type=\"submit\" id=\"foxy_cat_save\" name=\"foxy_cat_save\" value=\"save\" />
									</form>
								</td>";
					  }
					  $ImageOutput = "";
					  if($category->category_image != "")
					  {
						  $ImageOutput = "<div>
						  					<a href=" . INVENTORY_IMAGE_DIR . '/' . stripslashes($category->category_image) . " target=\"blank\">View Image</a> &nbsp;
											<a href=\"" . foxypress_GetCurrentPageURL(true) . "&mode=delete_image&category_id=" . $category->category_id . "\"><img src=\"" . plugins_url() . "/foxypress/img/delimg.png\" alt=\"\" /></a>
										  </div>";
					  }
					  else
					  {
						   $ImageOutput = "<form enctype=\"multipart/form-data\" method=\"post\" id=\"foxy_cat_image_form\" name=\"foxy_cat_image_form\">
						   					<input type=\"file\" name=\"fp_cat_image\" id=\"fp_cat_image\" />
											<input type=\"hidden\" name=\"hdn_category_id\" id=\"hdn_category_id\" value=\"" . $category->category_id . "\" />
											<input type=\"submit\" id=\"foxy_cat_image_submit\" name=\"foxy_cat_image_submit\" value=\"Save\">
										   </form>";
					  }
					  echo("<td>" . $ImageOutput . "</td>
					  		<td>
					  			<a href=\"" . foxypress_GetCurrentPageURL(true) . "&view=sort&categoryid=" . $category->category_id . "\">Sort Inventory Items</a>
							</td>");
					  if ($category->category_id == 1)
					  {
							echo "<td>" . __("N/A","inventory") . "</td>";
					  }
					  else
					  {
							echo "<td>
									<a href=\"" . foxypress_GetCurrentPageURL(true) . "&mode=delete&category_id=" . $category->category_id . "\" class=\"delete\" onclick=\"return confirm('" . __('Are you sure you want to delete this category?','inventory') . "');\">" .
						__("Delete","inventory") . "</a>
								  </td>";
					  }
					  echo "</tr>";
               }
            ?>
         </table>
        <?php
		if($drRows->RowCount > $limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
			echo ("<Br>" . $Pagination);
		}
    }
    else
    {
        echo '<p>There are currently no categories</p>';
    }
    echo '</div>'; //end wrap
}
?>