<?php
/**
 * Completa el bloque VFWoo de un snapshot cuando la factura queda disponible
 * después del primer instante de cobro.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Integrations;

use WCPOS\WooCommercePOS\Services\Receipt_Snapshot_Store;

defined( 'ABSPATH' ) || exit;

/**
 * WCPOS crea el snapshot al cobrar. Dependiendo de la política de emisión, la
 * factura VFWoo puede quedar lista unos instantes después, en el mismo flujo o
 * al entrar en completed. Esta clase modifica exclusivamente el subárbol
 * fiscal.extra_fields.vfwoo con la lectura ya confirmada de VFWoo.
 */
final class WCPOS_Snapshot_Refresher {
	/** @var VFWoo_Fiscal_Data_Provider */
	private $provider;

	public function __construct() {
		$this->provider = new VFWoo_Fiscal_Data_Provider();

		/* Se ejecuta después de la emisión VFWoo (prioridad 5) y del snapshot WCPOS (10). */
		add_action( 'woocommerce_payment_complete', array( $this, 'refresh' ), 999 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'refresh' ), 20 );
		add_action( 'vfwoo_invoice_confirmed', array( $this, 'refresh_after_confirmation' ), 20, 1 );
	}

	/**
	 * Completa el bloque fiscal propio si ya existe una factura real VFWoo.
	 *
	 * @param int $order_id ID del pedido WooCommerce.
	 */
	public function refresh( $order_id ): void {
		$order_id = absint( $order_id );
		if ( ! $order_id || ! $this->is_enabled() ) {
			return;
		}

		$data = $this->provider->for_order( $order_id );
		if ( empty( $data['available'] ) ) {
			return;
		}

		$snapshot_store = Receipt_Snapshot_Store::instance();
		$snapshot       = $snapshot_store->get_snapshot( $order_id );
		if ( ! is_array( $snapshot ) || ! isset( $snapshot['fiscal'] ) || ! is_array( $snapshot['fiscal'] ) ) {
			return;
		}

		if ( ! isset( $snapshot['fiscal']['extra_fields'] ) || ! is_array( $snapshot['fiscal']['extra_fields'] ) ) {
			$snapshot['fiscal']['extra_fields'] = array();
		}

		if ( isset( $snapshot['fiscal']['extra_fields']['vfwoo'] ) && $snapshot['fiscal']['extra_fields']['vfwoo'] === $data ) {
			return;
		}

		$snapshot['fiscal']['extra_fields']['vfwoo'] = $data;
		$json                                        = wp_json_encode( $snapshot );
		if ( ! is_string( $json ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$order->update_meta_data( Receipt_Snapshot_Store::META_KEY_PAYLOAD, $json );
		$order->update_meta_data( Receipt_Snapshot_Store::META_KEY_CHECKSUM, hash( 'sha256', $json ) );
		$order->save();
	}

	/**
	 * Atiende confirmaciones asíncronas de VFWoo.
	 *
	 * @param int $order_id ID del pedido WooCommerce.
	 */
	public function refresh_after_confirmation( $order_id ): void {
		$this->refresh( $order_id );
	}

	/** @return bool */
	private function is_enabled(): bool {
		$settings = function_exists( 'woocommerce_pos_get_settings' ) ? woocommerce_pos_get_settings( 'vfwoo_bridge' ) : array();

		return is_array( $settings ) && ! empty( $settings['enabled'] );
	}
}
