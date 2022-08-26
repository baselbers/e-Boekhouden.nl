<?php
if ( ! function_exists( 'is_admin' ) && ! defined( '_EBOEKHOUDEN_PLUGIN' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 * @todo
 * - [ ] "Separate" settings sections
 *      - [ ] General
 *      - [ ] "License"
 *      - [ ] Large Numbers
 *      >> http://qnimate.com/add-tabs-using-wordpress-settings-api/
 *      >> https://code.tutsplus.com/tutorials/the-wordpress-settings-api-part-5-tabbed-navigation-for-settings--wp-24971
 * - [ ] Add 'option': 'ebh_activated_license'      (false / true)
 *      >> if "false" then go to "settings -> license tab"
 *      >> Add 'validate license' button
 * - [ ] "mask out" part of license keys
 * - [ ] 'general'
 *      - [ ] "additional" plugins configuration
 *              >> example: WooCommerce Sequential Order Numbers Pro / WooCommerce PDF Invoices & Packing Slips
 *      - [ ] "debug" (logging"
 * - [ ] Add 'GetSetting' function
 *      >> So ALL 'setting'  related are in 1 class
 * - [ ] Add 'Slider'
 *      >> ex: payment term (min: 30 days, max: 90??)
 *      >> ex: 'order / product limits'     ??  (10 - 100)??
 * - [ ] 'Default largenumber' setting
 * - [ ]
 */


if ( ! class_exists( "Eboekhouden_Settings" ) ) {

	class Eboekhouden_Settings {

		// Eboekhouden 'objects / classes':
		/**
		 * @var Eboekhouden_Largenumbers
		 */
		private $Eboekhouden_Largenumbers;
		/**
		 * @var Eboekhouden_Plugins
		 */
		private $Eboekhouden_Plugins;
//        /**         
//         * @var Eboekhouden_Debug
//         */
//        private $Eboekhouden_Debug;
		/**
		 * @var Eboekhouden_Session
		 */
		private $Eboekhouden_Session;


		// License settings:
		private $ebh_username;
		private $ebh_security_code_1;
		private $ebh_security_code_2;


		// Largenumber settings:
		private $ebh_shippingcost_largenumber;
		private $ebh_paymentcost_largenumber;
		private $ebh_refund_largenumber;


		// General settings:
		private $ebh_payment_term;
		private $ebh_order_list_max;
		private $ebh_product_list_max;
		private $ebh_plugin_ordernumber;
		private $ebh_plugin_payment_reference;
		private $ebh_order_after_date;


		// Advanced settings:
		private $ebh_debug;
		private $ebh_log_directory;
		private $ebh_curl_timeout;


		function __construct() {
			$this->init();
			$this->hooks();
		}


		private function init() {
			$this->Eboekhouden_Largenumbers = new Eboekhouden_Largenumbers();
			$this->Eboekhouden_Plugins      = new Eboekhouden_Plugins();
			//$this->Eboekhouden_Debug = new Eboekhouden_Debug();
			$this->Eboekhouden_Session = new Eboekhouden_Session();


			$general_settings                   = get_option( 'ebh_settings_general' );
			$this->ebh_payment_term             = ( isset( $general_settings['ebh_payment_term'] ) ) ? $general_settings['ebh_payment_term'] : 30;
			$this->ebh_order_list_max           = ( isset( $general_settings['ebh_order_list_max'] ) ) ? $general_settings['ebh_order_list_max'] : 100;
			$this->ebh_product_list_max         = ( isset( $general_settings['ebh_product_list_max'] ) ) ? $general_settings['ebh_product_list_max'] : 100;
			$this->ebh_plugin_ordernumber       = ( isset( $general_settings['ebh_plugin_ordernumber'] ) ) ? $general_settings['ebh_plugin_ordernumber'] : 'default';
			$this->ebh_plugin_payment_reference = ( isset( $general_settings['ebh_plugin_payment_reference'] ) ) ? $general_settings['ebh_plugin_payment_reference'] : 'default';
			$this->ebh_order_after_date         = ( isset( $general_settings['ebh_order_after_date'] ) ) ? $general_settings['ebh_order_after_date'] : '';

			$license_settings          = get_option( 'ebh_settings_license' );
			$this->ebh_username        = ( isset( $license_settings['ebh_username'] ) ) ? $license_settings['ebh_username'] : null;
			$this->ebh_security_code_1 = ( isset( $license_settings['ebh_security_code_1'] ) ) ? $license_settings['ebh_security_code_1'] : null;
			$this->ebh_security_code_2 = ( isset( $license_settings['ebh_security_code_2'] ) ) ? $license_settings['ebh_security_code_2'] : null;


			$advanced_settings       = get_option( 'ebh_settings_advanced' );
			$this->ebh_debug         = ( isset( $advanced_settings['ebh_debug'] ) ) ? $advanced_settings['ebh_debug'] : false;
			$this->ebh_log_directory = ( isset( $advanced_settings['ebh_log_directory'] ) ) ? $advanced_settings['ebh_log_directory'] : null;
			$this->ebh_curl_timeout  = ( isset( $advanced_settings['ebh_curl_timeout'] ) ) ? $advanced_settings['ebh_curl_timeout'] : 10;

		}


		private function hooks() {
			add_action( 'admin_init', array( $this, 'ebh_settings_init' ), 20 );
		}


		/** Check and change the "old" settings to "new" version: */
		private function convert_old_settings() {
			$old_options = get_option( 'ebh_settings', false );

			if ( $old_options !== false ) {

				// License settings:
				if ( get_option( 'ebh_settings_license', false ) === false ) {
					$license_settings                        = array();
					$license_settings['ebh_username']        = isset( $old_options['ebh_username'] ) ? $old_options['ebh_username'] : '';
					$license_settings['ebh_security_code_1'] = isset( $old_options['ebh_security_code_1'] ) ? $old_options['ebh_security_code_1'] : '';
					$license_settings['ebh_security_code_2'] = isset( $old_options['ebh_security_code_2'] ) ? $old_options['ebh_security_code_2'] : '';
					update_option( 'ebh_settings_license', $license_settings );

					$message = __( 'Converted License settings', 'eboekhouden' );
					//$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_INFO, $message);
					$this->Eboekhouden_Session->ebhAddNotice( Eboekhouden_Session::MESSAGE_TYPE_INFO, $message );
				}

				// Largenumber settings:
				if ( get_option( 'ebh_settings_largenumbers', false ) === false ) {
					$largenumber_settings                                 = array();
					$largenumber_settings['ebh_shippingcost_largenumber'] = isset( $old_options['ebh_shippingcost_largenumber'] ) ? $old_options['ebh_shippingcost_largenumber'] : '';
					$largenumber_settings['ebh_paymentcost_largenumber']  = isset( $old_options['ebh_paymentcost_largenumber'] ) ? $old_options['ebh_paymentcost_largenumber'] : '';
					$largenumber_settings['ebh_refund_largenumber']       = isset( $old_options['ebh_refund_largenumber'] ) ? $old_options['ebh_refund_largenumber'] : '';
					update_option( 'ebh_settings_largenumbers', $largenumber_settings );

					$message = __( 'Converted Largenumber settings', 'eboekhouden' );
					//$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_INFO, $message);
					$this->Eboekhouden_Session->ebhAddNotice( Eboekhouden_Session::MESSAGE_TYPE_INFO, $message );

				}

				// Delete the "old" settings:
				delete_option( 'ebh_settings' );
			}

		}


		public function ebh_settings_init() {
			$this->convert_old_settings();

			/** Register settings: */
			register_setting( 'ebh_settings_general', 'ebh_settings_general' );
			register_setting( 'ebh_settings_largenumbers', 'ebh_settings_largenumbers' );
			register_setting( 'ebh_settings_license', 'ebh_settings_license' );
			register_setting( 'ebh_settings_advanced', 'ebh_settings_advanced' );

			/** General settings: **/
			add_settings_section( 'ebh_plugin_settings_section_general', __( 'General settings', 'eboekhouden' ), array(
				$this,
				'ebh_settings_section_callback'
			), 'ebh_settings_general' );
			add_settings_field( 'ebh_payment_term', __( 'Term of Payment', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_payment_term' ) );
			add_settings_field( 'ebh_order_list_max', __( 'Max. orders per page', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_order_list_max' ) );
			add_settings_field( 'ebh_product_list_max', __( 'Max. products per page', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_product_list_max' ) );
			add_settings_field( 'ebh_plugin_ordernumber', __( 'Ordernumber plugin', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_plugin_ordernumber' ) );
			add_settings_field( 'ebh_plugin_payment_reference', __( 'Payment refence plugin', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_plugin_payment_reference' ) );
			add_settings_field( 'ebh_order_after_date', __( 'Order after date', 'eboekhouden' ), array(
				$this,
				'ebh_setting_general'
			), 'ebh_settings_general', 'ebh_plugin_settings_section_general', array( 'name' => 'ebh_order_after_date' ) );

			/** Large-number settings: **/
			add_settings_section( 'ebh_plugin_settings_section_largenumbers', __( 'Largenumbers', 'eboekhouden' ), array(
				$this,
				'ebh_settings_section_callback'
			), 'ebh_settings_largenumbers' );
			add_settings_field( 'ebh_shippingcost_largenumber', __( 'Shipping Costs Large Number', 'eboekhouden' ), array(
				$this,
				'ebh_setting_largenumbers'
			), 'ebh_settings_largenumbers', 'ebh_plugin_settings_section_largenumbers', array( 'name' => 'ebh_shippingcost_largenumber' ) );
			add_settings_field( 'ebh_paymentcost_largenumber', __( 'Payment Costs Large Number', 'eboekhouden' ), array(
				$this,
				'ebh_setting_largenumbers'
			), 'ebh_settings_largenumbers', 'ebh_plugin_settings_section_largenumbers', array( 'name' => 'ebh_paymentcost_largenumber' ) );
			add_settings_field( 'ebh_refund_largenumber', __( 'Refund Large Number', 'eboekhouden' ), array(
				$this,
				'ebh_setting_largenumbers'
			), 'ebh_settings_largenumbers', 'ebh_plugin_settings_section_largenumbers', array( 'name' => 'ebh_refund_largenumber' ) );

			/** License settings: **/
			add_settings_section( 'ebh_plugin_settings_section_license', __( 'License', 'eboekhouden' ), array(
				$this,
				'ebh_settings_section_callback'
			), 'ebh_settings_license' );
			add_settings_field( 'ebh_username', __( 'Username', 'eboekhouden' ), array(
				$this,
				'ebh_setting_license'
			), 'ebh_settings_license', 'ebh_plugin_settings_section_license', array( 'name' => 'ebh_username' ) );
			add_settings_field( 'ebh_security_code_1', __( 'Security Code #1', 'eboekhouden' ), array(
				$this,
				'ebh_setting_license'
			), 'ebh_settings_license', 'ebh_plugin_settings_section_license', array( 'name' => 'ebh_security_code_1' ) );
			add_settings_field( 'ebh_security_code_2', __( 'Security Code #2', 'eboekhouden' ), array(
				$this,
				'ebh_setting_license'
			), 'ebh_settings_license', 'ebh_plugin_settings_section_license', array( 'name' => 'ebh_security_code_2' ) );

			/** Advanced settings: */
			add_settings_section( 'ebh_plugin_settings_section_advanced', __( 'Advanced settings', 'eboekhouden' ), array(
				$this,
				'ebh_settings_section_callback'
			), 'ebh_settings_advanced' );
			add_settings_field( 'ebh_debug', __( 'Enabled debug / logging', 'eboekhouden' ), array(
				$this,
				'ebh_settings_advanced'
			), 'ebh_settings_advanced', 'ebh_plugin_settings_section_advanced', array( 'name' => 'ebh_debug' ) );
			add_settings_field( 'ebh_log_directory', __( 'Log file directory', 'eboekhouden' ), array(
				$this,
				'ebh_settings_advanced'
			), 'ebh_settings_advanced', 'ebh_plugin_settings_section_advanced', array( 'name' => 'ebh_log_directory' ) );
			add_settings_field( 'ebh_curl_timeout', __( 'Curl Timeout', 'eboekhouden' ), array(
				$this,
				'ebh_settings_advanced'
			), 'ebh_settings_advanced', 'ebh_plugin_settings_section_advanced', array( 'name' => 'ebh_curl_timeout' ) );

		}


		function ebh_settings_section_callback( $args ) {
			if ( isset( $args['id'] ) && $args['id'] == 'ebh_plugin_settings_section_license' ) {
				echo __( 'Fill in the required fields as supplied by e-Boekhouden.nl', 'eboekhouden' );
			}
		}


		private function ebh_get_plugin_list() {
			$available_plugins = $this->Eboekhouden_Plugins->ebhCheckOrderNumberPlugins();

			$return            = array();
			$return['default'] = __( 'Default', 'eboekhouden' );

			foreach ( $available_plugins as $plugin_key => $plugin_name ) {
				$return[ $plugin_key ] = $plugin_name;
			}

			return $return;
		}


		public function ebh_setting_general( $args ) {
			$property = $args['name'];
			$output   = '';

			if ( $property == 'ebh_payment_term' || $property == 'ebh_order_list_max' || $property == 'ebh_product_list_max' ) {
				$output .= '<input type="hidden" name="ebh_settings_general[' . $property . ']" value="' . $this->$property . '" style="width: 400px">';

				/* prepared "slider" (change input to hidden): */
				//if ($property == 'ebh_order_list_max' || $property == 'ebh_product_list_max') {
				$output .= '<div id="slider-' . $property . '" style="width: 400px">';
				$output .= '<div id="custom-handle-' . $property . '" class="custom-handle ui-slider-handle"></div>';
				$output .= '</div>';
				//}

			} elseif ( $property == 'ebh_plugin_ordernumber' || $property == 'ebh_plugin_payment_reference' ) {
				$output .= '<select name="ebh_settings_general[' . $args['name'] . ']">';
				if ( ! count( $this->ebh_get_plugin_list() ) ) {
					$output .= '<option value="" disabled selected>...</option>';
				} else {
					foreach ( $this->ebh_get_plugin_list() as $key => $value ) {
						$selected = ( isset( $this->$property ) && $this->$property == $key ) ? 'selected' : '';
						$output   .= '<option value="' . $key . '" ' . $selected . '>';
						$output   .= $value;
						$output   .= '</option>';
					}
				}
				$output .= '</select>';

				if ( $property == 'ebh_plugin_payment_reference' ) {
					$output .= '<p class="description">';
					$output .= __( 'If set to "DEFAULT", it uses the same as "Ordernumber plugin"', 'eboekhouden' );
					$output .= '</p>';
				}
			} elseif ( $property == 'ebh_order_after_date' ) {
				$output .= '<input type="text" name="ebh_settings_general[' . $property . ']" placeholder="YYYY-MM-DD" value="' . $this->$property . '" style="width: 400px">';
			}

			echo $output;
		}


		public function ebh_setting_largenumbers( $args ) {
			$largenumber_settings = $this->Eboekhouden_Largenumbers->ebhGetLargeNumbersSettings();
			$optionsOptions       = $this->Eboekhouden_Largenumbers->ebhGetLargeNumbersOptions();

			$output = '<select name="ebh_settings_largenumbers[' . $args['name'] . ']">';

			if ( ! count( $optionsOptions ) ) {
				$output .= '<option value="" disabled selected>...</option>';
			} else {
				foreach ( $optionsOptions as $option ) {
					$selected = ( isset( $largenumber_settings[ $args['name'] ] ) && $largenumber_settings[ $args['name'] ] == $option['code'] ) ? 'selected' : '';
					$output   .= '<option value="' . $option['code'] . '" ' . $selected . '>';
					$output   .= $option['code'] . ' - ' . $option['description'];
					$output   .= '</option>';
				}
			}

			$output .= '</select>';
			echo $output;
		}


		public function ebh_setting_license( $args ) {
			$property = $args['name'];
			$output   = '<input type="text" name="ebh_settings_license[' . $property . ']" value="' . $this->$property . '" style="width: 400px">';
			echo $output;
		}


		public function ebh_settings_advanced( $args ) {
			$property = $args['name'];
			$output   = '';

			if ( $property == 'ebh_debug' ) {
				if ( $this->$property === true || $this->$property == 'yes' ) {
					$checked = 'checked';
				} else {
					$checked = '';
				}
				$output .= '<input type="checkbox" name="ebh_settings_advanced[' . $property . ']" value="yes" ' . $checked . '>';
				$output .= '<p class="description">' . __( 'Only enable when having issues', 'eboekhouden' ) . '</p>';
			} elseif ( $property == 'ebh_log_directory' ) {
				$output .= '<input type="text" name="ebh_settings_advanced[' . $property . ']" value="' . $this->$property . '" style="width: 400px">';
				$output .= '<p class="description">' . __( 'Directory for log-files (absolute path)', 'eboekhouden' ) . '</p>';
				$output .= '<p class="description">' . __( 'Default: {webroot}/ebh-logs/', 'eboekhouden' ) . '</p>';
			} elseif ( $property == 'ebh_curl_timeout' ) {
				$output .= '<input type="text" name="ebh_settings_advanced[' . $property . ']" value="' . $this->$property . '" style="width: 400px">';
				$output .= '<p class="description">' . __( 'Only change when having connection issues', 'eboekhouden' ) . '</p>';
			}

			echo $output;
		}


		public function ebhGetOption( $name, $default = null ) {
			if ( isset( $this->$name ) ) {
				return $this->$name;
			}

			if ( $default != null ) {
				return $default;
			}
		}


	}

}
