<?php
/**
 * Eboekhouden Connector Class
 * 
 */
/**
 * @todo:
 * - [ ] 'SoapClient' integration
 * - [ ] 'connector' order/priority
 *      - Soap
 *      - Curl
 *      - Url
 *     
 */
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


if (!class_exists('Eboekhouden_Connector')) {
    
    class Eboekhouden_Connector {
        
        private $ebh_api_url; 
        private $ebh_username;
        private $ebh_security_code_1;
        private $ebh_security_code_2;
        
        private $ebh_version;               // ?? Rename: api_version
        private $ebh_source;                // ?? Rename: api_source
        
        private $ebh_curl_timeout;          // add "setting option"     (default: 10)
        
        
        private $errors;
        
        
        
        
        function __construct() {
            $this->init();
        }
        
        
        private function init() {
            $this->errors = new WP_Error();            
            
            $this->ebh_api_url = 'https://secure.e-Boekhouden.nl/bh/api.asp?xml=';
            $this->ebh_version = '1.0';
            $this->ebh_source = 'WOO';
            
            
            $advanced_settings = get_option('ebh_settings_advanced');            
            $this->ebh_curl_timeout = (isset($advanced_settings['ebh_curl_timeout'])) ? $advanced_settings['ebh_curl_timeout'] : 10;
            
            
             
        }


        /**
         * Get and check the "License Settings":
         * @return bool
         */
        private function ebh_get_license() {
            $valid_license = false;
            $license_settings = get_option('ebh_settings_license');
            
            $this->ebh_username = (isset($license_settings['ebh_username'])) ? $license_settings['ebh_username'] : null;
            $this->ebh_security_code_1 = (isset($license_settings['ebh_security_code_1'])) ? $license_settings['ebh_security_code_1'] : null;
            $this->ebh_security_code_2 = (isset($license_settings['ebh_security_code_2'])) ? $license_settings['ebh_security_code_2'] : null;                       
            
            // Check 'username':
            if (!isset($this->ebh_username) || strlen($this->ebh_username) == 0 || $this->ebh_username == null) {                
                ebh_debug_message('Invalid or NO username', 'Eboekhouden_Connector', 'ebh_get_license', 'connector.log');
                $this->errors->add('ebh', __('COM_EBOEKHOUDEN_EXTERNAL_NO_USERNAME', 'eboekhouden'));
                $valid_license = false;
            } else {
                $valid_license = true;
            }
            
            // Check 'security code 1':
            if (!isset($this->ebh_security_code_1) || strlen($this->ebh_security_code_1) == 0 || $this->ebh_security_code_1 == null) {
                ebh_debug_message('Invalid or NO security-code 1', 'Eboekhouden_Connector', 'ebh_get_license', 'connector.log');
                $this->errors->add('ebh', __('COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_1', 'eboekhouden'));
                $valid_license = false;
            } else {
                $valid_license = true;
            }
            

            // Check 'security code 2':
            if (!isset($this->ebh_security_code_2) || strlen($this->ebh_security_code_2) == 0 || $this->ebh_security_code_2 == null) {
                ebh_debug_message('Invalid or NO securit-code 2', 'Eboekhouden_Connector', 'ebh_get_license', 'connector.log');
                $this->errors->add('ebh', __('COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_2', 'eboekhouden'));
                $valid_license = false;
            }  else {
                $valid_license = true;
            }                  
            
            return $valid_license;
        }
        
        
        /**
         * Create the "authentication" array
         * @return array
         */
        private function ebh_get_auth_data() {
            $auth_data = array();
            if ($this->ebh_get_license()) {
                $auth_data['AUTH'] = array(
                    'GEBRUIKERSNAAM' => $this->ebh_username,
                    'WACHTWOORD' => $this->ebh_security_code_1,
                    'GUID' => $this->ebh_security_code_2,
                    'VERSION' => $this->ebh_version,
                    'SOURCE' => $this->ebh_source
                ); 
            }
            return $auth_data;
        }
        
        
        private function ebh_connect_soapclient() {
//            if (class_exists('SoapClient')) {
//                
//            }
            die('TODO: Under construction (check ebh-api doc)');
            $wsdl_uri = 'https://soap.e-boekhouden.nl/soap.asmx?WSDL';
            
            try {
                $client = new SoapClient($wsdl_uri);
                
                $params = array(
                    'Username' => $this->ebh_username,
                    'SecurityCode1' => $this->ebh_security_code_1,
                    'SecurityCode2' => $this->ebh_security_code_2
                );
                $response = $client->__soapCall('OpenSession', array($params));
                $this->soap_check_for_error($response, 'OpenSessionResult');
                $SessionID = $response->OpenSessionResult->SessionID; 
                
                
                
            } catch (SoapFault $soapFault) {
                echo '<strong>Er is een fout opgetreden:</strong><br>';
                echo $soapFault;
            }
            
        }
        
        // standaard error afhandeling
        private function soap_check_for_error($rawresponse, $sub) {
            $LastErrorCode = $rawresponse->$sub->ErrorMsg->LastErrorCode;
            $LastErrorDescription = $rawresponse->$sub->ErrorMsg->LastErrorDescription;
            if($LastErrorCode <> '') {
                echo '<strong>Er is een fout opgetreden:</strong><br>';
                echo $LastErrorCode . ': ' . $LastErrorDescription;
                exit();
            }
        }        
        
        
        
        /** 
         * CURL 'connection': 
         */
        private function ebh_connect_curl($api_string) {
            $return = false;
            
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->ebh_api_url . $api_string);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->ebh_curl_timeout);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    // TODO: Create 'proper' message with __
                    $str_error = "Foutmelding bij verbinding maken met e-Boekhouden.nl: '" . curl_error($ch) . "' <br> Uw webhoster blokkeert de uitgaande verbinding.";
                    
                    ebh_debug_message('TEMP: Connection error', 'Eboekhouden_Connector', 'ebh_connect_curl', 'connector.log');
                    ebh_debug_message(curl_error($ch), 'Eboekhouden_Connector', 'ebh_connect_curl', 'connector.log');
  
                    $this->errors->add('ebh', __('TEMP: Connection error', 'eboekhouden'));
                    
                    $return = false;
                }
                
                
                
                //@$content = simplexml_load_string($result);
                //echo print_r($result);
                $return = simplexml_load_string($result);                        
                //echo print_r($return);
                
                if (strpos($return, "<ERROR>") > 0) {
                    ebh_debug_message('TEMP: error...', 'Eboekhouden_Connector', 'ebh_connect_curl', 'connector.log');
                    echo( "Fout: " . $result);
                    $return = false;
                }
                
                $log = array();
                $log['curl_result'] = print_r($result, true);
                ebh_debug_message($log, 'Eboekhouden_Connector', 'ebh_connect_curl', 'connector.log');
                curl_close($ch);
                
            } catch (exception $e) {
                //new WP_Error('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE'));
                ebh_debug_message('TEMP: Connection Issue', 'Eboekhouden_Connector', 'ebh_connect_curl', 'connector.log');
                $this->errors->add('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE', 'eboekhouden'));
                $return = false;
            }
            
            return $return;
        }
        
        
        /** 'Direct URL' connection: */
        private function ebh_connect_direct_url($api_string) {
            $return = false;
            try {
//                @$content = file_get_contents($this->ebh_api_url . $api_string);
//                @$content = simplexml_load_string($content);
                $return = file_get_contents($this->ebh_api_url . $api_string);
                $return = simplexml_load_string($return);
                
            } catch (exception $e) {
//                new WP_Error('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE'));
                ebh_debug_message('TEMP: Connection Issue', 'Eboekhouden_Connector', 'ebh_connect_direct_url', 'connector.log');
                $this->errors->add('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE', 'eboekhouden'));                
                $return = false;
            }
            
            return $return;
        }
        
        
        
        /**
         * Send "data" to eBoekhouden:
         * @param string $action
         * @param array $data
         * @return mixed
         */
        public function ebhSend($action, $data = array()) {
            // Move to 'property' ??
            $valid_action = array(
                'LIST_GBCODE',
                'ALTER_MUTATIE',
                'ADD_MUTATIE'
            );
            
            if (!in_array(strtoupper($action), $valid_action)) {
                die('TEMP ERROR: Invalid Action [' . $action . ']');
            }
            
            $params = array();
            $params['ACTION'] = strtoupper($action);            

            $export_data = array_merge($params, $data, $this->ebh_get_auth_data());

            
            $Eboekhouden_Xml = new Eboekhouden_Xml();
            $api_string = $Eboekhouden_Xml->ebh_build_xml($export_data);
            $api_string = urlencode($api_string);
            
//'ACTION' => $this->_data->mutation_nr ? 'ALTER_MUTATIE' : 'ADD_MUTATIE',         
//$params = array('ACTION' => 'LIST_GBCODE');            
//$str = self::_buildXML(array_merge($params, self::_getInfo()));


        
            $result = false;

            if(function_exists('curl_init')) {
                $result = $this->ebh_connect_curl($api_string);
            } else {
                $result = $this->ebh_connect_direct_url($api_string);
            }




            if ($result === false) {
                // "merge" with 'below' ( if (isset($result->ERROR))....
            }

            if (isset($result->ERROR)) {
    //            new WP_Error('ebh', __($content->ERROR->DESCRIPTION));

                // CHECK: If error, then $result == boolean (false) en NOT an 'object'
                // Add 'error' in called 'connection method':
                //new WP_Error('ebh', __($result->ERROR->DESCRIPTION));
                ebh_debug_message($result->ERROR->DESCRIPTION, 'Eboekhouden_Connector', 'ebhSend', 'connector.log');
                $this->errors->add('ebh', __($result->ERROR->DESCRIPTION, 'eboekhouden'));

            }

            $log = array();
            $log['ebhSend_result'] = print_r($result, true);
            ebh_debug_message($log, 'Eboekhouden_Connector', 'ebhSend', 'connector.log');
            
            return $result;            

        }
        
        
        
        
    }
    
    
    
}
