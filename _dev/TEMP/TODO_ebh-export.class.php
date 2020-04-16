<?php
// Supports WC version 3.x.x


//require_once('eboekhouden.class.php');


class EboekhoudenExport
{

    static $taxCodes    =   array(
                                'LOW' => 'LAAG_VERK', 'HIGH' => 'HOOG_VERK', 'VHIGH' => 'HOOG_VERK_21',
                                'ZERO_IN_EU' => 'BI_EU_VERK', 'ZERO_OUT_EU' => 'BU_EU_VERK', 'NONE' => 'GEEN'
                            );

    var $_id;
    var $_products = array();
    var $_customer = array();
    var $_mutations = array();
    var $_db;

    var $_data;
    var $_params;
    private $rbs_order_number;

    function __construct()
    {

        $eboek = new EboekhoudenJaagers();
        $this->_params = $eboek->_getInfo();
    }
    


    function setData($data)
    {
        $this->_data = $data;
    }

    function buildExportOrder()
    {

        $this->getData();

        if (!$this->checkValidData()) {
            return false;
        }

        return $this->buildCustomer()->buildProducts();
    }

    function checkValidData()
    {
        $pluginSettings = get_option('ebh_settings');

        $error = new WP_Error();

        if (!$this->_data || !$this->_params) {
            $error->add('ebh', __( "COM_EBOEKHOUDEN_ERROR_ORDER_NO_DATA"));
        }
        if (!isset($pluginSettings['ebh_username']) || !strlen($pluginSettings['ebh_username'])) {
            $error->add('ebh', __( "COM_EBOEKHOUDEN_EXTERNAL_NO_USERNAME"));
        }
        if (!$pluginSettings['ebh_security_code_1'] || !strlen($pluginSettings['ebh_security_code_1'])) {
            $error->add('ebh', __( "COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_1"));
        }
        if (!$pluginSettings['ebh_security_code_2'] || !strlen($pluginSettings['ebh_security_code_2'])) {
            $error->add('ebh', __("COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_2"));
        }

        if (count($error->errors)) {
            return false;
        }

        return true;
    }

    function getTaxes($incl, $excl, $country)
    {
        if (round($incl) == round($excl)) {
            $applied = 0;
            $tax_amount = 0;
        } else {
            // Thanks to Alexander Willemse for this
            $applied = (($incl / $excl) * 100) - 100;
            $tax_amount = round($incl - $excl, 2);
        }

        if ($applied) {
            if (intval($applied) < 12) {
                $code = self::$taxCodes['LOW'];
            } else {
                if(intval($applied) < 20) {
                    $code = self::$taxCodes['HIGH'];
                } else {
                    $code = self::$taxCodes['VHIGH'];
                }
            }
        } else {
            if (in_array(strtoupper($country), self::$euArray)) {
                if (strtoupper($country) != 'NL' && strtoupper($country) != 'NLD') {
                    $params = JComponentHelper::getParams('com_eboekhouden');
                    $c = $params->get('user_field');
                    if (isset($shipping->$c) && $shipping->$c) {
                        //$this->external_data->tax_code = self::$taxCodes['ZERO_IN_EU'];
                        $code = self::$taxCodes['ZERO_IN_EU'];
                    } else {
                        //$this->external_data->tax_code = self::$taxCodes['NONE'];
                        $code = self::$taxCodes['NONE'];
                    }
                } else {
                    $code = self::$taxCodes['NONE'];
                }
            } else {
                $code = self::$taxCodes['ZERO_OUT_EU'];
            }
        }
        return array('inclusive' => $incl, 'exclusive' => $excl, 'applied' => $applied,
            'tax_amount' => $tax_amount, 'code' => $code, 'country' => $country);
    }

    function getExportOrder($type = 'array') {
        //$order = new WC_Order($this->_data->ID);
        $order = wc_get_order($this->_data->ID);
        
        //$rbs_order_id = $order->get_id();
        $rbs_order_date = $order->get_date_created();
        
        /*** Check 'how to' format the OrderNumber: ***/
        if (class_exists('WC_Sequential_Order_Numbers') or function_exists('init_woocommerce_sequential_order_numbers_pro')) {
            $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
        } elseif (class_exists('WPO_WCPDF')) {
            $invoice = wcpdf_get_invoice( $order );            
            if ( $invoice_number = $invoice->get_number() ) {
                $rbs_order_number = $invoice_number->get_formatted();
            }
        } elseif (class_exists('WooCommerce_PDF_Invoices') ) {
            $wpo_wcpdf = new WooCommerce_PDF_Invoices();  
            $wpo_wcpdf->load_classes();
            $rbs_order_number = $wpo_wcpdf->export->get_invoice_number($order->id);
        } else {
            $rbs_order_number = apply_filters( 'woocommerce_order_number', $order->id, $order );
            $rbs_order_number = sprintf('%08d',$rbs_order_number);
        }
        
        /*** Return 'default' OrderNumber as 'fallback' (When not set) ***/
        if (is_string($rbs_order_number) && strlen($rbs_order_number) == 0) {
            $rbs_order_number = $order->get_order_number();
        }

        switch (strtolower($type)) {
            case 'array':
                return array(
                    'ACTION' => $this->_data->mutation_nr ? 'ALTER_MUTATIE' : 'ADD_MUTATIE',
                    'MUTATIE' => array(
                        'NAW' => $this->_customer,
                        'SOORT' => 2,
                        'REKENING' => 1300,
                        'INEX' => 'EX',
                        //'FACTUUR' => sprintf('%08s', $order->id),
                        
                        //'FACTUUR' => sprintf('%08s',$rbs_order_number),
                        'FACTUUR' => $rbs_order_number,
                        'BETALINGSTERMIJN' => 30,
                        'DATUM' => date('d-m-Y', strtotime($rbs_order_date)),
                        'BETALINGSKENMERK' => '#' . (string) $this->_data->ID,
                        'MUTATIEREGELS' => $this->_mutations
                    )
                );
                break;
        }
    }

    protected function buildCustomer()
    {
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

        //var_dump($this->_data);exit;

        $order = new WC_Order($this->_data->ID);
        
        $this->_customer = array(
            'BEDRIJF'   =>  (strlen($order->get_billing_company()) > 0) ? $order->get_billing_company()  : implode(' ', array($order->get_billing_first_name(), $order->get_billing_last_name())),
            'ADRES'     =>  implode(' ', array($order->get_billing_address_1(), $order->get_billing_address_2())),
            'POSTCODE'  =>  $order->get_billing_postcode(),
            'PLAATS'    =>  $order->get_billing_city(),
            'TELEFOON'  =>  $order->get_billing_phone(), // RBS 260117
            'EMAIL'     =>  $order->get_billing_email() // RBS 260117
            );   

        return $this;
    }

    protected function buildProducts()
    {

        $pluginSettings = get_option('ebh_settings');
        
   

        $order = new WC_Order($this->_data->ID);

        

        $orderDiscount = $order->get_total_discount();
        


        $orderShipping = $order->get_total_shipping();
        $orderShippingTax = $order->get_shipping_tax();
        // rbs added refunds    
        $orderRefunds = $order->get_refunds();
       
        
 
        
//        echo count($refunds);
//       echo print_r($refunds);die();
//        echo '<hr>';
        

        
        
        
        
//        echo $orderShippingTax;
//        die();
//        echo 'ordershipping' . $orderShipping . '<br>';
//        echo 'ordershippingtax' . $orderShippingTax . '<br>';
        
       
//        echo 'ordershipping:' . $orderShipping . '<br>';
//        echo 'ordershippingtax:' . $orderShippingTax;
//        die();
        // FIX RBS
//        if ($orderShipping > 0 ) {        
//
//        	$shippingTaxRate = WC_Tax::get_shipping_tax_rates();
////      echo print_r($shippingTaxRate);  
////die();                
//
//        	$shippingTaxRate = array_shift($shippingTaxRate);
//        }
// die();
        $orderItems = $order->get_items();
        
   
        
//        
//        
//        
//        
//        
//        
//        
//        
//        echo print_r($orderItems);
//        die('qqqq');
        
       // foreach ($orderItems as $ordit) {
            
           // echo $ordit['name'] . ': ' . 'btw:' . $ordit['item_meta']['_line_tax'][0] . 'Linetotal' . $ordit['item_meta']['_line_total'][0] .'<br>';
//            echo print_r($ordit['item_meta']);
//            die();
            
        //}
        //echo '<hr>' . 'korting' . $orderDiscount;
        //die();
//        foreach ($orderItems as $key => $value) {
//            //echo $key;
//            echo '<hr>';
//            echo $value['name'] . '<br>';
//            $cart_tax_classes = WC()->cart->get_cart_item_tax_classes();
//            
//            var_dump($value['item_meta']);
//        } 
//        die();

        /*var_dump($order->shipping_country);

        taxArray = eBoekhoudenHelperExternal::getTaxes(floatval($this->_data->
            order_shipment_tax) + floatval($this->_data->order_shipment), floatval($this->
        _data->order_shipment), $country);*/

        $_tax = new WC_Tax();//looking for appropriate vat for specific product

        foreach($orderItems as $orderItem) {
            //echo $orderItem->get_id();
            $item_data = $orderItem->get_data();
//            
//            echo print_r($item_data);
//            die();

//

            //echo print_r($orderItem['tax_class']);
            
            $tax_class = $orderItem['tax_class'];


            $rates = $_tax->get_rates( $tax_class );
            
            
//            echo print_r($rates);
//            die();

            $rate = current($rates);



            $orderItemProduct = new WC_Product($orderItem['product_id']);

            $largenumber = $orderItemProduct->get_attribute( 'large_number' );

            
            
            if(strlen($orderItemProduct->get_sale_price()) && $orderItemProduct->get_sale_price() < $orderItemProduct->get_regular_price()) {
                // do something with discount
            }
            
            
           $row_total = (float) $item_data['total'];
           $row_sub_total = (float) $item_data['subtotal'];
           $row_quantity = (float) $item_data['quantity'];
//           $row_discount = ($row_sub_total - $row_total) * $row_quantity;
           
//           echo 'rt' . $row_total . '<br>';
//           echo 'rst' . $row_sub_total . '<br>';
//           echo 'rq' . $row_quantity . '<br>';
//           echo 'rd' . $row_discount;

      
           
           
            // lower discount
            if ( $row_total < $row_sub_total) {
//                echo 'od' . $orderDiscount;
                $orderDiscount = $orderDiscount - (($row_sub_total - $row_total));
//                echo 'od' . $orderDiscount;die();
            };
      

            $mutation = new EboekhoudenMutation();
            

            $mutation->SetTotal($item_data['total'] + $item_data['total_tax']);
            $mutation->SetSubTotal($item_data['total']);
            $mutation->SetTaxTotal($item_data['total_tax']);
            $mutation->SetLargeNumber($largenumber);
            $mutation->SetTaxPercent((isset($rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rate['label']] : '');
            
            $this->_mutations[] = $mutation->GetMutation();
            
   
            
            
            

            
            
//            $this->_mutations[] = array(
//                'MUTATIEREGEL' =>
//                    array(
//                        'BEDRAGINCL'    =>  round($item_data['subtotal'] + $item_data['total_tax'], 2),
//                        'BEDRAGEXCL'    =>  round($item_data['subtotal'], 2),
//                        'BTWBEDRAG'     =>  round($item_data['total_tax'], 2),
//                        'TEGENREKENING' =>  $largenumber, //TODO
//                        'BTWPERC'       =>  (isset($rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rate['label']] : '' //TODO
//                    )
//               
//            );
            
            
        }
        
//                 echo print_r($this->_mutations);
//            die('zzz');

     
        // rbs added refund mutations
        foreach ($orderRefunds as $refund) {
            //$refund_data_item = $refund->get_data();
            //echo print_r($refund_data_item);
            
    

            
//             
            if (count($refund->get_items()) != 0) {
                foreach ($refund->get_items() as $refund_item) {
                    
                   

                    $rf_tax_class = $refund_item['tax_class'];

                    $rf_rates = $_tax->get_rates( $rf_tax_class );
                    
                    $rf_rate = current($rf_rates);

                    $rf_orderItemProduct = new WC_Product($refund_item['product_id']);

                    $rf_largenumber = $rf_orderItemProduct->get_attribute( 'large_number' );                

                    $mutation = new EboekhoudenMutation();

                    $mutation->SetTotal(get_post_meta($refund->id,'_order_total',true));
                    $mutation->SetSubTotal(get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true));
                    $mutation->SetTaxTotal(get_post_meta($refund->id,'_order_tax',true));
                    //$mutation->SetLargeNumber($rf_largenumber);
                    $mutation->SetLargeNumber(isset($pluginSettings['ebh_refund_largenumber']) ? $pluginSettings['ebh_refund_largenumber'] : '');
                    $mutation->SetTaxPercent((isset($rf_rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rf_rate['label']] : '');

                    $this->_mutations[] = $mutation->GetMutation(); 
                    
//                    $this->_mutations[] = array(
//                        'MUTATIEREGEL' =>
//                            array(
//                                'BEDRAGINCL'    =>  get_post_meta($refund->id,'_order_total',true),
//                                'BEDRAGEXCL'    =>  get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true),
//                                'BTWBEDRAG'     =>  get_post_meta($refund->id,'_order_tax',true),
//                                'TEGENREKENING' =>  $rf_largenumber, //TODO
//                                'BTWPERC'       =>  (isset($rf_rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rf_rate['label']] : '' //TODO
//                            )
//
//                    );                  

                }
            } else {
                    $refund_data_item = $refund->get_data();
//                    echo $refund_data_item['total']; 
//                    echo print_r($refund_data_item);
//                    die('qqqq');
                    
                    
                    $mutation = new EboekhoudenMutation();

                    $mutation->SetTotal(get_post_meta($refund->id,'_order_total',true));
                    $mutation->SetSubTotal(get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true));
                    $mutation->SetTaxTotal(get_post_meta($refund->id,'_order_tax',true));
//                    $mutation->SetLargeNumber($rf_largenumber);
                    $mutation->SetLargeNumber(isset($pluginSettings['ebh_refund_largenumber']) ? $pluginSettings['ebh_refund_largenumber'] : '');                    
                    $mutation->SetTaxPercent((isset($rf_rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rf_rate['label']] : '');
                    
                    $this->_mutations[] = $mutation->GetMutation(); 
                    
//                    $this->_mutations[] = array(
//                        'MUTATIEREGEL' =>
//                            array(
//                                'BEDRAGINCL'    =>  get_post_meta($refund->id,'_order_total',true),
//                                'BEDRAGEXCL'    =>  get_post_meta($refund->id,'_order_total',true) - get_post_meta($refund->id,'_order_tax',true),
//                                'BTWBEDRAG'     =>  get_post_meta($refund->id,'_order_tax',true),
//                                'TEGENREKENING' =>  $rf_largenumber, //TODO
//                                'BTWPERC'       =>  (isset($rf_rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rf_rate['label']] : '' //TODO
//                            )
//
//                    );                 
                
            }
            
//            echo print_r($this->_mutations);
//            die('qqqqqq');

            
            
            
            
            
            
         
           
           
        }        
//echo print_r($this->_mutations);
// die();
        
//        $mutations = $this->_mutations;
//        foreach ($mutations as $mut_item) {
//
//            echo 'Bedrag incl.' . $mut_item['MUTATIEREGEL']['BEDRAGINCL'] . '<br>';
//            echo 'Bedrag excl.' . $mut_item['MUTATIEREGEL']['BEDRAGEXCL'] . '<br>';
//            echo 'BTW.' . $mut_item['MUTATIEREGEL']['BTWBEDRAG'] . '<br>';
//            echo '<hr>';
//            
//            
//        }
//        die();  
        // RBS FIX 28-10-15
        //$orderDiscount = -0.00;
        
//        if (round($orderDiscount, 2) < 0) {
//            die('Negatieve korting ingevoerd in order!');
//        }
        
        

        // rbs change 220517


        if($orderShipping > 0) {
            
            $mutation = new EboekhoudenMutation();

            $mutation->SetTotal($orderShipping + $orderShippingTax);
            $mutation->SetSubTotal($orderShipping);
            $mutation->SetTaxTotal($orderShippingTax);
            $mutation->SetLargeNumber($pluginSettings['ebh_shippingcost_largenumber']);
            $mutation->SetTaxPercent(EboekhoudenJaagers::$taxCodes[$shippingTaxRate['label']]);
            
            $this->_mutations[] = $mutation->GetMutation();


//            $this->_mutations[] = array(
//                'MUTATIEREGEL' =>
//                    array(
//                        'BEDRAGINCL'    =>  round($orderShipping + $orderShippingTax, 2),
//                        'BEDRAGEXCL'    =>  round($orderShipping, 2),
//                        'BTWBEDRAG'     =>  round($orderShippingTax, 2),
//                        'TEGENREKENING' =>  $pluginSettings['ebh_shippingcost_largenumber'],
//                        'BTWPERC'       =>  EboekhoudenJaagers::$taxCodes[$shippingTaxRate['label']] //TODO
//                    )
//            );
        }
        
        $fees = $order->get_fees();

     

        if (count($fees) > 0 ) {
 
            foreach ($fees as $key => $value) {
                

                //$order_item_meta = $order->has_meta($key);

                $fee_mutation = array(
                   'BEDRAGINCL' => 0,
                   'BEDRAGEXCL' => 0,
                   'BTWBEDRAG' => 0,
                   'TEGENREKENING' =>  $pluginSettings['ebh_paymentcost_largenumber'],
//                   'BTWPERC' => EboekhoudenJaagers::$taxCodes[$rate['label']]
                    'BTWPERC' => (isset($rate['label'])) ? EboekhoudenJaagers::$taxCodes[$rate['label']] : ''
                       
                    
                );
                
//                foreach ($order_item_meta as $order_item_meta_key => $order_item_meta_value) {
//                    
////                    echo print_r($value);
////                    die();
//                    
//                    if ($order_item_meta_value['meta_key'] === '_line_total') {
//                        $line_total = $order_item_meta_value['meta_value'];
//                        
//                    } elseif ($order_item_meta_value['meta_key'] === '_line_tax') {
//                        
//                        $btw_bedrag = $order_item_meta_value['meta_value'];
//                    }    
//
//                    
//                    
//                }

                $line_total = $value->get_total();
                $btw_bedrag = $value->get_total_tax();

                $mutation = new EboekhoudenMutation();

                $mutation->SetTotal($line_total + $btw_bedrag);
                $mutation->SetSubTotal($line_total);
                $mutation->SetTaxTotal($btw_bedrag);
                $mutation->SetLargeNumber(isset($pluginSettings['ebh_paymentcost_largenumber']) ? $pluginSettings['ebh_paymentcost_largenumber'] : '');
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
                
                $this->_mutations[] = $mutation->GetMutation(true);    
                
                
                
            }

//            die();
            
        }
    
        
//        $all_mutations = $this->_mutations;
//        
//        foreach ($all_mutations as $single_mutation) {
//            echo print_r($single_mutation);
//            echo '<hr>';
//        }
//        die();

        
        
        return $this;

        /*// Somewhere this get's called twice, cba to find it...
        if (!empty($this->_mutations)) {
            return $this;
        }
        if(JRequest::getVar('conf_debug') && JRequest::getVar('debug_id') == 7) {
            var_dump($this->_products);die();
        }
        foreach ($this->_products as $i) {
            $d = $i->getTaxes($this->_data->order_id); // Setup taxes info
            $this->_mutations[] = array('MUTATIEREGEL' => array('BEDRAGINCL' => round($d->
                incl * $d->quantity, 2), 'BEDRAGEXCL' => round($d->excl * $d->quantity, 2),
                'BTWBEDRAG' => round($d->tax_amount * $d->quantity, 2), 'TEGENREKENING' => $i->
                getLargeNumber(), 'BTWPERC' => $d->tax_code));
        }

        // Add shipping mutation and order discount
        $q = eBoekhoudenHelperExternal::getExternalQuery('order.shipping', '',
            'WHERE a.virtuemart_order_id=' . $this->_data->virtuemart_order_id);
        $this->_db->setQuery($q);
        $this->_db->query();


        $shipping = $this->_db->loadObject();
        $country = $shipping->country_3_code;

        $taxArray = eBoekhoudenHelperExternal::getTaxes(floatval($this->_data->
            order_shipment_tax) + floatval($this->_data->order_shipment), floatval($this->
        _data->order_shipment), $country);

        $this->_mutations[] = array('MUTATIEREGEL' => array('BEDRAGINCL' => round($taxArray['inclusive'],
            2), 'BEDRAGEXCL' => round($taxArray['exclusive'], 2), 'BTWBEDRAG' => round($taxArray['tax_amount'],
            2), 'TEGENREKENING' => $this->params->get('shipping_number'), 'BTWPERC' => $taxArray['code']));

        if ($this->_data->order_discount != '0.00' || $this->_data->coupon_discount !=
            '0.00') {

            $this->_data->coupon_discount = $this->_data->coupon_discount * -1;
            $this->_data->order_discount = $this->_data->order_discount * -1;

            $amount = floatval($this->_data->coupon_discount) + floatval($this->_data->
                order_discount);

            $this->_mutations[] = array('MUTATIEREGEL' => array('BEDRAGINCL' => (string )
            round($amount, 2), 'BEDRAGEXCL' => (string )round($amount, 2), 'TEGENREKENING' =>
                $this->params->get('discount_number'), 'BTWPERC' => eBoekhoudenHelperExternal::
            $taxCodes['NONE']));
        }

        return $this;*/
    }

    protected function getData()
    {
        //print_r($this->_data);
		//echo $this->_data;
        if (isset($this->_data) && count($this->_data)) {
            return $this->_data;
        }
        return false;
    }

}
