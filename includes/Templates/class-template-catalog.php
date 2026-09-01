<?php
/**
 * Plantillas incluidas en el bridge, expresadas como definiciones nativas WCPOS.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Templates;

defined( 'ABSPATH' ) || exit;

final class Template_Catalog {
	/** @return array<int,array<string,mixed>> */
	public static function all(): array {
		return array(
			self::definition( 'vfwoo-fiscal-58mm', 'Ticket fiscal VFWoo — 58 mm', 'thermal', '58', 'vfwoo-fiscal-58mm.xml' ),
			self::definition( 'vfwoo-fiscal-80mm', 'Ticket fiscal VFWoo — 80 mm', 'thermal', '80', 'vfwoo-fiscal-80mm.xml' ),
			self::definition( 'vfwoo-fiscal-a4', 'Factura fiscal VFWoo — A4', 'logicless', '', 'vfwoo-fiscal-a4.html' ),
		);
	}

	/** @return array<string,mixed> */
	private static function definition( string $key, string $title, string $engine, string $paper_width, string $file ): array {
		return array(
			'key'         => $key,
			'title'       => $title,
			'description' => 'Plantilla nativa de WCPOS alimentada por Veri*Fac*WOO.',
			'engine'      => $engine,
			'paper_width' => $paper_width,
			'file'        => VFWOO_WCPOS_BRIDGE_PATH . 'templates/receipts/' . $file,
			'version'     => 1,
		);
	}
}
