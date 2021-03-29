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
	 */
	public function GetTaxCode( $item, $order_id, $virtual = false ) {
		// ICP.
		// WooCommerce EU VAT Number plugin should be used to validate VAT ID with VIES.
		$order = wc_get_order( $order_id );

		// Check if refund.
		if ( $order->get_parent_id() !== 0 ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		$is_vat_exempt = get_post_meta( $order_id, 'is_vat_exempt', true );
		$vat_number    = get_post_meta( $order_id, '_vat_number', true );
		if ( 'NL' !== $order->get_billing_country() && $is_vat_exempt === 'yes' && ! empty( $vat_number ) ) {

			if ( $virtual ) {
				return 'BI_EU_VERK_D'; // Services EU.
			}

			return 'BI_EU_VERK'; // Goods EU.
		}

		// Non-EU.
		if ( false === in_array( $order->get_billing_country(), WC()->countries->get_european_union_countries(), true ) ) {
			return 'BU_EU_VERK';
		}

		// Still no tax?
		if ( (float) $item->get_total_tax() === 0.00 ) {
			return 'GEEN';
		}

		$rate_percent = round( (float) abs( $item->get_total_tax() ) / (float) abs( $item->get_total() ) * 100 );
		// Standard rate.
		if ( (int) $rate_percent === 21 ) {
			return 'HOOG_VERK';
		}

		// Reduced rate.
		if ( (int) $rate_percent === 9 || (int) $rate_percent === 6 ) {
			return 'LAAG_VERK';
		}

		// Default.
		return 'GEEN';
	}
}
