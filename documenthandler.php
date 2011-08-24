<?php

require_once('../../../wp-includes/wp-db.php');	
require_once('../../../wp-config.php');	
global $wpdb;	

if (!empty($_FILES)) {
	$inventory_id = $_POST['inventory_id'];
	$downloadabletable = $_POST['prefix'];
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
		echo('<Error>Error uploading file</Error>');
	}		
}
else
{
	echo('<Error>Invalid post data</Error>');	
}
?>