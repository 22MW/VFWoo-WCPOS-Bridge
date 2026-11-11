<?php
/**
 * Store manager role for new POS cashiers.
 *
 * With the setting on, a cashier created from Webkul's POS Users screen also gets the WooCommerce
 * "Store manager" role, so the admin shortcuts in the POS work for that account. Existing users
 * are not changed.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Cashier_Roles {
	public const OPTION = 'vfwoo_webkul_cashier_manager_role';

	public function __construct() {
		add_action( 'wkwc_after_create_pos_user', array( $this, 'add_manager_role' ), 10, 2 );
	}

	public static function enabled(): bool {
		return '1' === (string) get_option( self::OPTION, '0' );
	}

	/**
	 * @param mixed $data    Webkul's submitted user data (unused).
	 * @param mixed $user_id New cashier.
	 */
	public function add_manager_role( $data, $user_id ): void {
		unset( $data );
		if ( ! self::enabled() || ! get_role( 'shop_manager' ) ) {
			return;
		}

		$user = get_userdata( absint( $user_id ) );
		if ( $user && in_array( 'pos_user', (array) $user->roles, true ) && ! in_array( 'shop_manager', (array) $user->roles, true ) ) {
			$user->add_role( 'shop_manager' );
		}
	}
}
