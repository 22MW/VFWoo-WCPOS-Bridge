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
			hasQr: !! ( fiscal && ( fiscal.qr_data_uri || fiscal.qr_src ) )
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
		var qrSource = fiscal.qr_data_uri || fiscal.qr_src;
		if ( qrSource ) {
			block += '<img src="' + escapeAttribute( qrSource ) + '" alt="QR Veri*Factu" width="120" height="120" />';
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

	var config = window.vfwooWebkulBridge;
	var serverVersion = config ? Number( config.catalogVersion ) || 0 : 0;
	var cachedVersion = null;
	var dismissedFor = 0;
	var notice = null;
	var lastCheck = 0;

	function removeNotice() {
		if ( notice ) {
			notice.remove();
			notice = null;
		}
	}

	function showNotice() {
		if ( notice || dismissedFor === serverVersion ) {
			return;
		}
		notice = document.createElement( 'div' );
		notice.className = 'vfwoo-webkul-notice';
		notice.textContent = config.staleNotice;
		var close = document.createElement( 'button' );
		close.type = 'button';
		close.setAttribute( 'aria-label', config.close );
		close.textContent = '\u00d7';
		close.addEventListener( 'click', function () {
			dismissedFor = serverVersion;
			removeNotice();
		} );
		notice.appendChild( close );
		document.body.appendChild( notice );
	}

	function evaluate() {
		if ( cachedVersion === null ) {
			return;
		}
		if ( cachedVersion < serverVersion ) {
			showNotice();
		} else {
			removeNotice();
		}
	}

	// Ask the server for the current catalog version; fails silently offline.
	function checkServerVersion() {
		var now = Date.now();
		if ( ! config || ! config.versionUrl || now - lastCheck < 60000 || ! window.fetch ) {
			return;
		}
		lastCheck = now;
		window.fetch( config.versionUrl, { cache: 'no-store', credentials: 'omit' } )
			.then( function ( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function ( data ) {
				var version = data ? Number( data.version ) || 0 : 0;
				if ( version > serverVersion ) {
					serverVersion = version;
					evaluate();
				}
			} )
			.catch( function () {} );
	}

	function checkCatalogVersion( products ) {
		if ( config && Array.isArray( products ) && products.length ) {
			cachedVersion = Number( products[ 0 ] && products[ 0 ].vfwoo_webkul_catalog_version ) || 0;
			evaluate();
		}
		return products;
	}

	function checkAfterSale( popup ) {
		checkServerVersion();
		return popup;
	}

	if ( config ) {
		[ 'pushState', 'replaceState' ].forEach( function ( method ) {
			var original = window.history[ method ];
			window.history[ method ] = function () {
				var result = original.apply( this, arguments );
				setTimeout( checkServerVersion, 0 );
				return result;
			};
		} );
		window.addEventListener( 'popstate', checkServerVersion );
		window.addEventListener( 'hashchange', checkServerVersion );
		window.setInterval( checkServerVersion, 300000 );
		window.setTimeout( checkServerVersion, 2000 );
	}

	// Required tax ID field in the POS customer form; Webkul submits every input of the form.
	function renderNifField( field, customer ) {
		var element = window.wp && window.wp.element;
		if ( ! element || ! config ) {
			return field;
		}
		var nif = customer && customer.vfwoo_webkul_nif ? customer.vfwoo_webkul_nif : '';
		var unverified = nif && customer.vfwoo_webkul_nif_status === 'unverified';
		return element.createElement(
			'div',
			{ className: 'customer-nif' },
			element.createElement( 'label', { htmlFor: 'pos_customer_nif' }, config.nifLabel, element.createElement( 'i', null, '*' ) ),
			element.createElement( 'input', { type: 'text', name: 'pos_customer_nif', id: 'pos_customer_nif', defaultValue: nif, autoComplete: 'off' } ),
			unverified ? element.createElement( 'span', { className: 'error' }, config.nifUnverified ) : null
		);
	}

	hooks.addFilter( 'wkwc_add_custom_field_in_form_after_email', 'vfwoo-webkul-pos-bridge', renderNifField );
	hooks.addFilter( 'wkwcpos_modify_order_success_popup', 'vfwoo-webkul-pos-bridge', checkAfterSale );
	hooks.addFilter( 'wkwcpos_modify_homepage_products', 'vfwoo-webkul-pos-bridge', checkCatalogVersion );
	hooks.addFilter( 'wkwcpos_invoice_after_footer_details_block', 'vfwoo-webkul-pos-bridge', renderFiscalBlock );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
