<?php
if (!function_exists('is_admin') || !defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

//function ebh_access_check() {    
//    if (!is_admin() && !current_user_can('manage_options')) {
//        header('Status: 403 Forbidden');
//        header('HTTP/1.1 403 Forbidden');
//        exit();        
//    }
//}



/** Load 'templates (html)': */
function ebh_load_template($template, $vars = array()) {
    if (!defined('EBH_TEMPLATE')) {
        define('EBH_TEMPLATE', 1);
    }
    $file = EBOEKHOUDEN_DIR_TEMPLATES . $template . '.html.php';
    if (!file_exists($file)) {
        wp_die('[eBoekhouden] Cannot find template: ' . $file);        
    }
    
    ob_start();
    if (count($vars) != 0)  {
        extract($vars);
    }
    require $file;
    return ob_get_clean();
    
}


/** Debug logging: */
function ebh_debug_message($text, $class = null, $function = null, $logfile = null) {
    // Add class_exists??
    $Eboekhouden_Debug = new Eboekhouden_Debug();
    $Eboekhouden_Debug->ebhDebugMessage($text, $class, $function, $logfile);    
}