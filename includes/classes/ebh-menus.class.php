<?php
if ( ! function_exists( 'is_admin' ) && ! defined( '_EBOEKHOUDEN_PLUGIN' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * @todo:
 * - [ ] Add 'advanced' menu WHEN 'debug' is enabled??
 *      >> see log files
 *      >> see 'information'        (system, phpinfo, ...)
 *      >> 'tools'
 * - [ ]
 */

if ( ! class_exists( "Eboekhouden_Menus" ) ) {

	class Eboekhouden_Menus {


		private $Eboekhouden_Session;
		private $Eboekhouden_Debug;


		function __construct() {

			$this->Eboekhouden_Session = new Eboekhouden_Session();
			$this->Eboekhouden_Debug   = new Eboekhouden_Debug();


			add_action( 'admin_menu', array( $this, 'ebh_admin_menus' ) );
		}


		public function ebh_admin_menus() {
			add_menu_page( 'e-Boekhouden.nl', 'e-Boekhouden.nl', 'manage_options', 'e-boekhouden', array(
				$this,
				'ebh_intro_page'
			), EBOEKHOUDEN_URL_ASSETS . 'images/ebh_icon.png' );
			add_submenu_page( 'e-boekhouden', __( 'Orders', 'eboekhouden' ), __( 'Orders', 'eboekhouden' ), 'manage_options', 'eboekhouden-orders', array(
				$this,
				'ebh_orders_page'
			) );
			remove_submenu_page( 'e-boekhouden', 'e-boekhouden' );
			add_submenu_page( 'e-boekhouden', __( 'Products', 'eboekhouden' ), __( 'Products', 'eboekhouden' ), 'manage_options', 'eboekhouden-products', array(
				$this,
				'ebh_products_page'
			) );

			/* Disabled: iFrame to 'eboekhouden' is NOT "allowed" >> "consult" with eboekhouden if they can 'allow' it */
			//add_submenu_page('e-boekhouden', 'e-Boekhouden', 'e-Boekhouden', 'manage_options', 'eboekhouden-boekhouden', array($this, 'ebh_eboekhouden_page'));
			add_submenu_page( 'e-boekhouden', __( 'Settings', 'eboekhouden' ), __( 'Settings', 'eboekhouden' ), 'manage_options', 'eboekhouden-settings', array(
				$this,
				'ebh_settings_page'
			) );

			if ( defined( 'EBOEKHOUDEN_DEBUG' ) ) {
				add_submenu_page( 'e-boekhouden', __( 'Logs', 'eboekhouden' ), __( 'Logs', 'eboekhouden' ), 'manage_options', 'eboekhouden-logs', array(
					$this,
					'ebh_logs_page'
				) );
			}

			add_submenu_page( 'e-boekhouden', __( 'Help', 'eboekhouden' ), __( 'Help', 'eboekhouden' ), 'manage_options', 'eboekhouden-help', array(
				$this,
				'ebh_help_page'
			) );


		}

		function ebh_intro_page() {
			$output = ebh_load_template( 'intropage' );
			echo $output;
		}


		function ebh_orders_page() {
			if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'ebh_export' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'ebh_export' ) ) {
				/*** ROBAS: Must be HIGHER than 999, because of 'WPO_WCPDF' Plugin ***/
				//$prio_ebh_export_orders = 1001;
				//add_action('init', array($this,'ebh_export_orders'), $prio_ebh_export_orders );
				$this->ebh_export_orders();
			}

			$Eboekhouden_Orders    = new Eboekhouden_Orders();
			$Eboekhouden_Orderlist = new Eboekhouden_Orderlist();

			$vars = array(
				'base_url'    => 'admin.php?page=eboekhouden-orders',
				'active_tab'  => ( filter_input( INPUT_GET, 'tab' ) ) ? filter_input( INPUT_GET, 'tab' ) : 'not_mutated',
				'order_count' => array(
					'all'         => Eboekhouden_Orders::CountOrders( 'all' ),
					'not_mutated' => Eboekhouden_Orders::CountOrders( 'not_mutated' ),
					'mutated'     => Eboekhouden_Orders::CountOrders( 'mutated' )
				),
				'order_list'  => $Eboekhouden_Orderlist
			);

			$output = ebh_load_template( 'orderspage', $vars );
			echo $output;
		}


		public function ebh_products_page() {
			$Eboekhouden_Productlist = new Eboekhouden_Productlist();

			if ( isset( $_POST['action'] ) && $_POST['action'] == 'ebhassignlargenumber' ) {
				$Eboekhouden_Largenumbers = new Eboekhouden_Largenumbers();
				$assign_largenumbers      = $Eboekhouden_Largenumbers->ebhAssignLargenumber( false );
			} else {
				$assign_largenumbers = false;
			}

			$vars = array(
				'base_url'            => 'admin.php?page=eboekhouden-products',
				'active_tab'          => ( filter_input( INPUT_GET, 'tab' ) ) ? filter_input( INPUT_GET, 'tab' ) : 'all',
				'product_list'        => $Eboekhouden_Productlist,
				'assign_largenumbers' => $assign_largenumbers
			);

			$output = ebh_load_template( 'productspage', $vars );
			echo $output;

		}


		public function ebh_export_orders() {
			$items = $_POST['ebh_orders'];


			if ( count( $items ) ) {
				$args = array(
					'post_type'   => array( 'shop_order', 'shop_order_refund' ),
					'post_status' => array( 'wc-processing', 'wc-completed', 'wc-refunded' ),
					'nopaging'    => true,
					'post__in'    => $items
				);

				//$Eboekhouden_Export = new Eboekhouden_Export();
				$exports = array();
				$orders  = get_posts( $args );


				foreach ( $orders as $order ) {
					$Eboekhouden_Export = new Eboekhouden_Export( $order->ID );
					$exports[]          = $Eboekhouden_Export->ebhExportOrder();
				}

				foreach ( $exports as $export ) {
					if ( isset( $export['order_id'] ) && isset( $export['mutation_nr'] ) && isset( $export['mutation_nr'] ) !== false ) {
						if ( false === defined( 'EBOEKHOUDEN_DEBUG' ) ) {
							update_post_meta( $export['order_id'], 'mutation_nr', $export['mutation_nr'] );
						}
					}
				}
			}

			$referrer = $_POST['_wp_http_referer'];
			wp_redirect( $referrer );

		}


		public function ebh_eboekhouden_page() {

			// put in "$vars"
//            settings_errors();
//            settings_fields('ebh_pluginPage');
//            do_settings_sections('ebh_pluginPage');
//            submit_button();            

			/*Load denied by X-Frame-Options: https://secure4.e-boekhouden.nl/bh/ does not permit cross-origin framing. */

			$vars   = array(
				//'ebh_url' => 'https://secure4.e-boekhouden.nl/bh/'              // << 'setting' ??
				//'ebh_url' => 'https://www.robas.com/'              // << 'setting' ??
				//'ebh_url' => 'https://secure.e-boekhouden.nl/bh/?ts=143533362764&c='
				'ebh_url' => 'https://secure.e-boekhouden.nl/bh/'
			);
			$output = ebh_load_template( 'eboekhoudenpage', $vars );
			echo $output;
		}


		public function ebh_settings_page() {
			// check if 'license' options are set (username, security_1, security_2)
			// IF NOT >> "active tab" == 'license

			$active_tab = 'general';
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			}

			$vars   = array(
				'active_tab' => $active_tab
			);
			$output = ebh_load_template( 'settingspage', $vars );
			echo $output;
		}


		public function ebh_logs_page() {
			$vars = array();

			if ( isset( $_GET['view'] ) ) {
				$view         = explode( ';', $_GET['view'] );
				$vars['name'] = $view[1];
				$vars['view'] = $this->Eboekhouden_Debug->ebhViewLog( $view[0], $view[1] );
			} elseif ( isset( $_GET['download'] ) ) {
				$download = $this->Eboekhouden_Debug->ebhDownloadLog( $_GET['download'] );
			} elseif ( isset( $_GET['delete'] ) ) {
				$delete = $this->Eboekhouden_Debug->ebhDeleteLog( $_GET['delete'] );
				wp_redirect( 'admin.php?page=eboekhouden-logs' );
			} else {
				$logs         = $this->Eboekhouden_Debug->ebhGetLogs();
				$vars['logs'] = $logs;
			}


			$output = ebh_load_template( 'logspage', $vars );
			echo $output;
		}


		public function ebh_help_page() {
			$active_tab = 'info';
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			}

			$vars   = array(
				'active_tab' => $active_tab
			);
			$output = ebh_load_template( 'helppage', $vars );
			echo $output;
		}

	}

}