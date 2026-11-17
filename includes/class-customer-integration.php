<?php
/**
 * Customer tax ID (NIF/NIE/CIF): checked on save (format always, AEAT census with Verifactu), required
 * only with Verifactu, and copied to POS orders.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Customer_Integration {
	private const USER_NIF_META   = 'billing_nif';
	private const USER_CHECK_META = '_vfwoo_webkul_nif_check';
	public const ORDER_NIF_META   = '_billing_nif';

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

		// The company name comes only from the POS dialog that creates customers for a complete invoice;
		// when the field is absent (any other customer form) the stored company is left as it is.
		$company = array_key_exists( 'pos_customer_company', $fields ) ? sanitize_text_field( $fields['pos_customer_company'] ) : null;

		// Document check: on for a Spanish customer, off for any other country, unless the cashier decides otherwise
		// with the «don't check» box of the POS dialog.
		$country  = strtoupper( trim( (string) ( $fields['pos_customer_country'] ?? '' ) ) );
		$no_check = array_key_exists( 'pos_customer_nocheck', $fields )
			? '1' === $fields['pos_customer_nocheck']
			: ( '' !== $country && 'ES' !== $country );

		$nif = self::normalize( $fields['pos_customer_nif'] ?? '' );
		if ( '' === $nif ) {
			// The tax ID is only required where the simplified invoice rules apply (Verifactu).
			if ( self::nif_required() ) {
				throw new \Exception( esc_html__( 'El DNI/NIE/CIF del cliente es obligatorio.', 'vfwoo-webkul-pos-bridge' ) );
			}
			// Optional and left empty: any ID the customer had before is cleared.
			$this->pending = array(
				'nif'      => '',
				'name'     => self::customer_name( $fields ),
				'company'  => $company,
				'verified' => false,
			);
			return $data;
		}
		if ( ! $no_check && Capabilities::nif_format() && ! self::format_ok( $nif, $country ) ) {
			throw new \Exception( esc_html__( 'El formato del documento no es válido. Si es de otro país, marca «No comprobar el documento».', 'vfwoo-webkul-pos-bridge' ) );
		}

		// The census knows a company by its registered name, so that is the name checked and remembered.
		$name     = '' !== (string) $company ? (string) $company : self::customer_name( $fields );
		$verified = false;

		// The AEAT census only knows Spanish IDs. It fails open (no 'resultado') when it cannot answer.
		if ( ! $no_check && self::is_spanish( $nif ) && '' !== $name && Capabilities::census() ) {
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
			'company'  => $company,
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
		if ( null !== $this->pending['company'] ) {
			update_user_meta( $user_id, 'billing_company', $this->pending['company'] );
		}
		$this->pending = null;
	}

	/**
	 * The tax ID of a POS customer is required only where the simplified invoice exists (Verifactu).
	 */
	public static function nif_required(): bool {
		return Capabilities::verifactu();
	}

	/**
	 * Sale rule of the POS: from the maximum limit of the simplified invoice (authorised sector on) a
	 * customer with a tax ID is required. It is fixed here and does not read the invoicing plugin's
	 * settings; it only applies with Verifactu, which is where the simplified invoice lives.
	 *
	 * @return array{simplifiedEnabled:bool,limit:float}
	 */
	public static function sale_rules(): array {
		return array(
			'simplifiedEnabled' => true,
			'limit'             => Capabilities::SIMPLIFIED_LIMIT,
		);
	}

	/**
	 * Tax ID and invoice name of a registered customer, as they were verified.
	 *
	 * @return array{nif:string,name:string,first:string,last:string,company:string,country:string}
	 */
	public static function identity( int $customer_id ): array {
		$identity = array(
			'nif'     => '',
			'name'    => '',
			'first'   => '',
			'last'    => '',
			'company' => '',
			'country' => '',
		);
		if ( ! $customer_id || ! get_userdata( $customer_id ) ) {
			return $identity;
		}

		$customer = new \WC_Customer( $customer_id );
		$first    = trim( $customer->get_billing_first_name() );
		$last     = trim( $customer->get_billing_last_name() );
		if ( '' === $first . $last ) {
			$first = trim( $customer->get_first_name() );
			$last  = trim( $customer->get_last_name() );
		}

		$identity['nif']     = trim( (string) get_user_meta( $customer_id, self::USER_NIF_META, true ) );
		$identity['first']   = $first;
		$identity['last']    = $last;
		$identity['name']    = trim( $first . ' ' . $last );
		$identity['company'] = trim( $customer->get_billing_company() );
		$identity['country'] = strtoupper( trim( $customer->get_billing_country() ) );
		return $identity;
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
		$customer_data['vfwoo_webkul_company']    = '';
		$customer_data['vfwoo_webkul_nif_status'] = 'missing';
		$customer_data['vfwoo_webkul_is_default'] = '1' === (string) get_user_meta( $customer_id, 'deault_customer_pos', true );
		if ( ! $customer_id ) {
			return $customer_data;
		}

		$customer_data['vfwoo_webkul_company'] = trim( (string) get_user_meta( $customer_id, 'billing_company', true ) );

		$nif = trim( (string) get_user_meta( $customer_id, self::USER_NIF_META, true ) );
		if ( '' === $nif ) {
			return $customer_data;
		}

		$who   = self::identity( $customer_id );
		$name  = '' !== $who['company'] ? $who['company'] : $who['name'];
		$check = (string) get_user_meta( $customer_id, self::USER_CHECK_META, true );
		$customer_data['vfwoo_webkul_nif']        = $nif;
		if ( ! Capabilities::census() || ! self::is_spanish( $nif ) ) {
			// Without the census, or with a foreign document, nothing is checked, so there is nothing to warn about.
			$customer_data['vfwoo_webkul_nif_status'] = 'unchecked';
		} else {
			$customer_data['vfwoo_webkul_nif_status'] = ( '' !== $check && hash_equals( $check, self::hash( $nif, $name ) ) ) ? 'verified' : 'unverified';
		}

		return $customer_data;
	}

	private static function normalize( string $value ): string {
		return strtoupper( preg_replace( '/[\s.\-]/', '', trim( $value ) ) );
	}

	/**
	 * Spanish customer: only a DNI, NIE or CIF with its control letter. Any other country: the generic
	 * tax ID formats (EU VAT numbers with their prefix and so on).
	 */
	private static function format_ok( string $nif, string $country ): bool {
		if ( ( '' === $country || 'ES' === $country ) && method_exists( '\VFWoo\NIF\NIF_Format', 'is_valid_spanish' ) ) {
			return \VFWoo\NIF\NIF_Format::is_valid_spanish( $nif );
		}
		return \VFWoo\NIF\NIF_Format::is_valid( $nif );
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
