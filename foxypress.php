<?php /*

**************************************************************************
Plugin Name: FoxyPress
Plugin URI: http://www.foxy-press.com/
Description: This free plugin allows you to easily add products to your WordPress pages using FoxyCart as your shopping cart solution. Manage inventories, set product options and organize transactions all within WordPress using a convenient WYSIWYG toolbar icon.
Author: WebMovement, LLC
Version: 0.2.1
Author URI: http://www.webmovementllc.com/

**************************************************************************

Copyright (C) 2008-2011 WebMovement, LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************

In short, this plugin is free to use by anyone and everyone. You are
welcome to use it on a commercial site or whatever you want. However, I do
very much appreciate donations for all of my time and effort, although
they obviously aren't required for you to use this plugin.

If you sell this code (i.e. are a web developer selling features provided
via this plugin to clients), it would be very nice if you threw some of
your profits my way. After all, you are profiting off my hard work. ;)

Thanks and enjoy this plugin!

**************************************************************************/

include_once( 'settings.php' );
global $foxypress_url;
$foxypress_url = get_option('foxycart_storeurl');
if ( !empty ( $foxypress_url ) ){
	// init process for button control
	add_action('init', 'myplugin_addbuttons');
	add_action('wp_head', 'foxypress_importFoxyScripts' );
}
add_shortcode('foxypress', 'foxypress_shortcode');
add_action( 'admin_menu', 'foxypress_add_menu' );
add_action('admin_init', 'foxypress_managetables');

// foxypress constants
define('WP_TRANSACTION_TABLE', $table_prefix . 'foxypress_transaction');
define('WP_TRANSACTION_NOTE_TABLE', $table_prefix . 'foxypress_transaction_note');
define('WP_TRANSACTION_SYNC_TABLE', $table_prefix . 'foxypress_transaction_sync');
define('WP_TRANSACTION_STATUS_TABLE', $table_prefix . 'foxypress_transaction_status');
define('WP_FOXYPRESS_CONFIG_TABLE', $table_prefix . 'foxypress_config');
define('WP_FOXYPRESS_INVENTORY_OPTIONS', $table_prefix . 'foxypress_inventory_options');
define('WP_FOXYPRESS_INVENTORY_ATTRIBUTES', $table_prefix . 'foxypress_inventory_attributes');
define('WP_FOXYPRESS_INVENTORY_OPTION_GROUP', $table_prefix . 'foxypress_inventory_option_group');
define('WP_INVENTORY_TABLE', $table_prefix . 'foxypress_inventory');
define('WP_INVENTORY_CATEGORIES_TABLE', $table_prefix . 'foxypress_inventory_categories');
define('WP_INVENTORY_TO_CATEGORY_TABLE', $table_prefix . 'foxypress_inventory_to_category');
define('WP_INVENTORY_IMAGES_TABLE', $table_prefix . 'foxypress_inventory_images');
define('WP_POSTS', $table_prefix . 'posts');
define('INVENTORY_IMAGE_DIR', get_bloginfo("url") . "/wp-content/inventory_images");
define('INVENTORY_IMAGE_LOCAL_DIR', "wp-content/inventory_images/");
define('INVENTORY_DEFAULT_IMAGE', "default-product-image.jpg");

if ( !empty ( $foxypress_url ) ){
	// Include inventory settings and functionality \\
	include_once('inventory.php');
	include_once('inventory-category.php');
	include_once('inventory-option-groups.php');
	include_once('order-management.php');
	include_once('status-management.php');
}

function foxypress_RunUpdates()
{
	$foxyUpdateFile = ABSPATH . "wp-content/plugins/foxypress/foxy.txt";
	$fh = fopen($foxyUpdateFile, 'r');
	$Run = fread($fh, filesize($foxyUpdateFile));
	if($Run == "1")
	{
		return true;
	}
	return false;
}

function foxypress_managetables()
{
	 global $wpdb;

	 if(!foxypress_RunUpdates()) { return; }
	 $new_install = false;
	 $wp_inventory_exists = false;
	 $wp_ordermanagement_exists = false;
	// Determine the version
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table ) {
		foreach ( $table as $value ) {
			if ( $value == WP_INVENTORY_TABLE ) {
				$wp_inventory_exists = true;
			}
			if($value == WP_TRANSACTION_TABLE)
			{
				$wp_ordermanagement_exists = true;
			}
		}
	}

	//fresh install
	if ($wp_inventory_exists == false && $wp_ordermanagement_exists == false) {
		//create inventory table
		//currently inventory_image is not used in this table, it shouldn't be here (they belong in the inventory-images table.
		//But we may able to salvage this for use later on as the default image if we start doing multiple images
		//date added is actually going to be when the product is available
		$sql = "CREATE TABLE " . WP_INVENTORY_TABLE . " (
					inventory_id INT(11) NOT NULL AUTO_INCREMENT ,
					date_added INT(11) NOT NULL ,
					inventory_code VARCHAR(30) NOT NULL,
					inventory_name VARCHAR(100) NOT NULL,
					inventory_description TEXT NOT NULL,
					inventory_weight VARCHAR(30) NULL,
					inventory_quantity INT(11) DEFAULT 0,
					category_id INT(11) NOT NULL,
					inventory_price FLOAT(10, 2) NOT NULL,
					inventory_image TEXT NULL,
					PRIMARY KEY (inventory_id)
				)";
		$wpdb->query($sql);
		//create inventory categories
		 $sql = "CREATE TABLE " . WP_INVENTORY_CATEGORIES_TABLE . " (
					category_id INT(11) NOT NULL AUTO_INCREMENT,
					category_name VARCHAR(30) NOT NULL ,
					PRIMARY KEY (category_id)
				)";
		$wpdb->query($sql);
		//insert default data
		$sql = "INSERT INTO " . WP_INVENTORY_CATEGORIES_TABLE . " SET category_id=1, category_name='General'";
		$wpdb->query($sql);
		//create images table
		$sql = "CREATE TABLE " . WP_INVENTORY_IMAGES_TABLE . " (
				 inventory_images_id INT(11) NOT NULL AUTO_INCREMENT ,
				 inventory_id INT(11) NOT NULL ,
				 inventory_image TEXT NULL,
				 PRIMARY KEY (inventory_images_id)
			 )";
		$wpdb->query($sql);
		//copy default image
		$inventoryfolder = ABSPATH . INVENTORY_IMAGE_LOCAL_DIR;
		$defaultImage = ABSPATH . 'wp-content/plugins/foxypress/img/' . INVENTORY_DEFAULT_IMAGE;
		if(!is_dir($inventoryfolder))
		{
			mkdir($inventoryfolder, 0777);
		}
		if (file_exists($defaultImage))
		{
			copy($defaultImage, ABSPATH . INVENTORY_IMAGE_LOCAL_DIR . INVENTORY_DEFAULT_IMAGE);
		}
		else
		{
			echo 'files does not exist at plugin directory';
		}

		//create config table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_CONFIG_TABLE . " (
				foxy_current_version VARCHAR(10) NOT NULL
			)";
		$wpdb->query($sql);
		//insert the current version
		$sql = "INSERT INTO " . WP_FOXYPRESS_CONFIG_TABLE . " (foxy_current_version) values ('0.2.0')";
		$wpdb->query($sql);

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
				foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0'
			)";
		$wpdb->query($sql);

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

		//create transaction note table
		$sql = "CREATE TABLE " . WP_TRANSACTION_NOTE_TABLE . " (
					foxy_transaction_note_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					foxy_transaction_id INT(11) NOT NULL,
					foxy_transaction_note TEXT NOT NULL,
					foxy_transaction_entered_by VARCHAR(30),
					foxy_transaction_date_entered DATE
				)";
		$wpdb->query($sql);

		//create sync table to keep track of when the last time we synched
		$sql = "CREATE TABLE " . WP_TRANSACTION_SYNC_TABLE . " (
				foxy_transaction_sync_date DATE,
				foxy_transaction_sync_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			)";
		$wpdb->query($sql);
		//insert default value
		$sql = "INSERT INTO " . WP_TRANSACTION_SYNC_TABLE . " (foxy_transaction_sync_date, foxy_transaction_sync_timestamp ) values ('1900-01-01', now())";
		$wpdb->query($sql);


		//create options table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (
					option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL ,
					option_group_id INT(11) NOT NULL ,
					option_text VARCHAR(50) NOT NULL ,
					option_value VARCHAR(50) NOT NULL ,
					option_extra_price FLOAT(10, 2) NOT NULL DEFAULT '0',
					option_active TINYINT NOT NULL DEFAULT '1'
			   ) ";
		$wpdb->query($sql);

		//create options group table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (
					option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					option_group_name VARCHAR(50) NOT NULL
				)";
		$wpdb->query($sql);


		//create custom inventory attributes
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (
					attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL ,
					attribute_text VARCHAR(50) NOT NULL ,
					attribute_value VARCHAR(50) NOT NULL
			   ) ";
		$wpdb->query($sql);

		//create inventory to category table
		$sql = "CREATE TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " (
					itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL,
					category_id INT(11) NOT NULL
			   ) ";
		$wpdb->query($sql);

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

		//update foxy.txt
		$foxyUpdateFile = ABSPATH . "wp-content/plugins/foxypress/foxy.txt";
		$fh = fopen($foxyUpdateFile, 'w');
		fwrite($fh, "0");
		fclose($fh);

		return;
	} //end if (new install)

	//////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////ALTERATIONS/////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////

	if($wp_ordermanagement_exists == false)
	{
		//if we don't have any order management tables, we are pre 1.8, no config table yet

		//create config table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_CONFIG_TABLE . " (
				foxy_current_version VARCHAR(10) NOT NULL
			)";
		$wpdb->query($sql);
		//insert the current version
		$sql = "INSERT INTO " . WP_FOXYPRESS_CONFIG_TABLE . " (foxy_current_version) values ('0.2.0')";
		$wpdb->query($sql);

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
				foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0'
			)";
		$wpdb->query($sql);

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

		//create transaction note table
		$sql = "CREATE TABLE " . WP_TRANSACTION_NOTE_TABLE . " (
					foxy_transaction_note_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					foxy_transaction_id INT(11) NOT NULL,
					foxy_transaction_note TEXT NOT NULL,
					foxy_transaction_entered_by VARCHAR(30),
					foxy_transaction_date_entered DATE
				)";
		$wpdb->query($sql);

		//create sync table to keep track of when the last time we synched
		$sql = "CREATE TABLE " . WP_TRANSACTION_SYNC_TABLE . " (
				foxy_transaction_sync_date DATE,
				foxy_transaction_sync_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			)";
		$wpdb->query($sql);
		//insert default value
		$sql = "INSERT INTO " . WP_TRANSACTION_SYNC_TABLE . " (foxy_transaction_sync_date, foxy_transaction_sync_timestamp ) values ('1900-01-01', now())";
		$wpdb->query($sql);


		//create options table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (
					option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL ,
					option_group_id INT(11) NOT NULL ,
					option_text VARCHAR(50) NOT NULL ,
					option_value VARCHAR(50) NOT NULL ,
					option_extra_price FLOAT(10, 2) NOT NULL DEFAULT '0',
					option_active TINYINT NOT NULL DEFAULT '1'
			   ) ";
		$wpdb->query($sql);

		//create options group table
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (
					option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					option_group_name VARCHAR(50) NOT NULL
				)";
		$wpdb->query($sql);


		//create custom inventory attributes
		$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (
					attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL ,
					attribute_text VARCHAR(50) NOT NULL ,
					attribute_value VARCHAR(50) NOT NULL
			   ) ";
		$wpdb->query($sql);

		//create inventory to category table
		$sql = "CREATE TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " (
					itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					inventory_id INT(11) NOT NULL,
					category_id INT(11) NOT NULL
			   ) ";
		$wpdb->query($sql);

		//if we have current category data we need to pull that over to our new table
		$sql = "insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id)
				select inventory_id, category_id from " . WP_INVENTORY_TABLE;
		$wpdb->query($sql);

		//trunc our current category id in the inventory table
		$sql = "update  " . WP_INVENTORY_TABLE . " set category_id=''";
		$wpdb->query($sql);

		//delete rows with default proudct image
		$sql = "delete from  " . WP_INVENTORY_IMAGES_TABLE . " where inventory_image='default-product-image.jpg'";
		$wpdb->query($sql);

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
	else // we are at least version 1.8
	{
		//we have some modifications to do.
		//version 0.1.8 - added all transaction tables/functionality (config table didnt exist yet)
		//version 0.1.9 - added foxypress config table, added is_test column to transaction table
		//version 0.2.0 - added options tables for inventory
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
			//create config table
			$sql = "CREATE TABLE " . WP_FOXYPRESS_CONFIG_TABLE . " (
						foxy_current_version VARCHAR(10) NOT NULL
					)";
			$wpdb->query($sql);
			//insert the current version
			$sql = "INSERT INTO " . WP_FOXYPRESS_CONFIG_TABLE . " (foxy_current_version) values ('0.2.0')";
			$wpdb->query($sql);


			//alter current tables
			$sql = "ALTER TABLE " . WP_TRANSACTION_TABLE . " ADD foxy_transaction_is_test tinyint(1) NOT NULL DEFAULT '0' AFTER foxy_transaction_shipping_country";
			$wpdb->query($sql);

			//create options table
			$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (
						option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						inventory_id INT(11) NOT NULL ,
						option_group_id INT(11) NOT NULL ,
						option_text VARCHAR(50) NOT NULL ,
						option_value VARCHAR(50) NOT NULL ,
						option_extra_price FLOAT(10, 2) NOT NULL DEFAULT '0',
						option_active TINYINT NOT NULL DEFAULT '1'
				   ) ";
			$wpdb->query($sql);

			//create options group table
			$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (
						option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						option_group_name VARCHAR(50) NOT NULL
					)";
			$wpdb->query($sql);

			//create custom inventory attributes
			$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (
						attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						inventory_id INT(11) NOT NULL ,
						attribute_text VARCHAR(50) NOT NULL ,
						attribute_value VARCHAR(50) NOT NULL
				   ) ";
			$wpdb->query($sql);

			//create inventory to category table
			$sql = "CREATE TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " (
						itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						inventory_id INT(11) NOT NULL,
						category_id INT(11) NOT NULL
				   ) ";
			$wpdb->query($sql);

			//if we have current category data we need to pull that over to our new table
			$sql = "insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id)
					select inventory_id, category_id from " . WP_INVENTORY_TABLE;
			$wpdb->query($sql);

			//trunc our current category id in the inventory table
			$sql = "update  " . WP_INVENTORY_TABLE . " set category_id=''";
			$wpdb->query($sql);

			//delete rows with default proudct image
			$sql = "delete from  " . WP_INVENTORY_IMAGES_TABLE . " where inventory_image='default-product-image.jpg'";
			$wpdb->query($sql);

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
		else
		{
			//get current version
			$drCurrentVersion = $wpdb->get_row("SELECT foxy_current_version from " . WP_FOXYPRESS_CONFIG_TABLE);
			if($drCurrentVersion->foxy_current_version == "0.1.9")
			{

				//create options table
				$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTIONS . " (
							option_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							inventory_id INT(11) NOT NULL ,
							option_group_id INT(11) NOT NULL ,
							option_text VARCHAR(50) NOT NULL ,
							option_value VARCHAR(50) NOT NULL ,
							option_extra_price FLOAT(10, 2) NOT NULL DEFAULT '0',
							option_active TINYINT NOT NULL DEFAULT '1'
					   ) ";
				$wpdb->query($sql);

				//create options group table
				$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " (
							option_group_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
							option_group_name VARCHAR(50) NOT NULL
						)";
				$wpdb->query($sql);


				//create custom inventory attributes
				$sql = "CREATE TABLE " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . " (
							attribute_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							inventory_id INT(11) NOT NULL ,
							attribute_text VARCHAR(50) NOT NULL ,
							attribute_value VARCHAR(50) NOT NULL
					   ) ";
				$wpdb->query($sql);

				//create inventory to category table
				$sql = "CREATE TABLE " . WP_INVENTORY_TO_CATEGORY_TABLE . " (
							itc_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							inventory_id INT(11) NOT NULL,
							category_id INT(11) NOT NULL
					   ) ";
				$wpdb->query($sql);

				//if we have current category data we need to pull that over to our new table
				$sql = "insert into " . WP_INVENTORY_TO_CATEGORY_TABLE . " (inventory_id, category_id)
						select inventory_id, category_id from " . WP_INVENTORY_TABLE;
				$wpdb->query($sql);

				//trunc our current category id in the inventory table
				$sql = "update  " . WP_INVENTORY_TABLE . " set category_id=''";
				$wpdb->query($sql);

				//update to current version
				$sql = "UPDATE " . WP_FOXYPRESS_CONFIG_TABLE . " SET foxy_current_version = '0.2.0'";
				$wpdb->query($sql);

				//delete rows with default proudct image
				$sql = "delete from  " . WP_INVENTORY_IMAGES_TABLE . " where inventory_image='default-product-image.jpg'";
				$wpdb->query($sql);


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
		}
	}
	//update foxy.txt
	$foxyUpdateFile = ABSPATH . "wp-content/plugins/foxypress/foxy.txt";
	$fh = fopen($foxyUpdateFile, 'w');
	fwrite($fh, "0");
	fclose($fh);
}


function myplugin_addbuttons() {
   // Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
    	return;

   // Add only in Rich Editor mode
   	if ( get_user_option('rich_editing') == 'true') {
    	add_filter("mce_external_plugins", "add_myplugin_tinymce_plugin");
    	add_filter('mce_buttons_3', 'register_myplugin_button');
   	}
}

function register_myplugin_button($buttons) {
	array_push($buttons, "foxypress");
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_myplugin_tinymce_plugin($plugin_array) {
	$path = url("foxypress");
	$plugin_array['foxypress'] = $path;
	return $plugin_array;
}

// determine absolute url path of editor_plugin.js
function url($type) {
    //check if defined WordPress Plugins URL
	if (defined('WP_PLUGINS_URL'))  {
		return WP_PLUGINS_URL."/". $type ."/editor_plugin.js";
	}else{
	//if not assumme it is default location.
		return "../../../wp-content/plugins/". $type ."/editor_plugin.js";
	}
}

function foxypress_handle_tracking_module()
{
	$url = get_bloginfo("url") . "/wp-content/plugins/foxypress/ajax.php";
	$trackingform = "<div> Enter your order number </div>
					 <div>
					 	<input type=\"text\" id=\"foxypress_order_number\" name=\"foxypress_order_number\" value=\"\" />
						<input type=\"button\" id=\"foxypress_tracking_button\"	name=\"foxypress_tracking_button\"	value=\"Find Tracking Number\" onclick=\"foxypress_find_tracking('" . $url . "');\" />
					 </div>
					 <div id=\"foxypress_find_tracking_return\"></div>
					 ";
	return $trackingform;
}

function foxypress_handle_shortcode_item ($item_id, $legacy_shortcode=false, $detailurl='', $show_addtocart)
{
	global $wpdb; global $foxypress_url;
	$MoreDetailDiv = "";
	//previous version used the item code (not unique) instead of the id.
	$idcolumn = ($legacy_shortcode) ? "inventory_code" : "inventory_id";
	$item = $wpdb->get_row("SELECT i.*
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
							WHERE i." . $idcolumn. " = '" . $item_id . "'
							ORDER BY i.inventory_code DESC");
	//check to see if we need to link to a detail page
	if($detailurl != "")
	{
		$FullURL =  get_bloginfo("url") . $detailurl;
		$FullURL = foxypress_AddQSValue($FullURL, "id",  $item->inventory_id);
		$MoreDetailDiv = "<div class=\"foxypress_item_readmore\"><a href=\"" . $FullURL . "\">Read More</a></div>";
	}

	if($show_addtocart)
	{
		$foxyForm = "<div class=\"foxy_item_wrapper\">
						<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\">
							<input type=\"hidden\" name=\"quantity\" value=\"1\" />
							<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->inventory_name) . "\" />
							<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($item->inventory_code) . "\" />
							<input type=\"hidden\" name=\"price\" value=\"" . stripslashes($item->inventory_price) . "\" />
							<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
							<input type=\"hidden\" name=\"image\" value=\"" . INVENTORY_IMAGE_DIR . '/' . (($item->inventory_image != "") ? stripslashes($item->inventory_image) : INVENTORY_DEFAULT_IMAGE) . "\" />
							<input type=\"hidden\" name=\"weight\" value=\"" . stripslashes($item->inventory_weight) . "\" />
							<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />";


		//check to see if we have any attributes & build up the list
		$foxyForm .= foxypress_buildattributeform($item->inventory_id);
		$foxyAttributes = foxypress_buildattributelist($item->inventory_id);
		//check to see if we have any options & build up the dropdown if we do
		$foxyOptionList = foxypress_buildoptionlist($item->inventory_id);

		$foxyForm .= ($item->inventory_image != "") ? "<div class=\"foxypress_item_image\"><img style=\"max-width: 300px;\"  src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" /></div>" : "<div class=\"foxypress_item_image\"><img  src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" /></div>";
		$foxyForm .= "<div class=\"foxypress_item_content_wrapper\">
						<div class=\"foxypress_item_name\">" . stripslashes($item->inventory_name) . "</div>" .
					 (($item->inventory_price != "" && $item->inventory_price != "0") ? "<div class=\"foxypress_item_price\">$" . stripslashes($item->inventory_price) . "</div>" : "") .
					 "  <div class=\"foxypress_item_description\">" . foxypress_TruncateString(stripslashes($item->inventory_description), 35) . "</div>" .
					 $foxyAttributes .
					 $foxyOptionList .
					 $MoreDetailDiv .
					 "  <div class=\"foxypress_item_submit_wrapper\">"
							.
							( (foxypress_canaddtocart($item->inventory_id)) ?
							"<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit\" />" :
							"Sorry, we are out of stock for this item, please check back later.")
							.
					 "  </div>" .
					 "</div>" .
					 "</form>" .
					 "</div>";
	}
	else
	{
		$foxyAttributes = foxypress_buildattributelist($item->inventory_id);
		$foxyForm =  "<div class=\"foxy_item_wrapper\">"
		 			  .
					 (($item->inventory_image != "") ?
					 	"<div class=\"foxypress_item_image\"><img style=\"max-width: 300px;\"  src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" /></div>" :
					 	"<div class=\"foxypress_item_image\"><img  src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" /></div>")
					  .
   				      	"<div class=\"foxypress_item_content_wrapper\">" .
							"<div class=\"foxypress_item_name\">" . stripslashes($item->inventory_name) . "</div>" .
							 (($item->inventory_price != "" && $item->inventory_price != "0") ? "<div class=\"foxypress_item_price\">$" . stripslashes($item->inventory_price) . "</div>" : "") .
						"<div class=\"foxypress_item_description\">" . foxypress_TruncateString(stripslashes($item->inventory_description), 70) . "</div>" .
						 $foxyAttributes .
						 $MoreDetailDiv .
						"</div>" .
					  "</div>";
	}
	return $foxyForm;
}

function foxypress_handle_shortcode_listing($categoryid, $limit = 5, $itemsperrow = 2, $detailurl = '')
{
	global $wpdb;
	//set up paging
	$targetpage = foxypress_GetFullURL();
	$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
	$pos = strrpos($targetpage, "?");
	if ($pos === false) {
		$targetpage .= "?";
	}
	$drRows = $wpdb->get_row("SELECT count(i.inventory_id) as RowCount
								FROM " . WP_INVENTORY_TABLE . " as i
								INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id and
																							ic.category_id = '" .  $categoryid . "'
								INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
								LEFT JOIN
									(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
									from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
								");
	$pageNumber = foxypress_FixGetVar('fp_pn');
	$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;

	//get all items within this category. format the result set somehow
	$items = $wpdb->get_results("SELECT i.*
									,c.category_name
									,im.inventory_images_id
									,im.inventory_image
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id and
																							ic.category_id = '" .  $categoryid . "'
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							LEFT JOIN
									(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
									from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
							ORDER BY i.inventory_code DESC
							LIMIT $start, $limit");
	$foxyResults = "";
	if(!empty($items))
	{
		$counter = 0;
		foreach($items as $item)
		{
			if($counter == 0)
			{
				$foxyResults .= "<div class=\"foxypress_item_row\">";
			}
			$foxyResults .= foxypress_handle_shortcode_item($item->inventory_id, false, $detailurl, false);
			$counter++;
			if($counter == $itemsperrow)
			{
				$foxyResults .= "<div class=\"foxypress_item_row_clear\">&nbsp;</div></div>";
				$counter = 0;
			}
		}
		//close out the last div if we haven't
		if($counter != 0)
		{
			$foxyResults .= "<div class=\"foxypress_item_row_clear\"></div></div>";
		}
		//pagination
		if($drRows->RowCount > $limit)
		{
			$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
			$foxyResults .= "<Br>" . $Pagination;
		}
	}
	return $foxyResults;
}

function foxypress_handle_shortcode_detail()
{
	global $wpdb; global $foxypress_url;
	$inventory_id = foxypress_FixGetVar('id');
	$item = $wpdb->get_row("SELECT i.*
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
							WHERE i.inventory_id = '$inventory_id'
							ORDER BY i.inventory_code DESC");

	$foxyForm = "<div class=\"foxy_item_wrapper\">
					<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\">
						<input type=\"hidden\" name=\"quantity\" value=\"1\" />
						<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->inventory_name) . "\" />
						<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($item->inventory_code) . "\" />
						<input type=\"hidden\" name=\"price\" value=\"" . stripslashes($item->inventory_price) . "\" />
						<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
						<input type=\"hidden\" name=\"image\" value=\"" . INVENTORY_IMAGE_DIR . '/' . (($item->inventory_image != "") ? stripslashes($item->inventory_image) : INVENTORY_DEFAULT_IMAGE) . "\" />
						<input type=\"hidden\" name=\"weight\" value=\"" . stripslashes($item->inventory_weight) . "\" />
						<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />";

	//check to see if we have any attributes & build up the list
	$foxyForm .= foxypress_buildattributeform($item->inventory_id);
	$foxyAttributes = foxypress_buildattributelist($item->inventory_id);
	//build option lists
	$foxyOptionList = foxypress_buildoptionlist($item->inventory_id);
	//get images
	$itemImages =  $wpdb->get_results("SELECT *
							FROM " . WP_INVENTORY_IMAGES_TABLE . "
							WHERE inventory_id = '$inventory_id'
							ORDER BY inventory_images_id");
	$foxyThumbs = "";
	if(!empty($itemImages) && ($wpdb->num_rows > 1))
	{
		$foxyThumbs = "<ul class=\"foxypress_item_image_thumbs_detail\">";
		foreach($itemImages as $ii)
		{
			$foxyThumbs .= "<li><a href=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" rel=\"colorbox\"><img src=\"" . INVENTORY_IMAGE_DIR . "/" . $ii->inventory_image . "\" /></a></li>";
		}
		$foxyThumbs .= "</ul>";
	}

	$foxyImages = ($item->inventory_image != "") ? "<div class=\"foxypress_item_image_detail\"><img style=\"max-width: 300px;\"  src=\"" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "\" />" : "<div class=\"foxypress_item_image_detail\"><img  src=\"" . INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE . "\" />";
	$foxyImages .= $foxyThumbs . "</div>";
	$foxyForm .= "<div class=\"foxypress_item_name_detail\">" . stripslashes($item->inventory_name) . "</div>" .
				 (($item->inventory_price != "" && $item->inventory_price != "0") ? "<div class=\"foxypress_item_price_detail\">$" . stripslashes($item->inventory_price) . "</div>" : "") .
 				 "<div class=\"foxypress_item_description_detail\">" . stripslashes($item->inventory_description) . "</div>" .
				 $foxyAttributes .
				 (($foxyOptionList != "") ? "<div class=\"foxypress_item_options_detail\">" . $foxyOptionList . "</div>" : "") .
				 $MoreDetailDiv .
				  "<div class=\"foxypress_item_submit_wrapper_detail\">"
				 	.
					( (foxypress_canaddtocart($item->inventory_id)) ?
					"<input type=\"submit\" value=\"Add To Cart\" class=\"foxypress_item_submit\" />" :
					"Sorry, we are out of stock for this item, please check back later.")
					.
				 "</div>" .
				 "</form>" .
				 "</div>";
	return $foxyForm . $foxyImages;
}

function foxypress_canaddtocart($inventory_id)
{
	//check the options available, if any of the option lists have 0 items, then we cannot add to cart
	global $wpdb;
	//get option groups
	$itemOptionGroups = $wpdb->get_results("SELECT distinct option_group_id
										FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
										WHERE inventory_id = '" . $inventory_id . "'");
	if(!empty($itemOptionGroups))
	{

		foreach($itemOptionGroups as $foxyoptiongroup)
		{
			//get option info
			$itemOptions = $wpdb->get_row(" SELECT (SELECT count(*)
															FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
														) AS TotalOptions,
														( SELECT count(*)
															FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . "
															WHERE inventory_id = '" . $inventory_id . "'
															AND option_group_id = '" .  $foxyoptiongroup->option_group_id . "'
															AND option_active = '0'
														) AS InactiveOptions");
			if(!empty($itemOptions))
			{
				if($itemOptions->TotalOptions == $itemOptions->InactiveOptions)
				{
					return false;
				}
			}
		}
	}

	return true;
}

function foxypress_buildattributeform($inventory_id)
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
	 											,a.attribute_value
	 									      FROM " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . "  a
											  inner join " . WP_INVENTORY_TABLE . " i on a.inventory_id = i.inventory_id
											  WHERE i.inventory_id = '" . $inventory_id . "'
											  order by a.attribute_text");

	$formAttributes = "";
	if(!empty($itemAttributes))
	{
		foreach($itemAttributes as $foxyatt)
		{
			$formAttributes .= "<input type=\"hidden\" name=\"" . stripslashes($foxyatt->attribute_text) . "\" value=\"" . stripslashes($foxyatt->attribute_value) . "\" />";
		}
	}
	return $formAttributes;
}

function foxypress_buildattributelist($inventory_id)
{
	global $wpdb;
	//check if we have any custom attributes
	$itemAttributes = $wpdb->get_results("SELECT a.attribute_text
	 											,a.attribute_value
	 									      FROM " . WP_FOXYPRESS_INVENTORY_ATTRIBUTES . "  a
											  inner join " . WP_INVENTORY_TABLE . " i on a.inventory_id = i.inventory_id
											  WHERE i.inventory_id = '" . $inventory_id . "'
											  order by a.attribute_text");

	$foxyAttributes = "";
	if(!empty($itemAttributes))
	{
		$foxyAttributes = "<div class=\"foxypress_item_attributes\">";
		foreach($itemAttributes as $foxyatt)
		{
			$foxyAttributes .= "<div>" . stripslashes($foxyatt->attribute_text) . ": " . stripslashes($foxyatt->attribute_value) .  "</div>";
		}
		$foxyAttributes .= "</div>";
	}
	return $foxyAttributes;
}

function foxypress_buildoptionlist($inventory_id)
{
	global $wpdb;
	//check if we have any options
	$itemOptions = $wpdb->get_results("SELECT o.option_text
											,o.option_value
											,og.option_group_name
											,o.option_extra_price
											,o.option_active
									   FROM " . WP_FOXYPRESS_INVENTORY_OPTIONS . " as o
									   inner join " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " as og on o.option_group_id = og.option_group_id
									   inner join " . WP_INVENTORY_TABLE . " i on o.inventory_id = i.inventory_id
									   WHERE i.inventory_id = '$inventory_id'
									   order by og.option_group_name");
	//check to see if we have any options & build up the dropdown if we do
	$foxyOptionList = "";
	if(!empty($itemOptions))
	{
		$OptionsList = array();
		$templist = "";
		$previousgroupname = "";
		$soldoutlist = array();
		foreach($itemOptions as $foxyoption)
		{
			//new group (new select list)
			if($previousgroupname != $foxyoption->option_group_name)
			{
				if($templist != "")
				{
					$templist .= "</select></div> ";
					if(count($soldoutlist) > 0)
					{
						$templist .= "<div class=\"foxypress_item_otions_soldout\">sold out options: ";
						$soldout = "";
						foreach($soldoutlist as $soldoutoption)
						{
							$soldout .= ($soldout == "") ? $soldoutoption : ", " . $soldoutoption;
						}
						$templist .= $soldout . "</div>";
						unset($soldoutlist);
					}
					$OptionsList[] = $templist;
					$templist = "";
				}
				$templist = $foxyoption->option_group_name . " <div class=\"foxypress_item_options\"><select name=\"" . stripslashes($foxyoption->option_group_name) . "\">";
				$previousgroupname = $foxyoption->option_group_name;
			}

			if($foxyoption->option_active == "1")
			{
				$extraattribute = "";
				$extraattributefriendly = "";
				if($foxyoption->option_extra_price != "" && $foxyoption->option_extra_price != 0)
				{
					$extraattribute = "{p+" . number_format($foxyoption->option_extra_price, 2). "}";
					$extraattributefriendly = " + $" . number_format($foxyoption->option_extra_price, 2)
					;
				}
				$templist  .= "<option value=\"" . stripslashes($foxyoption->option_value) . $extraattribute . "\">" . stripslashes($foxyoption->option_text) . $extraattributefriendly . "</option>";
			}
			else
			{
				$soldoutlist[] = $foxyoption->option_text;
			}
		}
		//close out the last one we are looping through
		$templist .= "</select></div>";
		if(count($soldoutlist) > 0)
		{
			$templist .= "<div class=\"foxypress_item_otions_soldout\">sold out options: ";
			$soldout = "";
			foreach($soldoutlist as $soldoutoption)
			{
				$soldout .= ($soldout == "") ? $soldoutoption : ", " . $soldoutoption;
			}
			$templist .= $soldout . "</div>";
			unset($soldoutlist);
		}
		$OptionsList[] = $templist;
		foreach($OptionsList as $foxylist)
		{
			$foxyOptionList .= $foxylist;
		}
	}
	return $foxyOptionList;
}

function foxypress_shortcode( $atts, $content = null) {
	global $wpdb; global $foxypress_url;
	$querystring = "";
	$invItems = array();
	$mode = trim($atts['mode']);
	$mode = ($mode == "") ? "single" : $mode;

	if(trim($atts['code']) != '' && $mode == 'single')
	{
		return foxypress_handle_shortcode_item(trim($atts['code']), true, '', true);
	}
	else if(trim($atts['id']) != '' && $mode == 'single')
	{
		return foxypress_handle_shortcode_item(trim($atts['id']), false, '', true);
	}
	else if(trim($atts['categoryid']) != '' && $mode == 'list')
	{
		return foxypress_handle_shortcode_listing(trim($atts['categoryid']), trim($atts['items']), trim($atts['cols']), trim($atts['detailurl']));
	}
	else if($mode == 'detail')
	{
		return foxypress_handle_shortcode_detail();
	}
	else if($mode == 'tracking')
	{
		return foxypress_handle_tracking_module();
	}
	/*
		$quantity = "1";
		$hashcode = get_option("foxycart_apikey");
		$codehash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'code' . stripslashes($item->inventory_code), $hashcode);
		$namehash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'name' . stripslashes($item->inventory_name), $hashcode);
		$pricehash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'price' . stripslashes($item->inventory_price), $hashcode);
		$categoryhash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'category' . stripslashes($item->category_name), $hashcode);
		$weighthash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'weight' . stripslashes($item->inventory_weight), $hashcode);
		$quantityhash = hash_hmac('sha256', stripslashes($item->inventory_code) . 'quantity' . $quantity, $hashcode);
		$foxyForm = "<form action=\"https://" . $foxypress_url . ".foxycart.com/cart\" method=\"POST\" accept-charset=\"utf-8\"  class=\"foxycart\">
						<input type=\"hidden\" name=\"quantity||" . $quantityhash . "\" value=\"" . $quantity . "\" />
						<input type=\"hidden\" name=\"name||" . $namehash . "\" value=\"" . stripslashes($item->inventory_name) . "\" />
						<input type=\"hidden\" name=\"code||" . $codehash . "\" value=\"" . stripslashes($item->inventory_code) . "\" />
						<input type=\"hidden\" name=\"price||" . $pricehash . "\" value=\"" . stripslashes($item->inventory_price) . "\" />
						<input type=\"hidden\" name=\"category||" . $categoryhash . "\" value=\"" . stripslashes($item->category_name) . "\" />
						<input type=\"hidden\" name=\"weight||" . $weighthash . "\" value=\"" . stripslashes($item->inventory_weight) . "\" />";
						//					<input type=\"hidden\" name=\"image\" value=\"" . INVENTORY_IMAGE_DIR . '/' . stripslashes($item->
	*/
}

function foxypress_category_item_count($category_name)
{
	global $wpdb;
	$item = $wpdb->get_row("SELECT count(i.inventory_id) as ItemCount
							FROM " . WP_INVENTORY_TABLE . " as i
							INNER JOIN " . WP_INVENTORY_TO_CATEGORY_TABLE . " as ic ON i.inventory_id=ic.inventory_id
							INNER JOIN " . WP_INVENTORY_CATEGORIES_TABLE . " as c ON ic.category_id = c.category_id
							where c.category_name='" . $category_name . "'");
	if(!empty($item))
	{
		return $item->ItemCount;
	}
	return "0";
}


function foxypress_request($name, $default=null) {
    if (!isset($_REQUEST[$name])) return $default;
    return stripslashes_deep($_REQUEST[$name]);
}

function foxypress_add_menu() {
    // Set admin as the only one who can use Inventory for security
    $allowed_group = 'manage_options';

      // Add the admin panel pages for Inventory. Use permissions pulled from above
    if ( function_exists( 'add_menu_page' ) ) {
       add_menu_page( __( 'Foxypress','foxypress' ), __( 'Foxypress','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
    if ( function_exists( 'add_submenu_page' ) ) {
       add_submenu_page( 'foxypress', __( 'Settings','foxypress' ), __( 'Manage Settings','foxypress' ), $allowed_group, 'foxypress', 'foxypress_options' );
     }
}

function foxypress_FixGetVar($variable, $default = '')
{
	$value = $default;
	if(isset($_GET[$variable]))
	{
		$value = trim($_GET[$variable]);
		if(get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_FixPostVar($variable, $default = '')
{
	$value = $default;
	if(isset($_POST[$variable]))
	{
		$value = trim($_POST[$variable]);
		//$value = addslashes($value);
		$value = mysql_real_escape_string($value);
	}
	return $value;
}

function foxypress_DeleteImage($fileloc)
{
	if (file_exists($fileloc))
	{
		unlink($fileloc);
	}
}

function foxypress_curlPostRequest($url, $postData) {
	$ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($postData))
	{
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	}
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  	curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
  	curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
  	$response = curl_exec($ch);
  	$info = curl_getinfo($ch);
	if ($response === false || $info['http_code'] != 200) {
		$output = "No cURL data returned for $url [". $info['http_code']. "]";
	  	if (curl_error($ch))
		{
	 		$response .= "\n". curl_error($ch);
		}
	}
	curl_close($ch);
    return($response);
}

//gets the exact url as presented in the url bar
function foxypress_GetFullURL()
{
	$pageURL = 'http';
	if (!empty($_SERVER['HTTPS'])) {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] . $_SERVER['REQUEST_URI'];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER['REQUEST_URI'];
	}
	return $pageURL;
}

//uses the phsyical file vs. the rewrite name
function foxypress_GetCurrentPageURL($includeQS = false) {
	$pageURL = 'http';
	if (!empty($_SERVER['HTTPS'])) {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER['PHP_SELF'];
	}
	if($includeQS)
	{
		$pageURL .= "?" . $_SERVER['QUERY_STRING'];
	}
	return $pageURL;
}


function foxypress_RemoveQSValue($url,$remove) {
    $infos=parse_url($url);
    $str=$infos["query"];
    $op = array();
    $pairs = explode("&", $str);
    foreach ($pairs as $pair) {
       list($k, $v) = array_map("urldecode", explode("=", $pair));
        $op[$k] = $v;
    }
    if(isset($op[$remove])){
        unset($op[$remove]);
    }
    return str_replace($str,http_build_query($op),$url);
}

function foxypress_AddQSValue($url, $key, $value) {
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    if (strpos($url, '?') === false) {
        return ($url . '?' . $key . '=' . $value);
    } else {
        return ($url . '&' . $key . '=' . $value);
    }
}

function foxypress_GetPagination($page, $total_pages, $limit, $targetpage, $qspagename = 'pagenum')
{
	$adjacents = 3;
	if ($page == 0) $page = 1;
	$prev = $page - 1;
	$next = $page + 1;
	$lastpage = ceil($total_pages/$limit);
	$lpm1 = $lastpage - 1;

	$pagination = "";
	if($lastpage > 1)
	{
		$pagination .= "<div class=\"foxy_item_pagination\">";
		//previous button
		if ($page > 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$prev\"><< previous</a>";
		else
			$pagination.= "<span class=\"disabled\"><< previous</span>";

		//pages
		if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
		{
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $page)
					$pagination.= "<span class=\"current\">$counter</span>";
				else
					$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
			}
		}
		elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
		{
			//close to beginning; only hide later pages
			if($page < 1 + ($adjacents * 2))
			{
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//in middle; hide some front and some back
			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage&pagenum=$counter\">$counter</a>";
				}
				$pagination.= "...";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lpm1\">$lpm1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$lastpage\">$lastpage</a>";
			}
			//close to end; only hide early pages
			else
			{
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=1\">1</a>";
				$pagination.= "<a href=\"$targetpage&" . $qspagename . "=2\">2</a>";
				$pagination.= "...";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage?" . $qspagename . "=$counter\">$counter</a>";
				}
			}
		}
		//next button
		if ($page < $counter - 1)
			$pagination.= "<a href=\"$targetpage&" . $qspagename . "=$next\">next >></a>";
		else
			$pagination.= "<span class=\"disabled\">next >></span>";
		$pagination.= "</div>\n";
	}
	return $pagination;
}

function foxypress_TruncateString($str, $length)
{
	if(strlen($str) > $length)
	{
		return substr($str, 0, $length) . "...";
	}
	return $str;
}

function foxypress_GenerateRandomString($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

function foxypress_importFoxyScripts(){
	$version = get_option('foxycart_storeversion');
	$includejq = get_option('foxycart_include_jquery');
	if(get_option('foxycart_storeurl')!=''){
		if($includejq)
		{
			echo("<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js\"></script>");
		}
		echo"
		<script type=\"text/javascript\" src=\"js/jquery.qtip.js\"></script>
		<script type='text/javascript'>
			function foxypress_find_tracking(baseurl)
			{
				var ordernumber = jQuery('#foxypress_order_number').val();
				var lastname = jQuery('#foxypress_order_name').val();
				if(ordernumber != '' && lastname != '')
				{
					var url = baseurl + '?m=tracking&id=' + ordernumber + '&ln=' + lastname;
					jQuery.ajax(
						{
							url : url,
							type : \"GET\",
							datatype : \"json\",
							cache : \"false\",
							success : function (data) { foxypress_find_tracking_callback(data); }
						}
					);
				}
				else
				{
					alert('Please fill out both the order number and you last name');
				}
			}

			function foxypress_find_tracking_callback(data)
			{
				var res = '';
				if(data.ajax_status == 'ok')
				{
					res =  '<div><div id=\"foxy_order_details\">Order Details</div><div id=\"foxy_order_details_name\">' + data.name + '</div><div id=\"foxy_order_details_address\">' + data.shipping_address + '</div><div id=\"foxy_order_details_status\">Status: ' + data.current_status + '</div><div id=\"foxy_order_details_tracking\">Tracking Number: ' + ((data.tracking_number != '') ? data.tracking_number : 'n/a') + '</div></div>';
				}
				else
				{
					res = 'We could not find that order number in our system, please try again or check back later.';
				}
				jQuery('#foxypress_find_tracking_return').html(res);

			}

			jQuery(document).ready(function() {
				jQuery(\"a[rel='colorbox']\").colorbox();
			});
		</script>
		";
		if($version=="0.7.1"){
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.3.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.16/style1_fc/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}else{
			echo'<!-- BEGIN FOXYCART FILES -->
			<script src="http://cdn.foxycart.com/' . get_option('foxycart_storeurl') . '/foxycart.complete.js" type="text/javascript" charset="utf-8"></script>
			<link rel="stylesheet" href="http://static.foxycart.com/scripts/colorbox/1.3.9/style1/colorbox.css" type="text/css" media="screen" charset="utf-8" />
			<!-- END FOXYCART FILES -->
			';
		}
	}
}

?>