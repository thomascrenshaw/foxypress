<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

add_action('admin_init', 'import_export_postback');

//global vars
global $Categories;
global $CategoriesFlipped;
global $OptionGroups;
global $OptionGroupsFlipped;

function import_export_postback()
{
	global $wpdb, $post, $error;	
	global $Categories, $CategoriesFlipped, $OptionGroups, $OptionGroupsFlipped;
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "import-export")
	{
		if(isset($_POST['file_submit']))
		{
			$uploaded = false;
			//delete current copy of Inventory.csv (if one exists)
			if (file_exists(WP_PLUGIN_DIR . '/foxypress/Inventory.csv')) 
			{
				unlink(WP_PLUGIN_DIR . '/foxypress/Inventory.csv');		
			}
			//upload && rename file to Inventory.csv		
			if ($_FILES['file_import']['name'] != "")
			{
				if (move_uploaded_file($_FILES['file_import']['tmp_name'], WP_PLUGIN_DIR . '/foxypress/Inventory.csv'))
				{
					$uploaded = true;
				}
			}
		
			if($uploaded)
			{
				foxypress_GetImportExportCategories();
				foxypress_GetImportExportOptionGroups();					
				$file = fopen(WP_PLUGIN_DIR . '/foxypress/Inventory.csv', 'r');
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
					$Product_SalePrice = "";
					$Product_SaleStartDate = "";
					$Product_SaleEndDate = "";
					$Product_Weight = "";
					$Product_Quantity = "";
					$Product_QuantityMin = "";
					$Product_QuantityMax = "";
					$Product_Options = "";
					$Product_Attributes = "";					
					$Product_DiscountQuantityAmount = "";
					$Product_DiscountQuantityPercentage = "";
					$Product_DiscountPriceAmount = "";
					$Product_PricePercentage = "";
					$Product_SubFrequency = "";
					$Product_SubStartDate = "";
					$Product_SubEndDate = "";
					$Product_StartDate = "";
					$Product_EndDate = "";
					$Product_Active = "";					
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
						$Product_SalePrice =  mysql_escape_string(str_replace('$','',$row[5]));
						$Product_SaleStartDate = mysql_escape_string($row[6]);
						$Product_SaleEndDate = mysql_escape_string($row[7]);
						$Product_Weight = mysql_escape_string($row[8]);
						$Product_Quantity = mysql_escape_string($row[9]);
						$Product_QuantityMin = mysql_escape_string($row[10]);
						$Product_QuantityMax = mysql_escape_string($row[11]);
						$Product_Options = $row[12];
						$Product_Attributes = $row[13];				
						$Product_DiscountQuantityAmount = mysql_escape_string($row[14]);
						$Product_DiscountQuantityPercentage = mysql_escape_string($row[15]);
						$Product_DiscountPriceAmount = mysql_escape_string($row[16]);
						$Product_PricePercentage = mysql_escape_string($row[17]);
						$Product_SubFrequency = mysql_escape_string($row[18]);
						$Product_SubStartDate = mysql_escape_string($row[19]);
						$Product_SubEndDate = mysql_escape_string($row[20]);
						$Product_StartDate = mysql_escape_string($row[21]);
						$Product_EndDate = mysql_escape_string($row[22]);
						$Product_Active = mysql_escape_string($row[23]);						
						$Product_Data_Set = true;
					}
					
					if($Product_Data_Set)
					{
						$my_post = array(
							 'post_title' => $Product_Name,
							 'post_content' => $Product_Description,
							 'post_status' => 'publish',
							 'post_author' => 1,
							 'post_type' => FOXYPRESS_CUSTOM_POST_TYPE
							 
						  );
					  	$inventory_id = wp_insert_post( $my_post );
						foxypress_save_meta_data($inventory_id, '_code', $Product_Code);
						foxypress_save_meta_data($inventory_id, '_price', $Product_Price);
						foxypress_save_meta_data($inventory_id, '_saleprice', $Product_SalePrice);
						foxypress_save_meta_data($inventory_id, '_salestartdate', $Product_SaleStartDate);
						foxypress_save_meta_data($inventory_id, '_saleenddate', $Product_SaleEndDate);
						foxypress_save_meta_data($inventory_id, '_weight', $Product_Weight);
						foxypress_save_meta_data($inventory_id, '_quantity', $Product_Quantity);
						foxypress_save_meta_data($inventory_id, '_quantity_min', $Product_QuantityMin);
						foxypress_save_meta_data($inventory_id, '_quantity_max', $Product_QuantityMax);						
						foxypress_save_meta_data($inventory_id, '_discount_quantity_amount', $Product_DiscountQuantityAmount);
						foxypress_save_meta_data($inventory_id, '_discount_quantity_percentage', $Product_DiscountQuantityPercentage);
						foxypress_save_meta_data($inventory_id, '_discount_price_amount', $Product_DiscountPriceAmount);
						foxypress_save_meta_data($inventory_id, '_discount_price_percentage', $Product_PricePercentage);
						foxypress_save_meta_data($inventory_id, '_sub_frequency', $Product_SubFrequency);
						foxypress_save_meta_data($inventory_id, '_sub_startdate', $Product_SubStartDate);
						foxypress_save_meta_data($inventory_id, '_sub_enddate', $Product_SubEndDate);
						foxypress_save_meta_data($inventory_id, '_item_start_date', $Product_StartDate);
						foxypress_save_meta_data($inventory_id, '_item_end_date', $Product_EndDate);
						foxypress_save_meta_data($inventory_id, '_item_active', $Product_Active);	
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
										$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_to_category (inventory_id, category_id) values ('$inventory_id', '" . $CategoryID . "')");
									}
								}
							}
						}
						
						//if we don't have any valid categories, insert with the default category (General)
						if(!$Product_Has_Cats)
						{
							$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_to_category (inventory_id, category_id) values ('$inventory_id', '1')");
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
											$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_options (inventory_id, option_group_id, option_text, option_value, option_extra_price, option_active, option_order) values ('$inventory_id', '" . $OptionGroupID . "', '" . mysql_escape_string($OptionExploded[1]) . "', '" . mysql_escape_string($OptionExploded[2]) . "' , '" . mysql_escape_string(str_replace('$','',$OptionExploded[3])) . "', '" . mysql_escape_string($OptionExploded[4]) . "', '" . mysql_escape_string($OptionExploded[5]) . "')");
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
										$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_attributes (inventory_id, attribute_text, attribute_value) values ('$inventory_id', '" . mysql_escape_string($AttributeExploded[0]) . "', '" . mysql_escape_string($AttributeExploded[1]) . "')");
									}					
								}
							}
						}	
					} //end if Product_Data_Set
				}
				fclose($file);
				$error = "Successfully Uploaded. <a href=\"" . foxypress_GetCurrentPageURL(false) . "?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "\">View Inventory</a>";
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
			$row[] = 'Item Sale Price';
			$row[] = 'Item Sale Start Date';
			$row[] = 'Item Sale End Date';
			$row[] = 'Item Weight';
			$row[] = 'Item Quantity';
			$row[] = 'Item Quantity Min';
			$row[] = 'Item Quantity Max';
			$row[] = 'Item Options';
			$row[] = 'Item Attributes';	
			$row[] = 'Item Discount Quantity Amount';	
			$row[] = 'Item Discount Quantity Percentage';	
			$row[] = 'Item Discount Price Amount';	
			$row[] = 'Item Discount Price Percentage';	
			$row[] = 'Subscription Frequency';	
			$row[] = 'Subscription Start Date';	
			$row[] = 'Subscription End Date';	
			$row[] = 'Item Start Date';	
			$row[] = 'Item End Date';	
			$row[] = 'Item Active';				
			
			//$data .= join(',', $row)."\r\n";		
			$list[] = $row;
			foxypress_GetImportExportCategories();
			foxypress_GetImportExportOptionGroups();	
			$cats = "";
			$opts = "";
			$attrs = "";
			$Items = $wpdb->get_results("select * from " . $wpdb->prefix . "posts where post_type='" . FOXYPRESS_CUSTOM_POST_TYPE . "' and post_status='publish' order by ID");
			if(!empty($Items))
			{
				foreach($Items as $item)
				{
					//get categories
					$cats = "";				
					$InventoryCategories = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_to_category where inventory_id='" . $item->ID. "'");
					if(!empty($InventoryCategories))
					{
						foreach($InventoryCategories as $ic)
						{
							$cats .= ($cats == "") ? $Categories[$ic->category_id] : "|" . $Categories[$ic->category_id];
						}
					}		
					//get options
					$opts = "";
					$InventoryOptions = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_options where inventory_id='" . $item->ID. "'");
					if(!empty($InventoryOptions))
					{
						foreach($InventoryOptions as $io)
						{
							//GroupName|Text|Value|Price|Weight|code|Quantity|Active|Order
							$opt = $OptionGroups[$io->option_group_id] . "|" 
									. stripslashes($io->option_text) . "|" 
									. stripslashes($io->option_value) . "|" 
									. $io->option_extra_price . "|" 
									. $io->option_extra_weight . "|" 
									. $io->option_code . "|" 
									. $io->option_quantity . "|" 
									. $io->option_active . "|" 
									. $io->option_order;
							$opts .= ($opts == "") ? $opt : "~~" . $opt ;
						}
					}	
					//get attributes
					$attrs = "";
					$InventoryAttributes = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_attributes where inventory_id='" . $item->ID. "'");
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
					$row[] = get_post_meta($item->ID, "_code", true);
					$row[] = $item->post_title;
					$row[] = $item->post_content;
					$row[] = $cats;
					$row[] = get_post_meta($item->ID, "_price", true);
					$row[] = get_post_meta($item->ID, "_saleprice", true);
					$row[] = get_post_meta($item->ID, "_salestartdate", true);
					$row[] = get_post_meta($item->ID, "_saleenddate", true);
					$row[] = get_post_meta($item->ID,'_weight', true);
					$row[] = get_post_meta($item->ID,'_quantity', true);
					$row[] = get_post_meta($item->ID,'_quantity_min', true);
					$row[] = get_post_meta($item->ID,'_quantity_max', true);
					$row[] = $opts;
					$row[] = $attrs;
					$row[] = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
					$row[] = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
					$row[] = get_post_meta($item->ID,'_discount_price_amount',TRUE);
					$row[] = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
					$row[] = get_post_meta($item->ID,'_sub_frequency',TRUE);
					$row[] = get_post_meta($item->ID,'_sub_startdate',TRUE);
					$row[] = get_post_meta($item->ID,'_sub_enddate',TRUE);
					$row[] = get_post_meta($item->ID,'_item_start_date',TRUE);
					$row[] = get_post_meta($item->ID,'_item_end_date',TRUE);
					$row[] = get_post_meta($item->ID,'_item_active',TRUE);										
					//$data .= join(',', $row)."\r\n";	
					$list[] = $row;
				}
			}
	
			if (file_exists(WP_PLUGIN_DIR . "/foxypress/Export.csv")) 
			{
				unlink(WP_PLUGIN_DIR . "/foxypress/Export.csv");
			}
			$f = fopen(WP_PLUGIN_DIR . "/foxypress/Export.csv", "x+");
			//fwrite($f,$data);		
			foreach ($list as $line)
			{
				fputcsv($f, $line );
				fseek($f, -1, SEEK_CUR);
				fwrite($f, "\r\n"); 
			}
			fclose($f);
			$error = "<a href=\"" . plugins_url() . "/foxypress/Export.csv\" target=\"_blank\">Download Export</a> <small><i>(Right Click, Save As)</i></small>";
		}//end if export
	}//end if were posting to this page
}

function foxypress_GetImportExportOptionGroups()
{
	global $OptionGroups;
	global $OptionGroupsFlipped;
	global $wpdb;	
	$OptionGroups = array();
	
	$OptionGroupData = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_option_group");
	if(!empty($OptionGroupData))
	{
		foreach($OptionGroupData as $ogd)
		{
			$OptionGroups[$ogd->option_group_id] = stripslashes($ogd->option_group_name);	
		}
	}	
	$OptionGroupsFlipped = array_flip($OptionGroups);
}

function foxypress_GetImportExportCategories()
{
	global $Categories;
	global $CategoriesFlipped;
	global $wpdb;	
	$Categories = array();	
	$CategoryData = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories");
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
		<div id="" class="settings_widefat">
			<div class="settings_head settings">
	            <?php _e('Import Inventory','status-management'); ?>
	        </div>
	        <div>
	            <p>We recommend reading the import instructions before selecting a file to upload.  When you are ready, simply browse to the file and click import.</p>
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
		</div>
        <br />
		<div id="" class="settings_widefat">
			<div class="settings_head advanced">
	            <?php _e('Export Inventory','status-management'); ?>
	        </div>
	        <div>
	        	<p>Click the button below to export an excel document of your inventory.</p>
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
		</div>
		<br />
		<div id="" class="settings_widefat">
			<div class="settings_head custom">
	            <?php _e('Import Notes - read before importing','status-management'); ?>
	        </div>		
			<table>
				<tr>
					<td valign="top" width="325px;">
						<ul>
							<li><b>Column Order</b>
								<ul style="list-style-type:disc;margin-left:40px;">
				                    <li>Item Code</li>
				                    <li>Item Name</li>
				                    <li>Item Description</li>
				                    <li>Item Category</li>
				                    <li>Item Price</li>
				                    <li>Item Sale Price</li>
				                    <li>Item Sale Start Date</li>
				                    <li>Item Sale End Date</li>
				                    <li>Item Weight</li>
				                    <li>Item Quantity</li>
				                    <li>Item Quantity Min</li>
				                    <li>Item Quantity Max</li>
				                    <li>Item Options</li>
				                    <li>Item Attributes</li>
				                    <li>Item Discount Quantity Amount</li>
				                    <li>Item Discount Quantity Percentage</li>
				                    <li>Item Discount Price Amount</li>
				                    <li>Item Discount Price Percentage</li>
				                    <li>Subscription Frequency</li>
				                    <li>Subscription Start Date</li>
				                    <li>Subscription End Date</li>
				                    <li>Item Start Date</li>
				                    <li>Item End Date</li>
				                    <li>Item Active</li>
								</ul>
							</li>
						</ul>
					</td>
					<td valign="top">
						<ul>
							<li><b>Formatting Notes</b>
								<ul style="list-style-type:disc;margin-left:40px;">
									<li>Categories must match exactly with categories that you have created in foxypress. If there are multiple categories for an item you can split them up by using "|" (without quotes). 
										<br /><b>Example:</b> General|Shirts|Fun Items
									</li>
									<li>Price does not need to have a currency symbol</li>
									<li>Options will be in this format: Option Group Name|Option Name|Option Value|Option Extra Price|Option Extra Weight|Option Code|Option Quantity|Active|Sort Order
										<ul style="list-style-type:disc;margin-left:40px;">
											<li>Active can be either 1(true) or 0(false)</li>
											<li>Option Group Name must match exactly with option groups that you have created in foxypress.</li>
											<li>Multiple options can be imported by using "~~" (without quotes) between sets.
												<br /><b>Example:</b> Color|Red|red|0.00|0|mycode|100|1|5~~Color|Blue|blue|0.00|0|mycode|100|1|6
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
					</td>
				</tr>
			</table>		
		</div>
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