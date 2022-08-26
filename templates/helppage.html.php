<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>

    <h2><?php _e('Help', 'eboekhouden');?></h2>
    
    <h2 class="nav-tab-wrapper">        
        <a href="?page=eboekhouden-help&tab=info" class="nav-tab <?php echo ($active_tab == 'info') ? 'nav-tab-active' : ''?> "><?php _e('Information', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-help&tab=filters" class="nav-tab <?php echo ($active_tab == 'filters') ? 'nav-tab-active' : ''?> "><?php _e('Filters (hooks)', 'eboekhouden'); ?></a>
        <a href="?page=eboekhouden-help&tab=plugins" class="nav-tab <?php echo ($active_tab == 'plugins') ? 'nav-tab-active' : ''?> "><?php _e('Plugins', 'eboekhouden'); ?></a>
    </h2>    
    
    <?php 
        if ($active_tab == 'info') {
            require_once 'help' . DIRECTORY_SEPARATOR . 'info.help.php';
        } elseif ($active_tab == 'filters') {
            require_once 'help' . DIRECTORY_SEPARATOR . 'filters.help.php';
        } elseif ($active_tab == 'plugins') {
            require_once 'help' . DIRECTORY_SEPARATOR . 'plugins.help.php';
        }
    ?>    
    
</div>

