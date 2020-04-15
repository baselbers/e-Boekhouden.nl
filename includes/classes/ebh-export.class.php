<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


/**
 * - [ ] Add 'error / debug' logging
 * - [ ] ?? Add 'check' if customer has all 'required' fields
 */



if (!class_exists("Eboekhouden_Export")) {
    
    class Eboekhouden_Export {
        
        private $wc_order_id;
        private $wc_order;
        //private $wc_tax;
        
        private $ebh_export_customer;
        private $ebh_export_mutations;
        
        private $Eboekhouden_Settings;
        private $Eboekhouden_Taxes;
        private $Eboekhouden_Largenumbers;
        private $Eboekhouden_Session;
        private $Eboekhouden_Plugins;
        
        
        
        function __construct($order_id) {
            $order = new WC_Order($order_id);
            
            if (!$order instanceof WC_Order) {
                ebh_debug_message('$order is not an WC_Order Object!', 'Eboekhouden_Export', '__construct', 'export.log');
                wp_die('TEMP ERROR: Invalid order object');                
            }
            
            $this->wc_order_id = $order_id;
            $this->wc_order = $order;
            
            /** ?? Integrate in  'Eboekhouden_Taxes class??: */
            //$this->wc_tax = new WC_Tax();       //looking for appropriate vat for specific product
            
            $this->ebh_export_customer = array();
            $this->ebh_export_mutations = array();
            
            $this->Eboekhouden_Settings = new Eboekhouden_Settings();
            $this->Eboekhouden_Taxes = new Eboekhouden_Taxes();
            $this->Eboekhouden_Largenumbers = new Eboekhouden_Largenumbers();
            $this->Eboekhouden_Session = new Eboekhouden_Session();
            $this->Eboekhouden_Plugins = new Eboekhouden_Plugins();
            
        }
        
        
        
        private function ebh_build_customer() {
            /**             
             * Customer Format
             * <NAW>
             *  <BEDRIJF>Yvonne van der A-Mulder</BEDRIJF>
             *  <ADRES>Hondsdraflaan 14</ADRES>
             *  <POSTCODE>2121RA</POSTCODE>
             *  <PLAATS>Bennebroek</PLAATS>
             *  <TELEFOON>023-5849879</TELEFOON>
             *  <EMAIL>info@email.nl</EMAIL>
             * </NAW>
             */

            $customer = array(
                'BEDRIJF' => strlen($this->wc_order->get_billing_company()) > 0 ? $this->wc_order->get_billing_company() : implode(' ', array($this->wc_order->get_billing_first_name(), $this->wc_order->get_billing_last_name())),
                'ADRES' => implode(' ', array($this->wc_order->get_billing_address_1(), $this->wc_order->get_billing_address_2())),
                'POSTCODE' => $this->wc_order->get_billing_postcode(),
                'PLAATS' => $this->wc_order->get_billing_city(),
                'TELEFOON' => $this->wc_order->get_billing_phone(), // RBS 260117
                'EMAIL' => $this->wc_order->get_billing_email() // RBS 260117
            );   

            $this->ebh_export_customer = apply_filters('ebh_filter_build_customer', $customer, $this->wc_order_id, $this->wc_order);
        }
        
        
        
        
        private function ebh_wc_order_items() {            
            
            $orderItems = $this->wc_order->get_items();
            $orderDiscount = $this->wc_order->get_total_discount();

            foreach($orderItems as $orderItem) {
                $item_data = $orderItem->get_data();
                $tax_class = $orderItem['tax_class'];
                $rates = $this->Eboekhouden_Taxes->wcTax()->get_rates($tax_class);//var_dump($tax_class);
                //var_dump($rates);
                $rate = current($rates);
                $orderItemProduct = new WC_Product($orderItem['product_id']);
                $largenumber = $orderItemProduct->get_attribute('large_number');

                if (strlen($orderItemProduct->get_sale_price()) && $orderItemProduct->get_sale_price() < $orderItemProduct->get_regular_price()) {
                    // do something with discount
                }
//var_dump($item_data);//die();
                $row_total = (float) $item_data['total'];
                $row_sub_total = (float) $item_data['subtotal'];
                $row_quantity = (float) $item_data['quantity'];
                
                // lower discount
                if ($row_total < $row_sub_total) {
                    $orderDiscount = $orderDiscount - (($row_sub_total - $row_total));
                };

                $mutation = new Eboekhouden_Mutation('orderitem', $this->wc_order_id);
                $mutation->SetTotal($item_data['total'] + $item_data['total_tax']);
                $mutation->SetSubTotal($item_data['total']);
                $mutation->SetTaxTotal($item_data['total_tax']);
                $mutation->SetLargeNumber($largenumber);
                //$mutation->SetTaxPercent((isset($rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rate['label']] : '');
                $mutation->SetTaxPercent((isset($rate['label'])) ? $this->Eboekhouden_Taxes->ebhGetTaxcode($rate['label']): ''); 
                //var_dump($rate);die();
                $this->ebh_export_mutations[] = $mutation->GetMutation();
            
            }       
            
         //   die();
        }
        
        
        private function ebh_wc_shipping() {
            $orderShipping = $this->wc_order->get_total_shipping();
            $orderShippingTax = $this->wc_order->get_shipping_tax();       
                   
            if ($orderShipping > 0) {
                $mutation = new Eboekhouden_Mutation('shipping', $this->wc_order_id);
                $mutation->SetTotal($orderShipping + $orderShippingTax);
                $mutation->SetSubTotal($orderShipping);
                $mutation->SetTaxTotal($orderShippingTax);
                //$mutation->SetLargeNumber($pluginSettings['ebh_shippingcost_largenumber']);
                $mutation->SetLargeNumber($this->Eboekhouden_Largenumbers->ebhGetLargenumber('shippingcost'));                
                //$mutation->SetTaxPercent(EboekhoudenJaagers::$taxCodes[$shippingTaxRate['label']]);
                $mutation->SetTaxPercent(isset($shippingTaxRate['label']) ? $this->Eboekhouden_Taxes->ebhGetTaxcode($shippingTaxRate['label']) : '');
                $this->ebh_export_mutations[] = $mutation->GetMutation();
            }            
        }

        private function ebh_wc_refunds() {
            
            $orderRefunds = $this->wc_order->get_refunds();
        
            foreach ($orderRefunds as $refund) {
                
                if (count($refund->get_items()) != 0) {
                    
                    foreach ($refund->get_items() as $refund_item) {

                        $rf_tax_class = $refund_item['tax_class'];
                        $rf_rates = $this->Eboekhouden_Taxes->wcTax()->get_rates($rf_tax_class);
                        $rf_rate = current($rf_rates);
//                        $rf_orderItemProduct = new WC_Product($refund_item['product_id']);
//                        $rf_largenumber = $rf_orderItemProduct->get_attribute('large_number');

                        $mutation = new Eboekhouden_Mutation('refund', $this->wc_order_id);
                        $mutation->SetTotal(get_post_meta($refund->id,'_order_total',true));
                        $mutation->SetSubTotal(get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true));
                        $mutation->SetTaxTotal(get_post_meta($refund->id,'_order_tax',true));
                        //$mutation->SetLargeNumber($rf_largenumber);
                        //$mutation->SetLargeNumber(isset($pluginSettings['ebh_refund_largenumber']) ? $pluginSettings['ebh_refund_largenumber'] : '');
                        $mutation->SetLargeNumber($this->Eboekhouden_Largenumbers->ebhGetLargenumber('refund'));
                        $mutation->SetTaxPercent((isset($rf_rate['label'])) ? $this->Eboekhouden_Taxes->ebhGetTaxcode($rf_rate['label']) : '');
                        $this->ebh_export_mutations[] = $mutation->GetMutation(); 
                    }
                    
                } else {
                        $refund_data_item = $refund->get_data();
                        
                        $mutation = new Eboekhouden_Mutation('refund', $this->wc_order_id);
                        $mutation->SetTotal(get_post_meta($refund->id,'_order_total',true));
                        $mutation->SetSubTotal(get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true));
                        $mutation->SetTaxTotal(get_post_meta($refund->id,'_order_tax',true));
    //                    $mutation->SetLargeNumber($rf_largenumber);
                        //$mutation->SetLargeNumber(isset($pluginSettings['ebh_refund_largenumber']) ? $pluginSettings['ebh_refund_largenumber'] : '');                    
                        $mutation->SetLargeNumber($this->Eboekhouden_Largenumbers->ebhGetLargenumber('refund'));
                        $mutation->SetTaxPercent((isset($rf_rate['label'])) ? $this->Eboekhouden_Taxes->ebhGetTaxcode($rf_rate['label']) : '');

                        $this->ebh_export_mutations[] = $mutation->GetMutation(); 
             

                }

            }               
            
        }
        

        private function ebh_wc_fees() {
            
            $fees = $this->wc_order->get_fees();
            if (count($fees) > 0 ) {

                foreach ($fees as $key => $value) {

//                    $fee_mutation = array(
//                       'BEDRAGINCL' => 0,
//                       'BEDRAGEXCL' => 0,
//                       'BTWBEDRAG' => 0,
//                       'TEGENREKENING' =>  $pluginSettings['ebh_paymentcost_largenumber'],
//                        'BTWPERC' => (isset($rate['label'])) ? $this->Eboekhouden_Taxes->ebhGetTaxcode($rate['label']) : ''
//
//
//                    );



                    $line_total = $value->get_total();
                    $btw_bedrag = $value->get_total_tax();

                    $mutation = new Eboekhouden_Mutation('fees', $this->wc_order_id);
                    $mutation->SetTotal($line_total + $btw_bedrag);
                    $mutation->SetSubTotal($line_total);
                    $mutation->SetTaxTotal($btw_bedrag);
                    //$mutation->SetLargeNumber(isset($pluginSettings['ebh_paymentcost_largenumber']) ? $pluginSettings['ebh_paymentcost_largenumber'] : '');
                    $mutation->SetLargeNumber($this->Eboekhouden_Largenumbers->ebhGetLargenumber('paymentcost'));
                    //$mutation->SetLargeNumber($pluginSettings['ebh_shippingcost_largenumber']);
                    //$mutation->SetTaxPercent(EboekhoudenJaagers::$taxCodes[$shippingTaxRate['label']]);                

    //                $fee_mutation['BEDRAGINCL'] = round($line_total + $btw_bedrag, 2);
    //                $fee_mutation['BEDRAGEXCL'] = round($line_total,2);
    //                $fee_mutation['BTWBEDRAG'] = round($btw_bedrag,2);
    //                $mutation_rule = array(
    //                'MUTATIEREGEL' =>   $fee_mutation
    //                );
    //                
    //                
    //                $this->_mutations[] = $mutation_rule;

                    $this->ebh_export_mutations[] = $mutation->GetMutation(true);    



                }

    //            die();

            }            
        }        
        
        
        
        
        
//        private function ebh_build_products() {
//            
//        }
        
        
//        public function ebhExportOrder($order) {
//            $export = $order->getExportOrder();
//
//            $res = self::_getResponse($export);
//
//            $rbs_order = new WC_Order($order->_data->ID);
//
//            $rbs_order_id = $rbs_order->get_id();
//
//            $rbs_order_number = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );
//
//            if (isset($res->MUTNR)) {
//                $_SESSION['eboekhouder-notices'][] = array(
//                    'type'      => 'success',
//                    'message'   => 'Order ' . $rbs_order_number . ": " . $res->RESULT . " mutatie " . $res->MUTNR
//
//                );
//
//                return (int)$res->MUTNR;
//            }
//
//            $_SESSION['eboekhouder-notices'][] = array(
//                'type'      => 'error',
//                'message'   => 'Order ' . $rbs_order_number . ": " . $res->ERROR->CODE . " " . $res->ERROR->DESCRIPTION
//
//                );
//
//            return 0;
//        }      
        
        
        
        private function ebh_get_ordernumber() {
////$order = new WC_Order($this->_data->ID);
//$order = wc_get_order($this->_data->ID);
//
////$rbs_order_id = $order->get_id();
//$rbs_order_date = $order->get_date_created();
//
///*** Check 'how to' format the OrderNumber: ***/
//if (class_exists('WC_Sequential_Order_Numbers') or function_exists('init_woocommerce_sequential_order_numbers_pro')) {
//    $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
//} elseif (class_exists('WPO_WCPDF')) {
//    $invoice = wcpdf_get_invoice( $order );            
//    if ( $invoice_number = $invoice->get_number() ) {
//        $rbs_order_number = $invoice_number->get_formatted();
//    }
//} elseif (class_exists('WooCommerce_PDF_Invoices') ) {
//    $wpo_wcpdf = new WooCommerce_PDF_Invoices();  
//    $wpo_wcpdf->load_classes();
//    $rbs_order_number = $wpo_wcpdf->export->get_invoice_number($order->id);
//} else {
//    $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
//    $rbs_order_number = sprintf('%08d',$rbs_order_number);
//}
//
///*** Return 'default' OrderNumber as 'fallback' (When not set) ***/
//if (is_string($rbs_order_number) && strlen($rbs_order_number) == 0) {
//    $rbs_order_number = $order->get_order_number();
//}               
            
        }
        
        
        public function ebhExportOrder() {
            
            //die('ebhExportOrder');
            $export_data = $this->ebhBuildExport();
            //$export_data = array_merge($this->ebh_export_customer, $this->ebh_export_mutations);

            
            if (get_post_meta($this->wc_order_id, 'mutation_nr', true)) {
                $action = 'ALTER_MUTATIE';
            } else {
                $action = 'ADD_MUTATIE';
            }
            
            $Eboekhouden_Connector = new Eboekhouden_Connector();
            $result = $Eboekhouden_Connector->ebhSend($action, $export_data);
            
            // SimpleXMLElement Object ( [RESULT] => OK [MUTNR] => 131 ) 1aaaaaaaaaaaa
            
            $ebh_order_number = apply_filters('woocommerce_order_number', $this->wc_order_id, $this->wc_order);
            
            if (isset($result->RESULT) && isset($result->MUTNR)) {
                $message = 'Order ' . $ebh_order_number . ": " . $result->RESULT . " mutatie " . $result->MUTNR;
                //$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message); 
                $this->Eboekhouden_Session->ebhAddNotice(Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message); 

                $return = array(
                    'order_id' => $this->wc_order_id,
                    'mutation_nr' => (int) $result->MUTNR
                );
                
            } else {
                $message = $ebh_order_number . ": " . $result->ERROR->CODE . " " . $result->ERROR->DESCRIPTION;
                ebh_debug_message($message, 'Eboekhouden_Export', 'ebhExportOrder', 'export.log');
                $this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_ERROR, $message); 

                $return = array(
                    'order_id' => $this->wc_order_id,
                    'mutation_nr' => false
                );            
                
            }
            
            
            return $return;
        }        
        
        
        
        public function ebhBuildExport() {
            
            $this->ebh_build_customer();
            
            
            $this->ebh_wc_order_items();
            $this->ebh_wc_shipping();
            $this->ebh_wc_fees();
            $this->ebh_wc_refunds();
            
//               return array(
//                    'ACTION' => $this->_data->mutation_nr ? 'ALTER_MUTATIE' : 'ADD_MUTATIE',
//                    'MUTATIE' => array(
//                        'NAW' => $this->_customer,
//                        'SOORT' => 2,
//                        'REKENING' => 1300,
//                        'INEX' => 'EX',
//                        //'FACTUUR' => sprintf('%08s', $order->id),
//                        
//                        //'FACTUUR' => sprintf('%08s',$rbs_order_number),
//                        'FACTUUR' => $rbs_order_number,
//                        'BETALINGSTERMIJN' => 30,
//                        'DATUM' => date('d-m-Y', strtotime($rbs_order_date)),
//                        'BETALINGSKENMERK' => '#' . (string) $this->_data->ID,
//                        'MUTATIEREGELS' => $this->_mutations
//                    )     
            
         
            

            $ebh_payment_term = $this->Eboekhouden_Settings->ebhGetOption('ebh_payment_term', 30);
            $ebh_order_date = date('d-m-Y', strtotime($this->wc_order->get_date_created()));
            $ebh_order_number = (string) $this->Eboekhouden_Plugins->ebhGetOrderNumber($this->wc_order_id);
            $ebh_payment_reference = '#' . (string) $this->Eboekhouden_Plugins->ebhGetOrderPaymentReference($this->wc_order_id);
            //$ebh_payment_reference = '#' . (string) $this->wc_order_id;
            
            
            
            
            $return = array();
            $return['MUTATIE'] = array (
                'NAW' => $this->ebh_export_customer,
                'SOORT' => 2,
                'REKENING' => 1300,
                'INEX' => 'EX',
                'FACTUUR' => $ebh_order_number,
                'BETALINGSTERMIJN' => $ebh_payment_term,                                       // create setting?
                'DATUM' => $ebh_order_date,
                'BETALINGSKENMERK' => $ebh_payment_reference,
                'MUTATIEREGELS' => $this->ebh_export_mutations
            );

            return apply_filters('ebh_filter_build_export', $return, $this->wc_order_id, $this->wc_order_id);            
        }
    }
    
}