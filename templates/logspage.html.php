<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div class="ebh-wrap">
    <div id="ebh-logo-container">
        <img id="ebh-logo-image" src="<?php echo EBOEKHOUDEN_URL_ASSETS . 'images/ebh_logo.png'?>" alt="e-Boekhouden" title="e-Boekhouden" />
    </div>

    <h2><?php _e('Logs', 'eboekhouden');?></h2>
<?php /*    
    TODO: <br>
    - check 'sorting' (lastest first)<br>
    - view "button"<br>
    - download "button" (zip)<br>
    - delete<br>
*/ ?>    
    
    <?php 
        if (isset($logs)) {
            require_once 'debug' . DIRECTORY_SEPARATOR . 'list.logs.php';
        } elseif (isset($view)) {
            require_once 'debug' . DIRECTORY_SEPARATOR . 'view.logs.php';
        }
    ?>
    
</div>




