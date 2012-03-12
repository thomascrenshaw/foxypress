<?php
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-config.php');
require_once($root.'/wp-includes/wp-db.php');

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
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
            'view_details' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s">View Details</a>',$_REQUEST['page'],'view_details',$item->id)
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->user_nicename,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_management_clicks($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->num_clicks
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
                    (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_referral_amount,
                    (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_affiliate_commission_type = '2') AS total_paid_referral_amount,
                    (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_referrals WHERE foxy_affiliate_referred_by_id = " . $affiliate_id . ") AS num_referrals";

            $referral = $wpdb->get_results($sql);

            if ($referral[0]->affiliate_referral_payout_type == 1) {
                $unpaid_referral_commission = $referral[0]->affiliate_referral_payout / 100 * $referral[0]->total_unpaid_referral_amount;
            } else {
                $unpaid_referral_commission = $referral[0]->affiliate_referral_payout * $referral[0]->num_unpaid_referral_orders;
            }
            $total_referral_commission = $unpaid_referral_commission + $referral[0]->total_paid_referral_amount;
            $total_referral_commission = number_format($total_referral_commission, 2, '.', ',');
        }

        if ($item->affiliate_payout_type == 1) {
            $unpaid_commission = $item->affiliate_payout / 100 * $item->total_unpaid_amount;
        } else {
            $unpaid_commission = $item->affiliate_payout * $item->num_unpaid_orders;
        }
        $total_commission  = $unpaid_commission + $item->total_commission;
        $total_commission  = number_format($total_commission, 2, '.', ',');
            
                    
        return sprintf('$%1$s',
            /*$1%s*/ $total_commission + $total_referral_commission
        );
    }

    function column_management_total_transactions($item)
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

            $sql = "SELECT count(foxy_transaction_id) AS num_total_orders FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id IN (" . $commission_ids . "," . $affiliate_id . ")";
            $orders = $wpdb->get_results($sql);
        } else {
            $sql = "SELECT count(foxy_transaction_id) AS num_total_orders FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id = " . $affiliate_id;
            $orders = $wpdb->get_results($sql);
        }
        return sprintf('%1$s',
            /*$1%s*/ $orders[0]->num_total_orders
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
        return sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&approve=%s&affiliate_id=%s">Approve</a>',$_REQUEST['page'],'pending_affiliates','true',$item->id);
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
            'pay_affiliate' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s&order_id=%s">Pay Affiliate</a>',$_REQUEST['page'],'pay_affiliate',$affiliate_id,$item->order_id),
            'view_order' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=order-management&transaction=%sb=0&mode=detail">View Order Detail</a>',$item->order_id)
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
                $order_commission = $item->affiliate_payout / 100 * $item->order_total;
                $order_commission = number_format($order_commission, 2, '.', ',');
            } else {
                $order_commission  = number_format($item->affiliate_payout, 2, '.', ',');
            }
        } else {
            if ($item->affiliate_referral_payout_type == 1) {
                $order_commission = $item->affiliate_referral_payout / 100 * $item->order_total;
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
                'management_affiliate'          => 'Affiliate',
                'management_clicks'             => 'Clicks',
                'management_total_commission'   => 'Total Commission',
                'management_total_transactions' => 'Total Transactions'
            );
        }
        else if ($mode === 'pending_affiliates')
        {
            $columns = array(
                'pending_affiliates_first_name'  => 'First Name',
                'pending_affiliates_last_name'   => 'Last Name',
                'pending_affiliates_age'         => 'Age',
                'pending_affiliates_gender'      => 'Gender',
                'pending_affiliates_description' => 'Message',
                'pending_affiliates_approve'     => 'Approve'
            );
        }
        else if ($mode === 'view_details')
        {
            $columns = array(
                //'cb'                          => '<input type="checkbox" />',
                'view_details_order_id'         => 'Order ID',
                'view_details_order_total'      => 'Order Total',
                'view_details_order_commission' => 'Affiliate Commission',
                'view_details_order_date'       => 'Order Date',
                'view_details_order_type'       => 'Order Type'
            );
        }
        else if ($mode === 'view_past_details')
        {
            $columns = array(
                'view_past_details_order_id'             => 'Order ID',
                'view_past_details_order_total'          => 'Order Total',
                'view_past_details_affiliate_payout'     => 'Affiliate Payout',
                'view_past_details_order_commission'     => 'Affiliate Commission',
                'view_past_details_payment_method'       => 'Payment Method',
                'view_past_details_payment_date'         => 'Payment Date',
                'view_past_details_order_type'           => 'Order Type'
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
                'management_clicks'             => array('management_clicks',false),
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
        $per_page = 10;
        
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
            else if ($order_by === 'management_clicks')
            {
                $sort_by = 'num_clicks ' . $sort_order;
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

            $sql_data = "SELECT u.id, u.user_nicename,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout_type' AND user_id = u.id) AS affiliate_payout_type,
                        (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = '" . $wpdb->prefix . "affiliate_payout' AND user_id = u.id) AS affiliate_payout,
                        (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = u.id) AS num_clicks,
                        (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = u.id AND foxy_affiliate_commission_type = '1') AS total_commission,
                        (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = u.id AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS num_unpaid_orders,
                        (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = u.id AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_amount
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

            $sql_data = "SELECT ft.foxy_transaction_id AS order_id, ft.foxy_transaction_order_total AS order_total, ft.foxy_transaction_date AS order_date, u.id, u.user_nicename,
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

    function get_affiliate_order_details()
    {
        global $wpdb;
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');

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
                    (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS total_unpaid_amount,
                    (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '2')) AS num_unpaid_referral_orders,
                    (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id IN (" . $commission_ids . ") AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_referral_amount,
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
                (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id AND " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_affiliate_commission_type = '1')) AS total_unpaid_amount,
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

    //Create an instance of our package class...
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
        $fp_affiliate->prepare_items($mode, $order_by, $order); 
        $affiliate_counts = $fp_affiliate->get_affiliate_counts(); ?>

        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2>FoxyPress Affiliates</h2>

            <?php $updated = $fp_affiliate->foxypress_FixGetVar('updated');
            if ($updated === 'true') { 

                if ($user_detail->$user_payout_type == 1) {
                    $affiliate_commission = 'Affiliate Commission: ' . $user_detail->$user_payout . '%';
                } else {
                    $affiliate_commission = 'Affiliate Commission: $' . $user_detail->$user_payout;
                }
                $mail_to = $user_detail->user_email;
                $mail_subject = 'Affiliate status approved!';
                $mail_body    = 'You have been approved to be an affiliate. Your affiliate details are below.<br /><br />' . $affiliate_commission . '<br />Affiliate URL: ' . $user_detail->$user_affiliate_url;

                foxypress_Mail($mail_to, $mail_subject, $mail_body); ?>

                <div class="updated" id="message">
                    <p><strong>Affiliate Approved!</strong></p>
                </div>

            <?php } ?>
            
            <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
                <?php if ($mode === 'management') { ?>
                    <p>Listed below you will find your affiliate's current stats.</p>
                <?php } else { ?>
                    <p>Listed below you will find your pending affiliates.</p>
                <?php } ?>
            </div>
            
            <?php $approve = $fp_affiliate->foxypress_FixGetVar('approve');
                if ($approve === 'true') {
                
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
                                <p><strong>Approving Affiliate...</strong></p>
                            </div>

                            <script type="text/javascript">window.location.href ='<?php echo $destination_url; ?>';</script>
        
                        <?php } else { ?>
                            <div class="error" id="message">
                                <?php if ($payout_amount_error) { ?>
                                <p><strong>You must enter a payout.</strong></p>
                                <?php } ?>

                                <?php if ($payout_type_error) { ?>
                                <p><strong>You must select a payout type.</strong></p>
                                <?php } ?>
                            </div>
                       <?php }
                    } ?>

                    <form id="affiliate_approve_form" name="affiliate_approve_form" method="POST">
                    <table class="form-table">
                        <tr>
                            <th><label for="affiliate_payout_type">Affiliate Payout Type</label></th>
                            <td>
                                <input type="radio" <?php if ($payout_type == 'percentage') { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="percentage">
                                <span class="description">Percentage of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($payout_type == 'dollars') { ?>checked="yes" <?php } ?>name="affiliate_payout_type" id="affiliate_payout_type" value="dollars">
                                <span class="description">Dollar amount of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout">Affiliate Payout <span class="description">(required)</span></label></th>
                            <td><input type="text" name="affiliate_payout" id="affiliate_payout" value="<?php echo $payout; ?>"> 
                            <span class="description">How much will this affiliate earn per sale? <b>(Enter 30 for 30% or $30.00)</b></span></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_referral">Enable Affiliate Referrals</label></th>
                            <td><input type="checkbox" <?php if ($referral == 'true') { ?>checked="yes" <?php } ?>name="affiliate_referral" id="affiliate_referral" value="true" /> Does this user's link allow for affiliate referrals?</td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout_type">Affiliate Referral Payout Type</label></th>
                            <td>
                                <input type="radio" <?php if ($referral_payout_type == 'percentage') { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="percentage">
                                <span class="description">Percentage of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($referral_payout_type == 'dollars') { ?>checked="yes" <?php } ?>name="affiliate_referral_payout_type" id="affiliate_referral_payout_type" value="dollars">
                                <span class="description">Dollar amount of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_referral_payout">Affiliate Referral Payout</label></th>
                            <td><input type="text" name="affiliate_referral_payout" id="affiliate_referral_payout" value="<?php echo $referral_payout; ?>"> 
                            <span class="description">How much will this affiliate earn per referral sale? <b>(Enter 30 for 30% or $30.00)</b></span></td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_discount">Enable Affiliate Discount</label></th>
                            <td><input type="checkbox" <?php if ($discount == 'true') { ?>checked="yes" <?php } ?>name="affiliate_discount" id="affiliate_discount" value="true" /> Does this user's link allow for an additional discount?</td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_payout_type">Affiliate Discount Type</label></th>
                            <td>
                                <input type="radio" <?php if ($discount_type == 1) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="percentage">
                                <span class="description">Percentage off of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <input type="radio" <?php if ($discount_type == 2) { ?>checked="yes" <?php } ?>name="affiliate_discount_type" id="affiliate_discount_type" value="dollars">
                                <span class="description">Dollar amount off of each order.</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affiliate_discount_amount">Affiliate Discount Amount</label></th>
                            <td>
                                <input type="text" name="affiliate_discount_amount" id="affiliate_discount_amount" value="<?php echo $discount_amount; ?>">
                                <span class="description">How much of a discount will user's receive? <b>(Enter 30 for 30% or $30.00)</b></span>
                            </td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="Approve" class="button-primary" id="affiliate_approve_submit" name="affiliate_approve_submit"></p>
                    </form>

            <?php } ?>
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <ul class="subsubsub">
                    <li class="all"><a class="<?php if ($mode === 'management') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s',$_REQUEST['page'],'management'); ?>">Approved Affiliates <span class="count">(<?php echo $affiliate_counts[0]->total_approved; ?>)</span></a> |</li>
                    <li class="administrator"><a class="<?php if ($mode === 'pending_affiliates') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s',$_REQUEST['page'],'pending_affiliates'); ?>">Pending Affiliates <span class="count">(<?php echo $affiliate_counts[0]->total_pending; ?>)</span></a></li>
                </ul>
                <!-- Now we can render the completed list table -->
                <?php $fp_affiliate->display() ?>
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
            <h2><?php if (!$user_detail->first_name && !$user_detail->last_name) { echo $user_detail->user_nicename; } else { echo $user_detail->first_name . " " . $user_detail->last_name; } ?> :: Affiliate Detail <a class="add-new-h2" href="<?php echo get_admin_url(); ?>user-edit.php?user_id=<?php echo $user_detail->ID; ?>">Edit User</a></h2>

            <?php $updated = $fp_affiliate->foxypress_FixGetVar('updated');
            if ($updated === 'true') { ?>
            <div class="updated" id="message">
                <p><strong>Payment Submitted Successfully</strong></p>
            </div>
            <?php } ?>
            
            <div>
				<div class='quickstats first'>
				</div>
				<div class='quickstats second'>
					<div class='number'><?php echo $order_detail[0]->num_clicks; ?></div>
					<div class='attribute'>Total Clicks</div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats second'>
                    <div class='number'><?php echo $order_detail[0]->num_referrals; ?></div>
                    <div class='attribute'>Total Referrals</div>
                </div>
                <?php } ?>
				<div class='quickstats second'>
					<div class='number'><?php echo $order_detail[0]->num_total_orders; ?></div>
					<div class='attribute'>Total Orders</div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php if(!$order_detail[0]->total_paid_referral_amount) { echo '0.00'; } else { echo $order_detail[0]->total_paid_referral_amount; } ?></div>
                    <div class='attribute'>Total Referral Paid Out</div>
                </div>
                <?php } ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php if(!$order_detail[0]->total_paid_amount) { echo '0.00'; } else { echo $order_detail[0]->total_paid_amount; } ?></div>
                    <div class='attribute'>Total Paid Out</div>
                </div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats third'>
                    <div class='number'>$<?php echo $referral_amount_due; ?></div>
                    <div class='attribute'>Referral Amount Due</div>
                </div>
                <?php } ?>
				<div class='quickstats third'>
					<div class='number'>$<?php echo $amount_due; ?></div>
					<div class='attribute'>Amount Due</div>
				</div>
                <?php if ($user_detail->$user_referral == 'true') { ?>
                <div class='quickstats last'>
                    <?php if ($user_detail->$user_referral_payout_type == 1) { ?>
                        <div class='number'><?php echo $user_detail->$user_referral_payout; ?>% </div>
                    <?php } else { ?>
                        <div class='number'>$<?php echo $user_detail->$user_referral_payout; ?></div>
                    <?php } ?>
                    <div class='attribute'>Commission <br />(per referral transaction)</div>
                </div>
                <?php } ?>
				<div class='quickstats last'>
					<?php if ($user_detail->$user_payout_type == 1) { ?>
                    	<div class='number'><?php echo $user_detail->$user_payout; ?>% </div>
                    <?php } else { ?>
                    	<div class='number'>$<?php echo $user_detail->$user_payout; ?></div>
                    <?php } ?>
                    <div class='attribute'>Commission <br />(per transaction)</div>
				</div>
				<div class="clearall"></div>
			</div>
			<div class="clearall"></div>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p><a class="button bold" style="float:right;" href="http://www.foxy-press.com/getting-started/affiliate-management/" target="_blank">Affiliate Documentation</a></p>
				<p>
					<b>Affiliate Link: </b><?php echo $user_detail->$user_affiliate_url; ?>
				</p>
			</div>
            
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <ul class="subsubsub">
                    <li class="all"><a class="<?php if ($mode === 'view_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_details',$user_detail->ID); ?>">Open Orders <span class="count">(<?php echo $total_unpaid_orders; ?>)</span></a> |</li>
                    <li class="administrator"><a class="<?php if ($mode === 'view_past_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_past_details',$user_detail->ID); ?>">Paid Orders <span class="count">(<?php echo $order_detail[0]->num_paid_orders; ?>)</span></a></li>
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
                $affiliate_commission = $user_detail->$user_payout / 100 * $order_detail[0]->foxy_transaction_order_total;
                $affiliate_payout = $user_detail->$user_payout . '%';
            } else {
                $affiliate_commission = $user_detail->$user_payout;
                $affiliate_payout = '$' . $user_detail->$user_payout;
            }
            $affiliate_referral = FALSE;
            $commission_type = 1;
            $pay_affiliate_payout_type = $user_detail->$user_payout_type;
            $pay_affiliate_payout = $user_detail->$user_payout;
        } else {
            if ($user_detail->$user_referral_payout_type == 1) {
                $affiliate_commission = $user_detail->$user_referral_payout / 100 * $order_detail[0]->foxy_transaction_order_total;
                $affiliate_payout = $user_detail->$user_referral_payout . '%';
            } else {
                $affiliate_commission = $user_detail->$user_referral_payout;
                $affiliate_payout = '$' . $user_detail->$user_referral_payout;
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
            <div class="settings_head settings">Pay Order Commission</div>      
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
                echo 'Submitting Payment...';
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
                        <td valign="top" nowrap="" align="right" class="title"><strong>Order ID</strong></td>
                        <td align="left"><?php echo $order_id; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class="title"><strong>Order Total</strong></td>
                        <td align="left">$<?php echo $order_detail[0]->foxy_transaction_order_total; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Affiliate</strong></td>
                        <td align="left"><?php echo $user_detail->first_name . " " . $user_detail->last_name; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Affiliate<?php if ($affiliate_referral) { echo ' Referral';} ?> Payout</strong></td>
                        <td align="left"><?php echo $affiliate_payout; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Affiliate<?php if ($affiliate_referral) { echo ' Referral';} ?> Commission</strong></td>
                        <td align="left">$<?php echo $commission; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right"><strong>Payment Method</strong></td>
                        <td align="left">
                            <select id="pay_affiliate_method" name="pay_affiliate_method">
                                <option selected value="">Please Select</option>
                                <option value="Paypal">Paypal</option>
                                <option value="Check">Check</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Payment Date</strong></td>
                        <td align="left"><input type="text" size="50" value="" id="pay_affiliate_date" name="pay_affiliate_date"></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="submit" id="pay_affiliate_submit" name="pay_affiliate_submit" class="button bold" value="Submit Payment" /></td>
                    </tr>                   
                </tbody></table>
                </form>
            </div>
        <?php } ?>
        </div>
    <?php }
    
}