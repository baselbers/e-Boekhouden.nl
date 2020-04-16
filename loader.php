<?php
if (!function_exists('is_admin') || !defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
} 

/** Load "core classes": */
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-debug.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-largenumbers.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-database.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-plugins.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-settings.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-menus.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-connector.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-orders.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-mutation.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-taxes.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-export.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-xml.class.php';
require_once EBOEKHOUDEN_DIR_CLASSES . 'ebh-session.class.php';

/** Load "functions": */
require_once EBOEKHOUDEN_DIR_INCLUDES . 'ebh-functions.php';


/** Load "woocommerce classes / functions": */
require_once EBOEKHOUDEN_WC_FOLDER . 'ebh-orderlist.class.php';
require_once EBOEKHOUDEN_WC_FOLDER . 'ebh-productlist.class.php';