<?php
/**
 * Module registry: finds the bridge modules, decides which are active and loads their code and POS assets.
 *
 * A module is a folder `includes/modules/{id}/` with a `module.php` manifest that returns an array. The registry
 * knows nothing about what a module does: it only checks it is switched on and that what it needs is present.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Module_Registry {
	public const OPTION      = 'vfwoo_webkul_modules';
	public const CORE_HANDLE = 'vfwoo-webkul-pos-bridge-core';

	/**
	 * Manifests found on disk, by module id.
	 *
	 * @var array<string,array<string,mixed>>|null
	 */
	private static ?array $found = null;

	public static function register_hooks(): void {
		// After the invoicing modules (init, 1): a module may ask Capabilities whether a piece exists.
		add_action( 'init', array( __CLASS__, 'boot' ), 2 );
		// After POS_Script (10), which registers the core script every module script depends on.
		add_action( 'wkwcpos_enqueue_pos_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'wkwcpos_add_custom_css', array( __CLASS__, 'allow_styles' ) );
		add_action( 'admin_post_vfwoo_webkul_save_modules', array( __CLASS__, 'save' ) );
	}

	/**
	 * Every module found, active or not.
	 *
	 * Manifest keys: label, description, default (bool, on when the admin never chose), requires (callable returning
	 * bool), requires_label (what it needs, for the admin), bootstrap (PHP file in the module folder), script (file in
	 * assets/js/modules), style (file in assets/css/modules), config (callable returning the data the script reads).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$found ) {
			return self::$found;
		}

		self::$found = array();
		$files       = glob( VFWOO_WEBKUL_PATH . 'includes/modules/*/module.php' );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			$id = basename( dirname( $file ) );
			if ( ! preg_match( '/^[a-z0-9-]+$/', $id ) ) {
				continue;
			}
			$manifest = include $file;
			if ( ! is_array( $manifest ) ) {
				continue;
			}
			self::$found[ $id ] = array_merge(
				array(
					'label'          => $id,
					'description'    => '',
					'default'        => false,
					'requires'       => null,
					'requires_label' => '',
					'bootstrap'      => '',
					'script'         => '',
					'style'          => '',
					'config'         => null,
				),
				$manifest,
				array(
					'id'  => $id,
					'dir' => dirname( $file ),
				)
			);
		}
		ksort( self::$found );

		return self::$found;
	}

	/**
	 * The admin's choices: module id => bool. A module the admin never touched is not in the list.
	 *
	 * @return array<string,bool>
	 */
	public static function choices(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$choices = array();
		foreach ( $stored as $id => $on ) {
			$choices[ sanitize_key( (string) $id ) ] = '1' === (string) $on || true === $on;
		}
		return $choices;
	}

	public static function is_switched_on( string $id ): bool {
		$modules = self::all();
		if ( ! isset( $modules[ $id ] ) ) {
			return false;
		}
		$choices = self::choices();
		return array_key_exists( $id, $choices ) ? $choices[ $id ] : (bool) $modules[ $id ]['default'];
	}

	public static function requirements_met( string $id ): bool {
		$modules = self::all();
		if ( ! isset( $modules[ $id ] ) ) {
			return false;
		}
		$requires = $modules[ $id ]['requires'];
		return is_callable( $requires ) ? (bool) call_user_func( $requires ) : true;
	}

	/**
	 * Switched on and with what it needs present.
	 */
	public static function is_active( string $id ): bool {
		return self::is_switched_on( $id ) && self::requirements_met( $id );
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function active(): array {
		return array_filter(
			self::all(),
			static function ( array $module ): bool {
				return self::is_active( $module['id'] );
			}
		);
	}

	/**
	 * Loads the PHP of every active module.
	 */
	public static function boot(): void {
		foreach ( self::active() as $module ) {
			$file = self::inside( $module['dir'], (string) $module['bootstrap'] );
			if ( $file ) {
				require_once $file;
			}
		}
	}

	/**
	 * Adds the script and style of every active module to the POS.
	 */
	public static function enqueue(): void {
		foreach ( self::active() as $module ) {
			$handle = self::handle( $module['id'] );

			$script = self::inside( VFWOO_WEBKUL_PATH . 'assets/js/modules', (string) $module['script'] );
			if ( $script ) {
				wp_enqueue_script(
					$handle,
					VFWOO_WEBKUL_URL . 'assets/js/modules/' . $module['script'],
					array( self::CORE_HANDLE ),
					VFWOO_WEBKUL_VERSION,
					false
				);
				$config = is_callable( $module['config'] ) ? call_user_func( $module['config'] ) : array();
				wp_add_inline_script(
					$handle,
					'window.vfwooBridgeModules = window.vfwooBridgeModules || {}; window.vfwooBridgeModules[' . wp_json_encode( $module['id'] ) . '] = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ';',
					'before'
				);
			}

			$style = self::inside( VFWOO_WEBKUL_PATH . 'assets/css/modules', (string) $module['style'] );
			if ( $style ) {
				wp_enqueue_style( $handle, VFWOO_WEBKUL_URL . 'assets/css/modules/' . $module['style'], array(), VFWOO_WEBKUL_VERSION );
			}
		}
	}

	/**
	 * Webkul deregisters every POS style that is not whitelisted.
	 *
	 * @param mixed $styles Whitelisted style handles.
	 */
	public static function allow_styles( $styles ): array {
		$styles = is_array( $styles ) ? $styles : array();
		foreach ( self::active() as $module ) {
			$styles[] = self::handle( $module['id'] );
		}
		return $styles;
	}

	public static function handle( string $id ): string {
		return 'vfwoo-webkul-pos-module-' . $id;
	}

	/**
	 * Saves the admin's choices. Only known modules are stored.
	 */
	public static function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'vfwoo_webkul_save_modules' );

		$posted = isset( $_POST['vfwoo_modules'] ) && is_array( $_POST['vfwoo_modules'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['vfwoo_modules'] ) )
			: array();

		$choices = array();
		foreach ( array_keys( self::all() ) as $id ) {
			$choices[ $id ] = in_array( $id, $posted, true ) ? '1' : '0';
		}
		update_option( self::OPTION, $choices, false );

		wp_safe_redirect( add_query_arg( 'vfwoo_modules_saved', '1', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=vfwoo-webkul-pos-bridge' ) ) );
		exit;
	}

	/**
	 * The "Módulos" block of the bridge page. Prints nothing while there are no modules.
	 */
	public static function render_admin_section(): void {
		$modules = self::all();
		if ( empty( $modules ) ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'Módulos', 'vfwoo-webkul-pos-bridge' ); ?></h2>
		<?php if ( isset( $_GET['vfwoo_modules_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Guardado. Recarga la caja para ver los cambios.', 'vfwoo-webkul-pos-bridge' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Cada módulo es una función independiente del bridge. Desactivado, no carga nada en la caja ni en el servidor.', 'vfwoo-webkul-pos-bridge' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="vfwoo_webkul_save_modules" />
			<?php wp_nonce_field( 'vfwoo_webkul_save_modules' ); ?>
			<?php foreach ( $modules as $module ) : ?>
				<?php $met = self::requirements_met( $module['id'] ); ?>
				<p><label>
					<input type="checkbox" name="vfwoo_modules[]" value="<?php echo esc_attr( $module['id'] ); ?>" <?php checked( self::is_switched_on( $module['id'] ) ); ?> <?php disabled( ! $met ); ?> />
					<strong><?php echo esc_html( (string) $module['label'] ); ?></strong>
				</label>
				<?php if ( $module['description'] ) : ?>
					<br /><span class="description"><?php echo esc_html( (string) $module['description'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! $met ) : ?>
					<br /><span class="description">
						<?php
						/* translators: %s: what the module needs. */
						echo esc_html( sprintf( __( 'Desactivado: requiere %s', 'vfwoo-webkul-pos-bridge' ), (string) $module['requires_label'] ) );
						?>
					</span>
				<?php endif; ?>
				</p>
			<?php endforeach; ?>
			<?php submit_button( __( 'Guardar módulos', 'vfwoo-webkul-pos-bridge' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Absolute path of a file that really lives inside a folder, or empty. A manifest cannot point outside its folder.
	 */
	private static function inside( string $dir, string $relative ): string {
		if ( '' === $relative ) {
			return '';
		}
		$base = realpath( $dir );
		$file = realpath( trailingslashit( $dir ) . $relative );
		if ( false === $base || false === $file || 0 !== strpos( $file, $base . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) {
			return '';
		}
		return $file;
	}
}
