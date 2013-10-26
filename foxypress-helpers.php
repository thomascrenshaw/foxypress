<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
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
								   ,"order" => $opt->option_order
								   ,"image" => $opt->option_image);
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
	
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
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

function foxypress_GetProducts($items_per_page = 999999, $page_number = 1, $onlyShowActive = "1")
{
	global $wpdb;
	if ( $onlyShowActive == "1" ){
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

	$primaryCategories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id, itc.category_primary
												FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " as itc inner join " .
												$wpdb->prefix . "foxypress_inventory_categories" . " as c on itc.category_id = c.category_id
												WHERE inventory_id='" . $inventory_id . "'");
	$primary_category = "";
	foreach($primaryCategories as $pc)
	{
		if($pc->category_primary == 1) {
			$primary_category = $pc->category_name;
		} 
	}
	
	//use previous category name if a new primary one hasn't been selected yet
	if($primary_category == "") {
		$primary_category = stripslashes($item->category_name);
	}
	
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$storeURL = get_option('foxycart_storeurl');
	}else{
		$storeURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	$form  = "<form action=\"https://" . $storeURL . "/cart\" method=\"POST\" class=\"foxycart\" accept-charset=\"utf-8\" id=\"" . $form_id . "\">"
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
			<input type=\"hidden\" name=\"category\" value=\"" . $primary_category . "\" />
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

function foxypress_GetCategoryByID($category_id)
{
	global $wpdb;
	$item = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_categories WHERE category_id='" . $category_id . "'");
							
	if(!empty($item)){
		$category = array();
		$category['id'] = $item->category_id;
		$category['name'] = $item->category_name;
		$category['image'] = INVENTORY_IMAGE_DIR . "/" . $item->category_image;
	}
	return $category;
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

/**
 * Get all related items for a post
 *
 * @since 0.4.3.4
 * 
 * @param int $post_id Product post ID
 * @return bool|array Returns an array of related items, or false if no related items are available
 */
function foxypress_GetRelatedItems($post_id) 
{
	$related_item_string = get_post_meta($post_id, '_relatedProducts', true);
	
	if ($related_item_string === "") 
	{
		// No related items stored in post meta
		return false;
	} 
	else 
	{
		// At least one related item. Split into array
		$related_items = split(",", $related_item_string);
		return $related_items;
	}
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
	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$storeURL = get_option('foxycart_storeurl');
	}else{
		$storeURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	if ( $dropdowndisplay == "" )
	{
		if ( $hideonzero == "1" )
		{
			?> <div id="fc_minicart"> <?php
		}
		?>
			<span id="fc_quantity">0</span> items.<br />
			<span id="fc_total_price">0.00</span>
			<a href="https://<?php echo($storeURL) ?>/cart?cart=view" class="foxycart">View Cart</a>
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
			<a href="https://<?php echo(storeURL) ?>/cart?checkout" id="fc_checkout_link">Check Out</a>
			<div class="fc_clear"></div>
		</div>
		<script type="text/javascript" charset="utf-8">
			var StoreURL = '<?php echo($storeURL) ?>';
			var FoxyDomain = StoreURL + "/";
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

function foxypress_GetUserTransactions($user_email, $type = '')
{
	global $wpdb, $post;

	$sql = "SELECT *, fts.foxy_transaction_status, fts.foxy_transaction_status_description
			FROM " . $wpdb->prefix ."foxypress_transaction
			LEFT JOIN " . $wpdb->prefix . "foxypress_transaction_status AS fts ON " . $wpdb->prefix ."foxypress_transaction.foxy_transaction_status = fts.foxy_transaction_status
			WHERE foxy_blog_id = " . (foxypress_IsMultiSite() ? "'" . $wpdb->blogid . "'" : "foxy_blog_id") . "
			AND foxy_transaction_email = '" . $user_email . "'";

	if ($type == 'current') {
		$sql .= " AND foxy_transaction_trackingnumber IS NULL";
	}

	if ($type == 'past') {
		$sql .= " AND foxy_transaction_trackingnumber IS NOT NULL";
	}
	
	$sql .= " ORDER BY foxy_transaction_id desc";

	$trans = $wpdb->get_results($sql);

	if (!empty($trans))
	{
		$transactions = array();

		foreach ($trans as $t)
		{
			$transactions[] = array(
				"id" 				=> $t->foxy_transaction_id,
				"status" 			=> $t->foxy_transaction_status_description,
				"first_name" 		=> $t->foxy_transaction_first_name,
				"last_name" 		=> $t->foxy_transaction_last_name,
				"email" 			=> $t->foxy_transaction_email,
				"tracking_number" 	=> $t->foxy_transaction_trackingnumber,
				"rma_number" 		=> $t->foxy_transaction_rmanumber,
				"billing_address1" 	=> $t->foxy_transaction_billing_address1,
				"billing_address2" 	=> $t->foxy_transaction_billing_address2,
				"billing_city" 		=> $t->foxy_transaction_billing_city,
				"billing_state" 	=> $t->foxy_transaction_billing_state,
				"billing_zip" 		=> $t->foxy_transaction_billing_zip,
				"billing_country" 	=> $t->foxy_transaction_billing_country,
				"shipping_address1" => $t->foxy_transaction_shipping_address1,
				"shipping_address2" => $t->foxy_transaction_shipping_address2,
				"shipping_city" 	=> $t->foxy_transaction_shipping_city,
				"shipping_state" 	=> $t->foxy_transaction_shipping_state,
				"shipping_zip" 		=> $t->foxy_transaction_shipping_zip,
				"shipping_country" 	=> $t->foxy_transaction_shipping_country,
				"date" 				=> $t->foxy_transaction_date,
				"product_total" 	=> $t->foxy_transaction_product_total,
				"tax_total" 		=> $t->foxy_transaction_tax_total,
				"shipping_total" 	=> $t->foxy_transaction_shipping_total,
				"order_total" 		=> $t->foxy_transaction_order_total
			);
		}
		return $transactions;
	}
	return null;
}

function foxypress_GetTransactionDetails($transaction)
{
	global $wpdb, $post;

	$remoteDomain = get_option('foxycart_remote_domain');
	if($remoteDomain){
		$foxyStoreURL = get_option('foxycart_storeurl');
	}else{
		$foxyStoreURL = get_option('foxycart_storeurl') . ".foxycart.com";
	}
	
	$foxyAPIKey =  get_option('foxycart_apikey');
	$foxyAPIURL = "https://" . $foxyStoreURL . "/api";
	$foxyData = array();
	$foxyData["api_token"] =  $foxyAPIKey;
	$foxyData["api_action"] = "transaction_get";
	$foxyData["transaction_id"] = $transaction;
	$SearchResults = foxypress_curlPostRequest($foxyAPIURL, $foxyData);
	$foxyXMLResponse = simplexml_load_string($SearchResults, NULL, LIBXML_NOCDATA);

	if($foxyXMLResponse->result == "SUCCESS")
	{
		$i=1;
		$items = array();
		foreach($foxyXMLResponse->transaction->transaction_details->transaction_detail as $td)
		{
			$Downloadable = false;
			$Inventory_ID = "";
			$options = array();
			foreach($td->transaction_detail_options->transaction_detail_option as $opt)
			{
				if(strtolower($opt->product_option_name) == "inventory_id")
				{
					$Inventory_ID = $opt->product_option_value;
				}
				else
				{
					$options[] =  array(
						"option_name"  => $opt->product_option_name,
						"option_value" => $opt->product_option_value
					);
				}
			}								
												
			//check if the item is a downloadable
			$dt_downloadable = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_downloadables WHERE inventory_id = '" . mysql_escape_string($Inventory_ID) . "' and status = '1'");
			if(!empty($dt_downloadable) && count($dt_downloadable) > 0)
			{
				$Downloadable = true;
			}	
					
			//check to see if we need to show downloadable information
			if($Downloadable && $Inventory_ID != "")
			{
				$dt = $wpdb->get_row("SELECT dt.* 
									  FROM " . $wpdb->prefix . "foxypress_inventory_downloadables as d 
									  INNER JOIN " . $wpdb->prefix . "foxypress_downloadable_transaction as dt on dt.downloadable_id = d.downloadable_id
									  and dt.foxy_transaction_id = '" . $foxyXMLResponse->transaction->id . "'
									  WHERE d.inventory_id = '" . mysql_escape_string($Inventory_ID) . "'");
				//generate url
				$DownloadURL = plugins_url() . "/foxypress/download.php?d=" . urlencode(foxypress_Encrypt($dt->downloadable_id)) . "&t=" . urlencode(foxypress_Encrypt($dt->download_transaction_id)) . "&b=" . urlencode(foxypress_Encrypt($BlogID));

				$options .= "<a href=\"" . $DownloadURL . "\">Downloadable Link</a><br />";
			} 

			$items[] = array(
				"product_code"    => $td->product_code,
				"product_name" 	  => $td->product_name,
				"product_price"   => $td->product_price,
				"product_options" => $options
			);
	
			$i+=1;
		}
	return $items;		
	}//end check for success
	else
	{
		return null;
	}
}

function foxypress_GetBanner($banner_id)
{
	global $wpdb;

	$data = "SELECT *
				FROM " . $wpdb->prefix . "foxypress_affiliate_assets
				WHERE id = " . $banner_id;

    return $wpdb->get_results($data);
}

function foxypress_GetBanners()
{
	global $wpdb;

	$data = "SELECT * FROM " . $wpdb->prefix . "foxypress_affiliate_assets";

    return $wpdb->get_results($data);
}

function foxypress_IsAffiliate($user_id)
{
	global $wpdb;

	$affiliate_user = get_user_option('affiliate_user', $user_id);
	if ($affiliate_user == 'true')
	{
		return true;
	}
	else
	{
		return false;
	}
	return false;

}

function foxypress_IsReferral($user_id)
{
	global $wpdb;

	$affiliate_user = get_user_option('affiliate_referral', $user_id);
	if ($affiliate_user == 'true')
	{
		return true;
	}
	else
	{
		return false;
	}
	return false;
}

function foxypress_GetAffiliateData($affiliate_id)
{
	global $wpdb;

	$referral = get_user_option('affiliate_referral', $affiliate_id);

	// Get ids of affiliate referrals
    $sql = "SELECT foxy_affiliate_id FROM " . $wpdb->prefix . "foxypress_affiliate_referrals
                WHERE foxy_affiliate_referred_by_id = " . $affiliate_id;

    $referred_affiliate_ids = $wpdb->get_results($sql);

    $commission_ids = array();
    $i = 0;
    foreach ($referred_affiliate_ids as $referred_affiliate)
    {
        $commission_ids[$i] = $referred_affiliate->foxy_affiliate_id;
        $i++;
    }
    $commission_id_array = $commission_ids;
    $commission_ids = implode(',', $commission_ids);

    array_push($commission_id_array, $affiliate_id);
    $commission_ids_user = implode(',', $commission_id_array);

    // Get counts for user with referrals or without
    if ($referral == 'true' && $referred_affiliate_ids) {
        $data = "SELECT 
                (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = " . $affiliate_id . ") AS num_clicks,
                (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id IN (" . $commission_ids . "," . $affiliate_id . ")) AS num_total_orders,
                (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . ") AS num_paid_orders,
                (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS num_unpaid_orders,
                (SELECT sum(foxy_transaction_product_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS total_unpaid_amount,
                (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '2')) AS num_unpaid_referral_orders,
                (SELECT sum(foxy_transaction_product_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_referral_amount,
                (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_affiliate_commission_type = '1') AS total_paid_amount,
                (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_referrals WHERE foxy_affiliate_referred_by_id = " . $affiliate_id . ") AS num_referrals,
                (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_affiliate_commission_type = '2') AS total_paid_referral_amount";

    } else {
        $data = "SELECT 
            (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = " . $affiliate_id . ") AS num_clicks,
            (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_referrals WHERE foxy_affiliate_referred_by_id = " . $affiliate_id . ") AS num_referrals,
            (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id = " . $affiliate_id . ") AS num_total_orders,
            (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . ") AS num_paid_orders,
            (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS num_unpaid_orders,
            (SELECT sum(foxy_transaction_product_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS total_unpaid_amount,
            (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_affiliate_commission_type = '1') AS total_paid_amount";
    }

    $order_detail = $wpdb->get_results($data);
    
    // Get all open orders
    $open_orders_data = "SELECT ft.foxy_transaction_id AS order_id, ft.foxy_transaction_product_total AS product_total, ft.foxy_transaction_order_total AS order_total, ft.foxy_transaction_date AS order_date, u.id, u.user_nicename
                	FROM " . $wpdb->prefix . "foxypress_transaction AS ft
                	LEFT JOIN " . $wpdb->base_prefix . "users AS u ON u.id = ft.foxy_affiliate_id
                	WHERE ft.foxy_affiliate_id IN (" . $commission_ids_user . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND foxy_affiliate_id = " . $affiliate_id . ")
                	ORDER BY ft.foxy_transaction_id DESC";

    $user_open_orders = $wpdb->get_results($open_orders_data);

    // Get all paid orders
    $paid_orders_data = "SELECT *
                		FROM " . $wpdb->prefix . "foxypress_affiliate_payments
                		WHERE foxy_affiliate_id = " . $affiliate_id . " 
                		ORDER BY foxy_transaction_id DESC";

    $user_paid_orders = $wpdb->get_results($paid_orders_data);

    // User payment fields
    $user_payout_type = get_user_option('affiliate_payout_type', $affiliate_id);
    $user_payout = get_user_option('affiliate_payout', $affiliate_id);
    $user_referral = get_user_option('affiliate_referral', $affiliate_id);
    $user_referral_payout_type = get_user_option('affiliate_referral_payout_type', $affiliate_id);
    $user_referral_payout = get_user_option('affiliate_referral_payout', $affiliate_id);
    $user_affiliate_url = get_user_option('affiliate_url', $affiliate_id);

    // Create open orders array
    if (!empty($user_open_orders))
    {
	    $open_orders = array();
	    foreach ($user_open_orders as $uoo)
	    {	
	    	// Calculate order commission
	    	if ($uoo->id == $affiliate_id)
	    	{
	            if ($user_payout_type == 1)
	            {
	                $order_commission = $user_payout / 100 * $uoo->product_total;
	                $order_commission = number_format($order_commission, 2, '.', ',');
	            }
	            else
	            {
	                $order_commission  = number_format($user_payout, 2, '.', ',');
	            }
	            $order_type = 'Regular';
	        }
	        else
	        {
	            if ($user_referral_payout_type == 1)
	            {
	                $order_commission = $user_referral_payout / 100 * $uoo->product_total;
	                $order_commission = number_format($order_commission, 2, '.', ',');
	            }
	            else
	            {
	                $order_commission  = number_format($user_referral_payout, 2, '.', ',');
	            }
	            $order_type = 'Referral';
	        }

	    	$open_orders[] = array(
	    		"order_id" => $uoo->order_id,
	    		"order_total" => $uoo->order_total,
	    		"affiliate_commission" => $order_commission,
	    		"order_date" => $uoo->order_date,
	    		"order_type" => $order_type
	    	);
	    }
	}

	// Create paid orders array
	if (!empty($user_paid_orders))
	{	
		$paid_orders = array();
		foreach ($user_paid_orders as $upo)
		{
			// Calculate order type
			if ($upo->foxy_affiliate_commission_type == 1)
			{
            	$order_type = 'Regular';
        	}
        	else
        	{
            	$order_type = 'Referral';
        	}

        	// Calculate payout
        	if ($upo->foxy_affiliate_payout_type == 1)
        	{
            	$affiliate_payout = $upo->foxy_affiliate_payout . '%';
        	}
        	else
        	{
            	$affiliate_payout = '$' . $upo->foxy_affiliate_payout;
        	}

			$paid_orders[] = array(
	    		"order_id" => $upo->foxy_transaction_id,
	    		"order_total" => $upo->foxy_transaction_order_total,
	    		"affiliate_payout" => $affiliate_payout,
	    		"affiliate_commission" => $upo->foxy_affiliate_commission,
	    		"payment_method" => $upo->foxy_affiliate_payment_method,
	    		"payment_date" => $upo->foxy_affiliate_payment_date,
	    		"order_type" => $order_type
	    	);
		}
	}

	// Calculate amount due and format
    if ($user_payout_type == 1)
    {
        $amount_due = $user_payout / 100 * $order_detail[0]->total_unpaid_amount;
    }
    else
    {
        $amount_due = $user_payout * $order_detail[0]->num_unpaid_orders;
    }
    $amount_due = number_format($amount_due, 2, '.', ',');

    // Calculate referral amount due and format
    if ($user_referral_payout_type == 1)
    {
        $referral_amount_due = $user_referral_payout / 100 * $order_detail[0]->total_unpaid_referral_amount;
    }
    else
    {
        $referral_amount_due = $user_referral_payout * $order_detail[0]->num_unpaid_referral_orders;
    }
    $referral_amount_due = number_format($referral_amount_due, 2, '.', ',');

    // Create affiliate stats array
    if (!empty($order_detail))
	{
		$affiliate_stats = array();

		if (!$order_detail[0]->total_paid_referral_amount)
		{ 
			$total_referral_paid_out = '0.00';
		}
		else
		{ 
			$total_referral_paid_out = $order_detail[0]->total_paid_referral_amount;
		}

		if (!$order_detail[0]->total_paid_amount)
		{ 
			$total_paid_out = '0.00';
		}
		else
		{ 
			$total_paid_out = $order_detail[0]->total_paid_amount;
		}

		if ($user_referral_payout_type == 1)
		{
            $referral_commission = $user_referral_payout . '%';
        }
        else
        { 
        	$referral_commission = '$' . $user_referral_payout;
        }

        if ($user_payout_type == 1)
		{
            $commission = $user_payout . '%';
        }
        else
        { 
        	$commission = '$' . $user_payout;
        }

		$affiliate_stats[] = array(
			"total_clicks" 				=> $order_detail[0]->num_clicks,
			"total_referrals"			=> $order_detail[0]->num_referrals,
			"total_orders"				=> $order_detail[0]->num_total_orders,
			"total_referral_paid_out"	=> $total_referral_paid_out,
			"total_paid_out"			=> $total_paid_out,
			"referral_amount_due"		=> $referral_amount_due,
			"amount_due"				=> $amount_due,
			"referral_commission"		=> $referral_commission,
			"commission"				=> $commission,
			"affiliate_url"				=> $user_affiliate_url,
			"user_facebook_page"		=> get_user_option('affiliate_facebook_page', $affiliate_id),
			"user_age"					=> get_user_option('affiliate_age', $affiliate_id),
			"user_gender"				=> get_user_option('affiliate_gender', $affiliate_id),
			"avatar_name"				=> get_user_option('affiliate_avatar_name', $affiliate_id),
			"avatar_ext"				=> get_user_option('affiliate_avatar_ext', $affiliate_id),
			"open_orders"				=> $open_orders,
			"paid_orders"				=> $paid_orders
		);

		return current($affiliate_stats);
	}

    return null;
}
/***************************************************************************************************/
/***************************************************************************************************/
/*********************************** End Functions For Users ***************************************/
/***************************************************************************************************/
/***************************************************************************************************/
?>