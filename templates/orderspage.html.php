<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>   
    
    <h2><?php _e('Orders', 'eboekhouden'); ?></h2>

    <h2 class="nav-tab-wrapper">        
        <a href="?page=eboekhouden-orders&tab=not_mutated" class="nav-tab <?php echo ($active_tab == 'not_mutated') ? 'nav-tab-active' : ''?> "><?php _e('Not mutated', 'eboekhouden'); echo ' (' . $order_count['not_mutated'] .')'; ?></a>
        <a href="?page=eboekhouden-orders&tab=mutated" class="nav-tab <?php echo ($active_tab == 'mutated') ? 'nav-tab-active' : ''?> "><?php _e('Mutated', 'eboekhouden'); echo ' (' . $order_count['mutated'] .')'; ?></a>
        <a href="?page=eboekhouden-orders&tab=all" class="nav-tab <?php echo ($active_tab == 'all') ? 'nav-tab-active' : ''?> "><?php _e('All', 'eboekhouden'); echo ' (' . $order_count['all'] .')'; ?></a>
    </h2>       
    
    <form method="post">
        <?php 
            // Disabled because of wordpress check/compare ($_GET['page'] == $_POST['page']) >>  Check 'usage'  looks like it is NOT used
            /*<input type="hidden" name="page" value="eboekhouden_order_list_table">   */ 
        ?>
        <?php $order_list->search_box(__('Search order', 'eboekhouden'), 'ebh_search_order'); ?>
        <?php $order_list->display(); ?>
    </form>

</div>