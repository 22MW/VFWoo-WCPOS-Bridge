( function ( hooks ) {
	'use strict';

	if ( ! hooks || ! hooks.addFilter ) {
		return;
	}

	function renderFiscalBlock( html, order ) {
		var fiscal = order && order.vfwoo_webkul_bridge ? order.vfwoo_webkul_bridge.fiscal : null;
		console.info( '[VFWoo Webkul Bridge] invoice filter', {
			orderId: order && ( order.order_id || order.id ) ? ( order.order_id || order.id ) : 0,
			hasBridgeData: !! ( order && order.vfwoo_webkul_bridge ),
			available: !! ( fiscal && fiscal.available ),
			hasNumber: !! ( fiscal && fiscal.invoice_number ),
			hasQr: !! ( fiscal && fiscal.qr_src )
		} );
		if ( ! fiscal || ! fiscal.available ) {
			return html;
		}

		var block = '<div class="vfwoo-webkul-fiscal" style="margin-top:10px;text-align:center;border-top:1px dashed #bbb;padding-top:8px;">';
		if ( fiscal.invoice_number ) {
			block += '<p><strong>Factura ' + escapeHtml( fiscal.invoice_number ) + '</strong>';
			if ( fiscal.invoice_type ) {
				block += ' (' + escapeHtml( fiscal.invoice_type ) + ')';
			}
			block += '</p>';
		}
		if ( fiscal.qr_src ) {
			block += '<img src="' + escapeAttribute( fiscal.qr_src ) + '" alt="QR Veri*Factu" width="120" height="120" />';
		}
		if ( fiscal.verification_url ) {
			block += '<p style="font-size:10px;word-break:break-word;">' + escapeHtml( fiscal.verification_url ) + '</p>';
		}
		if ( fiscal.legal_legend ) {
			block += '<p style="font-size:10px;">' + escapeHtml( fiscal.legal_legend ) + '</p>';
		}
		return html + block + '</div>';
	}

	function escapeHtml( value ) {
		return String( value ).replace( /[&<>'"]/g, function ( character ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ character ];
		} );
	}

	function escapeAttribute( value ) {
		return escapeHtml( value );
	}

	hooks.addFilter( 'wkwcpos_invoice_after_footer_details_block', 'vfwoo-webkul-pos-bridge', renderFiscalBlock );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
