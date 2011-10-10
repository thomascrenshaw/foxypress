<?php
function foxypress_Install($new_install = false, $installation_attempts = 0)
{
	 global $wpdb;
	 //NOTES
	 //Remember to update the table count if we add new tables so that we can verify installations
	 if(!foxypress_Installation_CanRunUpdates()) { return; }
	 $wp_inventory_exists = false;
	 $wp_ordermanagement_exists = false;
	// Determine the version
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table ) 
	{
		foreach ( $table as $value ) 
		{
			if ( $value == WP_INVENTORY_TABLE ) 
			{
				$wp_inventory_exists = true;
			}
			if($value == WP_TRANSACTION_TABLE)
			{
				$wp_ordermanagement_exists = true;
			}
		}
	}
	//check if we have a new installation, if we are passed a true value, just use that
	if(!$new_install)
	{
		$new_install = ($wp_inventory_exists == false && $wp_ordermanagement_exists == false);
	}

	//fresh install
	if ($new_install) 
	{		
		foxypress_Installation_CreateInventoryTable();
		foxypress_Installation_CreateInventoryCategoryTable();
		foxypress_Installation_CreateInventoryImagesTable();
		foxypress_Installation_CreateInventoryImagesDirectory();
		foxypress_Installation_CreateInventoryDownloadablesDirectory();
		foxypress_Installation_CreateConfigTable();
		foxypress_Installation_CreateTransactionTable();
		foxypress_Installation_CreateTransactionStatusTable();
		foxypress_Installation_CreateTransactionNoteTable();
		foxypress_Installation_CreateTransactionSyncTable();
		foxypress_Installation_CreateInventoryOptionsTable();
		foxypress_Installation_CreateInventoryOptionGroupsTable();
		foxypress_Installation_CreateInventoryAttributesTable();
		foxypress_Installation_CreateInventoryToCategoryTable();
		foxypress_Installation_CreateInventoryDownloadablesTable();
		foxypress_Installation_CreateDownloadTransactionTable();
		foxypress_Installation_CreateDownloadableDownloadTable();
		foxypress_Installation_CreateEncryptionSetting();
		foxypress_Installation_CreateProductDetailPage();
		foxypress_Installation_UpdateFoxyFile();
		//try verifying our installation
		foxypress_Installation_VerifyTableCount(true, $installation_attempts);
		return; //done son
	}

	//////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////ALTERATIONS/////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////

	if($wp_ordermanagement_exists == false)
	{
		//if we don't have any order management tables, we are pre 1.8, no config table yet
		foxypress_Installation_CreateConfigTable();
		foxypress_Installation_CreateTransactionTable();
		foxypress_Installation_CreateTransactionStatusTable();
		foxypress_Installation_CreateTransactionNoteTable();
		foxypress_Installation_CreateTransactionSyncTable();
		foxypress_Installation_CreateInventoryOptionsTable();
		foxypress_Installation_CreateInventoryOptionGroupsTable();
		foxypress_Installation_CreateInventoryAttributesTable();
		foxypress_Installation_CreateInventoryToCategoryTable();
		foxypress_Installation_CreateInventoryDownloadablesTable();
		foxypress_Installation_CreateDownloadTransactionTable();
		foxypress_Installation_CreateDownloadableDownloadTable();
		foxypress_Installation_CreateInventoryDownloadablesDirectory();
		foxypress_Installation_CreateEncryptionSetting();	
		foxypress_Installation_CreateProductDetailPage();	
		foxypress_Installation_AlterInventoryCategoryInformation();
		foxypress_Installation_DeleteDefaultImages();
		foxypress_Installation_AlterInventoryImagesTable();
		foxypress_Installation_AlterInventoryTableMinMaxQuantity();
		foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();
	}
	else // we are at least version 1.8
	{
		//we have some modifications to do.
		//version 0.1.8 - added all transaction tables/functionality (config table didnt exist yet)
		//version 0.1.9 - added foxypress config table, added is_test column to transaction table
		//version 0.2.0 - added options tables for inventory
		//version 0.2.5 - added date and total to transaction table
		//version 0.2.9 - added digital downloads
		$ConfigTableExists = false;
		$tables = $wpdb->get_results("show tables;");
		foreach ( $tables as $table ) {
			foreach ( $table as $value ) {
				if($value == WP_FOXYPRESS_CONFIG_TABLE)
				{
					$ConfigTableExists = true;
					break;
				}
			}
		}

		if($ConfigTableExists == false) //pre 1.9
		{
			foxypress_Installation_CreateConfigTable();
			foxypress_Installation_AlterTransactionTable();
			foxypress_Installation_CreateInventoryOptionsTable();
			foxypress_Installation_CreateInventoryOptionGroupsTable();
			foxypress_Installation_CreateInventoryAttributesTable();
			foxypress_Installation_CreateInventoryToCategoryTable();
			foxypress_Installation_CreateInventoryDownloadablesTable();
			foxypress_Installation_CreateDownloadTransactionTable();
			foxypress_Installation_CreateDownloadableDownloadTable();
			foxypress_Installation_CreateInventoryDownloadablesDirectory();
			foxypress_Installation_CreateEncryptionSetting();	
			foxypress_Installation_AlterInventoryCategoryInformation();
			foxypress_Installation_DeleteDefaultImages();
			foxypress_Installation_AlterInventoryImagesTable();
			foxypress_Installation_CreateProductDetailPage();	
			foxypress_Installation_AlterInventoryTableMinMaxQuantity();		
			foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();	
		}
		else
		{
			//get current version
			$drCurrentVersion = $wpdb->get_row("SELECT foxy_current_version from " . WP_FOXYPRESS_CONFIG_TABLE);
			if($drCurrentVersion->foxy_current_version == "0.1.9")
			{
				foxypress_Installation_CreateInventoryOptionsTable();
				foxypress_Installation_CreateInventoryOptionGroupsTable();
				foxypress_Installation_CreateInventoryAttributesTable();
				foxypress_Installation_CreateInventoryToCategoryTable();
				foxypress_Installation_CreateInventoryDownloadablesTable();
				foxypress_Installation_CreateDownloadTransactionTable();
				foxypress_Installation_CreateDownloadableDownloadTable();
				foxypress_Installation_CreateInventoryDownloadablesDirectory();
				foxypress_Installation_CreateEncryptionSetting();
				foxypress_Installation_AlterInventoryCategoryInformation();							
				foxypress_Installation_DeleteDefaultImages();
				foxypress_Installation_AlterInventoryImagesTable();
				foxypress_Installation_AlterTransactionTable();
				foxypress_Installation_CreateProductDetailPage();	
				foxypress_Installation_AlterInventoryTableMinMaxQuantity();
				foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();
				foxypress_Installation_UpdateCurrentVersion();	
			}
			else if(
						$drCurrentVersion->foxy_current_version == "0.2.0" ||
						$drCurrentVersion->foxy_current_version == "0.2.1" || 
						$drCurrentVersion->foxy_current_version == "0.2.2" || 
						$drCurrentVersion->foxy_current_version == "0.2.3" || 
						$drCurrentVersion->foxy_current_version == "0.2.4"
					)
			{
				foxypress_Installation_CreateInventoryDownloadablesTable();
				foxypress_Installation_CreateDownloadTransactionTable();
				foxypress_Installation_CreateDownloadableDownloadTable();
				foxypress_Installation_CreateInventoryDownloadablesDirectory();
				foxypress_Installation_CreateEncryptionSetting();
				foxypress_Installation_AlterTransactionTable();
				foxypress_Installation_AlterInventoryOptionsTable();
				foxypress_Installation_AlterInventoryOptionsTableAddWeight();
				foxypress_Installation_AlterInventoryOptionsTableAddCodeAndQuantity();
				foxypress_Installation_AlterInventoryImagesTable();
				foxypress_Installation_AlterInventoryCategoryOrderInformation();
				foxypress_Installation_AlterInventoryTableMinMaxQuantity();	
				foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();			
				foxypress_Installation_UpdateCurrentVersion();
			}
			else if(
						$drCurrentVersion->foxy_current_version == "0.2.5" ||
						$drCurrentVersion->foxy_current_version == "0.2.6" || 
						$drCurrentVersion->foxy_current_version == "0.2.7" || 
						$drCurrentVersion->foxy_current_version == "0.2.8"
					)
			{
				foxypress_Installation_CreateInventoryDownloadablesTable();
				foxypress_Installation_CreateDownloadTransactionTable();
				foxypress_Installation_CreateDownloadableDownloadTable();
				foxypress_Installation_CreateInventoryDownloadablesDirectory();
				foxypress_Installation_CreateEncryptionSetting();
				foxypress_Installation_AlterInventoryCategoryOrderInformation();
				foxypress_Installation_AlterInventoryTableMinMaxQuantity();
				foxypress_Installation_AlterInventoryOptionsTableAddWeight();
				foxypress_Installation_AlterInventoryOptionsTableAddCodeAndQuantity();
				foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();
				foxypress_Installation_UpdateCurrentVersion();
			}
			else if (
					$drCurrentVersion->foxy_current_version == "0.2.9" ||
					$drCurrentVersion->foxy_current_version == "0.3.0" 
				)
			{
				foxypress_Installation_AlterInventoryOptionsTableAddWeight();
				foxypress_Installation_AlterInventoryOptionsTableAddCodeAndQuantity();
				foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();
				foxypress_Installation_UpdateCurrentVersion();
			}
			else if(
				$drCurrentVersion->foxy_current_version == "0.3.1"
			)
			{
				foxypress_Installation_AlterInventoryOptionsTableAddCodeAndQuantity();	
				foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates();
				foxypress_Installation_UpdateCurrentVersion();
			}
		}
	}
	//try verifying our installation/updates
	foxypress_Installation_VerifyTableCount(false, $installation_attempts);
	foxypress_Installation_UpdateFoxyFile(); //done son
}

function foxypress_Installation_VerifyTableCount($new_install, $installation_attempts)
{
	global $wpdb;
	$foxyTableCount = 0;
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table ) 
	{
		foreach ( $table as $value ) 
		{
			$foxyTable = strpos($value, "foxypress");			
			if($foxyTable === false) { }
			else
			{
				$foxyTableCount++;
			}
		}
	}
	//verify that we have the correct number of tables
	if($foxyTableCount < 15)
	{
		//lets only try to recreate this a few times so were not stuck in an infinite loop
		$installation_attempts++;
		if($installation_attempts < 5)
		{
			foxypress_Install($new_install, $installation_attempts);
		}
	}	
}

function foxypress_Installation_CanRunUpdates()
{
	$foxyUpdateFile =  WP_PLUGIN_DIR . "/foxypress/foxy.txt";
	$fh = fopen($foxyUpdateFile, 'r');
	$Run = fread($fh, filesize($foxyUpdateFile));
	if($Run == "1")
	{
		return true;
	}
	return false;
}

function foxypress_Installation_CreateInventoryTable()
{
	global $wpdb;
	//currently inventory_image is not used in this table
	//date added is actually going to be when the product is available
	$sql = "CREATE TABLE " . WP_INVENTORY_TABLE . " (
				inventory_id INT(11) NOT NULL AUTO_INCREMENT ,
				date_added INT(11) NOT NULL ,
				inventory_code VARCHAR(30) NOT NULL,
				inventory_name VARCHAR(100) NOT NULL,
				inventory_description TEXT NOT NULL,
				inventory_weight VARCHAR(30) NULL,
				inventory_quantity INT(11) DEFAULT 0,
				inventory_quantity_max INT(11) DEFAULT 0,
				inventory_quantity_min INT(11) DEFAULT 0,
				category_id INT(11) NULL,
				inventory_price FLOAT(10, 2) NOT NULL,
				inventory_image TEXT NULL,
				inventory_sale_price FLOAT(10, 2) NULL,
				inventory_sale_start DATE NULL,
				inventory_sale_end DATE NULL,
				inventory_discount_quantity_amount VARCHAR(100) NULL,
				inventory_discount_quantity_percentage VARCHAR(100) NULL,
				inventory_discount_price_amount VARCHAR(100) NULL,
				inventory_discount_price_percentage VARCHAR(100) NULL,
				inventory_start_date DATE NULL,
				inventory_end_date DATE NULL,
				inventory_active TINYINT(1) DEFAULT 1,
				PRIMARY KEY (inventory_id)
			)";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryCategoryTable()
{
	global $wpdb;
	$sql = "CREATE TABLE " . WP_INVENTORY_CATEGORIES_TABLE . " (
				category_id INT(11) NOT NULL AUTO_INCREMENT,
				category_name VARCHAR(30) NOT NULL ,
				PRIMARY KEY (category_id)
			)";
	$wpdb->query($sql);			
	//insert default data
	$sql = "INSERT INTO " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_id=1, category_name='General'";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryImagesTable()
{
	global $wpdb;
	$sql = "CREATE TABLE " . WP_INVENTORY_IMAGES_TABLE . " (
				 inventory_images_id INT(11) NOT NULL AUTO_INCREMENT ,
				 inventory_id INT(11) NOT NULL ,
				 inventory_image TEXT NULL,
				 image_order int DEFAULT '99',
				 PRIMARY KEY (inventory_images_id)
			 )";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateInventoryImagesDirectory()
{
	//create images folder and copy default image
	$inventoryfolder = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
	$defaultImage = WP_PLUGIN_DIR . '/foxypress/img/' . INVENTORY_DEFAULT_IMAGE;
	if(!is_dir($inventoryfolder))
	{
		mkdir($inventoryfolder, 0777);
		chmod($inventoryfolder, 0777);
	}
	if (file_exists($defaultImage))
	{
		copy($defaultImage, ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . INVENTORY_DEFAULT_IMAGE);
	}
	else
	{
		echo 'files does not exist at plugin directory';
	}	
}

function foxypress_Installation_CreateInventoryDownloadablesDirectory()
{	
	//create downloadables folder
	$downlodablefolder = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR;
	if(!is_dir($downlodablefolder))
	{
		mkdir($downlodablefolder, 0777);
		chmod($downlodablefolder, 0777);
	}				
}

function foxypress_Installation_CreateConfigTable()
{
	global $wpdb;
	//create config table
	$sql = "CREATE TABLE " . WP_FOXYPRESS_CONFIG_TABLE . " (
			foxy_current_version VARCHAR(10) NOT NULL
		)";
	$wpdb->query($sql);	
	//insert the current version
	$sql = "INSERT INTO " . WP_FOXYPRESS_CONFIG_TABLE . " (foxy_current_version) values ('" . WP_FOXYPRESS_CURRENT_VERSION . "')";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateTransactionTable()
{	
	global $wpdb;
	//create main transaction table to hold data that gets synched up.
	$sql = "CREATE TABLE " . WP_TRANSACTION_TABLE . " (
			foxy_transaction_id INT(11) NOT NULL PRIMARY KEY,
			foxy_transaction_status VARCHAR(30) NOT NULL,
			foxy_transaction_first_name VARCHAR(50) NULL,
			foxy_transaction_last_name VARCHAR(50) NULL,
			foxy_transaction_email VARCHAR(50) NULL,
			foxy_transaction_trackingnumber VARCHAR(100) NULL,
			foxy_transaction_billing_address1 VARCHAR(50) NULL,
			foxy_transaction_billing_address2 VARCHAR(50) NULL,
			foxy_transaction_billing_city VARCHAR(50) NULL,
			foxy_transaction_billing_state VARCHAR(2) NULL,
			foxy_transaction_billing_zip VARCHAR(10) NULL,
			foxy_transaction_billing_country VARCHAR(50) NULL,
			foxy_transaction_shipping_address1 VARCHAR(50) NULL,
			foxy_transaction_shipping_address2 VARCHAR(50) NULL,
			foxy_transaction_shipping_city VARCHAR(50) NULL,
			foxy_transaction_shipping_state VARCHAR(2) NULL,
			foxy_transaction_shipping_zip VARCHAR(10) NULL,
			foxy_transaction_shipping_country VARCHAR(50) NULL,
			foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0',
			foxy_transaction_date DATETIME,
			foxy_transaction_product_total FLOAT(10, 2),
			foxy_transaction_tax_total FLOAT(10, 2),
			foxy_transaction_shipping_total FLOAT(10, 2),
			foxy_transaction_order_total FLOAT(10, 2),
			foxy_transaction_cc_type varchar(50)
		)";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateTransactionStatusTable()
{
	global $wpdb;
	//create custom status table
	$sql = "CREATE TABLE " . WP_TRANSACTION_STATUS_TABLE . " (
			foxy_transaction_status INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			foxy_transaction_status_description VARCHAR(50) NULL,
			foxy_transaction_status_email_flag tinyint(1) NOT NULL DEFAULT '0',
			foxy_transaction_status_email_subject TEXT NULL,
			foxy_transaction_status_email_body TEXT NULL,
			foxy_transaction_status_email_tracking tinyint(1) NOT NULL DEFAULT '0'
		)";
	$wpdb->query($sql);
	//insert the default category
	$sql = "INSERT INTO " . WP_TRANSACTION_STATUS_TABLE . " (foxy_transaction_status, foxy_transaction_status_description) values ('1', 'Uncategorized')";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateTransactionNoteTable()
{
	global $wpdb;
	//create transaction note table
	$sql = "CREATE TABLE " . WP_TRANSACTION_NOTE_TABLE . " (
				foxy_transaction_note_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_transaction_id INT(11) NOT NULL,
				foxy_transaction_note TEXT NOT NULL,
				foxy_transaction_entered_by VARCHAR(30),
				foxy_transaction_date_entered DATE
			)";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateTransactionSyncTable()
{
	global $wpdb;
	//create sync table to keep track of when the last time we synched
	$sql = "CREATE TABLE " . WP_TRANSACTION_SYNC_TABLE . " (
			foxy_transaction_sync_date DATE,
			foxy_transaction_sync_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)";

	$wpdb->query($sql);
	//insert default value
	$sql = "INSERT INTO " . WP_TRANSACTION_SYNC_TABLE . " (foxy_transaction_sync_date, foxy_transaction_sync_timestamp ) values ('1900-01-01', now())";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateInventoryOptionsTable()
{	
	global $wpdb;
	//create options table
	$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (
				option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL ,
				option_group_id INT(11) NOT NULL ,
				option_text VARCHAR(50) NOT NULL ,
				option_value VARCHAR(50) NOT NULL ,
				option_extra_price FLOAT(10,2) NOT NULL DEFAULT '0',
				option_extra_weight FLOAT(10,2) NOT NULL DEFAULT '0',
				option_code VARCHAR(30) NULL,
				option_quantity INT(11) NULL,
				option_active TINYINT NOT NULL DEFAULT '1',
				option_order INT DEFAULT '99'
		   ) ";
	$wpdb->query($sql);
}

function foxypress_Installation_CreateInventoryOptionGroupsTable()
{
	global $wpdb;
	//create options group table
	$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (
				option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				option_group_name VARCHAR(50) NOT NULL
			)";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateInventoryAttributesTable()
{
	global $wpdb;
	//create custom inventory attributes
	$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (
				attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL ,
				attribute_text VARCHAR(50) NOT NULL ,
				attribute_value VARCHAR(50) NOT NULL
		   ) ";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateInventoryToCategoryTable()
{
	global $wpdb;
	//create inventory to category table
	$sql = "CREATE TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " (
				itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL,
				category_id INT(11) NOT NULL,
				sort_order INT(11) NOT NULL DEFAULT '99'
		   ) ";
	$wpdb->query($sql);			
}

function foxypress_Installation_CreateInventoryDownloadablesTable()
{
	global $wpdb;
	//create inventory downloadables table
	$sql = "CREATE TABLE " . WP_INVENTORY_DOWNLOADABLES . " (
				downloadable_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				inventory_id INT(11) NOT NULL,
				filename varchar(255) NOT NULL,
				maxdownloads INT(11) NOT NULL DEFAULT '0',
				status INT(11) NOT NULL DEFAULT '1'
		   ) ";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateDownloadTransactionTable()
{
	global $wpdb;
	//create inventory download transation table, this id will be unique per download (the id used for the link)
	$sql = "CREATE TABLE " . WP_DOWNLOADABLE_TRANSACTION . " (
				download_transaction_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				foxy_transaction_id INT(11) NOT NULL,
				downloadable_id INT(11) NOT NULL,
				download_count INT(11) NOT NULL DEFAULT '0'
		   ) ";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateDownloadableDownloadTable()
{
	global $wpdb;
	//create downloadable downloads table
	$sql = "CREATE TABLE " . WP_DOWNLOADABLE_DOWNLOAD . " (
				download_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				download_transaction_id INT(11) NOT NULL,
				download_date DATETIME,
				ip_address varchar(25),
				referrer varchar(255)
		   ) ";
	$wpdb->query($sql);	
}

function foxypress_Installation_AlterInventoryCategoryOrderInformation()
{
	//we need to add sort_order to the inventory to category table
	global $wpdb;
	$sql = "ALTER TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " ADD sort_order int DEFAULT '99' AFTER category_id";
	$wpdb->query($sql);	
}

function foxypress_Installation_CreateEncryptionSetting()
{
	//create a setting for our encryption key
	add_option("foxypress_encryption_key", foxypress_GenerateRandomString(10), '', 'no');	
}

function foxypress_Installation_CreateProductDetailPage()
{
	global $wpdb;
	//check to see if the detail page exists already
	$sql = "select ID from " . WP_POSTS . " where post_name='foxy-product-detail'";
	$foxydetail = $wpdb->get_row($sql);
	if(empty($foxydetail))
	{
		get_currentuserinfo();
		$ProductDetailPost = array(
		  "comment_status" => "open",
		  "ping_status" => "open",
		  "post_author" => $current_user->ID, //The user ID number of the author.
		  "post_content" => "[foxypress mode='detail']FoxyPress[/foxypress]",
		  "post_name" => "foxy-product-detail", // The name (slug) for your post
		  "post_status" => "publish",
		  "post_title" => "",
		  "post_type" => "page"
		);
		wp_insert_post( $ProductDetailPost );
	}	
}

function foxypress_Installation_UpdateFoxyFile()
{
	//update foxy.txt
	$foxyUpdateFile = WP_PLUGIN_DIR . "/foxypress/foxy.txt";
	$fh = fopen($foxyUpdateFile, 'w');
	fwrite($fh, "0");
	fclose($fh);	
}

function foxypress_Installation_AlterInventoryCategoryInformation()
{
	global $wpdb;
	//if we have current category data we need to pull that over to our new table
	$sql = "insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id)
			select inventory_id, category_id from " . WP_INVENTORY_TABLE;
	$wpdb->query($sql);
	//trunc our current category id in the inventory table
	$sql = "update  " . WP_INVENTORY_TABLE . " set category_id=''";
	$wpdb->query($sql);
}

function foxypress_Installation_DeleteDefaultImages()
{
	global $wpdb;
	//delete rows with default proudct image
	$sql = "delete from  " . WP_INVENTORY_IMAGES_TABLE . " where inventory_image='default-product-image.jpg'";
	$wpdb->query($sql);
}

function foxypress_Installation_AlterInventoryImagesTable()
{
	global $wpdb;
	//add image_order to images table
	$sql = "ALTER TABLE " . WP_INVENTORY_IMAGES_TABLE . " ADD image_order int DEFAULT '99' AFTER inventory_image";
	$wpdb->query($sql);
}

function foxypress_Installation_AlterTransactionTable()
{
	global $wpdb;
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0' AFTER foxy_transaction_shipping_country;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_date DATETIME AFTER foxy_transaction_is_test;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_product_total FLOAT(10, 2) AFTER foxy_transaction_date;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_tax_total FLOAT(10, 2) AFTER foxy_transaction_product_total;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_shipping_total FLOAT(10, 2) AFTER foxy_transaction_tax_total;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_order_total FLOAT(10, 2) AFTER foxy_transaction_shipping_total;";
	$wpdb->query($sql);
	$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_cc_type varchar(50) AFTER foxy_transaction_order_total;";
	$wpdb->query($sql);
}

function foxypress_Installation_UpdateCurrentVersion()
{
	 global $wpdb;
	//update to current version
	$sql = "UPDATE " . WP_FOXYPRESS_CONFIG_TABLE . " SET foxy_current_version = '" . WP_FOXYPRESS_CURRENT_VERSION . "'";
	$wpdb->query($sql);	
}

function foxypress_Installation_AlterInventoryOptionsTable()
{
	global $wpdb;
	//add sort order to options table
	$sql = "ALTER TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " ADD option_order INT DEFAULT '99' AFTER option_active;";
	$wpdb->query($sql);	
}

function foxypress_Installation_AlterInventoryOptionsTableAddWeight()
{
	global $wpdb;
	//add extra weight
	$sql = "ALTER TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " ADD option_extra_weight FLOAT(10,2) NOT NULL DEFAULT '0' AFTER option_extra_price";
	$wpdb->query($sql);
}

function foxypress_Installation_AlterInventoryOptionsTableAddCodeAndQuantity()
{
	global $wpdb;
	//add unique code
	$sql = "ALTER TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " ADD option_code VARCHAR(30) NULL AFTER option_extra_weight";
	$wpdb->query($sql);
	//add quantity
	$sql = "ALTER TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " ADD option_quantity INT(11) NULL AFTER option_code";
	$wpdb->query($sql);
}

function foxypress_Installation_AlterInventoryTableMinMaxQuantity()
{
	global $wpdb;
	//add min and max qty to inventory table
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_quantity_max INT DEFAULT '0' AFTER inventory_quantity;";
	$wpdb->query($sql);	
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_quantity_min INT DEFAULT '0' AFTER inventory_quantity_max;";
	$wpdb->query($sql);	
}

function foxypress_Installation_AlterInventoryTableAddSalesDiscountsDates()
{
	global $wpdb;
	//add sale fields, add discount fields, add start/end/active fields for item
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_sale_price FLOAT(10, 2) NULL AFTER inventory_image;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_sale_start DATE NULL AFTER inventory_sale_price;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_sale_end DATE NULL AFTER inventory_sale_start;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_discount_quantity_amount VARCHAR(100) NULL AFTER inventory_sale_end;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_discount_quantity_percentage VARCHAR(100) NULL AFTER inventory_discount_quantity_amount;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_discount_price_amount VARCHAR(100) NULL AFTER inventory_discount_quantity_percentage;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_discount_price_percentage VARCHAR(100) NULL AFTER inventory_discount_price_amount;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_start_date DATE NULL AFTER inventory_discount_price_percentage;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_end_date DATE NULL AFTER inventory_start_date;";
	$wpdb->query($sql);		
	$sql = "ALTER TABLE " . WP_INVENTORY_TABLE . " ADD inventory_active TINYINT(1) DEFAULT 1 AFTER inventory_end_date;";
	$wpdb->query($sql);	
	//update existing items to be active
	$wpdb->query("UPDATE " . WP_INVENTORY_TABLE . " SET inventory_active='1'");	
}

?>