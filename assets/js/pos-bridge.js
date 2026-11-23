( function ( hooks ) {
	'use strict';

	var core = window.vfwooBridge;
	if ( ! hooks || ! hooks.addFilter || ! core ) {
		return;
	}

	// Shared pieces live in core.js.
	var escapeHtml = core.escapeHtml;
	var escapeAttribute = core.escapeAttribute;
	var node = core.node;
	var posPost = core.posPost;

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
			vfwoo_company_logo: store.company_logo ? literalSafe( '<img src="' + escapeAttribute( store.company_logo ) + '" alt="" style="max-width:50% !important;height:auto;" />' ) : '',
			vfwoo_pdf_footer: text( store.pdf_footer ).replace( /\r?\n/g, '<br>' ),
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
	// With the bridge's own template on, that template replaces the Webkul one on every print, so the
	// change applies at once and does not depend on the template Webkul cached in the browser.
	function fillInvoiceVariables( html, order ) {
		var settings = window.vfwooWebkulBridge;
		var own = !! ( settings && typeof settings.ownTemplate === 'string' && settings.ownTemplate );
		var bridge = order && order.vfwoo_webkul_bridge;
		if ( typeof html !== 'string' ) {
			return html;
		}
		if ( own ) {
			html = settings.ownTemplate;
			bridge = bridge || {};
		}
		if ( ! bridge ) {
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

		// A paragraph marked vfwoo-hide-empty disappears when all its VFWoo variables are empty.
		html = html.replace( /<p\b[^>]*\bvfwoo-hide-empty\b[^>]*>[\s\S]*?<\/p>/g, function ( paragraph ) {
			var tags = paragraph.match( /\$\{vfwoo_[a-z_]+\}/g );
			if ( ! tags ) {
				return paragraph;
			}
			var filled = tags.some( function ( tag ) {
				var value = values[ tag.slice( 2, -1 ) ];
				return value !== undefined && value !== '';
			} );
			return filled ? paragraph : '';
		} );

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

	// Tax ID field in the POS customer form (required only with Verifactu); Webkul submits every input of the form.
	function renderNifField( field, customer ) {
		var element = window.wp && window.wp.element;
		if ( ! element || ! config || ! config.nifLabel ) {
			return field;
		}
		var nif = customer && customer.vfwoo_webkul_nif ? customer.vfwoo_webkul_nif : '';
		var unverified = nif && customer.vfwoo_webkul_nif_status === 'unverified';
		return element.createElement(
			'div',
			{ className: 'customer-nif' },
			element.createElement( 'label', { htmlFor: 'pos_customer_nif' }, config.nifLabel, config.nifRequired ? element.createElement( 'i', null, '*' ) : null ),
			element.createElement( 'input', { type: 'text', name: 'pos_customer_nif', id: 'pos_customer_nif', defaultValue: nif, autoComplete: 'off' } ),
			unverified ? element.createElement( 'span', { className: 'error' }, config.nifUnverified ) : null
		);
	}

	// ---- "Marcas" screen: sales summary by brand, built with the shared POS components of core.js (window.vfwooBridge.ui) ----
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

	// "Marcas" is built like the POS Informe screen (core.js ui.report): filters, total cards and a report table.
	function buildBrandsPage( root ) {
		var ui = core.ui;
		var labels = config.brands;
		var loader = ui.loader( labels.loading );
		var money = function ( value, currency ) {
			try {
				return new Intl.NumberFormat( document.documentElement.lang || 'es-ES', { style: 'currency', currency: currency || 'EUR' } ).format( value );
			} catch ( error ) {
				return Number( value ).toFixed( 2 );
			}
		};
		var range = presetRange( 'today' );
		var data = null;
		var brandFilter = '';

		var report = ui.report( root, labels.title );

		// Period (fills the dates), the dates and the brand.
		var period = ui.select( [ [ 'today', labels.today ], [ 'yesterday', labels.yesterday ], [ 'week', labels.week ], [ 'month', labels.month ], [ 'custom', labels.custom ] ], 'today' );
		var fromInput = ui.input( 'date', range.start );
		var toInput = ui.input( 'date', range.end );
		period.addEventListener( 'change', function () {
			if ( period.value !== 'custom' ) {
				var preset = presetRange( period.value );
				fromInput.value = preset.start;
				toInput.value = preset.end;
			}
		} );
		[ fromInput, toInput ].forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				period.value = 'custom';
			} );
		} );
		var brandSelect = ui.select( [ [ '', labels.allBrands ] ], '' );
		brandSelect.addEventListener( 'change', function () {
			brandFilter = brandSelect.value;
			draw();
		} );
		report.fields.appendChild( ui.reportField( labels.period, period ) );
		report.fields.appendChild( ui.reportField( labels.from, fromInput ) );
		report.fields.appendChild( ui.reportField( labels.to, toInput ) );
		report.fields.appendChild( ui.reportField( labels.menu, brandSelect ) );
		var apply = ui.reportButton( labels.filter );
		apply.addEventListener( 'click', function () {
			range = { start: fromInput.value, end: toInput.value };
			load();
		} );
		report.footer.appendChild( apply );

		var content = node( 'div' );
		report.container.appendChild( content );

		function fillBrands() {
			brandSelect.textContent = '';
			[ [ '', labels.allBrands ] ].concat( data ? data.brands.map( function ( brand ) {
				return [ brand.name, brand.name ];
			} ) : [] ).forEach( function ( option ) {
				var item = node( 'option', '', option[ 1 ] );
				item.value = option[ 0 ];
				brandSelect.appendChild( item );
			} );
			brandSelect.value = brandFilter;
		}

		function draw() {
			content.textContent = '';
			if ( ! data ) {
				return;
			}
			var brands = data.brands.filter( function ( brand ) {
				return ! brandFilter || brand.name === brandFilter;
			} );
			if ( ! brands.length ) {
				content.appendChild( ui.noResult( labels.empty ) );
				return;
			}
			var total = { units: 0, net: 0, gross: 0, refund: 0, orders: 0 };
			brands.forEach( function ( brand ) {
				[ 'units', 'net', 'gross', 'refund', 'orders' ].forEach( function ( key ) {
					total[ key ] += brand[ key ];
				} );
			} );
			content.appendChild( ui.reportCards( [
				[ labels.units, total.units ],
				[ labels.net, money( total.net, data.currency ) ],
				[ labels.gross, money( total.gross, data.currency ) ],
				[ labels.orders, total.orders ],
				[ labels.refunds, money( total.refund, data.currency ) ]
			] ) );

			var table = ui.reportTable( labels.title, [ labels.brand, labels.units, labels.net, labels.gross, labels.orders, labels.refunds ] );
			function row( item, name, bold ) {
				var tr = node( 'tr' );
				var first = node( 'td' );
				first.appendChild( bold ? node( 'strong', '', name ) : document.createTextNode( name ) );
				tr.appendChild( first );
				[ item.units, money( item.net, data.currency ), money( item.gross, data.currency ), item.orders, money( item.refund, data.currency ) ].forEach( function ( value ) {
					tr.appendChild( node( 'td', '', String( value ) ) );
				} );
				return tr;
			}
			brands.forEach( function ( brand ) {
				var children = [];
				var tr = row( brand, '▸ ' + brand.name, true );
				var title = tr.querySelector( 'strong' );
				tr.addEventListener( 'click', function () {
					var open = title.textContent.charAt( 0 ) === '▸';
					title.textContent = ( open ? '▾ ' : '▸ ' ) + brand.name;
					children.forEach( function ( child ) {
						child.hidden = ! open;
					} );
				} );
				table.body.appendChild( tr );
				brand.products.forEach( function ( product ) {
					var child = row( product, product.name, false );
					child.hidden = true;
					children.push( child );
					table.body.appendChild( child );
				} );
			} );
			table.body.appendChild( row( total, labels.total, true ) );
			content.appendChild( table.element );
			content.appendChild( ui.note( labels.note ) );
		}

		function load() {
			loader.show();
			content.textContent = '';
			core.posPost( config.brandReportUrl, { start_date: range.start, end_date: range.end } )
				.then( function ( result ) {
					loader.hide();
					if ( ! result || ! result.success ) {
						data = null;
						content.appendChild( ui.noResult( result && result.message ? result.message : labels.error ) );
						return;
					}
					data = result.data;
					brandFilter = '';
					fillBrands();
					draw();
				} )
				.catch( function () {
					loader.hide();
					data = null;
					content.appendChild( ui.noResult( labels.error ) );
				} );
		}

		load();
	}

	if ( config && config.brands ) {
		core.addScreen( {
			id: 'brands',
			path: '/marcas',
			title: config.brands.menu,
			// A tag: label shape and its hole.
			iconPaths: [ 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z', 'M6.3 7.5a1.2 1.2 0 1 0 2.4 0a1.2 1.2 0 1 0 -2.4 0' ],
			build: buildBrandsPage
		} );
	}

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

	// ---- Complete invoice from a ticket, from the order detail (Verifactu registers it as F3 when it must) ----
	function openFullInvoiceDialog( order ) {
		var labels = config.fullInvoice;
		var orderId = order.order_id || order.id;
		var overlay = node( 'div', 'vfwoo-full-overlay' );
		var box = node( 'div', 'vfwoo-full-box' );
		var body = node( 'div', 'vfwoo-full-body' );
		var head = node( 'div', 'vfwoo-full-head' );
		var closeButton = node( 'button', 'vfwoo-full-x', '\u00d7' );
		closeButton.type = 'button';
		closeButton.setAttribute( 'aria-label', labels.close );
		closeButton.addEventListener( 'click', close );
		head.appendChild( node( 'h3', '', labels.title + ' #' + orderId ) );
		head.appendChild( closeButton );
		box.appendChild( head );
		box.appendChild( body );
		overlay.appendChild( box );
		document.body.appendChild( overlay );

		// How the invoice will be registered: 'local' (the ticket becomes the complete invoice) or 'verifactu'.
		var route = 'local';

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
			return node( 'p', 'vfwoo-full-message' + ( isError ? ' is-error' : '' ), text );
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
			posPost( config.fullInvoice.statusUrl, { order_id: orderId } )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						return fail( result && result.message );
					}
					if ( result.state === 'ready' ) {
						route = result.route === 'verifactu' ? 'verifactu' : 'local';
						return showSearch();
					}
					if ( result.state === 'pending' ) {
						return show( message( result.message || labels.none ), button( labels.refresh, 'primary vfwoo-full-primary', checkOrder ), button( labels.close, '', close ) );
					}
					return show( message( result.message || labels.none ), button( labels.close, '', close ) );
				} )
				.catch( function () {
					fail();
				} );
		}

		function showSearch() {
			var input = node( 'input', 'vfwoo-full-input' );
			input.type = 'search';
			input.placeholder = labels.searchHint;
			var results = node( 'div', 'vfwoo-full-results' );

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
				var row = node( 'div', 'vfwoo-full-row' );
				var name = ( ( customer.first_name || '' ) + ' ' + ( customer.last_name && customer.last_name !== 'null' ? customer.last_name : '' ) ).trim();
				var info = node( 'div', 'vfwoo-full-info' );
				info.appendChild( node( 'strong', '', customer.vfwoo_webkul_company || name || customer.email ) );
				info.appendChild( node( 'span', '', customer.email || '' ) );
				var nif = customer.vfwoo_webkul_nif || '';
				var usable = ( !! nif || ! labels.nifRequired ) && customer.vfwoo_webkul_is_default !== true;
				info.appendChild( node( 'span', '', customer.vfwoo_webkul_is_default === true ? labels.defaultCst : ( nif ? nif : ( labels.nifRequired ? labels.noNif : labels.noNifOptional ) ) ) );
				row.appendChild( info );
				var choose = button( labels.choose, 'primary vfwoo-full-primary', function () {
					showConfirm( customer, name, nif );
				} );
				choose.disabled = ! usable;
				row.appendChild( choose );
				return row;
			}

			var searchButton = button( labels.search, 'primary vfwoo-full-primary', search );
			input.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'Enter' ) {
					search();
				}
			} );
			var bar = node( 'div', 'vfwoo-full-bar' );
			bar.appendChild( input );
			bar.appendChild( searchButton );
			var actions = node( 'div', 'vfwoo-full-actions' );
			actions.appendChild( button( labels.create, '', showCreate ) );
			actions.appendChild( button( labels.cancel, '', close ) );
			show( bar, results, actions );
			input.focus();
		}

		function showCreate() {
			var fields = {};
			var form = node( 'div', 'vfwoo-full-form' );
			[ [ 'firstName', 'text' ], [ 'lastName', 'text' ], [ 'company', 'text' ], [ 'address', 'text' ], [ 'postcode', 'text' ], [ 'city', 'text' ], [ 'phone', 'tel' ], [ 'email', 'email' ] ].forEach( function ( definition ) {
				var label = node( 'label', '', labels[ definition[ 0 ] ] );
				var input = node( 'input', 'vfwoo-full-input' );
				input.type = definition[ 1 ];
				label.appendChild( input );
				form.appendChild( label );
				fields[ definition[ 0 ] ] = input;
			} );
			// Country: Spain by default. Outside Spain the document is not checked unless the cashier says so.
			var countryLabel = node( 'label', '', labels.countryLabel );
			var countrySelect = node( 'select', 'vfwoo-full-input' );
			var countries = labels.countries || { ES: 'España' };
			Object.keys( countries )
				.sort( function ( a, b ) {
					if ( a === 'ES' || b === 'ES' ) {
						return a === 'ES' ? -1 : 1;
					}
					return String( countries[ a ] ).localeCompare( String( countries[ b ] ) );
				} )
				.forEach( function ( code ) {
					var option = node( 'option', '', countries[ code ] );
					option.value = code;
					countrySelect.appendChild( option );
				} );
			countrySelect.value = 'ES';
			countryLabel.appendChild( countrySelect );
			form.appendChild( countryLabel );

			var nifLabel = node( 'label', '', config.nifLabel );
			var nifInput = node( 'input', 'vfwoo-full-input' );
			nifInput.type = 'text';
			nifInput.autocomplete = 'off';
			nifLabel.appendChild( nifInput );
			form.appendChild( nifLabel );

			var noCheckLabel = node( 'label', 'vfwoo-full-check' );
			var noCheck = node( 'input', '' );
			noCheck.type = 'checkbox';
			noCheckLabel.appendChild( noCheck );
			noCheckLabel.appendChild( document.createTextNode( ' ' + labels.noCheck ) );
			form.appendChild( noCheckLabel );
			countrySelect.addEventListener( 'change', function () {
				noCheck.checked = countrySelect.value !== 'ES';
			} );
			var status = message( '' );

			var save = button( labels.save, 'primary vfwoo-full-primary', function () {
				var company = fields.company.value.trim();
				// Webkul asks for a name: a company without a contact person uses its own name.
				var first = fields.firstName.value.trim() || company;
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
					[ 'pos_customer_nif', nifInput.value.trim() ],
					[ 'pos_customer_company', company ],
					[ 'pos_customer_address1', fields.address.value.trim() ],
					[ 'pos_customer_pincode', fields.postcode.value.trim() ],
					[ 'pos_customer_city', fields.city.value.trim() ],
					[ 'pos_customer_country', countrySelect.value ],
					[ 'pos_customer_nocheck', noCheck.checked ? '1' : '0' ]
				].map( function ( pair ) {
					return { name: pair[ 0 ], value: pair[ 1 ] };
				} );
				save.disabled = true;
				status.className = 'vfwoo-full-message';
				status.textContent = labels.checking;
				posPost( window.wkwcpos_variables.WK_CREATE_CUSTOMER_ENDPOINT, { pos: list } )
					.then( function ( result ) {
						save.disabled = false;
						if ( result && result.success && result.data ) {
							var created = result.data;
							var name = ( ( created.first_name || '' ) + ' ' + ( created.last_name || '' ) ).trim();
							return showConfirm( created, name, created.vfwoo_webkul_nif || '' );
						}
						status.className = 'vfwoo-full-message is-error';
						status.textContent = result && result.msg ? result.msg : labels.error;
					} )
					.catch( function () {
						save.disabled = false;
						status.className = 'vfwoo-full-message is-error';
						status.textContent = labels.error;
					} );
			} );
			var actions = node( 'div', 'vfwoo-full-actions' );
			actions.appendChild( save );
			actions.appendChild( button( labels.back, '', showSearch ) );
			show( form, status, actions );
		}

		function showConfirm( customer, name, nif ) {
			var company = customer.vfwoo_webkul_company || '';
			var text = ( route === 'verifactu' ? labels.confirmSent : labels.confirmLocal )
				.replace( '%order%', orderId )
				.replace( '%name%', company || name || customer.email )
				.replace( '%nif%', nif ? '(' + nif + ')' : '' );
			var status = message( '' );
			var issue = button( labels.issue, 'primary vfwoo-full-primary', function () {
				issue.disabled = true;
				status.className = 'vfwoo-full-message';
				status.textContent = labels.issuing;
				posPost( config.fullInvoice.issueUrl, { order_id: orderId, customer_id: customer.id } )
					.then( function ( result ) {
						if ( result && result.success ) {
							// Webkul prints from this same order object: give it the complete invoice data.
							if ( result.bridge ) {
								order.vfwoo_webkul_bridge = result.bridge;
							}
							// The button stays: a mistaken customer can be changed again (the server says when it cannot).
							return show( message( route === 'verifactu' ? labels.issuedSent : labels.issuedLocal ), button( labels.close, 'primary vfwoo-full-primary', close ) );
						}
						issue.disabled = false;
						status.className = 'vfwoo-full-message is-error';
						status.textContent = result && result.message ? result.message : labels.error;
					} )
					.catch( function () {
						issue.disabled = false;
						status.className = 'vfwoo-full-message is-error';
						status.textContent = labels.error;
					} );
			} );
			var actions = node( 'div', 'vfwoo-full-actions' );
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

	// Button next to "Print invoice" in the POS order detail. The server decides whether the order can change.
	function renderFullInvoiceButton( output, order ) {
		var element = window.wp && window.wp.element;
		if ( ! element || ! config || ! config.fullInvoice || ! order ) {
			return output;
		}
		return element.createElement(
			element.Fragment,
			null,
			output,
			// Same wrapper and classes as Webkul's own "Print invoice" button, so it looks the same in every theme.
			element.createElement(
				'div',
				{ className: 'pos-order-invoice vfwoo-full' },
				element.createElement(
					'button',
					{
						type: 'button',
						className: 'primary',
						onClick: function () {
							openFullInvoiceDialog( order );
						}
					},
					element.createElement(
						'svg',
						{ viewBox: '0 0 24 24', width: 22, height: 22, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' },
						element.createElement( 'path', { d: 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z' } ),
						element.createElement( 'path', { d: 'M14 3v5h5M9 13h6M9 17h6' } )
					),
					' ',
					config.fullInvoice.button
				)
			)
		);
	}

	hooks.addFilter( 'wkwcpos_add_after_print_invoice_button', 'vfwoo-webkul-pos-bridge', renderFullInvoiceButton );

	// Prints VFWoo's invoice PDF the way Webkul prints the ticket: a hidden iframe and print(), so the
	// print dialog opens without leaving the POS. If the browser refuses, the PDF opens in a new tab.
	// VFWoo builds the PDF on every request, so it can take a few seconds: `done` tells the button when
	// to stop showing the loading state (once the print starts, or after a safety timeout).
	function printInvoicePdf( order, done, refundId ) {
		var orderId = order.order_id || order.id;
		var url = config.pdf.url + '&order_id=' + encodeURIComponent( orderId ) + '&_wpnonce=' + encodeURIComponent( config.pdf.nonce );
		// With a refund id it prints that refund's credit note (rectificativa) instead of the invoice.
		if ( refundId ) {
			url += '&refund_id=' + encodeURIComponent( refundId );
		}
		var previous = document.querySelector( 'iframe.vfwoo-pdf-frame' );
		if ( previous ) {
			previous.remove();
		}

		var finished = false;
		var finish = function () {
			if ( ! finished ) {
				finished = true;
				done();
			}
		};
		// If the PDF never answers, give the button back after a minute.
		setTimeout( finish, 60000 );

		var frame = document.createElement( 'iframe' );
		frame.className = 'vfwoo-pdf-frame';
		frame.setAttribute( 'aria-hidden', 'true' );
		frame.setAttribute( 'tabindex', '-1' );
		var printed = false;
		frame.addEventListener( 'load', function () {
			if ( printed ) {
				return;
			}
			printed = true;
			setTimeout( function () {
				try {
					frame.contentWindow.focus();
					frame.contentWindow.print();
				} catch ( error ) {
					window.open( url, '_blank', 'noopener' );
				}
				finish();
			}, 500 );
		} );
		frame.src = url;
		document.body.appendChild( frame );
	}

	// Own component so the button can show its loading state (spinner, disabled) while the PDF is built.
	function PdfButton( props ) {
		var element = window.wp.element;
		var state = element.useState( false );
		var loading = state[ 0 ];
		var setLoading = state[ 1 ];
		var mounted = element.useRef( true );

		element.useEffect( function () {
			mounted.current = true;
			return function () {
				mounted.current = false;
			};
		}, [] );

		function start() {
			if ( loading ) {
				return;
			}
			setLoading( true );
			printInvoicePdf( props.order, function () {
				if ( mounted.current ) {
					setLoading( false );
				}
			}, props.refundId );
		}

		return element.createElement(
			'div',
			{ className: 'pos-order-invoice vfwoo-pdf' },
			element.createElement(
				'button',
				{
					type: 'button',
					className: 'primary' + ( loading ? ' vfwoo-loading' : '' ),
					disabled: loading,
					'aria-busy': loading ? 'true' : 'false',
					onClick: start
				},
				loading
					? element.createElement( 'span', { className: 'vfwoo-spinner', 'aria-hidden': 'true' } )
					: element.createElement(
						'svg',
						{ viewBox: '0 0 24 24', width: 22, height: 22, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' },
						element.createElement( 'path', { d: 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z' } ),
						element.createElement( 'path', { d: 'M14 3v5h5M9 14h6v4H9z' } )
					),
				' ',
				loading ? props.loadingLabel : props.label
			)
		);
	}

	// «Imprimir factura» below the «Cambiar factura» one, plus «Imprimir rectificativa» for each refund; shown only when the order already has an invoice number.
	function renderPdfButton( output, order ) {
		var element = window.wp && window.wp.element;
		var fiscal = order && order.vfwoo_webkul_bridge && order.vfwoo_webkul_bridge.fiscal;
		if ( ! element || ! element.useState || ! config || ! config.pdf || ! fiscal || ! fiscal.invoice_number ) {
			return output;
		}
		var buttons = [ element.createElement( PdfButton, { order: order, key: 'vfwoo-pdf', label: config.pdf.button, loadingLabel: config.pdf.loading } ) ];

		// One «Imprimir rectificativa» button per refund the order has (VFWoo issues a credit note for each).
		var refunds = Array.isArray( order.refunds ) ? order.refunds.filter( function ( refund ) {
			return refund && refund.id;
		} ) : [];
		refunds.forEach( function ( refund, index ) {
			buttons.push(
				element.createElement( PdfButton, {
					order: order,
					key: 'vfwoo-pdf-refund-' + refund.id,
					refundId: refund.id,
					label: config.pdf.creditNote + ( refunds.length > 1 ? ' ' + ( index + 1 ) : '' ),
					loadingLabel: config.pdf.creditNoteLoading
				} )
			);
		} );

		return element.createElement.apply( element, [ element.Fragment, null, output ].concat( buttons ) );
	}

	hooks.addFilter( 'wkwcpos_add_after_print_invoice_button', 'vfwoo-webkul-pos-bridge-pdf', renderPdfButton );

	// ---- Order status dropdown: only Completado, Anular (cancel) and Devolver (refund) ----
	// Webkul fills the dropdown with every WooCommerce status, from the same list it uses to name the status in
	// the order table, so that list cannot be trimmed without breaking those names. The options are tidied in
	// place instead: the unwanted ones are hidden (never removed: the dropdown belongs to Webkul's React tree),
	// «Cancelado» and «Reembolsado» read as the actions «Anular» and «Devolver», and the order's current status
	// always stays visible (as it is called) so the control never shows a wrong value.
	function findStatusSelects() {
		return Array.prototype.filter.call( document.querySelectorAll( 'select' ), function ( select ) {
			return !! select.querySelector( 'option[value="wc-completed"]' );
		} );
	}

	function tidyStatusSelect( select ) {
		var settings = config && config.orderStatus;
		if ( ! settings || ! select ) {
			return;
		}
		// Only the order status dropdown: the mousedown listener below calls this for any select in the POS.
		if ( ! select.querySelector( 'option[value="wc-completed"]' ) ) {
			return;
		}
		var allowed = ( settings.allowed || [] ).map( function ( status ) {
			return 'wc-' + status;
		} );
		Array.prototype.forEach.call( select.options, function ( option ) {
			var value = option.value;
			var isAllowed = allowed.indexOf( value ) !== -1;
			var isCurrent = value === select.value;
			var textNode = option.firstChild && option.firstChild.nodeType === 3 ? option.firstChild : null;
			if ( textNode && option.getAttribute( 'data-vfwoo-label' ) === null ) {
				option.setAttribute( 'data-vfwoo-label', textNode.nodeValue );
			}
			var verb = value === 'wc-cancelled' ? settings.cancel : ( value === 'wc-refunded' ? settings.refund : '' );
			var wanted = verb && ! isCurrent ? verb : option.getAttribute( 'data-vfwoo-label' );
			if ( textNode && wanted && textNode.nodeValue !== wanted ) {
				textNode.nodeValue = wanted;
			}
			if ( option.hidden !== ( ! isAllowed && ! isCurrent ) ) {
				option.hidden = ! isAllowed && ! isCurrent;
			}
			if ( option.disabled !== ! isAllowed ) {
				option.disabled = ! isAllowed;
			}
		} );
	}

	// Webkul asks «are you sure?» before changing the status; for Anular and Devolver the question says what happens.
	function patchStatusConfirm() {
		var jq = window.jQuery;
		if ( ! jq || typeof jq.confirm !== 'function' || jq.confirm.vfwooPatched ) {
			return;
		}
		var original = jq.confirm;
		var patched = function ( options ) {
			var args = Array.prototype.slice.call( arguments );
			try {
				var settings = config && config.orderStatus;
				if ( settings && options && typeof options === 'object' && options.buttons && options.buttons.change && typeof options.content === 'string' && options.content.indexOf( '<h4>' ) === 0 ) {
					var chosen = '';
					findStatusSelects().forEach( function ( select ) {
						if ( select.value === 'wc-cancelled' || select.value === 'wc-refunded' ) {
							chosen = select.value;
						}
					} );
					var text = chosen === 'wc-cancelled' ? settings.confirmCancel : ( chosen === 'wc-refunded' ? settings.confirmRefund : '' );
					if ( text ) {
						options.content = '<h4>' + escapeHtml( text ) + '</h4>';
						args[ 0 ] = options;
					}
				}
			} catch ( error ) {
				// Whatever goes wrong here, Webkul's own question is shown.
			}
			return original.apply( this, args );
		};
		Object.keys( original ).forEach( function ( key ) {
			patched[ key ] = original[ key ];
		} );
		patched.vfwooPatched = true;
		jq.confirm = patched;
	}

	( function watchStatusSelect() {
		if ( ! config || ! config.orderStatus || ! window.MutationObserver ) {
			return;
		}
		var timer = null;
		var run = function () {
			timer = null;
			patchStatusConfirm();
			findStatusSelects().forEach( tidyStatusSelect );
		};
		var schedule = function () {
			if ( ! timer ) {
				timer = window.setTimeout( run, 30 );
			}
		};
		var start = function () {
			new window.MutationObserver( schedule ).observe( document.body, { childList: true, subtree: true } );
			document.addEventListener( 'mousedown', function ( event ) {
				if ( event.target && event.target.tagName === 'SELECT' ) {
					tidyStatusSelect( event.target );
				}
			}, true );
			run();
		};
		if ( document.body ) {
			start();
		} else {
			document.addEventListener( 'DOMContentLoaded', start );
		}
	}() );
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

	// ---- Header shortcuts: WooCommerce admin screens in a window inside the POS, and the public shop ----
	// WooCommerce's own logo (from its admin menu); the other icons are Dashicons, as WooCommerce uses.
	var WOO_LOGO = "<svg aria-hidden=\"true\" focusable=\"false\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 85.9 47.6\"><path fill=\"currentColor\" d=\"M77.4,0.1c-4.3,0-7.1,1.4-9.6,6.1L56.4,27.7V8.6c0-5.7-2.7-8.5-7.7-8.5s-7.1,1.7-9.6,6.5L28.3,27.7V8.8 c0-6.1-2.5-8.7-8.6-8.7H7.3C2.6,0.1,0,2.3,0,6.3s2.5,6.4,7.1,6.4h5.1v24.1c0,6.8,4.6,10.8,11.2,10.8S33,45,36.3,38.9l7.2-13.5v11.4 c0,6.7,4.4,10.8,11.1,10.8s9.2-2.3,13-8.7l16.6-28c3.6-6.1,1.1-10.8-6.9-10.8C77.3,0.1,77.3,0.1,77.4,0.1z\"/></svg>";

	// Hides WordPress's menu and bar inside the window: the POS has its own menu.
	var EMBED_CSS = ':root{--wp-admin--admin-bar--height:0px !important}' +
		'#adminmenumain,#adminmenuback,#adminmenuwrap,#wpadminbar,#wpfooter{display:none !important}' +
		'html.wp-toolbar{padding-top:0 !important}' +
		'#wpcontent,#wpfooter{margin-left:0 !important}' +
		'#wpbody-content{padding-bottom:0 !important}' +
		'.woocommerce-layout__header{top:0 !important}';

	var shortcutWindow = null;

	function shortcutLabels() {
		return ( config && config.shortcutLabels ) || { close: 'Close', openTab: 'Open in new tab', loading: '...' };
	}

	function shortcutIcon( item, element ) {
		if ( 'woo' === item.icon ) {
			return element.createElement( 'span', { className: 'vfwoo-woo-logo', dangerouslySetInnerHTML: { __html: WOO_LOGO } } );
		}
		return element.createElement( 'span', { className: 'dashicons dashicons-' + item.icon, 'aria-hidden': 'true' } );
	}

	// Where the window goes: under the POS header and to the right of its left menu.
	function measureWindow() {
		var header = document.querySelector( 'header' );
		var top = header ? Math.round( header.getBoundingClientRect().bottom ) : 0;
		var rail = null;
		var probe = document.elementFromPoint( 4, top + 40 );
		while ( probe && probe !== document.body && probe !== document.documentElement ) {
			var box = probe.getBoundingClientRect();
			if ( box.left <= 1 && box.width <= 220 && box.height > 200 ) {
				rail = box;
			}
			probe = probe.parentElement;
		}
		return { top: top, left: rail ? Math.round( rail.right ) : 0 };
	}

	function placeWindow() {
		if ( ! shortcutWindow ) {
			return;
		}
		shortcutWindow.style.display = 'none';
		var spot = measureWindow();
		shortcutWindow.style.display = '';
		shortcutWindow.style.top = spot.top + 'px';
		shortcutWindow.style.left = spot.left + 'px';
	}

	function closeShortcutWindow() {
		if ( shortcutWindow ) {
			shortcutWindow.remove();
			shortcutWindow = null;
		}
		document.documentElement.removeAttribute( 'data-vfwoo-shortcut' );
		document.removeEventListener( 'keydown', onShortcutKey );
		window.removeEventListener( 'resize', placeWindow );
	}

	function onShortcutKey( event ) {
		if ( 'Escape' === event.key ) {
			closeShortcutWindow();
		}
	}

	function openShortcutWindow( item ) {
		var labels = shortcutLabels();
		closeShortcutWindow();

		var frame = node( 'iframe', 'vfwoo-frame' );
		var loading = node( 'div', 'vfwoo-frame-loading', labels.loading );
		var current = item.url;

		function show( url ) {
			current = url;
			frame.style.visibility = 'hidden';
			loading.hidden = false;
			frame.src = url;
		}

		frame.setAttribute( 'title', item.title );
		frame.addEventListener( 'load', function () {
			try {
				var doc = frame.contentDocument;
				if ( doc && doc.head && ! doc.getElementById( 'vfwoo-embed' ) ) {
					var style = doc.createElement( 'style' );
					style.id = 'vfwoo-embed';
					style.textContent = EMBED_CSS;
					doc.head.appendChild( style );
				}
				// Hide the page again as soon as it starts to leave, so the next one never shows WordPress's menu.
				frame.contentWindow.addEventListener( 'beforeunload', function () {
					frame.style.visibility = 'hidden';
					loading.hidden = false;
				} );
				current = frame.contentWindow.location.href;
			} catch ( error ) {
				// Not the same origin: keep it as it is.
			}
			frame.style.visibility = 'visible';
			loading.hidden = true;
		} );

		var win = node( 'div', 'vfwoo-frame-ov' );
		var bar = node( 'div', 'vfwoo-frame-bar' );
		var title = node( 'div', 'vfwoo-frame-title' );
		var icon = document.createElement( 'span' );
		icon.className = 'vfwoo-frame-icon' + ( 'woo' === item.icon ? ' vfwoo-woo-logo' : ' dashicons dashicons-' + item.icon );
		if ( 'woo' === item.icon ) {
			icon.innerHTML = WOO_LOGO;
		}
		title.appendChild( icon );
		title.appendChild( node( 'span', '', item.title ) );

		var newTab = node( 'button', '', labels.openTab );
		newTab.type = 'button';
		newTab.addEventListener( 'click', function () {
			window.open( current, '_blank', 'noopener' );
		} );
		var closeButton = node( 'button', 'primary', labels.close );
		closeButton.type = 'button';
		closeButton.addEventListener( 'click', closeShortcutWindow );

		bar.appendChild( title );
		bar.appendChild( newTab );
		bar.appendChild( closeButton );
		win.appendChild( bar );

		if ( Array.isArray( item.tabs ) && item.tabs.length > 1 ) {
			var tabs = node( 'div', 'vfwoo-frame-tabs' );
			item.tabs.forEach( function ( tab, index ) {
				var button = node( 'button', index ? '' : 'is-active', tab.title );
				button.type = 'button';
				button.addEventListener( 'click', function () {
					Array.prototype.forEach.call( tabs.children, function ( other ) {
						other.classList.toggle( 'is-active', other === button );
					} );
					show( tab.url );
				} );
				tabs.appendChild( button );
			} );
			win.appendChild( tabs );
		}

		var body = node( 'div', 'vfwoo-frame-body' );
		body.appendChild( loading );
		body.appendChild( frame );
		win.appendChild( body );
		document.body.appendChild( win );
		shortcutWindow = win;
		// The matching header icon takes the accent color while its window is open (see pos-bridge.css).
		document.documentElement.setAttribute( 'data-vfwoo-shortcut', item.id );
		placeWindow();
		document.addEventListener( 'keydown', onShortcutKey );
		window.addEventListener( 'resize', placeWindow );
		show( item.url );
	}

	function openShortcut( item ) {
		if ( 'tab' === item.kind ) {
			window.open( item.url, '_blank', 'noopener' );
			return;
		}
		openShortcutWindow( item );
	}

	// Hint with the name and purpose of a header shortcut. It is drawn in the body: the row of icons
	// clips whatever falls outside it.
	var tipTimer = null;
	var tipNode = null;

	function hideShortcutTip() {
		window.clearTimeout( tipTimer );
		if ( tipNode && tipNode.parentNode ) {
			tipNode.parentNode.removeChild( tipNode );
		}
		tipNode = null;
	}

	function showShortcutTip( item, target ) {
		hideShortcutTip();
		if ( ! item || ! target ) {
			return;
		}
		tipTimer = window.setTimeout( function () {
			if ( ! document.body.contains( target ) ) {
				return;
			}
			var node = document.createElement( 'div' );
			node.className = 'vfwoo-tip';
			node.setAttribute( 'role', 'tooltip' );
			var title = document.createElement( 'strong' );
			title.className = 'vfwoo-tip-title';
			title.textContent = item.title || '';
			node.appendChild( title );
			if ( item.description ) {
				var text = document.createElement( 'span' );
				text.textContent = item.description;
				node.appendChild( text );
			}
			document.body.appendChild( node );
			var box = target.getBoundingClientRect();
			var width = node.offsetWidth;
			var left = Math.min( Math.max( 8, box.right - width ), window.innerWidth - width - 8 );
			node.style.left = left + 'px';
			node.style.top = ( box.bottom + 8 ) + 'px';
			tipNode = node;
		}, 250 );
	}

	// The new icons go first in the header's row of icons, before Webkul's own.
	function renderShortcuts( output ) {
		var element = window.wp && window.wp.element;
		if ( ! element || ! config || ! Array.isArray( config.shortcuts ) || ! config.shortcuts.length ) {
			return output;
		}
		var last = config.shortcuts.length - 1;
		var items = config.shortcuts.map( function ( item, index ) {
			return element.createElement(
				'li',
				{ key: 'vfwoo-shortcut-' + item.id, 'data-shortcut': item.id, className: 'vfwoo-shortcut' + ( index === last ? ' vfwoo-shortcut-last' : '' ) },
				element.createElement(
					'div',
					{
						role: 'button',
						tabIndex: 0,
						'aria-label': item.title,
						onMouseEnter: function ( event ) {
							showShortcutTip( item, event.currentTarget );
						},
						onMouseLeave: hideShortcutTip,
						onFocus: function ( event ) {
							showShortcutTip( item, event.currentTarget );
						},
						onBlur: hideShortcutTip,
						onClick: function () {
							hideShortcutTip();
							openShortcut( item );
						},
						onKeyDown: function ( event ) {
							if ( 'Enter' === event.key || ' ' === event.key ) {
								event.preventDefault();
								openShortcut( item );
							}
						}
					},
					shortcutIcon( item, element )
				)
			);
		} );
		return element.createElement( element.Fragment, null, output || null, items );
	}

	// Centralized inventory (D-036): Webkul refuses to sell offline because it validates the cart against the server
	// before paying. We keep that check online and skip it offline, so the till sells from its local stock copy and
	// the order syncs later. Not centralized, or Webkul renamed the flag: nothing to do.
	function allowOfflineSales() {
		var script = window.apif_script;
		if ( ! script || ! script.wk_pos_validate_product_at_pay ) {
			return;
		}
		try {
			Object.defineProperty( script, 'wk_pos_validate_product_at_pay', {
				configurable: true,
				get: function () {
					return window.navigator.onLine ? 1 : 0;
				}
			} );
		} catch ( error ) {
			// Webkul keeps its own behaviour.
		}
	}

	allowOfflineSales();

	hooks.addFilter( 'wkwcpos_modify_load_category_products', 'vfwoo-webkul-pos-bridge', filterBrandProducts );

	hooks.addFilter( 'wkwc_add_custom_field_in_form_after_email', 'vfwoo-webkul-pos-bridge', renderNifField );
	hooks.addFilter( 'wkwcpos_modify_order_success_popup', 'vfwoo-webkul-pos-bridge', checkAfterSale );
	hooks.addFilter( 'wkwcpos_modify_homepage_products', 'vfwoo-webkul-pos-bridge', checkCatalogVersion );
	hooks.addFilter( 'wkwcpos_summary_modify_invoice_data', 'vfwoo-webkul-pos-bridge', fillInvoiceVariables );
	hooks.addFilter( 'wkwcpos_add_custom_header_data_at_right', 'vfwoo-webkul-pos-bridge', renderShortcuts );
	hooks.addFilter( 'wkwcpos_invoice_after_footer_details_block', 'vfwoo-webkul-pos-bridge', renderFiscalBlock );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
