<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_init', 'foxypress_inventory_category_postback');

function foxypress_inventory_category_postback()
{
	global $wpdb;
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	
	$PageName = filter(foxypress_FixGetVar("page"));
	if($PageName == "inventory-category")
	{
		$mode = filter(foxypress_FixGetVar('mode'));
		if(isset($_POST['foxypress_new_category_save']))
		{
			// Get POST variables
			$new_cat_name = foxypress_FixPostVar('foxypress_new_category');
			$new_cat_parent = foxypress_FixPostVar('parent', 0);
			$new_cat_image = null;
			
			if(!empty($_FILES["fp_cat_image"]))
			{
				$image = isset($_FILES["fp_cat_image"]["name"]) ? $_FILES["fp_cat_image"]["name"] : "";
				if ($image)
				{
					$imgname = foxypress_UploadImage("fp_cat_image", foxypress_GenerateRandomString(5));
				}
				//if the upload succeeded, insert into database
				if ($imgname)
				{
					$new_cat_image = $imgname;
				}
			}
			
			$wpdb->insert( 
				$wpdb->prefix . 'foxypress_inventory_categories', 
				array( 
					'category_name' => $new_cat_name, 
					'category_parent_id' => $new_cat_parent,
					'category_image' => $new_cat_image
				), 
				array( 
					'%s',
					'%d',
					'%s'
				)
			);
			
			//header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category&message=1&error=" . $wpdb->print_error());
		}
		else if(isset($_POST['foxy_cat_save'])) //updating
		{
			$Category_Name = foxypress_FixPostVar('foxy_cat_name');
			$Category_ID = foxypress_FixPostVar('foxy_cat_id');
			$sql = "UPDATE " . $wpdb->prefix . "foxypress_inventory_categories SET category_name='" . $Category_Name . "' WHERE category_id='" . $Category_ID . "'";
			$wpdb->query($sql);
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if($mode == "delete" &&  filter(foxypress_FixGetVar('category_id')) != "") //deleting
		{
			$category_id = filter(foxypress_FixGetVar('category_id'));
			
			delete_category_item($category_id);
			
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-category&message=2");
		}
		else if(isset($_POST['foxy_order_items_save']))
		{
			$categoryID = filter(foxypress_FixGetVar('categoryid'));
			$OrderArray = explode(",", foxypress_FixPostVar('hdn_foxy_items_order'));
			$counter = 1;
			foreach ($OrderArray as $itc_id)
			{
				$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_to_category SET sort_order = '$counter' WHERE itc_id='" . $itc_id . "'");
				$counter++;
			}
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category&view=sort&categoryid=" . $categoryID);
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
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if($mode == "delete_image" && filter(foxypress_FixGetVar('category_id')) != "")
		{
			$category_id = filter(foxypress_FixGetVar('category_id'));
			delete_category_image( $category_id );
			
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-category");
		}
		else if (isset($_POST['foxypress_multi_submit'])) 
		{
			if ($_POST['action'] == "delete") {
				foxypress_delete_multiple_categories($_POST['multi_select']);
				header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-category&message=3");
			}
		}
		else if (isset($_POST['foxypress_multi_submit_2'])) 
		{
			if ($_POST['action2'] == "delete") {
				foxypress_delete_multiple_categories($_POST['multi_select']);
				header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-category&message=3");
			}
		}
		else if (isset($_POST['foxypress_category_edit_update']))
		{
			// Update clicked from category edit page
			$category_id = $_POST['category_id'];
			$category_name = $_POST['name'];
			$category_parent_id = $_POST['parent'];
			$category_image = null;
			if(!empty($_FILES["fp_cat_image"]))
			{
				$image = isset($_FILES["fp_cat_image"]["name"]) ? $_FILES["fp_cat_image"]["name"] : "";
				if ($image)
				{
					$category_image = foxypress_UploadImage("fp_cat_image", $category_id);
				}
			}
			
			$result = foxypress_update_category_info( $category_id, $category_name, $category_parent_id, $category_image );
			
			if ($result === false) {
				// SQL update unsuccessful
				$_REQUEST['message'] = 1;
			} else {
				// Return user back to Manage Categories page
				$message = "";
				if ($result > 0) {
					$message = "&message=4";
				}
				header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-category" . $message);
			}
		}
		else if (isset($_POST['foxypress_category_edit_remove_image']))
		{
			$category_id = $_POST['category_id'];
			
			$result = delete_category_image($category_id);
			if ($result) {
				$_REQUEST['message'] = 2;
			} else {
				$_REQUEST['message'] = 3;
			}
		}
	}
}

//page load
function foxypress_inventory_category_page_load()
{
	$page_view = filter(foxypress_FixGetVar('view'));
	if($page_view == "sort")
	{
		foxypress_inventory_category_sort();
	}
	else if ($page_view == "category_edit") 
	{
		foxypress_inventory_category_edit();
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
	$category_id =  filter(foxypress_FixGetVar('categoryid'));
?>
	 <script type="text/javascript">
		jQuery(document).ready(function() {
			//sorting
		  	jQuery( "#foxypress_inventory_category_order tbody" ).sortable({
				update: function(event, ui) { jQuery('#hdn_foxy_items_order').val(jQuery( "#foxypress_inventory_category_order tbody" ).sortable("toArray")); }
			});
		});
	</script>
	<div class="wrap">
		<?php screen_icon('foxypress'); ?>
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
									<th class=\"manage-column\" scope=\"col\" width=\"50px\">" . __('Sort', 'foxypress') . "</th>
									<th class=\"manage-column\" scope=\"col\" width=\"75px\">" . __('Item Image', 'foxypress') . "</th>
									<th class=\"manage-column\" scope=\"col\" width=\"100px\">" . __('Item Code', 'foxypress') . "</th>
									<th class=\"manage-column\" scope=\"col\">" . __('Item Name', 'foxypress') . "</th>
								</tr>
							</thead>
							<tbody>");
					$current_item_order="";
					foreach($items as $i)
					{
						$current_item_order .= ($current_item_order == "") ? $i->itc_id : "," . $i->itc_id;
						$featuredImageID = (has_post_thumbnail($i->ID) ? get_post_thumbnail_id($i->ID) : 0);
						$imageNumber = 0;
						$src = "";
						$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $i->ID, 'order' => 'ASC','orderby' => 'menu_order'));
						foreach ($attachments as $attachment) 
						{
							$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
							if ($featuredImageID == $attachment->ID || ($featuredImageID == 0 && $imageNumber == 0)) $src = $thumbnailSRC[0];
							$imageNumber++;
						}
						if (!$src) $src = INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;	
						echo("<tr id=\"" . $i->itc_id . "\">
								<td style=\"cursor:pointer;\"><img src=\"" . plugins_url() . "/foxypress/img/sort.png\" style=\"padding-top:3px;\" /></td>
								<td><img src='" . $src . "' style='max-height:32px; max-width:40px;' /></td>
								<td>" .  get_post_meta($i->ID, "_code", true) . "&nbsp;&nbsp;</td>
								<td>" . $i->post_title . "</td>
							  </tr>");
					}
					echo("	</tbody>
						</table>");
					?>
                    <br />
					<form id="foxy_order_items" name="foxy_order_items" method="POST">
						<input type="submit" id="foxy_order_items_save" name="foxy_order_items_save" value="<?php _e('Update Order', 'foxypress'); ?> &raquo;"  class="button bold" />
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

//edit single category
function foxypress_inventory_category_edit() {
	$messages[1] = __('Unable to update category.');
	$messages[2] = __('Category image removed.');
	$messages[3] = __('Unable to remove image.');
	
	$cat_id =  filter(foxypress_FixGetVar('categoryid'));
	
	global $wpdb;
	$category = $wpdb->get_row("SELECT * 
	                          FROM " . $wpdb->prefix . "foxypress_inventory_categories 
	                          WHERE category_id = $cat_id");
	
	$cat_name = $category->category_name;
	$cat_image = $category->category_image;
	$cat_parent = $category->category_parent_id;
	
	$categories = foxypress_get_product_categories_not($cat_id);
?>
<div class="wrap">
<?php screen_icon('foxypress'); ?>
<h2><?php _e('Edit Category', 'foxypress'); ?></h2>

<?php if ( isset($_REQUEST['message']) && ( $msg = (int) $_REQUEST['message'] ) ) : ?>
<div id="message" class="updated"><p><?php echo $messages[$msg]; ?></p></div>
<?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif; ?>

<form enctype="multipart/form-data" class="validate" method="post" id="editcat" name="editcat">
<input type="hidden" value="<?php echo $cat_id; ?>" name="category_id">
<table class="form-table">
<tbody>

<tr class="form-required">
	<th valign="top" scope="row"><label for="name"><?php _e('Name', 'foxypress'); ?></label></th>
	<td>
		<?php if ($cat_id == 1) :
			// Disable renaming for Default category ?>
		<input type="text" aria-required="true" size="30" value="<?php echo $cat_name; ?>" id="name" name="name" readonly="readonly" class="disabled">
		<p class="description"><?php _e('<strong>Note:</strong><br />Name cannot be changed for the category <strong>Default</strong>.', 'foxypress'); ?></p>
		<?php else : ?>
		<input type="text" aria-required="true" size="30" value="<?php echo $cat_name; ?>" id="name" name="name">
		<p class="description"><?php _e('This category name must match your FoxyCart.com category name.', 'foxypress'); ?> <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182&url=https://admin.foxycart.com/admin.php?ThisAction=ManageProductCategories" target="_blank"><?php _e('Click here', 'foxypress'); ?></a> <?php _e('to view your FoxyCart.com category list.', 'foxypress'); ?></p>
		<?php endif; ?>
	</td>
</tr>

<tr>
	<th valign="top" scope="row"><label for="parent"><?php _e("Parent", "foxypress"); ?></label></th>
	<td>
		<?php if ($cat_id == 1) :
			// Disable changing parent for Default category ?>
		<select class="postform" id="parent" name="parent" readonly="readonly">
		<option value="0"><?php _e("None"); ?></option>
		</select>
		<p class="description"><?php _e('<strong>Note:</strong><br />Parent cannot be changed for the category <strong>Default</strong>.', 'foxypress'); ?></p>
		
		<?php else : ?>
		<select class="postform" id="parent" name="parent">
		<option value="0"><?php _e("None"); ?></option>
		<?php 
			foreach ( $categories as $category ) : 
				$option_cat_id = $category->category_id;
				$option_cat_name = $category->category_name;
				
				if ($option_cat_id == $cat_parent) :
					// This option should be selected
		?>
		<option value="<?php echo $option_cat_id; ?>" selected="selected"><?php echo $option_cat_name; ?></option>
		<?php 
				elseif ($option_cat_id == $cat_id): 
					// Do not present category itself as parent option
				else:
					// Display category as parent option
		?>
		<option value="<?php echo $option_cat_id; ?>"><?php echo $option_cat_name; ?></option>
		<?php endif; ?>
		<?php endforeach; ?>
		</select>
		<p class="description"><?php _e("Product categories can have a hierarchy. You might have a Pants category, and under that have children categories for Jeans and Slacks. Children categories will show up on their own category pages, as well as their parents' category pages.", "foxypress"); ?></p>
		<?php endif; ?>
	</td>
</tr>

<tr>
	<th valign="top" scope="row"><label for="fp_cat_image"><?php _e("Category Image", "foxypress"); ?></label></th>
	<td>
		<?php 
			if (empty($cat_image)) :
				// Display file selection
		?>
		<input type="file" name="fp_cat_image" id="fp_cat_image" />
		<?php
			else :
				// Display a button to remove image
		?>
		<p><img src="<?php echo INVENTORY_IMAGE_DIR . "/$cat_image"; ?>" style="max-height: 200px; display: block;" alt="" /></p>
		<?php submit_button( __('Remove Image','foxypress'), 'secondary', 'foxypress_category_edit_remove_image' ); ?>
		<?php endif; ?>
		<p class="description"><?php _e('The category image, if added, can be displayed on the category page.', 'foxypress'); ?></p>
	</td>
</tr>

</tbody>
</table>
<?php submit_button( __('Update','foxypress'), 'primary', 'foxypress_category_edit_update' ); ?>
</form>
</div>
<?
}

//manage categories
function foxypress_inventory_category_view_categories()
{ 
	$messages[1] = __('Category added.');
	$messages[2] = __('Category deleted.');
	$messages[3] = __('Categories deleted.');
	$messages[4] = __('Category updated.');
	
	$categories = foxypress_get_product_categories();
	$category_count = count($categories);
?>
<div class="wrap nosubsub">
<?php screen_icon('foxypress'); ?>
<h2><?php _e('Manage Categories','inventory'); ?></h2>

<?php if ( isset($_REQUEST['message']) && ( $msg = (int) $_REQUEST['message'] ) ) : ?>
<div id="message" class="updated"><p><?php echo $messages[$msg]; ?></p></div>
<?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif; ?>

<div id="col-container">

<div id="col-right">
<div class="col-wrap">

<form name="foxypress_multi_action" id="foxypress_multi_action" method="post" class="validate">

<div class="tablenav top">
	<div class="alignleft actions">
		<select name="action">
			<option selected="selected" value="-1">Bulk Actions</option>
			<option value="delete">Delete</option>
		</select>
		<?php submit_button( __('Apply','foxypress'), 'secondary', 'foxypress_multi_submit', false ); ?>
	</div>
	<?php foxypress_categories_tablenav($category_count); ?>
	<br class="clear">
</div><!-- /tablenav top -->


<table cellspacing="0" class="wp-list-table widefat fixed tags">
	<thead>
		<?php print_categories_table_headers(); ?>
	</thead>
	<tfoot>
		<?php print_categories_table_headers(); ?>
	</tfoot>

	<tbody data-wp-lists="list:tag" id="the-list">
<?php 
	$alternate = true;
	foreach ( $categories as $category ) : 
		$cat_id = $category->category_id;
		$cat_name = $category->category_name;
		$cat_image = $category->category_image;
?>
		<tr <?php if ( $alternate ) : ?>class="alternate"<?php endif; $alternate = !$alternate; ?>>
			<th class="check-column" scope="row">
				<label for="cb-select-4" class="screen-reader-text">Select <?php echo $cat_name; ?></label>
				<?php 
					// Disable editing the default category
					if ( $cat_id != 1 ) : ?>
				<input type="checkbox" id="cb-select-<?php echo $cat_id; ?>" value="<?php echo $cat_id; ?>" name="multi_select[]">
				<?php endif; ?>
			</th>
			<td class="id column-posts">
				<a href="<?php foxypress_category_edit_link($category->category_id); ?>"><?php echo $cat_id; ?></a>
			</td>
			<td class="name column-name">
				<strong><a title="Edit “<?php echo $cat_name; ?>”" href="<?php foxypress_category_edit_link($category->category_id); ?>" class="row-title"><?php echo $cat_name; ?></a></strong>
				<br>
				<div class="row-actions">
				<?php 
					// Disable editing the default category
					if ( $cat_id == 1 ) : ?>
					<span class="edit"><a href="<?php foxypress_category_edit_link($category->category_id); ?>">Edit</a> | </span>
					<span class="sort"><a href="<?php echo get_admin_url() . "edit.php?post_type=foxypress_product&page=inventory-category&view=sort&categoryid=" . $category->category_id; ?>"><?php _e('Sort Inventory Items', 'foxypress'); ?></a></span>
				</div>
				<?php else : ?>
					<span class="edit"><a href="<?php foxypress_category_edit_link($category->category_id); ?>">Edit</a> | </span>
					<span class="delete"><a href="<?php echo get_admin_url() . "edit.php?post_type=foxypress_product&page=inventory-category&mode=delete&category_id=" . $category->category_id; ?>" class="delete" onclick="return confirm('<?php echo _e('Are you sure you want to delete this category?','foxypress'); ?>');"><?php _e("Delete","foxypress"); ?></a> | </span>
					<span class="sort"><a href="<?php echo get_admin_url() . "edit.php?post_type=foxypress_product&page=inventory-category&view=sort&categoryid=" . $category->category_id; ?>"><?php _e('Sort Inventory Items', 'foxypress'); ?></a></span>
				<?php endif; ?>
				</div>
			</td>
			<td class="image column-image">
				<?php if ( ! empty($cat_image) ) : ?>
				<img src="<?php echo INVENTORY_IMAGE_DIR . "/$cat_image"; ?>" style="height: 43px; display: block;" alt="" />
				<?php endif; ?>
			</td>
		</tr>	
<?php endforeach; ?>
	</tbody>
</table>

<div class="tablenav bottom">
	<div class="alignleft actions">
		<select name="action2">
			<option selected="selected" value="-1">Bulk Actions</option>
			<option value="delete">Delete</option>
		</select>
		<?php submit_button( __('Apply','foxypress'), 'secondary', 'foxypress_multi_submit_2', false ); ?>
	</div>
	<?php foxypress_categories_tablenav($category_count); ?>
	<br class="clear">
</div><!-- /tablenav bottom -->

<br class="clear" />
</form>

<div class="form-wrap">
<p><?php printf(__('<strong>Note:</strong><br />Deleting a category does not delete the products in that category. Instead, products that were only assigned to the deleted category are set to the category <strong>Default</strong>.'), 'foxypress'); ?></p>
</div>

</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">
<div class="form-wrap">
<h3><?php _e('Add New Product Category','foxypress'); ?></h3>
<form enctype="multipart/form-data" name="foxypress_add_category" id="foxypress_add_category" class="wrap" method="post">

<div class="form-field form-required">
	<label for="foxypress_new_category"><?php _ex('Name', 'foxypress'); ?></label>
	<input name="foxypress_new_category" id="foxypress_new_category" type="text" value="" size="30" aria-required="true" />
	<p><?php _e('This category name must match your FoxyCart.com category name.', 'foxypress'); ?> <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182&url=https://admin.foxycart.com/admin.php?ThisAction=ManageProductCategories" target="_blank"><?php _e('Click here', 'foxypress'); ?></a> <?php _e('to view your FoxyCart.com category list.', 'foxypress'); ?></p>
</div>

<div class="form-field">
<label for="parent"><?php _e("Parent", "foxypress"); ?> <em>(<?php _e("Optional", "foxypress"); ?>)</em></label>
<select class="postform" id="parent" name="parent">
<option value="0"><?php _e("None"); ?></option>
<?php 
	foreach ( $categories as $category ) : 
		$cat_id = $category->category_id;
		$cat_name = $category->category_name;
?>
<option value="<?php echo $cat_id; ?>"><?php echo $cat_name; ?></option>
<?php endforeach; ?>
</select>
<p><?php _e("Product categories can have a hierarchy. You might have a Pants category, and under that have children categories for Jeans and Slacks. Children categories will show up on their own category pages, as well as their parents' category pages.", "foxypress"); ?></p>
</div>

<div class="form-field">
<label for="fp_cat_image"><?php _e("Category Image"); ?> <em>(<?php _e("Optional", "foxypress"); ?>)</em></label>
<input type="file" name="fp_cat_image" id="fp_cat_image" />
<p><?php _e('The category image, if added, can be displayed on the category page.', 'foxypress'); ?></p>
</div>

<?php submit_button( __('Add New Category','foxypress'), 'primary', 'foxypress_new_category_save' ); ?>
</form>
</div>
</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
</div><!-- /wrap -->
<?php
}

/***************************************************************************************************/
/***************************************************************************************************/
/********************************** Category Helper Functions **************************************/
/***************************************************************************************************/
/***************************************************************************************************/

/**
 * Prints table header for the categories table
 *
 * @since 0.4.3.6
 */
function print_categories_table_headers() { ?>
<tr>
	<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><label for="cb-select-all-1" class="screen-reader-text">Select All</label><input type="checkbox" id="cb-select-all-1"></th>
	<th style="" class="manage-column column-posts num" id="id" scope="col"><span>ID</span></th>
	<th style="" class="manage-column column-name" id="name" scope="col"><span>Name</span></th>
	<th style="" class="manage-column column-image" id="image" scope="col"><span>Image</span></th>
</tr>
<?php
}

/**
 * Deletes multiple categories from an array of category IDs.
 *
 * @since 0.4.3.6
 * 
 * @param array $category_ids An integer array of category IDs to delete
 */
function foxypress_delete_multiple_categories( $category_ids ) {
	foreach ($category_ids as $category_id) {
		delete_category_item( $category_id );
	}
}

/**
 * Deletes the image for a category given its ID. Removes the image string from the inventory
 * category and deletes the image from the image directory.
 *
 * @since 0.4.3.6
 * 
 * @param int $category_id ID of category to delete image from
 */
function delete_category_image( $category_id ) {
	global $wpdb;
	
	$result = true;
	$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
	$data = $wpdb->get_row("select category_image from " . $wpdb->prefix . "foxypress_inventory_categories where category_id = '" . mysql_escape_string($category_id) . "'");
	if (!empty($data))
	{
		foxypress_DeleteItem($directory . $data->category_image);
		$result = $wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_categories SET category_image = NULL where category_id = '" . mysql_escape_string($category_id) . "'");
	}
	
	return $result;
}

/**
 * Removes a category from the database. All products assigned to this category will be
 * assigned to the default category. Any child categories will have parent IDs set to
 * the Default category.
 *
 * @since 0.4.3.6
 * 
 * @param int $category_id ID of category to remove
 */
function delete_category_item( $category_id ) {
	global $wpdb;
	
	//before we delete, we need to default items to the general category unless they are already apart of that category
	$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_to_category (inventory_id, category_id)
			SELECT itc.inventory_id, '1'
			FROM " . $wpdb->prefix . "posts as i
			INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as itc on i.ID = itc.inventory_id
					AND itc.category_id = '" . filter(foxypress_FixGetVar('category_id')) . "'
			LEFT JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ltc on i.ID = ltc.inventory_id and ltc.category_id = '1'
			WHERE ltc.itc_id is null
				AND i.post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "'";
	$wpdb->query($sql);
	
	//change parent_id of this category's children to 0
	$wpdb->update( 
		$wpdb->prefix . "foxypress_inventory_categories", 
		array( 'category_parent_id' => 0 ), // SET
		array( 'category_parent_id' => $category_id ), // WHERE
		array( '%d' ), // SET format (decimal)
		array( '%d' ) // WHERE format (decimal)
	);
	
	//delete this category's image
	delete_category_image($category_id);

	//delete from categories
	$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_categories WHERE category_id='" . $category_id . "'";
	$wpdb->query($sql);

	//delete  from inventory to categories
	$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_to_category WHERE category_id='" . $category_id . "'";
	$wpdb->query($sql);
}

/**
 * Displays the category count for placement above and below the categories table
 *
 * @since 0.4.3.6
 * 
 * @param int $category_count Number of categories to display in this tablenav
 */
function foxypress_categories_tablenav($category_count) { ?>
<div class="tablenav-pages one-page">
	<span class="displaying-num"><?php echo $category_count; ?> <?php _e('items', 'foxypress'); ?></span>
	<!--
	<span class="pagination-links">
		<a href="http://www.wpbetatest.com.php54-1.dfw1-2.websitetestlink.com/wp-admin/edit-tags.php?taxonomy=category" title="Go to the first page" class="first-page disabled">«</a>
		<a href="http://www.wpbetatest.com.php54-1.dfw1-2.websitetestlink.com/wp-admin/edit-tags.php?taxonomy=category&amp;paged=1" title="Go to the previous page" class="prev-page disabled">‹</a>
		<span class="paging-input"><input type="text" size="1" value="1" name="paged" title="Current page" class="current-page"> of <span class="total-pages">1</span></span>
		<a href="http://www.wpbetatest.com.php54-1.dfw1-2.websitetestlink.com/wp-admin/edit-tags.php?taxonomy=category&amp;paged=1" title="Go to the next page" class="next-page disabled">›</a>
		<a href="http://www.wpbetatest.com.php54-1.dfw1-2.websitetestlink.com/wp-admin/edit-tags.php?taxonomy=category&amp;paged=1" title="Go to the last page" class="last-page disabled">»</a>
	</span>
	-->
</div>
<?php }

/**
 * Creates the category edit link for a requested category and prints it to
 * the screen
 *
 * @since 0.4.3.6
 * 
 * @param int     $category_id ID of category to delete image from
 * @param boolean $return      When this parameter is set to TRUE, the link will
 *                             be returned rather than printedwill
 */
function foxypress_category_edit_link( $category_id, $return = false ) {
	$link = get_admin_url() . "edit.php?post_type=foxypress_product&page=inventory-category&view=category_edit&categoryid=" . $category_id;
	if ($return == true) {
		return $link;
	} else {
		echo $link;
	}
}

/**
 * Updates a category with supplied name, parent, and image.
 *
 * @since 0.4.3.6
 * 
 * @param int    $category_id        ID of category to update information
 * @param string $category_name      Updated name for category
 * @param int    $category_parent_id ID of updated parent category
 * @param string $category_image     Category image URL (relative to image directory)
 */
function foxypress_update_category_info( $category_id, $category_name, $category_parent_id, $category_image ) {
	global $wpdb;
	return $wpdb->update( 
		$wpdb->prefix . "foxypress_inventory_categories", 
		array( 
			'category_name' => $category_name,	// string
			'category_image' => $category_image,	// string
			'category_parent_id' => $category_parent_id // integer (number) 
		), 
		array( 'category_id' => $category_id ), 
		array( 
			'%s',
			'%s',
			'%d'
		), 
		array( '%d' ) 
	);
}
?>