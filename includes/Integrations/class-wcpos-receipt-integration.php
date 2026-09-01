<?php
/**
 * Añade los datos de ticket de solo lectura de VFWoo a snapshots fiscales WCPOS.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Integrations;

defined( 'ABSPATH' ) || exit;

final class WCPOS_Receipt_Integration {
	/** @var VFWoo_Fiscal_Data_Provider */
	private $provider;

	public function __construct() {
		$this->provider = new VFWoo_Fiscal_Data_Provider();
		/*
		 * WCPOS guarda el snapshot al cobrar. Para productos físicos WooCommerce
		 * suele mantener entonces el pedido en `processing`; VFWoo ya ha emitido
		 * en la prioridad anterior del mismo hook, pero su API de ticket solo
		 * exponía `completed`. Ampliamos exclusivamente la lectura del contrato:
		 * no emitimos, no numeramos y no convertimos una venta no facturada en
		 * disponible, pues Ticket_Fiscal_Data sigue comprobando la factura real.
		 */
		add_filter( 'vfwoo_ticket_estados_validos', array( $this, 'allow_wcpos_payment_status' ) );
		add_filter( 'woocommerce_pos_fiscal_snapshot_enrich', array( $this, 'enrich_snapshot' ), 20, 2 );
	}

	/**
	 * Permite leer el ticket VFWoo de una venta WCPOS ya cobrada.
	 *
	 * @param array $statuses Estados permitidos por VFWoo.
	 * @return array
	 */
	public function allow_wcpos_payment_status( $statuses ): array {
		$statuses = is_array( $statuses ) ? $statuses : array();
		if ( ! in_array( 'processing', $statuses, true ) ) {
			$statuses[] = 'processing';
		}

		return $statuses;
	}

	/**
	 * Conserva los datos WCPOS y añade un bloque de presentación con espacio de nombres.
	 *
	 * @param array $snapshot Snapshot fiscal de WCPOS.
	 * @param int   $order_id ID del pedido de WooCommerce.
	 * @return array
	 */
	public function enrich_snapshot( $snapshot, $order_id ): array {
		if ( ! is_array( $snapshot ) || ! $this->is_enabled() ) {
			return is_array( $snapshot ) ? $snapshot : array();
		}

		$data = $this->provider->for_order( absint( $order_id ) );
		if ( empty( $data['available'] ) ) {
			return $snapshot;
		}

		if ( ! isset( $snapshot['fiscal'] ) || ! is_array( $snapshot['fiscal'] ) ) {
			return $snapshot;
		}
		if ( ! isset( $snapshot['fiscal']['extra_fields'] ) || ! is_array( $snapshot['fiscal']['extra_fields'] ) ) {
			$snapshot['fiscal']['extra_fields'] = array();
		}

		$snapshot['fiscal']['extra_fields']['vfwoo'] = $data;

		return $snapshot;
	}

	/** @return bool */
	private function is_enabled(): bool {
		$settings = function_exists( 'woocommerce_pos_get_settings' ) ? woocommerce_pos_get_settings( 'vfwoo_bridge' ) : array();

		return is_array( $settings ) && ! empty( $settings['enabled'] );
	}
}
