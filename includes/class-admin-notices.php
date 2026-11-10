<?php
/**
 * Admin notices that get in the way of the POS invoice template editor.
 *
 * WordPress moves every admin notice into the page, under its first heading, with JavaScript. In
 * Webkul's invoice template editor that heading belongs to the ticket, so a notice ends up inside
 * the template and is saved with it. Notices are therefore not printed on that screen.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Admin_Notices {
	public const OPTION_HIDE_COMPAT = 'vfwoo_webkul_hide_wc_compat_notice';

	private const TEMPLATES_PAGE = 'wc-pos-invoice-templates';

	public function __construct() {
		add_action( 'in_admin_header', array( $this, 'silence_template_editor' ), 0 );
		add_filter( 'woocommerce_plugins_are_incompatible_with_feature_by_default', array( $this, 'compat_by_default' ) );
	}

	public static function hide_compat_notice(): bool {
		return '1' === (string) get_option( self::OPTION_HIDE_COMPAT, '0' );
	}

	/**
	 * Nothing is printed by admin_notices on Webkul's invoice template screen.
	 */
	public function silence_template_editor(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::TEMPLATES_PAGE !== $page ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
	}

	/**
	 * With the switch on, a plugin that does not declare its WooCommerce feature compatibility is
	 * treated as compatible, so WooCommerce stops warning about it. Plugins that declare themselves
	 * incompatible are still reported.
	 *
	 * @param mixed $incompatible Whether undeclared plugins count as incompatible.
	 * @return mixed
	 */
	public function compat_by_default( $incompatible ) {
		return self::hide_compat_notice() ? false : $incompatible;
	}
}
