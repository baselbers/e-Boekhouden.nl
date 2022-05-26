<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div id="ebh-help-filters">

    <h3><?php _e('Available Hooks', 'eboekhouden');?></h3>

    <div id="ebh-hooks">
        <h4>
            <span class="ebh-list-label">Name:</span>
            ebh_filter_build_export
        </h4>                        
        <div>
            <ul class="ebh-hook-help">              
                <li>
                    <span class="ebh-list-label">Description:</span>
                    Change 'export' data
                </li>
                <li>
                    <span class="ebh-list-label">Type:</span>
                    Filter
                </li>                
                <li>
                    <span class="ebh-list-label">Params:</span>
                     $mutation, $order_id, $order
                </li>
                <li>
                    apply_filters('ebh_filter_build_export', $return, $this->wc_order_id, $this->wc_order_id);
                </li>                    
            </ul>
        </div>


        <h4>
            <span class="ebh-list-label">Name:</span>
            ebh_filter_build_customer
        </h4>
        <div>
            <ul class="ebh-hook-help">
                <li>
                    <span class="ebh-list-label">Description:</span>
                    Change 'customer' data
                </li>
                <li>
                    <span class="ebh-list-label">Type:</span>
                    Filter
                </li>                
                <li>
                    <span class="ebh-list-label">Params:</span>
                     $customer, $order_id, $order
                </li>
                <li>
                    apply_filters('ebh_filter_build_customer', $customer, $this->wc_order_id, $this->wc_order);
                </li>                    
            </ul>
        </div>


        <h4>
            <span class="ebh-list-label">Name:</span>
            ebh_filter_mutation_{mutation_type}
        </h4>
        <div>
            <ul class="ebh-hook-help">
                <li>
                    <span class="ebh-list-label">Mutation types:</span>
                    orderitems, fees, shipping, ...
                </li>
                <li>
                    <span class="ebh-list-label">Description:</span>
                    Change 'mutation' data
                </li>
                <li>
                    <span class="ebh-list-label">Type:</span>
                    Filter
                </li>                
                <li>
                    <span class="ebh-list-label">Params:</span>
                     $mutation
                </li>
                <li>
                     apply_filters('ebh_filter_mutation_{' . $this->mutation_type . '}',  $mutation);
                </li>                    
            </ul>
        </div>


        <h4>
            <span class="ebh-list-label">Name:</span>
            ebh_filter_order_number
        </h4>     
        <div>
            <ul class="ebh-hook-help">                  
                <li>
                    <span class="ebh-list-label">Description:</span>
                    Change 'order number'
                </li>
                <li>
                    <span class="ebh-list-label">Type:</span>
                    Filter
                </li>                
                <li>
                    <span class="ebh-list-label">Params:</span>
                    $order_number, $order_id, $wc_order, $ebh_plugin_ordernumber
                </li>
                <li>
                    apply_filters('ebh_filter_order_number', $order_number, $order_id, $wc_order, $ebh_plugin_ordernumber);
                </li>                    
            </ul>
        </div>


        <h4>
            <span class="ebh-list-label">Name:</span>
            ebh_filter_payment_reference_number
        </h4>     
        <div>
            <ul class="ebh-hook-help">               
                <li>
                    <span class="ebh-list-label">Description:</span>
                    Change 'order reference number'
                </li>
                <li>
                    <span class="ebh-list-label">Type:</span>
                    Filter
                </li>                
                <li>
                    <span class="ebh-list-label">Params:</span>
                    $reference_number, $order_id, $wc_order, $ebh_plugin_payment_reference
                </li>
                <li>
                    apply_filters('ebh_filter_payment_reference_number', $reference_number, $order_id, $wc_order, $ebh_plugin_payment_reference);
                </li>                    
            </ul>
        </div>

    </div>    
    
</div>