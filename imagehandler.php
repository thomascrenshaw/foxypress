<?php

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');	
global $wpdb;	

if (!empty($_FILES)) {
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$inventory_id = $_POST['inventory_id'];
	$imagetable = $_POST['prefix'];
	// Validate the file type
	$fileTypes = array('jpg','jpeg','gif','png', 'bmp', 'JPG', 'JPEG', 'GIF', 'PNG', 'BMP'); // File extensions
	$fileParts = pathinfo($_FILES['Filedata']['name']);	
	if (in_array($fileParts['extension'],$fileTypes)) 
	{
		$imgname = foxypress_UploadImage("Filedata", $inventory_id);
		$image_id = "0";
		if ($imgname != "") 
		{
			$imgquery = 'INSERT INTO ' . $imagetable . ' SET inventory_id=' . $inventory_id . ', inventory_image="' . mysql_escape_string($imgname) . '"';
			$wpdb->query($imgquery);
			$image_id = $wpdb->insert_id;
		}
		//return image id w/ name
		echo($imgname . "|" . $image_id);
	}
	else 
	{
		echo 'Invalid file type.';
	}
}
else
{
	echo('Error');	
}
?>