<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

	session_start();
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');	
	global $wpdb;	
	$switched_blog = false;
	
	//get downloadlable id
	$jibberish_downloadable_id = foxypress_FixGetVar('d');	
	$jibberish_download_transaction_id = foxypress_FixGetVar('t');
	$jibberish_blog_id = foxypress_FixGetVar('b');
	//decrypt it
	$download_transaction_id = foxypress_Decrypt($jibberish_download_transaction_id);	
	$downloadable_id = foxypress_Decrypt($jibberish_downloadable_id);		
	$blog_id = foxypress_Decrypt($jibberish_blog_id);	
	
	//if we have a multi-site and we are on the wrong blog, we need to switch
	if($blog_id != "" && $blog_id != "0")
	{
		if($wpdb->blogid != $blog_id)
		{
			switch_to_blog($blog_id);	
			$switched_blog = true;
		}
	}
			
	//look up downloadable
	$dt_downloadable = $wpdb->get_row("SELECT id.* 
									   FROM " . $wpdb->prefix . "foxypress_inventory_downloadables as id
									   INNER JOIN " . $wpdb->prefix . "foxypress_downloadable_transaction as dt
									   				ON id.downloadable_id = dt.downloadable_id
														AND dt.download_transaction_id = '" . mysql_escape_string($download_transaction_id) . "'
									   WHERE id.downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");									   
	
	if(!empty($dt_downloadable))
	{
		//look up # of downloads so far
		$dt_downloads = $wpdb->get_row("SELECT download_count FROM " . $wpdb->prefix . "foxypress_downloadable_transaction WHERE download_transaction_id = '" . mysql_escape_string($download_transaction_id) . "' AND downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");
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
				$wpdb->query("INSERT INTO " . $wpdb->prefix . "foxypress_downloadable_download SET download_transaction_id='" . mysql_escape_string($download_transaction_id) . "', download_date = now(), ip_address='" . mysql_escape_string($_SERVER['REMOTE_ADDR']) . "', referrer='" . mysql_escape_string($_SERVER['HTTP_REFERER']) . "'");
				$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_downloadable_transaction SET download_count = (download_count + 1) WHERE download_transaction_id = '" . mysql_escape_string($download_transaction_id) . "' AND downloadable_id = '" . mysql_escape_string($downloadable_id) . "'");				
				//restore blog
				if($switched_blog) { restore_current_blog(); }
								
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
				//restore blog
				if($switched_blog) { restore_current_blog(); }
				_e('Sorry, this download is no longer valid, you have reached maximum number of downloads');
				exit;
			}
		}
		else
		{
			//restore blog
			if($switched_blog) { restore_current_blog(); }
			_e('Invalid Download ID');
			exit;
		}	
	}
	else
	{
		//restore blog
		if($switched_blog) { restore_current_blog(); }
		_e('Invalid Download ID');
		exit;
	}
?>