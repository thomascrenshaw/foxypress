<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2011 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

/***************************************************************************************************/
/***************************************************************************************************/
/*************************************** Functions For Users ***************************************/
/***************************************************************************************************/
/***************************************************************************************************/

function foxypress_GetProduct($inventory_id)
{
	global $wpdb, $post;	
	$item = $wpdb->get_row("select i.*
								,d.*
							from " . $wpdb->prefix . "posts as i
							left join " . $wpdb->prefix . "foxypress_inventory_downloadables as d on i.ID = d.inventory_id 
																									and d.status = 1
							where i.ID = '" . mysql_escape_string($inventory_id) . "'");
	if(!empty($item))
	{		
		$product = array();				
		//base info
		$product['id'] = $inventory_id;
		$product['code'] = get_post_meta($item->ID,'_code',TRUE);
		$product['name'] = $item->post_title;
		$product['description'] = $item->post_content;
		$product['url'] = foxypress_get_product_url($inventory_id);
		$product['weight'] = get_post_meta($item->ID,'_weight',TRUE);
		$product['weight2'] = get_post_meta($item->ID,'_weight2',TRUE);
		$product['quantity'] = get_post_meta($item->ID,'_quantity',TRUE);
		$product['quantity_max'] = get_post_meta($item->ID,'_quantity_max',TRUE);
		$product['quantity_min'] = get_post_meta($item->ID,'quantity_min',TRUE);	
		$product['price'] = get_post_meta($item->ID,'_price',TRUE);
		$featuredImageID = (has_post_thumbnail($item->ID)) ? get_post_thumbnail_id($item->ID) : 0;
		if($featuredImageID != 0)
		{
			$featuredSrc = wp_get_attachment_image_src($featuredImageID, 'full');
			$product['featured_image'] = array("id" => $featuredImageID, "name" => $featuredSrc[0]);
		}
		else
		{
			$product['featured_image'] = "";
		}
		$product['sale_price'] = get_post_meta($item->ID,'_saleprice',TRUE);
		$product['sale_start'] = get_post_meta($item->ID,'_salestartdate',TRUE);
		$product['sale_end'] = get_post_meta($item->ID,'_saleenddate',TRUE);
		$product['discount_quantity_amount'] = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
		$product['discount_quantity_percentage'] = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
		$product['discount_price_amount'] = get_post_meta($item->ID,'_discount_price_amount',TRUE);
		$product['discount_price_percentage'] = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
		$product['start_date'] = get_post_meta($item->ID,'_item_start_date',TRUE);
		$product['end_date'] = get_post_meta($item->ID,'_item_end_date',TRUE);
		$product['active'] = get_post_meta($item->ID,'_item_active',TRUE);
		$product['sub_frequency'] = get_post_meta($item->ID,'_sub_frequency',TRUE);
		$product['sub_startdate'] = get_post_meta($item->ID,'_sub_startdate',TRUE);
		$product['sub_enddate'] = get_post_meta($item->ID,'_sub_enddate',TRUE);
		$product['downloadable'] = ($item->downloadable_id != "") 
									? array("id" => $item->downloadable_id
											,"filename" => INVENTORY_DOWNLOADABLE_DIR . "/" . $item->filename
											,"maxdownloads" => $item->maxdownloads)
									: null; 
		//categories
		$categories = array();
		$cats = $wpdb->get_results("select itc.category_id
										, c.category_name 
										, c.category_image
									from " . $wpdb->prefix . "foxypress_inventory_to_category as itc
									inner join  " . $wpdb->prefix . "foxypress_inventory_categories as c on itc.category_id = c.category_id
									where itc.inventory_id = '" . $inventory_id . "'");
		if(!empty($cats))
		{
			foreach($cats as $cat)
			{
				
				$categories[] = array("id" => $cat->category_id, "name" => $cat->category_name, "image" => INVENTORY_IMAGE_DIR . "/" . $cat->category_image);	
			}
		}
		$product['categories'] = $categories;		
		
		//images 
		$images = array();
		$current_images = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $inventory_id, 'order' => 'ASC','orderby' => 'menu_order', 'post_mime_type' => 'image'));
		foreach ($current_images as $img) 
		{
			$src = wp_get_attachment_image_src($img->ID, 'full');
			$images[] = array("id" => $img->ID, "name" => $src[0], "order" => $img->menu_order);	
		}
		$product['images'] = $images;
		
		//options
		$options = array();
		$opts = $wpdb->get_results("select o.*, og.option_group_name
									from " . $wpdb->prefix . "foxypress_inventory_options as o
									inner join " . $wpdb->prefix . "foxypress_inventory_option_group as og on o.option_group_id = og.option_group_id
									where o.inventory_id = '" . $inventory_id . "' 
									order by o.option_order");
		if(!empty($opts))
		{
			foreach($opts as $opt)
			{
				$options[] = array("id" => $opt->option_id
								   ,"group_id" => $opt->option_group_id
								   ,"group_name" => $opt->option_group_name
								   ,"text" => $opt->option_text
								   ,"value" => $opt->option_value
								   ,"extra_price" => $opt->option_extra_price
								   ,"extra_weight" => $opt->option_extra_weight
								   ,"code" => $opt->option_code
								   ,"quantity" => $opt->option_quantity
								   ,"active" => $opt->option_active
								   ,"order" => $opt->option_order);	
			}
		}
		$product['options'] = $options;
		
		//attributes
		$attributes = array();
		$atts = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_attributes where inventory_id = '" . $inventory_id . "' order by attribute_id");
		if(!empty($atts))
		{
			foreach($atts as $att)
			{
				$attributes[] = array("id" => $att->attribute_id, "text" => $att->attribute_text, "value" => $att->attribute_value);	
			}
		}
		$product['attributes'] = $attributes;
		
		return $product;
	
	}
	return null;	
}

function foxypress_GetProductQuantitySold($inventory_id)
{
	global $wpdb, $post;
	$code = get_post_meta($inventory_id,'_code',TRUE);
	$quantitySold = 0;
	$foxyStoreURL = get_option('foxycart_storeurl');
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . ".foxycart.com/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_list";
	$foxyData["transaction_date_filter_begin"] = $StartDate;
	$foxyData["transaction_date_filter_end"] = $EndDate;
	$foxyData["hide_transaction_filter"] = "";
	$foxyData["is_test_filter"] = "";	
	$foxyData["product_code_filter"] =  $code;	
	//$foxyData["pagination_start"] = $PageStart;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);
	$Orders = array();
	if($foxyXMLResponse->result == "SUCCESS")
	{
		foreach($foxyXMLResponse->transactions->transaction as $t)
		{
			foreach($t->transaction_details->transaction_detail as $td)
			{
				if($code == $td->product_code)
				{
					$quantitySold = $quantitySold + (int)$td->product_quantity;
				}
			}
		}
	}
	return $quantitySold;
}

function foxypress_GetProductsByCategory($category_id, $items_per_page = 999999, $page_number = 1)
{
	global $wpdb;
	$start = ($page_number != "" && $page_number != "0") ? ($page_number - 1) * $items_per_page : 0;
	$drRows = $wpdb->get_row("SELECT count(i.ID) as RowCount
								FROM " . $wpdb->prefix . "posts as i
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic ON i.ID=ic.inventory_id and
																								ic.category_id = '" .  $category_id . "'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'	
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'													
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE. "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())	
								ORDER BY ic.sort_order, i.ID DESC");
	$total_pages = ceil($drRows->RowCount/$items_per_page);
	$items = $wpdb->get_results("SELECT i.ID									
								FROM " . $wpdb->prefix . "posts as i
								INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic ON i.ID=ic.inventory_id and
																								ic.category_id = '" .  $category_id . "'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'	
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'													
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE. "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())	
								ORDER BY ic.sort_order, i.ID DESC
								LIMIT $start, $items_per_page");
	$products = array();
	$paging = array("total_pages" => $total_pages, "total_items" => $drRows->RowCount, "current_page" => $page_number, "items_per_page" => $items_per_page);
	if(!empty($items))
	{
		foreach($items as $item)
		{
			$product = foxypress_GetProduct($item->ID);
			if(!empty($product) && $product != null)
			{
				$products[] = $product;
			}
		}
		return array("products" => $products, "pagination" => $paging);
	}
	return null;
}

function foxypress_GetProducts($items_per_page = 999999, $page_number = 1, $include_history="1")
{
	global $wpdb;
	if ( $include_history == "1" ){
		$historySQL="AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())";
	}
	$start = ($page_number != "" && $page_number != "0") ? ($page_number - 1) * $items_per_page : 0;
	$drRows = $wpdb->get_row("SELECT count(i.ID) as RowCount
								FROM " . $wpdb->prefix . "posts as i
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'	
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'													
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									" . $historySQL . "								
								ORDER BY i.ID DESC");
	$total_pages = ceil($drRows->RowCount/$items_per_page);
	$items = $wpdb->get_results("SELECT i.ID									
								FROM " . $wpdb->prefix . "posts as i
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																						and pm_start_date.meta_key = '_item_start_date'	
								LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																						and pm_end_date.meta_key = '_item_end_date'													
								WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
									AND i.post_status = 'publish'
									AND pm_active.meta_value = '1'
									" . $historySQL . "								
								ORDER BY i.ID DESC
								LIMIT $start, $items_per_page");
	$products = array();
	$paging = array("total_pages" => $total_pages, "total_items" => $drRows->RowCount, "current_page" => $page_number, "" => $items_per_page);
	if(!empty($items))
	{
		foreach($items as $item)
		{
			$product = foxypress_GetProduct($item->ID);
			if(!empty($product) && $product != null)
			{
				$products[] = $product;
			}
		}
		return array("products" => $products, "pagination" => $paging);
	}
	return null;
}

function foxypress_SearchProducts($search_term, $items_per_page = 999999, $page_number = 1)
{
	global $wpdb;
	$start = ($page_number != "" && $page_number != "0") ? ($page_number - 1) * $items_per_page : 0;
	$drRows = $wpdb->get_row("SELECT SUM(SubRowCount) as RowCount FROM
								(
								  SELECT count(i.ID) as SubRowCount
									FROM " . $wpdb->prefix ."posts as i
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																				and pm_active.meta_key = '_item_active'
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																							and pm_start_date.meta_key = '_item_start_date'	
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																							and pm_end_date.meta_key = '_item_end_date'	
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																							and pm_code.meta_key = '_code'													
									WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
										AND i.post_status = 'publish'
										AND pm_active.meta_value = '1'
										AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
										AND (
												pm_code.meta_value = '" . mysql_escape_string($search_term) . "'
													or i.post_title = '" . mysql_escape_string($search_term) . "'
													or i.post_content = '" . mysql_escape_string($search_term) . "'
											)
								  UNION
								  SELECT count(i.ID) as SubRowCount
									FROM " . $wpdb->prefix ."posts as i
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																				and pm_active.meta_key = '_item_active'
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																							and pm_start_date.meta_key = '_item_start_date'	
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																							and pm_end_date.meta_key = '_item_end_date'	
									LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																							and pm_code.meta_key = '_code'													
									WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
										AND i.post_status = 'publish'
										AND pm_active.meta_value = '1'
										AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
										AND (
												pm_code.meta_value LIKE '%" . mysql_escape_string($search_term) . "%'
													or i.post_title LIKE '%" . mysql_escape_string($search_term) . "%'
													or i.post_content LIKE '%" . mysql_escape_string($search_term) . "%'
											)
								) as Counts");	
	$total_pages = ceil($drRows->RowCount/$items_per_page);
	$searchSQL = "(SELECT i.* 
					FROM " . $wpdb->prefix ."posts as i
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																and pm_active.meta_key = '_item_active'
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																			and pm_start_date.meta_key = '_item_start_date'	
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																			and pm_end_date.meta_key = '_item_end_date'	
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																			and pm_code.meta_key = '_code'													
					WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
						AND i.post_status = 'publish'
						AND pm_active.meta_value = '1'
						AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
						AND (
								pm_code.meta_value = '" . mysql_escape_string($search_term) . "'
									or i.post_title = '" . mysql_escape_string($search_term) . "'
									or i.post_content = '" . mysql_escape_string($search_term) . "'
							)
					ORDER BY i.ID
				  )
				  UNION
				  (SELECT i.* FROM " . $wpdb->prefix ."posts as i
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																and pm_active.meta_key = '_item_active'
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																			and pm_start_date.meta_key = '_item_start_date'	
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																			and pm_end_date.meta_key = '_item_end_date'	
					LEFT JOIN " . $wpdb->prefix . "postmeta as pm_code on i.ID = pm_code.post_ID
																			and pm_code.meta_key = '_code'													
					WHERE i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
						AND i.post_status = 'publish'
						AND pm_active.meta_value = '1'
						AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
						AND (
								pm_code.meta_value LIKE '%" . mysql_escape_string($search_term) . "%'
									or i.post_title LIKE '%" . mysql_escape_string($search_term) . "%'
									or i.post_content LIKE '%" . mysql_escape_string($search_term) . "%'
							)
					ORDER BY i.ID
				  ) 
				  LIMIT $start, $items_per_page";
	$items = $wpdb->get_results($searchSQL);
	$products = array();
	$paging = array("total_pages" => $total_pages, "total_items" => $drRows->RowCount, "current_page" => $page_number, "items_per_page" => $items_per_page);
	if(!empty($items))
	{
		foreach($items as $item)
		{
			$product = foxypress_GetProduct($item->ID);
			if(!empty($product) && $product != null)
			{
				$products[] = $product;
			}
		}
		return array("products" => $products, "pagination" => $paging);
	}
	return null;   
}

function foxypress_GetProductFormEnd()
{
	return "</form>";	
}

function foxypress_GetProductFormStart($inventory_id, $form_id = "foxypress_form", $include_quantity_field = true)
{
	global $wpdb, $post;
	$form = "";
	$item = $wpdb->get_row("SELECT i.*
									,c.category_name									
									,d.downloadable_id
							FROM " . $wpdb->prefix . "posts as i
							INNER JOIN (SELECT min( itc_id ) AS itc_id, inventory_id, category_id
											FROM " . $wpdb->prefix . "foxypress_inventory_to_category
											GROUP BY inventory_id) as ic on i.ID = ic.inventory_id
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id							
							LEFT JOIN " . $wpdb->prefix . "foxypress_inventory_downloadables as d on i.ID = d.inventory_id 
																								and d.status = 1
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																					and pm_start_date.meta_key = '_item_start_date'	
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																					and pm_end_date.meta_key = '_item_end_date'													
							WHERE (i.ID = '" . mysql_escape_string($inventory_id) . "')
								AND i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE . "'
								AND i.post_status = 'publish'
								AND pm_active.meta_value = '1'
								AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							");
	if(empty($item)){ return ""; }
	$_code = get_post_meta($item->ID,'_code',TRUE);
	$_name = $item->post_title;
	$_description = $item->post_content;
	$_weight = get_post_meta($item->ID,'_weight',TRUE);
	$_weight2 = get_post_meta($item->ID,'_weight2',TRUE);
	$_quantity = get_post_meta($item->ID,'_quantity',TRUE);
	$_quantity_min = get_post_meta($item->ID,'_quantity_min',TRUE);
	$_quantity_max = get_post_meta($item->ID,'_quantity_max',TRUE);	
	$_price = get_post_meta($item->ID,'_price',TRUE);	
	$_sale_price = get_post_meta($item->ID,'_saleprice',TRUE);
	$_sale_start = get_post_meta($item->ID,'_salestartdate',TRUE);
	$_sale_end = get_post_meta($item->ID,'_saleenddate',TRUE);
	$_discount_quantity_amount = get_post_meta($item->ID,'_discount_quantity_amount',TRUE);
	$_discount_quantity_percentage = get_post_meta($item->ID,'_discount_quantity_percentage',TRUE);
	$_discount_price_amount = get_post_meta($item->ID,'_discount_price_amount',TRUE);
	$_discount_price_percentage = get_post_meta($item->ID,'_discount_price_percentage',TRUE);
	$_start_date = get_post_meta($item->ID,'_item_start_date',TRUE);
	$_end_date = get_post_meta($item->ID,'_item_end_date',TRUE);
	$_active = get_post_meta($item->ID,'_item_active',TRUE);
	$_sub_frequency = get_post_meta($item->ID,'_sub_frequency',TRUE);
	$_sub_startdate = get_post_meta($item->ID,'_sub_startdate',TRUE);
	$_sub_enddate = get_post_meta($item->ID,'_sub_enddate',TRUE);	
	$_item_deal_active = get_post_meta($item->ID,'_item_deal_active',TRUE);
	$_item_deal_code_type = get_post_meta($item->ID,'_item_deal_code_type',TRUE);
	$_item_deal_static_code = get_post_meta($item->ID,'_item_deal_static_code',TRUE);
	$ActualPrice = foxypress_GetActualPrice($_price, $_sale_price, $_sale_start, $_sale_end);
	$main_inventory_image = foxypress_GetMainInventoryImage($item->ID);

	
	$form  = "<form action=\"https://" . get_option('foxycart_storeurl') . ".foxycart.com/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"" . $form_id . "\">"
			.
			( 
				($include_quantity_field)
					? "<input type=\"hidden\" name=\"quantity\" value=\"1\" />"
					: ""
			 )
			.
			"<input type=\"hidden\" name=\"name\" value=\"" . stripslashes($item->post_title) . "\" />
			<input type=\"hidden\" name=\"code\" value=\"" . stripslashes($_code) . "\" />
			<input type=\"hidden\" name=\"price\" value=\"" . $ActualPrice . "\" />
			<input type=\"hidden\" name=\"category\" value=\"" . stripslashes($item->category_name) . "\" />
			<input type=\"hidden\" name=\"image\" value=\"" . ( ($main_inventory_image != "") ? $main_inventory_image : INVENTORY_IMAGE_DIR . '/' . INVENTORY_DEFAULT_IMAGE ) . "\" />
			<input type=\"hidden\" name=\"weight\" value=\"" . foxypress_GetActualWeight($_weight, $_weight2) . "\" />
			<input type=\"hidden\" name=\"inventory_id\" value=\"" . $item->ID . "\" />
			<input type=\"hidden\" name=\"h:blog_id\" value=\"" . $wpdb->blogid . "\" />
			<input type=\"hidden\" name=\"h:affiliate_id\" value=\"" . $_SESSION['affiliate_id'] . "\" />"
			 .
				( ($_item_deal_active == "1" && $_item_deal_code_type == "static")
					? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . $_item_deal_static_code . "\" />"
					: ""
				)
			 .
			 	( ($_item_deal_active == "1" && $_item_deal_code_type == "random")
					? "<input type=\"hidden\" name=\"coupon_code\" value=\"" . getGUID() . "\" />"
					: ""
				)
			 .
				( (get_option('foxypress_include_memberid') == "1")
					? "<input type=\"hidden\" name=\"h:m_id\" value=\"" . $_SESSION["MEMBERID"] . "\" />"
					: ""
				)
			 .
				foxypress_GetMinMaxFormFields($item->downloadable_id, $_quantity_min, $_quantity_max, $_quantity)
			 .
				( ($_discount_quantity_amount != "") 
					? "<input type=\"hidden\" name=\"discount_quantity_amount\" value=\"" . stripslashes($_discount_quantity_amount) . "\" />" 
					: "" 
				)									 
			 .
				( ($_discount_quantity_percentage != "") 
					? "<input type=\"hidden\" name=\"discount_quantity_percentage\" value=\"" . stripslashes($_discount_quantity_percentage) . "\" />" 
					: "" 
				)	
			 .
				( ($_discount_price_amount != "") 
					? "<input type=\"hidden\" name=\"discount_price_amount\" value=\"" . stripslashes($_discount_price_amount) . "\" />" 
					: "" 
				)	
			 .
				( ($_discount_price_percentage != "") 
					? "<input type=\"hidden\" name=\"discount_price_percentage\" value=\"" . stripslashes($_discount_price_percentage) . "\" />" 
					: "" 
				)	
			 .
				( ($_sub_frequency != "") 
					? "<input type=\"hidden\" name=\"sub_frequency\" value=\"" . stripslashes($_sub_frequency) . "\" />" 
					: "" 
				)								 
			 .
				( ($_sub_startdate != "") 
					? "<input type=\"hidden\" name=\"sub_startdate\" value=\"" . stripslashes($_sub_startdate) . "\" />" 
					: "" 
				)
			 .
				( ($_sub_enddate != "") 
					? "<input type=\"hidden\" name=\"sub_enddate\" value=\"" . stripslashes($_sub_enddate) . "\" />" 
					: "" 
				)
			  . foxypress_BuildAttributeForm($inventory_id);

	return $form;
}	

function foxypress_GetCategories()
{	
	global $wpdb;	
	$categories = array();
	$cats = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories ORDER BY  category_id");
	if(!empty($cats))
	{
		foreach($cats as $cat)
		{
			$categories[] = array("id" => $cat->category_id, "name" => $cat->category_name, "image" => INVENTORY_IMAGE_DIR . "/" . $cat->category_image);	
		}
		return $categories;
	}	
	return null;
}


function foxypress_GetCategoryCount($category_id)
{
	global $wpdb;
	$item = $wpdb->get_row("SELECT count(i.ID) as ItemCount
							FROM " . $wpdb->prefix . "posts as i
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_to_category as ic ON i.ID=ic.inventory_id
							INNER JOIN " . $wpdb->prefix . "foxypress_inventory_categories as c ON ic.category_id = c.category_id
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_active on i.ID = pm_active.post_ID
																						and pm_active.meta_key = '_item_active'
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_start_date on i.ID = pm_start_date.post_ID
																					and pm_start_date.meta_key = '_item_start_date'	
							LEFT JOIN " . $wpdb->prefix . "postmeta as pm_end_date on i.ID = pm_end_date.post_ID
																					and pm_end_date.meta_key = '_item_end_date'													
							WHERE c.category_id='" . $category_id . "'
								AND i.post_type = '" . FOXYPRESS_CUSTOM_POST_TYPE. "'
								AND i.post_status = 'publish'
								AND pm_active.meta_value = '1'
								AND (coalesce(pm_start_date.meta_value, now()) <= now() AND coalesce(pm_end_date.meta_value, now()) >= now())
							");
	if(!empty($item))
	{
		return $item->ItemCount;
	}
	return "0";
}

function foxypress_GetTrackingModule()
{
	$url = plugins_url() . "/foxypress/ajax.php";
	$trackingform = "<div> Enter your order number </div>
		 <div><input type=\"text\" id=\"foxypress_order_number\" name=\"foxypress_order_number\" value=\"\" /></div>
		 <div> Enter your last name </div>
		 <div><input type=\"text\" id=\"foxypress_order_name\" name=\"foxypress_order_name\" value=\"\" /></div>
		 <div><input type=\"button\" id=\"foxypress_tracking_button\"	name=\"foxypress_tracking_button\"	value=\"Find Tracking Number\" onclick=\"foxypress_find_tracking('" . $url . "');\" />
		 </div>
		 <div id=\"foxypress_find_tracking_return\"></div>
	 ";
	return $trackingform;	
}

function foxypress_GetMiniCartWidget($dropdowndisplay, $hideonzero)
{
	if ( $dropdowndisplay == "" )
	{
		if ( $hideonzero == "1" )
		{
			?> <div id="fc_minicart"> <?php
		}
		?>
			<span id="fc_quantity">0</span> items.<br />
			<span id="fc_total_price">0.00</span>
			<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?cart=view" class="foxycart">View Cart</a>
		<?php
		if ( $hideonzero == "1" )
		{
			?> </div> <?php
		}
	}
	else
	{
		?>
		<a href="#" class="fc_link"><strong>Your Cart</strong></a>
		<div id="fc_cart">
			<img src="<?php echo(plugins_url()) ?>/foxypress/img/cart.png" alt="your cart"/>
			<h2>Your Cart</h2>
			<div class="fc_clear"></div>
			<table>
				<thead>
				<th>item</th>
				<th>qty</th>
				<th>price</th>
				</thead>
				<tbody id="cart_content">
				</tbody>
			</table>
			<a href="https://<?php echo(get_option('foxycart_storeurl')) ?>.foxycart.com/cart?checkout" id="fc_checkout_link">Check Out</a>
			<div class="fc_clear"></div>
		</div>
		<script type="text/javascript" charset="utf-8">
			var StoreURL = '<?php echo(get_option('foxycart_storeurl')) ?>';
			var FoxyDomain = StoreURL + ".foxycart.com/";
			var timer = 0;
			// this function hides the cart in a very nice way
			function json_cart_fade_out(){
				 if (timer != 0){
					  clearTimeout(timer);
					  timer = 0;
				 }
				 $("#fc_cart").animate({
					  top: 0 - $("#fc_cart").height(),
					  opacity: 0
				  }, 1000);
			}
			$(document).ready(function(){
				fcc.events.cart.postprocess.add(function(){
					fcc.cart_update.call(fcc);
					jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
						console.info(cart.product_count);
						console.info(cart.total_price);
						console.info(cart.total_discount);
						var total_price = cart.total_price - cart.total_discount;
						fc_FoxyCart = "";
						for (i=0;i<cart.products.length;i++) {
								fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
						}
						// fc_FoxyCart is a javascript variable that now holds your shopping cart data
						// if you have some products in your cart, why not display it?
						if (cart.products.length > 0) {
								$("#fc_cart #cart_content").html(fc_FoxyCart);
						} else {
								$("#fc_cart #cart_content").html("");
						}
					});
				});
				jQuery.getJSON('https://'+storedomain+'/cart?'+fcc.session_get()+'&output=json&callback=?', function(cart) {
					console.info(cart.product_count);
					console.info(cart.total_price);
					console.info(cart.total_discount);
					var total_price = cart.total_price - cart.total_discount;
					fc_FoxyCart = "";
					for (i=0;i<cart.products.length;i++) {
							fc_BuildFoxyCartRow(cart.products[i].name,cart.products[i].code,cart.products[i].options,cart.products[i].quantity,cart.products[i].price_each,cart.products[i].price);
					}
					// fc_FoxyCart is a javascript variable that now holds your shopping cart data
					// if you have some products in your cart, why not display it?
					if (cart.products.length > 0) {
							$("#fc_cart #cart_content").html(fc_FoxyCart);
					} else {
							$("#fc_cart #cart_content").html("");
					}
				});
			   // shows the cart when the mouse is positioned on an specific link
			   $(".fc_link").mouseover(function(){

				   if (timer!= 0){
						clearTimeout(timer);
						timer = 0;
				   }

				   $("#fc_cart").animate({
							 opacity: '.99',
							 top: '0px'
					   }, 1000);
				   timer = setTimeout(function(){
					  json_cart_fade_out();
				   }, 2500);
				   // if the user is looking/using the cart don't hide it
				   $("#fc_cart").hover(function(){
					  clearTimeout(timer);
					  timer = 0;
				   }, function(){
					  timer = setTimeout(function(){
						json_cart_fade_out();
					  }, 1000);
				   });

			   });

			});

			function fc_BuildFoxyCartRow(fc_name,fc_code,fc_options,fc_quantity,fc_price_each,fc_price) {
					fc_FoxyCart += "<tr>";
					fc_FoxyCart += "<td>" + fc_name + "</td>";
			//      fc_FoxyCart += "<td>" + fc_options + "</td>";
			//      fc_FoxyCart += "<td>" + fc_code + "</td>";
					fc_FoxyCart += "<td class=\"right-align\">" + fc_quantity + "</td>";
			//      fc_FoxyCart += "<td>" + fc_price_each + "</td>";
					fc_FoxyCart += "<td class=\"right-align\">" + fc_price.toFixed(2) + "</td>";
					fc_FoxyCart += "</tr>";
			}
		</script>
	<?php
	}
}
/***************************************************************************************************/
/***************************************************************************************************/
/*********************************** End Functions For Users ***************************************/
/***************************************************************************************************/
/***************************************************************************************************/
?>