<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Supports WC version 3.x.x


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
      
class Eboekhouden_Order_List_Table extends WP_List_Table {

    public $data;
    // RBS fix
    private $rbs_order;
    private $rbs_order_id;

    function __construct(){
        global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'order', 'eboekhoudenorderlisttable' ),     //singular name of the listed records
            'plural'    => __( 'orders', 'eboekhoudenorderlisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );

        if(isset($_SESSION['eboekhouder-notices']) && !isset($_POST['book']))
        {
            foreach($_SESSION['eboekhouder-notices'] as $item)
            {
                if($item['type'] == 'success')
                {
                    add_settings_error( 'e-boekhouden', '0', $item['message'], 'update' );
                }
                else if($item['type'] == 'error')
                {
                    add_settings_error( 'e-boekhouden', '0', $item['message'] );
                }
            }
            $_SESSION['eboekhouder-notices'] = null;
        }

        settings_errors() ;

        add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

    function set_data($data) {
        $this->data = $data;
    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

        if( 'my_list_test' != $page ) {
            return;
        }

        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 10%; }';
        echo '.wp-list-table .column-ordernr { width: 15%; }';
        echo '.wp-list-table .column-mutationnr { width: 15%; }';
        echo '.wp-list-table .column-orderinfo { width: 25%; }';
        echo '.wp-list-table .column-customerinfo { width: 25%;}';
        echo '</style>';
    }

    function no_items() {
        _e( 'No Orders Found' );
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            default:
                return false;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'ID'  => array('ID',false),
            'post_status'  => array('post_status',false),
            'post_date'  => array('post_date',false),
            'mutation_nr'  => array('mutation_nr',false)
        );
        return $sortable_columns;
    }

    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'ID'            => __( 'Order ID#', 'eboekhoudenorderlisttable' ),
            'post_date'     => __( 'Order Date', 'eboekhoudenorderlisttable' ),
            'post_status'   => __( 'Status', 'eboekhoudenorderlisttable' ),
            'mutation_nr'       => __( 'Mutatie', 'eboekhoudenorderlisttable' )
        );
        return $columns;
    }

    function usort_reorder( $a, $b ) {
        $a = (array) $a;
        $b = (array) $b;
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        $result = strcmp( $a[$orderby], $b[$orderby] );
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function get_bulk_actions() {
        $actions = array(
            'ebh_export'    => 'Export to e-Boekhouden.nl',
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="book[]" value="%s" />', $item->ID
        );
    }

    function column_ID($item) {

        
        
        $rbs_order = new WC_Order( $item->ID );
        
        $rbs_order_id  = $rbs_order->get_id();
        $order_date = $rbs_order->get_date_created();

        if (function_exists('wcpdf_get_invoice')) {
            
            $invoice = wcpdf_get_invoice( $rbs_order );
            $rbs_order_org = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );
            
            if ( $invoice_number = $invoice->get_number() ) {
                $rbs_order_number = $invoice_number->get_formatted();

            } else {
                $rbs_order_number = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );
            } 
            $rbs_order_number .= ' (' . $rbs_order_org . ')';
           
            //$rbs_order_number = get_post_meta($rbs_order_id,'_wcpdf_formatted_invoice_number',true);
            //$rbs_order_number = wcpdf_get_invoice($rbs_order)->get_invoice_number(); 
          
        } elseif (class_exists('WooCommerce_PDF_Invoices')) {
            
            $rbs_order_org = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );
            $wpo_wcpdf = new WooCommerce_PDF_Invoices();  
            $wpo_wcpdf->load_classes();
            $rbs_order_number = $wpo_wcpdf->export->get_invoice_number($rbs_order_id) . ' (' . $rbs_order_org . ')';
            
        } else {    
            $rbs_order_number = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );
        }
        //echo $rbs_order_number;
//        if (is_string($rbs_order_number) && strlen($rbs_order_number) == 0) {
//            $rbs_order_number = $rbs_order->get_order_number();
//        }        
      
        return '<a href="post.php?post=' . $item->ID . '&action=edit">' . $rbs_order_number . '</a>';
 
          
    }

    function column_post_date($item) {
        return $item->post_date;
    }

    function column_post_status($item) {
        return $item->post_status;
    }

    function column_mutation_nr($item) {
        return $item->mutation_nr;
    }

    function prepare_items() {
        $eb_order_status = (filter_input(INPUT_GET, 'tab')) ? filter_input(INPUT_GET, 'tab') : 'not_mutated';
        if ($eb_order_status == 'not_mutated') {
            $meta_query = array(
                'meta_key' => 'mutation_nr',
                'meta_compare' => 'NOT EXISTS'
                );
        } elseif ($eb_order_status == 'mutated') {
            $meta_query = array(
                'meta_key' => 'mutation_nr',
                'meta_compare' => 'EXISTS'
                );               
        } else {
            $meta_query = array();
        } 
        $columns  = $this->get_columns();
        
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page = 100;
        $current_page = $this->get_pagenum();

        if($current_page < 2) {
            $offset = 0;
        } else {
            $offset = $current_page * $per_page - $per_page;
        }
     
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';

        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';

        $oq_args = array(
            'post_type'     => 'shop_order',
            'post_status'   => array( 'wc-processing', 'wc-completed','wc-refunded' ),
            'posts_per_page'=> $per_page,
            'offset' => $offset,
            'orderby' => $orderby,
            'order' => $order            
        );
        $oq_args = array_merge($oq_args, $meta_query);
        $query_order = new WP_Query($oq_args);
        $orders = $query_order->posts;
        $total_items = $query_order->found_posts;
        $this->set_data($orders);
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );

        $this->items = $this->data;

    }

} //class