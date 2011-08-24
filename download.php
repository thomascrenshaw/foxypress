<?php
	session_start();
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');	
	global $wpdb;	
	
	//get downloadlable id
	$jibberish_downloadable_id = foxypress_FixGetVar('d');	
	$jibberish_download_transaction_id = foxypress_FixGetVar('t');
	//decrypt it
	$download_transaction_id = foxypress_Decrypt($jibberish_download_transaction_id);	
	$downloadable_id = foxypress_Decrypt($jibberish_downloadable_id);		
		
	//look up downloadable
	$dt_downloadable = $wpdb->get_row("SELECT * FROM " . WP_INVENTORY_DOWNLOADABLES . " WHERE downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");
	
	if(!empty($dt_downloadable))
	{
		//look up # of downloads so far
		$dt_downloads = $wpdb->get_row("SELECT download_count FROM " . WP_DOWNLOADABLE_TRANSACTION . " WHERE download_transaction_id = '" . mysql_escape_string($download_transaction_id) . "' AND downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");
		if(!empty($dt_downloads))
		{
			$NumberOfDownloads = $dt_downloads->download_count;
			//look up # of downloads available
			$global_max_downloads = get_option("foxypress_max_downloads");
			$max_downloads = $dt_downloadable->maxdownloads;
			//if the item level max is not set, use the global
			if($max_downloads == "" || $max_downloads == "0")
			{
				$max_downloads  = $global_max_downloads;
			}
			//check to see if we've maxed already			
			if($NumberOfDownloads < $max_downloads)
			{
				//log this download
				$wpdb->query("INSERT INTO " . WP_DOWNLOADABLE_DOWNLOAD . " SET download_transaction_id='" . mysql_escape_string($download_transaction_id) . "', download_date = now(), ip_address='" . mysql_escape_string($_SERVER['REMOTE_ADDR']) . "', referrer='" . mysql_escape_string($_SERVER['HTTP_REFERER']) . "'");
				$wpdb->query("UPDATE " . WP_DOWNLOADABLE_TRANSACTION . " SET download_count = (download_count + 1) WHERE download_transaction_id = '" . mysql_escape_string($download_transaction_id) . "' AND downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");
				
				
				//stream the file to the server		
				$filePath = ABSPATH . INVENTORY_DOWNLOADABLE_LOCAL_DIR . $dt_downloadable->filename;
				if ($fd = fopen ($filePath, "r")) {
					$fsize = filesize($filePath);
					$path_parts = pathinfo($filePath);
					$ext = strtolower($path_parts["extension"]);
					switch ($ext) {
						case "pdf":
							header("Content-type: application/pdf"); // add here more headers for diff. extensions
							header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
							break;
						default;
							header("Content-type: application/octet-stream");
							header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
							break;
					}
					header("Content-length: $fsize");
					header("Cache-control: private"); //use this to open files directly
					while(!feof($fd)) {
						$buffer = fread($fd, 2048);
						echo $buffer;
					}
				}
				fclose ($fd);
				exit;				
			}
			else
			{
				echo("Sorry, this download is no longer valid, you have reached maximum number of downloads");
				exit;
			}
		}
		else
		{
			echo("Invalid Download ID");
			exit;
		}	
	}
	else
	{
		echo("Invalid Download ID");
		exit;
	}
?>