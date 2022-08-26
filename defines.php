<?php
if (!function_exists('is_admin')  || !defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$wc_version = explode('.', get_option('woocommerce_version'));
// Define 'eboekhouden plugin "identifier":
define('_EBOEKHOUDEN_PLUGIN', '1');

// Define 'plugin "info":
define('EBOEKHOUDEN_VERSION', '3.0.0' );
define('EBOEKHOUDEN_RELEASE_DATE', date_i18n('F j, Y', '1397937230'));        // time() OF "update";
define('EBOEKHOUDEN_PLUGIN_FILE',  plugin_dir_path(__FILE__ ) . 'eboekhouden.php');

// Define "directory paths": 
define('EBOEKHOUDEN_DIR', plugin_dir_path(__FILE__ ));
define('EBOEKHOUDEN_DIR_INCLUDES', EBOEKHOUDEN_DIR . 'includes' . DIRECTORY_SEPARATOR);
define('EBOEKHOUDEN_DIR_CLASSES', EBOEKHOUDEN_DIR_INCLUDES . 'classes' . DIRECTORY_SEPARATOR);
define('EBOEKHOUDEN_DIR_TEMPLATES', EBOEKHOUDEN_DIR . 'templates' . DIRECTORY_SEPARATOR);
define('EBOEKHOUDEN_DIR_LANGUAGES', EBOEKHOUDEN_DIR . 'languages' . DIRECTORY_SEPARATOR);

// Define "url"
define('EBOEKHOUDEN_URL', plugin_dir_url(__FILE__ ));
define('EBOEKHOUDEN_URL_ASSETS', EBOEKHOUDEN_URL . '/assets/');

// Define woocmmerce "info":
define('EBOEKHOUDEN_WC_VERSION', get_option('woocommerce_version'));
define('EBOEKHOUDEN_WC_FOLDER', EBOEKHOUDEN_DIR_INCLUDES . 'wc3' . DIRECTORY_SEPARATOR);
