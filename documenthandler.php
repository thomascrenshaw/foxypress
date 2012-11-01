<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

if ( ! defined( 'ABSPATH' ) ){
	die( 'Direct access not permitted.' );
}

global $wpdb;

if (!empty($_FILES)) {
	$inventory_id = intval( $_POST['inventory_id'] );
	$downloadabletable = $wpdb->prefix . "foxypress_inventory_downloadables";
	$downloadablename = $_POST['downloadablename'];
	$downloadablemaxdownloads = $_POST['downloadablemaxdownloads'];
	$targetpath = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR;
	$fileExtension = foxypress_ParseFileExtension($_FILES['Filedata']['name']);
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
	$fileTypes = array('jpg','jpeg','gif','png','zip');

	if (in_array($fileExtension,$fileTypes)) {
		if(move_uploaded_file($_FILES['Filedata']['tmp_name'], $targetpath))
		{				
			$query = "INSERT INTO " . $downloadabletable . " SET inventory_id='" . $inventory_id . "', filename='" . mysql_escape_string($newfilename) . "',  maxdownloads= '" . mysql_escape_string($downloadablemaxdownloads) . "', status = 1";
			$wpdb->query($query);
			$downloadable_id = $wpdb->insert_id;
			echo($newfilename . "|" . $downloadable_id . "|" . $downloadablemaxdownloads);				
		} 
		else
		{
			//failure	
			esc_html_e('<Error>Error uploading file</Error>', 'foxypress');
		}	
	}else{
		//failure	
		esc_html_e('<Error>Invalid file type.' . $fileExtension .'</Error>', 'foxypress');
	}		
}
else
{
	esc_html_e('<Error>Invalid post data</Error>', 'foxypress');	
}
?>