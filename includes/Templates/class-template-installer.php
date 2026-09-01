<?php
/**
 * Instala las plantillas del bridge como plantillas nativas editables de WCPOS.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Templates;

use WCPOS\WooCommercePOS\Templates;

defined( 'ABSPATH' ) || exit;

final class Template_Installer {
	const META_KEY = '_vfwoo_wcpos_bridge_template_key';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 30 );
		add_action( 'admin_post_vfwoo_wcpos_bridge_install_templates', array( $this, 'handle_install' ) );
	}

	/** Registra una entrada de configuración dentro del menú POS existente. */
	public function register_admin_page(): void {
		add_submenu_page(
			'woocommerce-pos',
			__( 'VFWoo Bridge', 'vfwoo-wcpos-bridge' ),
			__( 'VFWoo Bridge', 'vfwoo-wcpos-bridge' ),
			'manage_woocommerce_pos',
			'vfwoo-wcpos-bridge',
			array( $this, 'render_admin_page' )
		);
	}

	/** Muestra la pantalla mínima de configuración POS de la primera versión. */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_woocommerce_pos' ) ) {
			return;
		}
		$settings = function_exists( 'woocommerce_pos_get_settings' ) ? woocommerce_pos_get_settings( 'vfwoo_bridge' ) : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'VFWoo Bridge', 'vfwoo-wcpos-bridge' ); ?></h1>
			<p><?php esc_html_e( 'Instala plantillas editables que aparecen en POS → Templates. La impresión y la selección siguen siendo responsabilidad de WCPOS.', 'vfwoo-wcpos-bridge' ); ?></p>
			<p><?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Integración fiscal activa.', 'vfwoo-wcpos-bridge' ) : esc_html__( 'La integración fiscal está desactivada. Actívala desde el ajuste WCPOS vfwoo_bridge antes de usar las plantillas en producción.', 'vfwoo-wcpos-bridge' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'vfwoo_wcpos_bridge_install_templates' ); ?>
				<input type="hidden" name="action" value="vfwoo_wcpos_bridge_install_templates" />
				<?php submit_button( __( 'Instalar plantillas VFWoo', 'vfwoo-wcpos-bridge' ), 'primary', 'submit', false ); ?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Diagnóstico de un pedido', 'vfwoo-wcpos-bridge' ); ?></h2>
			<p><?php esc_html_e( 'Esta consulta es de solo lectura. Compara VFWoo con el snapshot que WCPOS utilizará para imprimir.', 'vfwoo-wcpos-bridge' ); ?></p>
			<form method="get" action="">
				<input type="hidden" name="page" value="vfwoo-wcpos-bridge" />
				<label for="vfwoo-bridge-order-id"><?php esc_html_e( 'ID de pedido', 'vfwoo-wcpos-bridge' ); ?></label>
				<input id="vfwoo-bridge-order-id" name="vfwoo_bridge_order_id" type="number" min="1" value="<?php echo isset( $_GET['vfwoo_bridge_order_id'] ) ? esc_attr( absint( $_GET['vfwoo_bridge_order_id'] ) ) : ''; ?>" />
				<?php submit_button( __( 'Consultar diagnóstico', 'vfwoo-wcpos-bridge' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php $this->render_diagnostic(); ?>
		</div>
		<?php
	}

	/** Muestra información de diagnóstico sin modificar pedido, factura ni snapshot. */
	private function render_diagnostic(): void {
		$order_id = isset( $_GET['vfwoo_bridge_order_id'] ) ? absint( $_GET['vfwoo_bridge_order_id'] ) : 0;
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			echo '<p class="notice notice-error inline"><span>' . esc_html__( 'No existe ese pedido.', 'vfwoo-wcpos-bridge' ) . '</span></p>';
			return;
		}

		$snapshot = \WCPOS\WooCommercePOS\Services\Receipt_Snapshot_Store::instance()->get_snapshot( $order_id );
		$vfwoo    = \VFWoo\Ticket_Fiscal_Data::for_order( $order_id, true );
		$bridge   = is_array( $snapshot ) && isset( $snapshot['fiscal']['extra_fields']['vfwoo'] )
			? $snapshot['fiscal']['extra_fields']['vfwoo']
			: null;

		$report = array(
			'pedido' => array(
				'id'     => $order_id,
				'estado' => $order->get_status(),
			),
			'vfwoo' => array(
				'disponible'     => ! empty( $vfwoo['available'] ),
				'numero_factura' => $vfwoo['invoice_number'] ?? '',
				'confirmada'     => ! empty( $vfwoo['confirmed'] ),
				'qr_preparado'   => ! empty( $vfwoo['qr_is_prepared'] ),
			),
			'wcpos' => array(
				'snapshot_existe' => is_array( $snapshot ),
				'bloque_vfwoo'    => is_array( $bridge ),
				'numero_factura'  => is_array( $bridge ) ? ( $bridge['invoice_number'] ?? '' ) : '',
			),
		);

		echo '<h3>' . esc_html__( 'Resultado', 'vfwoo-wcpos-bridge' ) . '</h3>';
		echo '<pre style="max-width:900px;overflow:auto;padding:16px;background:#fff;border:1px solid #ccd0d4;">' . esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre>';
	}

	/** Instala las plantillas que falten. Nunca sobrescribe plantillas existentes. */
	public function handle_install(): void {
		if ( ! current_user_can( 'manage_woocommerce_pos' ) ) {
			wp_die( esc_html__( 'No tienes permiso para instalar las plantillas.', 'vfwoo-wcpos-bridge' ) );
		}
		check_admin_referer( 'vfwoo_wcpos_bridge_install_templates' );

		$result = $this->install_all();
		$url    = add_query_arg(
			array(
				'page'               => 'vfwoo-wcpos-bridge',
				'vfwoo_bridge_result' => is_wp_error( $result ) ? 'error' : 'ok',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/** @return true|\WP_Error */
	public function install_all() {
		foreach ( Template_Catalog::all() as $template ) {
			if ( $this->find_existing( $template['key'] ) ) {
				continue;
			}
			$result = $this->install( $template );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/** @return int|\WP_Error */
	private function install( array $template ) {
		$content = file_get_contents( $template['file'] );
		if ( false === $content ) {
			return new \WP_Error( 'vfwoo_wcpos_template_read_failed', __( 'No se ha podido leer una plantilla del bridge.', 'vfwoo-wcpos-bridge' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $template['title'],
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'wcpos_template',
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		wp_set_object_terms( $post_id, 'receipt', 'wcpos_template_type' );
		update_post_meta( $post_id, '_template_description', $template['description'] );
		update_post_meta( $post_id, '_template_engine', $template['engine'] );
		update_post_meta( $post_id, '_template_output_type', 'html' );
		update_post_meta( $post_id, '_template_language', 'thermal' === $template['engine'] ? 'xml' : 'html' );
		update_post_meta( $post_id, '_template_tax_display', 'default' );
		update_post_meta( $post_id, self::META_KEY, $template['key'] );
		update_post_meta( $post_id, '_vfwoo_wcpos_bridge_template_version', $template['version'] );
		if ( '' !== $template['paper_width'] ) {
			update_post_meta( $post_id, '_template_paper_width', $template['paper_width'] );
		}

		if ( ! Templates::save_raw_post_content( $post_id, $content ) ) {
			wp_delete_post( $post_id, true );
			return new \WP_Error( 'vfwoo_wcpos_template_save_failed', __( 'WCPOS no ha podido guardar el contenido de una plantilla.', 'vfwoo-wcpos-bridge' ) );
		}

		return $post_id;
	}

	/** @return int */
	private function find_existing( string $key ): int {
		$posts = get_posts(
			array(
				'post_type'      => 'wcpos_template',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_KEY,
				'meta_value'     => $key,
			)
		);

		return empty( $posts ) ? 0 : absint( $posts[0] );
	}
}
