<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


/**
 * @todo:
 * 
 * - [ ] Add 'error' logging/messages (and debug)
 * - [ ] ?? Check if 'XMLWriter' exists?
 * - [ ] ?? Check for '$settings'
 *      - [ ] Example: $xml->setIndent(false); / $xml->startDocument('1.0', 'UTF-8'); / $xml->startElement('API');
 * - [ ] Add 'try / catch' ??
 * 
 */

if (!class_exists('Eboekhouden_Xml')) {
    
    class Eboekhouden_Xml {
        
        //private $ebh_xml;
        
        
        
        function __construct() {

        }
        
        
        
        private function _build_xml($data) {
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->setIndent(false);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('API');

            foreach ($data as $key => $value) {
                $this->_add_elements($xml, $key, $value);
            }

            $xml->endElement();
            $xml->endDocument();

            $return = $xml->outputMemory(true);
            return $return;            
        }
        
        
        private function _add_elements(&$resource, $key, $value) {
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->setIndent(false);

            // Safety for non-associative array's
            if (!is_numeric($key)) {
                $xml->startElement($key);
            }

            if (!is_array($value)) {
                $xml->text($value === null ? '' : $value);
            } else {
                foreach ($value as $el_key => $el_value) {
                    $this->_add_elements($xml, $el_key, $el_value);
                }
            }

            $xml->endElement();
            $resource->writeRaw($xml->outputMemory(true));            
        }
        
        
        
        public function ebh_build_xml($data) {
            //ebh_debug_message('Building XML', 'Eboekhouden_Xml', 'ebh_build_xml', 'xml.log); 
            $return = $this->_build_xml($data);
            return $return;
        }
        
        
    }       
    
}
