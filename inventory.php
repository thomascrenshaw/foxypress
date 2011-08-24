<?php
session_start();
// Enable internationalisation
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
// Create a master category for Inventory and its sub-pages
add_action('admin_menu', 'inventory_menu');
add_action('admin_init', 'inventory_postback');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-sortable');

//handle all postbacks before we have headers
function inventory_postback()
{
	global $wpdb;
	global $users_entries;
	$inventory_id = foxypress_FixGetVar("inventory_id", "");
	$action = foxypress_FixGetVar("action", "");

	if(isset($_POST['option_group_save']))
	{
		$group_name = foxypress_FixPostVar('option_group_name');
		if($group_name != "")
		{
			//insert new option group
			$wpdb->query("insert into " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (option_group_name) values ('" . $group_name . "')");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-option-groups");
	}
	else if(isset($_POST['foxypress_edit_option_save']))
	{
		$group_name = foxypress_FixPostVar('foxypress_edit_option_name');
		$group_id = foxypress_FixPostVar('foxypress_edit_option_id');
		if($group_name != "" && $group_id != "")
		{
			//insert new option group
			$wpdb->query("update " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " set option_group_name ='" . $group_name . "' where option_group_id='" . $group_id . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-option-groups");
	}
	else if($action == "deleteoptiongroup")
	{
		$option_group_id = foxypress_FixGetVar('optiongroupid', '');
		if($option_group_id != "")
		{
			//delete option group
			$wpdb->query("delete from " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " where option_group_id = '" . $option_group_id . "'");
			//delete options related to option group
			$wpdb->query("delete from " . WP_FOXYPRESS_INVENTORY_OPTIONS . " where option_group_id = '" . $option_group_id . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory-option-groups");
	}
	else if(isset($_POST['foxy_option_save']))
	{
		$optionname = foxypress_FixPostVar('foxy_option_name');
		$optionvalue = foxypress_FixPostVar('foxy_option_value');
		$optiongroupid = foxypress_FixPostVar('foxy_option_group');
		$optionextraprice = foxypress_FixPostVar('foxy_option_extra_price', '0');
		$optionextraweight = foxypress_FixPostVar('foxy_option_extra_weight', '0'); 
		$optioncode = foxypress_FixPostVar('foxy_option_code', ''); 
		$optionquantity = foxypress_FixPostVar('foxy_option_quantity', '');
		
		if($optionname != "" && $optionvalue != "" && $optiongroupid != "")
		{
			//insert new option
			$wpdb->query("insert into " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (inventory_id, option_group_id, option_text, option_value, option_extra_price, option_extra_weight, option_code, option_quantity, option_active) values ('" . $inventory_id . "', '" . $optiongroupid . "', '" . $optionname . "', '" . $optionvalue . "', '" . $optionextraprice . "', '" . $optionextraweight . "', '" . $optioncode . "', " . (($optionquantity == "") ? "NULL" : "'" . $optionquantity . "'") . ", '1')");
		}
		//NOTE: currently unique product option codes only work per 1 group per item, so if they try entering in unique codes for mulitple
		//option groups we need ot wipe them out.
		$BadData = $wpdb->get_row("select count(distinct option_group_id) as GroupCount from " . WP_FOXYPRESS_INVENTORY_OPTIONS. " where inventory_id='" . $inventory_id . "'");
		if(!empty($BadData) && $BadData->GroupCount > 1)
		{
			//wipe out the codes and quantities
			$wpdb->query("update " . WP_FOXYPRESS_INVENTORY_OPTIONS . " 
						  set option_quantity = NULL
							,option_code = NULL
						  where inventory_id = '" . $inventory_id . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if(isset($_POST['foxy_options_update']))
	{
		//save option data
		$rowsToProcess = foxypress_FixPostVar('hdn_foxy_options_count');
		if($rowsToProcess > 0)
		{
			for($i=1; $i<=$rowsToProcess; $i++)
			{
				$optionID = foxypress_FixPostVar('hdn_foxy_option_id_' . $i);
				$optiongroupid = foxypress_FixPostVar('foxy_option_group_' . $i);
				$optionname = foxypress_FixPostVar('foxy_option_text_' . $i);
				$optionvalue = foxypress_FixPostVar('foxy_option_value_' . $i);				
				$optionextraprice = foxypress_FixPostVar('foxy_option_extra_price_' . $i);
				$optionextraweight = foxypress_FixPostVar('foxy_option_extra_weight_' . $i);
				$optionCode = foxypress_FixPostVar('foxy_option_code_' . $i, '');
				$optionActive = foxypress_FixPostVar('foxy_option_active_' . $i);
				$optionQuantity = foxypress_FixPostVar('foxy_option_quantity_' . $i, '');				
				$wpdb->query("update " . WP_FOXYPRESS_INVENTORY_OPTIONS . " 
							  set option_group_id = '" . $optiongroupid . "'
								  ,option_text = '" . $optionname . "'
								  ,option_value = '" . $optionvalue . "'
								  ,option_extra_price = '" . $optionextraprice . "'
								  ,option_extra_weight = '" . $optionextraweight . "'
								  ,option_code = '" . $optionCode. "'
								  ,option_quantity = " . (($optionQuantity == "") ? "NULL" : "'" . $optionQuantity . "'") . " 	
								  ,option_active = '" . $optionActive. "'								  						  
							  where option_id='" . $optionID . "'");
			}
			
			//NOTE: currently unique product option codes only work per 1 group per item, so if they try entering in unique codes for mulitple
			//option groups we need ot wipe them out.
			$BadData = $wpdb->get_row("select count(distinct option_group_id) as GroupCount from " . WP_FOXYPRESS_INVENTORY_OPTIONS. " where inventory_id='" . $inventory_id . "'");
			if(!empty($BadData) && $BadData->GroupCount > 1)
			{
				//wipe out the codes and quantities
				$wpdb->query("update " . WP_FOXYPRESS_INVENTORY_OPTIONS . " 
							  set option_quantity = NULL
							  	,option_code = NULL
							  where inventory_id = '" . $inventory_id . "'");
			}			
		}
		//update sort order		
		$OptionsOrderArray = explode(",", foxypress_FixPostVar('hdn_foxy_options_order'));
		$counter = 1;
		foreach ($OptionsOrderArray as $OptionID) 
		{
			$wpdb->query("update " . WP_FOXYPRESS_INVENTORY_OPTIONS . " set option_order = '$counter' where option_id='" . $OptionID . "'");
			$counter++;
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if(isset($_POST['foxy_attribute_save']))
	{
		$attributename = foxypress_FixPostVar('foxy_attribute_name');
		$attributevalue = foxypress_FixPostVar('foxy_attribute_value');
		if($attributename != "" && $attributevalue != "")
		{
			//insert new option
			$wpdb->query("insert into " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (inventory_id, attribute_text, attribute_value) values ('" . $inventory_id . "', '" . $attributename . "', '" . $attributevalue . "')");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if($action == "deleteoption")
	{
		$optionid = foxypress_FixGetVar("optionid", "");
		if($optionid != "")
		{
			$wpdb->query("delete from " . WP_FOXYPRESS_INVENTORY_OPTIONS . " where option_id = '" . $optionid . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if($action == "deleteattribute")
	{
		$attributeid = foxypress_FixGetVar("attributeid", "");
		if($attributeid != "")
		{
			$wpdb->query("delete from " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " where attribute_id = '" . $attributeid . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if ($action=="delimage")
	{
    	$image_id = foxypress_FixGetVar('image_id', '');
		if ($image_id != "")
		{
			$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
			$query = sprintf('SELECT inventory_image FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);
			$data = $wpdb->get_row($query);
			if (!empty($data)) {
				//only delete the file if it's not our default image
				if($data->inventory_image != INVENTORY_DEFAULT_IMAGE)
				{
					foxypress_DeleteItem($directory . $data->inventory_image);
				}
				$query = sprintf('DELETE FROM ' . WP_INVENTORY_IMAGES_TABLE . ' WHERE inventory_images_id=%d', $image_id);
				$wpdb->query($query);
			}
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
 	}
	elseif ($action == "delete") {
		if (!empty($inventory_id)) {
			//delete inventory item
		  	$wpdb->query("DELETE FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id='" . $inventory_id . "'");
			//delete inventory options
		  	$wpdb->query("DELETE FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " WHERE inventory_id='" . $inventory_id . "'");
			//get inventory images, delete from table, delete from file system
		  	$image_data = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_IMAGES_TABLE . " WHERE inventory_id='" . $inventory_id . "'");
			if(!empty($image_data))
			{
				foreach($image_data as $i)
				{
					if($i->inventory_image != INVENTORY_DEFAULT_IMAGE)
					{
						$directory = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
						foxypress_DeleteItem($directory . $i->inventory_image);
					}
				}
			}
		  	$wpdb->query("DELETE FROM " . WP_INVENTORY_IMAGES_TABLE . " WHERE inventory_id='" . $inventory_id . "'");
			//delete inventory attributes
		  	$wpdb->query("DELETE FROM " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " WHERE inventory_id='" . $inventory_id . "'");
			//delete inventory categories
		  	$wpdb->query("DELETE FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " WHERE inventory_id='" . $inventory_id . "'");
			//leave downloadables as is for legacy purposes.
			$wpdb->query("UPDATE " . WP_INVENTORY_DOWNLOADABLES . " SET status = 1 where WHERE inventory_id='" . $inventory_id . "'");
			header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory");
		}
	}
	else if($action == "deletecategory")
	{
		$itc_id = foxypress_FixGetVar("itc_id", "");
		if($itc_id != "")
		{
			$wpdb->query("delete from " . WP_INVENTORY_TO_CATEGORY_TABLE . " where itc_id = '" . $itc_id . "'");
		}
		header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id);
	}
	else if ( isset($_POST['foxy_inventory_save']) ) {
		$error = "";
		$saveok = true;
		$save = (isset($_REQUEST["inventory_name"])) ? 1 : 0;
		$code = foxypress_FixPostVar('inventory_code', '');
		$name = foxypress_FixPostVar('inventory_name', '');
		$desc = $_POST['inventory_description'];
		$cats = $_POST['foxy_categories'];
		$price = str_replace("$", "", str_replace(",", "", foxypress_FixPostVar('inventory_price', '')));
		$price = ($price * 1);
		$price = (!$price) ? 0 : $price;
		$sale_price = str_replace("$", "", str_replace(",", "", foxypress_FixPostVar('inventory_sale_price', '')));
		$sale_start_date = foxypress_FixPostVar('inventory_sale_start');
		$sale_end_date = foxypress_FixPostVar('inventory_sale_end');
		$weight = foxypress_FixPostVar('inventory_weight', '');
		$quantity = foxypress_FixPostVar('inventory_quantity', '');
		$quantity_min = foxypress_FixPostVar('inventory_quantity_min', '');
		$quantity_max = foxypress_FixPostVar('inventory_quantity_max', '');				
		$discount_quantity_amount = foxypress_FixPostVar('inventory_discount_quantity_amount', '');
		$discount_quantity_percentage = foxypress_FixPostVar('inventory_discount_quantity_percentage', '');
		$discount_price_amount = foxypress_FixPostVar('inventory_discount_price_amount', '');
		$discount_price_percentage = foxypress_FixPostVar('inventory_discount_price_percentage', '');			
		$item_active = foxypress_FixPostVar('inventory_active');
		$item_start_date = foxypress_FixPostVar('inventory_start_date');
		$item_end_date = foxypress_FixPostVar('inventory_end_date');			
		$added = time(); 
		// The name must be at least one character in length and no more than 100 - no non-standard characters allowed
		if ($save) {
			//check title
			if (strlen(trim($name))>1 && strlen(trim($name))<100){ }
			else
			{
				$saveok = false;
				?>
                <div class="error">
                	<p>
                    	<strong>Error:</strong>
                        The item name must be between 1 and 100 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one
					</p>
				</div>
                <?php
			}
			//check cats
			if($saveok && empty($cats))
			{
				$saveok = false;
				?>
                <div class="error">
                	<p>
                    	<strong>Error:</strong>
                        You must choose at least one category
					</p>
				</div>
                <?php
			}
		}

		if ($save && $saveok == 1) {
      		$cur_user = wp_get_current_user();
      		$cur_user = $cur_user->ID;
			$single_upload = false;
			if ($inventory_id != "" && $inventory_id != "0")
			{
				// update item
				$sql = "UPDATE " . WP_INVENTORY_TABLE . " SET inventory_code='" . mysql_escape_string($code) . "'"
				. ", inventory_name='" . mysql_escape_string($name) . "'"
				. ", inventory_description='" . mysql_escape_string($desc) . "'"
				. ", inventory_weight='" . mysql_escape_string($weight) . "'"
				. ", inventory_price='" . mysql_escape_string($price) . "'"
				. ", inventory_sale_price=" . (($sale_price == "") ? "NULL" : "'" . $sale_price . "'")
				. ", inventory_sale_start=" . (($sale_start_date == "") ? "NULL" : "'" . $sale_start_date . "'")
				. ", inventory_sale_end=" . (($sale_end_date == "") ? "NULL" : "'" . $sale_end_date . "'")
				. ", inventory_quantity=" . (($quantity == "") ? "NULL" : "'" . $quantity . "'")
				. ", inventory_quantity_min='" . $quantity_min . "'"
				. ", inventory_quantity_max='" . $quantity_max . "'"				
				. ", inventory_discount_quantity_amount='" . $discount_quantity_amount . "'"
				. ", inventory_discount_quantity_percentage='" . $discount_quantity_percentage . "'"
				. ", inventory_discount_price_amount='" . $discount_price_amount . "'"
				. ", inventory_discount_price_percentage='" . $discount_price_percentage . "'"		
				. ", inventory_active='" . $item_active . "'"			
				. ", inventory_start_date=" . (($item_start_date == "") ? "NULL" : "'" . $item_start_date . "'")
				. ", inventory_end_date=" . (($item_end_date == "") ? "NULL" : "'" . $item_end_date . "'")	
				. " WHERE inventory_id='" . mysql_escape_string($inventory_id) . "'";
				$wpdb->query($sql);
			}
			else
			{
				//new item
				$single_upload = true;
				$sql = "INSERT INTO " . WP_INVENTORY_TABLE . " SET inventory_code='" . mysql_escape_string($code) . "'"
				. ", inventory_name='" . mysql_escape_string($name) . "'"
				. ", inventory_description='" . mysql_escape_string($desc) . "'"
				. ", date_added='" . mysql_escape_string($added) . "'"
				. ", inventory_weight='" . mysql_escape_string($weight) . "'"
				. ", inventory_price='" . mysql_escape_string($price) . "'"
				. ", inventory_sale_price=" . (($sale_price == "") ? "NULL" : "'" . $sale_price . "'")
				. ", inventory_sale_start=" . (($sale_start_date == "") ? "NULL" : "'" . $sale_start_date . "'")
				. ", inventory_sale_end=" . (($sale_end_date == "") ? "NULL" : "'" . $sale_end_date . "'")
				. ", inventory_quantity=" . (($quantity == "") ? "NULL" : "'" . $quantity . "'")
				. ", inventory_quantity_min='" . $quantity_min . "'"
				. ", inventory_quantity_max='" . $quantity_max . "'"
				. ", inventory_discount_quantity_amount='" . $discount_quantity_amount . "'"
				. ", inventory_discount_quantity_percentage='" . $discount_quantity_percentage . "'"
				. ", inventory_discount_price_amount='" . $discount_price_amount . "'"
				. ", inventory_discount_price_percentage='" . $discount_price_percentage . "'"		
				. ", inventory_active='" . $item_active . "'"			
				. ", inventory_start_date=" . (($item_start_date == "") ? "NULL" : "'" . $item_start_date . "'")
				. ", inventory_end_date=" . (($item_end_date == "") ? "NULL" : "'" . $item_end_date . "'")		
				. ", category_id='0'"
				;
				$wpdb->query($sql);
				$inventory_id = $wpdb->insert_id;
			}

			//if the save fails
        	if ($inventory_id == "" || $inventory_id == "0")
			{
				echo '<div class="error">
			  		  	<p><strong>Error:</strong>Invalid Inventory Item</p>
					  </div>';
			}
			else
			{
				//handle categories
				$AllCategories = $wpdb->get_results( "SELECT category_id FROM " . WP_INVENTORY_CATEGORIES_TABLE );
				$CategoryArray = array();
				foreach($AllCategories as $ac)
				{
					$CategoryArray[] = $ac->category_id;
				}
				foreach($CategoryArray as $cat)
				{
					if(in_array($cat, $cats))
					{
						//check to see if it exists already
						$relationshipExists = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " WHERE inventory_id = '" . mysql_escape_string($inventory_id) . "' AND category_id='" . $cat . "'");
						if(empty($relationshipExists))
						{
							$sql = "INSERT INTO " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id) values ('" . mysql_escape_string($inventory_id) . "', '" . mysql_escape_string($cat)  . "')";
							$wpdb->query($sql);						
						}
					}
					else
					{
						$sql = "DELETE FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " WHERE inventory_id = '" . mysql_escape_string($inventory_id) . "' and category_id='" . $cat . "'";
						$wpdb->query($sql);	
					}					
				}
				//handle uploads
				if($single_upload)
				{
					//attempt to upload the image
					if(!empty($_FILES["fp_inv_image"]))
					{
						$image = isset($_FILES["fp_inv_image"]["name"]) ? $_FILES["fp_inv_image"]["name"] : "";
						if ($image)
						{
							$imgname = foxypress_UploadImage("fp_inv_image", $inventory_id);
						}
						//if the upload succeeded, insert into database
						if ($imgname) {
							$imgquery = 'INSERT INTO ' . WP_INVENTORY_IMAGES_TABLE . ' SET inventory_id=' . $inventory_id . ', inventory_image="' . mysql_escape_string($imgname) . '"';
							$wpdb->query($imgquery);
						}
					}
					//attempt to upload the downloadable
					if(!empty($_FILES["fp_inv_downloadable"]))
					{
						$targetpath = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR;	
						$fileExtension = foxypress_ParseFileExtension($_FILES['fp_inv_downloadable']['name']);
						$downloadablename = foxypress_FixPostVar("fp_inv_downloadable_name");
						$downloadablemaxdownloads = foxypress_FixPostVar("fp_inv_downloadable_max_downloads");
						$prefix = "";
						if($downloadablename == "")
						{
							$prefix = "downloadable_";
						}
						else
						{
							$prefix = str_replace(" ", "_", $downloadablename);
							$prefix = $prefix . "_";
						}	
						$newfilename = foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, $prefix);	
						$targetpath = $targetpath . $newfilename; 							
						if(move_uploaded_file($_FILES['fp_inv_downloadable']['tmp_name'], $targetpath))
						{
							$query = "INSERT INTO " . WP_INVENTORY_DOWNLOADABLES . " SET inventory_id='" . $inventory_id . "', filename='" . mysql_escape_string($newfilename) . "', maxdownloads= '" . mysql_escape_string($downloadablemaxdownloads) . "'";
							$wpdb->query($query);
						}
					}					
				}
				if($error != "")
				{
					$error = "&error=" . $error;
				}
				else { $error = ""; }
				header("location: " . $_SERVER['PHP_SELF'] . "?page=inventory&inventory_id=" . $inventory_id . $error);
          	}
		}
		else
		{
			// The form is going to be rejected due to field validation issues, so we preserve the users entries here
			$users_entries->inventory_code = $code;
			$users_entries->inventory_name = $name;
			$users_entries->inventory_description = $desc;
			$users_entries->date_added = $added;
			$users_entries->inventory_price = $price;
			$users_entries->inventory_category = $cat;
			$users_entries->inventory_quantity = $quantity;
		}
	}

}

////////////////////////////////////////////////////////////
//////////////////////helper functions /////////////////////
////////////////////////////////////////////////////////////

function foxypress_UploadImage($key, $inventory_id)
{
	if (!isset($_FILES[$key])) { return "";	}
	$image = $_FILES[$key];
	$name = $image["name"];
	$targetpath = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
	if ($name)
	{
		if (!foxypress_IsValidImage($name))
		{
			echo "Warning! NOT an image file! File not uploaded.";
			return "";
		}
		//get new file name
		$fileExtension = foxypress_ParseFileExtension($name);
		$name = foxypress_GenerateNewFileName($fileExtension, $inventory_id, $targetpath, "fp_");
		//make sure it doesn't exist already
		if (foxypress_UploadFile($image, $name, $targetpath, true))
		{
			return $name;
		}
		else
		{
			return false;
		}
	}
}

function foxypress_IsValidImage($file) {
	$imgtypes = array("JPG", "JPEG", "GIF", "PNG", "BMP");
	$ext = strtoupper( substr($file ,strlen($file )-(strlen( $file  ) - (strrpos($file ,".") ? strrpos($file ,".")+1 : 0) ))  ) ;
	if (in_array($ext, $imgtypes))
	{
		return true;
	}
	return false;
}

function foxypress_UploadFile($field, $filename, $savetopath, $overwrite, $name="") {
    global $message;
    if ( !is_array( $field ) ) {
		$field = $_FILES[$field];
    }
    if ( !file_exists( $savetopath ) ) {
		echo "<br>The save-to path doesn't exist.... attempting to create...<br>";
		mkdir(ABSPATH . "/" . str_replace("../", "", $savetopath));
    }
    if ( !file_exists( $savetopath ) ) {
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
    if ( $field["error"] > 0 ) {
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
		}
    }
}

////////////////////////////////////////////////////////////
//////////////////////page functions ///////////////////////
////////////////////////////////////////////////////////////

// Function to deal with adding the inventory menus
function inventory_menu()  {
  global $wpdb;
  // Set admin as the only one who can use Inventory for security
  $allowed_group = 'manage_options';
  // Add the admin panel pages for Inventory. Use permissions pulled from above
   if (function_exists('add_submenu_page'))
     {
       add_submenu_page('foxypress', __('Inventory','foxypress'), __('Manage Inventory','foxypress'), $allowed_group, 'inventory', 'inventory_page_load');
	   add_submenu_page('foxypress', __('Inventory Option Groups','foxypress'), __('Manage Option Groups','foxypress'), $allowed_group, 'inventory-option-groups', 'inventory_option_groups_page_load');
     }
}

//display inventory items
function foxypress_show_inventory() {
	global $wpdb;
  	global $current_user;
    get_currentuserinfo();
  	$cur_user = $current_user->ID;
  	$cur_user_admin = ( current_user_can('manage_options')) ? 1 : 0;

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
	$QuantityAlertLevel = get_option('foxypress_qty_alert');

	if ( !empty($items) ) {
		?>
		<table class="widefat page fixed" cellpadding="3" cellspacing="3" style="clear: both; width: 100%; margin-bottom: 15px;">
			<thead>
				<tr>
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
			<tr>
				<td colspan="9">
					<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&action=add" class='edit'>Add New Item</a>
				</td>
			</tr>
		<?php
		$class = '';
		foreach ( $items as $item ) {
			$class = ($class == 'alternate') ? '' : 'alternate';
			?>
			<tr class="<?php echo $class; ?>">
				<td><span <?php if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0"){ if($item->inventory_quantity != null && $QuantityAlertLevel > $item->inventory_quantity) { echo("class=\"quantityLow\""); } } ?>><?php echo stripslashes( $item->inventory_code ); ?></span></td>
				<td><span <?php if($QuantityAlertLevel != "" && $QuantityAlertLevel != "0"){ if($item->inventory_quantity != null && $QuantityAlertLevel > $item->inventory_quantity) { echo("class=\"quantityLow\""); } } ?>><?php echo stripslashes($item->inventory_name); ?></span></td>
				<td><?php echo foxypress_TruncateString(stripslashes(strip_tags($item->inventory_description)), 25); ?></td>
				<td><?php echo date("m/d/Y", $item->date_added); ?></td>
				<td><?php echo foxypress_FormatCurrency($item->inventory_price); ?></td>
				<td><?php echo stripslashes($item->category_name); ?></td>
				<td><img src="<?php echo (($item->inventory_image != "") ? INVENTORY_IMAGE_DIR . '/' . stripslashes($item->inventory_image) : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE) ?>" width="25px" /></td>
				<?php if ($cur_user_admin || !$limit_edit) { ?>
				<td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;inventory_id=<?php echo $item->inventory_id;?>" class='edit'><?php echo __('Edit','inventory'); ?></a></td>
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
		//pagination
		if($drRows->RowCount > $limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
			echo ("<Br>" . $Pagination);
		}

  	}
  	else {
		?>
		<p><?php _e("There are no inventory items in the database!",'inventory')  ?></p>
		<p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=inventory&amp;action=add" class='edit'>Add New Item</a></p>
		<?php
  	}
}

//our page load for inventory
function inventory_page_load() {
    global $current_user, $wpdb, $users_entries;
	$inventory_id = foxypress_FixGetVar('inventory_id');
	$action = foxypress_FixGetVar('action');
	// Check if it's the current user's item
	/*
	if ( $inventory_id )
	{
		$query = "SELECT inventory_userid FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id=" . mysql_escape_string($inventory_id);
		$data = $wpdb->get_results($query);
		$data = $data[0];
	}
	*/
	?>
	<link href="<?php echo(plugins_url())?>/foxypress/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/uploadify/jquery.uploadify.min.js"></script>
    <link rel="stylesheet" href="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/css/smoothness/jquery-ui-1.8.11.custom.css"> 
    <script type="text/javascript" src="<?php echo(get_bloginfo("url")) ?>/wp-content/plugins/foxypress/js/jquery-ui-1.8.11.custom.min.js"></script>
    <script type="text/javascript">
		jQuery(document).ready(function() {
		  //calendars		  
			jQuery("#inventory_sale_start").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#inventory_sale_end").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#inventory_start_date").datepicker({ dateFormat: 'yy-mm-dd' });
			jQuery("#inventory_end_date").datepicker({ dateFormat: 'yy-mm-dd' });
		
		  //uploadify
		  jQuery('#inv_image').uploadify({
			'swf'  : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify.swf',
			'cancelImage' : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify-cancel.png',
			'uploader'    : '<?php echo(plugins_url())?>/foxypress/imagehandler.php',
			'buttonText': 'Browse Files',
			'auto'      : true,
			'queueSizeLimit': 1,
			'fileTypeDesc': 'Image Files',
			'fileTypeExts': '*.jpg;*.jpeg;*.gif;*.png;*.bmp;*.JPG;*.JPEG;*.GIF;*.PNG;*.BMP',
			'multi': false,
			'postData' : { 'inventory_id' : '<?php echo($inventory_id) ?>', 'prefix' : '<?php echo(WP_INVENTORY_IMAGES_TABLE) ?>' },
			'checkExisting' : false,
			'onUploadSuccess': function (file, data, response) {
				ShowPhoto(data);
			}
		  });
		  
		  jQuery('#inv_downloadable').uploadify({
			'swf'  : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify.swf',
			'cancelImage' : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify-cancel.png',
			'uploader'    : '<?php echo(plugins_url())?>/foxypress/documenthandler.php',
			'buttonText': 'Browse Files',
			'auto'      : true,
			'queueSizeLimit': 1,
			'fileTypeDesc': 'Downloadables',
			'multi': false,
			'postData' : { 'inventory_id' : '<?php echo($inventory_id) ?>', 'prefix' : '<?php echo(WP_INVENTORY_DOWNLOADABLES) ?>', 'downloadablename' :  jQuery('#inv_downloadable_name').val(), 'downloadablemaxdownloads' : jQuery('#inv_downloadable_max_downloads').val() },
			'onDialogOpen' : function() {
							jQuery('#inv_downloadable').uploadifySettings('postData', { 'inventory_id' : '<?php echo($inventory_id) ?>', 'prefix' : '<?php echo(WP_INVENTORY_DOWNLOADABLES) ?>', 'downloadablename' :  jQuery('#inv_downloadable_name').val(), 'downloadablemaxdownloads' : jQuery('#inv_downloadable_max_downloads').val() });
						},
			'checkExisting' : false,
			'fileSizeLimit' : 16384000,
			'onUploadSuccess': function (file, data, response) {
				ShowDownloadable(data);
			}
		  });
		  
		  //sorting
		  jQuery( "#foxypress_inv_options tbody" ).sortable(
				{
					update: function(event, ui) { jQuery('#hdn_foxy_options_order').val(jQuery( "#foxypress_inv_options tbody" ).sortable("toArray")); }	
				}
			);
		
		  jQuery( "#inventory_images > *" ).sortable(
				{
					revert: true,
					update: function(event, ui) { SaveImageOrder(); }
				}
			);
			
		});
		
		function SaveImageOrder()
		{
			var ImageOrder = jQuery( "#inventory_images > *" ).sortable("toArray");
			var url = "<?php echo(plugins_url()) . "/foxypress/ajax.php?m=saveimageorder&sid=" . session_id() . "&inventoryid=" . $inventory_id . "&order=" ?>" + ImageOrder;
			
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false"
						}
					);
		}
				
		function ShowDownloadable(data)
		{
			//check for errors
			if(data.indexOf("<Error>") >= 0)
			{
				var error = data.replace("<Error>", "");
				error = error.replace("</Error>", "");
				alert(error);
			}
			else
			{
				var FileName = data.split("|")[0];
			 	var DownloadableID = data.split("|")[1];
				var MaxDownloads = data.split("|")[2];
				jQuery('#downloadable_wizard').hide();
				jQuery('#inventory_downloadables').html("<a href=\"<?php echo(get_bloginfo("url")) ?>/wp-content/inventory_downloadables/" + FileName + "\" target=\"_blank\">" + FileName + "</a> &nbsp; <img src=\"<?php echo(plugins_url())?>/foxypress/img/delimg.png\" alt=\"\" onclick=\"DeleteDownloadable('<?php echo(plugins_url()) . "/foxypress/ajax.php" ?>', '<?php echo(session_id()) ?>', '<?php echo($inventory_id) ?>', '" + DownloadableID + "');\" class=\"RemoveItem\" align=\"bottom\" /> Max Downloads: <input type=\"text\" name=\"inv_downloadable_update_max_downloads\" id=\"inv_downloadable_update_max_downloads\" value=\"" + MaxDownloads + "\" style=\"width:40px;\" /> <input type=\"button\" name=\"inv_downloadable_update_max_downloads_button\" id=\"inv_downloadable_update_max_downloads_button\" value=\"Update\" onclick=\"SaveMaxDownloads('<?php echo(plugins_url()) . "/foxypress/ajax.php" ?>', '<?php echo(session_id()) ?>', '<?php echo($inventory_id) ?>', '" + DownloadableID + "');\" /><img src=\"<?php echo(plugins_url())?>/foxypress/img/ajax-loader.gif\" id=\"inv_downloadable_loading\" name=\"inv_downloadable_loading\" style=\"display:none;\" />");				
			}
		}
		
		function DeleteDownloadable(baseurl, sid, inventoryid, downloadableid)
		{
			var url = baseurl + "?m=deletedownloadable&sid=" + sid + "&downloadableid=" + downloadableid + "&inventoryid=" + inventoryid;
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false"
						}
					);
			jQuery('#downloadable_wizard').show();
			DownloadableWizard(1);
			jQuery('#inventory_downloadables').html("");
		}
		
		function ShowPhoto(data)
		{
			var FileName = data.split("|")[0];
		 	var ImageID = data.split("|")[1];
			var FolderName = "<?php echo(INVENTORY_IMAGE_DIR) ?>";
			var LastNumberUsed = jQuery('#inventory_last_image_number').val();
			if(LastNumberUsed == "")
			{
				LastNumberUsed = 0;
			}
  			//var pics = jQuery("#inventory_images").children("div").length + 1;
			var pics = parseInt(LastNumberUsed) + 1;
			var newlistitem = jQuery('<li id="inventory_images-' + ImageID + '" class="CreatePhoto"><div class="PhotoWrapper"><img src="' + FolderName + '/' + FileName + '" width="50" /></div><div style="text-align:center;"><a id="inventory_images_remove-' + pics + '" class="RemoveItem"><img src="<?php echo(plugins_url())?>/foxypress/img/delimg.png" alt="" /></a></div></li>');
            jQuery("#inventory_images ul").append(newlistitem);
			jQuery("#inventory_last_image_number").val(pics);
			jQuery("#inventory_images_remove-" + ImageID).click(function () {
				//ajax to delete image
				jQuery(this).parent().parent().remove();
				DeletePhoto('<?php echo(plugins_url()) . "/foxypress/ajax.php" ?>', '<?php echo(session_id()) ?>', '<?php echo($inventory_id) ?>', ImageID);
			});
			jQuery( "#inventory_images > *" ).sortable(
				{
					revert: true,
					update: function(event, ui) { SaveImageOrder(); }
				}
			);
		}

		function DeletePhoto(baseurl, sid, inventoryid, imageid)
		{
			var url = baseurl + "?m=deletephoto&sid=" + sid + "&imageid=" + imageid + "&inventoryid=" + inventoryid;
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false"
						}
					);
			jQuery( "#inventory_images > *" ).sortable(
				{
					revert: true,
					update: function(event, ui) { SaveImageOrder(); }
				}
			);
		}
		
		function DownloadableWizard(step)
		{
			if(step == 1)
			{
				jQuery('#downloadable_1').show();
				jQuery('#downloadable_2').hide();
				jQuery('#downloadable_3').hide();
			}
			else if(step == 2)
			{
				jQuery('#downloadable_1').hide();
				jQuery('#downloadable_2').show();
				jQuery('#downloadable_3').hide();
			}
			else if(step == 3)
			{
				jQuery('#downloadable_1').hide();
				jQuery('#downloadable_2').hide();
				jQuery('#downloadable_3').show();
			}
		}
		
		function SaveMaxDownloads(baseurl, sid, inventoryid, downloadable_id)
		{
			jQuery('#inv_downloadable_update_max_downloads_button').hide();
			jQuery('#inv_downloadable_loading').show();
			var maxdownloads = jQuery('#inv_downloadable_update_max_downloads').val();
			var url = baseurl + "?m=savemaxdownloads&sid=" + sid + "&downloadableid=" + downloadable_id + "&inventoryid=" + inventoryid + "&maxdownloads=" + maxdownloads;
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false",
							success : function() {
								jQuery('#inv_downloadable_update_max_downloads_button').show();
								jQuery('#inv_downloadable_loading').hide();
							}
						}
					);
		}
		
	</script>
    <style type="text/css">
		.CreatePhoto
		{   float:left;
			width:60px;
		}
		.PhotoWrapper
		{
			padding: 5px;
			position:relative;
		}

		.RemoveItem
		{
			cursor:pointer;
		}
		#inventory-help a,#inventory-help a:visited { position: relative; display:block; width:100px; margin:0; text-decoration: none; }
		#inventory-help a span { display: none; }
		#inventory-help a span img { border: 1px solid black; float:right; margin-left:10px; margin-bottom:5px; }
		#inventory-help a:hover span { z-index: 25; display: block; position:absolute; min-height:15px; width:240px; color: black; font:14px ; margin-top: 5px; padding: 10px; background-color: #ffff88; border: 1px solid black; }
		#inventory-help a:hover span { width:240px;margin-left: 25px;}
		#inventory-help a:hover {text-indent: 0;}
		.inventory-title { width: 160px; }
		.quantityLow { color: #CC0000; }
		div.foxy_item_pagination {
			padding: 3px;
			margin: 3px;
		}
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
/*			background-color: #000099;*/
			color: #666666;
		}
		div.foxy_item_pagination span.disabled {
			padding: 2px 5px 2px 5px;
			margin: 2px;
			border: 1px solid #EEE;
			color: #ccc;
		}
		.required{color:red;}
    </style>
	<div class="wrap">
	<?php
		if ($inventory_id != "" && $inventory_id != "0")
		{
			?>
			<h2>Edit Inventory Item</h2>
		 	<?php foxypress_edit_item($inventory_id);
		}
		elseif ($action=='add')
		{ ?>
			<h2>Add Inventory Item</h2>
			<?php foxypress_edit_item();
		}
		else
		{
			?>
			<h2>Manage Inventory</h2>
			<?php
			foxypress_show_inventory();
		}
  	?>
	</div>
	<?php
}

/*
function foxypress_load_tinymce() {
	wp_enqueue_script('tiny_mce');
}
*/

function foxypress_edit_item($inventory_id = "") {
	global $wpdb, $users_entries;
	$data = false;

	if ($inventory_id != "0" && $inventory_id != "")
	{
		$data = $wpdb->get_row("SELECT * FROM " . WP_INVENTORY_TABLE . " WHERE inventory_id='" . mysql_escape_string($inventory_id) . "'");
		if ( empty( $data ) )
		{
			echo "<div class=\"error\"><p>".__("An item with that ID couldn't be found",'inventory')."</p></div>";
			return;
		}
		// Check if it's the current user's item
		/*$cur_user = wp_get_current_user();
		$cur_user = $cur_user->ID;
		if (!($data->inventory_userid==$cur_user || $data->inventory_userid==0)) {
		  	echo "<div class=\"error\"><p>".__("Not authorized to edit this item.",'inventory')."</p></div>";
			return;
		}*/
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
  	<form enctype="multipart/form-data" name="quoteform" id="quoteform" class="wrap" method="post">
        <div id="linkadvanceddiv" class="postbox">
        	<div style="float: left; width: 98%; clear: both;" class="inside">
            	<table cellpadding="5" cellspacing="5">
                    <tr>
                    	<td class="inventory-title"><legend><?php _e( 'Item Code' , 'inventory'); ?></legend></td>
                        <td>
                            <input style="float:left;"type="text" name="inventory_code" class="input" size="20" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_code); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                              <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                              <span>Item Code: i.e item sku, or number</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Item Name','inventory' ); ?><span class="required">*</span></legend></td>
                        <td>
                            <input type="text" name="inventory_name" class="input" size="60" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_name); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span><b>[Required]</b> Name of the item or product</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title" <?php if (!empty($data) && $data->inventory_id) { echo("valign=\"top\""); } ?>>Item Category<span class="required">*</span></td>
                        <td>
                        	<?php
							$CurrentCategoriesArray = array();
							//check for current categories
							if (!empty($data) && $data->inventory_id)
							{
								$inventory_categories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id
																			FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " as itc inner join " .
																			WP_INVENTORY_CATEGORIES_TABLE . " as c on itc.category_id = c.category_id
																			WHERE inventory_id='" . $data->inventory_id . "'");
								if(!empty($inventory_categories))
								{
									foreach($inventory_categories as $inventory_cat)
									{
										$CurrentCategoriesArray[] = $inventory_cat->category_id;
									}
								}
							}
                            // Grab all the categories and list them
                            $cats = $wpdb->get_results( "SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE );
                            foreach( $cats as $cat )
							{
								$checked="";
								if(in_array($cat->category_id, $CurrentCategoriesArray))
								{
									$checked = "checked=\"checked\"";
								}
								echo("<input type=\"checkbox\" name=\"foxy_categories[]\" value=\"" . $cat->category_id . "\" " . $checked. " /> " . stripslashes($cat->category_name) . "<br/>");
                            }
							?>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span><b>[Required]</b> Select the item category.  The categories must first be created via foxycart.com, then added to your inventory category list.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
						<td class="inventory-title" style="vertical-align:top;" nowrap><legend><?php _e('Item Description','inventory'); ?><span class="required">*</span></legend></td>
                        <td>
                        	<textarea style="width:500px;height:300px;" class="inventory_description" id="inventory_description" name="inventory_description"><?php if ( !empty($data) ) echo stripslashes($data->inventory_description); ?></textarea>
                            <script type="text/javascript">
								CKEDITOR.replace( 'inventory_description' );
							</script>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span><b>[Required]</b> Enter a description of your product or item.</span></a>
                            </div>
                        </td>
                    </tr>                    
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Image(s)','inventory'); ?></legend></td>
                        <?php if ($inventory_id == "0" || $inventory_id == "")  { ?>
                        <td>
                        	<input type="file" name="fp_inv_image" id="fp_inv_image" />
                        </td>
                        <?php } else { ?>
                        <td>
							<?php
								$fp_current_images_num = 0;
								$fp_current_images = "";
								$fp_current_images_js = "";
								$fp_ajax_url = plugins_url() . "/foxypress/ajax.php";
								//show images if we have any
								if (!empty($data) && $data->inventory_id) {
									//get current image
									$inventory_images = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_IMAGES_TABLE . " WHERE inventory_id=" . $data->inventory_id . " ORDER BY image_order");
									if(!empty($inventory_images))
									{
										foreach($inventory_images as $i)
										{
											$fp_current_images_num++;
											$fp_current_images .= "<li id=\"inventory_images-" . $i->inventory_images_id . "\" class=\"CreatePhoto\">
													 <div class=\"PhotoWrapper\">
														<img src=\"" . INVENTORY_IMAGE_DIR . "/" . $i->inventory_image . "\" style=\"max-width: 50px;\" />
													</div>
													<div style=\"text-align:center;\">
														<a id=\"inventory_images_remove-" . $i->inventory_images_id . "\" class=\"RemoveItem\">
														<img src=\"" . plugins_url() . "/foxypress/img/delimg.png\" alt=\"\" /></a>
													</div>
												 </li>";

											$fp_current_images_js .= "jQuery(\"#inventory_images_remove-" . $i->inventory_images_id . "\").click(function () {															jQuery(this).parent().parent().remove(); DeletePhoto('" . $fp_ajax_url. "', '" . session_id() . "', '" . $data->inventory_id . "', '" . $i->inventory_images_id . "');  }); ";
										}
									}
								}
							?>
                            <div id="filewrapper">
                                <input type="file" name="inv_image" id="inv_image">
                                <input type="hidden" name="inventory_last_image_number" id="inventory_last_image_number" value="<?php echo($fp_current_images_num) ?>" />
                            </div>
                            <div id="inventory_images"><ul><?php echo($fp_current_images) ?></ul></div>
                            <div style="clear:both;"></div>
                            <?php
                                if($fp_current_images_js != "")
                                {
                                    echo("<script type=\"text/javascript\" language=\"javascript\">" . $fp_current_images_js . "</script>");
                                }
                            ?>
                        </td>
                        <?php } ?>
                        <td>
                            <div id="inventory-help">
                            <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                            <span>Upload an image to associate to the product.  This will display in foxycart and the inventory.</span></a>
                            </div>
                        </td>
                    </tr>
                    <?php if ($inventory_id == "0" || $inventory_id == "")  { ?>
                    <tr>
                    	<td>Digital Download Name</td>                    	 
                        <td><input type="text" name="fp_inv_downloadable_name" id="fp_inv_downloadable_name" value="my_download" />    
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter a name for the digital download file.</span></a>
                            </div>
                        </td>                
                    </tr>
                    <tr>
                    	<td>Max Downloads</td>                    	 
                        <td><input type="text" name="fp_inv_downloadable_max_downloads" id="fp_inv_downloadable_max_downloads" value="" />                    
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the maximum number of downloads allowed for this particular file, if it's 0 or blank the global max download number will be used.</span></a>
                            </div>
                        </td> 
                    </tr>
                    <tr>
                    	<td>Digital Download</td>
                        <td>
                        	<input type="file" name="fp_inv_downloadable" id="fp_inv_downloadable" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Upload a file to associate to the product. This will be a downloadable item.</span></a>
                            </div>
                        </td> 
                    </tr>
                     <?php } else { ?>                    
                    <tr>
                    	<td>Digital Download</td>
                        <td>                        	
                        <?php
							$fp_current_downloadables = "";
							$fp_has_downloadable = false;
							$fp_ajax_url = plugins_url() . "/foxypress/ajax.php";
							//show downloads if we have any
							if (!empty($data) && $data->inventory_id) {
								//get current image
								$inventory_downloadables = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_DOWNLOADABLES . " WHERE inventory_id = '" . $data->inventory_id . "' AND status = '1'");
								if(!empty($inventory_downloadables))
								{
									foreach($inventory_downloadables as $d)
									{
										$fp_current_downloadables .= "<a href=\"" . get_bloginfo("url") . "/wp-content/inventory_downloadables/" . $d->filename . "\" target=\"_blank\">" . $d->filename . "</a> &nbsp; <img src=\"" . plugins_url() . "/foxypress/img/delimg.png\" alt=\"\" onclick=\"DeleteDownloadable('" . $fp_ajax_url. "', '" . session_id() . "', '" . $data->inventory_id . "', '" . $d->downloadable_id . "');\" class=\"RemoveItem\" align=\"bottom\"/> Max Downloads: <input type=\"text\" name=\"inv_downloadable_update_max_downloads\" id=\"inv_downloadable_update_max_downloads\" value=\"" . $d->maxdownloads . "\" style=\"width:40px;\" /> <input type=\"button\" name=\"inv_downloadable_update_max_downloads_button\" id=\"inv_downloadable_update_max_downloads_button\" value=\"Update\" onclick=\"SaveMaxDownloads('" . $fp_ajax_url . "', '" . session_id() . "', '" . $data->inventory_id . "', '" . $d->downloadable_id . "');\" /><img src=\"" . plugins_url() . "/foxypress/img/ajax-loader.gif\" id=\"inv_downloadable_loading\" name=\"inv_downloadable_loading\" style=\"display:none;\" />";
									}
									$fp_has_downloadable = true;
								}
							}
						?>                     
                        	<div id="downloadable_wizard" <?php echo(($fp_has_downloadable) ? " style=\"display:none;\"" : "") ?>>   
                                <div id="downloadable_1">
                                    Step 1. Digital Download Name: <input type="text" name="inv_downloadable_name" id="inv_downloadable_name" value="my_download" /> 
                                    <a href="javascript:DownloadableWizard(2);" />></a>
                                </div>
                                <div id="downloadable_2" style="display:none;">
                                    Step 2. Max Downloads: <input type="text" name="inv_downloadable_max_downloads" id="inv_downloadable_max_downloads" value="" /> <a href="javascript:DownloadableWizard(3);" />></a>
                                </div>
                                <div id="downloadable_3" style="display:none;">
                                    <input type="file" name="inv_downloadable" id="inv_downloadable"> 
                                </div>
                            </div>
                            <div id="inventory_downloadables"><?php echo($fp_current_downloadables) ?></div>
						</td>    
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Step 1: Enter the name of the digital download. Step 2: Enter the maximum number of downloads allowed for this particular file, if it's 0 or blank the global max download number will be used. Step 3: Upload your file.</span></a>
                            </div>
                        </td>                    
                    </tr>
                     <?php } ?>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Item Price','inventory'); ?></legend></td>
                        <td>
                            <?php echo(foxypress_GetCurrenySymbol()); ?><input type="text" name="inventory_price" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_price); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the price of the item without the currency symbol.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Sale Price','inventory'); ?></legend></td>
                        <td>
                            <?php echo(foxypress_GetCurrenySymbol()); ?><input type="text" name="inventory_sale_price" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_sale_price); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the sale price of the item without the currency symbol.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Sale Start Date','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_sale_start" id="inventory_sale_start" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_sale_start); ?>" /> <small><i>(yyyy-mm-dd format. ex: <?php echo(date("Y-m-d"))?>)</i></small>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the date when the sale starts.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Sale End Date','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_sale_end" id="inventory_sale_end" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_sale_end); ?>" /> <small><i>(yyyy-mm-dd format. ex: <?php echo(date("Y-m-d"))?>)</i></small>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the date when the sale ends.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Item Weight','inventory' ); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_weight" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_weight); ?>" />lb(s)
                    	</td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the product weight in one the following formats: 10, 10.23, 0.34</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Quantity','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_quantity" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_quantity); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Number of items available. Leave this field blank if there is an unlimited amount</span></a>
                        	</div>
                        </td>
                    </tr>
                     <tr>
                        <td class="inventory-title"><legend><?php _e( 'Minimum Quantity','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_quantity_min" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_quantity_min); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Minimum quantity per order. Leave this field blank or at 0 if you don't want to enforce a minimum quantity.</span></a>
                        	</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Maximum Quantity','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_quantity_max" class="input" size="10" maxlength="30" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_quantity_max); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Maximum quantity per order. Leave this field blank or at 0 if you don't want to enforce a maximum quantity.</span></a>
                        	</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Item Availability','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_start_date" id="inventory_start_date" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_start_date); ?>" /> to <input type="text" name="inventory_end_date" id="inventory_end_date" class="input" size="10" maxlength="20" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_end_date); ?>" /> <small><i>(yyyy-mm-dd format. ex: <?php echo(date("Y-m-d"))?>)</i></small>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Enter the start and end dates for when this item is available. Leave the dates blank if the item is always available.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e('Active','inventory'); ?></legend></td>
                        <td>
                            <select name="inventory_active">
                            	<option value="1" <?php echo(($data->inventory_active == "1") ? "selected=\"selected\"" : ""); ?>>Yes</option>
                                <option value="0" <?php echo(($data->inventory_active == "0") ? "selected=\"selected\"" : ""); ?>>No</option>
                            </select>
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Whether or not the item is currently active.</span></a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="3"><b>Item Discounts</b> <i>(For help with discounts visit FoxyCart's <a href="http://wiki.foxycart.com/v/0.7.0/coupons_and_discounts" target="_blank">documentation</a> or <a href="http://wiki.foxycart.com/v/0.7.0/cheat_sheet#discount_methods" target="_blank">cheat sheet</a>)</i></td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Quantity Amount','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_discount_quantity_amount" class="input" size="30" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_discount_quantity_amount); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Discounts by an amount, based on the quantity of to-be-discounted products.</span></a>
                        	</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Quantity Percentage','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_discount_quantity_percentage" class="input" size="30" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_discount_quantity_percentage); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Discounts by a percentage, based on the quantity of to-be-discounted products.</span></a>
                        	</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Price Amount','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_discount_price_amount" class="input" size="30" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_discount_price_amount); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Discounts by an amount, based on the price of to-be-discounted products.</span></a>
                        	</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="inventory-title"><legend><?php _e( 'Price Percentage','inventory'); ?></legend></td>
                        <td>
                            <input type="text" name="inventory_discount_price_percentage" class="input" size="30" maxlength="100" value="<?php if ( !empty($data) ) echo stripslashes($data->inventory_discount_price_percentage); ?>" />
                        </td>
                        <td>
                            <div id="inventory-help">
                                <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                <span>Discounts by a percentage, based on the price of to-be-discounted products.</span></a>
                        	</div>
                        </td>
                    </tr>
				</table>
      		</div>
            <div style="clear:both;"></div>
        </div>
	   	<input type="submit" name="foxy_inventory_save" id="foxy_inventory_save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" />
	</form>
    <?php if ($inventory_id != "0" && $inventory_id != "") { ?>
    	<br />
        <h2>Manage Options</h2>
        <?php
		$groups = $wpdb->get_results("select * from " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " order by option_group_name");
		$groups_selection_list = "";
		if(!empty($groups))
		{
			foreach($groups as $group)
			{
				$groups_selection_list .= "<option value=\"" . $group->option_group_id . "\">" . $group->option_group_name . "</option>";
			}
		}
		if(!empty($groups))
		{ //show add form if we at least have 1 option group to add to
			?>
			<form id="foxy_add_new_option" name="foxy_add_new_option" method="POST">
            	<div id="linkadvanceddiv" class="postbox">
        			<div style="float: left; width: 98%; clear: both;" class="inside">
                        <div style="padding:5px;"><b>New Option:</b></div>
                        <table cellpadding="5" cellspacing="5">
                            <tr>
                                <td>Option Name</td>
                                <td><input type="text" id="foxy_option_name" name="foxy_option_name" /></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Option Value</td>
                                <td><input type="text" id="foxy_option_value" name="foxy_option_value" /></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Option Group</td>
                                <td><select name="foxy_option_group" id="foxy_option_group"><?php echo($groups_selection_list) ?></select></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Extra Price</td>
                                <td><?php echo(foxypress_GetCurrenySymbol()); ?><input type="text" id="foxy_option_extra_price" name="foxy_option_extra_price" /></td>
                                <td>
                                    <div id="inventory-help">
                                        <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                        <span>Enter negative numbers if you need to subtract from the default price.</span></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Extra Weight</td>
                                <td><input type="text" id="foxy_option_extra_weight" name="foxy_option_extra_weight" />lb(s)</td>
                                <td>
                                    <div id="inventory-help">
                                        <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                        <span>Enter negative numbers if you need to subtract from the default weight.</span></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Unique Code</td>
                                <td><input type="text" id="foxy_option_code" name="foxy_option_code" /></td>
                                <td>
                                    <div id="inventory-help">
                                        <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                        <span>If your product code differs for different options, enter the correct code here. Currently option level quantities only work correctly for 1 option group per item.</span></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Option Quantity</td>
                                <td><input type="text" id="foxy_option_quantity" name="foxy_option_quantity" /></td>
                                <td>
                                    <div id="inventory-help">
                                        <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                                        <span>Use this field if have a unique product code and would like to keep track of inventory at the option specific level.</span></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"><input type="submit" id="foxy_option_save" name="foxy_option_save" value="<?php _e('Save'); ?> &raquo;"  class="button bold"  /></td>
                            </tr>
                        </table>
					</div>
                    <div style="clear:both;"></div>
				</div>
			</form>
            <Br />
            <form id="foxy_order_options" name="foxy_order_options" method="POST">
                <table id="foxypress_inv_options" class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
                    <thead>
                        <tr>
                            <th class="manage-column" scope="col" style="width:50px;">Sort</th>
                            <th class="manage-column" scope="col">Option Name</th>
                            <th class="manage-column" scope="col">Option Value</th>
                            <th class="manage-column" scope="col">Option Group</th>
                            <th class="manage-column" scope="col">Extra Price</th>
                            <th class="manage-column" scope="col">Extra Weight</th>
                            <th class="manage-column" scope="col">Unique Code</th>
                            <th class="manage-column" scope="col">Active</th>
                            <th class="manage-column" scope="col">Qty</th>
                            <th class="manage-column" scope="col">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                    //get options
                    $foxy_inv_options = $wpdb->get_results("select o.*, og.option_group_name
                                                            from " . WP_FOXYPRESS_INVENTORY_OPTIONS . " as o
                                                            inner join " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " as og on o.option_group_id = og.option_group_id
                                                            where o.inventory_id = '" . $inventory_id .  "'
                                                            order by option_order");
                    $current_option_order = "";
					$row = 1;
                    if(!empty($foxy_inv_options))
                    {
                        foreach($foxy_inv_options as $foxyopt)
                        {
                            $current_option_order .= ($current_option_order == "") ? $foxyopt->option_id : "," . $foxyopt->option_id;
                            echo("<tr id=\"" . $foxyopt->option_id . "\">
                                    <td style=\"cursor:pointer;\"><img src=\"" . plugins_url() . "/foxypress/img/sort.png\" style=\"padding-top:3px;\" /></td>
                                    <td><input type=\"text\" name=\"foxy_option_text_" . $row . "\" id=\"foxy_option_text_" . $row . "\" value=\"" . $foxyopt->option_text . "\" size=\"15\"></td>
                                    <td><input type=\"text\" name=\"foxy_option_value_" . $row . "\" id=\"foxy_option_value_" . $row . "\" value=\"" . $foxyopt->option_value . "\" size=\"15\"></td>
                                    <td><select id=\"foxy_option_group_" . $row . "\" name=\"foxy_option_group_" . $row . "\">" . foxypress_BuildInventoryOptionGroupList($groups, $foxyopt->option_group_id) . "</select></td>
                                    <td nowrap>" . foxypress_GetCurrenySymbol() . "<input type=\"text\" name=\"foxy_option_extra_price_" . $row . "\" id=\"foxy_option_extra_price_" . $row . "\" value=\"" . number_format($foxyopt->option_extra_price, 2) . "\" size=\"10\"></td>
                                    <td nowrap><input type=\"text\" name=\"foxy_option_extra_weight_" . $row . "\" id=\"foxy_option_extra_weight_" . $row . "\" value=\"" . number_format($foxyopt->option_extra_weight, 2) . "\" size=\"5\">lb(s)</td>
                                    <td><input type=\"text\" name=\"foxy_option_code_" . $row . "\" id=\"foxy_option_code_" . $row . "\" value=\"" . $foxyopt->option_code . "\" size=\"10\"></td>
                                    <td>
										<select id=\"foxy_option_active_" . $row . "\" name=\"foxy_option_active_" . $row . "\">
											<option value=\"1\" " . (($foxyopt->option_active == "1") ? "selected=\"selected\"" : "") . ">Yes</option>
											<option value=\"0\" " . (($foxyopt->option_active == "0") ? "selected=\"selected\"" : "") . ">No</option>											
										</select>
                                    </td>
									<td>
										<input type=\"text\" id=\"foxy_option_quantity_" . $row . "\" name=\"foxy_option_quantity_" . $row . "\" value=\"" . $foxyopt->option_quantity . "\" size=\"5\" />
									</td>
                                    <td><input type=\"hidden\" name=\"hdn_foxy_option_id_" . $row . "\" id=\"hdn_foxy_option_id_" . $row . "\" value=\"" . $foxyopt->option_id . "\" /><a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory&amp;action=deleteoption&inventory_id=" . $inventory_id . "&optionid=" . $foxyopt->option_id . "\" class=\"delete\" onclick=\"return confirm('Are you sure you want to delete this option?');\"><img src=\"" . plugins_url() . "/foxypress/img/delimg.png\" alt=\"Delete\" class=\"noBorder\" /></td>
                                 </tr>");
                            $row++;
                        }
                    }
                    else
                    {
                        echo("<tr><td colspan=\"7\">There are currently no options for this inventory item</td></tr>");
                    }
                ?>
                    </tbody>
                </table>
                <br />
           		<input type="submit" id="foxy_options_update" name="foxy_options_update" value="<?php _e('Save Options'); ?> &raquo;"  class="button bold" />
                <input type="hidden" id="hdn_foxy_options_order" name="hdn_foxy_options_order" value="<?php echo($current_option_order) ?>" />
                <input type="hidden" id="hdn_foxy_options_count" name="hdn_foxy_options_count" value="<?php echo($row-1); ?>" />
            </form>
		<?php
		}
		else
		{
			_e("<div>
					You do not have any option groups set up yet. In order to add a new option for this inventory item you must
					add a <a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory-option-groups\">new option group</a>.
			   </div>");
		}
		?>
		<br>
		<h2>Manage Custom Attributes</h2>
         <form id="foxy_add_new_attribute" name="foxy_add_new_attribute" method="POST">
             <div id="linkadvanceddiv" class="postbox">
                <div style="float: left; width: 98%; clear: both;" class="inside">
                    <div style="padding:5px;"><b>New Attribute:</b></div>
                    <table cellpadding="5" cellspacing="5">
                        <tr>
                            <td>Attribute Name</td>
                            <td><input type="text" id="foxy_attribute_name" name="foxy_attribute_name" /></td>
                        </tr>
                        <tr>
                            <td>Attribute Value</td>
                            <td><input type="text" id="foxy_attribute_value" name="foxy_attribute_value" /></td>
                        </tr>
                        <tr>
                            <td colspan="2"><input type="submit" id="foxy_attribute_save" name="foxy_attribute_save" value="<?php _e('Save'); ?> &raquo;"  class="button bold"  /></td>
                        </tr>
                    </table>
                </div>
                <div style="clear:both;"></div>
			</div>
        </form>
        <Br />
        <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
            <thead>
                <tr>
                    <th class="manage-column" scope="col">Attribute Name</th>
                    <th class="manage-column" scope="col">Attribute Value</th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
        <?php
            //get options
            $foxy_inv_attributes = $wpdb->get_results("select * from " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " where inventory_id = '" . $inventory_id .  "' order by attribute_text");
            if(!empty($foxy_inv_attributes))
            {
                foreach($foxy_inv_attributes as $foxyatt)
                {
                    echo("<tr>
                            <td>" . stripslashes($foxyatt->attribute_text) . "</td>
                            <td>" . stripslashes($foxyatt->attribute_value) . "</td>
                            <td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory&amp;action=deleteattribute&inventory_id=" . $inventory_id . "&attributeid=" . $foxyatt->attribute_id . "\" class=\"delete\" onclick=\"return confirm('Are you sure you want to delete this attribute?');\">Delete</td>
                         </tr>");
                }
            }
            else
            {
                echo("<tr><td colspan=\"3\">There are currently no attributes for this inventory item</td></tr>");
            }
        ?>
        </table>
		<?php

    } //end check if it's a new item
  	?>
  	<div style="clear:both; height:50px;">&nbsp;</div>
  <?php
}

function foxypress_BuildInventoryOptionGroupList($groups, $selectedid)
{
	$groups_selection_list = "";
	foreach($groups as $group)
	{
		$groups_selection_list .= "<option value=\"" . $group->option_group_id . "\" " . (($group->option_group_id == $selectedid) ? "selected=\"selected\"" : "") . ">" . $group->option_group_name . "</option>";
	}	
	return $groups_selection_list;
}
?>