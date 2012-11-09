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
	global $post, $wpdb;	
	$blog_id = foxypress_FixGetVar('b');
	$switched_blog = false;
	
	//if we have a multi-site and we are on the wrong blog, we need to switch
	if($wpdb->blogid != $blog_id)
	{
		switch_to_blog($blog_id);	
		$switched_blog = true;
	}
	
	$XMLData = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
			    <rss version =\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\">
			   		<channel>
						<title>Product Feed</title>
						<description>Google Product Feed</description>
						<link>" . get_bloginfo("url") . "</link>";
	
	$Items = $wpdb->get_results("SELECT i.*
								FROM " . $wpdb->prefix . "posts as i 								
								WHERE post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND post_status = 'publish'
								ORDER BY i.ID");
	if(!empty($Items))
	{
		foreach($Items as $item)
		{
			$price = foxypress_GetActualPrice(get_post_meta($item->ID, "_price", true), get_post_meta($item->ID, "_saleprice", true), get_post_meta($item->ID, "_salestartdate", true), get_post_meta($item->ID, "_saleenddate", true));
			$featuredImageID = (has_post_thumbnail($id) ? get_post_thumbnail_id($id) : 0);
			$imageNumber = 0;
			$src = "";
			$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $id, 'order' => 'ASC','orderby' => 'menu_order'));
			foreach ($attachments as $attachment) 
			{
				$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
				if ($featuredImageID == $attachment->ID || ($featuredImageID == 0 && $imageNumber == 0)) $src = $thumbnailSRC[0];
				$imageNumber++;
			}
			if (!$src) $src = INVENTORY_IMAGE_DIR . "/" . INVENTORY_DEFAULT_IMAGE;	
			$XMLData .= "<item>
					     	<title>" . $item->post_title . "</title>
							<description>" . htmlspecialchars($item->post_content) . "</description>
							<g:id>" . $item->ID . "</g:id>
							<g:image_link>" . $src . "</g:image_link>
							<link>" . foxypress_get_product_url($item->ID) . "</link>
							<g:mpn>" . $price . "</g:mpn>
							<g:price>" .  $price  . "</g:price>
							<g:condition>New</g:condition>";
			//get categories		
			$InventoryCategories = $wpdb->get_results("SELECT c.category_name as CategoryName 
													   FROM " . $wpdb->prefix . "foxypress_inventory_to_category as itc 
													   INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c 
													   		on itc.category_id = c.category_id  where itc.inventory_id='" . $item->ID . "'");
			if(!empty($InventoryCategories))
			{
				foreach($InventoryCategories as $ic)
				{
					$XMLData .= "<g:product_type>" . $ic->CategoryName . "</g:product_type>";
				}
			}	
			
			$XMLData .=	   "<g:quantity>" . get_post_meta($item->ID, '_quantity', true) . "</g:quantity>
							<g:weight>" . get_post_meta($item->ID, '_weight', true) . " lbs " . get_post_meta($item->ID, '_weight2', true) . " oz</g:weight>
						 </item>";
						 
						 

		}
	}
	
	$XMLData .= "	</channel>
				</rss>";
	
	//restore blog
	if($switched_blog) { restore_current_blog(); }
				
	header('Content-type: text/xml');
	echo($XMLData);
?>