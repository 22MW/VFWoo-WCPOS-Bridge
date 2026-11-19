/**
 * Shared pieces of the bridge in the POS. It runs before pos-bridge.js and before every module script, and
 * offers them one namespace: window.vfwooBridge. It does not change anything in the POS by itself.
 */
( function () {
	'use strict';

	if ( window.vfwooBridge ) {
		return;
	}

	var hooks = window.wp && window.wp.hooks ? window.wp.hooks : null;

	function escapeHtml( value ) {
		return String( value ).replace( /[&<>'"]/g, function ( character ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[ character ];
		} );
	}

	function escapeAttribute( value ) {
		return escapeHtml( value );
	}

	// Creates an element with an optional class and text (text is never parsed as HTML).
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

	function posUrl() {
		return ( window.wkwcpos_variables && window.wkwcpos_variables.POS_URL ) || '/pos';
	}

	function session() {
		try {
			return ( window.localStorage && window.localStorage.getItem( 'WKWCPOS_API_SESSION_ID' ) ) || '';
		} catch ( error ) {
			return '';
		}
	}

	// POST to a bridge REST route with the POS session. Webkul sends a new session id on a 401: it retries once.
	function posPost( url, payload, retry ) {
		payload.logged_in_user_id = window.apif_script && window.apif_script.logged_in ? window.apif_script.logged_in.user_id : 0;
		return window.fetch( url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', Accept: 'application/json', authkey: session() },
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

	// POST a FormData (files) to a bridge REST route with the POS session; same session retry as posPost.
	function posForm( url, formData, retry ) {
		formData.set( 'logged_in_user_id', window.apif_script && window.apif_script.logged_in ? window.apif_script.logged_in.user_id : 0 );
		return window.fetch( url, {
			method: 'POST',
			headers: { Accept: 'application/json', authkey: session() },
			body: formData
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result && result.success === false && result.status === 401 && result.session_id && retry !== false ) {
					window.localStorage.setItem( 'WKWCPOS_API_SESSION_ID', result.session_id );
					return posForm( url, formData, false );
				}
				return result;
			} );
	}

	// Data the server prepared for one module (see Module_Registry), or an empty object.
	function moduleConfig( id ) {
		return ( window.vfwooBridgeModules && window.vfwooBridgeModules[ id ] ) || {};
	}

	/**
	 * Adds a screen to the POS: a menu entry and a route drawn inside Webkul's own layout.
	 *
	 * screen = { id, path ('/marcas'), title (menu text), iconPaths (array of SVG path "d"), build( root, props ) }
	 * build() fills `root` (a div) once, with plain DOM.
	 */
	function addScreen( screen ) {
		if ( ! hooks || ! screen || ! screen.id || ! screen.path || typeof screen.build !== 'function' ) {
			return;
		}

		function Component( props ) {
			var element = window.wp.element;
			return element.createElement( 'div', {
				className: 'vfwoo-screen vfwoo-screen-' + screen.id,
				ref: function ( root ) {
					if ( root && ! root.getAttribute( 'data-ready' ) ) {
						root.setAttribute( 'data-ready', '1' );
						screen.build( root, props );
					}
				}
			} );
		}

		hooks.addFilter( 'wkwcpos_menus_list', 'vfwoo-webkul-pos-screen-' + screen.id, function ( menus ) {
			var element = window.wp && window.wp.element;
			if ( ! element || ! Array.isArray( menus ) ) {
				return menus;
			}
			var children = ( screen.iconPaths || [] ).map( function ( d, index ) {
				return element.createElement( 'path', { d: d, key: index } );
			} );
			var icon = element.createElement(
				'svg',
				{ viewBox: '0 0 24 24', width: 22, height: 22, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' },
				children
			);
			menus.push( { to: posUrl() + screen.path, classname: 'wkwcpos-menu-list', icon: icon, icon_classname: '', text: screen.title } );
			return menus;
		} );

		// Webkul builds its routes from this list.
		hooks.addFilter( 'wkwcpos_pages_list', 'vfwoo-webkul-pos-screen-' + screen.id, function ( pages ) {
			if ( ! Array.isArray( pages ) || ! window.wp || ! window.wp.element || ! window.apif_script ) {
				return pages;
			}
			pages.push( { name: 'Vfwoo-' + screen.id, path: '/' + window.apif_script.pos_path + screen.path, component: Component } );
			return pages;
		} );
	}

	/**
	 * UI components. They build the markup and classes the POS itself uses (see _dev/estilos-pos.md), so the POS
	 * styles apply to them: no look is defined here. Layout-only CSS lives in assets/css/ui.css.
	 */
	var ui = {};

	ui.input = function ( type, value ) {
		var element = node( 'input' );
		element.type = type;
		element.value = value === null || value === undefined ? '' : value;
		return element;
	};

	// options: [ [ value, text ], … ]
	ui.select = function ( options, value, className ) {
		var element = node( 'select', className || '' );
		options.forEach( function ( option ) {
			var item = node( 'option', '', option[ 1 ] );
			item.value = option[ 0 ];
			element.appendChild( item );
		} );
		if ( value !== undefined ) {
			element.value = value;
		}
		return element;
	};

	// The POS customer form ("Editar cliente"): div.pos-customers > div.pos-customer > form. Its styles hang from
	// that chain of classes, so the form copies it. Returns { element, form }; add fields to form.
	ui.formShell = function () {
		var wrap = node( 'div', 'pos-customers' );
		var box = node( 'div', 'pos-customer' );
		var form = node( 'form' );
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
		} );
		box.appendChild( form );
		wrap.appendChild( box );
		return { element: wrap, form: form };
	};

	// A form field as the POS draws it: div > label + control + span.error. Insert it before the actions of the form.
	ui.field = function ( text, control ) {
		var wrap = node( 'div' );
		wrap.appendChild( node( 'label', '', text ) );
		wrap.appendChild( control );
		wrap.appendChild( node( 'span', 'error' ) );
		return wrap;
	};

	// An on/off option: its text and the POS switch (the checkbox is the one given, so callers read .checked).
	ui.checkField = function ( text, box ) {
		var wrap = node( 'div', 'vfwoo-toggle' );
		wrap.appendChild( node( 'label', '', text ) );
		var field = node( 'div', 'pos-form-switch-field' );
		var label = node( 'label', 'pos-switch' );
		label.appendChild( box );
		label.appendChild( node( 'span', 'pos-slider' ) );
		field.appendChild( label );
		wrap.appendChild( field );
		return wrap;
	};

	// Two fields side by side: the POS "address-group".
	ui.pair = function () {
		return node( 'div', 'address-group' );
	};

	// A section title inside the form.
	ui.heading = function ( text ) {
		return node( 'h3', 'address-fields', text );
	};

	ui.note = function ( text ) {
		return node( 'p', 'vfwoo-note', text );
	};

	// The POS "no result" box: also for messages and errors. In the detail column of Pedidos the POS uses
	// `no-order-result` for "nothing selected": pass it as className.
	ui.noResult = function ( text, className ) {
		return node( 'div', className || 'no-result', text );
	};

	// The POS preloader, shown over the whole screen. show() and hide() can be nested.
	ui.loader = function ( text ) {
		var element = node( 'div', 'wkpos-loader-wrapper wkpos-loader-fullscreen' );
		element.appendChild( node( 'span', 'wkpos-loader' ) );
		element.appendChild( node( 'span', 'wkpos-loader-text', text ) );
		var count = 0;
		return {
			show: function () {
				count++;
				if ( ! element.parentNode ) {
					document.body.appendChild( element );
				}
			},
			hide: function () {
				count = Math.max( 0, count - 1 );
				if ( ! count ) {
					element.remove();
				}
			}
		};
	};

	// The POS switch (pos-form-switch-field > pos-switch > input + pos-slider).
	// opts: { checked, disabled, title, label, onChange( wanted, done ) }; done( false ) puts the switch back,
	// done( true, value ) sets it to value.
	ui.switchField = function ( opts ) {
		var field = node( 'div', 'pos-form-switch-field' );
		var label = node( 'label', 'pos-switch' );
		var box = ui.input( 'checkbox' );
		box.checked = !! opts.checked;
		if ( opts.label ) {
			box.setAttribute( 'aria-label', opts.label );
		}
		if ( opts.disabled ) {
			box.disabled = true;
			label.title = opts.title || '';
		}
		label.appendChild( box );
		label.appendChild( node( 'span', 'pos-slider' ) );
		field.appendChild( label );
		box.addEventListener( 'click', function ( event ) {
			event.stopPropagation();
		} );
		box.addEventListener( 'change', function () {
			var wanted = box.checked;
			box.disabled = true;
			opts.onChange( wanted, function ( ok, value ) {
				box.disabled = false;
				if ( ok === false ) {
					box.checked = ! wanted;
				} else if ( value !== undefined ) {
					box.checked = !! value;
				}
			} );
		} );
		return field;
	};

	// Page numbers: first, last and a few around the current page; a gap is 0.
	ui.pageNumbers = function ( page, pages ) {
		var wanted = {};
		[ 1, pages, page - 1, page, page + 1 ].forEach( function ( number ) {
			wanted[ number ] = true;
		} );
		if ( page <= 3 ) {
			wanted[ 2 ] = wanted[ 3 ] = wanted[ 4 ] = true;
		}
		if ( page >= pages - 2 ) {
			wanted[ pages - 1 ] = wanted[ pages - 2 ] = wanted[ pages - 3 ] = true;
		}
		var numbers = Object.keys( wanted ).map( Number ).filter( function ( number ) {
			return number >= 1 && number <= pages;
		} ).sort( function ( a, b ) {
			return a - b;
		} );
		var out = [];
		numbers.forEach( function ( number, index ) {
			if ( index && number - numbers[ index - 1 ] > 1 ) {
				out.push( 0 );
			}
			out.push( number );
		} );
		return out;
	};

	// POS pagination (wkpos-paginition-wrapper), plus first and last page.
	// data: { page, pages, total }; texts: { summary, first, prev, next, last }; onGo( page ).
	ui.pager = function ( wrapper, data, texts, onGo ) {
		wrapper.textContent = '';
		if ( data.pages < 1 ) {
			return;
		}
		var total = node( 'div', 'wkpos-total-items' );
		total.appendChild( node( 'span', '', texts.summary.replace( '%1$s', data.page ).replace( '%2$s', data.pages ).replace( '%3$s', data.total ) ) );
		var pagination = node( 'div', 'wkpos-paginition' );
		var ul = node( 'ul', 'pos-pagination-widget' );

		function item( text, page, className, label ) {
			var li = node( 'li', className || '' );
			var a = node( 'a', '', text );
			if ( label ) {
				a.setAttribute( 'aria-label', label );
			}
			if ( page !== null ) {
				a.addEventListener( 'click', function () {
					onGo( page );
				} );
			} else {
				li.className += ' disabled';
			}
			li.appendChild( a );
			ul.appendChild( li );
		}

		var first = data.page <= 1;
		var last = data.page >= data.pages;
		item( '«', first ? null : 1, 'previous', texts.first );
		item( texts.prev, first ? null : data.page - 1, 'previous' );
		ui.pageNumbers( data.page, data.pages ).forEach( function ( number ) {
			if ( number === 0 ) {
				item( '…', null );
			} else {
				item( String( number ), number === data.page ? null : number, number === data.page ? 'active' : '' );
			}
		} );
		item( texts.next, last ? null : data.page + 1, 'next' );
		item( '»', last ? null : data.pages, 'next', texts.last );

		pagination.appendChild( ul );
		wrapper.appendChild( total );
		wrapper.appendChild( pagination );
	};

	// One line of a POS list (wkwcpos-order-data): cells = [ [ column name, node|null ], … ]; each span gets
	// the class vfwoo-col-{name} so the width of the column can be set.
	ui.line = function ( kind, cells ) {
		var data = node( 'div', 'wkwcpos-order-data' );
		cells.forEach( function ( cell ) {
			var span = node( 'span', 'vfwoo-col-' + cell[ 0 ] );
			if ( cell[ 1 ] ) {
				span.appendChild( cell[ 1 ] );
			}
			data.appendChild( span );
		} );
		var wrap = node( 'div', kind );
		wrap.appendChild( data );
		return wrap;
	};

	// The sticky header of a POS list.
	ui.listHeader = function ( cells ) {
		var header = node( 'div', 'wkwcpos-order-list-header' );
		header.appendChild( ui.line( '', cells ).firstChild );
		return header;
	};

	// The Orders-style detail panel (pos-order-short + pos-sale-summary), for the right column of postabContainer.
	// The title block stays on top, `body` scrolls and `footer` (buttons) stays at the bottom, always in view.
	ui.detail = function () {
		var wrap = node( 'div', 'vfwoo-detail' );
		wrap.id = 'order-main-summary'; // Pedidos: the POS gives this id the surface, the radius, the margin and the height
		var short = node( 'div', 'pos-order-short' );
		var section = node( 'div', 'pos-order-section' );
		var titleWrap = node( 'div' );
		var title = node( 'h3' );
		titleWrap.appendChild( title );
		var extra = node( 'div', 'pos-order-extra-wrapper' );
		section.appendChild( titleWrap );
		section.appendChild( extra );
		short.appendChild( section );
		var body = node( 'div', 'pos-sale-summary' );
		var footer = node( 'div', 'vfwoo-detail-actions' );
		wrap.appendChild( short );
		wrap.appendChild( body );
		wrap.appendChild( footer );
		return {
			element: wrap,
			body: body,
			footer: footer,
			clear: function () {
				body.textContent = '';
				footer.textContent = '';
			},
			setTitle: function ( text ) {
				title.textContent = text || '';
			},
			setExtra: function ( lines ) {
				extra.textContent = '';
				( lines || [] ).forEach( function ( line ) {
					extra.appendChild( node( 'h5', '', line ) );
				} );
			}
		};
	};

	// The Informe screen of the POS (reports): div.pos-tabContent.wkreport-pos-report-container > div.wkreport-report-wrapper,
	// with a filter block (title, fields, "Filtrar" button) and a report container (cards, table). Returns
	// { fields, footer, container }: put ui.reportField() in fields, ui.reportButton() in footer and cards and
	// tables in container.
	ui.report = function ( root, title ) {
		var wrapper = node( 'div', 'wkreport-report-wrapper' );
		var filterContainer = node( 'div', 'wkreport-filter-container' );
		var filterWrapper = node( 'div', 'wkreport-filter-wrapper' );
		var body = node( 'div', 'wkreport-filter-body' );
		var top = node( 'div', 'wkreport-filter-top' );
		top.appendChild( node( 'h3', '', title ) );
		var fields = node( 'div', 'wkreport-all-filter-wrapper' );
		body.appendChild( top );
		body.appendChild( fields );
		var footer = node( 'div', 'wkreport-filter-footer' );
		filterWrapper.appendChild( body );
		filterWrapper.appendChild( footer );
		filterContainer.appendChild( filterWrapper );
		var container = node( 'div', 'wkreport-report-container' );
		wrapper.appendChild( filterContainer );
		wrapper.appendChild( container );
		root.className += ' pos-tabContent wkreport-pos-report-container vfwoo-screen-page';
		root.appendChild( wrapper );
		return { fields: fields, footer: footer, container: container };
	};

	// A filter of the report: its label above the control (the POS "Filter By").
	ui.reportField = function ( text, control ) {
		var wrap = node( 'div', 'wkreport-filter-by wkreport-width-250' );
		wrap.appendChild( node( 'p', '', text ) );
		wrap.appendChild( control );
		return wrap;
	};

	ui.reportButton = function ( text ) {
		var button = node( 'button', 'wkreport-filter-btn', text );
		button.type = 'button';
		return button;
	};

	// Total cards: items = [ [ title, value ], … ].
	ui.reportCards = function ( items ) {
		var cards = node( 'div', 'wkreport-card-report-container' );
		items.forEach( function ( item ) {
			var card = node( 'div', 'wkreport-card-wrapper' );
			var title = node( 'div', 'wkreport-card-title' );
			title.appendChild( node( 'span', '', item[ 0 ] ) );
			var value = node( 'div' );
			value.appendChild( node( 'h5', '', String( item[ 1 ] ) ) );
			card.appendChild( title );
			card.appendChild( value );
			cards.appendChild( card );
		} );
		return cards;
	};

	// A report table (wkreport-report-table). Returns { element, body }: add rows (tr > td) to body.
	ui.reportTable = function ( title, headers ) {
		var box = node( 'div', 'wkreport-table-report-container' );
		var grid = node( 'div', 'wkreport-grid-report-wrapper' );
		var view = node( 'div', 'wkreport-table-view-report-wrapper' );
		var head = node( 'div', 'wkreport-table-view-report-header' );
		head.appendChild( node( 'h6', 'wkreport-title-text', title ) );
		var tableWrap = node( 'div', 'wkreport-report-table-wrapper' );
		var table = node( 'table', 'wkreport-report-table' );
		var headRow = node( 'tr' );
		headers.forEach( function ( text ) {
			var th = node( 'th' );
			var cell = node( 'div', 'wkreport-table-head' );
			cell.appendChild( node( 'div', 'wkreport-table-head-title', text ) );
			th.appendChild( cell );
			headRow.appendChild( th );
		} );
		table.appendChild( node( 'thead' ) ).appendChild( headRow );
		var body = node( 'tbody' );
		table.appendChild( body );
		tableWrap.appendChild( table );
		view.appendChild( head );
		view.appendChild( tableWrap );
		grid.appendChild( view );
		box.appendChild( grid );
		return { element: box, body: body };
	};

	// The page frame of a screen, with the same elements as Pedidos: the root is div.pos-tabContent.pos-order.orders
	// with a tab bar and div.postabContainer.orders (the POS puts the list card and the detail column in two columns).
	// Returns { tabs, list, container }; give container a second child (a ui.detail() element) for the detail column,
	// or pass singleColumn for a screen with only the list card.
	ui.screen = function ( root, title, singleColumn ) {
		var tabsWrap = node( 'div' );
		var tabs = node( 'nav', 'pos-tabs' );
		var tabsList = node( 'ul' );
		var tabItem = node( 'li' );
		tabItem.appendChild( node( 'a', 'wkwcpos-tabs pos-active', title ) );
		tabsList.appendChild( tabItem );
		tabs.appendChild( tabsList );
		tabsWrap.appendChild( tabs );
		var container = node( 'div', 'postabContainer orders' );
		if ( ! singleColumn ) {
			container.id = 'pos-order-tab'; // the POS lays out its two columns (2.8fr 1.2fr) with this id
		}
		var list = node( 'div', 'pos-order-list' );
		container.appendChild( list );
		root.id = 'pos-order'; // as in Pedidos: div#pos-order.pos-tabContent.pos-order.orders
		root.className += ' pos-tabContent pos-order orders vfwoo-screen-page';
		root.appendChild( tabsWrap );
		root.appendChild( container );
		return { tabs: tabs, list: list, container: container };
	};

	window.vfwooBridge = {
		hooks: hooks,
		get config() {
			return window.vfwooWebkulBridge;
		},
		escapeHtml: escapeHtml,
		escapeAttribute: escapeAttribute,
		node: node,
		posUrl: posUrl,
		posPost: posPost,
		posForm: posForm,
		ui: ui,
		moduleConfig: moduleConfig,
		addScreen: addScreen
	};
}() );
