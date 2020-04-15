<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>

<div class="wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>   
    
    <h2><?php _e('Products', 'eboekhouden'); ?></h2>
    
    <form method="post">
    <input type="hidden" name="page" value="eboekhouden_product_list_table">

    <ul class="subsubsub">
        <li class="not-mutated"><a href="<?php echo $base_url;?>&status=not_mutated" class="<?php echo ($eb_order_status && $eb_order_status == 'not_mutated') ? 'current' : '';?>">
            <?php _e('Not mutated', 'eboekhouden'); ?> <span class="count">(<?php echo $ebOrderCount['not_mutated'];?>)</span></a> |
        </li>                
        <li class="not-mutated"><a href="<?php echo $base_url;?>&status=mutated" class="<?php echo ($eb_order_status && $eb_order_status == 'mutated') ? 'current' : '';?>">
           <?php _e('Mutated', 'eboekhouden'); ?> <span class="count">(<?php echo $ebOrderCount['mutated'];?>)</span></a> |
        </li>                      
        <li class="all"><a href="<?php echo $base_url;?>&status=all" class="<?php echo ($eb_order_status && $eb_order_status == 'all') ? 'current' : '';?>">
            <?php _e('All', 'eboekhouden'); ?> <span class="count">(<?php echo $ebOrderCount['all'];?>)</span></a>
        </li>
    </ul> 

    <?php $orderListTable->display(); ?>

    </form>

</div>


<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>   
    
    <h2><?php _e('Products', 'eboekhouden'); ?></h2>


    <h2 class="nav-tab-wrapper">        
        <a href="?page=eboekhouden-orders&tab=not-mutated" class="nav-tab <?php echo ($active_tab == 'not-mutated') ? 'nav-tab-active' : ''?> "><?php _e('Not mutated', 'eboekouden'); ?></a>
        <a href="?page=eboekhouden-orders&tab=mutated" class="nav-tab <?php echo ($active_tab == 'mutated') ? 'nav-tab-active' : ''?> "><?php _e('Mutated', 'eboekouden'); ?></a>
        <a href="?page=eboekhouden-orders&tab=all" class="nav-tab <?php echo ($active_tab == 'all') ? 'nav-tab-active' : ''?> "><?php _e('All', 'eboekouden'); ?></a>
    </h2>       

<?php /*    
    <form method="post">
        <input type="hidden" name="page" value="eboekhouden_order_list_table">

        <ul class="subsubsub">
            <li class="not-mutated"><a href="<?php echo $base_url;?>&status=not_mutated" class="<?php echo ($order_status && $order_status == 'not_mutated') ? 'current' : '';?>">
                <?php _e('Not mutated', 'eboekhouden'); ?> <span class="count">(<?php echo $order_count['not_mutated'];?>)</span></a> |
            </li>                
            <li class="not-mutated"><a href="<?php echo $base_url;?>&status=mutated" class="<?php echo ($order_status && $order_status == 'mutated') ? 'current' : '';?>">
               <?php _e('Mutated', 'eboekhouden'); ?> <span class="count">(<?php echo $order_count['mutated'];?>)</span></a> |
            </li>                      
            <li class="all"><a href="<?php echo $base_url;?>&status=all" class="<?php echo ($order_status && $order_status == 'all') ? 'current' : '';?>">
                <?php _e('All', 'eboekhouden'); ?> <span class="count">(<?php echo $order_count['all'];?>)</span></a>
            </li>
        </ul> 



    </form>
*/?>    
        <?php $order_list->display(); ?>    

</div>