<?php
/**
 * Read-only sales summary by brand for POS orders.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Brand_Report {
	private const MAX_DAYS = 366;
	private const TAXONOMY = 'product_brand';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'vfwoo-webkul/v1',
			'/brand-report',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // Authenticated inside with Webkul's POS session.
				'callback'            => array( $this, 'handle' ),
			)
		);
	}

	public function handle( \WP_REST_Request $request ) {
		if ( ! Pos_Auth::is_valid( $request->get_param( 'logged_in_user_id' ) ) ) {
			return Pos_Auth::unauthorized();
		}

		$range = $this->range( (string) $request->get_param( 'start_date' ), (string) $request->get_param( 'end_date' ) );
		if ( is_string( $range ) ) {
			return array(
				'success' => false,
				'status'  => 400,
				'message' => $range,
			);
		}

		return array(
			'success' => true,
			'data'    => $this->summarize( $range[0], $range[1] ),
		);
	}

	/**
	 * @return array{0:int,1:int}|string Timestamps, or an error message.
	 */
	private function range( string $start, string $end ) {
		$pattern = '/^\d{4}-\d{2}-\d{2}$/';
		if ( ! preg_match( $pattern, $start ) || ! preg_match( $pattern, $end ) ) {
			return __( 'Fechas no válidas.', 'vfwoo-webkul-pos-bridge' );
		}

		$timezone = wp_timezone();
		$from     = date_create_immutable( $start . ' 00:00:00', $timezone );
		$to       = date_create_immutable( $end . ' 23:59:59', $timezone );
		if ( ! $from || ! $to || $to < $from ) {
			return __( 'Fechas no válidas.', 'vfwoo-webkul-pos-bridge' );
		}
		if ( ( $to->getTimestamp() - $from->getTimestamp() ) > self::MAX_DAYS * DAY_IN_SECONDS ) {
			return __( 'El rango máximo es de un año.', 'vfwoo-webkul-pos-bridge' );
		}

		return array( $from->getTimestamp(), $to->getTimestamp() );
	}

	private function summarize( int $from, int $to ): array {
		$orders = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'limit'        => -1,
				'status'       => array( 'completed', 'processing' ),
				'date_created' => $from . '...' . $to,
				'meta_key'     => '_wk_wc_pos_outlet', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
			)
		);

		$brands_of = array();
		$brands    = array();
		$totals    = $this->blank();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				$product_id = (int) $item->get_product_id();
				if ( ! isset( $brands_of[ $product_id ] ) ) {
					$brands_of[ $product_id ] = $this->brand_names( $product_id );
				}

				$net    = (float) $item->get_total();
				$gross  = $net + (float) $item->get_total_tax();
				$refund = (float) $order->get_total_refunded_for_item( $item_id );
				$units  = (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item( $item_id );

				foreach ( $brands_of[ $product_id ] as $brand ) {
					if ( ! isset( $brands[ $brand ] ) ) {
						$brands[ $brand ] = $this->blank() + array( 'name' => $brand, 'products' => array() );
					}
					$this->add( $brands[ $brand ], $order->get_id(), $units, $net, $gross, $refund );

					if ( ! isset( $brands[ $brand ]['products'][ $product_id ] ) ) {
						$brands[ $brand ]['products'][ $product_id ] = $this->blank() + array( 'name' => $item->get_name() );
					}
					$this->add( $brands[ $brand ]['products'][ $product_id ], $order->get_id(), $units, $net, $gross, $refund );
				}
			}
			++$totals['orders'];
		}

		foreach ( $brands as &$brand ) {
			$brand['products'] = $this->finish( $brand['products'] );
			$brand             = $this->round_row( $brand );
		}
		unset( $brand );

		$totals = $this->round_row( $totals );
		$rows   = $this->finish( $brands );

		return array(
			'brands'   => $rows,
			'orders'   => $totals['orders'],
			'currency' => get_woocommerce_currency(),
		);
	}

	/**
	 * @return string[]
	 */
	private function brand_names( int $product_id ): array {
		$terms = get_the_terms( $product_id, self::TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array( __( 'Sin marca', 'vfwoo-webkul-pos-bridge' ) );
		}

		return array_map(
			static function ( $term ) {
				return html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			},
			$terms
		);
	}

	private function blank(): array {
		return array(
			'units'    => 0,
			'net'      => 0.0,
			'gross'    => 0.0,
			'refund'   => 0.0,
			'orders'   => 0,
			'order_ids' => array(),
		);
	}

	private function add( array &$row, int $order_id, int $units, float $net, float $gross, float $refund ): void {
		$row['units']  += $units;
		$row['net']    += $net;
		$row['gross']  += $gross;
		$row['refund'] += $refund;
		if ( ! isset( $row['order_ids'][ $order_id ] ) ) {
			$row['order_ids'][ $order_id ] = true;
			++$row['orders'];
		}
	}

	private function round_row( array $row ): array {
		$row['net']    = round( $row['net'], 2 );
		$row['gross']  = round( $row['gross'], 2 );
		$row['refund'] = round( $row['refund'], 2 );
		unset( $row['order_ids'] );
		return $row;
	}

	/**
	 * Round, drop helper keys and sort by net sales.
	 */
	private function finish( array $rows ): array {
		$rows = array_map(
			function ( $row ) {
				return isset( $row['order_ids'] ) ? $this->round_row( $row ) : $row;
			},
			array_values( $rows )
		);
		usort(
			$rows,
			static function ( $a, $b ) {
				return $b['net'] <=> $a['net'];
			}
		);
		return $rows;
	}
}
