<?php
$plugin_dir = basename(dirname(__FILE__));
//actions
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$plugin_dir, $plugin_dir);
add_action('admin_menu', 'import_export_menu');
add_action('admin_init', 'import_export_postback');
//global vars
global $Categories;
global $CategoriesFlipped;
global $OptionGroups;
global $OptionGroupsFlipped;

function import_export_menu()  {
	global $wpdb;
	$allowed_group = 'manage_options';	
	if (function_exists('add_submenu_page')) 
	 {
	   add_submenu_page('foxypress', __('Import/Export','foxypress'), __('Import/Export','foxypress'), $allowed_group, 'import-export', 'import_export_page_load');
	 }
}

function import_export_postback()
{
	global $wpdb;	
	global $Categories;
	global $CategoriesFlipped;
	global $OptionGroups;
	global $OptionGroupsFlipped;
	global $error;
	
	if(isset($_POST['file_submit']))
	{
		$uploaded = false;
		//delete current copy of Inventory.csv (if one exists)
		if (file_exists(ABSPATH . 'wp-content/plugins/foxypress/Inventory.csv')) 
		{
			unlink(ABSPATH . 'wp-content/plugins/foxypress/Inventory.csv');		
		}
		//upload && rename file to Inventory.csv		
		if ($_FILES['file_import']['name'] != "")
		{
			if (move_uploaded_file($_FILES['file_import']['tmp_name'], ABSPATH . 'wp-content/plugins/foxypress/Inventory.csv'))
			{
				$uploaded = true;
			}
		}
	
		if($uploaded)
		{
			foxypress_GetCategories();
			foxypress_GetOptionGroups();					
			$file = fopen(ABSPATH . 'wp-content/plugins/foxypress/Inventory.csv', 'r');
			while(! feof($file))
			{
				$inventory_id = "";
				$row = fgetcsv($file);			
				//defaults
				$Product_Code = "";
				$Product_Name = "";
				$Product_Description = "";
				$Product_Categories = "";
				$Product_Price = "";
				$Product_Weight = "";
				$Product_Quantity = "";
				$Product_Options = "";
				$Product_Attributes = "";
				$Product_Data_Set = false;
				$Product_Has_Cats = false;		
				
				if($row[0] == "Item Code" || $row[0] == "") { $Product_Data_Set = false; } //we are reading our row with titles, so skip to the next row
				else
				{
					$Product_Code = mysql_escape_string($row[0]);
					$Product_Name = mysql_escape_string($row[1]);
					$Product_Description = mysql_escape_string($row[2]);
					$Product_Categories = $row[3];
					$Product_Price = mysql_escape_string(str_replace('$','',$row[4]));
					$Product_Weight = mysql_escape_string($row[5]);
					$Product_Quantity = mysql_escape_string($row[6]);
					$Product_Options = $row[7];
					$Product_Attributes = $row[8];					
					$Product_Data_Set = true;
				}
				
				if($Product_Data_Set)
				{
					$wpdb->query("insert into " . WP_INVENTORY_TABLE . " (date_added, inventory_code, inventory_name, inventory_description, inventory_price, inventory_weight, inventory_quantity)  values ('" . time() . "', '$Product_Code', '$Product_Name', '$Product_Description', '$Product_Price', '$Product_Weight', '$Product_Quantity' )");
					$inventory_id = $wpdb->insert_id;
					
					//handle categories
					if(!empty($Product_Categories))
					{
						$CategoriesExploded = explode("|", $Product_Categories);
						if(count($CategoriesExploded) > 0)
						{
							foreach($CategoriesExploded as $Cat)
							{
								$CategoryID = foxypress_GetCategoryID($Cat);
								if($CategoryID != "0")
								{
									$Product_Has_Cats = true;
									$wpdb->query("insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id) values ('$inventory_id', '" . $CategoryID . "')");
								}
							}
						}
					}
					
					//if we don't have any valid categories, insert with the default category (General)
					if(!$Product_Has_Cats)
					{
						$wpdb->query("insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id) values ('$inventory_id', '1')");
					}
					
					//make sure we have some global option groups set up
					if(count($OptionGroups) > 0)
					{
						//handle options
						if(!empty($Product_Options))
						{
							$OptionsExploded = explode("~~", $Product_Options);
							if(count($OptionsExploded) > 0)
							{
								foreach($OptionsExploded as $Option)
								{
									$OptionExploded = explode("|", $Option);
									if(count($OptionExploded) == 6)
									{
										//get option group id
										$OptionGroupID = foxypress_GetOptionGroupID($OptionExploded[0]);					
										$wpdb->query("insert into " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (inventory_id, option_group_id, option_text, option_value, option_extra_price, option_active, option_order) values ('$inventory_id', '" . $OptionGroupID . "', '" . mysql_escape_string($OptionExploded[1]) . "', '" . mysql_escape_string($OptionExploded[2]) . "' , '" . mysql_escape_string(str_replace('$','',$OptionExploded[3])) . "', '" . mysql_escape_string($OptionExploded[4]) . "', '" . mysql_escape_string($OptionExploded[5]) . "')");
									}					
								}
							}
						}	
					}	
						
					
					//handle attributes
					if(!empty($Product_Attributes))
					{
						$AttributesExploded = explode("~~", $Product_Attributes);
						if(count($AttributesExploded) > 0)
						{
							foreach($AttributesExploded as $Attribute)
							{
								$AttributeExploded = explode("|", $Attribute);
								if(count($AttributeExploded) == 2)
								{
									$wpdb->query("insert into " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (inventory_id, attribute_text, attribute_value) values ('$inventory_id', '" . mysql_escape_string($AttributeExploded[0]) . "', '" . mysql_escape_string($AttributeExploded[1]) . "')");
								}					
							}
						}
					}	
				} //end if Product_Data_Set
			}
			fclose($file);
			$error = "Successfully Uploaded. <a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory\">View Inventory</a>";
		}//end if uploaded
		else 
		{
			$error = "Invalid Data";	
		}
	}//end if posted
	else if(isset($_POST['export_submit'])) //start export
	{	
		$list = array();
		$data = "";
		$row = array();
		$row[] = 'Item Code';
		$row[] = 'Item Name';
		$row[] = 'Item Description';
		$row[] = 'Item Category';
		$row[] = 'Item Price';
		$row[] = 'Item Weight';
		$row[] = 'Item Quantity';
		$row[] = 'Item Options';
		$row[] = 'Item Attributes';	
		//$data .= join(',', $row)."\r\n";		
		$list[] = $row;
		foxypress_GetCategories();
		foxypress_GetOptionGroups();	
		$cats = "";
		$opts = "";
		$attrs = "";
		$Items = $wpdb->get_results("select * from " . WP_INVENTORY_TABLE . " order by inventory_id");
		if(!empty($Items))
		{
			foreach($Items as $item)
			{
				//get categories
				$cats = "";				
				$InventoryCategories = $wpdb->get_results("select * from " . WP_INVENTORY_TO_CATEGORY_TABLE . " where inventory_id='" . $item->inventory_id. "'");
				if(!empty($InventoryCategories))
				{
					foreach($InventoryCategories as $ic)
					{
						$cats .= ($cats == "") ? $Categories[$ic->category_id] : "|" . $Categories[$ic->category_id];
					}
				}		
				//get options
				$opts = "";
				$InventoryOptions = $wpdb->get_results("select * from " . WP_FOXYPRESS_INVENTORY_OPTIONS . " where inventory_id='" . $item->inventory_id. "'");
				if(!empty($InventoryOptions))
				{
					foreach($InventoryOptions as $io)
					{
						//GroupName|Text|Value|Price|Active|Order
						$opt = $OptionGroups[$io->option_group_id] . "|" . stripslashes($io->option_text) . "|" . stripslashes($io->option_value) . "|" . $io->option_extra_price . "|" . $io->option_active . "|" . $io->option_order;
						$opts .= ($opts == "") ? $opt : "~~" . $opt ;
					}
				}	
				//get attributes
				$attrs = "";
				$InventoryAttributes = $wpdb->get_results("select * from " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " where inventory_id='" . $item->inventory_id. "'");
				if(!empty($InventoryAttributes))
				{
					foreach($InventoryAttributes as $ia)
					{
						//text|value
						$attr = $ia->attribute_text . "|" . $ia->attribute_value;
						$attrs .= ($attrs == "") ? $attr : "~~" . $attr;
					}
				}			
				//write row
				$row = array(); //clear previous items
				$row[] = $item->inventory_code;
				$row[] = $item->inventory_name;
				$row[] = $item->inventory_description;
				$row[] = $cats;
				$row[] = $item->inventory_price;
				$row[] = $item->inventory_weight;
				$row[] = $item->inventory_quantity;
				$row[] = $opts;
				$row[] = $attrs;
				//$data .= join(',', $row)."\r\n";	
				$list[] = $row;
			}
		}

		if (file_exists(ABSPATH . "wp-content/plugins/foxypress/Export.csv")) 
		{
			unlink(ABSPATH . "wp-content/plugins/foxypress/Export.csv");
		}
		$f = fopen(ABSPATH . "wp-content/plugins/foxypress/Export.csv", "x+");
		//fwrite($f,$data);		
		foreach ($list as $line)
		{
			fputcsv($f, $line );
			fseek($f, -1, SEEK_CUR);
            fwrite($f, "\r\n"); 
		}
		fclose($f);
		$error = "<a href=\"" . get_bloginfo("url") . "/wp-content/plugins/foxypress/Export.csv\" target=\"_blank\">Download Export</a> <small><i>(Right Click, Save As)</i></small>";
	}//end if export
}

function foxypress_GetOptionGroups()
{
	global $OptionGroups;
	global $OptionGroupsFlipped;
	global $wpdb;	
	$OptionGroups = array();
	
	$OptionGroupData = $wpdb->get_results("SELECT * FROM " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP);
	if(!empty($OptionGroupData))
	{
		foreach($OptionGroupData as $ogd)
		{
			$OptionGroups[$ogd->option_group_id] = stripslashes($ogd->option_group_name);	
		}
	}	
	$OptionGroupsFlipped = array_flip($OptionGroups);
}

function foxypress_GetCategories()
{
	global $Categories;
	global $CategoriesFlipped;
	global $wpdb;	
	$Categories = array();	
	$CategoryData = $wpdb->get_results("SELECT * FROM " . WP_INVENTORY_CATEGORIES_TABLE);
	if(!empty($CategoryData))
	{
		foreach($CategoryData as $cat)
		{
			$Categories[$cat->category_id] = stripslashes($cat->category_name);	
		}
	}
	$CategoriesFlipped = array_flip($Categories);	
}

function import_export_page_load()
{
	global $error;
	?>
	<div class="wrap">
    	<h2><?php _e('Import Inventory','status-management'); ?></h2>	
        <div>
            <form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
                <input type="file" name="file_import" id="file_import" /> 
                <input type="submit" name="file_submit" id="file_submit" value="Import" /> 
                <?php
					if( $error != "" && isset($_POST['file_submit']) )
					{
						echo($error);
					}
				?>                 
            </form>			
        </div>
        <br />
        <h2><?php _e('Export Inventory','status-management'); ?></h2>	
        <div>
        	<form method="POST" id="frmExport" name="frmExport">
            	<input type="submit" name="export_submit" id="export_submit" value="Export" /> 
                <?php
					if( $error != "" && isset($_POST['export_submit']) )
					{
						echo($error);
					}
				?>
            </form>
        </div>
		<br />
		<h2><?php _e('Import Notes - read before importing','status-management'); ?></h2>	
		Download a sample inventory CSV file <a href="http://www.foxy-press.com/wp-content/uploads/2011/05/Inventory.csv" target="_blank">here</a>.
		<ul>
			<li>Column Order
				<ul style="list-style-type:disc;margin-left:40px;">
					<li>Item Code</li>
					<li>Item Name</li>
					<li>Item Description</li>
					<li>Item Category</li>
					<li>Item Price</li>
					<li>Item Weight</li>
					<li>Item Quantity</li>
					<li>Item Options</li>
					<li>Item Attributes</li>
				</ul>
			</li>
			<li>Formatting Notes
				<ul style="list-style-type:disc;margin-left:40px;">
					<li>Categories must match exactly with categories that you have created in foxypress. If there are multiple categories for an item you can split them up by using "|" (without quotes). 
						<br /><b>Example:</b> General|Shirts|Fun Items
					</li>
					<li>Price does not need to have a currency symbol</li>
					<li>Options will be in this format: Option Group Name|Option Name|Option Value|Option Extra Price|Active|Sort Order
						<ul style="list-style-type:disc;margin-left:40px;">
							<li>Active can be either 1(true) or 0(false)</li>
							<li>Option Group Name must match exactly with option groups that you have created in foxypress.</li>
							<li>Multiple options can be imported by using "~~" (without quotes) between sets.
								<br /><b>Example:</b> Color|Red|red|0.00|0|5~~Size|X-Large|xlarge|2.00|1|4
							</li>
					</li>
				</ul>
			</li>
			<li>Attributes will be in this format: Attribute Name|Attribute Value
				<ul style="list-style-type:disc;margin-left:40px;">
					<li>Multiple attributes can be imported by using "~~" (without quotes) between sets.
						<br /><b>Example:</b> MyAttributeName|MyValue~~AnotheName|AnotherValue
					</li>
				</ul>
			</li>
		</ul>
    </div>	
	<?php
}

//Helper Functions
function foxypress_GetOptionGroupID($OptionGroupName)
{
	global $OptionGroups;
	global $OptionGroupsFlipped;

	if(in_array($OptionGroupName, $OptionGroups))
	{
		return $OptionGroupsFlipped[$OptionGroupName];
	}
	return "0";
}

function foxypress_GetCategoryID($CategoryName)
{
	global $Categories;
	global $CategoriesFlipped;

	if(in_array($CategoryName, $Categories))
	{
		return $CategoriesFlipped[$CategoryName];
	}
	return "0";
}
?>