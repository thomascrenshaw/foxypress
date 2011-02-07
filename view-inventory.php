<?php
/** Load WordPress Administration Bootstrap */
require_once('../../../wp-admin/admin.php');


// Enable the ability for the inventory to be loaded from pages
add_filter('the_content','inventory_insert');

function wp_display_foxypress_inventory(){
  global $wpdb;
  $items = $wpdb->get_results("SELECT " . WP_INVENTORY_TABLE . ".*, " . WP_INVENTORY_CATEGORIES_TABLE . ".category_name, " . WP_INVENTORY_IMAGES_TABLE . ".*  
    FROM " . WP_INVENTORY_TABLE . ", " . WP_INVENTORY_CATEGORIES_TABLE . ", " . WP_INVENTORY_IMAGES_TABLE . " 
    WHERE " . WP_INVENTORY_TABLE .".inventory_id = " . WP_INVENTORY_IMAGES_TABLE . ".inventory_id AND " 
    . WP_INVENTORY_TABLE .".category_id = " . WP_INVENTORY_CATEGORIES_TABLE . ".category_id   
    ORDER BY inventory_code DESC");

?>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<div style="background-image:url(img/top.jpg);height:19px;"></div>
  <div id="item-inventory" style="height: 470px; overflow-y: scroll;">
<?php

  if ( !empty($items) ) {
    ?>
    <table class="widefat page fixed" cellpadding="3" cellspacing="3" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
          <tr class="inventory-head-row">
            <td class="manage-column inventory-heading" scope="col"><?php _e('Select','inventory') ?></th>
            <td class="manage-column inventory-heading" scope="col"><?php _e('Item Image','inventory') ?></th>
            <td class="manage-column inventory-heading" scope="col"><?php _e('Item Number','inventory') ?></th>
            <td class="manage-column inventory-heading" scope="col"><?php _e('Item Name','inventory') ?></th>
            <td class="manage-column inventory-heading" scope="col"><?php _e('Price','inventory') ?></th>
            <td class="manage-column inventory-heading" scope="col"><?php _e('Item Category','inventory') ?></th>
     <?php
    $class = '';
    foreach ( $items as $item ) {      
      $class = ($class == 'alternate') ? '' : 'alternate';
      ?>
      <tr class="<?php echo $class; ?>">
        <?php 
          echo '<form name="item' . stripslashes($item->inventory_code) . '" method="post" action="dialog.php">'
        ?>
            <td><input type="submit" name="select" value="Select" /></td>
            <td><img src="<?php echo INVENTORY_IMAGE_DIR . '/' . stripslashes($item->inventory_image); ?>" width="35px" /></td>
            <td><label for="inventory_code" class="inventory-label"><?php echo stripslashes($item->inventory_code); ?></label>
              <input type="hidden" name="inventory_code" value="<?php echo stripslashes($item->inventory_code); ?>" />
            </td>
            <td><label for="inventory_name"><?php echo stripslashes($item->inventory_name); ?></label>
              <input type="hidden" name="inventory_name" value="<?php echo stripslashes($item->inventory_name); ?>" /></td>
            <td><label for="inventory_price"><?php echo "$" . number_format($item->inventory_price, 2); ?></label>
              <input type="hidden" name="inventory_price" value="<?php echo "$" . number_format($item->inventory_price, 2); ?>" /></td>                        
            <td><label for="category_name"><?php echo stripslashes($item->category_name); ?></label>
              <input type="hidden" name="category_name" value="<?php echo stripslashes($item->category_name); ?>" />
                <input type="hidden" name="inventory_image" value="<?php echo INVENTORY_IMAGE_LOCAL_DIR . '/' . stripslashes($item->inventory_image); ?>" />
                <input type="hidden" name="inventory_quantity" value="<?php echo stripslashes($item->inventory_quantity); ?>" />
                <input type="hidden" name="inventory_weight" value="<?php echo stripslashes($item->inventory_weight); ?>" />
            </td>
                     
       </form>
       </tr>
      <?php
    }
    ?>
    </table>
    <?php
  }
  else
  {
    ?>
    <p><?php _e("There are no inventory items in the database!",'inventory')  ?></p>
    <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=add&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'>Add New Item</a></p>
    <?php 
  }
}

wp_display_foxypress_inventory();
?>    
  </div>
  <div style="width: 420px; margin-left: auto; margin-right: auto;" >
    <img src="img/footer.png" /><br />
      <p style="text-align:center;">Please visit our forum for info and help for all your needs.
      <br />
      <a href="http://www.webmovementllc.com/foxypress/forum" target="_blank">http://www.webmovementllc.com/foxypress/forum</a>
      </p>
  </div>
<div style="background-image:url(img/bottom.jpg);height:19px;"></div>