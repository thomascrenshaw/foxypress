<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

	session_start();
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-includes/wp-db.php');	
	global $post, $wpdb;	
	$blog_id = foxypress_FixGetVar('b');
	$switched_blog = false;
	$locale = localeconv();
	$currency_symbol = trim( $locale[int_curr_symbol] );

	//if we have a multi-site and we are on the wrong blog, we need to switch
	if($wpdb->blogid != $blog_id)
	{
		switch_to_blog($blog_id);	
		$switched_blog = true;
	}
	
	// Check for additional item attributes via the foxypress_product_feed_additional_attributes filter
	$additional_attributes = apply_filters('foxypress_product_feed_additional_attributes', "");

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

			// Calculate item shipping weight in lbs
			$itemWeightLbs = get_post_meta($item->ID, '_weight', true);
			if (!is_numeric($itemWeightLbs)) {
				$itemWeightLbs = 0;
			}
			$itemWeightOz = get_post_meta($item->ID, '_weight2', true);
			if (!is_numeric($itemWeightOz)) {
				$itemWeightOz = 0;
			} else {
				$itemWeightOz = round($itemWeightOz / 16, 2);
			}
			$itemWeightTotal = $itemWeightLbs + $itemWeightOz;

			$itemColor = get_post_meta($item->ID, '_item_color', true);

			$XMLData .= "
		<item>
			<title>" . $item->post_title . "</title>
			<description>" . htmlspecialchars($item->post_content) . "</description>
			<g:id>" . $item->ID . "</g:id>
			<g:image_link>" . foxypress_GetMainInventoryImage($item->ID) . "</g:image_link>
			<link>" . foxypress_get_product_url($item->ID) . "</link>
			<g:mpn>" . $item->ID . "</g:mpn>
			<g:price>" .  $price  . " " . $currency_symbol . "</g:price>
			<g:condition>New</g:condition>";
			//get primary category
			$primary_category = foxypress_get_primary_category( $item->ID );
			$XMLData .= "
			<g:product_type>" . $primary_category . "</g:product_type>
			<g:quantity>" . get_post_meta($item->ID, '_quantity', true) . "</g:quantity>
			<g:shipping_weight>" . $itemWeightTotal . " lb</g:shipping_weight>
			<g:color>" . get_post_meta($item->ID, '_item_color', true) . "</g:color>" . $additional_attributes . "
		</item>";
		}
	}
	
	$XMLData .= "
	</channel>
</rss>";
	
	//restore blog
	if($switched_blog) { restore_current_blog(); }
				
	header('Content-type: text/xml');
	echo($XMLData);
?>