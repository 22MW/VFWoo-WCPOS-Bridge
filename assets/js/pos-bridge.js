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

	// ---- "Marcas" screen: sales summary by brand (vanilla DOM, independent of Webkul's React copy) ----
	var BRANDS_PATH = '/marcas';

	function posUrl() {
		return ( window.wkwcpos_variables && window.wkwcpos_variables.POS_URL ) || '/pos';
	}

	function addBrandsMenu( menus ) {
		var element = window.wp && window.wp.element;
		if ( ! element || ! config || ! config.brands || ! Array.isArray( menus ) ) {
			return menus;
		}
		var icon = element.createElement(
			'svg',
			{ viewBox: '0 0 24 24', width: 22, height: 22, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' },
			element.createElement( 'path', { d: 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z' } ),
			element.createElement( 'circle', { cx: 7.5, cy: 7.5, r: 1.2 } )
		);
		menus.push( { to: posUrl() + BRANDS_PATH, classname: 'wkwcpos-menu-list', icon: icon, icon_classname: '', text: config.brands.menu } );
		return menus;
	}

	// Webkul builds its routes from this list and draws each page inside its own layout (menu included).
	function BrandsScreen( props ) {
		var element = window.wp.element;
		return element.createElement( 'div', {
			className: 'vfwoo-brands-page',
			ref: function ( node ) {
				if ( node && ! node.getAttribute( 'data-ready' ) ) {
					node.setAttribute( 'data-ready', '1' );
					buildBrandsPage( node, { props: props } );
				}
			}
		} );
	}

	function addBrandsRoute( pages ) {
		if ( ! Array.isArray( pages ) || ! window.wp || ! window.wp.element || ! window.apif_script ) {
			return pages;
		}
		pages.push( { name: 'Brands', path: '/' + window.apif_script.pos_path + BRANDS_PATH, component: BrandsScreen } );
		return pages;
	}

	function pad( number ) {
		return ( number < 10 ? '0' : '' ) + number;
	}

	function ymd( date ) {
		return date.getFullYear() + '-' + pad( date.getMonth() + 1 ) + '-' + pad( date.getDate() );
	}

	function presetRange( name ) {
		var today = new Date();
		var start = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
		var end = new Date( start );
		if ( name === 'yesterday' ) {
			start.setDate( start.getDate() - 1 );
			end = new Date( start );
		} else if ( name === 'week' ) {
			start.setDate( start.getDate() - ( ( start.getDay() + 6 ) % 7 ) );
		} else if ( name === 'month' ) {
			start.setDate( 1 );
		}
		return { start: ymd( start ), end: ymd( end ) };
	}

	function node( tag, className, text ) {
		var created = document.createElement( tag );
		if ( className ) {
			created.className = className;
		}
		if ( text !== undefined ) {
			created.textContent = text;
		}
		return created;
	}

	function buildBrandsPage( root, component ) {
		var labels = config.brands;
		var money = function ( value, currency ) {
			try {
				return new Intl.NumberFormat( document.documentElement.lang || 'es-ES', { style: 'currency', currency: currency || 'EUR' } ).format( value );
			} catch ( error ) {
				return Number( value ).toFixed( 2 );
			}
		};
		var range = presetRange( 'today' );
		var selected = null; // null = every brand
		var data = null;

		var header = node( 'div', 'vfwoo-brands-header' );
		var back = node( 'button', 'vfwoo-brands-back', '← ' + labels.back );
		back.type = 'button';
		back.addEventListener( 'click', function () {
			if ( component && component.props && typeof component.props.navigate === 'function' ) {
				component.props.navigate( posUrl() + '/' );
			} else {
				window.location.assign( posUrl() + '/' );
			}
		} );
		header.appendChild( back );
		header.appendChild( node( 'h2', '', labels.title ) );

		var toolbar = node( 'div', 'vfwoo-brands-toolbar' );
		[ 'today', 'yesterday', 'week', 'month' ].forEach( function ( name ) {
			var button = node( 'button', 'vfwoo-brands-preset', labels[ name ] );
			button.type = 'button';
			button.addEventListener( 'click', function () {
				range = presetRange( name );
				fromInput.value = range.start;
				toInput.value = range.end;
				load();
			} );
			toolbar.appendChild( button );
		} );
		var fromInput = node( 'input' );
		fromInput.type = 'date';
		fromInput.value = range.start;
		var toInput = node( 'input' );
		toInput.type = 'date';
		toInput.value = range.end;
		var apply = node( 'button', 'vfwoo-brands-apply', labels.apply );
		apply.type = 'button';
		apply.addEventListener( 'click', function () {
			range = { start: fromInput.value, end: toInput.value };
			load();
		} );
		var fromLabel = node( 'label', '', labels.from + ' ' );
		fromLabel.appendChild( fromInput );
		var toLabel = node( 'label', '', labels.to + ' ' );
		toLabel.appendChild( toInput );
		toolbar.appendChild( fromLabel );
		toolbar.appendChild( toLabel );
		toolbar.appendChild( apply );

		var filter = node( 'div', 'vfwoo-brands-filter' );
		var status = node( 'p', 'vfwoo-brands-status' );
		var results = node( 'div', 'vfwoo-brands-results' );
		var note = node( 'p', 'vfwoo-brands-note', labels.note );

		[ header, toolbar, filter, status, results, note ].forEach( function ( part ) {
			root.appendChild( part );
		} );

		function drawFilter() {
			filter.textContent = '';
			if ( ! data || ! data.brands.length ) {
				return;
			}
			var all = node( 'button', 'vfwoo-brands-chip' + ( selected === null ? ' is-active' : '' ), labels.allBrands );
			all.type = 'button';
			all.addEventListener( 'click', function () {
				selected = null;
				draw();
			} );
			filter.appendChild( all );
			data.brands.forEach( function ( brand ) {
				var active = selected !== null && selected.indexOf( brand.name ) !== -1;
				var chip = node( 'button', 'vfwoo-brands-chip' + ( active ? ' is-active' : '' ), brand.name );
				chip.type = 'button';
				chip.addEventListener( 'click', function () {
					var current = selected === null ? [] : selected.slice();
					var position = current.indexOf( brand.name );
					if ( position === -1 ) {
						current.push( brand.name );
					} else {
						current.splice( position, 1 );
					}
					selected = current.length ? current : null;
					draw();
				} );
				filter.appendChild( chip );
			} );
		}

		function cells( row, name, className, currency ) {
			var tr = node( 'tr', className );
			tr.appendChild( node( 'td', 'vfwoo-brands-name', name ) );
			[ row.units, money( row.net, currency ), money( row.gross, currency ), row.orders, money( row.refund, currency ) ].forEach( function ( value ) {
				tr.appendChild( node( 'td', 'vfwoo-brands-number', String( value ) ) );
			} );
			return tr;
		}

		function draw() {
			drawFilter();
			results.textContent = '';
			if ( ! data ) {
				return;
			}
			var brands = data.brands.filter( function ( brand ) {
				return selected === null || selected.indexOf( brand.name ) !== -1;
			} );
			if ( ! brands.length ) {
				status.textContent = labels.empty;
				return;
			}
			status.textContent = '';

			var table = node( 'table', 'vfwoo-brands-table' );
			var head = node( 'tr' );
			[ labels.brand, labels.units, labels.net, labels.gross, labels.orders, labels.refunds ].forEach( function ( title ) {
				head.appendChild( node( 'th', '', title ) );
			} );
			table.appendChild( node( 'thead' ) ).appendChild( head );
			var body = node( 'tbody' );
			var total = { units: 0, net: 0, gross: 0, refund: 0, orders: 0 };

			brands.forEach( function ( brand ) {
				var products = [];
				var line = cells( brand, '▸ ' + brand.name, 'vfwoo-brands-brand', data.currency );
				line.addEventListener( 'click', function () {
					var open = line.classList.toggle( 'is-open' );
					line.firstChild.textContent = ( open ? '▾ ' : '▸ ' ) + brand.name;
					products.forEach( function ( product ) {
						product.hidden = ! open;
					} );
				} );
				body.appendChild( line );
				brand.products.forEach( function ( product ) {
					var child = cells( product, product.name, 'vfwoo-brands-product', data.currency );
					child.hidden = true;
					products.push( child );
					body.appendChild( child );
				} );
				total.units += brand.units;
				total.net += brand.net;
				total.gross += brand.gross;
				total.refund += brand.refund;
				total.orders += brand.orders;
			} );

			body.appendChild( cells( total, labels.total, 'vfwoo-brands-total', data.currency ) );
			table.appendChild( body );
			results.appendChild( table );
		}

		function request( retry ) {
			var session = ( window.localStorage && window.localStorage.getItem( 'WKWCPOS_API_SESSION_ID' ) ) || '';
			var logged = window.apif_script && window.apif_script.logged_in ? window.apif_script.logged_in.user_id : 0;
			return window.fetch( config.brandReportUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json', authkey: session },
				body: JSON.stringify( { logged_in_user_id: logged, start_date: range.start, end_date: range.end } )
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( result && result.success === false && result.status === 401 && result.session_id && retry ) {
						window.localStorage.setItem( 'WKWCPOS_API_SESSION_ID', result.session_id );
						return request( false );
					}
					return result;
				} );
		}

		function load() {
			status.textContent = labels.loading;
			results.textContent = '';
			request( true )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						data = null;
						drawFilter();
						status.textContent = result && result.message ? result.message : labels.error;
						return;
					}
					data = result.data;
					selected = null;
					draw();
				} )
				.catch( function () {
					data = null;
					drawFilter();
					status.textContent = labels.error;
				} );
		}

		load();
	}

	hooks.addFilter( 'wkwcpos_menus_list', 'vfwoo-webkul-pos-bridge', addBrandsMenu );
	hooks.addFilter( 'wkwcpos_pages_list', 'vfwoo-webkul-pos-bridge', addBrandsRoute );

	hooks.addFilter( 'wkwc_add_custom_field_in_form_after_email', 'vfwoo-webkul-pos-bridge', renderNifField );
	hooks.addFilter( 'wkwcpos_modify_order_success_popup', 'vfwoo-webkul-pos-bridge', checkAfterSale );
	hooks.addFilter( 'wkwcpos_modify_homepage_products', 'vfwoo-webkul-pos-bridge', checkCatalogVersion );
	hooks.addFilter( 'wkwcpos_invoice_after_footer_details_block', 'vfwoo-webkul-pos-bridge', renderFiscalBlock );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
