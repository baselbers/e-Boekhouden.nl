<?php
// Supports WC version 3.x.x


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Eboekhouden_Product_List_Table extends WP_List_Table {

    public $data;
    public $eboek;

    function __construct(){
        global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'product', 'eboekhoudenproductlisttable' ),     //singular name of the listed records
            'plural'    => __( 'products', 'eboekhoudenproductlisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );


        if(isset($_SESSION['eboekhouder-notices']))
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
        $this->eboek = new EboekhoudenJaagers();
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
        echo '.wp-list-table .column-sku { width: 15%; }';
        echo '.wp-list-table .column-largenumber { width: 15%; }';
        echo '.wp-list-table .column-productname { width: 25%; }';
        echo '.wp-list-table .column-productcategory { width: 25%; }';
        echo '</style>';
    }

    function no_items() {
        _e( 'No products Found' );
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            default:
                return false;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'ID'  => array('ID',false)
        );
        return $sortable_columns;
    }

    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'sku'           => __( 'Product SKU', 'eboekhoudenproductlisttable' ),
            'large_number'  => __( 'Large Number', 'eboekhoudenproductlisttable' ),
            'product_name'  => __( 'Product name', 'eboekhoudenproductlisttable' )
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
            'ebhassignlargenumber'    => 'Mass-assign largenumber',
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="book[]" value="%s" />', $item['ID']
        );
    }

    function column_ID($item) {
        return $item['ID'];
    }

    function column_sku($item) {
        if(strlen($item['sku'])) {
            return '<a href="post.php?post=' . $item['ID'] . '&action=edit">' . $item['sku'] . '</a>';
        } else {
            return '<a href="post.php?post=' . $item['ID'] . '&action=edit">--</a>';
        }
    }

    function column_large_number($item) {
        if(strlen($item['large_number'])) {
            return $item['large_number'];
        } else {
            return '--';
        }
    }

    function column_product_name($item) {
        return '<a href="post.php?post=' . $item['ID'] . '&action=edit">' . $item['product_name'] . '</a>';
    }

    function prepare_items() {

        $this->process_bulk_action();

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
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';

        $total_products = get_posts( array(
            'post_type'     => 'product',
            'nopaging'      => true
        ));
        
        
        //rbs fix
        $args = array(
            'post_type'     => 'product',
            'post_status'	=> 'publish',
            'posts_per_page'=> $per_page,
            'offset' => $offset,
            'orderby' => $orderby,
            'order' => $order
        );

        $tmpProducts = new WP_Query( $args );

	      
        foreach ($tmpProducts->posts as $post_item) {
        
        
            $product_item = wc_get_product($post_item->ID);
      
            $tmpProduct = array();
            // rbs fix $tmpProduct['ID'] = $product_item->id;
            $tmpProduct['ID'] = $product_item->get_id();
            $tmpProduct['sku'] = $product_item->get_sku();
           
            $tmpProduct['large_number'] = $product_item->get_attribute('large_number');
            $tmpProduct['product_name'] = $product_item->get_formatted_name();

            $products[] = $tmpProduct;        
      
        
        }        

        $total_items = count($total_products);

        $this->set_data($products);

        usort( $this->data, array( &$this, 'usort_reorder' ) );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );

        $this->items = $this->data;
    }

    function process_bulk_action() {
        if ( 'ebhassignlargenumber' === $this->current_action() ) {
            $this->ebh_assign_largenumber();
        }
        if ( isset($_POST['action']) && $_POST['action'] == 'ebh_assign_largenumber' ) {
            $this->process_ebh_assign_largenumber();
        }
    }

    function display() {
        if(!isset($_POST['action'])) { ?>
            <div class='wrap'>
                <h2>Products</h2>
                <form method="post">
                    <input type="hidden" name="page" value="eboekhouden_order_list_table">
                    <?php
                        parent::display();
                    ?>
                </form>
            </div>
        <?php }
    }

    function ebh_assign_largenumber(){

        if(isset($_POST['book'])) {
            $items = $_POST['book'];
        }

        if(isset($items) && count($items)) { ?>

            <div class='wrap'>
            <h2>Assign</h2>

            <form method="post">
                <label for=""><?php echo _e('Choose largenumber'); ?></label>
                <?php
                $optionsOptions = $this->eboek->getLargeNumbersOptions();
                ?>
                <input type="hidden" name="action" value="ebh_assign_largenumber" />
                <input type="hidden" name="ids" value="<?php echo implode(',', $items); ?>" />
                <select name='ebh_largenumber'>
                    <?php if(!count($optionsOptions)) : ?>
                        <option value="" disabled selected>...</option>
                    <?php endif; ?>
                    <?php foreach($optionsOptions as $option) : ?>
                        <option value='<?php echo $option['code']; ?>'>
                            <?php echo $option['code'] . ' - ' . $option['description']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><?php echo _e('Save'); ?></button>
            </form>

            </div>

        <?php }

    }

    function process_ebh_assign_largenumber(){

        if(isset($_POST['ids'])) {
            $items = explode(',', $_POST['ids']);
        }


        if(isset($items) && count($items)) {

            $args = array(
                'post_type'     => 'product',
                'posts_per_page' => -1,
                'post__in'      => $items
            );

            $tmpProducts = new WP_Query( $args );
            


            if ( $tmpProducts->have_posts() ):
                while ( $tmpProducts->have_posts() ): $tmpProducts->the_post();

                    global $product;

                    $_SESSION['eboekhouder-notices'][] = array(
                        'type'      => 'success',
                        'message'   => 'Product "' . $product->get_formatted_name( ) . "' was updated to large number " . $_POST['ebh_largenumber']
                    );


                    $this->set_attributes( $product->get_id(), 'large_number', $_POST['ebh_largenumber']);
                endwhile;
            else:
                $_SESSION['eboekhouder-notices'][] = array(
                    'type'      => 'error',
                    'message'   => 'Products ' . implode(',', $items) . ": could not assign large number"
                );
            endif; //wp_reset_postdata();

        }

        echo "<script>window.location='admin.php?page=e-Boekhouden.nl%2Feboekhouden.php%2Fproducts';</script>";exit;

        wp_redirect( 'admin.php?page=e-Boekhouden.nl%2Feboekhouden.php%2Fproducts' );
        exit;

    }

    function set_attributes($post_id, $attributeName, $attributeValue) {

        $current_product_attributes = get_post_meta($post_id, '_product_attributes', true);

        if (isset ($current_product_attributes['large_number'])) {
            $current_product_attributes['large_number']['value'] = $attributeValue;
            update_post_meta($post_id, '_product_attributes', $current_product_attributes);
        } else {
            if(!is_array($current_product_attributes)) {
                $current_product_attributes = array();
            }
            
            $large_number = array(
                //Make sure the 'name' is same as you have the attribute
                'name' => htmlspecialchars(stripslashes($attributeName)),
                'value' => $attributeValue,
                'position' => 1,
                'is_visible' => 0,
                'is_variation' => 0,
                'is_taxonomy' => 0
            );
            
            $current_product_attributes['large_number'] = $large_number;
            update_post_meta($post_id, '_product_attributes', $current_product_attributes);
        }  

    }

} //class