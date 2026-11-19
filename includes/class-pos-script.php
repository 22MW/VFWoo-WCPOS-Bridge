<?php
/**
 * POS frontend extension.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class POS_Script {
	public function __construct() {
		add_action( 'wkwcpos_enqueue_pos_scripts', array( $this, 'enqueue' ) );
		add_filter( 'wkwcpos_add_custom_css', array( $this, 'allow_style' ) );
	}

	/**
	 * Webkul deregisters every POS style that is not whitelisted.
	 *
	 * @param mixed $styles Whitelisted style handles.
	 */
	public function allow_style( $styles ): array {
		$styles   = is_array( $styles ) ? $styles : array();
		$styles[] = 'vfwoo-webkul-pos-bridge';
		$styles[] = 'dashicons';
		return $styles;
	}

	public function enqueue(): void {
		// Shared pieces (window.vfwooBridge): pos-bridge.js and every module script load after it.
		wp_enqueue_script(
			Module_Registry::CORE_HANDLE,
			VFWOO_WEBKUL_URL . 'assets/js/core.js',
			array( 'wp-hooks', 'wp-element', 'wk-wc-pos-script' ),
			VFWOO_WEBKUL_VERSION,
			false
		);
		wp_enqueue_script(
			'vfwoo-webkul-pos-bridge',
			VFWOO_WEBKUL_URL . 'assets/js/pos-bridge.js',
			array( Module_Registry::CORE_HANDLE ),
			VFWOO_WEBKUL_VERSION,
			false
		);
		$config = array(
			'catalogVersion' => Catalog_Integration::catalog_version(),
			'versionUrl'     => esc_url_raw( rest_url( 'vfwoo-webkul/v1/catalog-version' ) ),
			'staleNotice'    => __( 'Hay cambios en los productos. Pulsa Resync → Products para actualizar el catálogo.', 'vfwoo-webkul-pos-bridge' ),
			'shortcuts'      => Admin_Shortcuts::items(),
			'shortcutLabels' => array(
				'close'   => __( 'Cerrar', 'vfwoo-webkul-pos-bridge' ),
				'openTab' => __( 'Abrir en pestaña nueva', 'vfwoo-webkul-pos-bridge' ),
				'loading' => __( 'Cargando…', 'vfwoo-webkul-pos-bridge' ),
			),
			'close'          => __( 'Cerrar', 'vfwoo-webkul-pos-bridge' ),
			'brandReportUrl' => esc_url_raw( rest_url( 'vfwoo-webkul/v1/brand-report' ) ),
			'brands'         => array(
				'menu'      => __( 'Marcas', 'vfwoo-webkul-pos-bridge' ),
				'title'     => __( 'Ventas por marca', 'vfwoo-webkul-pos-bridge' ),
				'back'      => __( 'Volver', 'vfwoo-webkul-pos-bridge' ),
				'today'     => __( 'Hoy', 'vfwoo-webkul-pos-bridge' ),
				'yesterday' => __( 'Ayer', 'vfwoo-webkul-pos-bridge' ),
				'week'      => __( 'Esta semana', 'vfwoo-webkul-pos-bridge' ),
				'month'     => __( 'Este mes', 'vfwoo-webkul-pos-bridge' ),
				'from'      => __( 'Desde', 'vfwoo-webkul-pos-bridge' ),
				'to'        => __( 'Hasta', 'vfwoo-webkul-pos-bridge' ),
				'apply'     => __( 'Ver', 'vfwoo-webkul-pos-bridge' ),
				'allBrands' => __( 'Todas las marcas', 'vfwoo-webkul-pos-bridge' ),
				'brand'     => __( 'Marca / producto', 'vfwoo-webkul-pos-bridge' ),
				'units'     => __( 'Unidades', 'vfwoo-webkul-pos-bridge' ),
				'net'       => __( 'Ventas sin IVA', 'vfwoo-webkul-pos-bridge' ),
				'gross'     => __( 'Ventas con IVA', 'vfwoo-webkul-pos-bridge' ),
				'orders'    => __( 'Pedidos', 'vfwoo-webkul-pos-bridge' ),
				'refunds'   => __( 'Devoluciones', 'vfwoo-webkul-pos-bridge' ),
				'total'     => __( 'Total', 'vfwoo-webkul-pos-bridge' ),
				'loading'   => __( 'Cargando…', 'vfwoo-webkul-pos-bridge' ),
				'empty'     => __( 'No hay ventas en este periodo.', 'vfwoo-webkul-pos-bridge' ),
				'error'     => __( 'No se han podido cargar los datos.', 'vfwoo-webkul-pos-bridge' ),
				'note'      => __( 'Solo ventas del POS. La marca es la actual del producto; un producto con varias marcas cuenta en cada una.', 'vfwoo-webkul-pos-bridge' ),
			),
			'sale'           => Customer_Integration::sale_rules() + array(
				'currency'      => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'blockedLimit'  => __( 'La venta (%total%) alcanza el límite del ticket (%limit%). Elige un cliente con NIF.', 'vfwoo-webkul-pos-bridge' ),
				'blockedAlways' => __( 'La tienda emite siempre factura. Elige un cliente con NIF.', 'vfwoo-webkul-pos-bridge' ),
				'unverified'    => __( 'El NIF de este cliente no está verificado en el censo de la AEAT.', 'vfwoo-webkul-pos-bridge' ),
			),
			'fullInvoice'    => array(
				'statusUrl'    => esc_url_raw( rest_url( 'vfwoo-webkul/v1/full-invoice-status' ) ),
				'issueUrl'     => esc_url_raw( rest_url( 'vfwoo-webkul/v1/full-invoice-issue' ) ),
				'nifRequired'  => Customer_Integration::nif_required(),
				'countries'    => function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_countries() : array( 'ES' => 'España' ),
				'countryLabel' => __( 'País', 'vfwoo-webkul-pos-bridge' ),
				'noCheck'      => __( 'No comprobar el documento (por defecto, solo se comprueba en España)', 'vfwoo-webkul-pos-bridge' ),
				'button'       => __( 'Cambiar factura', 'vfwoo-webkul-pos-bridge' ),
				'title'        => __( 'Factura del pedido', 'vfwoo-webkul-pos-bridge' ),
				'checking'     => __( 'Comprobando el pedido…', 'vfwoo-webkul-pos-bridge' ),
				'refresh'      => __( 'Actualizar estado', 'vfwoo-webkul-pos-bridge' ),
				'none'         => __( 'Este pedido no admite hacer factura desde el POS.', 'vfwoo-webkul-pos-bridge' ),
				'searchHint'   => __( 'Busca por nombre, teléfono o email (mínimo 2 letras)', 'vfwoo-webkul-pos-bridge' ),
				'search'       => __( 'Buscar', 'vfwoo-webkul-pos-bridge' ),
				'choose'       => __( 'Elegir', 'vfwoo-webkul-pos-bridge' ),
				'noResults'    => __( 'No hay clientes con ese dato.', 'vfwoo-webkul-pos-bridge' ),
				'noNif'        => __( 'Sin NIF: no se puede usar. Complétalo o crea uno nuevo.', 'vfwoo-webkul-pos-bridge' ),
				'noNifOptional' => __( 'Sin NIF', 'vfwoo-webkul-pos-bridge' ),
				'defaultCst'   => __( 'Cliente por defecto: no se puede usar.', 'vfwoo-webkul-pos-bridge' ),
				'unverified'   => __( 'NIF sin verificar en el censo de la AEAT.', 'vfwoo-webkul-pos-bridge' ),
				'create'       => __( 'Crear cliente nuevo', 'vfwoo-webkul-pos-bridge' ),
				'firstName'    => __( 'Nombre', 'vfwoo-webkul-pos-bridge' ),
				'lastName'     => __( 'Apellidos', 'vfwoo-webkul-pos-bridge' ),
				'company'      => __( 'Empresa / razón social (si la factura es a nombre de una empresa)', 'vfwoo-webkul-pos-bridge' ),
				'address'      => __( 'Domicilio', 'vfwoo-webkul-pos-bridge' ),
				'postcode'     => __( 'Código postal', 'vfwoo-webkul-pos-bridge' ),
				'city'         => __( 'Población', 'vfwoo-webkul-pos-bridge' ),
				'phone'        => __( 'Teléfono', 'vfwoo-webkul-pos-bridge' ),
				'email'        => __( 'Email (obligatorio)', 'vfwoo-webkul-pos-bridge' ),
				'save'         => __( 'Crear y elegir', 'vfwoo-webkul-pos-bridge' ),
				'back'         => __( 'Volver', 'vfwoo-webkul-pos-bridge' ),
				'cancel'       => __( 'Cancelar', 'vfwoo-webkul-pos-bridge' ),
				'close'        => __( 'Cerrar', 'vfwoo-webkul-pos-bridge' ),
				'confirmLocal' => __( 'El ticket del pedido #%order% pasará a ser factura a nombre de %name% %nif%, con el mismo número. Si ya tenía otro cliente, se sustituye.', 'vfwoo-webkul-pos-bridge' ),
				'confirmSent'  => __( 'Se hará la factura del pedido #%order% a nombre de %name% %nif% y se enviará a Verifactu. El ticket se conserva.', 'vfwoo-webkul-pos-bridge' ),
				'issue'        => __( 'Hacer factura', 'vfwoo-webkul-pos-bridge' ),
				'issuing'      => __( 'Emitiendo…', 'vfwoo-webkul-pos-bridge' ),
				'issuedLocal'  => __( 'Factura guardada. Al imprimir el pedido saldrá con los datos del cliente; si no, haz Resync de pedidos.', 'vfwoo-webkul-pos-bridge' ),
				'issuedSent'   => __( 'Factura enviada a Verifactu. Al imprimir el pedido saldrá con los datos del cliente; si no, haz Resync de pedidos.', 'vfwoo-webkul-pos-bridge' ),
				'error'        => __( 'No se ha podido completar la operación.', 'vfwoo-webkul-pos-bridge' ),
			),
			'orderStatus'    => array(
				'allowed'        => Order_Status::ALLOWED,
				'cancel'         => __( 'Anular', 'vfwoo-webkul-pos-bridge' ),
				'refund'         => __( 'Devolver', 'vfwoo-webkul-pos-bridge' ),
				'confirmCancel'  => Capabilities::verifactu()
					? __( 'Se anulará el pedido y su factura, y la anulación se enviará a Verifactu. ¿Continuar?', 'vfwoo-webkul-pos-bridge' )
					: __( 'Se anulará el pedido. ¿Continuar?', 'vfwoo-webkul-pos-bridge' ),
				'confirmRefund'  => __( 'Se devolverá el pedido entero: el importe pasa a 0 y se crea la rectificativa. El dinero lo devuelves tú. ¿Continuar?', 'vfwoo-webkul-pos-bridge' ),
			),
			'brandsCategory' => __( 'Marcas', 'vfwoo-webkul-pos-bridge' ),
			'store'          => Invoice_Variables::store(),
			'qrAlt'          => __( 'QR Veri*Factu', 'vfwoo-webkul-pos-bridge' ),
			'nifLabel'       => __( 'DNI / NIE / CIF', 'vfwoo-webkul-pos-bridge' ),
			'nifRequired'    => Customer_Integration::nif_required(),
			'nifUnverified'  => __( 'NIF sin verificar en el censo de la AEAT.', 'vfwoo-webkul-pos-bridge' ),
			'ownTemplate'    => Own_Template::enabled() ? Own_Template::html() : '',
		);

		// The invoice PDF button needs an invoicing plugin and a user who can open orders in the admin.
		if ( Pdf_Actions::can_print() ) {
			$config['pdf'] = array(
				'url'     => esc_url_raw( admin_url( 'admin-post.php?action=' . Pdf_Actions::ACTION ) ),
				'nonce'   => wp_create_nonce( Pdf_Actions::ACTION ),
				'button'  => __( 'Imprimir factura', 'vfwoo-webkul-pos-bridge' ),
				'loading' => __( 'Preparando factura…', 'vfwoo-webkul-pos-bridge' ),
				'creditNote'        => __( 'Imprimir rectificativa', 'vfwoo-webkul-pos-bridge' ),
				'creditNoteLoading' => __( 'Preparando rectificativa…', 'vfwoo-webkul-pos-bridge' ),
			);
		}

		// Each part of the POS extension that leans on the invoicing plugins is left out when what it needs is missing.
		if ( ! Capabilities::verifactu() ) {
			unset( $config['sale'] );
		}
		if ( ! Capabilities::invoices() ) {
			unset( $config['fullInvoice'], $config['nifLabel'], $config['nifRequired'], $config['nifUnverified'] );
		}

		wp_add_inline_script(
			Module_Registry::CORE_HANDLE,
			'window.vfwooWebkulBridge = ' . wp_json_encode( $config ) . ';',
			'before'
		);
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'vfwoo-webkul-pos-bridge',
			VFWOO_WEBKUL_URL . 'assets/css/pos-bridge.css',
			array(),
			VFWOO_WEBKUL_VERSION
		);
	}
}
