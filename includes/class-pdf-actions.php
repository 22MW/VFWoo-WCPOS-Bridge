<?php
/**
 * Invoice PDF from the POS: attached to the «Send order email» message and printed from the
 * order detail.
 *
 * Needs an invoicing plugin. It only uses that plugin's own PDF engine and preview screen, and does
 * nothing when something it needs is missing: the email then goes out exactly as Webkul sends it.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Pdf_Actions {
	public const OPTION_EMAIL = 'vfwoo_webkul_email_pdf';
	public const ACTION       = 'vfwoo_webkul_pdf';

	/**
	 * Order whose email the POS button is sending right now (set by Webkul's own filter).
	 */
	private static int $pos_email_order = 0;

	/**
	 * Temporary PDFs to delete when the request ends.
	 *
	 * @var string[]
	 */
	private static array $temp_files = array();

	public function __construct() {
		add_filter( 'wkwcpos_modify_send_mail_id', array( $this, 'mark_pos_email' ), 10, 3 );
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_pdf' ), 120, 4 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'redirect_to_pdf' ) );
		add_action( 'shutdown', array( $this, 'cleanup' ) );
	}

	/**
	 * On unless the shop turned it off.
	 */
	public static function email_enabled(): bool {
		return '0' !== get_option( self::OPTION_EMAIL, '1' );
	}

	/**
	 * Who gets the print button: whoever can open an order in the admin, like VFWoo's own PDF screen.
	 */
	public static function can_print(): bool {
		return Capabilities::invoices() && is_user_logged_in() && current_user_can( 'edit_shop_orders' );
	}

	/**
	 * Webkul runs this filter in its «Send order email» endpoint right before it sends the email.
	 * It is only used to know which order the POS is mailing; the email id is returned untouched.
	 *
	 * @param mixed $email_id Email id Webkul chose.
	 * @param mixed $request  Request.
	 * @param mixed $order    Order.
	 */
	public function mark_pos_email( $email_id, $request = null, $order = null ) {
		self::$pos_email_order = $order instanceof \WC_Order ? $order->get_id() : 0;
		return $email_id;
	}

	/**
	 * Attaches the invoice PDF to the message the POS «Send order email» button sends, every time.
	 * VFWoo's own attachment is not enough here: in the VERI*FACTU channel it does not attach to
	 * WooCommerce emails at all, and in the local one it attaches only once per order, so a resend
	 * from the POS went out without it. Only to the customer and only when the invoice can be printed.
	 *
	 * @param mixed $attachments Attachments.
	 * @param mixed $email_id    Email id.
	 * @param mixed $object      Order the email is about.
	 * @param mixed $email       Email object.
	 */
	public function attach_pdf( $attachments, $email_id = '', $object = null, $email = null ) {
		$attachments = is_array( $attachments ) ? $attachments : array();

		// Not the POS button's message: nothing to do, and nothing to report.
		if ( ! self::$pos_email_order || ! $object instanceof \WC_Order || $object->get_id() !== self::$pos_email_order ) {
			return $attachments;
		}

		$skip = static function ( string $reason ) use ( $object, $attachments ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only with WP_DEBUG: tells why the PDF was not attached.
				error_log( sprintf( '[VFWoo Webkul Bridge] PDF no adjuntado al correo del pedido #%d: %s', $object->get_id(), $reason ) );
			}
			return $attachments;
		};

		if ( ! self::email_enabled() ) {
			return $skip( 'casilla «Correo del pedido» desactivada' );
		}
		if ( ! $email instanceof \WC_Email || ! $email->is_customer_email() ) {
			return $skip( 'no es un correo al cliente' );
		}
		if ( isset( $email->refund ) || in_array( (string) $email_id, array( 'customer_refunded_order', 'customer_partially_refunded_order' ), true ) ) {
			return $skip( 'correo de reembolso' );
		}
		// A cancelled or failed order is not a sale: its email must not carry an invoice.
		if ( in_array( $object->get_status(), array( 'cancelled', 'failed' ), true ) ) {
			return $skip( 'pedido cancelado o fallido' );
		}
		if ( ! Capabilities::invoices() ) {
			return $skip( 'no hay plugin de facturas con motor de PDF' );
		}

		$invoice = \VFWoo\Helpers\Invoice_Helper::get_invoice_by_order_id( $object->get_id() );
		$number  = $invoice ? (string) $invoice->get_numero_factura_completo() : '';
		if ( '' === $number ) {
			return $skip( 'el pedido aún no tiene número de factura' );
		}
		// Older invoicing plugins have no channels: their invoices are always printable.
		$channel = class_exists( 'VFWoo\\Channels\\Channel_Registry' ) ? \VFWoo\Channels\Channel_Registry::current() : null;
		if ( $channel && ! $channel->is_printable( $invoice ) ) {
			return $skip( 'la factura aún no se puede imprimir (estado ' . ( method_exists( $invoice, 'get_estado' ) ? (string) $invoice->get_estado() : '?' ) . ')' );
		}

		// As VFWoo's own mailer does: outside a page, the number the template reads would be empty.
		$number_filter = static function () use ( $number ) {
			return $number;
		};
		add_filter( 'vfwoo_pdf_invoice_number', $number_filter );
		try {
			$path = (string) \VFWOO_PDF_Engine::generate_pdf( $object->get_id(), 'invoice' );
		} catch ( \Throwable $e ) {
			$path = '';
		} finally {
			remove_filter( 'vfwoo_pdf_invoice_number', $number_filter );
		}

		if ( '' === $path || ! file_exists( $path ) ) {
			return $skip( 'VFWoo no ha podido generar el PDF' );
		}

		// VFWoo writes the PDF of an order to the same path, so if it already attached it, it is not repeated.
		if ( ! in_array( $path, $attachments, true ) ) {
			$attachments[] = $path;
		}
		self::$temp_files[] = $path;

		return $attachments;
	}

	/**
	 * Target of the POS «Imprimir factura» and «Imprimir rectificativa» buttons. VFWoo's PDF screen asks for a nonce tied to the browser
	 * session, so the link is built here, in that session, and the browser is sent to it.
	 */
	public function redirect_to_pdf(): void {
		if ( ! self::can_print() ) {
			wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified above.
		if ( ! $order_id || ! wc_get_order( $order_id ) ) {
			wp_die( esc_html__( 'Pedido no encontrado.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 404 ) );
		}

		// The credit note (rectificativa) of a refund: VFWoo identifies it by the refund's id, not the order's.
		$document_id = $order_id;
		$type        = 'invoice';
		$refund_id   = isset( $_GET['refund_id'] ) ? absint( wp_unslash( $_GET['refund_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified above.
		if ( $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( ! $refund instanceof \WC_Order_Refund || (int) $refund->get_parent_id() !== $order_id ) {
				wp_die( esc_html__( 'Reembolso no encontrado.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 404 ) );
			}
			$document_id = $refund_id;
			$type        = 'credit_note';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'action'   => 'vfwoo_pdf_preview',
					'order_id' => $document_id,
					'type'     => $type,
					'format'   => 'pdf',
					'_nonce'   => wp_create_nonce( 'vfwoo_pdf_preview_' . $document_id ),
				),
				admin_url( 'admin-post.php' )
			)
		);
		exit;
	}

	/**
	 * Deletes the PDFs generated for the email.
	 */
	public function cleanup(): void {
		foreach ( self::$temp_files as $file ) {
			if ( is_string( $file ) && file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
		self::$temp_files      = array();
		self::$pos_email_order = 0;
	}
}
