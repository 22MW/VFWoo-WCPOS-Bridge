<?php
/**
 * Uninstall handler.
 *
 * El bridge nunca debe borrar registros fiscales ni snapshots de recibos WCPOS.
 * Future bridge-owned data requires an explicit, documented deletion policy.
 *
 * @package VFWoo_WCPOS_Bridge
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
