<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

add_action("template_redirect", 'foxypress_theme_redirect', 1);
function foxypress_theme_redirect() 
{
	global $wp, $wp_query, $currentPageName;

	$currentName = (isset($wp->query_vars["name"]) ? $wp->query_vars["name"] : "");
	$currentPageName = (isset($wp->query_vars["pagename"]) ? $wp->query_vars["pagename"] : "");
	$currentPostType = (isset($wp->query_vars["post_type"]) ? $wp->query_vars["post_type"] : "");
	$currentProduct = (isset($wp->query_vars[FOXYPRESS_CUSTOM_POST_TYPE]) ? $wp->query_vars[FOXYPRESS_CUSTOM_POST_TYPE] : "");	
	if ($currentPostType == FOXYPRESS_CUSTOM_POST_TYPE && FOXYPRESS_CUSTOM_POST_TYPE != "") 
	{
		if (have_posts())
		{
			if (get_option('fp_product_template', "") !== "")
			{
				include get_theme_root() . '/' . get_template() . '/' . get_option('fp_product_template');
			}
			else if(defined('FOXYPRESS_PRODUCT_TEMPLATE_PATH') && FOXYPRESS_PRODUCT_TEMPLATE_PATH != "")
			{
				include FOXYPRESS_PRODUCT_TEMPLATE_PATH;	
			}
			else
			{
				include FOXYPRESS_PATH . "/themes/foxypress-product.php";
			}
			die;
		} 
		else 
		{
			$wp_query->is_404 = true;
		}
	}
	
}
?>