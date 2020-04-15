<?php
if (!function_exists('is_admin') && !defined('_EBOEKHOUDEN_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/**
 * @todo:
 * - [ ] Add 'error / debug' logging
 * - [ ] ?? Add 'filters' ??
 * - [ ] ?? Add 'settings' ??
 *      >> MAG en is het 'mogelijk' dat gebruikers zelf extra codes kunnen aanmaken/ gebruiken??
 * - [ ] Check: ebh_create_eu_countries_array()
 *      - [ ] If all 'countries' are 'correct
 *      - [ ] Add country names to the '3 letter' codes
 * - [ ] Check Code
 *      >> commented '// JOOMLA??'
 * - [ ] ?? Add 'filters'
 *      >> Example 'tax calc'
 * - [ ] Add 'extra (new)' setttings:
 *      - [ ] tax "mapping" for WC-taxes <-> ebh_create_taxcodes_array
 *      - [ ] "eu" countries
 *      - [ ] 
 * 
 */


if (!class_exists('Eboekhouden_Taxes')) {
    
    class Eboekhouden_Taxes {
        
        private $ebh_tax_codes;
        private $ebh_eu_countries;
        
        private $wc_tax;
        
        
        function __construct() {
            $this->ebh_tax_codes = array();
            $this->ebh_eu_countries = array();
            
            $this->init();
        }
        
        
        private function init() {
            $this->ebh_create_taxcodes_array();
            $this->ebh_create_eu_countries_array();
            
            $this->wc_tax = new WC_Tax();
        }
        
        
        private function ebh_create_taxcodes_array() {
            $this->ebh_tax_codes['NONE'] = 'GEEN';
            $this->ebh_tax_codes['LOW'] = 'LAAG_VERK';
            $this->ebh_tax_codes['HIGH'] = 'HOOG_VERK';
            $this->ebh_tax_codes['VHIGH'] = 'HOOG_VERK_21';
            $this->ebh_tax_codes['ZERO_IN_EU'] = 'BI_EU_VERK';
            $this->ebh_tax_codes['ZERO_OUT_EU'] = 'BU_EU_VERK';            
        }
        
        private function ebh_create_eu_countries_array() {
//            $this->ebh_eu_countries = array(
//                'AUT', 'AT', 'BEL', 'BE', 'BGR', 'BG', 'CZE', 'CZ',
//                'DEU', 'DE', 'DNK', 'DK', 'ESP', 'ES', 'EST', 'EE', 
//                'FRA', 'FR', 'FIN', 'FI', 'GBR', 'GB', 'GRC', 'GR', 
//                'HUN', 'HU', 'ITA', 'IT', 'IRL', 'IE', 'LUX', 'LU',
//                'LVA', 'LV', 'MLT', 'MT', 'MCO', 'MC', 'NLD', 'NL', 
//                'PRT', 'PT', 'POL', 'PL', 'ROM', 'RO', 'SWE', 'SE', 
//                'SVK', 'SK', 'SVN', 'SI'
//            );
            $this->ebh_eu_countries['AUT'] = 'Austria';
            $this->ebh_eu_countries['AT'] = 'Austria';
            $this->ebh_eu_countries['BEL'] = 'Belgium';
            $this->ebh_eu_countries['BE'] = 'Belgium';
            $this->ebh_eu_countries['BGR'] = 'a';
            $this->ebh_eu_countries['BG'] = 'Bulgaria';
            $this->ebh_eu_countries['CZE'] = 'a';
            $this->ebh_eu_countries['CZ'] = 'Czechia';
            $this->ebh_eu_countries['DEU'] = 'a';
            $this->ebh_eu_countries['DE'] = 'Germany';
            $this->ebh_eu_countries['DNK'] = 'a';
            $this->ebh_eu_countries['DK'] = 'Denmark';
            $this->ebh_eu_countries['ESP'] = 'a';
            $this->ebh_eu_countries['ES'] = 'Spain';
            $this->ebh_eu_countries['EST'] = 'a';
            $this->ebh_eu_countries['EE'] = 'Estonia';
            $this->ebh_eu_countries['FRA'] = 'a';
            $this->ebh_eu_countries['FR'] = 'France';
            $this->ebh_eu_countries['FIN'] = 'a';
            $this->ebh_eu_countries['FI'] = 'Finland';
            $this->ebh_eu_countries['GBR'] = 'a';
            $this->ebh_eu_countries['GB'] = 'Great Britain';
            $this->ebh_eu_countries['GRC'] = 'a';
            $this->ebh_eu_countries['GR'] = 'Greece';
            $this->ebh_eu_countries['HUN'] = 'a';
            $this->ebh_eu_countries['HU'] = 'Hungary';
            $this->ebh_eu_countries['ITA'] = 'a';
            $this->ebh_eu_countries['IT'] = 'Italy';
            $this->ebh_eu_countries['IRL'] = 'a';
            $this->ebh_eu_countries['IE'] = 'Ireland';
            $this->ebh_eu_countries['LUX'] = 'a';
            $this->ebh_eu_countries['LU'] = 'Luxembourg';
            $this->ebh_eu_countries['LVA'] = 'a';
            $this->ebh_eu_countries['LV'] = 'Latvia';
            $this->ebh_eu_countries['MCO'] = 'a';
            $this->ebh_eu_countries['MC'] = 'Monaco';            
            $this->ebh_eu_countries['MLT'] = 'a';
            $this->ebh_eu_countries['MT'] = 'Malta';
            $this->ebh_eu_countries['NLD'] = 'Netherlands';
            $this->ebh_eu_countries['NL'] = 'Netherlands';            
            $this->ebh_eu_countries['POL'] = 'a';
            $this->ebh_eu_countries['PL'] = 'Poland';                
            $this->ebh_eu_countries['PRT'] = 'a';
            $this->ebh_eu_countries['PT'] = 'Portugal';        
            $this->ebh_eu_countries['ROM'] = 'a';
            $this->ebh_eu_countries['RO'] = 'Romania';
            $this->ebh_eu_countries['SWE'] = 'a';
            $this->ebh_eu_countries['SE'] = 'Sweden';
            $this->ebh_eu_countries['SVK'] = 'a';
            $this->ebh_eu_countries['SK'] = 'Slovakia';
            $this->ebh_eu_countries['SVN'] = 'a';
            $this->ebh_eu_countries['SI'] = 'Slovenia';
                 
        }
        
        
        
        public function ebhGetTaxcode($taxcode) {
            return '';
            
//            if (array_key_exists(strtoupper($taxcode), $this->ebh_tax_codes)) {
//                return $this->ebh_tax_codes[strtoupper($taxcode)];
//            } else {
//                return '';
//                return $taxcode;
////                $message = 'Invalid $taxcode: ' . $taxcode;                
////                ebh_debug_message($message, 'Eboekhouden_Taxes', 'ebhGetTaxcode', 'taxes.log');
////                wp_die($message);
//            }
        }
        
        
        
        public function ebhGetTaxes($incl, $excl, $country) {
            $return = array();
            
            if (round($incl) == round($excl)) {
                $applied = 0;
                $tax_amount = 0;
            } else {
                // Thanks to Alexander Willemse for this
                $applied = (($incl / $excl) * 100) - 100;
                $tax_amount = round($incl - $excl, 2);
            }

            if ($applied) {
                
                if (intval($applied) < 12) {                    
                    $code = $this->ebh_get_taxcode('LOW');
                } else {
                    if (intval($applied) < 20) {
                        $code = $this->ebh_get_taxcode('HIGH');
                    } else {
                        $code = $this->ebh_get_taxcode('VHIGH');
                    }
                }
                
            } else {
                
                if (array_key_exists(strtoupper($country), $this->ebh_eu_countries)) {
                    
                    if (strtoupper($country) != 'NL' && strtoupper($country) != 'NLD') {
                        // JOOMLA??
                        // Looks like "joomla code"... WHAT should be in 'params' ??
                        $params = JComponentHelper::getParams('com_eboekhouden');
                        $c = $params->get('user_field');
                        if (isset($shipping->$c) && $shipping->$c) {
                            //$this->external_data->tax_code = self::$taxCodes['ZERO_IN_EU'];
                            //$code = self::$taxCodes['ZERO_IN_EU'];
                            $code = $this->ebh_get_taxcode('ZERO_IN_EU');
                        } else {
                            //$this->external_data->tax_code = self::$taxCodes['NONE'];
                            //$code = self::$taxCodes['NONE'];
                            $code = $this->ebh_get_taxcode('NONE');
                        }
                        
                    } else {
                        $code = $this->ebh_get_taxcode('NONE');
                    }
                    
                } else {                    
                    $code = $this->ebh_get_taxcode('ZERO_OUT_EU');
                }
            }
             
            $return['inclusive'] = $incl;
            $return['exclusive'] = $excl;
            $return['applied'] = $applied;
            $return['tax_amount'] = $tax_amount;
            $return['code'] = $code;
            $return['country'] = $country;
            
            
            // TODO: Add 'filter' ??
            return $return;                             
            
        }
        
        
        public function wcTax() {           
            if (!$this->wc_tax instanceof WC_Tax) {
                $message = '$wc_tax NOT an "instance_of" WC_TAX';                
                ebh_debug_message($message, 'Eboekhouden_Taxes', 'wcTax', 'taxes.log');                
                wp_die($message);                
            }
            return $this->wc_tax;
        }
        
    }
}
