<?php
// Enable internationalisation
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);

// Define the tables used in Inventory
define('WP_INVENTORY_TABLE', $table_prefix . 'foxypress_inventory');
define('WP_INVENTORY_CATEGORIES_TABLE', $table_prefix . 'foxypress_inventory_categories');
define('WP_INVENTORY_IMAGES_TABLE', $table_prefix . 'foxypress_inventory_images');
define('INVENTORY_IMAGE_DIR', get_bloginfo("url") . "/wp-content/inventory_images");
define('INVENTORY_IMAGE_LOCAL_DIR', "/wp-content/inventory_images");
define('INVENTORY_SAVE_TO', "../wp-content/inventory_images");
define('INVENTORY_LOAD_FROM', "wp-content/inventory_images");

// Create a master category for Inventory and its sub-pages
add_action('admin_menu', 'inventory_menu');


// Function to deal with adding the inventory menus
function inventory_menu()  {
  global $wpdb;
  // We make use of the Inventory tables so we must have installed Inventory
  check_inventory();
  // Set admin as the only one who can use Inventory for security
  $allowed_group = 'manage_options';

  // Add the admin panel pages for Inventory. Use permissions pulled from above
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('foxypress', __('Inventory','foxypress'), __('Manage Inventory','foxypress'), $allowed_group, 'inventory', 'edit_inventory');
       // Note only admin can change inventory options
       add_submenu_page('foxypress', __('Inventory Categories','foxypress'), __('Manage Categories','foxypress'), $allowed_group, 'inventory-categories', 'manage_inventory_categories');
     }
}


// Function to check what version of Inventory is installed and install if needed
function check_inventory() {
  // Checks to make sure Inventory is installed, if not it adds the default
  // database tables and populates them with test data. If it is, then the 
  // version is checked through various means and if it is not up to date 
  // then it is upgraded.

  // Lets see if this is first run and create us a table if it is!
  global $wpdb;


  // Assume this is not a new install until we prove otherwise
  $new_install = false;

  $wp_inventory_exists = false;

  // Determine the inventory version
  $tables = $wpdb->get_results("show tables;");
  foreach ( $tables as $table ) {
      foreach ( $table as $value ) {
        if ( $value == WP_INVENTORY_TABLE ) {
            $wp_inventory_exists = true;
        }
      }
  }

  if ( $wp_inventory_exists == false ) {
      $new_install = true;
  }

  // Now we've determined what the current install is or isn't 
  // we perform operations according to the findings
  if ( $new_install == true) {
  
  // CREATE DATABASE TABLES TO HOLD INVENTORY/ITEM INFORMATION \\  
      $sql = "CREATE TABLE " . WP_INVENTORY_TABLE . " (
                inventory_id INT(11) NOT NULL AUTO_INCREMENT ,
                date_added INT(11) NOT NULL ,
                inventory_code VARCHAR(30) NOT NULL,
                inventory_name VARCHAR(100) NOT NULL,
                inventory_description TEXT NOT NULL,
                inventory_order INT(11) NOT NULL,
                inventory_weight VARCHAR(30) NULL,
                inventory_quantity INT(11) DEFAULT 0,
                category_id INT(11) NOT NULL,
                inventory_price FLOAT(10, 2) NOT NULL,
                inventory_image TEXT NULL,
                PRIMARY KEY (inventory_id)
                )";
      $wpdb->get_results($sql);
     
      $sql = "CREATE TABLE " . WP_INVENTORY_CATEGORIES_TABLE . " ( 
              category_id INT(11) NOT NULL AUTO_INCREMENT, 
              category_name VARCHAR(30) NOT NULL , 
              PRIMARY KEY (category_id) 
           )";
      $wpdb->query($sql);
      $sql = "INSERT INTO " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_id=1, category_name='General'";
      $wpdb->query($sql);
      
      $sql = "CREATE TABLE " . WP_INVENTORY_IMAGES_TABLE . " (
             inventory_images_id INT(11) NOT NULL AUTO_INCREMENT ,
             inventory_id INT(11) NOT NULL ,
             inventory_image TEXT NULL,
             PRIMARY KEY (inventory_images_id)
             )";
      $wpdb->query($sql);
  // COPY DEFAULT PRODUCT IMAGE TO IMAGE DIRECOTRY \\
    $defaultImage = '../wp-content/plugins/foxypress/img/default-product-image.jpg';
    $defaultImgMove = INVENTORY_SAVE_TO . '/default-product-image.jpg';
    echo $defaultImage;
    if ( file_exists( $defaultImage ) ) {            
      if ( copy ( $defaultImage, $defaultImgMove ) ) {
      } else {
        echo 'could not copy default product image';
      }
    }
  else{ echo 'files does not exist at plugin directory';}
  }
}

// Used on the manage events admin page to display a list of events
function wp_inventory_display_list() {
  global $wpdb;
  global $current_user;
    get_currentuserinfo();
  $cur_user = $current_user->ID;
  $cur_user_admin = ( current_user_can('manage_options')) ? 1 : 0;
  
  $items = $wpdb->get_results("SELECT " . WP_INVENTORY_TABLE . ".*, " . WP_INVENTORY_CATEGORIES_TABLE . ".category_name, " . WP_INVENTORY_IMAGES_TABLE . ".*  
    FROM " . WP_INVENTORY_TABLE . ", " . WP_INVENTORY_CATEGORIES_TABLE . ", " . WP_INVENTORY_IMAGES_TABLE . " 
    WHERE " . WP_INVENTORY_TABLE .".inventory_id = " . WP_INVENTORY_IMAGES_TABLE . ".inventory_id AND " 
    . WP_INVENTORY_TABLE .".category_id = " . WP_INVENTORY_CATEGORIES_TABLE . ".category_id   
    ORDER BY inventory_order DESC");
    
  if ( !empty($items) ) {
    ?>
    <table class="widefat page fixed" cellpadding="3" cellspacing="3" style="clear: both; width: 100%; margin-bottom: 15px;">
            <thead>
          <tr>
            <th class="manage-column" scope="col"><?php _e('ID','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e( 'Item Code','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e( 'Item Name','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e('Description','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e('Date Added','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e('Price','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e( 'Category','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e( 'Image','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e('Edit','inventory') ?></th>
            <th class="manage-column" scope="col"><?php _e('Delete','inventory') ?></th>
          </tr>
            </thead>
        <tr><td colspan="10"><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=add&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'>Add New Item</a></td></tr>
    <?php
    $class = '';
    foreach ( $items as $item ) {
      $class = ($class == 'alternate') ? '' : 'alternate';
      ?>
      <tr class="<?php echo $class; ?>">
        <th scope="row"><?php echo stripslashes( $item->inventory_id ); ?></th>
        <td><?php echo stripslashes( $item->inventory_code ); ?></td>
        <td><?php echo stripslashes($item->inventory_name); ?></td>
        <td><?php echo stripslashes($item->inventory_description); ?></td>        
        <td><?php echo date("m/d/Y", $item->date_added); ?></td>
        <td><?php echo "$" . number_format($item->inventory_price, 2); ?></td>
        <td><?php echo stripslashes($item->category_name); ?></td>
        <td><img src="<?php echo INVENTORY_IMAGE_DIR . '/' . stripslashes($item->inventory_image); ?>" width="25px" /></td>
        <?php if ($cur_user == $item->inventory_userid || $item->inventory_userid==0 || $cur_user_admin || !$limit_edit) { ?>
        <td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=edit&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'><?php echo __('Edit','inventory'); ?></a></td>
        <td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=delete&amp;inventory_id=<?php echo $item->inventory_id;?>" class="delete" onclick="return confirm('<?php _e('Are you sure you want to delete this item?','inventory'); ?>')"><?php echo __('Delete','inventory'); ?></a></td>
        <?php } else {
          echo "<td>&nbsp;</td><td>&nbsp;</td>";
        } ?>
      </tr>
      <?php
    }
    ?>
    </table>
    <?php
  }
  else {
    ?>
    <p><?php _e("There are no inventory items in the database!",'inventory')  ?></p>
    <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=add&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'>Add New Item</a></p>
    <?php 
  }
}

// Function to handle the management of categories
function manage_inventory_categories() {
  global $wpdb;

  // Inventory must be installed and upgraded before this will work
  check_inventory();

  // We do some checking to see what we're doing
  if (isset($_POST['mode']) && $_POST['mode'] == 'add')    {
      $sql = "INSERT INTO " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_name='".mysql_escape_string($_POST['category_name'])."'";
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category added successfully','inventory')."</strong></p></div>";
    }
  else if ( isset( $_GET['mode'] ) && isset( $_GET['category_id'] ) && $_GET['mode'] == 'delete') {
      $sql = "DELETE FROM " . WP_INVENTORY_CATEGORIES_TABLE . " WHERE category_id=".mysql_escape_string( $_GET['category_id'] );
      $wpdb->get_results($sql);
      $sql = "UPDATE " . WP_INVENTORY_TABLE . " SET category_id=1 WHERE category_id=".mysql_escape_string($_GET['category_id']);
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category deleted successfully','inventory')."</strong></p></div>";
    }
  else if ( isset( $_GET['mode'] ) && isset( $_GET['category_id'] ) && $_GET['mode'] == 'edit' && !isset( $_POST['mode'] ) ) {
      $sql = "SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE . " WHERE category_id=".mysql_escape_string( $_GET['category_id'] );
      $cur_cat = $wpdb->get_row($sql);
      ?>
<div class="wrap">
   <h2><?php _e('Edit Category','inventory'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=inventory-categories">
      <input type="hidden" name="mode" value="edit" />
      <input type="hidden" name="category_id" value="<?php echo $cur_cat->category_id ?>" />
      <div id="linkadvanceddiv" class="postbox">
        <div style="float: left; width: 98%; clear: both;" class="inside">
          <table cellpadding="5" cellspacing="5">
            <tr>
              <td><legend><?php _e('Category Name','inventory'); ?>:</legend></td>
              <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="<?php echo $cur_cat->category_name ?>" /></td>
            </tr>
          </table>
        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>
      </div>
    <input type="submit" name="save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" />
    </form>
</div>
<?php
    }
    else if ( isset( $_POST['mode'] ) && isset( $_POST['category_id'] ) && isset( $_POST['category_name'] ) && $_POST['mode'] == 'edit' )
    {
      $sql = "UPDATE " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_name='".mysql_escape_string($_POST['category_name'])."' WHERE category_id=".mysql_escape_string($_POST['category_id']);
      $wpdb->get_results($sql);
      echo "<div class=\"updated\"><p><strong>".__('Category edited successfully','inventory')."</strong></p></div>";
    }

  if ($_GET['mode'] != 'edit' || $_POST['mode'] == 'edit')  {
?>
  <div class="wrap">
    <h2><?php _e('Add Category','inventory'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=inventory-categories">
      <input type="hidden" name="mode" value="add" />
      <input type="hidden" name="category_id" value="">
      <div id="linkadvanceddiv" class="postbox">
        <div style="float: left; width: 98%; clear: both;" class="inside">
          <table cellspacing="5" cellpadding="5">
            <tr>
              <td><legend><?php _e('Category Name','inventory'); ?>:</legend></td>
              <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="" /></td>
            </tr>
          </table>
        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>
      </div>
      <input type="submit" name="save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" />
    </form>
    <h2><?php _e('Manage Categories','inventory'); ?></h2>
<?php
    
    // We pull the categories from the database 
    $categories = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE . " ORDER BY category_id ASC");

 if ( !empty($categories) ) {
     ?>
     <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
       <thead> 
         <tr>
           <th class="manage-column" scope="col"><?php _e('ID','inventory') ?></th>
           <th class="manage-column" scope="col"><?php _e('Category Name','inventory') ?></th>
           <th class="manage-column" scope="col"><?php _e('Edit','inventory') ?></th>
           <th class="manage-column" scope="col"><?php _e('Delete','inventory') ?></th>
         </tr>
       </thead>
        <?php
           $class = '';
           foreach ( $categories as $category ) {
              $class = ($class == 'alternate') ? '' : 'alternate';
              echo '<tr class="' .  $class . '">
              <th scope="row">' . $category->category_id . '</th>
              <td>' . $category->category_name . '</td>      
              <td><a href="' . $_SERVER["PHP_SELF"] . '?page=inventory-categories&amp;mode=edit&amp;category_id=' . $category->category_id .'" class="edit">' . __('Edit','inventory') . '</a></td>';
              if ($category->category_id == 1)  {
                echo '<td>' . __('N/A','inventory') . '</td>';
              } 
              else {
                echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?page=inventory-categories&amp;mode=delete&amp;category_id=' . $category->category_id . 
                '" class="delete" onclick="return confirm(\'' . __('Are you sure you want to delete this category?','inventory') . '\');">' . 
                __('Delete','inventory') . '</a></td>';
              }
              echo '</tr>';
           }
        ?>
     </table>
    <?php
   } else {
     echo '<p>'.__('There are no categories in the database - something has gone wrong!','inventory').'</p>';
   }
   echo '</div>';
      } 
}

// The actual function called to render the manage inventory page and 
// to deal with posts
function edit_inventory() {
    global $current_user, $wpdb, $users_entries;


// First some quick cleaning up 
$edit = $create = $save = $delete = false;

$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
$inventory_id = !empty($_REQUEST['inventory_id']) ? $_REQUEST['inventory_id'] : '';
$inventory_prev_userid = !empty($_REQUEST['inventory_prev_userid']) ? $_REQUEST['inventory_prev_userid'] : '';


// Lets see if this is first run and create us a table if it is!
check_inventory();

  // Check if it's the current user's item
  if ( $inventory_id ) {
      $query = "SELECT inventory_userid FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id=" . mysql_escape_string($inventory_id);
      $data = $wpdb->get_results($query);
      $data = $data[0];
  }

  // First, let's check the delete image action and perform it
  if ($action=="delimage") {
    $image_id = !empty($_REQUEST['image_id']) ? $_REQUEST['image_id'] : '';
  
    if ($image_id) {
      $directory = INVENTORY_SAVE_TO;
      $directory .= ($directory!="") ? "/" : "";
      $query = sprintf('SELECT inventory_image FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);
      $data = $wpdb->get_results($query);
      if (empty($data)) {
        echo "<div class=\"error\"><p>".__("An image with that ID couldn't be found",'inventory')."</p></div>";
        return;
      }
      $data = $data[0];
      inv_deleteFile($directory . $data->inventory_image);
      $query = sprintf('DELETE FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);
      
      $wpdb->query($query);
    }
    $action="edit";
    
  }

  // Deal with adding an item to the database
  if ( $action == 'add' || $action=='edit_save') {
    $save = (isset($_REQUEST["inventory_name"])) ? 1 : 0;
    $code = !empty($_REQUEST['inventory_code']) ? $_REQUEST['inventory_code'] : '';
    $name = !empty($_REQUEST['inventory_name']) ? $_REQUEST['inventory_name'] : '';
    $desc = !empty($_REQUEST['inventory_description']) ? $_REQUEST['inventory_description'] : '';
    $order = !empty($_REQUEST['inventory_order']) ? $_REQUEST['inventory_order'] : '';
    $cat = !empty($_REQUEST['category_id']) ? $_REQUEST['category_id'] : '';
    $price = !empty($_REQUEST['inventory_price']) ? $_REQUEST['inventory_price'] : '';
    $weight = !empty($_REQUEST['inventory_weight']) ? $_REQUEST['inventory_weight'] : '';
    $added = !empty($_REQUEST['date_added']) ? $_REQUEST['date_added'] : '';
    
    $quantity = !empty($_REQUEST['inventory_quantity']) ? $_REQUEST['inventory_quantity'] : '';
   
    $image = isset($_FILES["inv_image"]["name"]) ? $_FILES["inv_image"]["name"] : "";
    if ($image) {
      $imgname = inv_doImages("inv_image");
      if (!$imgname) {
        $strerror = "Image could NOT be uploaded.<br>";
      } else {
        $strerror = "";
      }
    }
    if (!$image){
      $imgname = 'default-product-image.jpg';
    }
    $order= ($order * 1);
    $order = (!$order) ? 0 : $order;
    $price = ($price * 1);
    $price = (!$price) ? 0 : $price;
    $added = strtotime($added);
    if (!$added) {$added = time();}
  
    if ( ini_get('magic_quotes_gpc') ) {
      $code = stripslashes($code);
      $name = stripslashes($name);
      $desc = stripslashes($desc);
      $order = stripslashes($order);
      $cat = stripslashes($cat);
      $price = stripslashes($price);
      $added = stripslashes($added);
      $weight = stripslashes($weight);      
      $quantity = stripslashes($quantity);

    } 
    
    // The name must be at least one character in length and no more than 100 - no non-standard characters allowed
    if ($save) {
      if (strlen(trim($name))>1 && strlen(trim($name))<100) {
          $title_ok = 1;
        } else { ?>
                  <div class="error"><p><strong><?php _e('Error','inventory'); ?>:</strong> <?php _e('The item name must be between 1 and 100 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one.','inventory'); ?></p></div>
                  <?php
        }
    }
    if ($save && $title_ok == 1) {
       $wpdb->show_errors();
       $cur_user = wp_get_current_user();
      $cur_user = $cur_user->ID;
      if ($action=="add") {
          $sql = "INSERT INTO " . WP_INVENTORY_TABLE . " SET inventory_code='" . mysql_escape_string($code)
        . "', inventory_name='" . mysql_escape_string($name)
        . "', inventory_description='" . mysql_escape_string($desc)
        . "', category_id='" . mysql_escape_string($cat) 
        . "', inventory_order='" . mysql_escape_string($order)
        . "', inventory_weight='" . mysql_escape_string($weight)
        . "', inventory_price='" . mysql_escape_string($price)
        . "', inventory_quantity='" . ($quantity*1) . "'"
        ;
        $wpdb->query($sql);
        $inventory_id = $wpdb->insert_id;
        $inv_msg = "Added";
      } elseif ($action="edit_save") {
        // Save the changes
        $sql = "UPDATE " . WP_INVENTORY_TABLE . " SET inventory_code='" . mysql_escape_string($code)
        . "', inventory_name='" . mysql_escape_string($name)
        . "', inventory_description='" . mysql_escape_string($desc)
        . "', category_id='" . mysql_escape_string($cat) 
        . "', inventory_order='" . mysql_escape_string($order)
        . "', inventory_weight='" . mysql_escape_string($weight)
        . "', inventory_price='" . mysql_escape_string($price) 
        . "', inventory_quantity='" . ($quantity*1)
        . $inv_user_update
        . "' WHERE inventory_id='" . mysql_escape_string($inventory_id) . "'";
          $wpdb->query($sql);
        $inv_msg = "Updated";
      }
    
        if (!$inventory_id) {
            echo '<div class="error">
          <p><strong>' .  _e('Error','inventory') . ':</strong>' .
          _e('An item with the details you submitted could not be found in the database. This may indicate a problem with your database or the way in which it is configured.','inventory') 
          . '</p></div>';
          } else {
            if ($imgname) {
          $imgquery = 'INSERT INTO ' . WP_INVENTORY_IMAGES_TABLE . ' SET inventory_id=' . $inventory_id . ', inventory_image="' . mysql_escape_string($imgname) . '"';
          $wpdb->query($imgquery);
       }
       echo '
        <div class="updated"><p>' .  __($strerror . 'Inventory item ' . $inv_msg . '. It will now show in your inventory. <a href="' . $_SERVER["PHP_SELF"] . '?page=inventory">View Inventory</a>','inventory')
         . '</p></div>';
          }
      } else {
        // The form is going to be rejected due to field validation issues, so we preserve the users entries here
        $users_entries->inventory_code = $code;
        $users_entries->inventory_name = $name;
        $users_entries->inventory_description = $desc;
        $users_entries->date_added = $added;
        $users_entries->inventory_price = $price;
        $users_entries->inventory_order = $order;
        $users_entries->inventory_category = $cat;      
        $users_entries->inventory_quantity = $quantity;
      }
  }
  // Deal with deleting an item from the database
  elseif ($action == 'delete') {
    if (empty($inventory_id)) {
      ?>
      <div class="error"><p><strong><?php _e('Error','inventory'); ?>:</strong> <?php _e("You can't delete an item if you haven't submitted an event id",'inventory'); ?></p></div>
      <?php     
    } else {
      $sql = "DELETE FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id='" . mysql_escape_string($inventory_id) . "'";
      $wpdb->get_results($sql);
      
      $sql = "SELECT inventory_id FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id='" . mysql_escape_string($inventory_id) . "'";
      $result = $wpdb->get_results($sql);
      
      if ( empty($result) || empty($result[0]->inventory_id) )
      {
        ?>
        <div class="updated"><p><?php _e('Item deleted successfully','inventory'); ?></p></div>
        <?php
      } else {
      ?>
        <div class="error"><p><strong><?php _e('Error','inventory'); ?>:</strong> <?php _e('Despite issuing a request to delete, the item still remains in the database. Please investigate.','inventory'); ?></p></div>
        <?php
      }   
    }
  }
?>

<div class="wrap">
  <?php
  if ($action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1))
  {
    ?>
    <h2><?php _e('Edit Inventory Item','inventory'); ?></h2>
    <?php
    if ( empty($inventory_id) ) {
      echo "<div class=\"error\"><p>".__("You must provide an item id in order to edit it",'inventory')."</p></div>";
    } else {
      wp_inventory_edit_form('edit_save', $inventory_id);
    } 
  } elseif ($action=='add') { ?>
     <h2><?php _e('Add Inventory Item','inventory'); ?></h2>
    <?php wp_inventory_edit_form();
  } else {
    ?>
    
    <h2><?php _e('Manage Inventory','inventory'); ?></h2>
    <?php
      wp_inventory_display_list();
  }
  ?>
</div>

<?php
 
}

// The event edit form for the manage events admin page
function wp_inventory_edit_form($mode='add', $inventory_id=false) {
  global $wpdb,$users_entries;

  $data = false;
  
  if ($inventory_id !== false) {
    if ( intval( $inventory_id ) != $inventory_id ) {
      echo "<div class=\"error\"><p>".__('No inventory id set.','inventory')."</p></div>";
      return;
    } else {
      $data = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id='" . mysql_escape_string($inventory_id) . "' LIMIT 1");
      if ( empty( $data ) ) {
        echo "<div class=\"error\"><p>".__("An item with that ID couldn't be found",'inventory')."</p></div>";
        return;
      }
      $data = $data[0];
    }
    // Check if it's the current user's item    
    $cur_user = wp_get_current_user();
    $cur_user = $cur_user->ID;
    if (!($data->inventory_userid==$cur_user || $data->inventory_userid==0)) {
      echo "<div class=\"error\"><p>".__("Not authorized to edit this item.",'inventory')."</p></div>";
        return;
    }
        
    // Recover users entries if they exist; in other words if editing an event went wrong
    if (!empty($users_entries)) {
        $data = $users_entries;
    }
  }
  // Deal with possibility that form was submitted but not saved due to error - recover user's entries here
  else {
      $data = $users_entries;
  }
  ?>
  <style type="text/css">
  #inventory-help a,#inventory-help a:visited { position: relative; display:block; width:100px; margin:0; text-decoration: none; }
  #inventory-help a span { display: none; }  
  #inventory-help a span img { border: 1px solid black; float:right; margin-left:10px; margin-bottom:5px; }  
  #inventory-help a:hover span { z-index: 25; display: block; position:absolute; min-height:15px; width:240px; color: black; font:14px ; margin-top: 5px; padding: 10px; background-color: #ffff88; border: 1px solid black; }  
  #inventory-help a:hover span { width:240px;margin-left: 25px;} 
  #inventory-help a:hover {text-indent: 0;}
  
  .inventory-title { width: 75px; }
  </style>
  <form enctype="multipart/form-data" name="quoteform" id="quoteform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=inventory">
    <input type="hidden" name="action" value="<?php echo $mode; ?>">
    <input type="hidden" name="inventory_id" value="<?php echo $inventory_id; ?>">
    <input type="hidden" name="inventory_prev_userid" value="<?php echo $data->inventory_userid; ?>">
  
    <div id="linkadvanceddiv" class="postbox">
      <div style="float: left; width: 98%; clear: both;" class="inside">
          <table cellpadding="5" cellspacing="5">
            <tr>        
              <table>
                <tr>
                  <td class="inventory-title"><legend><?php _e( 'Item Code' , 'inventory'); ?></legend></td>
                  <td>
                      <input style="float:left;"type="text" name="inventory_code" class="input" size="20" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_code); ?>" />
                  </td>
                  <td>
                    <div id="inventory-help">
                      <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                      <span>Item Code: i.e item sku, or number</span></a>
                    </div>              
                  </td>
                 </tr>
              </table>
            </tr>
            <tr>
              <table>
                <tr>    
                  <td class="inventory-title"><legend><?php _e( 'Item Name','inventory' ); ?></legend></td>
                  <td>
                    
                      <input type="text" name="inventory_name" class="input" size="60" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_name); ?>" />                      
                  </td>
                  <td>
                    <div id="inventory-help">
                      <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                      <span>Name of the item or product</span></a>
                    </div>
                  </td>
                </tr>

            <?php 
              if (!empty($data) && $data->inventory_id) {
                $ires = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_IMAGES_TABLE . " WHERE inventory_id=" . $data->inventory_id);
                foreach ($ires as $imagedata) {
                    echo '<tr><tdclass="inventory-title"><legend>Existing Image</legend>';
                    echo '<br><a href="' . $_SERVER['PHP_SELF'] . '?page=inventory&action=delimage&image_id=' . $imagedata->inventory_images_id . '&inventory_id=' . $imagedata->inventory_id . '">(Remove)</a>';
                    echo '</td><td><img style="max-width: 300px;"  src="' . INVENTORY_IMAGE_DIR . "/" . $imagedata->inventory_image . '">';
                } 
              }
            ?>

            <tr><?php /* IMAGE UPLOAD */ ?>
              <table>
                <tr>
                  <td class="inventory-title"><legend><?php _e('Add Image','inventory'); ?></legend></td>
                  <td>                  
                      <input type="file" name="inv_image"  class="input" size="60">                      
                  </td>
                  <td>
                     <div id="inventory-help">
                      <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                      <span>Upload a thumbnail image to associate to the product.  This will display in foxycart and the inventory.</span></a>
                    </div>
                  </td>
                </tr>
              </table>                  
            </tr>
            <tr>
            <table>
                <tr>
                  <td class="inventory-title" style="vertical-align:top;"><legend><?php _e('Item Description','inventory'); ?></legend></td>
                  <td>                
                      <textarea name="inventory_description" class="input" rows="5" cols="50"><?php if ( !empty($data) ) echo stripslashes($data->inventory_description); ?></textarea>                  
                  </td>              
                  <td>
                    <div id="inventory-help">
                      <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                      <span>Enter a description of your product or item.</span></a>
                    </div>
                  </td>
              </tr>
              </table>
            </tr>
            <tr>
            <table>
              <tr>
              <td class="inventory-title">Item Category</td>
              <td>                
                  <select name="category_id">
                      <?php
                       // Grab all the categories and list them
                       $cats = $wpdb->get_results( "SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE );
                       foreach( $cats as $cat ) {
                          echo '<option value="' . $cat->category_id . '"';
                          if ( !empty( $data ) ) {
                             if ( $data->category_id == $cat->category_id ) {
                              echo ' selected="SELECTED"';
                            }
                          }
                          echo '>'.$cat->category_name.'</option>';
                       }
                       echo "</select>";
                       ?>                                       
              </td>
              <td>
                <div id="inventory-help">
                    <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                    <span>Select the item category.  The categories must first be created via foxycart.com, then added to your inventory category list.</span></a>
                </div>  
              </td>
            </table>
            </tr>            
            <tr>
            <table>
            <tr>
              <td class="inventory-title"><legend><?php _e('Added Date','inventory'); ?></legend></td>
              <td>               
                  <input type="text" name="date_added" class="input" size="12" value="<?php 
                    if ( !empty($data))  {
                      echo htmlspecialchars(date("m/d/Y", $data->date_added));
                    } 
                    else {
                      echo date("m/d/Y");
                    } ?>" />                                
              </td>
              <td>
               <div id="inventory-help">
                <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                  <span>Enter the date you want the item submission logged by.</span></a>
                </div>  
              </td>              
            </tr>
            </table>
            </tr>
            <tr> 
              <table>
              <tr>       
              <td class="inventory-title"><legend><?php _e('Item Price','inventory'); ?></legend></td>
              <td>                
                  $<input type="text" name="inventory_price" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_price); ?>" />                  
              </td>
              <td>
                <div id="inventory-help">
                  <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                  <span>Enter the price of the item in US Dollars.</span></a>
                </div>
              </td>
            </tr>  
            </table>
            </tr>          
            <tr> 
            <table>
            <tr>       
              <td class="inventory-title"><legend><?php _e( 'Item Weight','inventory' ); ?></legend></td>
              <td>               
                  <input type="text" name="inventory_weight" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_weight); ?>" />lbs                   
              </td>
              <td>
              <div id="inventory-help">
                <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                  <span>Enter the product weight in one the following formats: 10, 10.23, 0.34</span></a>
                </div>
              </td>
            </tr>
            </table>
            </tr>
            <tr>
            <table>
              <tr>        
              <td class="inventory-title"><legend><?php _e( 'Quantity','inventory'); ?></legend></td>
              <td>                
                  <input type="text" name="inventory_quantity" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_quantity); ?>" />                  
              </td>
                  <td>
                  <div id="inventory-help">
                  <a href="#"><img src="http://static-p4.fotolia.com/jpg/00/12/15/15/400_F_12151553_jI74hHdTUVmUpWV8KhX3NGLdbfbuNvsw.jpg" height="15px" />
                  <span>Number of items to sell.</span></a>
                </div>
                  </td>
            </tr>
            </table>
            </tr>
       </table>
      </div>
    </div>
    <input type="submit" name="save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" />
  </form>
  <div style="clear:both; height:50px;">&nbsp;</div>
  <?php
}


function inv_doImages($key) {
  if (!isset($_FILES[$key])) {
    return "";
  }
  $image = $_FILES[$key];
  $name = $image["name"];  
  if ($name) {
    if (!inv_isImage($name)) {
      echo "Warning! NOT an image file! File not uploaded.";
      return "";
    }
    if (inv_uploadFile($image, $name, INVENTORY_SAVE_TO, true)) {
      return $name;
    } else {
      return false;
    }
  }
}

function inv_isImage($file) {
  $imgtypes =array("BMP", "JPG", "JPEG", "GIF", "PNG");
  $ext =strtoupper( substr($file ,strlen($file )-(strlen( $file  ) - (strrpos($file ,".") ? strrpos($file ,".")+1 : 0) ))  ) ;
  if (in_array($ext, $imgtypes)) {
    return true;
  } else {
    return false;
  }
}

function inv_uploadFile($field, $filename, $savetopath, $overwrite, $name="") { 
    global $message;
    if (!is_array($field)) {
      $field = $_FILES[$field];
    }
    if (!file_exists($savetopath)) {
      echo "<br>The save-to path doesn't exist.... attempting to create...<br>";
      mkdir(ABSPATH . "/" . str_replace("../", "", $savetopath));
    }
    if (!file_exists($savetopath)) {
      echo "<br>The save-to directory (" . $savetopath . ") does not exist, and could not be created automatically.<br>";
      return false;
    }
    $saveto = $savetopath . "/" . $filename;
    if ($overwrite!=true) {
      if(file_exists($saveto)) {
        echo "<br>The " . $name . " file " . $saveto . " already exists.<br>";
        return false;
      }
    }
    if ($field["error"] > 0) {
            switch ($field["error"]) {
                case 1:
                    $error = "The file is too big. (php.ini)"; // php installation max file size error
                    break;
                case 2:
                    $error = "The file is too big. (form)"; // form max file size error
                    break;
                case 3:
                    $error = "Only part of the file was uploaded";
                    break;
                case 4:
                    $error = "No file was uploaded";
                    break;
                case 6:
                    $error = "Missing a temporary folder.";
                    break;
                case 7:
                    $error = "Failed to write file to disk";
                    break;
                case 8:
                    $error = "File upload stopped by extension";
                    break;
        default:
          $error = "Unknown error (" . $field["error"] . ")";
          break;
            }
      
    echo $field["error"];
    echo $error;
        return "<br>Error: " . $error . "<br>";
      } else {
      if (move_uploaded_file($field["tmp_name"], $saveto)) {
        return true;
      } else {
        die("Unable to write uploaded file.  Check permissions on upload directory.");
      };
      
    }
}

function inv_deleteFile($fileloc) {
  if (file_exists($fileloc)) {
    $del = unlink($fileloc);
    
    return ($del) ? "<br>Image deleted." : "<br>Image not found to delete.";
  } else {
    return "<br>File " . $fileloc . " could not be deleted (does not exist).";
  }
}

?>