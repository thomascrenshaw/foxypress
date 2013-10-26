<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

add_action('admin_init', 'import_export_postback');

function import_export_postback()
{
	global $foxyExported;
	$foxyExported = false;
	global $foxyImportData;
	$foxyImportData = false;
	global $foxyImported;
	$foxyImported = false;
	
	$PageName = foxypress_FixGetVar("page");
	if($PageName == "import-export")
	{
		if(isset($_POST['file_submit']))
		{
			// This is the initial file upload. Generate import preview.
			$foxyImportData = import_export_ImportPreview();
		}
		elseif(isset($_POST['verify_import'])) 
		{
			// Admin has verified import data and wants to proceed with import.
			$foxyImported = foxypress_process_import(false);
		}
		else if(isset($_POST['export_submit'])) //start export
		{	
			$foxyExported = import_export_Export();
		}//end if export
	}//end if were posting to this page
}

function import_export_page_load()
{
	global $error;
	global $foxyExported;
	global $foxyImportData;
	global $foxyImported;
	?>
	<div class="wrap">
		<?php screen_icon('foxypress'); ?>
		<h2><?php _e('Import/Export','foxypress'); ?></h2>
		<div class="settings_widefat">
			<div class="settings_head settings">
        <?php _e('Import','foxypress'); ?>
    	</div>
    	<?php 
    		if ($foxyImportData):
					// Import file uploaded 
					if ($foxyImportData['error']):
						// Error on import preview
			?>
			<div class="fp-import-export-container">
			  <p><?php _e('Error when uploading file and generating import preview:','foxypress'); ?></p>
			  <p><?php echo $foxyImportData['message']; ?></p>
			  <form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
			  	<input type="file" name="file_import" id="file_import" /> 
			  	<input type="submit" name="file_submit" id="file_submit" value="<?php _e('Import Preview', 'foxypress'); ?>" /> 
			  </form>		
			</div>
			<?php
					else:
						// No import errors on import upload. Display import preview.
			?>
			<div class="import-preview fp-import-export-container">
			  <p><?php _e('FoxyPress import file uploaded. Please verify data to be imported below before clicking Import.', 'foxypress'); ?></p>
			  <p>
				  <form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
				  	<input type="submit" name="verify_import" id="verify_import" value="<?php _e('Import', 'foxypress'); ?>" /> 
				  </form>
			  </p>
			  <h3>Categories</h3>
			  <div class="category-table-wrapper">
				  <table>
						<thead>
							<tr>
								<td>Category ID</td>
								<td>Category Name</td>
								<td>Category Parent ID</td>
								<td>Existing Category or New?</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($foxyImportData['categories'] as $category) :
								$existing_text = "";
								if ($category['category_insert']) {
									$existing_text = "New";
								} else {
									$existing_text = "Existing";
								}
							?>
							<tr>
								<td><?php echo $category['category_import_id']; ?></td>
								<td><?php echo $category['category_name']; ?></td>
								<td><?php echo $category['category_parent_id']; ?></td>
								<td><?php echo $existing_text; ?></td>
							</tr>
							<?php
							endforeach;
							?>
						</tbody>
					</table>
				</div>
				<h3>Products</h3>
				<div class="product-table-wrapper">
					<table>
						<thead>
							<tr>
								<td>Name</td>
								<td class="description">Description</td>
								<td>Code</td>
								<td>Price</td>
								<td>Sale Price</td>
								<td>Sale Start Date</td>
								<td>Sale End Date</td>
								<td>Weight</td>
								<td>Quantity</td>
								<td>Quantity Min</td>
								<td>Quantity Max</td>
								<td>Discount Qty Amount</td>
								<td>Discount Qty Percent</td>
								<td>Discount Price Amount</td>
								<td>Discount Price Percent</td>
								<td>Subscription Frequency</td>
								<td>Subscription Start</td>
								<td>Subscription End</td>
								<td>Item Start</td>
								<td>Item End</td>
								<td>Item Active</td>
								<td>Categories</td>
								<td>Options</td>
								<td>Images</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($foxyImportData['products'] as $product) :
								$image_thumbnails = "";
								$image_urls = explode("|", $product['_images']);
								foreach ($image_urls as $image_url) {
									$image_thumbnails .= "<img src='$image_url' style='width:50px; height: 50px' alt='' />";
								}
							?>
							<tr>
								<td><?php echo $product['_name']; ?></td>
								<td class="description"><?php echo $product['_description']; ?></td>
								<td><?php echo $product['_code']; ?></td>
								<td><?php echo $product['_price']; ?></td>
								<td><?php echo $product['_saleprice']; ?></td>
								<td><?php echo $product['_salestartdate']; ?></td>
								<td><?php echo $product['_saleenddate']; ?></td>
								<td><?php echo $product['_weight']; ?></td>
								<td><?php echo $product['_quantity']; ?></td>
								<td><?php echo $product['_quantity_min']; ?></td>
								<td><?php echo $product['_quantity_max']; ?></td>
								<td><?php echo $product['_discount_quantity_amount']; ?></td>
								<td><?php echo $product['_discount_quantity_percentage']; ?></td>
								<td><?php echo $product['_discount_price_amount']; ?></td>
								<td><?php echo $product['_discount_price_percentage']; ?></td>
								<td><?php echo $product['_sub_frequency']; ?></td>
								<td><?php echo $product['_sub_startdate']; ?></td>
								<td><?php echo $product['_sub_enddate']; ?></td>
								<td><?php echo $product['_item_start_date']; ?></td>
								<td><?php echo $product['_item_end_date']; ?></td>
								<td><?php echo $product['_item_active']; ?></td>
								<td><?php echo $product['_categories']; ?></td>
								<td><?php echo $product['_options']; ?></td>
								<td><?php echo $image_thumbnails; ?></td>
							</tr>
							<?php
							endforeach;
							?>
						</tbody>
					</table>
				</div>
				<p><?php _e('If import above is incorrect, please fix import file and reupload below:', 'foxypress'); ?></p>
				<form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
					<input type="file" name="file_import" id="file_import" /> 
					<input type="submit" name="file_submit" id="file_submit" value="<?php _e('Reupload Import', 'foxypress'); ?>" /> 
				</form>
			</div>
			<?php
					endif;
    		elseif ($foxyImported): 
    			// Import confirmed 
    			if ($foxyImported['error']):
    				// Error on import data
    	?>
    	<div class="fp-import-export-container">
    	  <p><?php _e('Error when processing import:','foxypress'); ?></p>
    	  <p><?php echo $foxyImported['message']; ?></p>
    	  <form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
    	  	<input type="file" name="file_import" id="file_import" />
    	  	<input type="submit" name="file_submit" id="file_submit" value="<?php _e('Import Preview', 'foxypress'); ?>" />
    	  </form>
    	</div>
    	<?php
    			else:
    				// No import errors on process import file
    	?>
    	<div class="fp-import-export-container">
    	  <p><?php echo $foxyImported['message']; ?></p>
    	</div>
    	<?php 
    			endif;
    		else: 
    			// Import file not uploaded. Display import upload form. 
    	?>
    	<div class="fp-import-export-container">
    	  <p><?php _e('We recommend reading the import instructions before selecting a file to upload.  When you are ready, simply browse to the file and click import.', 'foxypress'); ?></p>
    		<form method="POST" enctype="multipart/form-data" id="frmImport" name="frmImport">
    			<input type="file" name="file_import" id="file_import" />
    			<input type="submit" name="file_submit" id="file_submit" value="<?php _e('Import Preview', 'foxypress'); ?>" />
    	  </form>
    	</div>
    	<?php endif; ?>
		</div>
		<div class="settings_widefat">
			<div class="settings_head advanced">
	            <?php _e('Export','foxypress'); ?>
	        </div>
	        <div class="fp-import-export-container">
	        	<p><?php _e('Click the button below to generate a CSV export of your FoxyPress categories and products', 'foxypress'); ?>.</p>
				<form method="POST" id="frmExport" name="frmExport">
	            	<input type="submit" name="export_submit" id="export_submit" value="<?php _e('Export', 'foxypress'); ?>" />
	                <?php
						if( isset($foxyExported['message']) )
						{
							echo $foxyExported['message'];
						}
					?>
	            </form>
	        </div>
		</div>
		<div class="settings_widefat">
			<div class="settings_head custom">
				<?php _e('Import Notes - read before importing','foxypress'); ?>
			</div>
			<div class="fp-import-export-container fp-import-notes">
				<p>If creating an import file for ease of data entry, it’s often a good idea to build out a couple sample categories/products in FoxyPress, then generate a FoxyPress export from this page to see the correct import file format.</p>
				<p>A valid FoxyPress import file is a CSV that contains three sections:</p>
				<ul>
					<li>Version</li>
					<li>Categories</li>
					<li>Products</li>
				</ul>

				<h3>Version</h3>
				<p>The version is used by FoxyPress to ensure import compatibility. Old FoxyPress exports cannot be imported unless updated to match the current version syntax.</p>
				<p>The FoxyPress import file must contain the version as the first line of the CSV.</p>
				<pre>%%%VERSION,2</pre>
				<p>The current import version is 2.</p>

				<h3>Categories</h3>
				<p>The following three columns are used by FoxyPress categories:</p>
				<ul>
					<li>Category ID</li>
					<li>Category Name</li>
					<li>Category Parent ID</li>
				</ul>
				<pre>%%%CATEGORIES
"Category ID","Category Name","Category Parent"
72,Shirts,0
73,Accessories,0
78,Socks,73</pre>
				<p>Category ID and Category Parent ID will not be directly imported into the database. A new product category ID will be generated for each category that is added to the FoxyPress install.</p>
				<p>If an import category name matches an existing category name, a duplicate will not be generated. Instead all products that point to the import category will be assigned to the existing category.</p>

				<h3>Products</h3>
				<p>The following three columns are used by FoxyPress categories:</p>
				<ul>
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
					<li>Item Images</li>
				</ul>
				<pre>%%%PRODUCTS
"Item Code","Item Name","Item Description","Item Category","Item Price","Item Sale Price","Item Sale Start Date","Item Sale End Date","Item Weight","Item Quantity","Item Quantity Min","Item Quantity Max","Item Options","Item Attributes","Item Discount Quantity Amount","Item Discount Quantity Percentage","Item Discount Price Amount","Item Discount Price Percentage","Subscription Frequency","Subscription Start Date","Subscription End Date","Item Start Date","Item End Date","Item Active","Item Images"
CHAIR1,"Corner Chair","Diam dictumst dis.",1,429.00,279.00,,,,,,,,,,,,,,,,,,1,http://www.example.com/wp-content/uploads/2013/10/fp_6zzhwvjy5j_802.jpg|http://www.example.com/wp-content/uploads/2013/10/fp_xntb8yf8y3_802.jpg
TWL1,Towels,"Sit augue vut cursus elementum cursus a mus?",1,25.99,21.99,,,,,,,,,,,,,,,,,,1,http://www.example.com/wp-content/uploads/2013/10/fp_ff357tgk_805.jpg|http://www.example.com/wp-content/uploads/2013/10/fp_du9plx40xi_805.jpg</pre>
				<h4>Item Price</h4>
				<p>Price does not require a currency symbol.</p>
				<h4>Item Category</h4>
				<p>Categories are specified by the category ID from the above <code>%%%CATEGORIES</code> section. Multiple categories can be specified by the vertical pipe character: <code>|</code></p>
				<p>Example:</p>
				<pre>1|73|78</pre>
				<h4>Item Options</h4>
				<p>Options are saved in the following format: <code>Option Group Name|Option Name|Option Value|Option Extra Price|Option Extra Weight|Option Code|Option Quantity|Active|Sort Order</code></p>
				<ul>
					<li><code>Active</code> can be either 1(true) or 0(false)</li>
					<li><code>Option Group Name</code> must match exactly with option groups that you have created in FoxyPress</li>
					<li>Multiple options can be imported by using <code>~~</code> (two tildes) between sets.</li>
				</ul>
				<p>Example:</p>
				<pre>Color|Red|red|0.00|0|mycode|100|1|5~~Color|Blue|blue|0.00|0|mycode|100|1|6</pre>
				<h4>Item Attributes</h4>
				<p>Attributes are saved in the following format: <code>Attribute Name|Attribute Value</code></p>
				<p>Multiple attributes can be imported by using <code>~~</code> (two tildes) between sets.</p>
				<p>Example:</p>
				<pre>MyAttributeName|MyValue~~AnotheName|AnotherValue</pre>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Stores POSTed import file and runs import preview on it
 * 
 * @since 0.4.3.5
 * 
 * @return array Response object as an array
 */
function import_export_ImportPreview() {
	$response = array();
	
	// Upload the import file to the server
	$uploaded = false;
	//delete current copy of Inventory.csv (if one exists)
	if (file_exists(WP_PLUGIN_DIR . '/foxypress/Import.csv')) 
	{
		unlink(WP_PLUGIN_DIR . '/foxypress/Import.csv');		
	}
	//upload && rename file to Inventory.csv
	if ($_FILES['file_import']['name'] != "")
	{
		if (move_uploaded_file($_FILES['file_import']['tmp_name'], WP_PLUGIN_DIR . '/foxypress/Import.csv'))
		{
			$uploaded = true;
		}
	}
	
	// If import file not uploaded, set error and return before executing
	//   the rest of this function
	if (!$uploaded) 
	{
		$response['error'] = true;
		$response['message'] = 'Invalid Data';
		return $response;
	}

	return foxypress_process_import();
}

/**
 * Request to export foxypress category and product information
 * 
 * @since 0.4.3.6
 * 
 * @return array Response object as an array
 */
function import_export_Export() {
	
	$response = array();
	
	if ( foxypress_generate_export() ) {
		$response['error'] = false;
		$response['message'] = "<a href=\"" . plugins_url() . "/foxypress/Export.csv\" target=\"_blank\">Download Export</a> <small><i>(Right Click, Save As)</i></small>";
	} else {
		$response['error'] = true;
		$response['message'] = "<small><i>Unable to generate FoxyPress export</i></small>";
	}
	
	return $response;
}
?>