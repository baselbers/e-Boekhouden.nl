<?php

defined( 'ABSPATH' ) || exit;

class Eboekhouden_Export {

	private $wc_order_id;
	private $wc_order;

	private $ebh_export_customer;
	private $ebh_export_mutations;

	private $Eboekhouden_Settings;
	private $Eboekhouden_Taxes;
	private $Eboekhouden_Largenumbers;
	private $Eboekhouden_Session;
	private $Eboekhouden_Plugins;

	function __construct( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			ebh_debug_message( '$order is not an WC_Order Object!', 'Eboekhouden_Export', '__construct', 'export.log' );
			wp_die( 'TEMP ERROR: Invalid order object' );
		}

		$this->wc_order_id = $order_id;
		$this->wc_order    = $order;

		$this->ebh_export_customer  = array();
		$this->ebh_export_mutations = array();

		$this->Eboekhouden_Settings     = new Eboekhouden_Settings();
		$this->Eboekhouden_Taxes        = new Eboekhouden_Taxes();
		$this->Eboekhouden_Largenumbers = new Eboekhouden_Largenumbers();
		$this->Eboekhouden_Session      = new Eboekhouden_Session();
		$this->Eboekhouden_Plugins      = new Eboekhouden_Plugins();
	}

	private function ebh_build_customer( $order ) {
		/**
		 * Customer Format
		 * <NAW>
		 *  <BEDRIJF>Yvonne van der A-Mulder</BEDRIJF>
		 *  <ADRES>Hondsdraflaan 14</ADRES>
		 *  <POSTCODE>2121RA</POSTCODE>
		 *  <PLAATS>Bennebroek</PLAATS>
		 *  <TELEFOON>023-5849879</TELEFOON>
		 *  <EMAIL>info@email.nl</EMAIL>
		 * </NAW>
		 */

		$customer = array(
			'BEDRIJF'  => strlen( $order->get_billing_company() ) > 0 ? $order->get_billing_company() : implode( ' ', array(
				$order->get_billing_first_name(),
				$order->get_billing_last_name()
			) ),
			'ADRES'    => implode( ' ', array(
				$order->get_billing_address_1(),
				$order->get_billing_address_2()
			) ),
			'POSTCODE' => $order->get_billing_postcode(),
			'PLAATS'   => $order->get_billing_city(),
			'TELEFOON' => $order->get_billing_phone(), // RBS 260117
			'EMAIL'    => $order->get_billing_email(), // RBS 260117
		);

		$vat_number = get_post_meta( $order->get_id(), '_vat_number', true );
		if ( '' !== $vat_number ) {
			$customer['OBNUMMER'] = (string) $vat_number;
		}

		$this->ebh_export_customer = apply_filters( 'ebh_filter_build_customer', $customer, $order->get_id(), $order );
	}

	private function ebh_wc_order_items( $order ) {
		foreach ( $order->get_items() as $orderItem ) {
			$item_data        = $orderItem->get_data();
			$orderItemProduct = new WC_Product( $orderItem['product_id'] );

			$mutation = new Eboekhouden_Mutation( 'orderitem', $order->get_id() );
			$mutation->SetTotal( (float) $item_data['total'] + (float) $item_data['total_tax'] );
			$mutation->SetSubTotal( (float) $item_data['total'] );
			$mutation->SetTaxTotal( (float) $item_data['total_tax'] );
			$mutation->SetLargeNumber( $this->Eboekhouden_Largenumbers->ebhGetLargenumber( 'paymentcost' ) );

			$taxCode = $this->Eboekhouden_Taxes->GetTaxCode( $orderItem, $order->get_id(), $orderItemProduct->is_virtual() );
			$mutation->SetTaxPercent( $taxCode );

			$this->ebh_export_mutations[] = $mutation->GetMutation();
		}
	}

	private function ebh_wc_shipping( $order ) {
		foreach ( $order->get_shipping_methods() as $shipping_id => $shipping ) {
			$mutation = new Eboekhouden_Mutation( 'shipping', $order->get_id() );
			$mutation->SetTotal( (float) $shipping->get_total() + (float) $shipping->get_total_tax() );
			$mutation->SetSubTotal( (float) $shipping->get_total() );
			$mutation->SetTaxTotal( (float) $shipping->get_total_tax() );
			$mutation->SetLargeNumber( $this->Eboekhouden_Largenumbers->ebhGetLargenumber( 'shippingcost' ) );

			$taxCode = $this->Eboekhouden_Taxes->GetTaxCode( $shipping, $order->get_id() );
			$mutation->SetTaxPercent( $taxCode );

			$this->ebh_export_mutations[] = $mutation->GetMutation();
		}
	}

	private function ebh_wc_fees( $order ) {
		foreach ( $order->get_items( 'fee' ) as $key => $fee ) {
			$mutation = new Eboekhouden_Mutation( 'fees', $order->get_id() );
			$mutation->SetTotal( (float) $fee->get_total() + (float) $fee->get_total_tax() );
			$mutation->SetSubTotal( (float) $fee->get_total() );
			$mutation->SetTaxTotal( (float) $fee->get_total_tax() );
			$mutation->SetLargeNumber( $this->Eboekhouden_Largenumbers->ebhGetLargenumber( 'paymentcost' ) );
			$taxCode = $this->Eboekhouden_Taxes->GetTaxCode( $fee, $order->get_id() );
			$mutation->SetTaxPercent( $taxCode );

			$this->ebh_export_mutations[] = $mutation->GetMutation( true );
		}
	}

	/*public function ebhExportOrder($order) {
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
	}*/

	public function ebhExportOrder() {
		$export_data = $this->ebhBuildExport();

		if ( get_post_meta( $this->wc_order_id, 'mutation_nr', true ) ) {
			$action = 'ALTER_MUTATIE';
		} else {
			$action = 'ADD_MUTATIE';
		}

		$Eboekhouden_Connector = new Eboekhouden_Connector();
		$result                = $Eboekhouden_Connector->ebhSend( $action, $export_data );

		$ebh_order_number = apply_filters( 'woocommerce_order_number', $this->wc_order_id, $this->wc_order );

		if ( isset( $result->RESULT ) && isset( $result->MUTNR ) ) {
			$message = 'Order ' . $ebh_order_number . ": " . $result->RESULT . " mutatie " . $result->MUTNR;
			//$this->Eboekhouden_Session->ebhAddNotice($this->Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message);
			$this->Eboekhouden_Session->ebhAddNotice( Eboekhouden_Session::MESSAGE_TYPE_SUCCESS, $message );

			$return = array(
				'order_id'    => $this->wc_order_id,
				'mutation_nr' => (int) $result->MUTNR
			);

		} else {
			$message = $ebh_order_number . ": " . $result->ERROR->CODE . " " . $result->ERROR->DESCRIPTION;
			ebh_debug_message( $message, 'Eboekhouden_Export', 'ebhExportOrder', 'export.log' );
			$this->Eboekhouden_Session->ebhAddNotice( $this->Eboekhouden_Session::MESSAGE_TYPE_ERROR, $message );

			$return = array(
				'order_id'    => $this->wc_order_id,
				'mutation_nr' => false
			);

		}

		return $return;
	}

	private function is_order_fully_refunded() {
		if ( $this->wc_order->get_parent_id() === 0 ) {
			return false;
		}

		$refund = new WC_Order_Refund( $this->wc_order->get_id() );
		if ( 'Order fully refunded.' !== $refund->get_reason() ) {
			return false;
		}

		return true;
	}

	public function ebhBuildExport() {
		// Fully refunded orders do not have any refunded items so we need to get the parent items.
		if ( $this->is_order_fully_refunded() ) {
			$parent_order = wc_get_order( $this->wc_order->get_parent_id() );
			$this->ebh_build_customer( $parent_order );
			$this->ebh_wc_order_items( $parent_order );
			$this->ebh_wc_shipping( $parent_order );
			$this->ebh_wc_fees( $parent_order );

			// Items need to be negative?
			foreach ( $this->ebh_export_mutations as $index => $mutation ) {
				foreach ( $mutation['MUTATIEREGEL'] as $key => $value ) {
					if ( 'BEDRAGINCL' === $key || 'BEDRAGEXCL' === $key || 'BTWBEDRAG' === $key ) {
						if ( $value >= 0 ) {
							$this->ebh_export_mutations[ $index ]['MUTATIEREGEL'][ $key ] = - $value;
						} else {
							// Discounts needs to be positive.
							$this->ebh_export_mutations[ $index ]['MUTATIEREGEL'][ $key ] = abs( $value );
						}
					}
				}
			}
		} else {
			// No fully refunded order.
			$this->ebh_build_customer( $this->wc_order );
			$this->ebh_wc_order_items( $this->wc_order );
			$this->ebh_wc_shipping( $this->wc_order );
			$this->ebh_wc_fees( $this->wc_order );
		}

		$return['MUTATIE'] = array(
			'NAW'              => $this->ebh_export_customer,
			'SOORT'            => 2,
			'REKENING'         => 1300,
			'INEX'             => 'EX',
			'FACTUUR'          => $this->wc_order->get_order_number(),
			'BETALINGSTERMIJN' => $this->Eboekhouden_Settings->ebhGetOption( 'ebh_payment_term', 30 ),
			'DATUM'            => date( 'd-m-Y', strtotime( $this->wc_order->get_date_created() ) ),
			'OMSCHRIJVING'     => $this->get_payment_reference( $this->wc_order ),
			'MUTATIEREGELS'    => $this->ebh_export_mutations
		);

		return apply_filters( 'ebh_filter_build_export', $return, $this->wc_order_id, $this->wc_order_id );
	}

	private function get_payment_reference( $order ) {
		$payment_reference = $this->get_invoice_number( $order );

		if ( $order->get_parent_id() !== 0 ) {
			// Is refund.
			$parent_order = wc_get_order( $order->get_parent_id() );

			$payment_reference = 'Credit ' . $payment_reference . ' Refund ' . $order->get_id() . ' for Order ' . $parent_order->get_order_number();
		} else {
			$payment_reference = 'Invoice ' . $payment_reference . ' Order ' . $order->get_order_number();
		}

		return $payment_reference;
	}

	private function get_invoice_number( $order ) {
		// WooCommerce PDF Invoices plugins?
		if ( function_exists( 'WPI' ) && function_exists( 'WPIP' ) && false !== BEWPI_Abstract_Invoice::exists( $order->get_id() ) ) {
			if ( $order->get_parent_id() !== 0 ) {
				// Refund.
				$credit_note = new BEWPIP_Credit_Note( $order->get_id() );

				return $credit_note->get_formatted_number();
			} else {
				$invoice = WPI()->get_invoice( $order->get_id() );

				return $invoice->get_formatted_number();
			}
		}

		// Use order number as invoice number.
		if ( $order->get_parent_id() !== 0 ) {
			// Is refund.
			$parent_order = wc_get_order( $order->get_parent_id() );

			return $parent_order->get_order_number();
		}

		return $order->get_order_number();
	}
}
