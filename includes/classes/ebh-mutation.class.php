<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/**
 * @file: eboekhouden-mutation.class.php
 * @description: EBoekhouden Mutatie Object-Class:
 * @version: 1.0.0
 * 
 */

/**
 * @todo:
 * - [ ] ? Add 'auto' Calculation
 *      - [ ] $total = $sub_total + $total_tax                                  (default?)
 *      - [ ] $sub_total = $total - $total_tax
 *      - [ ] $total = $total * -1 AND $sub_total * -1                          ("negative" / "invert")
 *      - [ ] NO calculation                                                    (1 on 1 value parsing)
 * - [ ] ? Add 'mutation - types' 
 *      >> Based on 'type' value(s) can be calculated automatically
 *      - [ ] 'Normal'
 *      - [ ] 'Refund'
 *      - [ ] ? 'Discount'
 *      - [ ] ? 'Free'      (maybe same as 'normal')
 * - [ ] ? Add 'decimal'  
 * - [ ] ? Modify of 'Mutation Array Keys'
 * - [ ] ? Get values from 'get_post_meta()'
 *      >> $post_id
 *      >> Config of: 'meta_key' names
 * - [ ] 'Getters' for all 'values'
 * - [ ] 'Class' options:
 *      - [ ] Round values      (bool)
 *      - [ ] 
 * - [ ] Add (error) message to 'GetMutation()'
 *      >> ex: if NOT all (required) values are set
 * - [ ] Set 'empty' value replacements
 *      >> ex: if value is empty (or 0) then 'return' replacement value 
 * - [ ] 
 */

if (!class_exists('Eboekhouden_Mutation')) {

    class Eboekhouden_Mutation {

        private $mutation_type;
        private $mutation_order_id;

        private $round;

        private $total;
        private $sub_total;
        private $total_tax;
        private $largenumber;
        private $tax_percent;



        function __construct($type, $order_id, $total = null, $sub_total = null, $total_tax = null, $largenumber = null, $tax_percent = null) {
            $this->round = 2;

            $this->SetMutationType($type);
            $this->SetMutationOrderID($order_id);            
            
            $this->SetTotal($total);
            $this->SetSubTotal($sub_total);
            $this->SetTaxTotal($total_tax);
            $this->SetLargeNumber($largenumber);
            $this->SetTaxPercent($tax_percent);
        }       


        public function SetMutationType($type) {
            $this->mutation_type = $type;
        }
        
        public function SetMutationOrderID($order_id) {
            $this->mutation_order_id = $order_id;
        }
        

        public function SetTotal($total) {
            $this->total = $total;
        }

        public function SetSubTotal($sub_total) {
            $this->sub_total = $sub_total;
        }

        public function SetTaxTotal($total_tax) {
            $this->total_tax = $total_tax;
        }

        public function SetLargeNumber($largenumber) {
            $this->largenumber = $largenumber;
        }    

        public function SetTaxPercent($tax_percent) {
            $this->tax_percent = $tax_percent;
        }



    // ??? $mutation_type (used for calculation $calculate = true, $round = true    
        public function GetMutation($remove_empty = false) {
            $mutation = array();
            $mutation['MUTATIEREGEL']['BEDRAGINCL'] = round($this->total, $this->round);
            $mutation['MUTATIEREGEL']['BEDRAGEXCL'] = round($this->sub_total, $this->round);
            $mutation['MUTATIEREGEL']['BTWBEDRAG'] = round($this->total_tax, $this->round);
            $mutation['MUTATIEREGEL']['TEGENREKENING'] = $this->largenumber;
            $mutation['MUTATIEREGEL']['BTWPERC'] = $this->tax_percent;

            
            $log = array();
            $log['mutation_type'] = $this->mutation_type;
            $log['mutation_order_id'] = $this->mutation_order_id;
            $log['mutation'] = $mutation;
            if ($remove_empty) {
                $log['mutation_removed'] = 'Empty mutation';
            }
            ebh_debug_message($log, 'Eboekhouden_Mutation', 'GetMutation', 'mutation.log');            
            
            // Remove 'empty' values from array:
            if ($remove_empty) {
                foreach($mutation['MUTATIEREGEL'] as $rule=>$value) {
                    if (!isset($value)) {
                        unset($mutation['MUTATIEREGEL'][$rule]);
                    }
                }
            }
            


            return apply_filters('ebh_filter_mutation_{' . $this->mutation_type . '}',  $mutation);
        }

    }


}