<?php
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_menu', 'foxypress_inventory_category_menu');
add_action('admin_init', 'foxypress_inventory_category_postback');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-sortable');

function foxypress_inventory_category_menu()
{
	$allowed_group = 'manage_options';
 	add_submenu_page('foxypress', __('Inventory Categories','foxypress'), __('Manage Categories','foxypress'), $allowed_group, 'inventory-category', 'foxypress_inventory_category_page_load');
}

function foxypress_inventory_category_postback()
{
	global $wpdb;
	$mode = foxypress_FixGetVar('mode');
	if(isset($_POST['foxypress_new_category_save']))
	{
		$sql = "INSERT INTO " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_name='" . foxypress_FixPostVar('foxypress_new_category') . "'";
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-category");
	}
	else if(isset($_POST['foxy_cat_save'])) //updating
	{
		$Category_Name = foxypress_FixPostVar('foxy_cat_name');
		$Category_ID = foxypress_FixPostVar('foxy_cat_id');
		$sql = "UPDATE " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_name='" . $Category_Name . "' WHERE category_id=" . $Category_ID;
		$wpdb->query($sql);	
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-category");
	}
	else if($mode == "delete" &&  foxypress_FixGetVar('category_id') != "") //deleting
	{
		$category_id = foxypress_FixGetVar('category_id');
		//before we delete, we need to default items to the general category unless they are already apart of that category
		$sql = "INSERT INTO " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id) 
				SELECT i.inventory_id, '1' 
				FROM " . WP_INVENTORY_TABLE . " as i
				INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as itc on i.inventory_id = itc.inventory_id
						AND itc.category_id = '" . foxypress_FixGetVar('category_id') . "' 
				LEFT JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ltc on i.inventory_id = ltc.inventory_id and ltc.category_id = '1'
				WHERE ltc.itc_id is null";
		$wpdb->query($sql);
		
		//delete from categories
		$sql = "DELETE FROM " . WP_INVENTORY_CATEGORIES_TABLE . " WHERE category_id=" . $category_id;
		$wpdb->query($sql);
		
		//delete  from inventory to categories
		$sql = "DELETE FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " WHERE category_id=" . $category_id;
		$wpdb->query($sql);
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-category");
	}
	else if(isset($_POST['foxy_order_items_save']))
	{	
		$categoryID = foxypress_FixGetVar('categoryid');
		$OrderArray = explode(",", foxypress_FixPostVar('hdn_foxy_items_order'));
		$counter = 1;
		foreach ($OrderArray as $itc_id) 
		{
			$wpdb->query("UPDATE " . WP_INVENTORY_TO_CATEGORY_TABLE . " SET sort_order = '$counter' WHERE itc_id='" . $itc_id . "'");
			$counter++;
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-category&view=sort&categoryid=" . $categoryID);
	}
}

//page load
function foxypress_inventory_category_page_load() {
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
											FROM " . WP_INVENTORY_TABLE . " as i
											INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE. " AS itc on i.inventory_id = itc.inventory_id
											WHERE itc.category_id = '" . $category_id . "'
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
								<td>" . $i->inventory_code . "&nbsp;&nbsp;</td>
								<td>" . $i->inventory_name . "</td>
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
	$targetpage = foxypress_GetFullURL();
	$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
	$pos = strrpos($targetpage, "?");
	if ($pos === false) { 
		$targetpage .= "?";
	}	
	$drRows = $wpdb->get_row("select count(category_id) as RowCount from " . WP_INVENTORY_CATEGORIES_TABLE);
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;	
	
    $categories = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE . " ORDER BY category_id ASC LIMIT $start, $limit");
     if ( !empty($categories) ) 
     {
         ?>
         <style type="text/css">
		 	div.foxy_item_pagination a {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #AAAADD;	
				text-decoration: none; /* no underline */
				/*color: #000099;*/
			}
			div.foxy_item_pagination a:hover, div.foxy_item_pagination a:active {
				border: 1px solid #000099;
				color: #000;
			}
			div.foxy_item_pagination span.current {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #666666;	
				font-weight: bold;
				/*background-color: #000099;*/
				color: #666666;
			}
			div.foxy_item_pagination span.disabled {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #EEE;
				color: #ccc;
			}
		 </style>
         <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
           <thead> 
             <tr>
               <th class="manage-column" scope="col"><?php _e('ID','inventory') ?></th>
               <th class="manage-column" scope="col"><?php _e('Category Name','inventory') ?></th>
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
					  echo("<td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory-category&amp;view=sort&amp;categoryid=" . $category->category_id . "\">Sort Inventory Items</a></td>");
					  if ($category->category_id == 1)  
					  {
							echo "<td>" . __("N/A","inventory") . "</td>";
					  } 
					  else 
					  {
							echo "<td>
									<a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory-category&amp;mode=delete&amp;category_id=" . $category->category_id . "\" class=\"delete\" onclick=\"return confirm('" . __('Are you sure you want to delete this category?','inventory') . "');\">" . 
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