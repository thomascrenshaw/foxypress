<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Foxypress_affiliate_banners extends WP_List_Table 
{

	function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'banner',
            'plural'    => 'banners',
            'ajax'      => false
        ) );
    }

	function foxypress_FixGetVar($variable, $default = 'management')
    {
        $value = $default;
        if(isset($_GET[$variable]))
        {
            $value = trim($_GET[$variable]);
            if(get_magic_quotes_gpc())
            {
                $value = stripslashes($value);
            }
            $value = mysql_real_escape_string($value);
        }
        return $value;
    }

    function foxypress_FixPostVar($variable, $default = '')
    {
        $value = $default;
        if(isset($_POST[$variable]))
        {
            $value = trim($_POST[$variable]);
            $value = mysql_real_escape_string($value);
        }
        return $value;
    }

    // Page Default
    function column_default($item, $column_name)
    {
        switch($column_name){
            case 'test':
            default:
                return print_r($item,true);
        }
    }

	/** ************************************************************************
     * Main page affiliate banner columns
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    
    function column_management_asset_type($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->foxy_asset_type
        );
    }

    function column_management_asset_name($item)
    {
        //Build row actions
        $actions = array(
            'view_banner' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&banner_id=%s">' . __('Edit Details', 'foxypress') . '</a>',
				$_REQUEST['page'],'view_banner',$item->id)
        );
        
        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item->foxy_asset_name,
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    function column_management_asset_image($item)
    {
		return '<img src="' . content_url() . '/affiliate_images/' . $item->foxy_asset_file_name . $item->foxy_asset_file_ext . '" style="max-height:32px; max-width:40px;" />';
        /*return sprintf('%1$s',
            $item->foxy_asset_file_name . $item->foxy_asset_file_ext
        );*/
    }

	function column_management_asset_landing_url($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->foxy_asset_landing_url
        );
    }
		
	function column_management_asset_delete($item)
    {
        return sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&banner_id=%s" onclick="return confirm(\'' . __('Are you sure you want to delete this banner?','foxypress') . '\');">' . __('Delete', 'foxypress') . '</a>',
			/*$1%s*/ $_REQUEST['page'],'delete_banner',$item->id
		);
    }

	function get_columns()
    {
        $columns = array(
                'management_asset_name'		    => __('Asset Name', 'foxypress'),
                'management_asset_type'         => __('Asset Type', 'foxypress'),
                'management_asset_image'        => __('Image', 'foxypress'),
                'management_asset_landing_url'  => __('URL', 'foxypress'),
				'management_asset_delete'  		=> __('Delete', 'foxypress')
            );

        return $columns;

    }
    
    function get_sortable_columns()
    {
        $sortable_columns = array(
                'management_asset_name'			=> array('management_asset_name',true),     //true means its already sorted
                'management_asset_type'         => array('management_asset_type',false),
                'management_asset_landing_url'	=> array('management_asset_landing_url',false)
            );
        
        return $sortable_columns;
    }
    
    function get_bulk_actions() 
    {
        $actions = array();

        return $actions;
    }
    
    function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        //if( 'delete'===$this->current_action() ) {
            //wp_die('Items deleted (or they would be if we had items to delete)!');
        //}
    }
    
    function prepare_items($order_by = '', $order = '')
    {
        //How many items per page
        $per_page = 20;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        global $wpdb;

        if (!$order) {
			$sort_order = 'ASC';
		} else {
			$sort_order = strtoupper($order);
		}
		
		if ($order_by === 'management_asset_name')
		{
			$sort_by = 'foxy_asset_name ' . $sort_order;
		}
		else if ($order_by === 'management_asset_type')
		{
			$sort_by = 'foxy_asset_type ' . $sort_order;
		}
		else if ($order_by === 'management_asset_landing_url')
		{
			$sort_by = 'foxy_asset_landing_url ' . $sort_order;
		}
		else
		{
			$sort_by = 'id ASC';
		}
		
		$sql_data = "SELECT *
			FROM " . $wpdb->base_prefix . "foxypress_affiliate_assets
			ORDER BY " . $sort_by;

        $data = $wpdb->get_results($sql_data);
        
        $current_page = $this->get_pagenum();
        
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ));
    }

	function get_affiliate_banner()
    {
        global $wpdb;
        $banner_id = $this->foxypress_FixGetVar('banner_id');

        $data = "SELECT *
                FROM " . $wpdb->prefix . "foxypress_affiliate_assets
                WHERE id = " . $banner_id;

        return $wpdb->get_results($data);
    }
}

class Foxypress_affiliate_management extends WP_List_Table 
{
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'affiliate',
            'plural'    => 'affiliates',
            'ajax'      => false
        ) );
    }

    function foxypress_FixGetVar($variable, $default = 'management')
    {
        $value = $default;
        if(isset($_GET[$variable]))
        {
            $value = trim($_GET[$variable]);
            if(get_magic_quotes_gpc())
            {
                $value = stripslashes($value);
            }
            $value = mysql_real_escape_string($value);
        }
        return $value;
    }

    function foxypress_FixPostVar($variable, $default = '')
    {
        $value = $default;
        if(isset($_POST[$variable]))
        {
            $value = trim($_POST[$variable]);
            $value = mysql_real_escape_string($value);
        }
        return $value;
    }

    // Page Default
    function column_default($item, $column_name)
    {
        switch($column_name){
            case 'test':
            default:
                return print_r($item,true);
        }
    }
    
    /** ************************************************************************
     * Main page affiliate management columns
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_management_affiliate($item)
    {

        //Build row actions
        $actions = array(
            'view_details' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s">' . __('View Details', 'foxypress') . '</a>',$_REQUEST['page'],'view_details',$item->id)
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->user_nicename,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_management_first_name($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->first_name
        );
    }

    function column_management_last_name($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->last_name
        );
    }

    function column_management_clicks($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->num_clicks
        );
    }

    function column_management_total_due($item)
    {
        global $wpdb;
        $affiliate_id = $item->id;
        $user_detail  = get_userdata($affiliate_id);
        //User detail multisite specific meta variables
        $user_payout_type = $wpdb->prefix . 'affiliate_payout_type'; 
        $user_payout = $wpdb->prefix . 'affiliate_payout';
        $user_referral = $wpdb->prefix . 'affiliate_referral';
        $user_referral_payout_type = $wpdb->prefix . 'affiliate_referral_payout_type'; 
        $user_referral_payout = $wpdb->prefix . 'affiliate_referral_payout';

        $order_detail = $this->get_affiliate_order_details($affiliate_id);

        if ($user_detail->$user_payout_type == 1) {
            $amount_due = $user_detail->$user_payout / 100 * $order_detail[0]->total_unpaid_amount;
        } else {
            $amount_due = $user_detail->$user_payout * $order_detail[0]->num_unpaid_orders;
        }
        $amount_due = number_format($amount_due, 2, '.', ',');

        if ($user_detail->$user_referral_payout_type == 1) {
            $referral_amount_due = $user_detail->$user_referral_payout / 100 * $order_detail[0]->total_unpaid_referral_amount;
        } else {
            if(isset($order_detail[0]->num_unpaid_referral_orders)){
				$referral_amount_due = $user_detail->$user_referral_payout * $order_detail[0]->num_unpaid_referral_orders;
			}else{
				$referral_amount_due = $user_detail->$user_referral_payout * 0;
			}			
        }
        $referral_amount_due = number_format($referral_amount_due, 2, '.', ',');

        return sprintf('$%1$s',
            /*$1%s*/ number_format($amount_due + $referral_amount_due, 2, '.', ',')
        );
    }

    function column_management_total_commission($item)
    {
        global $wpdb;
        $affiliate_id = $item->id;

        $referral = get_user_option('affiliate_referral', $affiliate_id);

        $sql = "SELECT foxy_affiliate_id FROM " . $wpdb->prefix . "foxypress_affiliate_referrals
                    WHERE foxy_affiliate_referred_by_id = " . $affiliate_id;

        $referred_affiliate_ids = $wpdb->get_results($sql);

        if ($referral == 'true' && $referred_affiliate_ids) {
            $commission_ids = array();
            $i = 0;

            foreach ($referred_affiliate_ids as $referred_affiliate)
            {
                $commission_ids[$i] = $referred_affiliate->foxy_affiliate_id;
                $i++;
            }
            $commission_ids = implode(',', $commission_ids);

            $sql = "SELECT
                    (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_referral_payout_type' AND user_id = " . $affiliate_id . ") AS affiliate_referral_payout_type,
                    (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_referral_payout' AND user_id = " . $affiliate_id . ") AS affiliate_referral_payout,
                    (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '2')) AS num_unpaid_referral_orders,
                    (SELECT sum(foxy_transaction_product_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_referral_amount,
                    (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_affiliate_commission_type = '2') AS total_paid_referral_amount,
                    (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_referrals WHERE foxy_affiliate_referred_by_id = " . $affiliate_id . ") AS num_referrals";

            $referral = $wpdb->get_results($sql);

            if ($referral[0]->affiliate_referral_payout_type == 1) {
                $unpaid_referral_commission = $referral[0]->affiliate_referral_payout / 100 * $referral[0]->total_unpaid_referral_amount;
            } else {
                $unpaid_referral_commission = $referral[0]->affiliate_referral_payout * $referral[0]->num_unpaid_referral_orders;
            }
            $total_referral_commission = $unpaid_referral_commission + $referral[0]->total_paid_referral_amount;
        }

        if ($item->affiliate_payout_type == 1) {
            $unpaid_commission = $item->affiliate_payout / 100 * $item->total_unpaid_amount;
        } else {
            $unpaid_commission = $item->affiliate_payout * $item->num_unpaid_orders;
        }
        $total_commission  = $unpaid_commission + $item->total_commission;
		if(isset($total_referral_commission)){
			return sprintf('$%1$s',
				/*$1%s*/ number_format($total_commission + $total_referral_commission, 2, '.', ',')
			);
		}else{
			return sprintf('$%1$s',
				/*$1%s*/ number_format($total_commission, 2, '.', ',')
			);
		}        
    }

    function column_management_total_transactions($item)
    {
        global $wpdb;
        $affiliate_id = $item->id;

        $order_detail = $this->get_affiliate_order_details($affiliate_id);

        return sprintf('%1$s',
            /*$1%s*/ $order_detail[0]->num_total_orders
        );
    }
    
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item->id
        );
    }

    /** ************************************************************************
     * View Detail page affiliate management columns
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_pending_affiliates_first_name($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->first_name
        );
    }

    function column_pending_affiliates_last_name($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->last_name
        );
    }

    function column_pending_affiliates_age($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->age
        );
    }

    function column_pending_affiliates_gender($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->gender
        );
    }

    function column_pending_affiliates_description($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->description
        );
    }

    function column_pending_affiliates_approve($item)
    {
        return sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&approve=%s&affiliate_id=%s">' . __('Approve', 'foxypress') . '</a>',$_REQUEST['page'],'pending_affiliates','true',$item->id);
    }
    
    /** ************************************************************************
     * View Detail page affiliate management columns
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_view_details_order_id($item)
    {
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');

        //Build row actions
        $actions = array(
            'pay_affiliate' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s&order_id=%s">' . __('Pay Affiliate', 'foxypress') . '</a>',$_REQUEST['page'],'pay_affiliate',$affiliate_id,$item->order_id),
            'view_order' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=order-management&transaction=%sb=0&mode=detail">' . __('View Order Detail', 'foxypress') . '</a>',$item->order_id)
        );

        return sprintf('%1$s %2$s',
            /*$1%s*/ $item->order_id,
                     $this->row_actions($actions)
        );
    } 

    function column_view_details_order_total($item)
    {
        return sprintf('$%1$s',
            /*$1%s*/ $item->order_total
        );
    }
    
    function column_view_details_order_commission($item)
    {
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        if ($item->id == $affiliate_id) {
            if ($item->affiliate_payout_type == 1) {
                $order_commission = $item->affiliate_payout / 100 * $item->product_total;
                $order_commission = number_format($order_commission, 2, '.', ',');
            } else {
                $order_commission  = number_format($item->affiliate_payout, 2, '.', ',');
            }
        } else {
            if ($item->affiliate_referral_payout_type == 1) {
                $order_commission = $item->affiliate_referral_payout / 100 * $item->product_total;
                $order_commission = number_format($order_commission, 2, '.', ',');
            } else {
                $order_commission  = number_format($item->affiliate_referral_payout, 2, '.', ',');
            }
        }


        return sprintf('$%1$s',
            /*$1%s*/ $order_commission
        );
    }

    function column_view_details_order_date($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->order_date
        );
    }

    function column_view_details_order_type($item)
    {
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        if ($item->id == $affiliate_id) {
            $order_type = '';
        } else {
            $order_type = 'Referral';
        }

        return sprintf('%1$s',
            /*$1%s*/ $order_type
        );
    }

    /** ************************************************************************
     * View Detail page affiliate management columns
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_view_past_details_order_id($item)
    {
        return sprintf('<strong><a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=order-management&transaction=%1$s&b=0&mode=detail">%2$s</a></strong>',
            /*$1%s*/ $item->foxy_transaction_id,
            /*$2%s*/ $item->foxy_transaction_id
        );
    } 

    function column_view_past_details_order_total($item)
    {
        return sprintf('$%1$s',
            /*$1%s*/ $item->foxy_transaction_order_total
        );
    }

    function column_view_past_details_affiliate_payout($item)
    {   
        if ($item->foxy_affiliate_payout_type == 1) {
            $affiliate_payout = $item->foxy_affiliate_payout . '%';
        } else {
            $affiliate_payout = '$' . $item->foxy_affiliate_payout;
        }

        return sprintf('%1$s',
            /*$1%s*/ $affiliate_payout
        );
    }
    
    function column_view_past_details_order_commission($item)
    {
        return sprintf('$%1$s',
            /*$1%s*/ $item->foxy_affiliate_commission
        );
    }

    function column_view_past_details_payment_method($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->foxy_affiliate_payment_method
        );
    }

    function column_view_past_details_payment_date($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->foxy_affiliate_payment_date 
        );
    }

    function column_view_past_details_order_type($item)
    {
        if ($item->foxy_affiliate_commission_type == 1) {
            $order_type = '';
        } else {
            $order_type = 'Referral';
        }
        return sprintf('%1$s',
            /*$1%s*/ $order_type
        );
    }

    /** ************************************************************************
     * 
     * 
     **************************************************************************/
    function get_columns($mode)
    {
        if ($mode === 'management')
        {
            $columns = array(
                'management_affiliate'          => __('Affiliate', 'foxypress'),
                'management_first_name'         => __('First Name', 'foxypress'),
                'management_last_name'          => __('Last Name', 'foxypress'),
                'management_clicks'             => __('Clicks', 'foxypress'),
                'management_total_due'          => __('Total Due', 'foxypress'),
                'management_total_commission'   => __('Total Commission', 'foxypress'),
                'management_total_transactions' => __('Total Transactions', 'foxypress')
            );
        }
        else if ($mode === 'pending_affiliates')
        {
            $columns = array(
                'pending_affiliates_first_name'  => __('First Name', 'foxypress'),
                'pending_affiliates_last_name'   => __('Last Name', 'foxypress'),
                'pending_affiliates_age'         => __('Age', 'foxypress'),
                'pending_affiliates_gender'      => __('Gender', 'foxypress'),
                'pending_affiliates_description' => __('Message', 'foxypress'),
                'pending_affiliates_approve'     => __('Approve', 'foxypress')
            );
        }
        else if ($mode === 'view_details')
        {
            $columns = array(
                //'cb'                          => '<input type="checkbox" />',
                'view_details_order_id'         => __('Order ID', 'foxypress'),
                'view_details_order_total'      => __('Order Total', 'foxypress'),
                'view_details_order_commission' => __('Affiliate Commission', 'foxypress'),
                'view_details_order_date'       => __('Order Date', 'foxypress'),
                'view_details_order_type'       => __('Order Type', 'foxypress')
            );
        }
        else if ($mode === 'view_past_details')
        {
            $columns = array(
                'view_past_details_order_id'             => __('Order ID', 'foxypress'),
                'view_past_details_order_total'          => __('Order Total', 'foxypress'),
                'view_past_details_affiliate_payout'     => __('Affiliate Payout', 'foxypress'),
                'view_past_details_order_commission'     => __('Affiliate Commission', 'foxypress'),
                'view_past_details_payment_method'       => __('Payment Method', 'foxypress'),
                'view_past_details_payment_date'         => __('Payment Date', 'foxypress'),
                'view_past_details_order_type'           => __('Order Type', 'foxypress')
            );
        }

        return $columns;

    }
    
    function get_sortable_columns($mode)
    {
        if ($mode === 'management')
        {
            $sortable_columns = array(
                'management_affiliate'          => array('management_affiliate',true),     //true means its already sorted
                'management_first_name'         => array('management_first_name',false),
                'management_last_name'          => array('management_last_name',false),
                'management_clicks'             => array('management_clicks',false),
                'management_total_due'          => array('management_total_due',false),
                'management_total_commission'   => array('management_total_commission',false),
                'management_total_transactions' => array('management_total_transactions', false)
            );
        }
        else if ($mode === 'pending_affiliates')
        {
            $sortable_columns = array();
        }
        else if ($mode === 'view_details')
        {
            $sortable_columns = array(
                'view_details_order_id'         => array('view_details_order_id', true),
                'view_details_order_total'      => array('view_details_order_total', false),
                'view_details_order_commission' => array('view_details_order_commission', false),
                'view_details_order_date'       => array('view_details_order_date', false)
            );
        }
        else if ($mode === 'view_past_details')
        {
            $sortable_columns = array(
                'view_past_details_order_id'             => array('view_past_details_order_id', true),
                'view_past_details_order_total'          => array('view_past_details_order_total', false),
                'view_past_details_affiliate_payout'     => array('view_past_details_affiliate_payout', false),
                'view_past_details_order_commission'     => array('view_past_details_order_commission', false),
                'view_past_details_payment_method'       => array('view_past_details_payment_method', false),
                'view_past_details_payment_date'         => array('view_past_details_payment_date', false)
            );
        }
        
        return $sortable_columns;
    }
    
    function get_bulk_actions() 
    {
        $actions = array();

        return $actions;
    }
    
    function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        //if( 'delete'===$this->current_action() ) {
            //wp_die('Items deleted (or they would be if we had items to delete)!');
        //}
    }
    
    function prepare_items($mode, $order_by = '', $order = '')
    {
        //How many items per page
        $per_page = 20;
        
        $columns = $this->get_columns($mode);
        $hidden = array();
        $sortable = $this->get_sortable_columns($mode);
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        global $wpdb;

        if ($mode === 'management')
        {
            if (!$order) {
                $sort_order = 'ASC';
            } else {
                $sort_order = strtoupper($order);
            }

            if ($order_by === 'management_affiliate')
            {
                $sort_by = 'u.user_nicename ' . $sort_order;
            }
            else if ($order_by === 'management_first_name')
            {
                $sort_by = 'first_name ' . $sort_order;
            }
            else if ($order_by === 'management_last_name')
            {
                $sort_by = 'last_name ' . $sort_order;
            }
            else if ($order_by === 'management_clicks')
            {
                $sort_by = 'num_clicks ' . $sort_order;
            }
            else if ($order_by === 'management_total_due')
            {
                $sort_by = 'total_unpaid_amount ' . $sort_order;
            }
            else if ($order_by === 'management_total_commission')
            {
                $sort_by = 'total_commission ' . $sort_order;
            }
            else if ($order_by === 'management_total_transactions')
            {
                $sort_by = 'num_total_orders ' . $sort_order;
            }
            else
            {
                $sort_by = 'u.id ASC';
            }

            $sql = "SELECT user_id FROM " . $wpdb->base_prefix . "usermeta
                WHERE meta_key = '" . $wpdb->prefix . "affiliate_user' AND meta_value = 'true'";

            $affiliate_ids = $wpdb->get_results($sql);
            
            $ids = array();
            $i = 0;
            foreach ($affiliate_ids as $affiliate)
            {
                $ids[$i] = $affiliate->user_id;
                $i++;
            }

            $ids = implode(',', $ids);
			if($ids==""){$ids="''";}
            $sql_data = "SELECT u.id, u.user_nicename,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.id) AS first_name,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.id) AS last_name,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout_type' AND user_id = u.id) AS affiliate_payout_type,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout' AND user_id = u.id) AS affiliate_payout,
                        (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = u.id) AS num_clicks,
                        (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = u.id AND foxy_affiliate_commission_type = '1') AS total_commission,
                        (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = u.id AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS num_unpaid_orders,
                        (SELECT sum(foxy_transaction_product_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = u.id AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_amount
                        FROM " . $wpdb->base_prefix . "users AS u
                        WHERE u.id in (" . $ids . ")
                        ORDER BY " . $sort_by;
        }
        else if ($mode === 'pending_affiliates')
        {
            $sql = "SELECT user_id FROM " . $wpdb->base_prefix . "usermeta
                WHERE meta_key = '" . $wpdb->prefix . "affiliate_user' AND meta_value = 'pending'";

            $pending_affiliate_ids = $wpdb->get_results($sql);
        
            $pending_ids = array();
            $i = 0;
            foreach ($pending_affiliate_ids as $pending_affiliate)
            {
                $pending_ids[$i] = $pending_affiliate->user_id;
                $i++;
            }

            $pending_ids = implode(',', $pending_ids);
			if($pending_ids==""){$pending_ids="''";}
            $sql_data = "SELECT u.id, u.user_nicename,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.id) AS first_name,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.id) AS last_name,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_facebook_page' AND user_id = u.id) AS facebook_page,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_gender' AND user_id = u.id) AS gender,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_age' AND user_id = u.id) AS age,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'description' AND user_id = u.id) AS description
                        FROM " . $wpdb->base_prefix . "users AS u
                        WHERE u.id in (" . $pending_ids . ")
                        ORDER BY u.id ASC";

        }
        else if ($mode === 'view_details')
        {
            if (!$order) {
                $sort_order = 'ASC';
            } else {
                $sort_order = strtoupper($order);
            }

            if ($order_by === 'view_details_order_id')
            {
                $sort_by = 'ft.foxy_transaction_id ' . $sort_order;
            }
            else if ($order_by === 'view_details_order_total')
            {
                $sort_by = 'ft.foxy_transaction_order_total ' . $sort_order;
            }
            else if ($order_by === 'view_details_order_commission')
            {
                $sort_by = 'ft.foxy_transaction_order_total ' . $sort_order;
            }
            else if ($order_by === 'view_details_order_date')
            {
                $sort_by = 'ft.foxy_transaction_date ' . $sort_order;
            }
            else
            {
                $sort_by = 'ft.foxy_transaction_id DESC';
            }

            $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');

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
            array_push($commission_ids, $affiliate_id);
            $commission_ids = implode(',', $commission_ids);

            $sql_data = "SELECT ft.foxy_transaction_id AS order_id, ft.foxy_transaction_product_total AS product_total, ft.foxy_transaction_order_total AS order_total, ft.foxy_transaction_date AS order_date, u.id, u.user_nicename,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout_type' AND user_id = " . $affiliate_id . ") AS affiliate_payout_type,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout' AND user_id = " . $affiliate_id . ") AS affiliate_payout,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_referral_payout_type' AND user_id = " . $affiliate_id . ") AS affiliate_referral_payout_type,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_referral_payout' AND user_id = " . $affiliate_id . ") AS affiliate_referral_payout
                        FROM " . $wpdb->prefix . "foxypress_transaction AS ft
                        LEFT JOIN " . $wpdb->base_prefix . "users AS u ON u.id = ft.foxy_affiliate_id
                        WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND foxy_affiliate_id = " . $affiliate_id . ")
                        ORDER BY " . $sort_by;
        }
        else if ($mode === 'view_past_details')
        {
            if (!$order) {
                $sort_order = 'ASC';
            } else {
                $sort_order = strtoupper($order);
            }

            if ($order_by === 'view_past_details_order_id')
            {
                $sort_by = 'foxy_transaction_id ' . $sort_order;
            } 
            else if ($order_by === 'view_past_details_order_total')
            {
                $sort_by = 'foxy_transaction_order_total ' . $sort_order;
            }
            else if ($order_by === 'view_past_details_affiliate_payout')
            {
                $sort_by = 'foxy_affiliate_payout ' . $sort_order;
            }
            else if ($order_by === 'view_past_details_order_commission')
            {
                $sort_by = 'foxy_affiliate_commission ' . $sort_order;
            }
            else if ($order_by === 'view_past_details_payment_method')
            {
                $sort_by = 'foxy_affiliate_payment_method ' . $sort_order;
            }
            else if ($order_by === 'view_past_details_payment_date')
            {
                $sort_by = 'foxy_affiliate_payment_date ' . $sort_order;
            }
            else
            {
                $sort_by = 'foxy_transaction_id DESC';
            }

            $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');

            $sql_data = "SELECT *
                        FROM " . $wpdb->prefix . "foxypress_affiliate_payments
                        WHERE foxy_affiliate_id = " . $affiliate_id . " 
                        ORDER BY " . $sort_by;
        }

        $data = $wpdb->get_results($sql_data);
        
        $current_page = $this->get_pagenum();
        
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ));
    }

    function get_affiliate_order_details($affiliate_id = '')
    {
        global $wpdb;

        if ($affiliate_id == '') {
            $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        }

        $referral = get_user_option('affiliate_referral', $affiliate_id);

        $sql = "SELECT foxy_affiliate_id FROM " . $wpdb->prefix . "foxypress_affiliate_referrals
                    WHERE foxy_affiliate_referred_by_id = " . $affiliate_id;

        $referred_affiliate_ids = $wpdb->get_results($sql);

        if ($referral == 'true' && $referred_affiliate_ids) {
        
            $commission_ids = array();
            $i = 0;

            foreach ($referred_affiliate_ids as $referred_affiliate)
            {
                $commission_ids[$i] = $referred_affiliate->foxy_affiliate_id;
                $i++;
            }
            $commission_ids = implode(',', $commission_ids);
            
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
        return $wpdb->get_results($data);
    }

    function get_affiliate_user_details()
    {
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        
        $data = get_userdata($affiliate_id);
        return $data;
    }

    function get_affiliate_order_detail()
    {
        global $wpdb;
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        $order_id     = $this->foxypress_FixGetVar('order_id');

        $data = "SELECT *
                FROM " . $wpdb->prefix . "foxypress_transaction
                WHERE foxy_transaction_id = " . $order_id;

        return $wpdb->get_results($data);
    }

    function get_affiliate_counts()
    {
        global $wpdb;
        
        $data = "SELECT 
                (SELECT count(user_id) FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_user' AND meta_value = 'pending') AS total_pending,
                (SELECT count(user_id) FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_user' AND meta_value = 'true') AS total_approved";

        return $wpdb->get_results($data);
    }
}

function foxypress_create_affiliate_table() {

    global $wpdb;

	//Create an instance of our package class for banners...
	$fp_banner	  		 = new Foxypress_affiliate_banners();
    $banner_order_by     = $fp_banner->foxypress_FixGetVar('orderby');
    $banner_order        = $fp_banner->foxypress_FixGetVar('order');

    //Create an instance of our package class for affiliates...
    $fp_affiliate = new Foxypress_affiliate_management();
    $mode         = $fp_affiliate->foxypress_FixGetVar('mode');
    $order_by     = $fp_affiliate->foxypress_FixGetVar('orderby');
    $order        = $fp_affiliate->foxypress_FixGetVar('order');

    //Get user data object
    $user_detail  = $fp_affiliate->get_affiliate_user_details();
    //User detail multisite specific meta variables
    $user_payout_type = $wpdb->prefix . 'affiliate_payout_type'; 
    $user_payout = $wpdb->prefix . 'affiliate_payout';
    $user_referral = $wpdb->prefix . 'affiliate_referral';
    $user_referral_payout_type = $wpdb->prefix . 'affiliate_referral_payout_type'; 
    $user_referral_payout = $wpdb->prefix . 'affiliate_referral_payout';
    $user_affiliate_url = $wpdb->prefix . 'affiliate_url';

    if ($mode === 'management' || $mode === 'pending_affiliates'){ 

        //Fetch, prepare, sort, and filter our data...
        $fp_banner->prepare_items($banner_order_by, $banner_order); 
		$fp_affiliate->prepare_items($mode, $order_by, $order); 
        $affiliate_counts = $fp_affiliate->get_affiliate_counts();
		
		$banner_deleted = $fp_affiliate->foxypress_FixGetVar('banner_deleted');
		if ($banner_deleted === 'true') { ?>
			<div class="error" id="message">
				<p><strong><?php _e('Banner Deleted!', 'foxypress'); ?></strong></p>
			</div>
		<?php }

		$banner_added = $fp_affiliate->foxypress_FixGetVar('banner_added');
		if ($banner_added === 'true') { ?>
			<div class="updated" id="message">
				<p><strong><?php _e('Banner Added!', 'foxypress'); ?></strong></p>
			</div>
		<?php }

		$banner_updated = $fp_affiliate->foxypress_FixGetVar('banner_updated');
		if ($banner_updated === 'true') { ?>
			<div class="updated" id="message">
				<p><strong><?php _e('Banner Updated!', 'foxypress'); ?></strong></p>
			</div>
		<?php } ?>

        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e('FoxyPress Affiliate Management', 'foxypress'); ?></h2>

			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
 				<p><?php _e('Listed below you will find your affiliate banners.', 'foxypress'); ?></p>
            </div>

			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get" style="margin-top: -5px;position:relative;">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <!-- Now we can render the completed list table -->
                <?php echo(sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s" class="add-new-h2" style="position:absolute;left:0px;top:5px;">' . __('Add New Banner', 'foxypress') . '</a>',$_REQUEST['page'],'add_banner')); ?>
				<?php $fp_banner->display(); ?>
            </form>
			
            <?php 
				
				$updated = $fp_affiliate->foxypress_FixGetVar('updated');
	            				
				if ($updated === 'true') { 
	
	                if ($user_detail->$user_payout_type == 1) {
	                    $affiliate_commission = 'Affiliate Commission: ' . $user_detail->$user_payout . '%';
	                } else {
	                    $affiliate_commission = 'Affiliate Commission: $' . $user_detail->$user_payout;
	                }
	                $mail_to = $user_detail->user_email;
	                $mail_subject = get_option("foxypress_affiliate_approval_email_subject");
					//replace tokens
					$mail_subject = str_replace("{{first_name}}", $user_detail->first_name, $mail_subject);
					$mail_subject = str_replace("{{last_name}}", $user_detail->last_name, $mail_subject);
					$mail_subject = str_replace("{{email}}", $user_detail->user_email, $mail_subject);
					$mail_subject = str_replace("{{affiliate_commission}}", $affiliate_commission, $mail_subject);
					$mail_subject = str_replace("{{affiliate_url}}", $user_detail->$user_affiliate_url, $mail_subject);

	                $mail_body    = get_option('foxypress_affiliate_approval_email_body');
					//replace tokens
					$mail_body = str_replace("{{first_name}}", $user_detail->first_name, $mail_body);
					$mail_body = str_replace("{{last_name}}", $user_detail->last_name, $mail_body);
					$mail_body = str_replace("{{email}}", $user_detail->user_email, $mail_body);
					$mail_body = str_replace("{{affiliate_commission}}", $affiliate_commission, $mail_body);
					$mail_body = str_replace("{{affiliate_url}}", $user_detail->$user_affiliate_url, $mail_body);
	
	                foxypress_Mail($mail_to, $mail_subject, $mail_body); ?>
	
	                <div class="updated" id="message">
	                    <p><strong><?php _e('Affiliate Approved!', 'foxypress'); ?></strong></p>
	                </div>

            <?php } ?>
            
            <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
                <?php if ($mode === 'management') { ?>
                    <p><?php _e('Listed below you will find your affiliate\'s current stats.', 'foxypress'); ?></p>
                <?php } else { ?>
                    <p><?php _e('Listed below you will find your pending affiliates.', 'foxypress'); ?></p>
                <?php } ?>
            </div>
            
            <?php $approve = $fp_affiliate->foxypress_FixGetVar('approve');
                if ($approve === 'true') {
					$payout_type          = "";
					$payout               = "";
					$referral             = "";
					$referral_payout_type = "";
					$referral_payout      = "";
					$discount             = "";
					$discount_type        = "";
					$discount_amount      = "";
                    $user_id = $fp_affiliate->foxypress_FixGetVar('affiliate_id');
                    if (isset($_POST['affiliate_approve_submit'])) {
						$payout_type          = $fp_affiliate->foxypress_FixPostVar('affiliate_payout_type');
                        $payout               = $fp_affiliate->foxypress_FixPostVar('affiliate_payout');
                        $referral             = $fp_affiliate->foxypress_FixPostVar('affiliate_referral');
                        $referral_payout_type = $fp_affiliate->foxypress_FixPostVar('affiliate_referral_payout_type');
                        $referral_payout      = $fp_affiliate->foxypress_FixPostVar('affiliate_referral_payout');
                        $discount             = $fp_affiliate->foxypress_FixPostVar('affiliate_discount');
                        $discount_type        = $fp_affiliate->foxypress_FixPostVar('affiliate_discount_type');
                        $discount_amount      = $fp_affiliate->foxypress_FixPostVar('affiliate_discount_amount');
                        $error                = false;

                        if (empty($payout)) {
                            $error = true;
                            $payout_error = true;
                        }

                        if (empty($payout_type)) {
                            $error = true;
                            $payout_type_error = true;
                        }

                        if (!$error) {
                            if ($payout_type == 'percentage') {
                                $payout_type_converted = 1;
                            } else if ($payout_type == 'dollars') {
                                $payout_type_converted = 2;
                            }

                            if ($referral_payout_type == 'percentage') {
                                $referral_payout_type_converted = 1;
                            } else if ($referral_payout_type == 'dollars') {
                                $referral_payout_type_converted = 2;
                            }

                            if ($discount_type == 'percentage') {
                                $discount_type_converted = 1;
                            } else if ($discount_type == 'dollars') {
                                $discount_type_converted = 2;
                            }
                            
                            update_user_option($user_id, 'affiliate_user', 'true');
                            update_user_option($user_id, 'affiliate_payout_type', $payout_type_converted);
                            update_user_option($user_id, 'affiliate_payout', $payout);
                            update_user_option($user_id, 'affiliate_referral', $referral);
                            update_user_option($user_id, 'affiliate_referral_payout_type', $referral_payout_type_converted);
                            update_user_option($user_id, 'affiliate_referral_payout', $referral_payout);
                            update_user_option($user_id, 'affiliate_discount', $discount);
                            update_user_option($user_id, 'affiliate_discount_type', $discount_type_converted);
                            update_user_option($user_id, 'affiliate_discount_amount', $discount_amount);

                            $affiliate_url = plugins_url() . '/foxypress/foxypress-affiliate.php?aff_id=' . $user_id;
                            update_user_option($user_id, 'affiliate_url', $affiliate_url);                         

                            $destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&updated=%s&affiliate_id=%s',$_REQUEST['page'],'pending_affiliates','true',$user_id); ?>

                            <div class="updated" id="message">
                                <p><strong><?php _e('Approving Affiliate...', 'foxypress'); ?></strong></p>
                            </div>

                            <script type="text/javascript">window.location.href ='<?php echo $destination_url; ?>';</script>
        
                        <?php } else { ?>
                            <div class="error" id="message">
                                <?php if ($payout_amount_error) { ?>
                                <p><strong><?php _e('You must enter a payout.', 'foxypress'); ?></strong></p>
                                <?php } ?>

                                <?php if ($payout_type_error) { ?>
                                <p><strong><?php _e('You must select a payout type.', 'foxypress'); ?></strong></p>
                                <?php } ?>
                            </div>
                       <?php }
                    } ?>

                    <form id="affiliate_approve_form" name="affiliate_approve_form" method="POST">
                    <table class="form-table">
                        <tr>
                            <th><label for="affiliate_payout_type"><?php _e('Affiliate Payout Type', 'foxypress'); ?></label></th>
                            <td>
                                <input type="radio" <?php if ($payout_type == 'percentage') { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="percentage">
                                <span class="description"><?php _e('Percentage of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($payout_type == 'dollars') { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="dollars">
                                <span class="description"><?php _e('Dollar amount of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout"><?php _e('Affiliate Payout', 'foxypress'); ?><span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
                            <td><input type="text" name="affiliate_payout" id="affiliate_payout" value="<?php echo $payout; ?>"> 
                            <span class="description"><?php _e('How much will this affiliate earn per sale?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_referral"><?php _e('Enable Affiliate Referrals', 'foxypress'); ?></label></th>
                            <td><input type="checkbox" <?php if ($referral == 'true') { ?>checked="yes" <?php } ?>name="affiliate_referral" id="affiliate_referral" value="true" /> <?php _e('Does this user\'s link allow for affiliate referrals?', 'foxypress'); ?></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout_type"><?php _e('Affiliate Referral Payout Type', 'foxypress'); ?></label></th>
                            <td>
                                <input type="radio" <?php if ($referral_payout_type == 'percentage') { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="percentage">
                                <span class="description"><?php _e('Percentage of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($referral_payout_type == 'dollars') { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="dollars">
                                <span class="description"><?php _e('Dollar amount of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_referral_payout"><?php _e('Affiliate Referral Payout', 'foxypress'); ?></label></th>
                            <td><input type="text" name="affiliate_referral_payout" id="affiliate_referral_payout" value="<?php echo $referral_payout; ?>"> 
                            <span class="description"><?php _e('How much will this affiliate earn per referral sale?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_discount"><?php _e('Enable Affiliate Discount', 'foxypress'); ?></label></th>
                            <td><input type="checkbox" <?php if ($discount == 'true') { ?>checked="yes" <?php } ?>name="affiliate_discount" id="affiliate_discount" value="true" /> <?php _e('Does this user\'s link allow for an additional discount?', 'foxypress'); ?></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout_type"><?php _e('Affiliate Discount Type', 'foxypress'); ?></label></th>
                            <td>
                                <input type="radio" <?php if ($discount_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="percentage">
                                <span class="description"><?php _e('Percentage off of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($discount_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="dollars">
                                <span class="description"><?php _e('Dollar amount off of each order.', 'foxypress'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_discount_amount"><?php _e('Affiliate Discount Amount', 'foxypress'); ?></label></th>
                            <td>
                                <input type="text" name="affiliate_discount_amount" id="affiliate_discount_amount" value="<?php echo $discount_amount; ?>">
                                <span class="description"><?php _e('How much of a discount will user\'s receive?', 'foxypress'); ?> <b>(<?php _e('Enter 30 for 30% or $30.00', 'foxypress'); ?>)</b></span>
                            </td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Approve', 'foxypress'); ?>" class="button-primary" id="affiliate_approve_submit" name="affiliate_approve_submit"></p>
                    </form>

            <?php } ?>
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get" style="position:relative;">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <ul class="subsubsub">
                    <li class="all"><a class="<?php if ($mode === 'management') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s',$_REQUEST['page'],'management'); ?>"><?php _e('Approved Affiliates', 'foxypress'); ?> <span class="count">(<?php echo $affiliate_counts[0]->total_approved; ?>)</span></a> |</li>
                    <li class="administrator"><a class="<?php if ($mode === 'pending_affiliates') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s',$_REQUEST['page'],'pending_affiliates'); ?>"><?php _e('Pending Affiliates', 'foxypress'); ?> <span class="count">(<?php echo $affiliate_counts[0]->total_pending; ?>)</span></a></li>
                </ul>
				<a href="user-new.php" class="add-new-h2" style="position:absolute;left:0px;top:40px;">Add New Affiliate</a>
                <!-- Now we can render the completed list table -->
                <?php $fp_affiliate->display(); ?>
            </form>
            
        </div>			
    <?php } else if ($mode === 'delete_banner') { 
		global $wpdb;
		$banner_id = $fp_banner->foxypress_FixGetVar('banner_id');
		
		$sql = "DELETE FROM " . $wpdb->prefix . "foxypress_affiliate_assets WHERE ID = '" . $banner_id . "'";
		$wpdb->query($sql);
		
		$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&banner_deleted=%s',$_REQUEST['page'],'true');
		_e('Deleting Banner...', 'foxypress');
        echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';
	?>
	<?php } else if ($mode === 'add_banner' || $mode === 'view_banner') { 
		global $wpdb;
		$item = $fp_banner->get_affiliate_banner();

		if (isset($_POST['banner_creation_submit'])) { 
    	
			$foxy_asset_id				= foxypress_FixPostVar('asset_id');
	    	$foxy_asset_type			= foxypress_FixPostVar('asset_type');
	    	$foxy_asset_name     		= foxypress_FixPostVar('asset_name');
	    	$foxy_asset_file_name 		= foxypress_FixPostVar('affiliate_avatar_name');
	    	$foxy_asset_file_ext 		= foxypress_FixPostVar('affiliate_avatar_ext');
	    	$foxy_asset_landing_url		= foxypress_FixPostVar('asset_landing_url');
	    	
	    	$error 		   				= false;
	
	    	if (empty($foxy_asset_name)) {
	    		$error = true;
	    		$asset_name_error = true;
	    	}
	
	    	if (!$error) {
				if($foxy_asset_id==""){
					//new asset
		    		$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_assets (foxy_asset_type, foxy_asset_name, foxy_asset_file_name, foxy_asset_file_ext, foxy_asset_landing_url) values ('" . $foxy_asset_type . "', '" . $foxy_asset_name . "', '" . $foxy_asset_file_name . "', '" . $foxy_asset_file_ext . "', '" . $foxy_asset_landing_url . "')";
					$wpdb->query($sql);	
					
					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&banner_added=%s',$_REQUEST['page'],'true');
					_e('Adding Banner...', 'foxypress');
		            echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';
				}else{
					//update asset
					$sql = "UPDATE " . $wpdb->prefix . "foxypress_affiliate_assets SET foxy_asset_type='" . $foxy_asset_type . "', foxy_asset_name='" . $foxy_asset_name . "', foxy_asset_file_name='" . $foxy_asset_file_name . "', foxy_asset_file_ext='" . $foxy_asset_file_ext . "', foxy_asset_landing_url='" . $foxy_asset_landing_url . "' WHERE id= '" . $foxy_asset_id . "' ";
					$wpdb->query($sql);

					$destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&banner_updated=%s',$_REQUEST['page'],'true');
					_e('Updating Banner...', 'foxypress');
		            echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>';
				}
			}
		}
    ?>
		<div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e('FoxyPress Affiliate Banner Details', 'foxypress'); ?></h2>

			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
 				<p><?php _e('Enter the details of your affiliate banner below. The landing page url can be used to direct a user to a specific page when using this specific banner.', 'foxypress'); ?></p>
            </div>

			<form id="asset_creation_form" name="asset_creation_form" method="POST">
			<table class="form-table">
				<input type="hidden" id="asset_id" name="asset_id" value="<?php echo $item[0]->ID; ?>" />
				<tr class="<?php if ($asset_name_error) { ?>form-invalid<?php } ?>">
					<th><label for="asset_name"><?php _e('Asset Name', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><input class="regular-text" type="text" name="asset_name" id="asset_name" value="<?php echo $item[0]->foxy_asset_name; ?>"><br /></td>
				</tr>
				<tr class="<?php if ($asset_type_error) { ?>form-invalid<?php } ?>">
					<th><label for="asset_type"><?php _e('Asset Type', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td>
						<select name="asset_type" id="asset_type">
							<option><?php _e('Select', 'foxypress'); ?></option>
							<option <?php if ($item[0]->foxy_asset_type == 'Image') { ?>selected<?php } ?> value="Image"><?php _e('Image', 'foxypress'); ?></option>
						</select>
					</td>
				</tr>			
				<tr class="<?php if ($avatar_error) { ?>form-invalid<?php } ?>">
					<th><label for="banner_avatar"><?php _e('Banner', 'foxypress'); ?></label></th>
					<td>
						<div id="avatar"><?php if ($item[0]->foxy_asset_file_name) { ?><img src="<?php echo content_url(); ?>/affiliate_images/<?php echo $item[0]->foxy_asset_file_name; ?><?php echo $item[0]->foxy_asset_file_ext; ?>" width="96" height="96" alt="" /><?php } ?></div>
						<input type="file" name="avatar_upload" id="avatar_upload" value="">
						<input type="hidden" name="affiliate_avatar_name" id="affiliate_avatar_name" value="">
						<input type="hidden" name="affiliate_avatar_ext" id="affiliate_avatar_ext" value="">
					</td>
				</tr>
				<tr class="<?php if ($asset_landing_url_error) { ?>form-invalid<?php } ?>">
					<th><label for="asset_landing_page"><?php _e('Landing Page URL', 'foxypress'); ?></label></th>
					<td><input class="regular-text" type="text" name="asset_landing_url" id="asset_landing_url" value="<?php echo $item[0]->asset_landing_url; ?>"><br /></td>
				</tr>
			</table>
			<p class="submit"><input type="submit" value="<?php _e('Save Banner', 'foxypress'); ?>" class="button-primary" id="banner_creation_submit" name="banner_creation_submit"></p>
			</form>
		</div>
	<?php } else if ($mode === 'view_details' || $mode === 'view_past_details') { 
        
        global $wpdb;
        //Fetch, prepare, sort, and filter our data...
        $fp_affiliate->prepare_items($mode, $order_by, $order); ?>
        
        <?php
            $order_detail = $fp_affiliate->get_affiliate_order_details($commission_ids);

            if ($user_detail->$user_payout_type == 1) {
                $amount_due = $user_detail->$user_payout / 100 * $order_detail[0]->total_unpaid_amount;
            } else {
                $amount_due = $user_detail->$user_payout * $order_detail[0]->num_unpaid_orders;
            }
            $amount_due = number_format($amount_due, 2, '.', ',');

            if ($user_detail->$user_referral_payout_type == 1) {
                $referral_amount_due = $user_detail->$user_referral_payout / 100 * $order_detail[0]->total_unpaid_referral_amount;
            } else {
                $referral_amount_due = $user_detail->$user_referral_payout * $order_detail[0]->num_unpaid_referral_orders;
            }
            $referral_amount_due = number_format($referral_amount_due, 2, '.', ',');

            $total_unpaid_orders = $order_detail[0]->num_unpaid_referral_orders + $order_detail[0]->num_unpaid_orders;
        ?>    

        <div class="wrap">

            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php if (!$user_detail->first_name && !$user_detail->last_name) { echo $user_detail->user_nicename; } else { echo $user_detail->first_name . " " . $user_detail->last_name; } ?> :: <?php _e('Affiliate Detail', 'foxypress'); ?> <a class="add-new-h2" href="<?php echo get_admin_url(); ?>user-edit.php?user_id=<?php echo $user_detail->ID; ?>"><?php _e('Edit User', 'foxypress'); ?></a></h2>

            <?php $updated = $fp_affiliate->foxypress_FixGetVar('updated');
            if ($updated === 'true') { ?>
            <div class="updated" id="message">
                <p><strong><?php _e('Payment Submitted Successfully', 'foxypress'); ?></strong></p>
            </div>
            <?php } ?>
            
            <div>
				<div class='quickstats first'>
				</div>
				<div class='quickstats second'>
					<div class='number'><?php echo $order_detail[0]->num_clicks; ?></div>
					<div class='attribute'><?php _e('Total Clicks', 'foxypress'); ?></div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats second'>
                    <div class='number'><?php echo $order_detail[0]->num_referrals; ?></div>
                    <div class='attribute'><?php _e('Total Referrals', 'foxypress'); ?></div>
                </div>
                <?php } ?>
				<div class='quickstats second'>
					<div class='number'><?php echo $order_detail[0]->num_total_orders; ?></div>
					<div class='attribute'><?php _e('Total Orders', 'foxypress'); ?></div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php if(!$order_detail[0]->total_paid_referral_amount) { echo '0.00'; } else { echo $order_detail[0]->total_paid_referral_amount; } ?></div>
                    <div class='attribute'><?php _e('Total Referral Paid Out', 'foxypress'); ?></div>
                </div>
                <?php } ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php if(!$order_detail[0]->total_paid_amount) { echo '0.00'; } else { echo $order_detail[0]->total_paid_amount; } ?></div>
                    <div class='attribute'><?php _e('Total Paid Out', 'foxypress'); ?></div>
                </div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php echo $referral_amount_due; ?></div>
                    <div class='attribute'><?php _e('Referral Amount Due', 'foxypress'); ?></div>
                </div>
                <?php } ?>
				<div class='quickstats third'>
					<div class='number'>$<?php echo $amount_due; ?></div>
					<div class='attribute'><?php _e('Amount Due', 'foxypress'); ?></div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats last'>
                    <?php if ($user_detail->$user_referral_payout_type == 1) { ?>
                        <div class='number'><?php echo $user_detail->$user_referral_payout; ?>% </div>
                    <?php } else { ?>
                        <div class='number'>$<?php echo $user_detail->$user_referral_payout; ?></div>
                    <?php } ?>
                    <div class='attribute'><?php _e('Commission', 'foxypress'); ?> <br />(<?php _e('per referral transaction', 'foxypress'); ?>)</div>
                </div>
                <?php } ?>
				<div class='quickstats last'>
					<?php if ($user_detail->$user_payout_type == 1) { ?>
                    	<div class='number'><?php echo $user_detail->$user_payout; ?>% </div>
                    <?php } else { ?>
                    	<div class='number'>$<?php echo $user_detail->$user_payout; ?></div>
                    <?php } ?>
                    <div class='attribute'><?php _e('Commission', 'foxypress'); ?> <br />(<?php _e('per transaction', 'foxypress'); ?>)</div>
				</div>
				<div class="clearall"></div>
			</div>
			<div class="clearall"></div>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p><a class="button bold" style="float:right;" href="http://www.foxy-press.com/getting-started/affiliate-management/" target="_blank"><?php _e('Affiliate Documentation', 'foxypress'); ?></a></p>
				<p>
					<b><?php _e('Affiliate Link', 'foxypress'); ?>: </b><?php echo $user_detail->$user_affiliate_url; ?>
				</p>
			</div>
            
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <ul class="subsubsub">
                    <li class="all"><a class="<?php if ($mode === 'view_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_details',$user_detail->ID); ?>"><?php _e('Open Orders', 'foxypress'); ?> <span class="count">(<?php echo $total_unpaid_orders; ?>)</span></a> |</li>
                    <li class="administrator"><a class="<?php if ($mode === 'view_past_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_past_details',$user_detail->ID); ?>"><?php _e('Paid Orders', 'foxypress'); ?> <span class="count">(<?php echo $order_detail[0]->num_paid_orders; ?>)</span></a></li>
                </ul>
                <!-- Now we can render the completed list table -->
                <?php $fp_affiliate->display() ?>
            </form>

        </div>

    <?php } else if ($mode === 'pay_affiliate') {

        add_action('admin_head',$fp_affiliate->pay_affiliate_head);

        $order_detail = $fp_affiliate->get_affiliate_order_detail();
        $order_id     = $fp_affiliate->foxypress_FixGetVar('order_id');
        $affiliate_id = $fp_affiliate->foxypress_FixGetVar('affiliate_id');
        
        if ($order_detail[0]->foxy_affiliate_id == $affiliate_id) {
            if ($user_detail->$user_payout_type == 1) {
                $affiliate_commission = $user_detail->$user_payout / 100 * $order_detail[0]->foxy_transaction_product_total;
                $affiliate_payout = $user_detail->$user_payout . '%';
            } else {
                $affiliate_commission = $user_detail->$user_payout;
                $affiliate_payout = '$' . number_format($user_detail->$user_payout, 2, '.', ',');
            }
            $affiliate_referral = FALSE;
            $commission_type = 1;
            $pay_affiliate_payout_type = $user_detail->$user_payout_type;
            $pay_affiliate_payout = $user_detail->$user_payout;
        } else {
            if ($user_detail->$user_referral_payout_type == 1) {
                $affiliate_commission = $user_detail->$user_referral_payout / 100 * $order_detail[0]->foxy_transaction_product_total;
                $affiliate_payout = $user_detail->$user_referral_payout . '%';
            } else {
                $affiliate_commission = $user_detail->$user_referral_payout;
                $affiliate_payout = '$' . number_format($user_detail->$user_referral_payout, 2, '.', ',');
            }
            $affiliate_referral = TRUE;
            $commission_type = 2;
            $pay_affiliate_payout_type = $user_detail->$user_referral_payout_type;
            $pay_affiliate_payout = $user_detail->$user_referral_payout;
        }
        $commission = number_format($affiliate_commission, 2, '.', ',');
        ?>
        <script type="text/javascript" src="<?php echo(plugins_url())?>/foxypress/js/jquery-ui-1.8.11.custom.min.js"></script>
        <script> 
            jQuery(function() {
                jQuery("#pay_affiliate_date").datepicker({ dateFormat: 'yy-mm-dd' });
            });
        </script>
        <div class="settings_widefat" id="">
        <?php if(isset($_POST['pay_affiliate_submit']))
        { ?>
            <div class="settings_head settings"><?php _e('Pay Order Commission', 'foxypress'); ?></div>      
            <div class="settings_inside">
                <?php
                $order_id                  = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_order_id');
                $order_total               = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_order_total');
                $affiliate_id              = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_id');
                $affiliate_payout_type     = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_payout_type');
                $affiliate_payout          = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_payout');
                $affiliate_commission      = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_commission');
                $affiliate_commission_type = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_commission_type');
                $payment_method            = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_method');
                $payment_date              = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_date');

                global $wpdb;
                $sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_payments (foxy_affiliate_id, foxy_transaction_id, foxy_transaction_order_total, foxy_affiliate_payout, foxy_affiliate_payout_type, foxy_affiliate_commission, foxy_affiliate_commission_type, foxy_affiliate_payment_method, foxy_affiliate_payment_date) values ('$affiliate_id', '$order_id', '$order_total', '$affiliate_payout', '$affiliate_payout_type', '$affiliate_commission', '$affiliate_commission_type', '$payment_method', '$payment_date')";
                $wpdb->query($sql);
                
                $destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s&updated=true',$_REQUEST['page'],'view_details',$affiliate_id);
                _e('Submitting Payment...', 'foxypress');
                echo '<script type="text/javascript">window.location.href = \'' . $destination_url . '\'</script>'; ?>
            </div>
        <?php } else { ?>
            			
			<div class="settings_head settings">
                Pay Order Commission
            </div>      
            <div class="settings_inside">
                <form id="pay_affiliate_form" name="pay_affiliate_form" method="POST">
                <input type="hidden" id="pay_affiliate_order_id" name="pay_affiliate_order_id" value="<?php echo $order_id; ?>">
                <input type="hidden" id="pay_affiliate_order_total" name="pay_affiliate_order_total" value="<?php echo $order_detail[0]->foxy_transaction_order_total; ?>">
                <input type="hidden" id="pay_affiliate_id" name="pay_affiliate_id" value="<?php echo $affiliate_id; ?>">
                <input type="hidden" id="pay_affiliate_payout_type" name="pay_affiliate_payout_type" value="<?php echo $pay_affiliate_payout_type; ?>">
                <input type="hidden" id="pay_affiliate_payout" name="pay_affiliate_payout" value="<?php echo $pay_affiliate_payout; ?>">
                <input type="hidden" id="pay_affiliate_commission" name="pay_affiliate_commission" value="<?php echo $commission; ?>">
                <input type="hidden" id="pay_affiliate_commission_type" name="pay_affiliate_commission_type" value="<?php echo $commission_type; ?>">
                <table>  
                    <tbody><tr>
                        <td valign="top" nowrap="" align="right" class="title"><strong><?php _e('Order ID', 'foxypress'); ?></strong></td>
                        <td align="left"><?php echo $order_id; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class="title"><strong><?php _e('Order Total', 'foxypress'); ?></strong></td>
                        <td align="left">$<?php echo $order_detail[0]->foxy_transaction_order_total; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong><?php _e('Affiliate', 'foxypress'); ?></strong></td>
                        <td align="left"><?php echo $user_detail->first_name . " " . $user_detail->last_name; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong><?php _e('Affiliate', 'foxypress'); ?><?php if ($affiliate_referral) { echo ' Referral';} ?> <?php _e('Payout', 'foxypress'); ?></strong></td>
                        <td align="left"><?php echo $affiliate_payout; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong><?php _e('Affiliate', 'foxypress'); ?><?php if ($affiliate_referral) { echo ' Referral';} ?> <?php _e('Commission', 'foxypress'); ?></strong></td>
                        <td align="left">$<?php echo $commission; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right"><strong><?php _e('Payment Method', 'foxypress'); ?></strong></td>
                        <td align="left">
                            <select id="pay_affiliate_method" name="pay_affiliate_method">
                                <option selected value=""><?php _e('Please Select', 'foxypress'); ?></option>
                                <option value="Paypal">Paypal</option>
                                <option value="Check"><?php _e('Check', 'foxypress'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong><?php _e('Payment Date', 'foxypress'); ?></strong></td>
                        <td align="left"><input type="text" size="50" value="" id="pay_affiliate_date" name="pay_affiliate_date"></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="submit" id="pay_affiliate_submit" name="pay_affiliate_submit" class="button bold" value="<?php _e('Submit Payment', 'foxypress'); ?>" /></td>
                    </tr>                   
                </tbody></table>
                </form>
            </div>
        <?php } ?>
        </div>
    <?php }    
}