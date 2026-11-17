<?php
/**
 * What the invoicing plugins installed next to the POS can do, asked by capability and not by plugin.
 *
 * The bridge is about the POS: it does not need any particular invoicing plugin. Each function turns
 * on when the piece it uses exists, so the same code works with an invoicing core on its own, with
 * the core plus the Verifactu add-on, or with the previous single plugin.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Capabilities {
	/**
	 * Invoices and PDF: numbered invoices of an order and the engine that prints them.
	 * Gives the number on the ticket, the PDF button, the PDF in the email and the shop data.
	 */
	public static function invoices(): bool {
		return class_exists( 'VFWoo\\Helpers\\Invoice_Helper' ) && class_exists( 'VFWOO_PDF_Engine' );
	}

	/**
	 * The Verifactu add-on plugin is there (its classes load whether or not its module is switched on).
	 */
	public static function verifactu_installed(): bool {
		return class_exists( 'VFWoo\\Ticket_Fiscal_Data' );
	}

	/**
	 * Verifactu is in use: QR, legal legend and invoice type of the ticket. It also marks where the rules of the
	 * simplified invoice apply, so the sale limit and the required tax ID depend on it.
	 *
	 * An active add-on plugin is not enough: with the invoicing core, Verifactu is a module that the shop
	 * switches on or off (VFWoo → Módulos). With the module off it does nothing, so the bridge ignores it too.
	 * The single-plugin VFWoo has no module switch: there, being installed is being in use.
	 */
	public static function verifactu(): bool {
		if ( ! self::verifactu_installed() ) {
			return false;
		}
		if ( class_exists( 'VFWoo\\Modules' ) && method_exists( 'VFWoo\\Modules', 'is_active' ) ) {
			return (bool) \VFWoo\Modules::is_active( 'verifactu' );
		}
		return true;
	}

	/**
	 * Verifactu is sending invoices to the AEAT (sandbox or production), not just numbering them locally.
	 */
	public static function sending(): bool {
		return self::verifactu()
			&& class_exists( 'VFWoo\\Helpers\\Mode_Detector' )
			&& method_exists( 'VFWoo\\Helpers\\Mode_Detector', 'is_basic' )
			&& ! \VFWoo\Helpers\Mode_Detector::is_basic();
	}

	/**
	 * AEAT census: checks a Spanish tax ID against its name. Only Verifactu can do it.
	 */
	public static function census(): bool {
		return self::verifactu() && class_exists( 'VFWoo\\NIF\\NIF_Census' ) && method_exists( 'VFWoo\\NIF\\NIF_Census', 'validate' );
	}

	/**
	 * Format check of a tax ID (including the check letter).
	 */
	public static function nif_format(): bool {
		return class_exists( 'VFWoo\\NIF\\NIF_Format' ) && method_exists( 'VFWoo\\NIF\\NIF_Format', 'is_valid' );
	}

	/**
	 * Callable that issues the complete invoice (F3) that replaces a simplified one, or null.
	 * The add-on carries it; the previous single plugin had it in the invoice helper.
	 *
	 * @return callable|null
	 */
	public static function f3_emitter() {
		foreach ( array( 'VFWoo\\Helpers\\Invoice_Helper_Verifactu', 'VFWoo\\Helpers\\Invoice_Helper' ) as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'emitir_sustitutiva' ) ) {
				return array( $class, 'emitir_sustitutiva' );
			}
		}
		return null;
	}

	/**
	 * The F3 (Verifactu's way to register a complete invoice that replaces a simplified one) needs something
	 * that issues it and the Verifactu invoice it replaces.
	 */
	public static function f3(): bool {
		return self::verifactu() && null !== self::f3_emitter();
	}

	/**
	 * Fixed rule of the POS for the simplified invoice: the maximum limit (authorised sector on).
	 * It only exists where the simplified invoice does (Verifactu) and does not read the invoicing
	 * plugin's own settings.
	 */
	public const SIMPLIFIED_LIMIT = 3000.0;

	/**
	 * Status of every capability, in the order the settings screen lists them.
	 *
	 * @return array<int,array{label:string,active:bool,needs:string}>
	 */
	public static function overview(): array {
		return array(
			array(
				'label'  => __( 'Número de factura en el ticket, «Imprimir factura» y correo con PDF', 'vfwoo-webkul-pos-bridge' ),
				'active' => self::invoices(),
				'needs'  => __( 'un plugin de facturas (VFWoo Invoices)', 'vfwoo-webkul-pos-bridge' ),
			),
			array(
				'label'  => __( 'QR, leyenda legal y tipo de factura en el ticket', 'vfwoo-webkul-pos-bridge' ),
				'active' => self::verifactu(),
				'needs'  => __( 'Verifactu', 'vfwoo-webkul-pos-bridge' ),
			),
			array(
				'label'  => __( 'NIF obligatorio y límite de venta sin cliente (3.000 €)', 'vfwoo-webkul-pos-bridge' ),
				'active' => self::verifactu(),
				'needs'  => __( 'Verifactu', 'vfwoo-webkul-pos-bridge' ),
			),
			array(
				'label'  => __( 'Comprobar el nombre del cliente contra el censo de la AEAT', 'vfwoo-webkul-pos-bridge' ),
				'active' => self::census(),
				'needs'  => __( 'Verifactu', 'vfwoo-webkul-pos-bridge' ),
			),
			array(
				'label'  => __( 'Botón «Cambiar factura»: convertir un ticket en factura (con Verifactu se registra como F3)', 'vfwoo-webkul-pos-bridge' ),
				'active' => self::invoices(),
				'needs'  => __( 'un plugin de facturas (VFWoo Invoices)', 'vfwoo-webkul-pos-bridge' ),
			),
		);
	}
}
