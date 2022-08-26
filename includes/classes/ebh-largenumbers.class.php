<?php
if ( ! function_exists( 'is_admin' ) && ! defined( '_EBOEKHOUDEN_PLUGIN' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * @todo:
 * - [ ] Add 'get/set largenumber' in the 'woocommerce product properties'
 *      >> same as "current" set largenumbers, but then in the 'product tab'
 * - [ ] Translate all 'texts'
 * - [ ] Check if 'license' settings are 'correct'
 * - [ ]
 */

if ( ! class_exists( 'Eboekhouden_Largenumbers' ) ) {

	class Eboekhouden_Largenumbers {


		private $Eboekhouden_Connector;
		private $Eboekhouden_Session;

		private $ebh_shippingcost_largenumber;
		private $ebh_paymentcost_largenumber;
		private $ebh_refund_largenumber;

		//private $ebh_api_url = 'https://secure.e-Boekhouden.nl/bh/api.asp?xml=';


		function __construct() {
			$this->Eboekhouden_Connector = new Eboekhouden_Connector();
			$this->Eboekhouden_Session   = new Eboekhouden_Session();

			$largenumber_settings               = get_option( 'ebh_settings_largenumbers' );
			$this->ebh_shippingcost_largenumber = ( isset( $largenumber_settings['ebh_shippingcost_largenumber'] ) ) ? $largenumber_settings['ebh_shippingcost_largenumber'] : null;
			$this->ebh_paymentcost_largenumber  = ( isset( $largenumber_settings['ebh_paymentcost_largenumber'] ) ) ? $largenumber_settings['ebh_paymentcost_largenumber'] : null;
			$this->ebh_refund_largenumber       = ( isset( $largenumber_settings['ebh_refund_largenumber'] ) ) ? $largenumber_settings['ebh_refund_largenumber'] : null;

		}


		public function ebh_set_largenumber( $post_id, $large_number ) {
			$current_product_attributes = get_post_meta( $post_id, '_product_attributes', true );

			if ( isset ( $current_product_attributes['large_number'] ) ) {
				$current_product_attributes['large_number']['value'] = $large_number;
				update_post_meta( $post_id, '_product_attributes', $current_product_attributes );
			} else {
				if ( ! is_array( $current_product_attributes ) ) {
					$current_product_attributes = array();
				}

				$large_number_attribute = array(
					//Make sure the 'name' is same as you have the attribute
					'name'         => htmlspecialchars( stripslashes( 'large_number' ) ),
					'value'        => $large_number,
					'position'     => 1,
					'is_visible'   => 0,
					'is_variation' => 0,
					'is_taxonomy'  => 0
				);

				$current_product_attributes['large_number'] = $large_number_attribute;
				update_post_meta( $post_id, '_product_attributes', $current_product_attributes );
			}

		}


		public function ebhGetLargeNumbersSettings() {
			$largenumber_settings = get_option( 'ebh_settings_largenumbers' );

			return $largenumber_settings;
		}


		public function ebhGetLargenumber( $type ) {
			if ( $type == 'shippingcost' ) {
				return $this->ebh_shippingcost_largenumber;
			} elseif ( $type == 'paymentcost' ) {
				return $this->ebh_paymentcost_largenumber;
			} elseif ( $type == 'refund' ) {
				return $this->ebh_refund_largenumber;
			} else {
				$message = 'Invalid largenumber type: ' . $type;
				ebh_debug_message( $message, 'Eboekhouden_Largenumbers', 'ebhGetLargenumber', 'largenumbers.log' );
				wp_die( $message );
			}
		}

		public function ebhGetLargeNumbersOptions() {
//            $params = array('ACTION' => 'LIST_GBCODE');
//            //$res = self::_getResponse($params);
//            $res = $this->_getResponse($params);

			$res = $this->Eboekhouden_Connector->ebhSend( 'LIST_GBCODE' );
			//echo print_r($res);
			$arr = array();

			if ( isset( $res->RESULT->GBCODES->GBCODE ) ) {
				foreach ( $res->RESULT->GBCODES->GBCODE as $i ) {
					$arr[] = array( 'code' => $i->CODE, 'description' => $i->OMSCHRIJVING );
				}
			}

			return $arr;
		}


		public function ebhGetLargeNumbers( $current_selected, $param = false ) {
//            $params = array('ACTION' => 'LIST_GBCODE');
//            //$res = self::_getResponse($params);
//            $res =$this->_getResponse($params);


			$res = $this->Eboekhouden_Connector->ebhSend( 'LIST_GBCODE' );

			$name = $param ? "params[$param]" : 'large_number';

			$strHtml = '<select name="' . $name . '" id="large_number" >';
			$strHtml .= '<option value="">' . _e( 'EBOEKHOUDEN_NO_LARGE_NUMBER', 'eboekhouden' ) . '</option>';

			if ( isset( $res->RESULT->GBCODES->GBCODE ) ) {
				foreach ( $res->RESULT->GBCODES->GBCODE as $i ) {
					if ( $i->CODE == $current_selected ) {
						$strHtml .= '<option value="' . $i->CODE . '" selected="true">' . $i->CODE . ' ' . $i->OMSCHRIJVING . '</option>';
					} else {
						$strHtml .= '<option value="' . $i->CODE . '">' . $i->CODE . ' ' . $i->OMSCHRIJVING . '</option>';
					}

				}
			}
			$strHtml .= '</select>';

			return $strHtml;
		}


		public function ebhAssignLargenumber( $echo = true ) {
			if ( isset( $_POST['ebh_products'] ) ) {
				$items = $_POST['ebh_products'];
			}

			$largenumber_options = $this->ebhGetLargeNumbersOptions();

			if ( isset( $items ) && count( $items ) ) {

				$strHtml = '<div class="wrap">';
				$strHtml .= '<h2>Assign</h2>';
				$strHtml .= '<form method="post">';
				$strHtml .= '<label for="">' . __( 'Choose largenumber', 'eboekhouden' ) . '</label>';
				$strHtml .= '<input type="hidden" name="action" value="ebh_assign_largenumber" />';
				$strHtml .= '<input type="hidden" name="ids" value="' . implode( ',', $items ) . '" />';
				$strHtml .= '<select name="ebh_largenumber">';

				if ( count( $largenumber_options ) == 0 ) {
					$strHtml .= '<option value="" disabled selected>...</option>';
				} else {
					foreach ( $largenumber_options as $option ) {
						$strHtml .= '<option value="' . $option['code'] . '">';
						$strHtml .= $option['code'] . ' - ' . $option['description'];
						$strHtml .= '</option>';
					}
				}
				$strHtml .= '</select>';

				$strHtml .= '<button class="ebh-button" type="submit">' . __( 'Save', 'eboekhouden' ) . '</button>';
				$strHtml .= '</form>';
				$strHtml .= '</div>';

				if ( $echo ) {
					echo $strHtml;
				}

				return $strHtml;

			}

		}


		public function ebhProcessAssignLargenumber() {     // $item_ids ??
			if ( isset( $_POST['ids'] ) ) {
				$items = explode( ',', $_POST['ids'] );
			}

			if ( isset( $items ) && count( $items ) ) {
				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => - 1,
					'post__in'       => $items
				);


				$tmpProducts = new WP_Query( $args );
				if ( $tmpProducts->have_posts() ) {
					foreach ( $tmpProducts->posts as $post ) {
						$product = new WC_Product( $post );
						// ??add check: $product->ID === $post->ID
						$message = 'Product "' . $product->get_formatted_name() . "' was updated to large number " . $_POST['ebh_largenumber'];
						ebh_debug_message( $message, 'Eboekhouden_Largenumbers', 'ebhProcessAssignLargenumber', 'largenumbers.log' );
						//$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message);
						$this->Eboekhouden_Session->ebhAddNotice( Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message );
						$this->ebh_set_largenumber( $product->get_id(), $_POST['ebh_largenumber'] );
					}

				} else {
					$message = 'Products ' . implode( ',', $items ) . ": could not assign large number";
					ebh_debug_message( $message, 'Eboekhouden_Largenumbers', 'ebhProcessAssignLargenumber', 'largenumbers.log' );
					//$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_ERROR, $message);
					$this->Eboekhouden_Session->ebhAddNotice( Eboekhouden_Session::MESSAGE_TYPE_ERROR, $message );
				}


			}

			//echo "<script>window.location='admin.php?page=e-Boekhouden.nl%2Feboekhouden.php%2Fproducts';</script>";
			//exit;

			// http://ebh3x.dev.robas.com/wp-admin/admin.php?page=eboekhouden-products&tab=all
			$redirect_page = 'admin.php?page=eboekhouden-products&tab=all';
			wp_redirect( $redirect_page );
			exit;
		}
	}

}