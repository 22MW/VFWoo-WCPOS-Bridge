<?php
/**
 * Manifest of the "Productos" module: a light product manager inside the POS.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'       => __( 'Productos', 'vfwoo-webkul-pos-bridge' ),
	'description' => __( 'Pestaña «Productos» en la caja: lista del catálogo con filtros, interruptores «En la tienda» y «En la caja» y edición básica. Sustituye al acceso «Productos» de la cabecera. Solo para cajeros con el modo de gestor de tienda.', 'vfwoo-webkul-pos-bridge' ),
	'default'     => true,
	'bootstrap'   => 'class-products-module.php',
	'script'      => 'products.js',
	'style'       => 'products.css',
	'config'      => static function (): array {
		return \VFWoo_Webkul_POS_Bridge\Products_Module::config();
	},
);
