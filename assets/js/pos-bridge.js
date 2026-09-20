( function ( hooks ) {
	'use strict';

	if ( ! hooks || ! hooks.addFilter ) {
		return;
	}

	function renderFiscalBlock( html, order ) {
		var fiscal = order && order.vfwoo_webkul_bridge ? order.vfwoo_webkul_bridge.fiscal : null;
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
		return html + literalSafe( block + '</div>' );
	}

	function escapeHtml( value ) {
		return String( value ).replace( /[&<>'"]/g, function ( character ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ character ];
		} );
	}

	function escapeAttribute( value ) {
		return escapeHtml( value );
	}

	// Webkul evaluates the ticket HTML as a template literal: inserted text must not contain \, ` or ${.
	function literalSafe( value ) {
		return String( value ).replace( /\\/g, '\\\\' ).replace( /`/g, '\\`' ).replace( /\$\{/g, '\\${' );
	}

	function invoiceValues( bridge, customer ) {
		var fiscal = bridge.fiscal || {};
		var store = ( window.vfwooWebkulBridge && window.vfwooWebkulBridge.store ) || {};
		var alt = ( window.vfwooWebkulBridge && window.vfwooWebkulBridge.qrAlt ) || 'QR';
		var text = function ( value ) {
			return literalSafe( escapeHtml( value || '' ) );
		};
		var qr = fiscal.qr_data_uri || fiscal.qr_src;
		var shown = !! ( customer && customer.visible );

		return {
			vfwoo_company_name: text( store.company_name ),
			vfwoo_company_nif: text( store.company_nif ),
			vfwoo_company_address: text( store.company_address ),
			vfwoo_company_phone: text( store.company_phone ),
			vfwoo_company_email: text( store.company_email ),
			vfwoo_company_logo: store.company_logo ? literalSafe( '<img src="' + escapeAttribute( store.company_logo ) + '" alt="" style="max-width:100%;height:auto;" />' ) : '',
			vfwoo_invoice_number: text( fiscal.invoice_number ),
			vfwoo_invoice_type: text( fiscal.invoice_type ),
			vfwoo_invoice_date: text( fiscal.issued_at ),
			vfwoo_legal_legend: text( fiscal.legal_legend ),
			vfwoo_verification_url: text( fiscal.verification_url ),
			vfwoo_qr: qr ? literalSafe( '<img src="' + escapeAttribute( qr ) + '" alt="' + escapeAttribute( alt ) + '" width="120" height="120" />' ) : '',
			vfwoo_customer_name: shown ? text( customer.name ) : '',
			vfwoo_customer_nif: shown ? text( customer.nif ) : '',
			vfwoo_customer_address: shown ? text( customer.address ) : '',
			vfwoo_customer_email: shown ? text( customer.email ) : '',
			vfwoo_customer_phone: shown ? text( customer.phone ) : ''
		};
	}

	var CUSTOMER_TAGS = [ '${customer_fname}', '${customer_lname}', '${customer_phone}' ];

	// Runs before Webkul evaluates the template: hides customer data on F2 and fills the VFWoo variables.
	function fillInvoiceVariables( html, order ) {
		var bridge = order && order.vfwoo_webkul_bridge;
		if ( ! bridge || typeof html !== 'string' ) {
			return html;
		}

		if ( bridge.customer && ! bridge.customer.visible ) {
			CUSTOMER_TAGS.forEach( function ( tag ) {
				html = html.split( tag ).join( '' );
			} );
		}

		if ( html.indexOf( '${vfwoo_' ) === -1 ) {
			return html;
		}

		// The template places the data by hand: drop the automatic block to avoid repeating it.
		html = html.replace( /<div class="vfwoo-webkul-fiscal"[\s\S]*?<\/div>/, '' );
		var values = invoiceValues( bridge, bridge.customer );
		return html.replace( /\$\{(vfwoo_[a-z_]+)\}/g, function ( match, name ) {
			return values[ name ] !== undefined ? values[ name ] : '';
		} );
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
		var apply = node( 'button', 'vfwoo-brands-apply primary', labels.apply );
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
			var all = node( 'button', 'vfwoo-brands-chip' + ( selected === null ? ' is-active primary' : '' ), labels.allBrands );
			all.type = 'button';
			all.addEventListener( 'click', function () {
				selected = null;
				draw();
			} );
			filter.appendChild( all );
			data.brands.forEach( function ( brand ) {
				var active = selected !== null && selected.indexOf( brand.name ) !== -1;
				var chip = node( 'button', 'vfwoo-brands-chip' + ( active ? ' is-active primary' : '' ), brand.name );
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

	// ---- Sale checks: a simplified invoice (F2) is not valid from the limit on, so a customer with a tax ID is needed ----
	function formatMoney( value ) {
		return Number( value ).toFixed( 2 ) + ' ' + ( ( config.sale && config.sale.currency ) || '' );
	}

	// Reads the live cart and customer from Webkul's store. Returns null, a warning or a blocking problem.
	function saleProblem() {
		var state = window.posStore && window.posStore.getState ? window.posStore.getState() : null;
		if ( ! config || ! config.sale || ! state || ! state.cart || ! state.cart.total ) {
			return null;
		}

		var total = parseFloat( state.cart.total.cart_total );
		var list = state.customers && state.customers.default;
		var current = list && list.length ? list[ 0 ] : null;
		if ( isNaN( total ) || ! current ) {
			return null;
		}

		var sale = config.sale;
		var nif = current.vfwoo_webkul_nif;
		var isDefault = current.vfwoo_webkul_is_default === true;
		var hasNif = ! isDefault && typeof nif === 'string' && nif !== '';
		var unknown = ! isDefault && nif === undefined; // customer cached before the bridge sent the tax ID
		var required = ! sale.simplifiedEnabled || total >= sale.limit;

		if ( required && ! hasNif && ! unknown ) {
			return {
				block: true,
				message: ( sale.simplifiedEnabled ? sale.blockedLimit : sale.blockedAlways )
					.replace( '%total%', formatMoney( total ) )
					.replace( '%limit%', formatMoney( sale.limit ) )
			};
		}
		if ( hasNif && current.vfwoo_webkul_nif_status === 'unverified' ) {
			return { block: false, message: sale.unverified };
		}
		return null;
	}

	function notifySale( problem ) {
		var toast = window.posToast;
		if ( ! toast ) {
			window.alert( problem.message );
		} else if ( problem.block ) {
			toast.error( problem.message, { id: 'vfwoo-sale-problem', duration: 8000 } );
		} else {
			toast( problem.message, { id: 'vfwoo-sale-warning', duration: 6000 } );
		}
	}

	var stopMessage = '';

	// Cart "Pay" button: do not go to the payment screen.
	hooks.addFilter( 'wkwcpos_allow_pay_btn_add_product_in_cart', 'vfwoo-webkul-pos-bridge', function ( allow ) {
		var problem = allow ? saleProblem() : null;
		if ( problem ) {
			notifySale( problem );
			return ! problem.block;
		}
		return allow;
	} );

	// Payment screen: do not create the order. Also covers reaching /pay directly.
	hooks.addFilter( 'wkwcpos_stop_execution_payment_order', 'vfwoo-webkul-pos-bridge', function ( stop ) {
		var problem = stop ? null : saleProblem();
		if ( problem && problem.block ) {
			stopMessage = problem.message;
			return true;
		}
		return stop;
	} );
	hooks.addAction( 'wkwcpos_stop_order_execution_text', 'vfwoo-webkul-pos-bridge', function () {
		notifySale( { block: true, message: stopMessage } );
	} );

	// ---- F3: complete invoice that replaces a simplified one, from the order detail ----
	function posPost( url, payload, retry ) {
		var session = ( window.localStorage && window.localStorage.getItem( 'WKWCPOS_API_SESSION_ID' ) ) || '';
		payload.logged_in_user_id = window.apif_script && window.apif_script.logged_in ? window.apif_script.logged_in.user_id : 0;
		return window.fetch( url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', Accept: 'application/json', authkey: session },
			body: JSON.stringify( payload )
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result && result.success === false && result.status === 401 && result.session_id && retry !== false ) {
					window.localStorage.setItem( 'WKWCPOS_API_SESSION_ID', result.session_id );
					return posPost( url, payload, false );
				}
				return result;
			} );
	}

	function openF3Dialog( order ) {
		var labels = config.f3;
		var orderId = order.order_id || order.id;
		var overlay = node( 'div', 'vfwoo-f3-overlay' );
		var box = node( 'div', 'vfwoo-f3-box' );
		var body = node( 'div', 'vfwoo-f3-body' );
		var head = node( 'div', 'vfwoo-f3-head' );
		var closeButton = node( 'button', 'vfwoo-f3-x', '\u00d7' );
		closeButton.type = 'button';
		closeButton.setAttribute( 'aria-label', labels.close );
		closeButton.addEventListener( 'click', close );
		head.appendChild( node( 'h3', '', labels.title + ' #' + orderId ) );
		head.appendChild( closeButton );
		box.appendChild( head );
		box.appendChild( body );
		overlay.appendChild( box );
		document.body.appendChild( overlay );

		function close() {
			overlay.remove();
		}

		function button( text, className, handler ) {
			var created = node( 'button', className || '', text );
			created.type = 'button';
			created.addEventListener( 'click', handler );
			return created;
		}

		function message( text, isError ) {
			return node( 'p', 'vfwoo-f3-message' + ( isError ? ' is-error' : '' ), text );
		}

		function show() {
			body.textContent = '';
			for ( var index = 0; index < arguments.length; index++ ) {
				body.appendChild( arguments[ index ] );
			}
		}

		function fail( text ) {
			show( message( text || labels.error, true ), button( labels.close, '', close ) );
		}

		function checkOrder() {
			show( message( labels.checking ) );
			posPost( config.f3.statusUrl, { order_id: orderId } )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						return fail( result && result.message );
					}
					if ( result.state === 'ready' ) {
						return showSearch();
					}
					if ( result.state === 'pending' ) {
						return show( message( labels.pending ), button( labels.refresh, 'primary vfwoo-f3-primary', checkOrder ), button( labels.close, '', close ) );
					}
					return show( message( result.state === 'done' ? labels.done : labels.none ), button( labels.close, '', close ) );
				} )
				.catch( function () {
					fail();
				} );
		}

		function showSearch() {
			var input = node( 'input', 'vfwoo-f3-input' );
			input.type = 'search';
			input.placeholder = labels.searchHint;
			var results = node( 'div', 'vfwoo-f3-results' );

			function search() {
				var term = input.value.trim();
				if ( term.length < 2 ) {
					return;
				}
				results.textContent = labels.checking;
				var endpoint = window.wkwcpos_variables && window.wkwcpos_variables.WK_GET_CUSTOMERS_SEARCH_ENDPOINT;
				posPost( endpoint, { search: term } )
					.then( function ( customers ) {
						results.textContent = '';
						if ( ! Array.isArray( customers ) || ! customers.length ) {
							results.appendChild( message( labels.noResults ) );
							return;
						}
						customers.forEach( function ( customer ) {
							results.appendChild( customerRow( customer ) );
						} );
					} )
					.catch( function () {
						results.textContent = '';
						results.appendChild( message( labels.error, true ) );
					} );
			}

			function customerRow( customer ) {
				var row = node( 'div', 'vfwoo-f3-row' );
				var name = ( ( customer.first_name || '' ) + ' ' + ( customer.last_name && customer.last_name !== 'null' ? customer.last_name : '' ) ).trim();
				var info = node( 'div', 'vfwoo-f3-info' );
				info.appendChild( node( 'strong', '', name || customer.email ) );
				info.appendChild( node( 'span', '', customer.email || '' ) );
				var nif = customer.vfwoo_webkul_nif || '';
				var usable = !! nif && customer.vfwoo_webkul_is_default !== true;
				info.appendChild( node( 'span', '', customer.vfwoo_webkul_is_default === true ? labels.defaultCst : ( nif ? nif : labels.noNif ) ) );
				row.appendChild( info );
				var choose = button( labels.choose, 'primary vfwoo-f3-primary', function () {
					showConfirm( customer, name, nif );
				} );
				choose.disabled = ! usable;
				row.appendChild( choose );
				return row;
			}

			var searchButton = button( labels.search, 'primary vfwoo-f3-primary', search );
			input.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Enter' ) {
					search();
				}
			} );
			var bar = node( 'div', 'vfwoo-f3-bar' );
			bar.appendChild( input );
			bar.appendChild( searchButton );
			var actions = node( 'div', 'vfwoo-f3-actions' );
			actions.appendChild( button( labels.create, '', showCreate ) );
			actions.appendChild( button( labels.cancel, '', close ) );
			show( bar, results, actions );
			input.focus();
		}

		function showCreate() {
			var fields = {};
			var form = node( 'div', 'vfwoo-f3-form' );
			[ [ 'firstName', 'text' ], [ 'lastName', 'text' ], [ 'phone', 'tel' ], [ 'email', 'email' ] ].forEach( function ( definition ) {
				var label = node( 'label', '', labels[ definition[ 0 ] ] );
				var input = node( 'input', 'vfwoo-f3-input' );
				input.type = definition[ 1 ];
				label.appendChild( input );
				form.appendChild( label );
				fields[ definition[ 0 ] ] = input;
			} );
			var nifLabel = node( 'label', '', config.nifLabel );
			var nifInput = node( 'input', 'vfwoo-f3-input' );
			nifInput.type = 'text';
			nifInput.autocomplete = 'off';
			nifLabel.appendChild( nifInput );
			form.appendChild( nifLabel );
			var status = message( '' );

			var save = button( labels.save, 'primary vfwoo-f3-primary', function () {
				var first = fields.firstName.value.trim();
				var last = fields.lastName.value.trim();
				var email = fields.email.value.trim();
				var phone = fields.phone.value.trim();
				var list = [
					[ 'pos_customer_id', '' ],
					[ 'pos_customer_name', ( first + ' ' + last ).trim() ],
					[ 'pos_customer_fname', first ],
					[ 'pos_customer_lname', last ],
					[ 'pos_customer_phone', phone ],
					[ 'pos_customer_email', email ],
					[ 'pos_customer_bemail', email ],
					[ 'pos_customer_nif', nifInput.value.trim() ]
				].map( function ( pair ) {
					return { name: pair[ 0 ], value: pair[ 1 ] };
				} );
				save.disabled = true;
				status.className = 'vfwoo-f3-message';
				status.textContent = labels.checking;
				posPost( window.wkwcpos_variables.WK_CREATE_CUSTOMER_ENDPOINT, { pos: list } )
					.then( function ( result ) {
						save.disabled = false;
						if ( result && result.success && result.data ) {
							var created = result.data;
							var name = ( ( created.first_name || '' ) + ' ' + ( created.last_name || '' ) ).trim();
							return showConfirm( created, name, created.vfwoo_webkul_nif || '' );
						}
						status.className = 'vfwoo-f3-message is-error';
						status.textContent = result && result.msg ? result.msg : labels.error;
					} )
					.catch( function () {
						save.disabled = false;
						status.className = 'vfwoo-f3-message is-error';
						status.textContent = labels.error;
					} );
			} );
			var actions = node( 'div', 'vfwoo-f3-actions' );
			actions.appendChild( save );
			actions.appendChild( button( labels.back, '', showSearch ) );
			show( form, status, actions );
		}

		function showConfirm( customer, name, nif ) {
			var text = labels.confirm.replace( '%order%', orderId ).replace( '%name%', name || customer.email ).replace( '%nif%', nif );
			var status = message( '' );
			var issue = button( labels.issue, 'primary vfwoo-f3-primary', function () {
				issue.disabled = true;
				status.className = 'vfwoo-f3-message';
				status.textContent = labels.issuing;
				posPost( config.f3.issueUrl, { order_id: orderId, customer_id: customer.id } )
					.then( function ( result ) {
						if ( result && result.success ) {
							// Webkul prints from this same order object: give it the F3 data.
							if ( result.bridge ) {
								order.vfwoo_webkul_bridge = result.bridge;
							} else if ( order.vfwoo_webkul_bridge && order.vfwoo_webkul_bridge.fiscal ) {
								order.vfwoo_webkul_bridge.fiscal.invoice_type = 'F3';
							}
							// Hide, never remove: the button belongs to Webkul's React tree and removing it by hand
							// breaks the next re-render ("Algo salió mal"). The next render drops it by itself.
							Array.prototype.forEach.call( document.querySelectorAll( '.vfwoo-f3' ), function ( element ) {
								element.style.display = 'none';
							} );
							return show( message( labels.issued ), button( labels.close, 'primary vfwoo-f3-primary', close ) );
						}
						issue.disabled = false;
						status.className = 'vfwoo-f3-message is-error';
						status.textContent = result && result.message ? result.message : labels.error;
					} )
					.catch( function () {
						issue.disabled = false;
						status.className = 'vfwoo-f3-message is-error';
						status.textContent = labels.error;
					} );
			} );
			var actions = node( 'div', 'vfwoo-f3-actions' );
			actions.appendChild( issue );
			actions.appendChild( button( labels.back, '', showSearch ) );
			var lines = [ message( text ) ];
			if ( customer.vfwoo_webkul_nif_status === 'unverified' ) {
				lines.push( message( labels.unverified, true ) );
			}
			show.apply( null, lines.concat( [ status, actions ] ) );
		}

		checkOrder();
	}

	// Button next to "Print invoice" in the POS order detail; only for orders sold as F2.
	function renderF3Button( output, order ) {
		var element = window.wp && window.wp.element;
		var fiscal = order && order.vfwoo_webkul_bridge && order.vfwoo_webkul_bridge.fiscal;
		if ( ! element || ! config || ! config.f3 || ! fiscal || fiscal.invoice_type !== 'F2' ) {
			return output;
		}
		return element.createElement(
			element.Fragment,
			null,
			output,
			// Same wrapper and classes as Webkul's own "Print invoice" button, so it looks the same in every theme.
			element.createElement(
				'div',
				{ className: 'pos-order-invoice vfwoo-f3' },
				element.createElement(
					'button',
					{
						type: 'button',
						className: 'primary',
						onClick: function () {
							openF3Dialog( order );
						}
					},
					element.createElement(
						'svg',
						{ viewBox: '0 0 24 24', width: 22, height: 22, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' },
						element.createElement( 'path', { d: 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z' } ),
						element.createElement( 'path', { d: 'M14 3v5h5M9 13h6M9 17h6' } )
					),
					' ',
					config.f3.button
				)
			)
		);
	}

	hooks.addFilter( 'wkwcpos_add_after_print_invoice_button', 'vfwoo-webkul-pos-bridge', renderF3Button );
	// ---- "Marcas" as one more category in the home category bar ----
	// The POS keeps its categories in a local table it only refreshes when it is empty, so the node is
	// added in memory each time the list is loaded, from the brands that already travel with the products.
	// Brand term ids never clash with category ids (term ids are unique across taxonomies).
	var BRANDS_ROOT_ID = 2000000000;
	var lastCategories = null;
	var lastProducts = null;
	var lastSignature = '';

	function collectBrands( products ) {
		var found = {};
		products.forEach( function ( product ) {
			( product && Array.isArray( product.vfwoo_webkul_brands ) ? product.vfwoo_webkul_brands : [] ).forEach( function ( brand ) {
				if ( brand && brand.id && ! found[ brand.id ] ) {
					found[ brand.id ] = brand;
				}
			} );
		} );
		return Object.keys( found )
			.map( function ( id ) {
				return found[ id ];
			} )
			.sort( function ( a, b ) {
				return String( a.name ).localeCompare( String( b.name ) );
			} );
	}

	function syncBrandCategories() {
		var store = window.posStore;
		var state = store && store.getState ? store.getState() : null;
		var categories = state && state.categories ? state.categories.list : null;
		var products = state && state.products ? state.products.list : null;
		if ( ! config || ! config.brandsCategory || ! Array.isArray( categories ) || ! Array.isArray( products ) || ! products.length ) {
			return;
		}
		if ( categories === lastCategories && products === lastProducts ) {
			return;
		}
		lastCategories = categories;
		lastProducts = products;

		var plain = categories.filter( function ( category ) {
			return category.cat_id !== BRANDS_ROOT_ID;
		} );
		var hasNode = plain.length !== categories.length;
		var brands = collectBrands( products );
		var signature = brands.map( function ( brand ) {
			return brand.id + ':' + brand.name;
		} ).join( '|' );

		if ( hasNode && signature === lastSignature ) {
			return;
		}
		if ( ! brands.length && ! hasNode ) {
			return;
		}
		lastSignature = signature;

		var list = plain;
		if ( brands.length ) {
			list = [ {
				name: config.brandsCategory,
				cat_id: BRANDS_ROOT_ID,
				thumbnail: false,
				child: brands.map( function ( brand ) {
					return { name: brand.name, cat_id: Number( brand.id ), thumbnail: brand.thumbnail || false, child: [] };
				} )
			} ].concat( plain );
		}
		store.dispatch( { type: 'POS_CATEGORIES', categories: { list: list, isFetching: state.categories.isFetching } } );
	}

	// Products of a brand category (or of the "Marcas" root: every product that has a brand).
	function filterBrandProducts( result, categoryId ) {
		var id = parseInt( categoryId, 10 );
		if ( ! id || ! result || ! Array.isArray( result.list ) ) {
			return result;
		}
		var brandIds = collectBrands( result.list ).map( function ( brand ) {
			return Number( brand.id );
		} );
		var isRoot = id === BRANDS_ROOT_ID;
		if ( ! isRoot && brandIds.indexOf( id ) === -1 ) {
			return result;
		}
		var matching = result.list.filter( function ( product ) {
			return Array.isArray( product.vfwoo_webkul_brands ) && product.vfwoo_webkul_brands.some( function ( brand ) {
				return isRoot || Number( brand.id ) === id;
			} );
		} );
		matching.sort( function ( first ) {
			return first.pin_product === 'pin' ? -1 : 1;
		} );
		return Object.assign( {}, result, { cproducts: matching } );
	}

	function startBrandCategories( attempt ) {
		if ( window.posStore && window.posStore.subscribe ) {
			window.posStore.subscribe( syncBrandCategories );
			syncBrandCategories();
		} else if ( attempt < 20 ) {
			window.setTimeout( function () {
				startBrandCategories( attempt + 1 );
			}, 500 );
		}
	}

	if ( config ) {
		startBrandCategories( 0 );
	}
	hooks.addFilter( 'wkwcpos_modify_load_category_products', 'vfwoo-webkul-pos-bridge', filterBrandProducts );

	hooks.addFilter( 'wkwc_add_custom_field_in_form_after_email', 'vfwoo-webkul-pos-bridge', renderNifField );
	hooks.addFilter( 'wkwcpos_modify_order_success_popup', 'vfwoo-webkul-pos-bridge', checkAfterSale );
	hooks.addFilter( 'wkwcpos_modify_homepage_products', 'vfwoo-webkul-pos-bridge', checkCatalogVersion );
	hooks.addFilter( 'wkwcpos_summary_modify_invoice_data', 'vfwoo-webkul-pos-bridge', fillInvoiceVariables );
	hooks.addFilter( 'wkwcpos_invoice_after_footer_details_block', 'vfwoo-webkul-pos-bridge', renderFiscalBlock );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
