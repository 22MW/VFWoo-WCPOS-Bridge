<?php
/**
 * Sección de ajustes WCPOS del bridge VFWoo.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Settings;

use WCPOS\WooCommercePOS\Services\Settings\Abstract_Section;

defined( 'ABSPATH' ) || exit;

/**
 * Almacena solo preferencias de presentación. La verdad fiscal permanece en VFWoo.
 */
final class VFWoo_Bridge_Section extends Abstract_Section {
	/** @inheritDoc */
	public function id(): string {
		return 'vfwoo_bridge';
	}

	/** @inheritDoc */
	public function defaults(): array {
		return array(
			'enabled'                => true,
			'qr_size'                => 180,
			'template_58_id'         => 0,
			'template_80_id'         => 0,
			'template_a4_id'         => 0,
			'date_modified_gmt'      => '',
		);
	}

	/** @inheritDoc */
	protected function sanitize( array $settings ): array {
		$defaults = $this->defaults();

		return array(
			'enabled'           => ! empty( $settings['enabled'] ),
			'qr_size'           => min( 320, max( 80, absint( $settings['qr_size'] ?? $defaults['qr_size'] ) ) ),
			'template_58_id'    => absint( $settings['template_58_id'] ?? 0 ),
			'template_80_id'    => absint( $settings['template_80_id'] ?? 0 ),
			'template_a4_id'    => absint( $settings['template_a4_id'] ?? 0 ),
		);
	}
}
