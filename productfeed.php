<?php
	session_start();
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');	
	global $wpdb;	
	
	$XMLData = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
			    <rss version =\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\">
			   		<channel>
						<title>Product Feed</title>
						<description>Google Product Feed</description>
						<link>" . get_bloginfo("url") . "</link>";
	
	$Items = $wpdb->get_results("SELECT i.* ,im.inventory_image
								FROM " . WP_INVENTORY_TABLE . " as i 
								LEFT JOIN
									(select min(inventory_images_id) as inventory_images_id, inventory_id, inventory_image
									from " . WP_INVENTORY_IMAGES_TABLE . " group by inventory_id) as im ON i.inventory_id = im.inventory_id
								ORDER BY i.inventory_id");
	if(!empty($Items))
	{
		foreach($Items as $item)
		{
			$XMLData .= "<item>
					     	<title>" . htmlspecialchars($item->inventory_name) . "</title>
							<description>" . htmlspecialchars($item->inventory_description) . "</description>
							<g:id>" . $item->inventory_id . "</g:id>
							<g:image_link>" . INVENTORY_IMAGE_DIR . "/" . $item->inventory_image . "</g:image_link>
							<link>" . get_bloginfo("url") . "/foxy-product-detail?id=" . $item->inventory_id .  "</link>
							<g:mpn>" . $item->inventory_code . "</g:mpn>
							<g:price>" . $item->inventory_price . "</g:price>
							<g:condition>New</g:condition>";
			//get categories		
			$InventoryCategories = $wpdb->get_results("SELECT c.category_name as CategoryName FROM " . WP_INVENTORY_TO_CATEGORY_TABLE . " as itc inner join " . WP_INVENTORY_CATEGORIES_TABLE . " as c on itc.category_id = c.category_id  where itc.inventory_id='" . $item->inventory_id . "'");
			if(!empty($InventoryCategories))
			{
				foreach($InventoryCategories as $ic)
				{
					$XMLData .= "<g:product_type>" . $ic->CategoryName . "</g:product_type>";
				}
			}	
			$XMLData .=	   "<g:quantity>" . $item->inventory_quantity . "</g:quantity>
							<g:weight>" . $item->inventory_weight . "</g:weight>
						 </item>";
		}
	}
	
	$XMLData .= "	</channel>
				</rss>";
				
	header('Content-type: text/xml');
	echo($XMLData);
?>