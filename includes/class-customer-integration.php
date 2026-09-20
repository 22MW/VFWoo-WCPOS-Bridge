<?php
/**
 * Customer tax ID (NIF/NIE/CIF): required, verified on save and copied to POS orders.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Customer_Integration {
	private const USER_NIF_META   = 'billing_nif';
	private const USER_CHECK_META = '_vfwoo_webkul_nif_check';
	private const ORDER_NIF_META  = '_billing_nif';

	/**
	 * Result of the tax ID check for the customer being saved.
	 *
	 * @var array|null
	 */
	private $pending = null;

	public function __construct() {
		add_filter( 'wkwcpos_modify_customer_update_request', array( $this, 'validate_request' ), 10, 1 );
		add_action( 'wkwc_add_meta_customer_data', array( $this, 'save_nif' ), 10, 2 );
		add_filter( 'manage_custom_customer_details_support', array( $this, 'add_customer_data' ), 20, 2 );
		add_filter( 'wkwcpos_modify_customer_details_by_customer_id_at_pos', array( $this, 'add_customer_data_by_id' ), 20, 2 );
		add_filter( 'wkwcpos_alter_pos_order', array( $this, 'copy_nif_to_order' ), 20, 2 );
	}

	/**
	 * Reject the customer before Webkul saves it. Webkul turns any exception
	 * raised here into an error message shown to the cashier.
	 *
	 * @param mixed $data Serialized POS customer form.
	 * @throws \Exception When the tax ID is missing or not valid.
	 */
	public function validate_request( $data ) {
		$this->pending = null;
		$this->allow_pos_registration();
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$fields = array();
		foreach ( $data as $item ) {
			if ( is_array( $item ) && isset( $item['name'] ) ) {
				$fields[ (string) $item['name'] ] = isset( $item['value'] ) ? (string) $item['value'] : '';
			}
		}

		$nif = self::normalize( $fields['pos_customer_nif'] ?? '' );
		if ( '' === $nif ) {
			throw new \Exception( esc_html__( 'El DNI/NIE/CIF del cliente es obligatorio.', 'vfwoo-webkul-pos-bridge' ) );
		}
		if ( class_exists( 'VFWoo\\NIF\\NIF_Format' ) && ! \VFWoo\NIF\NIF_Format::is_valid( $nif ) ) {
			throw new \Exception( esc_html__( 'El formato del DNI/NIE/CIF no es válido.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$name     = self::customer_name( $fields );
		$verified = false;

		// The AEAT census only knows Spanish IDs. It fails open (no 'resultado') when it cannot answer.
		if ( self::is_spanish( $nif ) && '' !== $name && class_exists( 'VFWoo\\NIF\\NIF_Census' ) ) {
			$result = \VFWoo\NIF\NIF_Census::validate( $nif, $name );
			if ( ! empty( $result['resultado'] ) ) {
				if ( empty( $result['valid'] ) ) {
					throw new \Exception( esc_html( ! empty( $result['message'] ) ? $result['message'] : __( 'El NIF y el nombre no coinciden en el censo de la AEAT.', 'vfwoo-webkul-pos-bridge' ) ) );
				}
				$verified = true;
			}
		}

		$this->pending = array(
			'nif'      => $nif,
			'name'     => $name,
			'verified' => $verified,
		);

		return $data;
	}

	/**
	 * WP Armour Extended treats any registration without its web-form honeypot field as spam.
	 * POS customers are created by an authenticated cashier through the API, which never has it,
	 * so its check is skipped for this request only (it stays active on the storefront).
	 */
	private function allow_pos_registration(): void {
		if ( function_exists( 'wpa_woocommerce_register_validation' ) ) {
			remove_filter( 'woocommerce_registration_errors', 'wpa_woocommerce_register_validation', 10 );
		}
	}

	public function save_nif( $data, $user_id ): void {
		unset( $data );
		$user_id = absint( $user_id );
		if ( ! $user_id || null === $this->pending ) {
			return;
		}

		update_user_meta( $user_id, self::USER_NIF_META, $this->pending['nif'] );
		update_user_meta( $user_id, self::USER_CHECK_META, $this->pending['verified'] ? self::hash( $this->pending['nif'], $this->pending['name'] ) : '' );
		$this->pending = null;
	}

	/**
	 * Limit of the simplified invoice (F2) as VFWoo applies it: from that total on, a customer with a
	 * tax ID is required. With simplified invoices disabled, every sale needs one.
	 *
	 * @return array{simplifiedEnabled:bool,limit:float}
	 */
	public static function sale_rules(): array {
		$enabled = true;
		$limit   = 400.0;
		if ( class_exists( 'VFWoo\\NIF\\NIF_Config' ) && class_exists( 'VFWoo\\NIF\\NIF_Rules' ) ) {
			$settings = \VFWoo\NIF\NIF_Config::settings();
			$enabled  = \VFWoo\NIF\NIF_Rules::is_simplified_enabled( $settings );
			$limit    = \VFWoo\NIF\NIF_Rules::simplified_limit( $settings );
		}

		return array(
			'simplifiedEnabled' => $enabled,
			'limit'             => (float) $limit,
		);
	}

	public function add_customer_data( $customer_data, $customer ) {
		if ( ! is_array( $customer_data ) || ! $customer instanceof \WC_Customer ) {
			return $customer_data;
		}
		return $this->with_nif( $customer_data, $customer->get_id() );
	}

	public function add_customer_data_by_id( $customer_data, $customer_id ) {
		return is_array( $customer_data ) ? $this->with_nif( $customer_data, absint( $customer_id ) ) : $customer_data;
	}

	/**
	 * Give the order the customer's tax ID so VFWoo issues a complete invoice (F1).
	 * The default (over-the-counter) customer and customers without an ID stay simplified (F2).
	 *
	 * @param mixed $order      Order just created by Webkul.
	 * @param mixed $order_data Webkul order data.
	 */
	public function copy_nif_to_order( $order, $order_data ) {
		$customer_id = is_array( $order_data ) ? absint( $order_data['customer'] ?? 0 ) : 0;
		if ( ! $order instanceof \WC_Order || ! $customer_id || '1' === (string) get_user_meta( $customer_id, 'deault_customer_pos', true ) ) {
			return $order;
		}

		$nif = trim( (string) get_user_meta( $customer_id, self::USER_NIF_META, true ) );
		if ( '' !== $nif ) {
			$order->update_meta_data( self::ORDER_NIF_META, $nif );
		}

		return $order;
	}

	private function with_nif( array $customer_data, int $customer_id ): array {
		$customer_data['vfwoo_webkul_nif']        = '';
		$customer_data['vfwoo_webkul_nif_status'] = 'missing';
		$customer_data['vfwoo_webkul_is_default'] = '1' === (string) get_user_meta( $customer_id, 'deault_customer_pos', true );
		if ( ! $customer_id ) {
			return $customer_data;
		}

		$nif = trim( (string) get_user_meta( $customer_id, self::USER_NIF_META, true ) );
		if ( '' === $nif ) {
			return $customer_data;
		}

		$customer = new \WC_Customer( $customer_id );
		$name     = trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() );
		if ( '' === $name ) {
			$name = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );
		}

		$check = (string) get_user_meta( $customer_id, self::USER_CHECK_META, true );
		$customer_data['vfwoo_webkul_nif']        = $nif;
		$customer_data['vfwoo_webkul_nif_status'] = ( '' !== $check && hash_equals( $check, self::hash( $nif, $name ) ) ) ? 'verified' : 'unverified';

		return $customer_data;
	}

	private static function normalize( string $value ): string {
		return strtoupper( preg_replace( '/[\s.\-]/', '', trim( $value ) ) );
	}

	private static function is_spanish( string $nif ): bool {
		return (bool) preg_match( '/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z]|[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-Z])$/', $nif );
	}

	/**
	 * Name the invoice will carry: billing first and last name, or the full name field.
	 */
	private static function customer_name( array $fields ): string {
		$name = trim( ( $fields['pos_customer_fname'] ?? '' ) . ' ' . ( $fields['pos_customer_lname'] ?? '' ) );
		return '' !== $name ? $name : trim( $fields['pos_customer_name'] ?? '' );
	}

	private static function hash( string $nif, string $name ): string {
		return hash( 'sha256', $nif . '|' . strtolower( preg_replace( '/\s+/', ' ', trim( $name ) ) ) );
	}
}
