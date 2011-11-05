<?php
$root = dirname(dirname(dirname(dirname(__FILE__))));
load_plugin_textdomain( 'foxypress','wp-content/plugins/'.$root, $root);
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
        $order_commission = $item->affiliate_percentage / 100 * $item->total_commission;
        $order_commission = number_format($order_commission, 2, '.', ',');
        
        return sprintf('$%1$s',
            /*$1%s*/ $order_commission
        );
    }

    function column_management_total_transactions($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->num_total_orders
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
        //Build row actions
        $actions = array(
            'pay_affiliate' => sprintf('<a href="?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s&order_id=%s">Pay Affiliate</a>',$_REQUEST['page'],'pay_affiliate',$item->id,$item->order_id),
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
        $order_commission = $item->affiliate_percentage / 100 * $item->order_total;
        $order_commission = number_format($order_commission, 2, '.', ',');

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

    function column_view_past_details_affiliate_percentage($item)
    {
        return sprintf('%1$s',
            /*$1%s*/ $item->foxy_affiliate_percentage . '%'
        );
    }
    
    function column_view_past_details_order_commission($item)
    {
        $order_commission = $item->foxy_affiliate_percentage / 100 * $item->foxy_transaction_order_total;
        $order_commission = number_format($order_commission, 2, '.', ',');

        return sprintf('$%1$s',
            /*$1%s*/ $order_commission
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
                'view_details_order_date'       => 'Order Date'
            );
        }
        else if ($mode === 'view_past_details')
        {
            $columns = array(
                'view_past_details_order_id'             => 'Order ID',
                'view_past_details_order_total'          => 'Order Total',
                'view_past_details_affiliate_percentage' => 'Affiliate Percentage',
                'view_past_details_order_commission'     => 'Affiliate Commission',
                'view_past_details_payment_method'       => 'Payment Method',
                'view_past_details_payment_date'         => 'Payment Date'
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
                'view_past_details_affiliate_percentage' => array('view_past_details_affiliate_percentage', false),
                'view_past_details_order_commission'     => array('view_past_details_order_commission', false),
                'view_past_details_payment_method'       => array('view_past_details_payment_method', false),
                'view_past_details_payment_date'         => array('view_past_details_payment_date', false)
            );
        }
        
        return $sortable_columns;
    }
    
    function get_bulk_actions() 
    {
        $mode = $this->foxypress_FixGetVar('mode');

        if ($mode === 'management')
        {
            $actions = array(
                
            );
        }
        else if ($mode === 'view_details')
        {
            $actions = array(
                
            );
        }

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
        $sql = "SELECT user_id FROM `wp_usermeta`
                WHERE meta_key = 'affiliate_user' AND meta_value = 'true'";

        $affiliate_ids = $wpdb->get_results($sql);
        
        $i = 0;
        foreach ($affiliate_ids as $affiliate)
        {
            $ids[$i] = $affiliate->user_id;
            $i++;
        }

        $ids = implode(',', $ids);

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

            $sql_data = "SELECT u.id, u.user_nicename, um.meta_key, um.meta_value AS affiliate_percentage,
                        (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = u.id) AS num_clicks,
                        (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id = u.id) AS num_total_orders,
                        (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id = u.id) AS total_commission
                        FROM " . $wpdb->prefix . "users AS u
                        LEFT JOIN " . $wpdb->prefix . "usermeta AS um ON um.meta_key = 'affiliate_percentage' AND um.user_id = u.id
                        WHERE u.id in (" . $ids . ")
                        ORDER BY " . $sort_by;
        }
        else if ($mode === 'pending_affiliates')
        {
            $sql = "SELECT user_id FROM `wp_usermeta`
                WHERE meta_key = 'affiliate_user' AND meta_value = 'pending'";

            $pending_affiliate_ids = $wpdb->get_results($sql);
            
            $i = 0;
            foreach ($pending_affiliate_ids as $pending_affiliate)
            {
                $pending_ids[$i] = $pending_affiliate->user_id;
                $i++;
            }

            $pending_ids = implode(',', $pending_ids);

            $sql_data = "SELECT u.id, u.user_nicename,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.id) AS first_name,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.id) AS last_name,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_facebook_page' AND user_id = u.id) AS facebook_page,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_gender' AND user_id = u.id) AS gender,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_age' AND user_id = u.id) AS age,
                        (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'description' AND user_id = u.id) AS description
                        FROM " . $wpdb->prefix . "users AS u
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

            $sql_data = "SELECT ft.foxy_transaction_id AS order_id, ft.foxy_transaction_order_total AS order_total, ft.foxy_transaction_date AS order_date, u.id, u.user_nicename, um.meta_key, um.meta_value AS affiliate_percentage
                        FROM " . $wpdb->prefix . "foxypress_transaction AS ft
                        LEFT JOIN " . $wpdb->prefix . "users AS u ON u.id = ft.foxy_affiliate_id
                        LEFT JOIN " . $wpdb->prefix . "usermeta AS um ON um.meta_key = 'affiliate_percentage' AND um.user_id = ft.foxy_affiliate_id
                        WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)
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
            else if ($order_by === 'view_past_details_affiliate_percentage')
            {
                $sort_by = 'foxy_affiliate_percentage ' . $sort_order;
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
        
        $data = "SELECT 
                (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_tracking WHERE affiliate_id = " . $affiliate_id . ") AS num_clicks,
                (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction WHERE foxy_affiliate_id = " . $affiliate_id . ") AS num_total_orders,
                (SELECT count(id) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . ") AS num_paid_orders,
                (SELECT count(foxy_transaction_id) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS num_unpaid_orders,
                (SELECT sum(foxy_transaction_order_total) FROM " . $wpdb->prefix . "foxypress_transaction AS ft WHERE ft.foxy_affiliate_id = " . $affiliate_id . " AND NOT EXISTS (SELECT foxy_transaction_id FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE " . $wpdb->prefix . "foxypress_affiliate_payments.foxy_transaction_id = ft.foxy_transaction_id)) AS total_unpaid_amount,
                (SELECT sum(foxy_affiliate_commission) FROM " . $wpdb->prefix . "foxypress_affiliate_payments WHERE foxy_affiliate_id = " . $affiliate_id . ") AS total_paid_amount";

        return $wpdb->get_results($data);
    }

    function get_affiliate_user_details()
    {
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');

        $data = (object) array(
            'firstname'  => get_user_meta($affiliate_id, 'first_name', true),
            'lastname'   => get_user_meta($affiliate_id, 'last_name', true),
            'percentage' => get_user_meta($affiliate_id, 'affiliate_percentage', true)
            );

        return $data;
    }

    function get_affiliate_order_detail()
    {
        global $wpdb;
        $affiliate_id = $this->foxypress_FixGetVar('affiliate_id');
        $order_id     = $this->foxypress_FixGetVar('order_id');

        $data = "SELECT *
                FROM " . $wpdb->prefix . "foxypress_transaction
                WHERE foxy_affiliate_id = " . $affiliate_id . " AND foxy_transaction_id = " . $order_id;

        return $wpdb->get_results($data);
    }

    function get_affiliate_counts()
    {
        global $wpdb;
        
        $data = "SELECT 
                (SELECT count(user_id) FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_user' AND meta_value = 'pending') AS total_pending,
                (SELECT count(user_id) FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'affiliate_user' AND meta_value = 'true') AS total_approved";

        return $wpdb->get_results($data);
    }
}

function foxypress_create_affiliate_table() {

    //Create an instance of our package class...
    $fp_affiliate = new Foxypress_affiliate_management();
    $mode         = $fp_affiliate->foxypress_FixGetVar('mode');
    $order_by     = $fp_affiliate->foxypress_FixGetVar('orderby');
    $order        = $fp_affiliate->foxypress_FixGetVar('order');

    if ($mode === 'management' || $mode === 'pending_affiliates'){ 

        //Fetch, prepare, sort, and filter our data...
        $fp_affiliate->prepare_items($mode, $order_by, $order); 
        $affiliate_counts = $fp_affiliate->get_affiliate_counts(); ?>

        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2>FoxyPress Affiliates</h2>

            <?php $updated = $fp_affiliate->foxypress_FixGetVar('updated');
            if ($updated === 'true') { 
                
                $user_id = $fp_affiliate->foxypress_FixGetVar('affiliate_id');

                $percentage = get_the_author_meta('affiliate_percentage', $user_id);
                $affiliate_url = get_the_author_meta('affiliate_url', $user_id);
                $mail_to = get_the_author_meta('user_email', $user_id);
                $mail_subject = 'Affiliate status approved!';
                $mail_body    = 'You have been approved to be an affiliate. Your affiliate details are below.<br /><br />Affiliate Commission: ' . $percentage . '%<br />Affiliate URL: ' . $affiliate_url;

                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
                $headers .= 'From: <' . get_settings("admin_email ") . '>' . "\r\n";
                foxypress_Mail($mail_to,$mail_subject,$mail_body); ?>

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
                        $percentage = $fp_affiliate->foxypress_FixPostVar('affiliate_percentage');
                        $error      = false;

                        if (empty($percentage)) {
                            $error = true;
                            $percentage_error = true;
                        }

                        if (!$error) {
                            update_usermeta($user_id, 'affiliate_percentage', $percentage);
                            update_usermeta($user_id, 'affiliate_user', 'true');

                            $affiliate_url = plugins_url() . '/foxypress/foxypress-affiliate.php?aff_id=' . $user_id;
                            update_usermeta($user_id, 'affiliate_url', $affiliate_url);                         

                            $destination_url = get_admin_url() . sprintf('edit.php?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&updated=%s&affiliate_id=%s',$_REQUEST['page'],'pending_affiliates','true',$user_id); ?>

                            <div class="updated" id="message">
                                <p><strong>Approving Affiliate...</strong></p>
                            </div>

                            <script type="text/javascript">window.location.href ='<?php echo $destination_url; ?>';</script>
        
                        <?php } else { ?>
                            <div class="error" id="message">
                                <p><strong>You must enter a percentage value!</strong></p>
                            </div>
                       <?php }
                    } ?>

                    <form id="affiliate_approve_form" name="affiliate_approve_form" method="POST">
                    <table class="form-table">
                        <tr class="">
                            <th><label for="affiliate_first_name">Percentage <span class="description">(required)</span></label></th>
                            <td><input type="text" name="affiliate_percentage" id="affiliate_percentage" value=""> 
                            <span class="description">How much will this affiliate earn per sale? <b>(Enter 30 for 30%)</b></span></td>
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
    
        //Fetch, prepare, sort, and filter our data...
        $fp_affiliate->prepare_items($mode, $order_by, $order); ?>
        
        <?php
            $affiliate_id = $fp_affiliate->foxypress_FixGetVar('affiliate_id');
            $user_detail  = $fp_affiliate->get_affiliate_user_details();
            $order_detail = $fp_affiliate->get_affiliate_order_details();

            $amount_due = $user_detail->percentage / 100 * $order_detail[0]->total_unpaid_amount;
            $amount_due = number_format($amount_due, 2, '.', ',');
        ?>    

        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php echo $user_detail->firstname . " " . $user_detail->lastname; ?> :: Affiliate Detail <a class="add-new-h2" href="<?php echo get_admin_url(); ?>user-edit.php?user_id=<?php echo $affiliate_id; ?>">Edit User</a></h2>

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
				<div class='quickstats second'>
					<div class='number'><?php echo $order_detail[0]->num_total_orders; ?></div>
					<div class='attribute'>Total Orders</div>
				</div>
				<div class='quickstats third'>
					<div class='number'>$<?php echo $order_detail[0]->total_paid_amount; ?></div>
					<div class='attribute'>Total Paid Out</div>
				</div>
				<div class='quickstats third'>
					<div class='number'>$<?php echo $amount_due; ?></div>
					<div class='attribute'>Amount Due</div>
				</div>
				<div class='quickstats last'>
					<div class='number'><?php echo $user_detail->percentage; ?>%</div>
					<div class='attribute'>Commission</div>
				</div>
				<div class="clearall"></div>
            </div>
			<div class="clearall"></div>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
					<a style="text-decoration:none;" href="<?php echo get_admin_url(); ?>user-edit.php?user_id=<?php echo $affiliate_id; ?>">Edit User</a>
                </p>
			</div>
            
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="affiliate-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <ul class="subsubsub">
                    <li class="all"><a class="<?php if ($mode === 'view_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_details',$affiliate_id); ?>">Open Orders <span class="count">(<?php echo $order_detail[0]->num_unpaid_orders; ?>)</span></a> |</li>
                    <li class="administrator"><a class="<?php if ($mode === 'view_past_details') { ?>current<?php } ?>" href="<?php echo sprintf('?post_type=' . FOXYPRESS_CUSTOM_POST_TYPE . '&page=%s&mode=%s&affiliate_id=%s',$_REQUEST['page'],'view_past_details',$affiliate_id); ?>">Paid Orders <span class="count">(<?php echo $order_detail[0]->num_paid_orders; ?>)</span></a></li>
                </ul>
                <!-- Now we can render the completed list table -->
                <?php $fp_affiliate->display() ?>
            </form>

        </div>

    <?php } else if ($mode === 'pay_affiliate') {

        add_action('admin_head',$fp_affiliate->pay_affiliate_head);

        $user_detail  = $fp_affiliate->get_affiliate_user_details();
        $order_detail = $fp_affiliate->get_affiliate_order_detail();
        $order_id     = $fp_affiliate->foxypress_FixGetVar('order_id');
        $affiliate_id = $fp_affiliate->foxypress_FixGetVar('affiliate_id');

        $affiliate_commission = $user_detail->percentage / 100 * $order_detail[0]->foxy_transaction_order_total;
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
                $order_id             = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_order_id');
                $order_total          = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_order_total');
                $affiliate_id         = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_id');
                $affiliate_percentage = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_percentage');
                $affiliate_commission = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_commission');
                $payment_method       = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_method');
                $payment_date         = $fp_affiliate->foxypress_FixPostVar('pay_affiliate_date');

                global $wpdb;
                $sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_payments (foxy_affiliate_id, foxy_transaction_id, foxy_transaction_order_total, foxy_affiliate_percentage, foxy_affiliate_commission, foxy_affiliate_payment_method, foxy_affiliate_payment_date) values ('$affiliate_id', '$order_id', '$order_total', '$affiliate_percentage', '$affiliate_commission', '$payment_method', '$payment_date')";
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
                <input type="hidden" id="pay_affiliate_percentage" name="pay_affiliate_percentage" value="<?php echo $user_detail->percentage; ?>">
                <input type="hidden" id="pay_affiliate_commission" name="pay_affiliate_commission" value="<?php echo $commission; ?>">
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
                        <td align="left"><?php echo $user_detail->firstname . " " . $user_detail->lastname; ?></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Affiliate Percentage</strong></td>
                        <td align="left"><?php echo $user_detail->percentage; ?>%</td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="" align="right" class=="title"><strong>Affiliate Commission</strong></td>
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