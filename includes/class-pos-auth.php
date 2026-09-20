<?php
/**
 * Authentication of REST calls made by the POS app.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Pos_Auth {
	/**
	 * Whether the request carries a valid Webkul POS session for this user.
	 *
	 * @param mixed $user_id Value of `logged_in_user_id`.
	 */
	public static function is_valid( $user_id ): bool {
		$user_id = absint( $user_id );
		if ( ! $user_id || ! class_exists( 'WKWC_POS\\Api\\Includes\\WKWCPOS_API_Authentication' ) ) {
			return false;
		}

		$authentication = new \WKWC_POS\Api\Includes\WKWCPOS_API_Authentication();
		return 'ok' === $authentication->wkwcpos_authenticate_request( $user_id );
	}

	/**
	 * Response in the shape the POS app expects for a rejected session.
	 */
	public static function unauthorized(): array {
		return array(
			'success'    => false,
			'status'     => 401,
			'session_id' => false,
			'message'    => __( 'Sesión del POS no válida.', 'vfwoo-webkul-pos-bridge' ),
		);
	}
}
