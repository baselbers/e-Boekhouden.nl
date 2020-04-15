<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


/**
 * @todo
 * - [ ] ?? Rename class ??
 *      >> find 'better' name     
 */

if (!class_exists("Eboekhouden_Plugins")) {
    
    class Eboekhouden_Plugins {
        
        private $Eboekhouden_Session;
        
        private $active_plugins;
        
        function __construct() {
            $this->Eboekhouden_Session = new Eboekhouden_Session();
        }
        
        
        private function ebh_is_plugin_enabled($plugin) {
            
        }
        
        
        
        private function ebh_number_plugin($plugin, $order) {            
            $number = null;
            //echo $plugin;
            //$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_ERROR, 'order/invoice nummer met plugins moet GOED uitgewerkt worden, return = $order->get_order_number()');
            //$plugin = 'default';
            
            
            if ($plugin == 'default') {                              
                //$number = apply_filters('woocommerce_order_number', $order->get_id(), $order);  
                //$number = apply_filters('woocommerce_order_number', $order->get_id(), $order);  
                $number = $order->get_order_number();
                $number = sprintf('%08d', $number);        
                
            } elseif ($plugin == 'woocommerce-sequential-order-numbers') {
                //die('TODO: plugin support for "woocommerce-sequential-order-numbers"');
                $WC_Seq_Order_Number = new WC_Seq_Order_Number();
                $number = $WC_Seq_Order_Number->get_order_number($order->get_id(), $order);
                
            } elseif ($plugin == 'woocommerce-pdf-invoices-packing-slips') {
                $invoice = wcpdf_get_invoice($order);
                
                //var_dump($invoice);
                $invoice_number = $invoice->get_number();
                //var_dump($invoice_number);
                if ($invoice_number != null) {
                    $number = $invoice_number->get_formatted();
                }
                
            } elseif ($plugin == 'wc-sequential-order-numbers-master') {
                $message = 'Plugin: "wc-sequential-order-numbers-master" wordt NIET meer ondersteund!!!';
                $this->Eboekhouden_Session->ebhAddNotice('error', $message);
                //$WC_Sequential_Order_Numbers_Admin = new WC_Sequential_Order_Numbers_Admin();
                //$number = $WC_Sequential_Order_Numbers_Admin->get_order_number($order->get_the_id(), $order);
                //$number = apply_filters('woocommerce_order_number', $order, $order);
                //$number = 888;
                $number = $order->get_order_number();
                
            } elseif ($plugin == 'WooCommerce_PDF_Invoices') {
                $wpo_wcpdf = new WooCommerce_PDF_Invoices();
                $wpo_wcpdf->load_classes();
                $number = $wpo_wcpdf->export->get_invoice_number($order->get_id());                
            }

            
              if (is_string($number) && strlen($number) == 0 || $number == null) {
                  $number = $order->get_order_number();
              }
              
              return $number;
            
//            
//            if (class_exists('WPO_WCPDF')) {
//                  $invoice = wcpdf_get_invoice( $order );            
//                  if ( $invoice_number = $invoice->get_number() ) {
//                      $rbs_order_number = $invoice_number->get_formatted();
//                  }
//              } elseif (class_exists('WC_Sequential_Order_Numbers') or function_exists('init_woocommerce_sequential_order_numbers_pro')) {
//                  $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
//              } elseif (class_exists('WooCommerce_PDF_Invoices') ) {
//                  $wpo_wcpdf = new WooCommerce_PDF_Invoices();
//                  $wpo_wcpdf->load_classes();
//                  $rbs_order_number = $wpo_wcpdf->export->get_invoice_number($order->id);
//              } else {
//                  $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
//                  $rbs_order_number = sprintf('%08d',$rbs_order_number);
//              }
//              /*** aangepast als  sequential ordernumers aanwezig is dit als betalingskenmerk anders het normale id.***/
//              if (class_exists('WC_Sequential_Order_Numbers') or function_exists('init_woocommerce_sequential_order_numbers_pro')) {
//                  $rbs_order_number_payment = '#' . apply_filters( 'woocommerce_order_number', $order->id, $order );
//              } else {
//                  $rbs_order_number_payment = '#' . (string) $this->_data->ID;
//              }
//
//              /*** Return 'default' OrderNumber as 'fallback' (When not set) ***/
//              if (is_string($rbs_order_number) && strlen($rbs_order_number) == 0) {
//                  $rbs_order_number = $order->get_order_number();
//              }            
            
            
        }
        
        
        
        public function ebhCheckOrderNumberPlugins() {
            $return = array();
            $supported_plugins = array();
            $supported_plugins['woocommerce-sequential-order-numbers'] = 'WooCommerce Sequential Order Numbers';
            $supported_plugins['woocommerce-pdf-invoices-packing-slips'] = 'WooCommerce PDF Invoices & Packing Slips';
            //$supported_plugins['wc-sequential-order-numbers-master'] = 'WC Sequential Order Numbers ';            // "niet meer up-2-date" >> Geeft "error"
            
            $active_plugins = get_option('active_plugins');
            foreach($active_plugins as $active_plugin) {
                $plugin = explode('/', $active_plugin);
                if (isset($plugin[0])) {
                    if (array_key_exists($plugin[0], $supported_plugins)) {                       
                        $return[$plugin[0]] = $supported_plugins[$plugin[0]];
                    }                            
                }
            }

            return $return;
        }
        
        
        
        public function ebhGetOrderNumber($order_id) {
            $general_settings = get_option('ebh_settings_general');            
            $ebh_plugin_ordernumber = (isset($general_settings['ebh_plugin_ordernumber'])) ? $general_settings['ebh_plugin_ordernumber'] : 'default';
            
            $wc_order = new WC_Order($order_id);
            $order_number = $this->ebh_number_plugin($ebh_plugin_ordernumber, $wc_order);
            
            $message = array();
            $message['order_id'] = $order_id;
            $message['ordernumber_plugin'] = $ebh_plugin_ordernumber;
            $message['order_number'] = $order_number;            
            ebh_debug_message($message, 'Eboekhouden_Plugins', 'ebhGetOrderNumber', 'plugins.log');

            //return apply_filters('ebh_filter_order_number', $order_number, $order_id, $wc_order, $ebh_plugin_ordernumber);
            $ebh_filter_order_number = apply_filters('ebh_filter_order_number', $order_number, $order_id, $wc_order, $ebh_plugin_ordernumber);
            update_post_meta($order_id, '_ebh_order_number', $ebh_filter_order_number);
            return $ebh_filter_order_number;
        }
        
        
        public function ebhGetOrderPaymentReference($order_id) {
            $general_settings = get_option('ebh_settings_general');
            $ebh_plugin_payment_reference = (isset($general_settings['ebh_plugin_payment_reference'])) ? $general_settings['ebh_plugin_payment_reference'] : 'default';                        

            $wc_order = new WC_Order($order_id);
            
            if ($ebh_plugin_payment_reference == 'default') {
                $reference_number = $this->ebhGetOrderNumber($order_id);
            } else {                
                $reference_number = $this->ebh_number_plugin($ebh_plugin_payment_reference, $wc_order);
            }
            
            $message = array();
            $message['order_id'] = $order_id;
            $message['payment_reference_plugin'] = $ebh_plugin_payment_reference;
            $message['reference_number'] = $reference_number;            
            ebh_debug_message($message, 'Eboekhouden_Plugins', 'ebhGetOrderNumber', 'plugins.log');            
            
            return apply_filters('ebh_filter_payment_reference_number', $reference_number, $order_id, $wc_order, $ebh_plugin_payment_reference);
        }
        
        
        
    }
    
}
        