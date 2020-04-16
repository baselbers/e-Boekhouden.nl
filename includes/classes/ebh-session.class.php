<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}



/**
 * @todo:
 * - [ ] ?? Rename class to "Eboekhouden_Messages" 
 * - [ ] Check if "session" are required
 *      - [ ] check for 'better' solution
 * - [ ] "info type" is not wordpress type
 *      >> Add 'custom' styling for this message-type
 * - [ ] "Debug notice"
 *      >> sometimes multiple messages
 * - [ ] "remove" duplicate messages
 *  
 */


if (!class_exists('Eboekhouden_Session')) {
    
    
    class Eboekhouden_Session {
        
        
        const MESSAGE_TYPE_SUCCESS = 10001;
        const MESSAGE_TYPE_INFO = 10002;        
        const MESSAGE_TYPE_WARNING = 10003;        
        const MESSAGE_TYPE_ERROR = 10004;
                
        
        function __construct() {          
            add_action('init', array($this, 'ebhRegisterSession'));
            add_action('admin_notices', array($this, 'ebhShowNotices'));
            
        }
        
        
        public function ebhRegisterSession(){
            if (!session_id()) {
                session_start();
            }      
        }
        
        
        
        
        public function ebhAddNotice($type, $message, $dismissable = true) {
            $this->ebhRegisterSession();
            
            $_SESSION['eboekhouder-notices'][] = array(
                'type' => $type,
                'message' => $message,
                'dismissable' => $dismissable
            );
        }
        
        
        public function ebhShowNotices() {
            
//            if (defined('EBOEKHOUDEN_DEBUG')) {
//                $this->ebhAddNotice(self::MESSAGE_TYPE_WARNING, 'Eboekhouden DEBUG is Enabled!!');
////                echo '<div class="notice notice-warning is-dismissible">';
////                echo 'Eboekhouden DEBUG is Enabled!!';
////                echo '</div>';                
//            }            
            
            $messages = array();
            
            if (isset($_SESSION['eboekhouder-notices'])) {
                foreach ($_SESSION['eboekhouder-notices'] as $item) {
                    
                    
                    
                    if (!in_array($item['message'], $messages)) {
                    
                        $is_dismissible = isset($item['dismissable']) && $item['dismissable'] == true ? 'is-dismissible' : '';

                        if ($item['type'] == self::MESSAGE_TYPE_SUCCESS) {
                            echo '<div class="notice notice-success ' . $is_dismissible . '">';
                            echo $item['message'];
                            echo '</div>';
                        } elseif($item['type'] == self::MESSAGE_TYPE_ERROR) {            
                            echo '<div class="notice notice-error ' . $is_dismissible . '">';
                            echo $item['message'];
                            echo '</div>';
                        } elseif($item['type'] ==  self::MESSAGE_TYPE_WARNING) {            
                            echo '<div class="notice notice-warning ' . $is_dismissible . '">';
                            echo $item['message'];
                            echo '</div>';
                        } elseif($item['type'] == self::MESSAGE_TYPE_INFO) {            
                            echo '<div class="notice notice-info ' . $is_dismissible . '">';
                            echo $item['message'];
                            echo '</div>';
                        }
                        
                        
                        
                    }
                    
                    $messages[] = $item['message'];
                    
                }
                //echo print_r($messages);
                $this->ebhClearNotices();
            }

        }
        
        
        public function ebhClearNotices() {
            $_SESSION['eboekhouder-notices'] = null;            
        }
        
        
    }
    
    
    
}
