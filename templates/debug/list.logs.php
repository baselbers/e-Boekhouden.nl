<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>

    <?php if (isset($logs) && count($logs) != 0): ?>
    <div id="ebh-logs">
        
        <p>
            <?php _e('Download log file and email this to: support@e-Boekhouden.nl', 'eboekhouden'); ?>
        </p>
        
        <?php foreach($logs as $directory => $files): ?>
        <h4>
            <span class="ebh-list-label">Log: </span>            
            <?php echo date_create_from_format('Ymd', $directory)->format('l j F Y'); ?>
            [<?php echo $directory; ?>]
            
            <a href="admin.php?page=eboekhouden-logs&delete=<?php echo $directory;?>" class="ebh-log-directory-delete"><?php _e('Delete', 'eboekhouden');?></a>
            <a href="admin.php?page=eboekhouden-logs&download=<?php echo $directory;?>" class="ebh-log-directory-download" target="_blank"><?php _e('Download', 'eboekhouden'); ?></a>
            
        </h4>                        
        <div>
            
            <ul class="ebh-date-logs">      
                <?php foreach($files as $file): ?>
                <li>
                    <span class="ebh-list-label"><?php echo basename($file, '.log'); ?></span>
                    <a href="admin.php?page=eboekhouden-logs&view=<?php echo $directory . ';' . basename($file);?>" target="_blank">
                        <?php echo $file; ?>
                    </a>                    
                </li>    
                <?php endforeach; ?>
            </ul>
            
        </div>    
        <?php endforeach; ?>
    
    </div>    
    <?php endif; ?>
