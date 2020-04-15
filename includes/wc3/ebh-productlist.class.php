<?php
// Supports WC version 3.x.x
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * @todo:
 * - [ ] prepare_items():
 *      - [ ] Tax / meta query for 'product_attribute: large_numer'
 *      >> 
 */


if (!class_exists('Eboekhouden_Productlist')) {

    class Eboekhouden_Productlist extends WP_List_Table {

        public $data;
        
        
        private $Eboekhouden_Settings;
        private $Eboekhouden_Largenumbers;
        private $Eboekhouden_Session;
        
        
        private $max_products_per_page;
        
        

        function __construct($prepare_items = true){
            //global $status, $page;
            $parent_args = array(
                'singular' => __('product', 'eboekhouden'),                    //singular name of the listed records
                'plural' => __('products', 'eboekhouden' ),                    //plural name of the listed records
                'ajax' => false                                                 //does this table support ajax?
            ) ;

            parent::__construct($parent_args);
            $this->init();
            
            if ($prepare_items) {
                $this->prepare_items();
            }            
        }
        
        
        private function init() {
            $this->Eboekhouden_Settings = new Eboekhouden_Settings();
            $this->Eboekhouden_Largenumbers = new Eboekhouden_Largenumbers();
            $this->Eboekhouden_Session = new Eboekhouden_Session();
            
            $this->max_products_per_page = $this->Eboekhouden_Settings->ebhGetOption('ebh_product_list_max');
              
        }
        

        public function set_data($data) {
            $this->data = $data;
        }

        


        public function no_items() {
            _e('No products Found', 'eboekhouden');
        }

        public function column_default( $item, $column_name ) {
            switch( $column_name ) {
                default:
                    return false;
            }
        }

        public function get_sortable_columns() {
            $sortable_columns = array(
                'ID'  => array('ID',false)
            );
            return $sortable_columns;
        }

        public function get_columns(){
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'sku' => __( 'Product SKU', 'eboekhouden'),
                'large_number' => __('Large Number', 'eboekhouden'),
                'product_name' => __('Product name', 'eboekhouden')
            );
            return $columns;
        }

        public function usort_reorder( $a, $b ) {
            $a = (array) $a;
            $b = (array) $b;
            $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';
            $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
            $result = strcmp( $a[$orderby], $b[$orderby] );
            return ( $order === 'asc' ) ? $result : -$result;
        }

        public function get_bulk_actions() {
            $actions = array(
                'ebhassignlargenumber' => __('Mass-assign Large Number', 'eboekhouden'),
            );
            return $actions;
        }

        public function column_cb($item) {
            return sprintf(
                '<input type="checkbox" name="ebh_products[]" value="%s" />', $item['ID']
            );
        }

        public function column_ID($item) {
            return $item['ID'];
        }

        public function column_sku($item) {
            if(strlen($item['sku'])) {
                return '<a href="post.php?post=' . $item['ID'] . '&action=edit">' . $item['sku'] . '</a>';
            } else {
                return '<a href="post.php?post=' . $item['ID'] . '&action=edit">--</a>';
            }
        }

        public function column_large_number($item) {
            if(strlen($item['large_number'])) {
                return $item['large_number'];
            } else {
                return '--';
            }
        }

        public function column_product_name($item) {
            return '<a href="post.php?post=' . $item['ID'] . '&action=edit">' . $item['product_name'] . '</a>';
        }

        
        public function prepare_items() {
            $ebh_product_status = (filter_input(INPUT_GET, 'tab')) ? filter_input(INPUT_GET, 'tab') : 'all';
//            if ($ebh_product_status == 'no_largenumber') {
//                $meta_query = array(
//                    'meta_key' => 'pa_large_number',
//                    'meta_compare' => 'NOT EXISTS'
//                    );
//            } elseif ($ebh_product_status == 'has_largenumber') {
//                $meta_query = array(
//                    'meta_key' => 'pa_large_number',
//                    'meta_compare' => 'EXISTS'
//                    );               
//            } else {
//                $meta_query = array();
//            }             
            
//            $tax_query = array(
//                'taxonomy' =>  'pa_large_number',
//                'field' => 'name',
//                'terms' => 9999,
//                'operator' => 'IN'
//            );
            
            $this->process_bulk_action();

            $columns  = $this->get_columns();
            $hidden   = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable );

            $per_page = $this->max_products_per_page;
            $current_page = $this->get_pagenum();

            if($current_page < 2) {
                $offset = 0;
            } else {
                $offset = $current_page * $per_page - $per_page;
            }

            $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';
            $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';

            /* Check: 'gives memory exceed' when large number of products */
//            $total_products = get_posts( array(
//                'post_type'     => 'product',
//                'nopaging'      => true
//            ));



            $args = array(
                'post_type'     => 'product',
                'post_status'	=> 'publish',
                'posts_per_page'=> $per_page,
                'offset' => $offset,
                'orderby' => $orderby,
                'order' => $order,
                //'tax_query' => array($tax_query)
                    
            );
            if (isset($_POST['s'])) {
                $args['s'] = $_POST['s'];
            }

            $products = array();
            $tmpProducts = new WP_Query( $args );
            
            if (count($tmpProducts->posts) != 0) {
                foreach ($tmpProducts->posts as $post_item) {
                    $product_item = wc_get_product($post_item->ID);
                    
                    if ($ebh_product_status == 'no_largenumber' && $product_item->get_attribute('large_number')) {
                        continue;
                    } elseif ($ebh_product_status == 'has_largenumber' && !$product_item->get_attribute('large_number')) {
                        continue;
                    }
                    
                    $tmpProduct = array();
                    $tmpProduct['ID'] = $product_item->get_id();
                    $tmpProduct['sku'] = $product_item->get_sku();
                    $tmpProduct['large_number'] = $product_item->get_attribute('large_number');
                    $tmpProduct['product_name'] = $product_item->get_formatted_name();

                    $products[] = $tmpProduct;        

                }        
            }

            $total_items = count($products);
            //$total_items = count($total_products);
            $this->set_data($products);
            usort( $this->data, array( &$this, 'usort_reorder' ) );

            $this->set_pagination_args( array(
                'total_items' => $total_items,                  //WE have to calculate the total number of items
                'per_page'    => $per_page                     //WE have to determine how many items to show on a page
            ) );

            $this->items = $this->data;
        }

        
        public function process_bulk_action() {
//            if ('ebhassignlargenumber' === $this->current_action() ) {
//                $this->Eboekhouden_Largenumbers->ebhAssignLargenumber();
//            }
            if ( isset($_POST['action']) && $_POST['action'] == 'ebh_assign_largenumber' ) {
                $this->Eboekhouden_Largenumbers->ebhProcessAssignLargenumber();
            }
        }

//        public function display() {
//            $this->prepare_items();
//            $this->search_box('Search', 'products');
//            if( !isset($_POST['action'])) {                
//                $strHtml = '<div class="wrap">';
//                echo '<h2>' . __('Products', 'eboekhouden') . '</h2>';
//                $strHtml .= '<form method="post">';
//                $strHtml .= '<input type="hidden" name="page" value="eboekhouden_product_list_table">';
//                parent::display();
//                $strHtml .= '</form>';
//                $strHtml .= '</div>';
//            }
//        }


    }
    
    
}