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

add_action('init', 'foxypress_create_custom_post_type', 1);
add_action('after_setup_theme','foxypress_setup_post_thumbnails', 999);
add_action('manage_posts_custom_column', 'manage_custom_columns', 10, 2);
add_filter('manage_edit-foxypress_product_columns', 'add_new_foxypress_product_columns');
add_filter( 'manage_edit-foxypress_product_sortable_columns', 'foxypress_product_sortable_columns' );
add_filter( 'request', 'foxypress_product_sortable_columns_orderby' );
add_filter('post_updated_messages', 'foxypress_updated_messages');
add_action('admin_init', 'foxypress_product_meta_init');
add_action('before_delete_post', 'foxypress_delete_product');

define('SCRIPT_DEBUG', true);

function foxypress_create_custom_post_type()
{
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-datepicker');

	$labels = array(
		'name' => __('Products', 'foxypress'),
		'singular_name' => __('Product', 'foxypress'),
		'add_new' => __('Add New Product', 'foxypress'),
		'add_new_item' => __('Add New Product', 'foxypress'),
		'all_items' => __('Manage Products', 'foxypress'),
		'edit_item' => __('Edit Product', 'foxypress'),
		'new_item' => __('New Product', 'foxypress'),
		'view_item' => __('View Product', 'foxypress'),
		'menu_name' => __('FoxyPress', 'foxypress'),
		'not_found' =>  __('No Products Found', 'foxypress'),
		'not_found_in_trash' => __('No Products Found in Trash', 'foxypress'),
		'search_items' => __('Search Products', 'foxypress'),
		'parent_item_colon' => ''
	);
	$post_type_support = array('title','editor','thumbnail','excerpt', 'comments');
	$custom_post_slug = "products";
	if(defined('FOXYPRESS_CUSTOM_POST_TYPE_SLUG') && FOXYPRESS_CUSTOM_POST_TYPE_SLUG != "")
	{
		$custom_post_slug = FOXYPRESS_CUSTOM_POST_TYPE_SLUG;
	}
	register_post_type(FOXYPRESS_CUSTOM_POST_TYPE, array(
		'labels' => $labels,
		'description' => __('FoxyPress Products', 'foxypress'),
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'page',
		'hierarchical' => false,
		'supports' => $post_type_support,
		'rewrite' => array("slug" => $custom_post_slug),
		'menu_icon' => plugins_url('/img/icon_foxypress.png', __FILE__)
	));
}

function foxypress_delete_product($postid)
{
	global $post, $wpdb;
	//check post type
	if(get_post_type($postid) == FOXYPRESS_CUSTOM_POST_TYPE)
	{
		//set inventory id
		$inventory_id = $postid;
		//delete inventory options
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "foxypress_inventory_options WHERE inventory_id='" . $inventory_id . "'");
		//delete attributes
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "foxypress_inventory_attributes WHERE inventory_id='" . $inventory_id . "'");
		//delete inventory categories
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "foxypress_inventory_to_category WHERE inventory_id='" . $inventory_id . "'");
		//leave downloadables as is for legacy purposes.
		$wpdb->query("UPDATE " . $wpdb->prefix . "foxypress_inventory_downloadables SET status = 1 where WHERE inventory_id='" . $inventory_id . "'");
	}
}

function foxypress_product_sortable_columns( $columns ) {

	$columns['productcode'] = 'productcode';
	$columns['id'] = 'id';
	return $columns;
}

function foxypress_product_sortable_columns_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'productcode' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => '_code',
			'orderby' => 'meta_value'
		) );
	}

	return $vars;
}

function add_new_foxypress_product_columns($cols)
{
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['id'] = __('ID', 'foxypress');
	$new_columns['title'] = __('Product Title', 'foxypress');
	$new_columns['description'] = __('Description', 'foxypress');
	$new_columns['productcode'] = __('Code', 'foxypress');
	$new_columns['price'] = __('Price', 'foxypress');
	$new_columns['qty'] = __('Qty', 'foxypress');
	$new_columns['productimage'] = __('Image', 'foxypress');
	$new_columns['date'] = __('Date', 'foxypress');
	return $new_columns;
}

function manage_custom_columns($column_name, $id)
{
	global $wpdb;
	switch ($column_name)
	{
		case 'id':
			echo $id;
			break;
		case 'description':
			$description = get_post($id)->post_content;
			echo foxypress_TruncateString(stripslashes(strip_tags($description)), 25);
			break;
		case 'productcode':
			$productcode = get_post_meta($id, "_code", true);
			echo ($productcode ? $productcode : 'n/a');
			break;
		case 'qty':
			$qty = get_post_meta($id, "_quantity", true);
			if($qty!=""){
				echo ($qty ? $qty : __('sold out', 'foxypress'));
			}else{
				echo ($qty ? $qty : __('not set', 'foxypress'));
			}
			break;
		case 'price':
			$salestartdate = get_post_meta($id,'_salestartdate',TRUE);
			$saleenddate = get_post_meta($id,'_saleenddate',TRUE);
			$originalprice = get_post_meta($id,'_price', true);
			$saleprice = get_post_meta($id,'_saleprice', true);
			$actualprice = foxypress_GetActualPrice($originalprice, $saleprice, $salestartdate, $saleenddate);
			if($actualprice == $originalprice)
			{
				echo foxypress_FormatCurrency($originalprice);
			}
			else
			{
				echo "<span class=\"price_strike\">" . foxypress_FormatCurrency($originalprice) . "</span> <span class=\"price_sale\">" . foxypress_FormatCurrency($saleprice) . "</span>";
			}
			break;
		case 'productimage':
			
			$src = "";
			
			if (has_post_thumbnail($id)) {
				// Get featured image if it exists
				$featuredImage = wp_get_attachment_image_src(get_post_thumbnail_id($id), "thumbnail");
				$src = $featuredImage[0];
			} else {
				// Use first valid image
				$inventory_images = $wpdb->get_results( 
					"
					SELECT attached_image_id, image_id
					FROM " . $wpdb->prefix . "foxypress_inventory_images
					WHERE inventory_id = " . $id . " 
					ORDER BY image_order ASC
					"
				);
				
				foreach ( $inventory_images as $image ) 
				{
					if ($image != null) {
						
						// Check to see if image has been deleted
						if (get_post($image->attached_image_id) == null) {
							// Delete image attachment
							foxypress_RemoveInventoryImage($image->image_id);
						} else {
							$featuredImage = wp_get_attachment_image_src($image->attached_image_id, "full");
							$src = $featuredImage[0];
							break; // Exit foreach loop
						}
					}
				}
				
				// Use default product image if no attached image was found
				if ($src == "") {
					$src = plugins_url( 'img/' . INVENTORY_DEFAULT_IMAGE , __FILE__ );
				}
			}
			
			echo '<a href="post.php?post=' . $id . '&amp;action=edit"><img src="' . $src . '" style="max-height:32px; max-width:40px;" /></a>';
			
			break;
		default:
	}
}


function foxypress_setup_post_thumbnails()
{
	add_theme_support('post-thumbnails');
}

function foxypress_updated_messages($messages)
{
	global $post, $post_ID;
	$messages[FOXYPRESS_CUSTOM_POST_TYPE] = array(
		1 => __('Product updated.', 'foxypress') . ' <a href="'.esc_url(get_permalink($post_ID)).'">' . __('View product', 'foxypress') . '</a>',
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Product updated.'),
		6 => 'Product published. <a href="' . esc_url(get_permalink($post_ID)) . '">' . __('View product', 'foxypress') . '</a>',
		7 => __('Product saved.'),
		8 => 'Product submitted. <a target="_blank" href="'.esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))).'">' . __('Preview product', 'foxypress') . '</a>',
		9 => 'Product scheduled for: <strong>'.date_i18n( __("", "foxypress"), strtotime($post->post_date)).'</strong>. <a target="_blank" href="'.esc_url(get_permalink($post_ID)).'">' . __('Preview product', 'foxypress') . '</a>',
		10 => 'Product draft updated. <a target="_blank" href="'.esc_url(add_query_arg( 'preview', 'true', get_permalink($post_ID))).'">' . __('Preview product', 'foxypress') . '</a>'
	);
	return $messages;
}

function foxypress_product_meta_init()
{
	global $wpdb;
	//handle postback/qs actions
	$inventory_id = foxypress_FixGetVar("inventory_id", "");
	if(foxypress_FixGetVar('deleteattribute', '') != "")
	{
		$attributeid = foxypress_FixGetVar("attributeid", "");
		if($attributeid != "")
		{
			$wpdb->query("delete from " . $wpdb->prefix . "foxypress_inventory_attributes" . " where attribute_id = '" . $attributeid . "'");
		}
		header("location: post.php?post=" . $inventory_id . "&action=edit");
	}
	else if(foxypress_FixGetVar('deleteoption', '') != "")
	{
		$optionid = foxypress_FixGetVar("optionid", "");
		if($optionid != "")
		{
			$wpdb->query("delete from " . $wpdb->prefix . "foxypress_inventory_options" . " where option_id = '" . $optionid . "'");
		}
		header("location: post.php?post=" . $inventory_id . "&action=edit");
	}

	//show meta boxes
	add_meta_box('product_details_meta', 'Required Product Details', 'foxypress_product_details_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'high');
	add_meta_box('product_categories_meta', 'Product Categories', 'foxypress_product_categories_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'high');
	add_meta_box('product_categories_primary_meta', 'Primary Category', 'foxypress_product_categories_primary_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'high');
	add_meta_box('product_related_items_meta', 'Related Items', 'foxypress_related_items_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'low');
	add_meta_box('extra_product_details_meta', 'Extra Product Details', 'foxypress_extra_product_details_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'low');
	add_meta_box('product_deal_meta', 'Daily Deal', 'foxypress_product_deal_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'side', 'low');
	add_meta_box('product_images_meta', 'Product Images', 'foxypress_product_images_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'normal', 'high');
	add_meta_box('product_digital_download_meta', 'Digital Downloads', 'foxypress_product_digital_download_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'normal', 'high');
	add_meta_box('product_options_meta', 'Product Options', 'foxypress_product_options_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'normal', 'high');
	add_meta_box('product_attributes_meta', 'Product Attributes', 'foxypress_product_attributes_setup', FOXYPRESS_CUSTOM_POST_TYPE, 'normal', 'high');
	add_action('save_post','foxypress_product_meta_save');
}

function foxypress_product_categories_primary_setup()
{
	global $post, $wpdb;
	//check for current primary categories	
	$primaryCategories = $wpdb->get_results("SELECT c.category_name, c.category_id, itc.itc_id, itc.category_primary
												FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " as itc inner join " .
												$wpdb->prefix . "foxypress_inventory_categories" . " as c on itc.category_id = c.category_id
												WHERE inventory_id='" . $post->ID . "'");
	echo("<select name=\"_primary_category\" id=\"_primary_category\">");
	foreach($primaryCategories as $pc)
	{
		if($pc->category_primary == 1) {
			echo("<option value=\"" . $pc->category_id . "\" / selected=\"selected\"> " . stripslashes($pc->category_name) . " </option>");
		} else {
			echo("<option value=\"" . $pc->category_id . "\" /> " . stripslashes($pc->category_name) . " </option>");
		}
	}
	echo("</select>");
	
	?>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
		jQuery('#product_categories_meta .inside input').click(function(){
			   	var current_selected = jQuery('#product_categories_primary_meta .inside #_primary_category option:selected').val();
			   	var html_replace = "<select name='_primary_category' id='_primary_category'>";
		       	jQuery('#product_categories_meta .inside input:checkbox').each(function(index) {
		           	if(jQuery(this).prop('checked')) {
		           		var $label = jQuery(this).next('label');
		           		if(jQuery(this).val() == current_selected) {
		           			html_replace += "<option value='" + jQuery(this).val() + "' selected='selected'> " + $label.text() + " </option>";
		           		} else {
		           			html_replace += "<option value='" + jQuery(this).val() + "'> " + $label.text() + " </option>";
		           		}
		           	}
		       	});
		       	
		       	html_replace += "</select>";
		       	jQuery('#product_categories_primary_meta .inside #_primary_category').html(html_replace);
		       	
		       	// check length
		       	var options = jQuery('#product_categories_primary_meta .inside #_primary_category option').length;
		       	if(options < 1) {
			  		jQuery('#product_categories_primary_meta').hide();
			  	} else {
			  		jQuery('#product_categories_primary_meta').show();
			  	}
			});
		});
	</script>
	<?php
}

function foxypress_product_categories_setup()
{
	global $post, $wpdb;
	
	$all_categories = foxypress_get_product_categories();
	$product_categories = foxypress_GetCategoriesFor( $post->ID );
	
	foreach ($all_categories as $category) :
		
		// Determine if category is already selected or not
		$checked = "";
		if (in_array($category->category_id, $product_categories)) {
			$checked = 'checked="checked"';
		}
?>
<input type="checkbox" name="foxy_categories[]" value="<?php echo $category->category_id; ?>" <?php echo $checked; ?> /> <?php echo stripslashes($category->category_name); ?><br/>
<?php
	endforeach;
}

function foxypress_product_digital_download_setup()
{
	global $post, $wpdb;
	$fp_ajax_url = plugins_url() . "/foxypress/ajax.php";
	$inventory_downloadables = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_downloadables WHERE inventory_id = '" . $post->ID . "' AND status = '1'");
?>
<ul class="download-list">
<?php
	foreach($inventory_downloadables as $d) :
?>
	<li class="download-id-<?php echo $d->downloadable_id; ?>"><strong><?php echo $d->filename; ?></strong></li>
	<li class="download-id-<?php echo $d->downloadable_id; ?>">
		<ul class="indent">
			<li>Max downloads: <input type="text" name="inv_downloadable_update_max_downloads" class="inv_downloadable_update_max_downloads" value="<?php echo $d->maxdownloads; ?>" style="width:40px;" /><a class="button download-update" href="#" data-id="<?php echo $d->downloadable_id; ?>">Update</a></li>
			<li><span class="submitbox"><a class="submitdelete download-delete" href="#" data-id="<?php echo $d->downloadable_id; ?>">Remove Download</a></span></li>
		</ul>
	</li>
<?php
	endforeach;
?>
</ul>
<a class="fp-attach-download button" href="#" target="wp-preview">Add Digital Download</a>
<span class="download-message"><span class="spinner" style="float: left;"></span><span class="text"></span></span>
<div style="clear:both;"></div>
<script>
	jQuery(document).ready(function($){
    // Prepare the variable that holds our custom media manager.
    var fp_download_frame;
    
    // Bind to our click event in order to open up the new media experience.
    $(document.body).on('click.fpAttachDownload', '.fp-attach-download', function(e){
        // Prevent the default action from occuring.
        e.preventDefault();
				
        // If the frame already exists, re-open it.
        if ( fp_download_frame ) {
            fp_download_frame.open();
            return;
        }
				
        // Create media frame with options
        //   *For additional options see wp-includes/js/media-views.js
        fp_download_frame = wp.media.frames.fp_download_frame = wp.media({
            
            className: 'media-frame fp-download-frame', // Custom frame class name
            frame: 'select', // Frame type. Either 'select' or 'post'
            multiple: false,
            title: 'Select File for Digital Download',
            button: {
                text:  'Select File'
            }
        });
				
        // On form submit event handler
        fp_download_frame.on('select', function(){
          // Grab our attachment selection and construct a JSON representation of the model.
          var file_attachments = fp_download_frame.state().get('selection').toJSON();
					
					// Update Product Images with selected images
					for (var i = 0; i < file_attachments.length; i++) {
						addToDigitalDownloads(file_attachments[i]);
					}
        });
				
        // Now that everything has been set, let's open up the frame.
        fp_download_frame.open();
    });
		
		var download_ajax_request;
		
    function addToDigitalDownloads(file_attachment) {
    	
    	$(".download-message .spinner").show();
    	$(".fp-attach-download").hide();
    	
			var ajaxUrl = "<?php echo $fp_ajax_url; ?>";
			var mode = "add_downloadable";
			var session_id = "<?php echo session_id(); ?>";
			var attachment_id = file_attachment.id;
			var post_id = "<?php echo $post->ID; ?>";
			
    	var url = ajaxUrl + "?m=" + mode + "&sid=" + session_id + "&aid=" + attachment_id + "&pid=" + post_id;
    	
			download_ajax_request = jQuery.ajax({
				url : url,
				type : "GET",
				datatype : "json",
				cache : "false",
				success : function(data, textStatus, jqXHR) {
					if (data.ajax_status == "ok") {
						addDigitalDownload(data.filename, data.maxdownloads, data.downloadable_id);
						$(".download-message .text").text("");
					} else {
						$(".download-message .text").text(data.error_text);
					}
				},
				error : function(jqXHR, textStatus, errorThrown) {
					$(".download-message .text").text(errorThrown);
				},
				complete : function(jqXHR, textStatus) {
					$(".download-message .spinner").hide();
					$(".fp-attach-download").show();
				}
			});
    }
    
    function addDigitalDownload(filename, maxdownloads, downloadable_id) {
    	$(".download-list").append('<li class="download-id-' + downloadable_id + '"><strong>' + filename + '</strong></li><li class="download-id-' + downloadable_id + '"><ul class="indent"><li>Max downloads: <input type="text" name="inv_downloadable_update_max_downloads" class="inv_downloadable_update_max_downloads" value="' + maxdownloads + '" style="width:40px;" /><a class="button download-update" href="#" data-id="' + downloadable_id + '">Update</a></li><li><span class="submitbox"><a class="submitdelete download-delete" href="#" data-id="' + downloadable_id + '">Remove Download</a></span></li></ul></li>');
    }
    
    // Click listener for digital downloads update
    $(document.body).on('click', '.download-update', function(e){
        // Prevent the default action from occuring.
        e.preventDefault();
    		
    		var ajaxUrl = "<?php echo $fp_ajax_url; ?>";
    		var mode = "update_downloadable";
    		var session_id = "<?php echo session_id(); ?>";
    		var downloadable_id = $(this).attr('data-id');
    		var max_downloads = $('.download-id-' + $(this).attr('data-id') + ' .inv_downloadable_update_max_downloads').val();
    		
    		var url = ajaxUrl + "?m=" + mode + "&sid=" + session_id + "&did=" + downloadable_id + "&maxdl=" + max_downloads;
    		
    		var delete_downloadable_ajax_request = jQuery.ajax({
    			url : url,
    			type : "GET",
    			datatype : "json",
    			cache : "false"
    		});
    });
    
    // Click listener for digital downloads remote
    $(document.body).on('click', '.download-delete', function(e){
      // Prevent the default action from occuring.
      e.preventDefault();
  		
  		var ajaxUrl = "<?php echo $fp_ajax_url; ?>";
  		var mode = "remove_downloadable";
  		var session_id = "<?php echo session_id(); ?>";
  		var downloadable_id = $(this).attr('data-id');
  		
  		var url = ajaxUrl + "?m=" + mode + "&sid=" + session_id + "&did=" + downloadable_id;
  		
  		var delete_downloadable_ajax_request = jQuery.ajax({
  			url : url,
  			type : "GET",
  			datatype : "json",
  			cache : "false"
  		});
  		
  		// Remove entry from the list of digital downloads
  		$('.download-id-' + $(this).attr('data-id')).remove();
    });
    
	});
</script>

<?php
	
	/*
?>
	<div id="inventory_downloadable_upload" <?php echo(($fp_has_downloadable) ? " style=\"display:none;\"" : "") ?>>
    	<p><?php _e('Making your product into a digital download is simple, just fill out the form below.  Once you\'ve filled out the information below and provided a downloadable, your product will be marked as a downloadable product and will send your users an email with their download link.', 'foxypress'); ?></p>
        <div class="foxypress_download_field"><?php _e('Digital Download Name', 'foxypress'); ?></div>
		<div><input type="text" name="inv_downloadable_name" id="inv_downloadable_name" value="my_download" /></div>
        <div class="foxypress_download_field"><?php _e('Max Downloads allowed  (if you need to override the main setting)', 'foxypress'); ?></div>
        <div><input type="text" name="inv_downloadable_max_downloads" id="inv_downloadable_max_downloads" value="" /></div>
        <div class="foxypress_download_field"><input type="file" name="inv_downloadable" id="inv_downloadable"> </div>
    </div>
    <div id="inventory_downloadables"><?php echo($fp_current_downloadables) ?></div>

  	<link href="<?php echo(plugins_url())?>/foxypress/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
    <?php echo("<script type=\"text/javascript\" src=\"" . plugins_url() . "/foxypress/uploadify/jquery.uploadify.min.js\"></script>")?>
    <script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			jQuery('#inv_downloadable').uploadify({
				'swf'  : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify.swf',
				'cancelImage' : '<?php echo(plugins_url())?>/foxypress/uploadify/uploadify-cancel.png',
				'uploader'    : ajaxurl,
				'buttonText': 'Browse Files',
				'auto'      : true,
				'queueSizeLimit': 1,
				'fileTypeDesc': 'Downloadables',
				'multi': false,
				'postData' : { 'action' : 'foxypress_download', 
								'auth_cookie' : '<?php echo $_COOKIE[LOGGED_IN_COOKIE]; ?>', 
								'security' : '<?php echo($ajax_nonce); ?>', 
								'inventory_id' : '<?php echo($post->ID) ?>',  
								'downloadablename' :  jQuery('#inv_downloadable_name').val(), 
								'downloadablemaxdownloads' : jQuery('#inv_downloadable_max_downloads').val() },
				'onDialogOpen' : function() {
								jQuery('#inv_downloadable').uploadifySettings('postData', { 'action' : 'foxypress_download', 'auth_cookie' : '<?php echo $_COOKIE[LOGGED_IN_COOKIE]; ?>', 'security' : '<?php echo($ajax_nonce); ?>', 'inventory_id' : '<?php echo($post->ID) ?>', 'prefix' : '<?php echo($wpdb->prefix . "foxypress_inventory_downloadables") ?>', 'downloadablename' :  jQuery('#inv_downloadable_name').val(), 'downloadablemaxdownloads' : jQuery('#inv_downloadable_max_downloads').val() });
							},
				'checkExisting' : false,
				'fileSizeLimit' : 16384000,
				'onUploadSuccess': function (file, data, response) {
					ShowDownloadable(data);
				}
			  });
		});


		function ShowDownloadable(data)
		{
			//check for errors
			if(data.indexOf("<Error>") >= 0)
			{
				var error = data.replace("<Error>", "");
				error = error.replace("</Error>", "");
				alert(error);
			}
			else
			{
				var FileName = data.split("|")[0];
			 	var DownloadableID = data.split("|")[1];
				var MaxDownloads = data.split("|")[2];
				jQuery('#inventory_downloadable_upload').hide();
				jQuery('#inventory_downloadables').html("<a href=\"<?php echo(get_bloginfo("url")) ?>/wp-content/inventory_downloadables/" + FileName + "\" target=\"_blank\">" + FileName + "</a> &nbsp; <img src=\"<?php echo(plugins_url())?>/foxypress/img/delimg.png\" alt=\"\" onclick=\"DeleteDownloadable('<?php echo(plugins_url()) . "/foxypress/ajax.php" ?>', '<?php echo(session_id()) ?>', '<?php echo($inventory_id) ?>', '" + DownloadableID + "');\" class=\"RemoveItem\" align=\"bottom\" /> Max Downloads: <input type=\"text\" name=\"inv_downloadable_update_max_downloads\" id=\"inv_downloadable_update_max_downloads\" value=\"" + MaxDownloads + "\" style=\"width:40px;\" /> <input type=\"button\" name=\"inv_downloadable_update_max_downloads_button\" id=\"inv_downloadable_update_max_downloads_button\" value=\"Update\" onclick=\"SaveMaxDownloads('<?php echo(plugins_url()) . "/foxypress/ajax.php" ?>', '<?php echo(session_id()) ?>', '<?php echo($inventory_id) ?>', '" + DownloadableID + "');\" /><img src=\"<?php echo(plugins_url())?>/foxypress/img/ajax-loader.gif\" id=\"inv_downloadable_loading\" name=\"inv_downloadable_loading\" style=\"display:none;\" />");
			}
		}

		function DeleteDownloadable(baseurl, sid, inventoryid, downloadableid)
		{
			var url = baseurl + "?m=deletedownloadable&sid=" + sid + "&downloadableid=" + downloadableid + "&inventoryid=" + inventoryid;
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false"
						}
					);
			jQuery('#inventory_downloadable_upload').show();
			jQuery('#inventory_downloadables').html("");
		}

		function SaveMaxDownloads(baseurl, sid, inventoryid, downloadable_id)
		{
			jQuery('#inv_downloadable_update_max_downloads_button').hide();
			jQuery('#inv_downloadable_loading').show();
			var maxdownloads = jQuery('#inv_downloadable_update_max_downloads').val();
			var url = baseurl + "?m=savemaxdownloads&sid=" + sid + "&downloadableid=" + downloadable_id + "&inventoryid=" + inventoryid + "&maxdownloads=" + maxdownloads;
			jQuery.ajax(
						{
							url : url,
							type : "GET",
							datatype : "json",
							cache : "false",
							success : function() {
								jQuery('#inv_downloadable_update_max_downloads_button').show();
								jQuery('#inv_downloadable_loading').hide();
							}
						}
					);
		}
	</script>
<?php
*/
}

function foxypress_product_images_setup()
{
	global $post, $wpdb;
	
	// Check for featured image
	if (has_post_thumbnail($post->ID)) {
		$featuredImage = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "thumbnail");
		$src = $featuredImage[0];
		?>
		<div class='subhead'><?php _e('Featured Product Image', 'foxypress'); ?></div>
		<div class='PhotoWrapper'><img src="<?php echo $src; ?>" style="max-width:150px;"></div>
		<?php
	}
	
	// Check for current images
	$current_images = "";
	$images = $wpdb->get_results( 
		"
		SELECT attached_image_id, image_id
		FROM " . $wpdb->prefix . "foxypress_inventory_images
		WHERE inventory_id = " . $post->ID . " 
		ORDER BY image_order ASC
		"
	);
	
	foreach ( $images as $image ) 
	{
		// Check to see if image has been deleted
		if (get_post($image->attached_image_id) == null) {
			// Delete image attachment
			foxypress_RemoveInventoryImage($image->image_id);
		} else {
			$current_images .= "<li id='product-image-" . $image->attached_image_id . "' class='CreatePhoto'><div class='PhotoWrapper'><img src='" . wp_get_attachment_thumb_url($image->attached_image_id) . "' style='max-width:150px;' /><div id='remove-image-" . $image->attached_image_id . "' class='remove-image'><img src='" . plugins_url( 'img/x.png' , __FILE__ ) . "' /></div></div></li>";
		}
	}
	?>
	<div class="subhead"><?php _e('Current Product Images', 'foxypress'); ?></div>
	<a class="fp-open-media button" href="#" target="wp-preview" id="post-preview">Add Product Images</a>
	<div id="inventory_images"><ul><?php echo($current_images) ?></ul></div>
	<div style="clear:both;"></div>
	<script>
		jQuery(document).ready(function($){
		    // Prepare the variable that holds our custom media manager.
		    var fp_media_frame;
		    
		    // Bind to our click event in order to open up the new media experience.
		    $(document.body).on('click.fpOpenMediaManager', '.fp-open-media', function(e){
		        // Prevent the default action from occuring.
		        e.preventDefault();
		
		        // If the frame already exists, re-open it.
		        if ( fp_media_frame ) {
		            fp_media_frame.open();
		            return;
		        }
		
		        // Create media frame with options
		        //   *For additional options see wp-includes/js/media-views.js
		        fp_media_frame = wp.media.frames.fp_media_frame = wp.media({
		            
		            className: 'media-frame fp-media-frame', // Custom frame class name
		            frame: 'select', // Frame type. Either 'select' or 'post'
		            multiple: true,
		            title: 'Choose Additional Product Image(s)',
		            // Limit view to library
		            library: {
		                type: 'image'
		            },
		            button: {
		                text:  'Select Image(s)'
		            }
		        });
		
		        // On form submit event handler
		        fp_media_frame.on('select', function(){
		            // Grab our attachment selection and construct a JSON representation of the model.
		            var media_attachments = fp_media_frame.state().get('selection').toJSON();
					
					// Update Product Images with selected images
					for (var i = 0; i < media_attachments.length; i++) {
						addToProductImages(media_attachments[i]);
					}
					
					productImagesSortable(); // Re-enable sortable 
					saveImageOrder(); // Saves image order
		        });
		
		        // Now that everything has been set, let's open up the frame.
		        fp_media_frame.open();
		    });
		    
		    function addToProductImages(media_attachment) {
		    	
		    	// Check to make sure the image doesn't already exist in the image array
		    	if ($('#product-image-' + media_attachment.id).length > 0) {
		    		// Image already exists - do not add again
		    	} else {
		    		
		    		var thumbnail_url;
		    		if (media_attachment.sizes.thumbnail !== undefined) {
		    			// Image thumbnail exists
		    			thumbnail_url = media_attachment.sizes.thumbnail.url;
		    		} else {
		    			// No thumbnail - use full image URL
		    			thumbnail_url = media_attachment.url;
		    		}

		    		$('#inventory_images > ul').append("<li id='product-image-" + media_attachment.id + "' class='CreatePhoto'><div class='PhotoWrapper'><img src='" + thumbnail_url + "' style='max-width:150px;' /><div id='remove-image-" + media_attachment.id + "' class='remove-image'><img src='<?php echo  plugins_url( 'img/x.png' , __FILE__ ); ?>' /></div></div></li>");
		    	}
		    }
		    
		    function productImagesSortable() {
		    	jQuery( "#inventory_images > *" ).sortable(
		    		{
		    			revert: true,
		    			update: function(event, ui) { saveImageOrder(); }
		    		}
		    	);
		    }
		    
		    // Execute on first load
		    productImagesSortable();
			
			var image_ajax_request;
			
			function saveImageOrder()
			{
				var ImageOrder = jQuery( "#inventory_images > *" ).sortable("toArray");
	
				var url = "<?php echo(plugins_url()) . "/foxypress/ajax.php?m=update-images&sid=" . session_id() . "&product-id=" . $post->ID . "&order=" ?>" + ImageOrder;
	
				image_ajax_request = jQuery.ajax({
					url : url,
					type : "GET",
					datatype : "json",
					cache : "false"
				});
			}
			
			$(document).on("click", ".remove-image", function(){ 
				// Remove list item from DOM
				$(this).parent().parent().remove();
				// Save new image order
				saveImageOrder();
			});
		});
	</script>
	<?php
}

function foxypress_product_details_setup()
{
	global $post;
	$_price = get_post_meta($post->ID,'_price',TRUE);
	$_code = get_post_meta($post->ID,'_code',TRUE);
	$_weight = get_post_meta($post->ID,'_weight',TRUE);
	$_weight2 = get_post_meta($post->ID,'_weight2',TRUE);
	$_quantity = get_post_meta($post->ID,'_quantity',TRUE);
	$_quantity_min = get_post_meta($post->ID,'_quantity_min',TRUE);
	$_quantity_max = get_post_meta($post->ID,'_quantity_max',TRUE);
?>
	<div class="foxypress_field_control">
		<label for="_price"><?php _e('Item Price', 'foxypress'); ?></label>
		<input type="text" name="_price" id="_price" value="<?php echo $_price; ?>"  style="width: 90px; float: left;" />
	</div>
	<div class="foxypress_field_control">
		<label for="_code"><?php _e('Item Code', 'foxypress'); ?></label>
		<input type="text" name="_code" id="_code" value="<?php echo $_code; ?>" />
	</div>
	<div class="foxypress_field_control">
		<label for="_weight1"><?php _e('Weight', 'foxypress'); ?></label>
		<input type="text" name="_weight1" id="_weight1" value="<?php echo $_weight; ?>" />
		<span style="float: left; margin: 9px 0 0 5px; width: 34px;">lbs</span>
		<input type="text" name="_weight2" id="_weight2" value="<?php echo $_weight2; ?>" />
		<span style="float: left; margin: 9px 0 0 5px;">oz</span>
	</div>
    <div class="foxypress_field_control">
		<label for="_code"><?php _e('Quantity', 'foxypress'); ?></label>
		<input type="text" name="_quantity" id="_quantity" value="<?php echo $_quantity; ?>" />
	</div>
    <div class="foxypress_field_control">
		<label for="_quantity_min"><?php _e('Qty Settings', 'foxypress'); ?></label>
		<input type="text" name="_quantity_min" id="_quantity_min" value="<?php echo $_quantity_min; ?>" style="width: 30px; float: left;"  />
        <span style="float: left; margin: 9px 0 0 5px; width: 34px;"><?php _e('min', 'foxypress'); ?></span>
		<input type="text" name="_quantity_max" id="_quantity_max" value="<?php echo $_quantity_max; ?>" style="width: 30px; float: left;"  />
		<span style="float: left; margin: 9px 0 0 5px; width: 34px;"><?php _e('max', 'foxypress'); ?></span>
	</div>
    <div style="clear:both"></div>
    <input type="hidden" name="products_meta_noncename" value="<?php echo(wp_create_nonce(__FILE__)); ?>" />
<?php
}

function foxypress_related_items_setup()
{
	global $post;
	$products_selected = foxypress_GetRelatedItems($post->ID);
	$products = get_posts( array( 'post_type' => FOXYPRESS_CUSTOM_POST_TYPE, 'numberposts' => 1000 ) );
?>
	<div id="add-page" class="postbox ">
		<div id="posttype-page" class="posttypediv">
			<div id="tabs-panel-posttype-page-most-recent" class="tabs-panel tabs-panel-active">
				<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
				<?php
					foreach( $products as $product ) : 
						$checked = "";
						// Determine if this product is related
						if ($products_selected === false) {
							// There are no related products
						} else {
							if(in_array($product->ID, $products_selected)) {
								$checked = " checked";
							} 
						}
				?>
						<li>
							<label class="menu-item-title">
								<input type="checkbox" name="_relatedProducts[]" value="<?php echo($product->ID); ?>"<?php echo $checked ?> /> <?php echo($product->post_title); ?>
							</label>
						</li>
					<?php 
					endforeach; ?>
				</ul>
			</div><!-- /.tabs-panel -->
		</div>
	</div>
<?php
}

function foxypress_extra_product_details_setup()
{
	global $post, $wpdb;
	$_saleprice = get_post_meta($post->ID,'_saleprice',TRUE);
	$_salestartdate = get_post_meta($post->ID,'_salestartdate',TRUE);
	$_saleenddate = get_post_meta($post->ID,'_saleenddate',TRUE);
	//Format Sale Date
	$_discount_quantity_amount = get_post_meta($post->ID,'_discount_quantity_amount',TRUE);
	$_discount_quantity_percentage = get_post_meta($post->ID,'_discount_quantity_percentage',TRUE);
	$_discount_price_amount = get_post_meta($post->ID,'_discount_price_amount',TRUE);
	$_discount_price_percentage = get_post_meta($post->ID,'_discount_price_percentage',TRUE);
	$_sub_frequency = get_post_meta($post->ID,'_sub_frequency',TRUE);
	$_sub_startdate = get_post_meta($post->ID,'_sub_startdate',TRUE);
	$_sub_enddate = get_post_meta($post->ID,'_sub_enddate',TRUE);

	$_item_start_date = get_post_meta($post->ID,'_item_start_date',TRUE);
	$_item_end_date = get_post_meta($post->ID,'_item_end_date',TRUE);
	$_item_active = get_post_meta($post->ID,'_item_active',TRUE);

	$_item_email_active = get_post_meta($post->ID, '_item_email_active', TRUE);
	$_item_email_template = get_post_meta($post->ID, '_item_email_template', TRUE);

	$_item_color = get_post_meta($post->ID, '_item_color', TRUE);
?>
	<h4>
		<?php 
			_e('Current Time: ', 'foxypress');
			echo date('m-d-y h:m:s', current_time('mysql')); 
		?>
	</h4>
	<h4><?php _e('Sale', 'foxypress'); ?></h4>
	<div class="foxypress_field_control">
		<label for="_saleprice"><?php _e('Sale Price', 'foxypress'); ?></label>
		<input type="text" name="_saleprice" id="_saleprice" value="<?php echo $_saleprice; ?>" style="width: 87px; float: left;" />
	</div>
	<div class="foxypress_field_control">
		<label for="_salestartdate"><?php _e('Start Date', 'foxypress'); ?></label>
		<input type="text" id="_salestartdate" name="_salestartdate" value="<?php echo $_salestartdate; ?>" style="width: 87px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">yyyy-mm-dd</span>
	</div>
	<div class="foxypress_field_control">
		<label for="_saleenddate"><?php _e('End Date', 'foxypress'); ?></label>
		<input type="text" id="_saleenddate" name="_saleenddate" value="<?php echo $_saleenddate; ?>" style="width: 87px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">yyyy-mm-dd</span>
	</div>
	<div style="clear: both;"></div>
	<h4><?php _e('Discounts', 'foxypress'); ?> <a href="http://wiki.foxycart.com/v/0.7.1/coupons_and_discounts" target="_blank">(<?php _e('reference', 'foxypress'); ?>)</a></h4>
	<div class="foxypress_field_control discount_fields">
		<label for="_discount_quantity_amount"><?php _e('Quantity $', 'foxypress'); ?></label>
		<input type="text" name="_discount_quantity_amount" id="_discount_quantity_amount" value="<?php echo $_discount_quantity_amount; ?>" />
		<div style="clear:both;"></div>
	</div>
	<div class="foxypress_field_control discount_fields">
		<label for="_discount_quantity_percentage"><?php _e('Quantity %', 'foxypress'); ?></label>
		<input type="text" name="_discount_quantity_percentage" id="_discount_quantity_percentage" value="<?php echo $_discount_quantity_percentage; ?>" />
		<div style="clear:both;"></div>
	</div>
	<div class="foxypress_field_control discount_fields">
		<label for="_discount_price_amount"><?php _e('Price $', 'foxypress'); ?></label>
		<input type="text" name="_discount_price_amount" id="_discount_price_amount" value="<?php echo $_discount_price_amount; ?>" />
		<div style="clear:both;"></div>
	</div>
	<div class="foxypress_field_control discount_fields">
		<label for="_discount_price_percentage"><?php _e('Price %', 'foxypress'); ?></label>
		<input type="text" name="_discount_price_percentage" id="_discount_price_percentage" value="<?php echo $_discount_price_percentage; ?>" />
		<div style="clear:both;"></div>
	</div>
    <div style="clear:both;"></div>
    <h4><?php _e('Subscription Attributes'); ?> <a href="http://wiki.foxycart.com/v/0.7.1/cheat_sheet#subscription_product_options" target="_blank">(<?php _e('reference', 'foxypress'); ?>)</a></h4>
	<div id="foxypress_subscription_attributes">
		<div class="foxypress_field_control">
			<label for="_sub_frequency"><?php _e('Frequency', 'foxypress'); ?></label>
			<input type="text" name="_sub_frequency" id="_sub_frequency" value="<?php echo $_sub_frequency; ?>" />
			<span>60d, 2w, 1m, 1y, .5m</span>
		</div>
		<div class="foxypress_field_control">
			<label for="_sub_startdate"><?php _e('Start Date', 'foxypress'); ?></label>
			<input type="text" id="_sub_startdate" name="_sub_startdate" value="<?php echo $_sub_startdate; ?>" />
			<span>YYYYMMDD or D</span>
		</div>
		<div class="foxypress_field_control">
			<label for="_sub_enddate"><?php _e('End Date', 'foxypress'); ?></label>
			<input type="text" id="_sub_enddate" name="_sub_enddate" value="<?php echo $_sub_enddate; ?>" />
			<span>YYYYMMDD or D</span>
		</div>
		<div style="clear: both;"></div>
	</div>
	<div style="clear:both;"></div>
    <h4><?php _e('Item Availability', 'foxypress'); ?></h4>
    <div class="foxypress_field_control">
		<label for="_item_start_date"><?php _e('Start Date', 'foxypress'); ?></label>
		<input type="text" name="_item_start_date" id="_item_start_date" value="<?php echo $_item_start_date; ?>" style="width: 87px; float: left;" />
        <span style="float: left; margin: 9px 0 0 5px;">yyyy-mm-dd</span>
        <div style="clear: both;"></div>
	</div>
    <div class="foxypress_field_control">
		<label for="_item_end_date"><?php _e('End Date', 'foxypress'); ?></label>
		<input type="text" name="_item_end_date" id="_item_end_date" value="<?php echo $_item_end_date; ?>" style="width: 87px; float: left;" />
        <span style="float: left; margin: 9px 0 0 5px;">yyyy-mm-dd</span>
        <div style="clear: both;"></div>
	</div>
    <div class="foxypress_field_control">
		<label for="_item_active"><?php _e('Active', 'foxypress'); ?></label>
		<select name="_item_active" id="_item_active">
        	<option value="1" <?php if($_item_active == "1") {echo("selected=\"selected\"");} ?>>Yes</option>
            <option value="0" <?php if($_item_active == "0") {echo("selected=\"selected\"");} ?>>No</option>
        </select>
        <div style="clear: both;"></div>
	</div>
    <div style="clear: both;"></div>
    <h4><?php _e('Item Email', 'foxypress'); ?></h4>
    <div class="foxypress_field_control">
		<label for="_item_email_active"><?php _e('Active', 'foxypress'); ?></label>
		<select name="_item_email_active" id="_item_email_active">
			<option value=""> -- </option>
        	<option value="1" <?php if($_item_email_active == "1") {echo("selected=\"selected\"");} ?>>Yes</option>
            <option value="0" <?php if($_item_email_active == "0") {echo("selected=\"selected\"");} ?>>No</option>
        </select>
        <div style="clear: both;"></div>
	</div>
	<div class="foxypress_field_control">
		<label for="_item_email_template"><?php _e('Template', 'foxypress'); ?></label>
		<select name="_item_email_template" id="_item_email_template">
			<option value=""> -- </option>
			<?php
			$t_options=$wpdb->get_results("SELECT * FROM " . $wpdb->prefix ."foxypress_email_templates");
			if(count($t_options)==0){
				//$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s','manage-emails', 'new');
				//echo"You do not have any email templates defined.  Add one <a href='" . $destination_url . "'>here</a>.";
			}else{

				foreach ( $t_options as $te )
				{ ?>
					<option value="<?php echo $te->email_template_id; ?>" <?php if($_item_email_template == $te->email_template_id) {echo("selected=\"selected\"");} ?>><?php echo $te->foxy_email_template_name; ?></option>
				<?php }
			}
			?>
        </select>
        <div style="clear: both;"></div>
	</div>
	<div style="clear: both;"></div>
    <h4><?php _e('Product Feed Options', 'foxypress'); ?></h4>
    <div class="foxypress_field_control">
		<label for="_item_color"><?php _e('Color', 'foxypress'); ?></label>
		<input type="text" name="_item_color" id="_item_color" value="<?php echo $_item_color; ?>" />
        <div style="clear: both;"></div>
    </div>
	<div style="clear: both;"></div>
    <script type="text/javascript" langauge="javascript">
		jQuery(document).ready(function() {
		  	jQuery('#_salestartdate').datepicker({dateFormat : 'yy-mm-dd'});
		  	jQuery('#_saleenddate').datepicker({dateFormat : 'yy-mm-dd'});		  	
		  	jQuery('#_sub_startdate').datepicker({dateFormat : 'yy-mm-dd'});
		  	jQuery('#_sub_enddate').datepicker({dateFormat : 'yy-mm-dd'});
		  	jQuery('#_item_start_date').datepicker({dateFormat : 'yy-mm-dd'});
		  	jQuery('#_item_end_date').datepicker({dateFormat : 'yy-mm-dd'});
		});
	</script>
<?php
}

function foxypress_product_deal_setup()
{
	global $post;
	$_item_deal_active = get_post_meta($post->ID,'_item_deal_active',TRUE);
	$_item_deal_code_type = get_post_meta($post->ID,'_item_deal_code_type',TRUE);
	$_item_deal_static_code = get_post_meta($post->ID,'_item_deal_static_code',TRUE);
?>
	<div class="foxypress_field_control">
		<label for="_item_deal_active"><?php _e('Active Deal'); ?></label>
		<select name="_item_deal_active" id="_item_deal_active">
			<option value=""> -- </option>
        	<option value="1" <?php if($_item_deal_active == "1") {echo("selected=\"selected\"");} ?>><?php _e('Yes', 'foxypress'); ?></option>
            <option value="0" <?php if($_item_deal_active == "0") {echo("selected=\"selected\"");} ?>><?php _e('No', 'foxypress'); ?></option>
        </select>
	</div>
	<div class="foxypress_field_control">
		<label for="_item_deal_code_type"><?php _e('Code Type'); ?></label>
		<select name="_item_deal_code_type" id="_item_deal_code_type">
			<option value=""> -- </option>
			<option value="none" <?php if($_item_deal_code_type == "none") {echo("selected=\"selected\"");} ?>><?php _e('None', 'foxypress'); ?></option>
        	<option value="static" <?php if($_item_deal_code_type == "static") {echo("selected=\"selected\"");} ?>><?php _e('Static', 'foxypress'); ?></option>
            <option value="random" <?php if($_item_deal_code_type == "random") {echo("selected=\"selected\"");} ?>><?php _e('Random', 'foxypress'); ?></option>
        </select>
        <div style="clear: both;"></div>
	</div>
	<div class="foxypress_field_control discount_fields">
		<label for="_item_deal_static_code"><?php _e('Static Code', 'foxypress'); ?></label>
		<input type="text" name="_item_deal_static_code" id="_item_deal_static_code" value="<?php echo $_item_deal_static_code; ?>" />
		<div style="clear:both;"></div>
	</div>
    <div style="clear:both"></div>
<?php
}

function foxypress_product_meta_save($post_id)
{
	global $wpdb;
	if (!wp_verify_nonce((isset($_POST['products_meta_noncename']) ? $_POST['products_meta_noncename'] : ""),__FILE__)) return $post_id;
	if (!current_user_can('edit_'.($_POST['post_type'] == 'page' ? 'page' : 'post'), $post_id)) return $post_id;
	$inventory_id = $post_id;
	if(isset($_POST['foxy_option_save']))
	{
		$optionname = foxypress_FixPostVar('foxy_option_name');
		$optionvalue = foxypress_FixPostVar('foxy_option_value');
		$optiongroupid = foxypress_FixPostVar('foxy_option_group');
		$optionextraprice = foxypress_FixPostVar('foxy_option_extra_price', '0');
		$optionextraweight = foxypress_FixPostVar('foxy_option_extra_weight', '0');
		$optioncode = foxypress_FixPostVar('foxy_option_code', '');
		$optionquantity = foxypress_FixPostVar('foxy_option_quantity', '');
		$optionimage = foxypress_FixPostVar('foxy_option_image');

		if($optionname != "" && $optionvalue != "" && $optiongroupid != "")
		{
			//insert new option
			$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_options (inventory_id, option_group_id, option_text, option_value, option_extra_price, option_extra_weight, option_code, option_quantity, option_active, option_image) values ('" . $inventory_id . "', '" . $optiongroupid . "', '" . $optionname . "', '" . $optionvalue . "', '" . $optionextraprice . "', '" . $optionextraweight . "', '" . $optioncode . "', " . (($optionquantity == "") ? "NULL" : "'" . $optionquantity . "'") . ", '1', '" . $optionimage . "')");
		}
		//NOTE: currently unique product option codes only work per 1 group per item, so if they try entering in unique codes for mulitple
		//option groups we need ot wipe them out.
		$BadData = $wpdb->get_row("select count(distinct option_group_id) as GroupCount from " . $wpdb->prefix . "foxypress_inventory_options". " where inventory_id='" . $inventory_id . "'");
		if(!empty($BadData) && $BadData->GroupCount > 1)
		{
			//wipe out the codes and quantities
			$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options
						  set option_quantity = NULL
							,option_code = NULL
						  where inventory_id = '" . $inventory_id . "'");
		}
	}
	else if(isset($_POST['foxy_options_update'])) //save options
	{
		//save option data
			$rowsToProcess = foxypress_FixPostVar('hdn_foxy_options_count');

			if($rowsToProcess > 0)
			{
				// This array is used to track if there is an option code associated
				//   with each unique option group, as a single product cannot have 
				//   more than one option group with codes
				$option_groups_codes = array();

				for($i=1; $i<=$rowsToProcess; $i++)
				{
					$optionID = foxypress_FixPostVar('hdn_foxy_option_id_' . $i);
					$optiongroupid = foxypress_FixPostVar('foxy_option_group_' . $i);
					$optionname = foxypress_FixPostVar('foxy_option_text_' . $i);
					$optionvalue = foxypress_FixPostVar('foxy_option_value_' . $i);
					$optionextraprice = foxypress_FixPostVar('foxy_option_extra_price_' . $i);
					$optionextraweight = foxypress_FixPostVar('foxy_option_extra_weight_' . $i);
					$optionCode = foxypress_FixPostVar('foxy_option_code_' . $i, '');
					$optionActive = foxypress_FixPostVar('foxy_option_active_' . $i);
					$optionQuantity = foxypress_FixPostVar('foxy_option_quantity_' . $i, '');
					$optionImage = foxypress_FixPostVar('foxy_option_image_' . $i, '');
					$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options" . "
								  set option_group_id = '" . $optiongroupid . "'
									  ,option_text = '" . $optionname . "'
									  ,option_value = '" . $optionvalue . "'
									  ,option_extra_price = '" . $optionextraprice . "'
									  ,option_extra_weight = '" . $optionextraweight . "'
									  ,option_code = '" . $optionCode. "'
									  ,option_quantity = " . (($optionQuantity == "") ? "NULL" : "'" . $optionQuantity . "'") . "
									  ,option_image = '" . $optionImage . "'
									 ,option_active = '" . $optionActive. "'
								  where option_id='" . $optionID . "'");

					// If this option has a code set, add it to the array of unique
					//   option groups with codes set
					if (!empty($optionCode)) {
						$option_groups_codes[$optiongroupid] = true;
					}
				}

				// This line used for debugging to database
				//foxypress_LogEvent("Saving options. Option groups with codes: " . count($option_groups_codes));

				// If there is more than one option group with product codes assigned, clear
				//   all this product's option group codes and quantities
				if (count($option_groups_codes) > 1) {
					$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options" . "
						  set option_quantity = NULL
							,option_code = NULL
						  where inventory_id = '" . $inventory_id . "'");
				}
			}
			//update sort order
			$OptionsOrderArray = explode(",", foxypress_FixPostVar('hdn_foxy_options_order'));
			$counter = 1;
			foreach ($OptionsOrderArray as $OptionID)
			{
				$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_options" . " set option_order = '$counter' where option_id='" . $OptionID . "'");
				$counter++;
			}
	}
	else if(isset($_POST['foxy_attribute_save'])) //save attributes
	{
		$attributename = foxypress_FixPostVar('foxy_attribute_name');
		$attributevalue = foxypress_FixPostVar('foxy_attribute_value');
		if($attributename != "" && $attributevalue != "")
		{
			//insert new option
			$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_attributes" . " (inventory_id, attribute_text, attribute_value) values ('" . $inventory_id . "', '" . $attributename . "', '" . $attributevalue . "')");
		}
	}
	else // save details
	{
		//save details data
		foxypress_save_meta_data($post_id, '_price',number_format((double)str_replace(",","",$_POST['_price']),2,".",""));
		foxypress_save_meta_data($post_id, '_code',trim($_POST['_code']));
		foxypress_save_meta_data($post_id, '_weight', $_POST['_weight1']);
		foxypress_save_meta_data($post_id, '_weight2', $_POST['_weight2']);
		foxypress_save_meta_data($post_id, '_quantity', trim($_POST['_quantity']));
		foxypress_save_meta_data($post_id, '_quantity_min',$_POST['_quantity_min']);
		foxypress_save_meta_data($post_id, '_quantity_max',$_POST['_quantity_max']);

		//save sale pricing
		if($_POST['_saleprice']!=""){
			foxypress_save_meta_data($post_id, '_saleprice',number_format((double)str_replace(",","",$_POST['_saleprice']),2,".",""));
		}else{
			foxypress_save_meta_data($post_id, '_saleprice',$_POST['_saleprice']);
		}

		foxypress_save_meta_data($post_id, '_salestartdate',$_POST['_salestartdate']);
		foxypress_save_meta_data($post_id, '_saleenddate',$_POST['_saleenddate']);

		//save discounts
		foxypress_save_meta_data($post_id, '_discount_quantity_amount',$_POST['_discount_quantity_amount']);
		foxypress_save_meta_data($post_id, '_discount_quantity_percentage',$_POST['_discount_quantity_percentage']);
		foxypress_save_meta_data($post_id, '_discount_price_amount',$_POST['_discount_price_amount']);
		foxypress_save_meta_data($post_id, '_discount_price_percentage',$_POST['_discount_price_percentage']);

		//save subscriptions
		if (isset($_POST['_sub_frequency'])) {
			if ($_POST['_sub_frequency'] == "") {
				foxypress_save_meta_data($post_id, '_sub_frequency',"");
				foxypress_save_meta_data($post_id, '_sub_startdate',"");
				foxypress_save_meta_data($post_id, '_sub_enddate',"");
			} else {
				foxypress_save_meta_data($post_id, '_sub_frequency',$_POST['_sub_frequency']);
				foxypress_save_meta_data($post_id, '_sub_startdate',$_POST['_sub_startdate']);
				foxypress_save_meta_data($post_id, '_sub_enddate',$_POST['_sub_enddate']);
			}
		}

		//save item availability
		foxypress_save_meta_data($post_id, '_item_start_date',$_POST['_item_start_date']);
		foxypress_save_meta_data($post_id, '_item_end_date',$_POST['_item_end_date']);
		foxypress_save_meta_data($post_id, '_item_active',$_POST['_item_active']);

		foxypress_save_meta_data($post_id, '_item_color',$_POST['_item_color']);

		//categories
		$cats = $_POST['foxy_categories'];
		
		//set categories as defined
		$AllCategories = $wpdb->get_results( "SELECT category_id FROM " . $wpdb->prefix . "foxypress_inventory_categories" );
		$CategoryArray = array();
		foreach($AllCategories as $ac)
		{
			$CategoryArray[] = $ac->category_id;
		}
		foreach($CategoryArray as $cat)
		{
			if(in_array($cat, $cats))
			{
				//check to see if it exists already
				$relationshipExists = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " WHERE inventory_id = '" . mysql_escape_string($post_id) . "' AND category_id='" . $cat . "'");
				if(empty($relationshipExists))
				{
					$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_to_category" . " (inventory_id, category_id) values ('" . mysql_escape_string($post_id) . "', '" . mysql_escape_string($cat)  . "')";
					$wpdb->query($sql);
				}
			}
			else
			{
				$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " WHERE inventory_id = '" . mysql_escape_string($post_id) . "' and category_id='" . $cat . "'";
				$wpdb->query($sql);
			}
		}
		
		//set Default as category if there were no categories selected
		if (count($cats) == 0) 
		{
			$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_inventory_to_category" . " (inventory_id, category_id) values ('" . mysql_escape_string($post_id) . "', '1')";
			$wpdb->query($sql);
		}

		//update primary category
		$primaryCategories = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "foxypress_inventory_to_category" . " WHERE inventory_id = '" . mysql_escape_string($post_id) ."'");
		foreach($primaryCategories as $pc)
		{
			$update_value = ($pc->category_id == $_POST['_primary_category'] ? 1 : 0);
			$sql = "UPDATE " . $wpdb->prefix . "foxypress_inventory_to_category" . " SET category_primary = " . $update_value . " WHERE category_id = " . mysql_escape_string($pc->category_id)  . " AND inventory_id = " . $pc->inventory_id ."";
			$wpdb->query($sql);	
		}

		//save item email
		foxypress_save_meta_data($post_id, '_item_email_active',$_POST['_item_email_active']);
		foxypress_save_meta_data($post_id, '_item_email_template',$_POST['_item_email_template']);

		//save deal info
		foxypress_save_meta_data($post_id, '_item_deal_active',$_POST['_item_deal_active']);
		foxypress_save_meta_data($post_id, '_item_deal_code_type',$_POST['_item_deal_code_type']);
		foxypress_save_meta_data($post_id, '_item_deal_static_code',$_POST['_item_deal_static_code']);
		
		//save related products
		$products_selected['_relatedProducts'] = $_POST['_relatedProducts'];
		foreach ($products_selected as $key => $value) { 
			if( $post->post_type == 'revision' ) return;
			$value = implode(',', (array)$value); 
			if(get_post_meta($post_id, $key, FALSE)) {
			    update_post_meta($post_id, $key, $value);
			} else { 
			    add_post_meta($post_id, $key, $value);
			}
			if(!$value) delete_post_meta($post_id, $key);
		}
	}
	return $post_id;
}

function foxypress_product_options_setup()
{
	global $wpdb, $post;
	$inventory_id = $post->ID;
	$groups = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_option_group order by option_group_name");
	$groups_selection_list = "";
	if(!empty($groups))
	{
		foreach($groups as $group)
		{
			$groups_selection_list .= "<option value=\"" . $group->option_group_id . "\">" . $group->option_group_name . "</option>";
		}
	}
	if(!empty($groups))
	{
?>
	<script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/expand.js"></script>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {

			jQuery( "#sortable" ).sortable(
				{
					update: function(event, ui) { jQuery('#hdn_foxy_options_order').val(jQuery( "#sortable" ).sortable("toArray")); }
				}
			);
			//jQuery( "#sortable" ).disableSelection();
			jQuery("span.expand").toggler({speed: "slow"});

			//Option Image Upload
			jQuery('.add_option_image').click(function() {
				parentID = jQuery(this).closest('td').attr('id');
				console.log(parentID);
				uploadID = jQuery(this).prev('input');
    			tb_show('', 'media-upload.php?type=image&amp;TB_iframe=1&amp;width=640&amp;height=536');
    			return false;
    		});
    		
    		wp.media.editor.send.attachment = function( a, b) {
		       console.log(b); // b has all informations about the attachment
		       // or whatever you want to do with the data at this point
		       // original function makes an ajax call to retrieve the image html tag and does a little more
		    };
    		
    		window.original_send_to_editor = window.send_to_editor;

    		window.send_to_editor = function(html) {
    			imgurl = jQuery('img',html).attr('src');
    			console.log(imgurl);	
    			uploadID.val(imgurl); /*assign the value to the input*/
    			console.log('Editor: ' + parentID);
    			jQuery('#' + parentID + ' .option_image_preview').html('<img src="' + imgurl + '">');
    			tb_remove();
			};
			
//			uploadID = jQuery('#foxy_option_image');
//			parentID = jQuery(uploadID).closest('td').attr('id');
//    		
    		// backup of original send function
//    		original_send = wp.media.editor.send.attachment;
//    		
    		// new send function
//    		wp.media.editor.send.attachment = function( a, b) {
//    		   console.log(b); // b has all informations about the attachment	
//    		   imgurl = b.url;
//    		   uploadID.val(imgurl);
//    		   console.log(imgurl);
//    		   console.log(parentID);
//    		   console.log(uploadID);
//    		   jQuery('#' + parentID + ' .option_image_preview').html('<img src="' + imgurl + '">');
//    		   tb_remove();
    		   // or whatever you want to do with the data at this point
    		   // original function makes an ajax call to retrieve the image html tag and does a little more
//    		};
    		
    		// wp.media.send.to.editor will automatically trigger window.send_to_editor for backwards compatibility
    		
			// backup original window.send_to_editor
//			window.original_send_to_editor = window.send_to_editor; 
//			
			// override window.send_to_editor
//			window.send_to_editor = function(html) {
			   // html argument might not be useful in this case
			   // use the data from var b (attachment) here to make your own ajax call or use data from b and send it back to your defined input fields etc.
//			   imgurl = jQuery('img',html).attr('src');
//			   uploadID.val(imgurl); /*assign the value to the input*/
//			   console.log('Editor: ' + parentID);
//			   console.log('imgurl: ' + imgurl);
//			   jQuery('#' + parentID + ' .option_image_preview').html('<img src="' + imgurl + '">');
//			   tb_remove();
//			}

			});

	</script>
	<h4><?php _e('New Product Option', 'foxypress'); ?></h4>
	<p><?php _e('Below you can add options to your product.  These are useful for allowing various sizes or colors, or additional weights and prices for a product.  Setting a quantity available for an option level is possible, but remember to set a code for the option or it will not take affect.  Also, when re-ordering options, make sure you click save options, not the blue "update" button.  Read more on product options ', 'foxypress'); ?><a href="http://www.foxy-press.com/getting-started/managing-inventory/" target="_blank"><?php _e('here', 'foxypress'); ?></a>.</p>
	<table class="product_options" cellpadding="5" cellspacing="5">
		<tr>
			<td><input type="text" id="foxy_option_name" name="foxy_option_name" style="min-width:150px;" /></td>
			<td class="field_name"><?php _e('Name', 'foxypress'); ?></td>
			<td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Enter the name of your product option.', 'foxypress'); ?></span></a>
                </div>
            </td>
		</tr>
		<tr>
			<td><input type="text" id="foxy_option_value" name="foxy_option_value" style="min-width:150px;" /></td>
			<td class="field_name"><?php _e('Value', 'foxypress'); ?></td>
			<td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Enter the value of your product option.', 'foxypress'); ?></span></a>
                </div>
            </td>
		</tr>
		<tr>
			<td><select name="foxy_option_group" id="foxy_option_group" style="min-width:150px;"><?php echo($groups_selection_list) ?></select></td>
			<td class="field_name"><?php _e('Option Group', 'foxypress'); ?></td>
            <td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Select the group that this option will be a part of.', 'foxypress'); ?></span></a>
                </div>
            </td>
		</tr>
		<tr>
			<td><input type="text" id="foxy_option_code" name="foxy_option_code" style="min-width:150px;" /></td>
			<td class="field_name"><?php _e('Unique Code', 'foxypress'); ?></td>
            <td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('If your product code differs for different options, enter the correct code here. Currently option level quantities only work correctly for 1 option group per item.', 'foxypress'); ?></span></a>
                </div>
            </td>
		</tr>
		<tr>
			<td><input type="text" id="foxy_option_extra_weight" name="foxy_option_extra_weight" style="min-width:150px;" /> lb(s)</td>
			<td class="field_name"><?php _e('Extra Weight', 'foxypress'); ?></td>
			<td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Enter negative numbers if you need to subtract from the default weight.', 'foxypress'); ?></span></a>
                </div>
            </td>
		</tr>
        <tr>
            <td><?php echo(foxypress_GetCurrencySymbol()); ?><input type="text" id="foxy_option_extra_price" name="foxy_option_extra_price" style="min-width:150px;" /></td>
            <td class="field_name"><?php _e('Extra Price', 'foxypress'); ?></td>
            <td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Enter negative numbers if you need to subtract from the default price.', 'foxypress'); ?></span></a>
                </div>
            </td>
        </tr>
        <tr>
            <td><input type="text" id="foxy_option_quantity" name="foxy_option_quantity" style="min-width:150px;" /></td>
            <td class="field_name"><?php _e('Quantity', 'foxypress'); ?></td>
            <td>
                <div id="inventory-help">
                    <a href="#"><img src="<?php echo(plugins_url())?>/foxypress/img/help-icon.png" height="15px" />
                    <span><?php _e('Use this field if you have a unique product code and would like to keep track of inventory at the option specific level. Quantity will be reduced by one for each option sold. When quantity reaches 0, option will be listed as sold out. Leave blank to disable quantity tracking for this option.', 'foxypress'); ?></span></a>
                </div>
            </td>
        </tr>
        <tr>
        	<td id="0">
            	<input type="hidden" id="foxy_option_image" name="foxy_option_image" />
            	<a title="Add an Image" class="thickbox add_option_image" href="#">Upload</a><br /><br />
            	<div class="option_image_preview" style="max-width: 150px; max-height: 150px; overflow: hidden; border: 4px solid #c4c4c4;"></div>
            </td>
            <td class="field_name"><?php _e('Option Image', 'foxypress'); ?></td>
        </tr>
        <tr>
            <td colspan="3"><input type="submit" id="foxy_option_save" name="foxy_option_save" value="<?php _e('Save'); ?> &raquo;"  class="button bold"  /></td>
        </tr>
    </table>
    <h4><?php _e('Current Options', 'foxypress'); ?></h4>
    <div class="demo">
		<style>
			#sortable li {font-size: 12px!important;}
		</style>
		<ul id="sortable">

        <?php
		$foxy_inv_options = $wpdb->get_results("select o.*, og.option_group_name
											from " . $wpdb->prefix . "foxypress_inventory_options" . " as o
											inner join " . $wpdb->prefix . "foxypress_inventory_option_group" . " as og on o.option_group_id = og.option_group_id
											where o.inventory_id = '" . $inventory_id .  "'
											order by option_order");
		$current_option_order = "";
		$row = 1;
		if(!empty($foxy_inv_options))
		{
			foreach($foxy_inv_options as $foxyopt)
			{
				$current_option_order .= ($current_option_order == "") ? $foxyopt->option_id : "," . $foxyopt->option_id;
		?>

			<li class="ui-state-default" id="<?php echo($foxyopt->option_id);?>">
				<div class="ui-icon ui-icon-arrowthick-2-n-s"></div>
				<span class="expand"><?php echo($foxyopt->option_text . " - " . $foxyopt->option_value);?></span>
				<div class="collapse">
				<?php
					echo("<table class=\"product_options\" cellpadding=\"5\" cellspacing=\"5\" class=\"\">
						<tr>
							<td><input type=\"text\" name=\"foxy_option_text_" . $row . "\" id=\"foxy_option_text_" . $row . "\" value=\"" . $foxyopt->option_text . "\" style=\"min-width:150px;\"></td>
							<td class=\"field_name\">" . __('Name', 'foxypress') . "</td>
						</tr>
						<tr>
							<td><input type=\"text\" name=\"foxy_option_value_" . $row . "\" id=\"foxy_option_value_" . $row . "\" value=\"" . $foxyopt->option_value . "\" style=\"min-width:150px;\"></td>
							<td class=\"field_name\">" . __('Value', 'foxypress') . "</td>
						</tr>
						<tr>
							<td><select id=\"foxy_option_group_" . $row . "\" name=\"foxy_option_group_" . $row . "\" style=\"min-width:150px;\">" . foxypress_BuildInventoryOptionGroupList($groups, $foxyopt->option_group_id) . "</select></td>
							<td class=\"field_name\">" . __('Option Group', 'foxypress') . "</td>
						</tr>
						<tr>
							<td><input type=\"text\" name=\"foxy_option_code_" . $row . "\" id=\"foxy_option_code_" . $row . "\" value=\"" . $foxyopt->option_code . "\" style=\"min-width:150px;\"></td>
							<td class=\"field_name\">" . __('Unique Code', 'foxypress') . "</td>
						</tr>
						<tr>
							<td><input type=\"text\" name=\"foxy_option_extra_weight_" . $row . "\" id=\"foxy_option_extra_weight_" . $row . "\" value=\"" . number_format($foxyopt->option_extra_weight, 2) . "\" style=\"min-width:150px;\"> lb(s)</td>
							<td class=\"field_name\">" . __('Extra Weight', 'foxypress') . "</td>
						</tr>
						<tr>
						    <td>" . foxypress_GetCurrencySymbol() . "<input type=\"text\" name=\"foxy_option_extra_price_" . $row . "\" id=\"foxy_option_extra_price_" . $row . "\" value=\"" . number_format($foxyopt->option_extra_price, 2) . "\" style=\"min-width:150px;\"></td>
						    <td class=\"field_name\">" . __('Extra Price', 'foxypress') . "</td>
						</tr>
						<tr>
						    <td><input type=\"text\" id=\"foxy_option_quantity_" . $row . "\" name=\"foxy_option_quantity_" . $row . "\" value=\"" . $foxyopt->option_quantity . "\" style=\"min-width:150px;\" /></td>
						    <td class=\"field_name\">" . __('Quantity', 'foxypress') . "</td>
						</tr>
						<tr>
							<td id=\"" . $row . "\">
								<input type=\"hidden\" id=\"foxy_option_image_" . $row . "\" name=\"foxy_option_image_" . $row . "\" value=\"" . $foxyopt->option_image . "\" style=\"min-width:150px;\" />
								<a title=\"Add an Image\" class=\"thickbox add_option_image\" href=\"#\">Upload</a><br /><br />
								<div class=\"option_image_preview\" style=\"max-width: 150px; max-height: 150px; overflow: hidden; border: 4px solid #c4c4c4; position: relative; margin: 0;\">" . (($foxyopt->option_image) ? "<img src=\"" . $foxyopt->option_image . "\">" : "") . "</div>
							</td>
						    <td class=\"field_name\">" . __('Option Image', 'foxypress') . "</td>
						</tr>
						<tr>
							<td>
								<select id=\"foxy_option_active_" . $row . "\" name=\"foxy_option_active_" . $row . "\">
									<option value=\"1\" " . (($foxyopt->option_active == "1") ? "selected=\"selected\"" : "") . ">" . __('Yes', 'foxypress') . "</option>
									<option value=\"0\" " . (($foxyopt->option_active == "0") ? "selected=\"selected\"" : "") . ">" . __('No', 'foxypress') . "</option>
								</select>
							</td>
							<td class=\"field_name\">" . __('Active', 'foxypress') . "</td>
						</tr>
		                </table>
						<input type=\"hidden\" name=\"hdn_foxy_option_id_" . $row . "\" id=\"hdn_foxy_option_id_" . $row . "\" value=\"" . $foxyopt->option_id . "\" />
						<a class=\"button bold\" href=\"" . get_admin_url() . "post.php?post=" . $post->ID . "&message=4&action=edit&deleteoption=true&inventory_id=" . $inventory_id . "&optionid=" . $foxyopt->option_id . "\"  onclick=\"return confirm('" . __('Are you sure you want to delete this option?', 'foxypress') . "');\">" . __('Delete Option', 'foxypress') . "</a>");
                		$row++;
					?>
            	</div>
			</li>
        <?php
			}
		}
		else
		{
			echo("There are currently no options for this inventory item");
		}
		?>
		</ul>
	</div>



    <br />
    <input type="submit" id="foxy_options_update" name="foxy_options_update" value="<?php _e('Save Options', 'foxypress'); ?> &raquo;"  class="button bold" />
    <input type="hidden" id="hdn_foxy_options_order" name="hdn_foxy_options_order" value="<?php echo($current_option_order) ?>" />
    <input type="hidden" id="hdn_foxy_options_count" name="hdn_foxy_options_count" value="<?php echo($row-1); ?>" />
<?php
	}
	else
	{
		_e("<div>
				You do not have any option groups set up yet. In order to add a new option for this inventory item you must
				add a <a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-option-groups\">new option group</a>.
		   </div>", "foxypress");
	}
}

function foxypress_BuildInventoryOptionGroupList($groups, $selectedid)
{
	$groups_selection_list = "";
	foreach($groups as $group)
	{
		$groups_selection_list .= "<option value=\"" . $group->option_group_id . "\" " . (($group->option_group_id == $selectedid) ? "selected=\"selected\"" : "") . ">" . $group->option_group_name . "</option>";
	}
	return $groups_selection_list;
}
function foxypress_product_attributes_setup()
{
	global $wpdb, $post;
?>
	<h4><?php _e('New Product Attribute', 'foxypress'); ?></h4>
	<p><?php _e('Product attributes are fantastic for using in conjunction with developing custom functionality, or adding additional information to your product that you\'d like to see in order management.', 'foxypress'); ?></p>
	<table cellpadding="5" cellspacing="5">
        <tr>
            <td><?php _e('Attribute Name', 'foxypress'); ?></td>
            <td><input type="text" id="foxy_attribute_name" name="foxy_attribute_name" /></td>
        </tr>
        <tr>
            <td><?php _e('Attribute Value', 'foxypress'); ?></td>
            <td><input type="text" id="foxy_attribute_value" name="foxy_attribute_value" /></td>
        </tr>
        <tr>
            <td colspan="2"><input type="submit" id="foxy_attribute_save" name="foxy_attribute_save" value="<?php _e('Save'); ?> &raquo;"  class="button bold"  /></td>
        </tr>
    </table>
    <h4><?php _e('Current Product Attributes', 'foxypress'); ?></h4>
    <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
            <thead>
                <tr>
                    <th class="manage-column" scope="col"><?php _e('Attribute Name', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col"><?php _e('Attribute Value', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
        <?php
            //get options
            $foxy_inv_attributes = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_attributes where inventory_id = '" .  $post->ID .  "' order by attribute_text");
            if(!empty($foxy_inv_attributes))
            {
                foreach($foxy_inv_attributes as $foxyatt)
                {
                    echo("<tr>
                            <td>" . stripslashes($foxyatt->attribute_text) . "</td>
                            <td>" . stripslashes($foxyatt->attribute_value) . "</td>
                            <td><a href=\"" . get_admin_url() . "post.php?post=" . $post->ID . "&message=4&action=edit&deleteattribute=true&inventory_id=" . $post->ID . "&attributeid=" . $foxyatt->attribute_id . "\" class=\"delete\" onclick=\"return confirm('Are you sure you want to delete this attribute?');\"><img src=\"" . plugins_url() . "/foxypress/img/delimg.png\" alt=\"Delete\" class=\"noBorder\" /></td>
                         </tr>");
                }
            }
            else
            {
                echo("<tr><td colspan=\"3\">" . __('There are currently no attributes for this inventory item', 'foxypress') . "</td></tr>");
            }
        ?>
        </table>
<?php
}
?>