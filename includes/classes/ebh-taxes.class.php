<?php

defined( 'ABSPATH' ) || exit;

class Eboekhouden_Taxes {

	private $ebh_tax_codes;
	private $wc_tax;

	function __construct() {
		$this->ebh_tax_codes = array();
		$this->init();
	}

	private function init() {
		$this->ebh_create_taxcodes_array();
		$this->wc_tax = new WC_Tax();
	}

	private function ebh_create_taxcodes_array() {
		$this->ebh_tax_codes['NONE']        = 'GEEN';
		$this->ebh_tax_codes['LOW']         = 'LAAG_VERK';
		$this->ebh_tax_codes['HIGH']        = 'HOOG_VERK';
		$this->ebh_tax_codes['VHIGH']       = 'HOOG_VERK_21';
		$this->ebh_tax_codes['ZERO_IN_EU']  = 'BI_EU_VERK';
		$this->ebh_tax_codes['ZERO_OUT_EU'] = 'BU_EU_VERK';
	}

	/**
	 * Get tax code for ebh.
	 * We do not want to find the tax rates based on the tax class since the tax rates can change over time while the orders do not.
	 *
	 * @param object $item Can be one of many WC Order Item types like line items, fees etc.
	 * @param int    $order_id WC Order ID.
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function GetTaxCode( $item, $order_id, $virtual = false ) {
		// ICP.
		// WooCommerce EU VAT Number plugin should be used to validate VAT ID with VIES.
		$order = wc_get_order( $order_id );

		// Check if refund.
		if ( $order->get_parent_id() !== 0 ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		if ( $order->get_billing_country() !== $order->get_shipping_country() ) {
			wc_get_logger()->critical( sprintf( 'Billing country is not the same as shipping country on Order #%s.', $order->get_id() ) );
		}

		// NL always higher rate.
		if ( $order->get_billing_country() === 'NL' ) {
			return 'HOOG_VERK';
		}

		// Non-EU.
		if ( false === in_array( $order->get_billing_country(), WC()->countries->get_european_union_countries(), true ) ) {
			return 'BU_EU_VERK';
		}

		// Intra-Community Goods & Services.
		$vat_number = get_post_meta( $order_id, '_vat_number', true );
		if ( ! empty( $vat_number ) ) {

			$is_vat_exempt = get_post_meta( $order_id, 'is_vat_exempt', true );
			if ( $is_vat_exempt !== 'yes' ) {
				wc_get_logger()->critical( sprintf( 'VAT is not exempt while VAT number exists on Order #%s.', $order->get_id() ) );
			}

			if ( $virtual ) {
				return 'BI_EU_VERK_D'; // Services EU.
			}

			return 'BI_EU_VERK'; // Goods EU.
		}

		// Private EU orders.
		// No tax.
		if ( (float) $item->get_total_tax() === 0.00 ) {

			wc_get_logger()->critical( sprintf( 'No tax on private Order #%s.', $order->get_id() ) );

			return 'GEEN';
		}

		if ( (float) abs( $item->get_total() ) === 0.00 ) {
			wc_get_logger()->critical( sprintf( 'Total amount zero Order #%s.', $order->get_id() ) );

			return 'GEEN';
		}

		// EU OSS private orders.
		// Mutation should contain another row for the VAT amount.
		$rate_percent = round( (float) abs( $item->get_total_tax() ) / (float) abs( $item->get_total() ) * 100 );
		if ( (int) $rate_percent > 9 ) {

			wc_get_logger()->critical( sprintf( 'Unie mutation on Order #%s.', $order->get_id() ) );

			// Unieregeling.
			$unie_date = new WC_DateTime( '2022-04-01', new DateTimeZone( wp_timezone_string() ) );
			if ( $order->get_date_paid() < $unie_date ) {
				return 'HOOG_VERK';
			}

			return 'GEEN';
		}

		// Reduced rate for NL.
		if ( $order->get_billing_country() === 'NL' && ( (int) $rate_percent === 9 || (int) $rate_percent === 6 ) ) {
			return 'LAAG_VERK';
		}

		// Default.
		return 'GEEN';
	}
}
