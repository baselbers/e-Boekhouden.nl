<?php
if ( ! function_exists( 'is_admin' ) && ! defined( '_EBOEKHOUDEN_PLUGIN' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * @file: eboekhouden-orders.class.php
 * @description: EBoekhouden Orders Class:
 * @version: 0.0.1
 *
 */

/**
 * @todo:
 * - [ ] Rename methods:
 *      >> lowercase, underscore and starting with 'ebh_'
 * - [x] GetOrders
 * - [x] CountOrders
 * - [ ] Create / modify: 'GetOrders()' so it can be used in:
 *      >> 'Eboekhouden_Order_List_Table' classes (wc2 AND wc3)
 * - [ ] Extend with frequently used 'functions'
 *
 */

if ( ! class_exists( 'Eboekhouden_Orders' ) ) {

	class Eboekhouden_Orders {

		public static function CountOrders( $status = 'all' ) {
			if ( 'not_mutated' === $status ) {
				return self::GetOrders( 'not_mutated' )->post_count;
			} else if ( 'mutated' === $status ) {
				return self::GetOrders( 'mutated' )->post_count;
			}

			return self::GetOrders( 'all' )->post_count;
		}

		/**
		 * Get order to count them.
		 *
		 * @param string $status
		 *
		 * @return WP_Query
		 */
		private static function GetOrders( $status = 'all' ) {
			$meta_query = array();

			if ( $status == 'not_mutated' ) {
				$meta_query = array(
					array(
						'key'     => 'mutation_nr',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key'     => '_order_total',
						'value'   => '0.00',
						'compare' => '!='
					),
				);
			} elseif ( $status == 'mutated' ) {
				$meta_query = array(
					array(
						'key'     => 'mutation_nr',
						'compare' => 'EXISTS',
					),
				);
			}

			$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';
			$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';

			$oq_args = array(
				'post_type'      => array( 'shop_order', 'shop_order_refund' ),
				'post_status'    => array( 'wc-processing', 'wc-completed', 'wc-refunded', 'wc-waiting', 'wc-shipped' ),
				'posts_per_page' => - 1,
				'fields'         => 'ids',      // << ivm grote aantallen alleen id's ophalen ipv alle velden
				'orderby'        => $orderby,
				'order'          => $order
			);

			$settings = get_option( 'ebh_settings_general' );
			if ( isset( $settings['ebh_order_after_date'] ) && '' !== $settings['ebh_order_after_date'] ) {
				$oq_args['date_query'] = array(
					'after'     => date( $settings['ebh_order_after_date'] ),
					'inclusive' => false,
				);
			}

			$oq_args['meta_query'] = $meta_query;

			return new WP_Query( $oq_args );
		}

		public function ebhFormatOrdernumber( $order_id ) {
			$wc_Order = new WC_Order( $order_id );

			$wc_order_id   = $wc_Order->get_id();
			$wc_order_date = $wc_Order->get_date_created();


			if ( function_exists( 'wcpdf_get_invoice' ) ) {
				$message = 'Ordernumber filter: "wcpdf_get_invoice"';
				ebh_debug_message( $message, 'Eboekhouden_Orders', 'ebhFormatOrdernumber', 'orders.log' );

				$invoice               = wcpdf_get_invoice( $wc_Order );
				$original_order_number = apply_filters( 'woocommerce_order_number', $wc_order_id, $wc_Order );

				if ( $invoice_number = $invoice->get_number() ) {
					$ebh_order_number = $invoice_number->get_formatted();

				} else {
					$ebh_order_number = apply_filters( 'woocommerce_order_number', $wc_order_id, $wc_Order );
				}
				$ebh_order_number .= ' (' . $original_order_number . ')';

				//$rbs_order_number = get_post_meta($rbs_order_id,'_wcpdf_formatted_invoice_number',true);
				//$rbs_order_number = wcpdf_get_invoice($rbs_order)->get_invoice_number();

			} elseif ( class_exists( 'WooCommerce_PDF_Invoices' ) ) {
				$message = 'Ordernumber filter: "WooCommerce_PDF_Invoices"';
				ebh_debug_message( $message, 'Eboekhouden_Orders', 'ebhFormatOrdernumber', 'orders.log' );

				$original_order_number = apply_filters( 'woocommerce_order_number', $wc_order_id, $wc_Order );
				$wpo_wcpdf             = new WooCommerce_PDF_Invoices();
				$wpo_wcpdf->load_classes();
				$ebh_order_number = $wpo_wcpdf->export->get_invoice_number( $wc_order_id ) . ' (' . $original_order_number . ')';

			} else {
				$message = 'Ordernumber filter: "default woocommerce"';
				ebh_debug_message( $message, 'Eboekhouden_Orders', 'ebhFormatOrdernumber', 'orders.log' );

				$ebh_order_number = apply_filters( 'woocommerce_order_number', $wc_order_id, $wc_Order );
			}

			$message             = array();
			$message['order_id'] = $order_id;
			if ( isset( $original_order_number ) ) {
				$message['original_order_number'] = $original_order_number;
			}
			$message['order_number'] = $ebh_order_number;
			ebh_debug_message( $message, 'Eboekhouden_Orders', 'ebhFormatOrdernumber', 'orders.log' );

			return $ebh_order_number;
		}


		public function get_pagenum() {
			$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

			if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
				$pagenum = $this->_pagination_args['total_pages'];
			}

			return max( 1, $pagenum );
		}


	}

}
