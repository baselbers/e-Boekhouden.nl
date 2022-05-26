<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/**
 * @ref:
 * - https://codex.wordpress.org/Creating_Tables_with_Plugins
 * 
 * @todo:
 * - [ ] Create tables
 *      - [ ] orders
 *      - [ ] ?? products ??
 * - [ ] (database) Upgrade function
 * - [ ] Query methods:
 *      - [x] insert / update
 *      - [x] remove
 *      - [90%] find
 *      >> add 'wheres' ebhFindOrders();
 * - [ ] Remove tables 
 *      >> when deleting plugin
 * - [ ] "empty" table(s)
 *      >> remove ALL data
 * - [ ] "export"
 * - [ ] "secure" sql queries
 *      >> see 'ref documentation'
 * 
 * - [ ] ?? Make 'post_id (== order_id)' unique??
 * - [ ] ?? Add 'comments / notes' column 
 * - [ ] ?? Add 'original order number' column ??
 *      >> unless it is the same as 'order_id'
 * - [ ] ?? Add 'order_date' column ??
 *      >> CAN BE different then the 'date_created' column
 * 
 */


/**
 * @sql:
CREATE TABLE IF NOT EXISTS `ebh3x_eboekhouden_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `mutation_number` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 */

if (!class_exists("Eboekhouden_Database")) {
    
    class Eboekhouden_Database {
        
        private $ebh_db_version = '1.0';
        private $table_name_orders;
        private $charset_collate;
        
        
        function __construct() {
            
            if (defined('EBOEKHOUDEN_PLUGIN_FILE')) {
                $this->init();
                $this->hooks();
            } else {
                wp_die('ERRROR: "EBOEKHOUDEN_PLUGIN_FILE" is NOT set!!');
            }
            
                        
        }
        
        
        private function init() {
            global $wpdb;
            $this->table_name_orders = $wpdb->prefix . 'eboekhouden_orders';
            $this->charset_collate = $wpdb->get_charset_collate();
                        
            register_activation_hook(EBOEKHOUDEN_PLUGIN_FILE, array($this, 'ebh_install_tables'));
        }
        
        
        private function hooks() {            
            add_action('woocommerce_new_order', array($this, 'ebh_new_order'), 1, 1);
        }
        
        
        
        public function ebh_install_tables() {
            $sql_orders_table = 
                "CREATE TABLE $this->table_name_orders (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `post_id` int(11) NOT NULL,
                    `order_number` varchar(255) NOT NULL,
                    `reference_number` varchar(255) NOT NULL,
                    `mutation_number` int(11) NOT NULL,
                    `date_created` datetime NOT NULL,
                    `date_modified` datetime NOT NULL,
                    PRIMARY KEY (`id`)
                ) $this->charset_collate ;";
                    
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $result = dbDelta($sql_orders_table);
            
            //echo print_r($result);            
            
            add_option('ebh_db_version', $this->ebh_db_version);
            
            
            return $result;
        }
        
        
        
        public function ebh_new_order($order_id) {
            $wc_order = new WC_Order($order_id);
            echo 'ebh_new_order - 000';
            $this->ebhInsertOrder($order_id, $wc_order->get_order_number(), null, null);
            echo 'ebh_new_order - 1111';
            
            die();
        }
        
        
        
        public function ebhInsertOrder($order_id, $order_number, $reference_number, $mutation_number) {
            global $wpdb;
            
            $create_date = date('Y-m-d H:i:s');            
            
            $data = array();
            $data['post_id'] = $order_id;
            $data['order_number'] = $order_number;
            $data['reference_number'] = $reference_number;
            $data['mutation_number'] = $mutation_number;
            $data['date_created'] = $create_date;
            $data['date_modified'] = $create_date;            
            
            $result = $wpdb->insert($this->table_name_orders, $data);
            
            var_dump($result);
            return $result;            
        }
        
        
        public function ebhUpdateOrder($order_id, $order_number = null, $reference_number = null, $mutation_number = null) {
            global $wpdb;
            
            $where = array();
            $where['post_id'] = $order_id;
            
            $data = array();
            $data['order_number'] = $order_number;
            $data['reference_number'] = $reference_number;
            $data['mutation_number'] = $mutation_number;
            
            // array_filter == removes "empty / null" values from array
            $result = $wpdb->update($this->table_name_orders, array_filter($data), $where);     
            return $result;            
        }
        
        
        public function ebhGetOrder($order_id) {
            global $wpdb;            
            $sql = "SELECT * FROM $this->table_name_orders WHERE post_id = $order_id";
            $result = $wpdb->get_row($sql);            
            return $result;
        }
        
        
        public function ebhFindOrders($order_id = null, $order_number = null, $reference_number = null, $mutation_number = null) {
            global $wpdb;
            $sql = "SELECT * FROM $this->table_name_orders ";
            
            // TODO: Add 'wheres' based on function params
            
            $result = $wpdb->get_results($sql);
            return $result;
        }
        
        
        public function ebhRemoveOrder($order_id) {
            global $wpdb;
            $where = array();
            $where['post_id'] = $order_id;
            $result = $wpdb->delete($this->table_name_orders, $where);
            return $result;
        }
        
    }
    
}
        
