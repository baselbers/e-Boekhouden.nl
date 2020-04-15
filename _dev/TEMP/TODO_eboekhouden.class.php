<?php

class EboekhoudenJaagers {

    public $ebhError;

    public static function get_username() {
        return apply_filters( 'eboekhouden_get_username', get_option('eboekhouden_username_text') );
    }

    public static function get_code1() {
        return apply_filters( 'eboekhouden_get_code1', get_option('eboekhouden_security_code_1_text') );
    }

    public static function get_code2() {
        return apply_filters( 'eboekhouden_get_code2', get_option('eboekhouden_security_code_2_text') );
    }

    static $taxCodes = array('LOW' => 'LAAG_VERK', 'HIGH' => 'HOOG_VERK', 'VHIGH' => 'HOOG_VERK_21',
        'ZERO_IN_EU' => 'BI_EU_VERK', 'ZERO_OUT_EU' => 'BU_EU_VERK', 'NONE' => 'GEEN');

    static $euArray = array('AUT', 'AT', 'BEL', 'BE', 'BGR', 'BG', 'CZE', 'CZ',
        'DEU', 'DE', 'DNK', 'DK', 'ESP', 'ES', 'EST', 'EE', 'FRA', 'FR', 'FIN', 'FI',
        'GBR', 'GB', 'GRC', 'GR', 'HUN', 'HU', 'ITA', 'IT', 'IRL', 'IE', 'LUX', 'LU',
        'LVA', 'LV', 'MLT', 'MT', 'MCO', 'MC', 'NLD', 'NL', 'PRT', 'PT', 'POL', 'PL',
        'ROM', 'RO', 'SWE', 'SE', 'SVK', 'SK', 'SVN', 'SI');

    static function getTaxes($incl, $excl, $country)
    {
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
                $code = self::$taxCodes['LOW'];
            } else {
                if(intval($applied) < 20) {
                    $code = self::$taxCodes['HIGH'];
                } else {
                    $code = self::$taxCodes['VHIGH'];
                }
            }
        } else {
            if (in_array(strtoupper($country), self::$euArray)) {
                if (strtoupper($country) != 'NL' && strtoupper($country) != 'NLD') {
                    $params = JComponentHelper::getParams('com_eboekhouden');
                    $c = $params->get('user_field');
                    if (isset($shipping->$c) && $shipping->$c) {
                        $code = self::$taxCodes['ZERO_IN_EU'];
                    } else {
                        $code = self::$taxCodes['NONE'];
                    }
                } else {
                    $code = self::$taxCodes['NONE'];
                }
            } else {
                $code = self::$taxCodes['ZERO_OUT_EU'];
            }
        }
        return array('inclusive' => $incl, 'exclusive' => $excl, 'applied' => $applied,
            'tax_amount' => $tax_amount, 'code' => $code, 'country' => $country);
    }

    function getLargeNumbersOptions()
    {
        $params = array('ACTION' => 'LIST_GBCODE');

        $res = self::_getResponse($params);
        $arr = array();

        if (isset($res->RESULT->GBCODES->GBCODE)) {
            foreach ($res->RESULT->GBCODES->GBCODE as $i) {
                $arr[] = array('code' => $i->CODE, 'description' => $i->OMSCHRIJVING);
            }
        }

        return $arr;
    }

    function getLargeNumbers($current_selected, $param = false)
    {
        $params = array('ACTION' => 'LIST_GBCODE');

        $res = self::_getResponse($params);

        $name = $param ? "params[$param]" : 'large_number';

        $html = '<select name="' . $name . '" id="large_number" >';
        $html .= '<option value="">' . _e( 'EBOEKHOUDEN_NO_LARGE_NUMBER', 'eboekhouden' ) . '</option>';

        if (isset($res->RESULT->GBCODES->GBCODE)) {
            foreach ($res->RESULT->GBCODES->GBCODE as $i) {
                if ($i->CODE == $current_selected) {
                    $html .= '<option value="' . $i->CODE . '" selected="true">' . $i->CODE . ' ' . $i->OMSCHRIJVING . '</option>';
                } else {
                    $html .= '<option value="' . $i->CODE . '">' . $i->CODE . ' ' . $i->OMSCHRIJVING . '</option>';
                }

            }
        }
        $html .= '</select>';
        return $html;
    }

    static function _getInfo()
    {

        $pluginSettings = get_option('ebh_settings');
        $error = new WP_Error();

        if (!isset($pluginSettings['ebh_username']) || !strlen($pluginSettings['ebh_username'])) {
            $error->add('ebh', __( "COM_EBOEKHOUDEN_EXTERNAL_NO_USERNAME"));
        }
        if (!get_option('eboekhouden_code_1_text') || !strlen(get_option('eboekhouden_code_1_text'))) {
            $error->add('ebh', __( "COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_1"));
        }
        if (!get_option('eboekhouden_code_2_text') || !strlen(get_option('eboekhouden_code_2_text'))) {
            $error->add('ebh', __("COM_EBOEKHOUDEN_EXTERNAL_NO_CODE_2"));
        }

        //add_action('admin_notices', 'admin_notice_message');

        return array(
            'AUTH' => array('GEBRUIKERSNAAM' => $pluginSettings['ebh_username'],
            'WACHTWOORD' => $pluginSettings['ebh_security_code_1'],
            'GUID' => $pluginSettings['ebh_security_code_2'],
            'VERSION' => '1.0',
            'SOURCE' => 'WOO'
            )
        );
    }

    static function exportOrder($order)
    {

        $export = $order->getExportOrder();

        $res = self::_getResponse($export);
      
        $rbs_order = new WC_Order($order->_data->ID);
        
        $rbs_order_id = $rbs_order->get_id();
      
        $rbs_order_number = apply_filters( 'woocommerce_order_number', $rbs_order_id, $rbs_order );

        if (isset($res->MUTNR)) {
            $_SESSION['eboekhouder-notices'][] = array(
                'type'      => 'success',
                'message'   => 'Order ' . $rbs_order_number . ": " . $res->RESULT . " mutatie " . $res->MUTNR

            );

            return (int)$res->MUTNR;
        }

        $_SESSION['eboekhouder-notices'][] = array(
            'type'      => 'error',
            'message'   => 'Order ' . $rbs_order_number . ": " . $res->ERROR->CODE . " " . $res->ERROR->DESCRIPTION

            );

        return 0;
    }

    protected static function _getResponse($params)
    {
        $str = self::_buildXML(array_merge($params, self::_getInfo()));


        if(function_exists('curl_init')) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://secure.e-Boekhouden.nl/bh/api.asp?xml='.urlencode($str));
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				
				$cont=curl_exec($ch);
					if(curl_errno($ch)){
						echo "Foutmelding bij verbinding maken met e-Boekhouden.nl: '" . curl_error($ch) . "' <br> Uw webhoster blokkeert de uitgaande verbinding.";
					}			
					@$content = simplexml_load_string($cont);
					if (strpos($cont,"<ERROR>")>0)
					{
						
						echo( "Fout: " . $cont);
						
					}
                curl_close($ch);
            } catch (exception $e) {
                new WP_Error('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE'));
            }
        } else {
            try {
                @$content = file_get_contents('https://secure.e-Boekhouden.nl/bh/api.asp?xml=' .
                    urlencode($str));
                @$content = simplexml_load_string($content);
            }
            catch (exception $e) {
                new WP_Error('ebh', __('COM_EBOEKHOUDEN_CONNECTION_ISSUE'));
            }
        }

        if (isset($content->ERROR)) {
            new WP_Error('ebh', __($content->ERROR->DESCRIPTION));
        }

        return $content;
    }

    protected static function _buildXML($elements)
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(false);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('API');

        foreach ($elements as $k => $v) {
            self::_addElements($xml, $k, $v);
        }

        $xml->endElement();
        $xml->endDocument();

        $ret = $xml->outputMemory(true);

        return $ret;
    }

    protected static function _addElements(&$resource, $key, $value)
    {
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
            foreach ($value as $k => $v) {
                self::_addElements($xml, $k, $v);
            }
        }

        $xml->endElement();
        $resource->writeRaw($xml->outputMemory(true));
    }

    function admin_notice_message(){
        echo '<div class="updated"><p>Message to be shown</p></div>';
    }

}