<?php
/**
 * Plugin Name: e-Boekhouden.nl
 * Plugin URI: https://www.e-boekhouden.nl
 * Description: A plugin which exports orders to e-Boekhouden.nl
 * Version: 3.0.0
 * Author: e-Boekhouden.nl
 * Author URI: https://www.e-boekhouden.nl
 * Text Domain: eboekhouden
 * License: GPL2
 *
 * @package e-Boekhouden.nl
 * @category Core
 * @author e-Boekhouden.nl
 */

//function ebh_register_session(){
//    if( !session_id() )
//        session_start();
//}
//add_action('init','ebh_register_session');


if (!function_exists('is_admin') || !defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


//echo '<h1>[EBOEKHOUDEN.PHP]</h1>';
//echo 'BUG: "ordernumber" plugins  <br>';
//echo 'BUG: "debug" warning message not working (session) <br>';
//echo 'BUG: check "classes" WHEN and WHERE to "instanciate" (private $Eboekhouden_%CLASS%) <br>';
//echo 'IMPLEMENT: (f3) base/prefab instance <br>';
//echo 'BUS': Search and pagination on product and order tables<br>';
//die('ISSUES');

/**
 * @todo:
 * - [ ] Minimum PHP version check
 *      - [ ] "Warning" for php 5.6.x
 *      - [ ] Min. php 7.x.x ??
 * - [ ] Check if Woocommerce is installed
 *      - [ ] Add 'message' if not found / installed
 *      - [ ] Check for 'minimum' version is 3.x.x
 * - [ ] 'Update process'
 * - [ ] Delete 'process'
 *      - [ ] Remove "options / settings"
 *      - [ ] Remove "order / product" data
 * - [ ] Activate / Decativate 'process'
 */


if (!class_exists("Eboekhouden")) {

    class Eboekhouden {
//        var $settings, $options_page, $eboek;
//        private $order_nr_prefix;
//        private $order_nr_suffix;
        
        private $Eboekhouden_Debug;
        
        private $Eboekhouden_Settings;
        private $Eboekhouden_Menus;        
        private $Eboekhouden_Session;
        
        private $Eboekhouden_Database;
        
        
        function __construct() {            
            $this->load();
            $this->init();
            $this->hooks();
        }
        
        
        private function load() {
            require_once 'defines.php';
            require_once 'loader.php';
        }
        
        
        private function init() {
            $this->Eboekhouden_Debug = new Eboekhouden_Debug();            
            $this->Eboekhouden_Session = new Eboekhouden_Session();
            $this->Eboekhouden_Settings = new Eboekhouden_Settings();
            $this->Eboekhouden_Menus = new Eboekhouden_Menus();
            
            //$this->Eboekhouden_Database = new Eboekhouden_Database();            
            //$this->Eboekhouden_Database->ebh_new_order(19);
        }
        
        
        private function hooks() {
            add_action('init', array($this, 'ebh_load_plugin_textdomain'));
            add_action('admin_enqueue_scripts', array($this, 'ebh_enqueue_scripts'));
             
        }

        
        public function ebh_load_plugin_textdomain() {
            //echo EBOEKHOUDEN_DIR_LANGUAGES;            
            load_plugin_textdomain('eboekhouden', false, basename(dirname(__FILE__)) . '/languages/' ); 
        }
        
        
        public function ebh_enqueue_scripts() {            
            wp_register_script('jquery-ui-full_min_js', EBOEKHOUDEN_URL_ASSETS . 'js/jquery-ui.min.js');
            wp_enqueue_script('jquery-ui-full_min_js');

            wp_register_style('jquery-ui-full_min_css', EBOEKHOUDEN_URL_ASSETS . 'css/jquery-ui.min.css');
            wp_enqueue_style('jquery-ui-full_min_css');
            
            
            wp_register_script('ebh-script', EBOEKHOUDEN_URL_ASSETS . 'js/ebh-script.js');
            wp_enqueue_script('ebh-script');            
            
            wp_register_style('ebh-styles', EBOEKHOUDEN_URL_ASSETS . 'css/ebh-styles.css');
            wp_enqueue_style('ebh-styles');
        }

    } 
    
}


global $eboekhouden;
if (class_exists("Eboekhouden") && !$eboekhouden) {
    $eboekhouden = new Eboekhouden();
}

