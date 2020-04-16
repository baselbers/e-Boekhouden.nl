<?php defined('EBH_TEMPLATE') or wp_die('No access!'); ?>
<div id="ebh-help-info">

    <div id="ebh-help-info-plugin">
        <h3><?php _e('Readme.txt', 'eboekhouden');?></h3>
        <?php echo nl2br(file_get_contents(EBOEKHOUDEN_DIR . 'readme.txt')); ?>
    </div>
    
    <div id="ebh-help-info-contact">
        <h3><?php _e('Contact', 'eboekhouden');?></h3>
        <ul class="ebh-contact">
            <li>
                <span class="ebh-list-label">Adres:</span>
                Kanaaldijk 2a <br>
                5735 SL Aarle-Rixtel<br>
            </li>

            <li>
                <span class="ebh-list-label">Telefoon:</span>
                Tel: 088 - 6500 200<br>
                Fax: 088 - 6500 210<br>
            </li>

            <li>
                <span class="ebh-list-label">E-mail:</span>
                Helpdesk: support@e-Boekhouden.nl<br>
                Sales: sales@e-Boekhouden.nl<br>
                Overig: info@e-Boekhouden.nl<br>
            </li>        

        </ul>
    </div>

    <div id="ebh-help-info-links">
        <h3><?php _e('Links', 'eboekhouden');?></h3>
        <ul class="ebh-links">
            <li>
                <a href="https://www.e-boekhouden.nl/contact" target="_blank">Contact</a>
            </li>
            <li>
                <a href="https://www.e-boekhouden.nl/contactgegevens" target="_blank">Contact Gegevens</a>
            </li>
            <li>
                <a href="https://www.e-boekhouden.nl/veelgestelde-vragen" target="_blank">Veel Gestelde Vragen</a>
            </li>    
        </ul>
    </div>

</div>