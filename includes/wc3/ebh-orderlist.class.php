<?php
// Supports WC version 3.x.x

if ( ! function_exists( 'is_admin' ) && ! defined( '_EBOEKHOUDEN_PLUGIN' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'Eboekhouden_Orderlist' ) ) {


	class Eboekhouden_Orderlist extends WP_List_Table {

//        private $ebh_order;
//        private $ebh_order_id;
		private $Eboekhouden_Settings;
		private $Eboekhouden_Orders;
		private $Eboekhouden_Session;
		private $Eboekhouden_Plugins;
		private $max_orders_per_page;
		public $data;

		function __construct( $prepare_items = true ) {
			//global $status, $page;

			$parent_args = array(
				'singular' => __( 'order', 'eboekhouden' ),
				//singular name of the listed records
				'plural'   => __( 'orders', 'eboekhouden' ),
				//plural name of the listed records
				'ajax'     => false
				//does this table support ajax?
			);

			parent::__construct( $parent_args );
			$this->init();

			if ( $prepare_items ) {
				$this->prepare_items();
			}

		}

		private function init() {
			$this->Eboekhouden_Settings = new Eboekhouden_Settings();
			$this->Eboekhouden_Orders   = new Eboekhouden_Orders();
			$this->Eboekhouden_Session  = new Eboekhouden_Session();
			$this->Eboekhouden_Plugins  = new Eboekhouden_Plugins();

			$this->max_orders_per_page = $this->Eboekhouden_Settings->ebhGetOption( 'ebh_order_list_max' );

		}


		public function set_data( $data ) {
			$this->data = $data;
		}


		public function no_items() {
			_e( 'No Orders Found', 'eboekhouden' );
		}


		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				default:
					return false;
			}
		}


		public function get_sortable_columns() {
			$sortable_columns = array(
				'post_title'  => array( 'post_title', false ),
				'post_status' => array( 'post_status', false ),
				'post_date'   => array( 'post_date', false ),
				'mutation_nr' => array( 'mutation_nr', false )
			);

			return $sortable_columns;
		}


		public function get_columns() {
			$columns = array(
				'cb'          => '<input type="checkbox" />',
				'post_title'  => __( 'Order', 'eboekhouden' ),
				'post_date'   => __( 'Order Date', 'eboekhouden' ),
				'post_status' => __( 'Status', 'eboekhouden' ),
				'mutation_nr' => __( 'Mutation', 'eboekhouden' )
			);

			return $columns;
		}


		public function usort_reorder( $a, $b ) {
			$a       = (array) $a;
			$b       = (array) $b;
			$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ID';
			$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
			$result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

			return ( $order === 'asc' ) ? $result : - $result;
		}


		public function get_bulk_actions() {
			$actions = array(
				'ebh_export' => 'Export to e-Boekhouden.nl',
			);

			return $actions;
		}


		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="ebh_orders[]" value="%s" />', $item->ID
			);
		}


		public function column_ID( $item ) {
			$ebh_order_number = $this->Eboekhouden_Plugins->ebhGetOrderNumber( $item->ID );

			return '<a href="post.php?post=' . $item->ID . '&action=edit">' . $ebh_order_number . '</a>';
		}

		public function column_post_title( $item ) {
			$order = wc_get_order( $item->ID );

			if ( $order->get_parent_id() !== 0 ) {
				$parent_order = wc_get_order( $order->get_parent_id() );
				$title        = 'Refund ' . $order->get_id() . ' for Order <a href="post.php?post=' . $parent_order->get_id() . '&action=edit">' . $parent_order->get_order_number() . '</a>';
			} else {
				$title = '<a href="post.php?post=' . $order->get_id() . '&action=edit">' . $order->get_order_number() . '</a>';
			}

			return $title;
		}

		public function column_post_date( $item ) {
			return $item->post_date;
		}

		public function column_post_status( $item ) {
			return $item->post_status;
		}

		public function column_mutation_nr( $item ) {
			return $item->mutation_nr;
		}

		public function prepare_items() {
			$tab          = isset( $_GET['tab'] ) ? $_GET['tab'] : 'not_mutated';
			$per_page     = $this->max_orders_per_page;
			$current_page = $this->get_pagenum();

			if ( $current_page < 2 ) {
				$offset = 0;
			} else {
				$offset = $current_page * $per_page - $per_page;
			}

			$args = array(
				'post_type'      => array( 'shop_order', 'shop_order_refund' ),
				'post_status'    => array( 'wc-processing', 'wc-completed', 'wc-refunded' ),
				'posts_per_page' => $per_page,
				'offset'         => $offset,
				'orderby'        => ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'ID',
				'order'          => ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc',
			);

			$order_after_date = $this->Eboekhouden_Settings->ebhGetOption( 'ebh_order_after_date', '' );
			if ( '' !== $order_after_date ) {
				$args['date_query'] = array(
					'after'     => date( $order_after_date ),
					'inclusive' => false,
				);
			}

			// Search order.
			if ( isset( $_POST['s'] ) && '' !== $_POST['s'] ) {
				$s     = $_POST['s'];

				// Remove prefix.
				if ( 7 === strlen( $s ) ) {
					$s = (int) substr( $s, 1, 7 );
				}

				$args['post__in'] = array( (int) $s );
			}

			// Tabs.
			if ( 'not_mutated' === $tab ) {
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'mutation_nr',
						'compare' => 'NOT EXISTS'
					)
				);
			} elseif ( 'mutated' === $tab ) {
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'mutation_nr',
						'compare' => 'EXISTS'
					)
				);
			}

			$results = new WP_Query( $args );

			$columns               = $this->get_columns();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, array(), $sortable );

			$this->set_data( $results->get_posts() );
			$this->items = $this->data;

			$pagination_args = array(
				'total_items' => (int) $results->found_posts,
				'total_pages' => $results->max_num_pages,
				'per_page'    => $per_page,
			);

			$this->set_pagination_args( $pagination_args );
		}
	}
}
