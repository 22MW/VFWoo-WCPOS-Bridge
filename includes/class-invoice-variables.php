<?php
/**
 * VFWoo variables for the Webkul invoice template editor and the ticket.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Invoice_Variables {
	/**
	 * Invoice types whose ticket carries the customer data. Extend here for rectificativas.
	 */
	private const CUSTOMER_TYPES = array( 'F1', 'F3' );

	private const EDITOR_PAGE = 'wc-pos-invoice-templates';

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_script' ) );
	}

	/**
	 * Variables offered in the template editor: tag => label.
	 *
	 * @return array<int,array{tag:string,label:string}>
	 */
	public static function definitions(): array {
		return array(
			array( 'tag' => '${vfwoo_company_name}', 'label' => __( 'Tienda: nombre o razón social (VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_company_nif}', 'label' => __( 'Tienda: NIF (VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_company_address}', 'label' => __( 'Tienda: dirección (VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_company_phone}', 'label' => __( 'Tienda: teléfono (VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_company_email}', 'label' => __( 'Tienda: email (VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_company_logo}', 'label' => __( 'Tienda: logo (imagen, VFWoo)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_invoice_number}', 'label' => __( 'Factura: número', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_invoice_type}', 'label' => __( 'Factura: tipo (F1, F2…)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_invoice_date}', 'label' => __( 'Factura: fecha de expedición', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_legal_legend}', 'label' => __( 'Factura: leyenda legal Veri*Factu', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_verification_url}', 'label' => __( 'Factura: URL de cotejo', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_qr}', 'label' => __( 'Factura: código QR (imagen)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_customer_name}', 'label' => __( 'Cliente: nombre (solo F1/F3)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_customer_nif}', 'label' => __( 'Cliente: NIF (solo F1/F3)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_customer_address}', 'label' => __( 'Cliente: domicilio (solo F1/F3)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_customer_email}', 'label' => __( 'Cliente: email (solo F1/F3)', 'vfwoo-webkul-pos-bridge' ) ),
			array( 'tag' => '${vfwoo_customer_phone}', 'label' => __( 'Cliente: teléfono (solo F1/F3)', 'vfwoo-webkul-pos-bridge' ) ),
		);
	}

	public function enqueue_editor_script(): void {
		// The screen id contains Webkul's translatable menu title, so the page slug is checked instead.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || self::EDITOR_PAGE !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_enqueue_script(
			'vfwoo-webkul-invoice-editor',
			VFWOO_WEBKUL_URL . 'assets/js/invoice-editor.js',
			array( 'wp-hooks', 'wp-element' ),
			VFWOO_WEBKUL_VERSION,
			false
		);
		wp_add_inline_script(
			'vfwoo-webkul-invoice-editor',
			'window.vfwooWebkulInvoiceVars = ' . wp_json_encode( self::definitions() ) . ';',
			'before'
		);
	}

	/**
	 * Store data configured in VFWoo, read through its public shortcode.
	 *
	 * @return array<string,string>
	 */
	public static function store(): array {
		$store = array();
		foreach ( array( 'company_name', 'company_nif', 'company_address', 'company_phone', 'company_email', 'company_logo' ) as $field ) {
			$value           = do_shortcode( '[verifacwoo_config return="' . $field . '"]' );
			$store[ $field ] = trim( html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' ) );
		}
		return $store;
	}

	/**
	 * Customer data for the ticket. Only invoices addressed to a customer (F1/F3) carry it.
	 * Without an invoice yet, an order that has a tax ID is treated as complete (F1).
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $data  VFWoo ticket data.
	 */
	public static function customer_block( \WC_Order $order, array $data ): array {
		$type = strtoupper( (string) ( $data['invoice_type'] ?? '' ) );
		$nif  = trim( (string) $order->get_meta( '_billing_nif', true ) );

		if ( ! in_array( $type, self::CUSTOMER_TYPES, true ) && ! ( '' === $type && '' !== $nif ) ) {
			return array( 'visible' => false );
		}

		$customer_id = (int) $order->get_customer_id();
		$customer    = $customer_id ? new \WC_Customer( $customer_id ) : null;

		$first = $customer ? $customer->get_billing_first_name() : '';
		$last  = $customer ? $customer->get_billing_last_name() : '';
		if ( '' === trim( $first . $last ) ) {
			$first = $customer ? $customer->get_first_name() : $order->get_billing_first_name();
			$last  = $customer ? $customer->get_last_name() : $order->get_billing_last_name();
		}

		$street = $customer ? array( $customer->get_billing_address_1(), $customer->get_billing_address_2() ) : array();
		$town   = $customer ? trim( $customer->get_billing_postcode() . ' ' . $customer->get_billing_city() ) : '';
		$parts  = array_filter(
			array(
				implode( ' ', array_filter( $street ) ),
				$town,
				$customer ? $customer->get_billing_state() : '',
				$customer ? $customer->get_billing_country() : '',
			)
		);

		return array(
			'visible' => true,
			'name'    => trim( $first . ' ' . $last ),
			'nif'     => $nif,
			'address' => implode( ', ', $parts ),
			'email'   => $customer && $customer->get_billing_email() ? $customer->get_billing_email() : $order->get_billing_email(),
			'phone'   => $customer && $customer->get_billing_phone() ? $customer->get_billing_phone() : $order->get_billing_phone(),
		);
	}
}
