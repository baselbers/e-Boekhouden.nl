<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>    
    
    <h2 class="nav-tab-wrapper">        
        <a href="?page=eboekhouden-settings&tab=general" class="nav-tab <?php echo ($active_tab == 'general') ? 'nav-tab-active' : ''?> "><?php _e('General Options', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-settings&tab=largenumbers" class="nav-tab <?php echo ($active_tab == 'largenumbers') ? 'nav-tab-active' : ''?> "><?php _e('Large Number Options', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-settings&tab=license" class="nav-tab <?php echo ($active_tab == 'license') ? 'nav-tab-active' : ''?> "><?php _e('License', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-settings&tab=advanced" class="nav-tab <?php echo ($active_tab == 'advanced') ? 'nav-tab-active' : ''?> "><?php _e('Advanced', 'eboekhouden'); ?></a>
    </h2>    
    
    <form action="options.php" method="post">
        <?php 
            settings_errors();
            
            //settings_fields('ebh_settings_general');
            //settings_fields('ebh_settings_largenumbers');
            //settings_fields('ebh_settings_license');
            
            if ($active_tab == 'general') {
                settings_fields('ebh_settings_general');
                do_settings_sections('ebh_settings_general'); 
            } elseif ($active_tab == 'largenumbers') {
                settings_fields('ebh_settings_largenumbers');
                do_settings_sections('ebh_settings_largenumbers');                 
            } elseif ($active_tab == 'license') {            
                settings_fields('ebh_settings_license');
                do_settings_sections('ebh_settings_license'); 
            } elseif ($active_tab == 'advanced') {            
                settings_fields('ebh_settings_advanced');
                do_settings_sections('ebh_settings_advanced'); 
            }
            
            submit_button();
        ?>
    </form>
</div>


<?php 
//PREPARED "slider":


//ebh_settings_general[ebh_payment_term] (30 - 60)
 ?>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        
        function ebh_slider_payment_termt() {        
            var input_element = $('input[name="ebh_settings_general[ebh_payment_term]"');
            var handle = $("#custom-handle-ebh_payment_term");
            $('#slider-ebh_payment_term').slider({
                min: 0,
                max: 90,
                step: 1,
                value: input_element.val(),
                create: function() {
                  handle.text( $( this ).slider( "value" ) );
                },
                slide: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                },
                change: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                }            
           });
        }
        
        
        function ebh_slider_order_list() {        
            var input_element = $('input[name="ebh_settings_general[ebh_order_list_max]"');
            var handle = $("#custom-handle-ebh_order_list_max");
            $('#slider-ebh_order_list_max').slider({
                min: 10,
                max: 200,
                step: 10,
                value: input_element.val(),
                create: function() {
                  handle.text( $( this ).slider( "value" ) );
                },
                slide: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                },
                change: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                }            
           });
        }
        
        function ebh_slider_product_list() {        
            var input_element = $('input[name="ebh_settings_general[ebh_product_list_max]"');
            var handle = $("#custom-handle-ebh_product_list_max");
            $('#slider-ebh_product_list_max').slider({
                min: 10,
                max: 200,
                step: 10,
                value: input_element.val(),
                create: function() {
                  handle.text( $( this ).slider( "value" ) );
                },
                slide: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                },
                change: function( event, ui ) {
                  handle.text( ui.value );
                  input_element.val($( this ).slider( "value" ));
                }            
           });
        }        
        
        ebh_slider_payment_termt();
        ebh_slider_order_list();
        ebh_slider_product_list();
    });
</script>

