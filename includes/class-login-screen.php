<?php
/**
 * POS login screen: custom heading and footer text, no Webkul version line, demo credentials.
 *
 * Webkul prints the login as a PHP template with no filters for these texts, so the heading goes
 * through its own option and the rest is applied with a small script on the login page only.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Login_Screen {
	public const OPTION = 'vfwoo_webkul_login';

	public function __construct() {
		add_filter( 'option__pos_heading_login', array( $this, 'heading' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 100 );
	}

	/**
	 * Saved settings with their defaults. Everything is off or empty until the admin sets it.
	 *
	 * @return array{heading:string,footer:string,hide_version:bool,demo:bool,user:string,pass:string}
	 */
	public static function settings(): array {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();

		return array(
			'heading'      => isset( $saved['heading'] ) ? (string) $saved['heading'] : '',
			'footer'       => isset( $saved['footer'] ) ? (string) $saved['footer'] : '',
			'hide_version' => ! empty( $saved['hide_version'] ),
			'demo'         => ! empty( $saved['demo'] ),
			'user'         => isset( $saved['user'] ) ? (string) $saved['user'] : '',
			'pass'         => isset( $saved['pass'] ) ? (string) $saved['pass'] : '',
		);
	}

	/**
	 * Heading shown on the login. The admin keeps seeing Webkul's own value in its settings.
	 *
	 * @param mixed $value Webkul's heading.
	 * @return mixed
	 */
	public function heading( $value ) {
		if ( is_admin() ) {
			return $value;
		}
		$heading = self::settings()['heading'];
		return '' !== $heading ? $heading : $value;
	}

	/**
	 * Same test Webkul uses to print its login: POS endpoint with nobody logged in.
	 */
	private function is_login_screen(): bool {
		if ( is_user_logged_in() ) {
			return false;
		}

		global $wp;
		$endpoint = get_option( '_pos_endpoint_name' );
		$endpoint = ! empty( $endpoint ) ? $endpoint : 'pos';
		$vars     = is_object( $wp ) && is_array( $wp->query_vars ) ? $wp->query_vars : array();

		return array_key_exists( $endpoint, $vars ) || ( isset( $vars['pagename'] ) && $endpoint === $vars['pagename'] );
	}

	public function enqueue(): void {
		if ( ! $this->is_login_screen() ) {
			return;
		}

		$settings = self::settings();
		$demo     = $settings['demo'] && '' !== $settings['user'];
		$config   = array(
			'footer'      => $settings['footer'],
			'hideVersion' => $settings['hide_version'],
			'user'        => $demo ? $settings['user'] : '',
			'pass'        => $demo ? $settings['pass'] : '',
		);
		if ( '' === $config['footer'] && ! $config['hideVersion'] && '' === $config['user'] ) {
			return;
		}

		wp_enqueue_style( 'vfwoo-webkul-login', VFWOO_WEBKUL_URL . 'assets/css/login-screen.css', array(), VFWOO_WEBKUL_VERSION );
		wp_enqueue_script( 'vfwoo-webkul-login', VFWOO_WEBKUL_URL . 'assets/js/login-screen.js', array(), VFWOO_WEBKUL_VERSION, false );
		wp_add_inline_script(
			'vfwoo-webkul-login',
			'window.vfwooWebkulLogin = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
			'before'
		);
	}
}
