<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>   
    
<?php if ($assign_largenumbers === false): ?>    
    <h2><?php _e('Products', 'eboekhouden'); ?></h2>

    <h2 class="nav-tab-wrapper">        
        <a href="?page=eboekhouden-products&tab=all" class="nav-tab <?php echo ($active_tab == 'all') ? 'nav-tab-active' : ''?> "><?php _e('All', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-products&tab=no_largenumber" class="nav-tab <?php echo ($active_tab == 'no_largenumber') ? 'nav-tab-active' : ''?> "><?php _e('No Large Number', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-products&tab=has_largenumber" class="nav-tab <?php echo ($active_tab == 'has_largenumber') ? 'nav-tab-active' : ''?> "><?php _e('Has Large Number', 'eboekhouden'); ?></a>        
    </h2>       
    

    <form method="post">
        <?php 
        // Disabled because of wordpress check/compare ($_GET['page'] == $_POST['page']) >>  Check 'usage'  looks like it is NOT used
        /* <input type="hidden" name="page" value="eboekhouden_product_list_table">        */
        ?>
        <?php $product_list->search_box(__('search', 'eboekhouden'), 'ebh_search_product'); ?>
        <?php $product_list->display(); ?>    
    </form>
<?php else: ?>
    <?php echo $assign_largenumbers; ?>
<?php endif; ?>

</div>