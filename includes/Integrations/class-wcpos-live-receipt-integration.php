<?php
/**
 * Añade el bloque VFWoo al recibo live de WCPOS sin escribir en el pedido.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Algunas configuraciones de WCPOS imprimen el recibo live y no generan un
 * snapshot fiscal. WCPOS no expone un filtro para Receipt_Data_Builder, pero
 * entrega el payload live desde su endpoint REST oficial. El bridge completa
 * solamente la respuesta de ese endpoint para el usuario POS autenticado.
 */
final class WCPOS_Live_Receipt_Integration {
	/** @var VFWoo_Fiscal_Data_Provider */
	private $provider;

	public function __construct() {
		$this->provider = new VFWoo_Fiscal_Data_Provider();
		add_filter( 'rest_request_after_callbacks', array( $this, 'enrich_live_receipt_response' ), 20, 3 );
	}

	/**
	 * Inserta los datos fiscales en una respuesta GET de recibo WCPOS.
	 *
	 * @param mixed            $response Respuesta REST.
	 * @param array            $handler  Handler resuelto por WordPress.
	 * @param \WP_REST_Request $request  Petición REST.
	 * @return mixed
	 */
	public function enrich_live_receipt_response( $response, $handler, $request ) {
		unset( $handler );
		if ( ! $this->is_receipt_request( $request ) || is_wp_error( $response ) || ! $this->is_enabled() ) {
			return $response;
		}

		$is_response_object = is_object( $response ) && method_exists( $response, 'get_data' ) && method_exists( $response, 'set_data' );
		$body               = $is_response_object ? $response->get_data() : $response;
		if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return $response;
		}

		$order_id = absint( $request->get_param( 'order_id' ) );
		$data     = $this->provider->for_order( $order_id );
		if ( empty( $data['available'] ) ) {
			return $response;
		}

		if ( ! isset( $body['data']['fiscal'] ) || ! is_array( $body['data']['fiscal'] ) ) {
			return $response;
		}
		if ( ! isset( $body['data']['fiscal']['extra_fields'] ) || ! is_array( $body['data']['fiscal']['extra_fields'] ) ) {
			$body['data']['fiscal']['extra_fields'] = array();
		}

		$body['data']['fiscal']['extra_fields']['vfwoo'] = $data;
		if ( $is_response_object ) {
			$response->set_data( $body );
			return $response;
		}

		return $body;
	}

	/** @return bool */
	private function is_receipt_request( $request ): bool {
		if ( ! $request instanceof \WP_REST_Request || 'GET' !== $request->get_method() ) {
			return false;
		}

		return 1 === preg_match( '#^/wcpos/v1/receipts/\\d+$#', $request->get_route() );
	}

	/** @return bool */
	private function is_enabled(): bool {
		$settings = function_exists( 'woocommerce_pos_get_settings' ) ? woocommerce_pos_get_settings( 'vfwoo_bridge' ) : array();

		return is_array( $settings ) && ! empty( $settings['enabled'] );
	}
}
