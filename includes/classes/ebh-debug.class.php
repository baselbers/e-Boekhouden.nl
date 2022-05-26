<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


/**
 * @todo:
 * - [ ] ??"Discus" HOW and WHAT to log
 * - [x] ?? Separate log files ??
 *      >> Now only 1 (current date) 
 *      >> ?? create directory with 'current' date
 * - [ ] ?? Rename to "logger" ???
 * - [ ] log messages to text 
 *      >> "append"
 * - [x] backend:
 *      - [x] list of log files
 *      - [x] view log file
 *      - [x] download log file
 *      - [x] delete log file
 * - [ ] ebhDeleteLog():
 *      >> change 'ebhDebugMessage' to 'wp_error' OR 'Session->Message'
 *      >> Else 'log file' is created WHEN deleting and thus cannot be deleted
 * 
 */

if (!class_exists('Eboekhouden_Debug')) {
    
    class Eboekhouden_Debug {
        
        
        //private $Eboekhouden_Settings;
        private $Eboekhouden_Session;
        
        
        private $ebh_debug_enabled;
        private $ebh_base_log_directory;
        private $ebh_log_directory;
        
        //private $ebh_log_file;
        
        
        
        function __construct() {
            //$this->Eboekhouden_Settings = new Eboekhouden_Settings();
            $this->Eboekhouden_Session = new Eboekhouden_Session();
            
            $this->init();           
        }
        
        
        private function init() {
            $advanced_settings = get_option('ebh_settings_advanced');
            $this->ebh_debug_enabled = (isset($advanced_settings['ebh_debug'])) ? $advanced_settings['ebh_debug'] : false;
            $this->ebh_base_log_directory = (isset($advanced_settings['ebh_log_directory'])) ? $advanced_settings['ebh_log_directory'] : null;                  
            //$this->ebh_log_file = date('Ymd') . '.log';
            
            if ($this->ebh_base_log_directory == null) {
                $this->ebh_base_log_directory = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
                $this->ebh_base_log_directory .= DIRECTORY_SEPARATOR . 'ebh-logs' . DIRECTORY_SEPARATOR;
            }
            
            $this->ebh_log_directory = $this->ebh_base_log_directory . date('Ymd') . DIRECTORY_SEPARATOR;            
            //$this->ebh_check_log_directory($this->ebh_log_directory);           // << Move to 'write log' method
            
            //$this->ebh_debug_enabled = $this->Eboekhouden_Settings->ebhGetOption('ebh_debug', false);
            //$this->ebh_log_directory = $this->Eboekhouden_Settings->ebhGetOption('ebh_log_directory', null);

            if ($this->ebh_debug_enabled === true || $this->ebh_debug_enabled == 'yes') {
                if (!defined('EBOEKHOUDEN_DEBUG')) {
                    define('EBOEKHOUDEN_DEBUG', 1);                    
                    //$message_type = $this->Eboekhouden_Session::MESSAGE_TYPE_WARNING;
                    //$message_type = Eboekhouden_Session::MESSAGE_TYPE_WARNING;
                    $this->Eboekhouden_Session->ebhAddNotice(Eboekhouden_Session::MESSAGE_TYPE_WARNING, 'eBoekhouden <strong>DEBUG</strong> is Enabled!!');
                }                
            }
            
            

        }
        
        
        
        private function ebh_check_log_directory($directory) {
            if (!file_exists($directory)) {
                $chmod = 0777;
                mkdir($directory, $chmod, true);
            }
            
            if (!file_exists($directory . '.htaccess')) {
                // Add "empty index.html" ???
                $htaccess = 'Deny from all' . "\r\n";
                file_put_contents($directory . '.htaccess' , $htaccess);                
            }
            

        }
        
        
        private function _OLD_ebh_get_system_info() {
            // Only run "once" PER "log (day)"
            //  Check 'file exists'
            $info = array();
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            
            $info['phpinfo'] = $phpinfo;
        }
        
        
        private function ebh_get_system_info($directory) {
            $filename = $directory . DIRECTORY_SEPARATOR . 'system.txt';

            $system_status = new WC_REST_System_Status_Controller();
            
            //$plugin_updates = new WC_Plugin_Updates();            // "Not found!!? "
            
            $info = array();
            $info['environment'] = $system_status->get_environment_info();
            $info['database'] = $system_status->get_database_info();            
            $info['theme'] = $system_status->get_theme_info();
            $info['security'] = $system_status->get_security_info();
            $info['settings'] = $system_status->get_settings();
            $info['active_plugins'] = $system_status->get_active_plugins();
            //$info['untested_plugins'] = $plugin_updates->get_untested_plugins( WC()->version, 'minor' );
            
            $output = '';
            foreach($info as $type => $content) {
                $output .= '[' . $type . ']' . "\r\n";
                $output .= print_r($content, true) . "\r\n";
                $output .= '---------------------------------' . "\r\n";
            }
            

            file_put_contents($filename, $output);
            return $filename;
            
//            $post_type_counts = $system_status->get_post_type_counts();
//            $active_plugins   = $system_status->get_active_plugins();
//            $theme            = $system_status->get_theme_info();
//            $security         = $system_status->get_security_info();
//            $settings         = $system_status->get_settings();
//            $pages            = $system_status->get_pages();
//            $plugin_updates   = new WC_Plugin_Updates();
//            $untested_plugins = $plugin_updates->get_untested_plugins( WC()->version, 'minor' );
            
            
            
        }
        
        
        
        private function ebh_list_log_files($directory) {
            $return = array();
            $pattern = $directory . DIRECTORY_SEPARATOR . '*.log';
            foreach(glob($pattern) as $file) {
                $return[] = $file;
            }            
            return $return;            
        }
        
        
        private function ebh_list_log_directories() {
            $return = array();
            $pattern = $this->ebh_base_log_directory . '*';
            foreach(glob($pattern, GLOB_ONLYDIR ) as $directory) {
                $return[] = $directory;
            }
            return $return;
        }
        
        
        private function ebh_download_file($filename) {
            ob_start();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            while (ob_get_level()) {
                ob_end_clean();
                @readfile($filename);
            }
            exit;
        }
        
        
        public function ebhGetLogs() {
            $logs = array();
            
            foreach($this->ebh_list_log_directories() as $log_directory) {
                $directory = basename($log_directory);
                $logs[$directory] = $this->ebh_list_log_files($log_directory);
            }
            krsort($logs);
            return $logs;
        }
        
        
        
        public function ebhViewLog($directory, $file) {            
            $log_file = $this->ebh_base_log_directory . $directory . DIRECTORY_SEPARATOR . $file;            
            if (file_exists($log_file)) {
                $log = file_get_contents($log_file);
                return nl2br($log);
            } else {
                wp_die('Log file does NOT exist: ' . $log_file);
            }
            
            return false;
        }
        
        
        public function ebhDownloadLog($directory) {
            $logs = $this->ebh_list_log_files($this->ebh_base_log_directory . $directory);

            if (count($logs) == 0) {
                $this->ebhDebugMessage('No log files in directory: ' . $directory, 'Eboekhouden_Debug', 'ebhDownloadLog', 'debug.log');
                wp_die('No log files in directory: ' . $directory);
            }
            
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                $out_file = $this->ebh_base_log_directory . $directory . DIRECTORY_SEPARATOR . $directory . '.zip';
                if ($zip->open($out_file, ZipArchive::CREATE || ZipArchive::OVERWRITE) !== TRUE) {
                    //exit("cannot open <$filename>\n");
                    $this->ebhDebugMessage('Could NOT create zipfile: ' . $out_file, 'Eboekhouden_Debug', 'ebhDownloadLog', 'debug.log');
                    wp_die('Could NOT create zipfile: ' . $out_file);
                }                
   
                $system_info = $this->ebh_get_system_info($this->ebh_base_log_directory . $directory);
                $zip->addFile($system_info, basename($system_info));
                
                foreach($logs as $logfile) {
                    $zip->addFile($logfile, basename($logfile));
                }                
                $zip->close();


                
                $this->ebh_download_file($out_file);
            } else {
                $this->ebhDebugMessage('TEMP ERROR: "ZipArchive" is NOT available on this system!', 'Eboekhouden_Debug', 'ebhDownloadLog', 'debug.log');
                wp_die('TEMP ERROR: "ZipArchive" is NOT available on this system!');
            }
            
        }
        
        
        public function ebhDeleteLog($directory) {
            $pattern = $this->ebh_base_log_directory . $directory . DIRECTORY_SEPARATOR . '*.*';
            // Remove Files:
            foreach(glob($pattern) as $file) {
                if (unlink($file)) {
                    //$this->ebhDebugMessage('Removed file: ' . $file, 'Eboekhouden_Debug', 'ebhDeleteLog', 'debug.log');
                } else {
                    //$this->ebhDebugMessage('Could NOT remove file: ' . $file, 'Eboekhouden_Debug', 'ebhDeleteLog', 'debug.log');
                }
            }
            
            // Remove '.htaccess'   (not found with "glob"):
            unlink($this->ebh_base_log_directory . $directory . DIRECTORY_SEPARATOR . '.htaccess');            
            
            // TEMP: Remove 'debug.log'
            // TODO: Check if 'removed' log is NOT 'current' log
            //unlink($this->ebh_base_log_directory . $directory . DIRECTORY_SEPARATOR . 'debug.log');                 
            
            // Remove Directory:
            if (rmdir($this->ebh_base_log_directory . $directory)) {
                //$this->ebhDebugMessage('Removed directory: ' . $directory, 'Eboekhouden_Debug', 'ebhDeleteLog', 'debug.log');
            } else {
                //$this->ebhDebugMessage('Could NOT remove directory: ' . $directory, 'Eboekhouden_Debug', 'ebhDeleteLog', 'debug.log');
            }
            
            
       
            
        }
        
        
        
        public function ebhDebugMessage($text, $class = null, $function = null, $logfile = null) {
            if (defined('EBOEKHOUDEN_DEBUG')) {
                $newline = "\r\n";

                $message = array();
                $message[] = '[' . date('H:i:s') . ']';

                if ($class != null) {
                    $message[] = 'CLASS: ' . $class;
                }

                if ($class != null && $function != null) {
                    $message[] = 'METHOD: ' . $function;
                } elseif ($class == null && $function != null) {
                    $message[] = 'FUNCTION: ' . $function;
                }
                
                if (is_string($text)) {
                    $message[] = $text;
                } elseif (is_array($text)) {
                    foreach ($text as $key => $value) {
                        if (is_string($value)) {
                            $message[] = strtoupper($key) . ': ' . $value;
                        } else {
                            $message[] = strtoupper($key) . ': ' . print_r($value, true);
                        }
                    }
                } else {
                    $message[] = print_r($text, true);
                }
                
                $message[] = '--------------------' . $newline;

                $output = implode($newline, $message);
            
                if ($logfile == null) {
                    $logfile = 'eboekhouden.log';
                }                

                $this->ebh_check_log_directory($this->ebh_log_directory);
                $result = file_put_contents($this->ebh_log_directory . $logfile, $output, FILE_APPEND);
                if ($result === false) {
                    wp_die('Could not write log file: ' . $this->ebh_log_directory . $logfile);
                }
                
            }
            
        }
        
    }       
    
}

