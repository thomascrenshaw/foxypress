<?php
//uninstall foxypress
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
{
   	exit();
}
//delete options
$keys = array(
        'foxycart_storeurl',
        'foxycart_apikey',
        'foxycart_storeversion',
        'foxycart_include_jquery',
		'foxypress_base_url',
		'foxycart_enable_multiship',
		'foxypress_max_downloads',
		'foxypress_encryption_key',
		'foxycart_use_lightbox',
		'foxycart_show_dashboard_widget',
		'foxypress_qty_alert',
		'foxycart_datafeeds',
		'foxycart_currency_locale',
		'foxypress_inactive_message',
		'foxypress_out_of_stock_message'
		);
		
foreach( $keys as $key ) 
{
	delete_option( $key );
}

global $wpdb;
//delete tables
$wpdb->query("DROP TABLE " . $wpdb->prefix . "foxypress_transaction");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_note");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_sync");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_transaction_status");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_config");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_options"); 
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_attributes");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_option_group");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_categories");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_to_category");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_images");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_inventory_downloadables");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_downloadable_transaction");
$wpdb->query("DROP TABLE " . $wpdb->prefix  . "foxypress_downloadable_download");

//delete downloadable directory
foxypress_recursiveDelete(ABSPATH . "wp-content/inventory_downloadables/");
//delete inventory images directory
foxypress_recursiveDelete(ABSPATH . "wp-content/inventory_images/");

//functions
function foxypress_recursiveDelete($str){
	if(is_file($str)){
		return @unlink($str);
	}
	elseif(is_dir($str)){
		$scan = glob(rtrim($str,'/').'/*');
		foreach($scan as $index=>$path){
			foxypress_recursiveDelete($path);
		}
		return @rmdir($str);
	}
}
?>