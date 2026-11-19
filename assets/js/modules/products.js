/**
 * Module "Productos": a light product manager inside the POS (list, filters, shop and POS switches).
 * Plain DOM, drawn inside Webkul's layout through window.vfwooBridge.addScreen.
 *
 * It reuses the POS markup and classes (the same ones the Orders screen uses: pos-tabContent, pos-order,
 * pos-tabs, pos-order-list, pos-order-search, wkwcpos-order-data rows, pos-pagination-widget, pos-modal-*,
 * pos-form-field, pos-switch, wkpos-loader), so the POS styles apply and no look is invented here.
 */
( function () {
	'use strict';

	var core = window.vfwooBridge;
	if ( ! core ) {
		return;
	}
	var config = core.moduleConfig( 'products' );
	if ( ! config.enabled || ! config.labels ) {
		return;
	}

	var labels = config.labels;
	var node = core.node;

	function build( root, component ) {
		var state = { search: '', shop: '', pos: '', stock: '', category: '', incomplete: '', orderby: '', sale: '', brand: '', page: 1 };
		var selected = {};
		var timer = null;
		var requestId = 0;
		var rowCount = 0;

		// ---- Layout: the same wrappers as the POS Orders screen ----
		var order = node( 'div', 'pos-order' );
		var orders = node( 'div', 'orders' );
		var tabs = node( 'nav', 'pos-tabs' );
		var tabsList = node( 'ul' );
		var tabItem = node( 'li' );
		tabItem.appendChild( node( 'a', 'wkwcpos-tabs pos-active', labels.title ) );
		tabsList.appendChild( tabItem );
		tabs.appendChild( tabsList );
		var container = node( 'div', 'postabContainer' );
		var list = node( 'div', 'pos-order-list' );

		// Search and buttons.
		var top = node( 'div', 'vfwoo-products-top' );
		var searchInput = node( 'input' );
		searchInput.type = 'search';
		searchInput.placeholder = labels.search;
		searchInput.autocomplete = 'off';
		searchInput.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				state.search = searchInput.value.trim();
				state.page = 1;
				load();
			}, 350 );
		} );
		top.appendChild( searchInput );
		if ( config.canCreate ) {
			var quick = node( 'button', 'primary', labels.newQuick );
			quick.type = 'button';
			quick.addEventListener( 'click', function () {
				openCreator( '' );
			} );
			top.appendChild( quick );
		}
		var wp = node( 'button', '', labels.allInWp );
		wp.type = 'button';
		wp.addEventListener( 'click', function () {
			window.open( config.allUrl, '_blank', 'noopener' );
		} );
		top.appendChild( wp );

		// Search and buttons sit in the tab bar, next to the title.
		tabs.appendChild( top );

		// Filters and order.
		var toolbar = node( 'div', 'vfwoo-products-toolbar' );
		toolbar.appendChild( select( 'shop', [ [ '', labels.shopAll ], [ 'yes', labels.shopYes ], [ 'no', labels.shopNo ] ] ) );
		toolbar.appendChild( select( 'pos', [ [ '', labels.posAll ], [ 'yes', labels.posYes ], [ 'no', labels.posNo ] ] ) );
		toolbar.appendChild( select( 'stock', [ [ '', labels.stockAll ], [ 'in', labels.stockIn ], [ 'out', labels.stockOut ] ] ) );
		var categories = [ [ '', labels.categoryAll ] ];
		( config.categories || [] ).forEach( function ( category ) {
			categories.push( [ String( category.id ), category.name ] );
		} );
		toolbar.appendChild( select( 'category', categories ) );
		if ( config.brands && config.brands.length ) {
			var brands = [ [ '', labels.brandAll ] ];
			config.brands.forEach( function ( brand ) {
				brands.push( [ String( brand.id ), brand.name ] );
			} );
			toolbar.appendChild( select( 'brand', brands ) );
		}
		toolbar.appendChild( select( 'sale', [ [ '', labels.saleAll ], [ 'yes', labels.saleYes ], [ 'no', labels.saleNo ] ] ) );
		toolbar.appendChild( select( 'incomplete', [ [ '', labels.incAll ], [ 'photo', labels.incPhoto ], [ 'price', labels.incPrice ], [ 'sku', labels.incSku ], [ 'gtin', labels.incGtin ], [ 'duplicate', labels.incDuplicate ] ] ) );
		toolbar.appendChild( select( 'orderby', [ [ '', labels.sortName ], [ 'name_desc', labels.sortNameDesc ], [ 'date_desc', labels.sortNewest ], [ 'sales_desc', labels.sortSold ], [ 'price_asc', labels.sortPriceAsc ], [ 'price_desc', labels.sortPriceDesc ], [ 'stock_asc', labels.sortStockAsc ], [ 'stock_desc', labels.sortStockDesc ] ] ) );

		var status = node( 'div', 'no-result' );
		status.style.display = 'none';
		var bulkBar = node( 'div', 'vfwoo-products-bulk' );
		bulkBar.style.display = 'none';
		var pagerTop = node( 'div', 'wkpos-paginition-wrapper' );
		var pagerBottom = node( 'div', 'wkpos-paginition-wrapper' );
		var rows = node( 'div', 'dropdownlist-order vfwoo-products-rows' );

		list.appendChild( toolbar );
		list.appendChild( status );
		list.appendChild( bulkBar );
		list.appendChild( pagerTop );
		list.appendChild( rows );
		list.appendChild( pagerBottom );
		container.appendChild( list );
		orders.appendChild( tabs );
		orders.appendChild( container );
		order.appendChild( orders );
		root.className += ' pos-tabContent vfwoo-products-page';
		root.appendChild( order );

		// The POS preloader, as the POS draws it.
		var loader = node( 'div', 'wkpos-loader-wrapper wkpos-loader-fullscreen' );
		loader.appendChild( node( 'span', 'wkpos-loader' ) );
		loader.appendChild( node( 'span', 'wkpos-loader-text', labels.loading ) );

		function setLoading( on ) {
			if ( on ) {
				if ( ! loader.parentNode ) {
					document.body.appendChild( loader );
				}
			} else {
				loader.remove();
			}
		}

		function select( key, options ) {
			var element = node( 'select', 'vfwoo-products-select' );
			options.forEach( function ( option ) {
				var item = node( 'option', '', option[ 1 ] );
				item.value = option[ 0 ];
				element.appendChild( item );
			} );
			element.addEventListener( 'change', function () {
				state[ key ] = element.value;
				state.page = 1;
				load();
			} );
			return element;
		}

		// A message in the POS "no result" box (also used for errors); empty text hides it.
		function setStatus( text ) {
			status.textContent = text || '';
			status.style.display = text ? '' : 'none';
		}

		function load( keepStatus ) {
			var current = ++requestId;
			setLoading( true );
			if ( ! keepStatus ) {
				setStatus( '' );
			}
			core.posPost( config.listUrl, {
				search: state.search,
				shop: state.shop,
				pos: state.pos,
				stock: state.stock,
				category: state.category,
				incomplete: state.incomplete,
				orderby: state.orderby,
				sale: state.sale,
				brand: state.brand,
				page: state.page
			} )
				.then( function ( result ) {
					if ( current !== requestId ) {
						return;
					}
					setLoading( false );
					if ( ! result || ! result.success ) {
						setStatus( ( result && result.message ) || labels.error );
						return;
					}
					if ( ! keepStatus ) {
						setStatus( '' );
					}
					renderTable( result.data );
				} )
				.catch( function () {
					if ( current === requestId ) {
						setLoading( false );
						setStatus( labels.error );
					}
				} );
		}

		// Page numbers: first, last and a few around the current page, with gaps marked 0.
		function pageNumbers( page, pages ) {
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
		}

		// Same markup as the POS pagination (wkpos-paginition-wrapper), plus first and last page.
		function renderPager( wrapper, data ) {
			wrapper.textContent = '';
			if ( data.pages < 1 ) {
				return;
			}
			var total = node( 'div', 'wkpos-total-items' );
			total.appendChild( node( 'span', '', labels.pageOf.replace( '%1$s', data.page ).replace( '%2$s', data.pages ).replace( '%3$s', data.total ) ) );
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
						state.page = page;
						load();
						rows.scrollTop = 0;
					} );
				} else {
					li.className += ' disabled';
				}
				li.appendChild( a );
				ul.appendChild( li );
			}

			var first = data.page <= 1;
			var last = data.page >= data.pages;
			item( '«', first ? null : 1, 'previous', labels.first );
			item( labels.prev, first ? null : data.page - 1, 'previous' );
			pageNumbers( data.page, data.pages ).forEach( function ( number ) {
				if ( number === 0 ) {
					item( '…', null );
				} else {
					item( String( number ), number === data.page ? null : number, number === data.page ? 'active' : '' );
				}
			} );
			item( labels.next, last ? null : data.page + 1, 'next' );
			item( '»', last ? null : data.pages, 'next', labels.last );

			pagination.appendChild( ul );
			wrapper.appendChild( total );
			wrapper.appendChild( pagination );
		}

		// One line of the list: the POS row markup, with one span per column.
		function line( kind, cells ) {
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
		}

		function renderTable( data ) {
			rows.textContent = '';
			selected = {};
			rowCount = 0;
			renderBulk();
			renderPager( pagerTop, data );
			renderPager( pagerBottom, data );

			if ( ! data.items.length ) {
				var empty = node( 'div', 'no-result', labels.empty );
				rows.appendChild( empty );
				if ( /^[0-9A-Za-z-]{6,}$/.test( state.search ) && config.canCreate ) {
					var offer = node( 'button', 'primary', labels.createWithCode.replace( '%s', state.search ) );
					offer.type = 'button';
					offer.addEventListener( 'click', function () {
						openCreator( state.search );
					} );
					empty.appendChild( node( 'br' ) );
					empty.appendChild( offer );
				}
				return;
			}

			var all = input( 'checkbox' );
			all.title = labels.selectPage;
			all.setAttribute( 'aria-label', labels.selectPage );
			all.addEventListener( 'change', function () {
				rows.querySelectorAll( 'input[data-id]' ).forEach( function ( box ) {
					box.checked = all.checked;
					selected[ box.getAttribute( 'data-id' ) ] = all.checked;
				} );
				renderBulk();
			} );
			var header = node( 'div', 'wkwcpos-order-list-header' );
			header.appendChild( line( '', [
				[ 'check', all ],
				[ 'photo', null ],
				[ 'name', document.createTextNode( labels.product ) ],
				[ 'price', document.createTextNode( labels.price ) ],
				[ 'stock', document.createTextNode( labels.stock ) ],
				[ 'switch', document.createTextNode( labels.inShop ) ],
				[ 'switch', document.createTextNode( labels.inPos ) ],
				[ 'actions', null ]
			] ).firstChild );
			rows.appendChild( header );
			data.items.forEach( function ( item ) {
				rows.appendChild( buildRow( item ) );
			} );
		}

		function stockText( item ) {
			if ( item.stockQty !== null ) {
				return String( item.stockQty );
			}
			if ( item.stockStatus === 'outofstock' ) {
				return labels.outOfStock;
			}
			if ( item.stockStatus === 'onbackorder' ) {
				return labels.backorder;
			}
			return labels.inStock;
		}

		function buildRow( item ) {
			var box = input( 'checkbox' );
			box.setAttribute( 'data-id', item.id );
			box.setAttribute( 'aria-label', item.name );
			box.addEventListener( 'change', function () {
				selected[ item.id ] = box.checked;
				renderBulk();
			} );

			var photo = null;
			if ( item.image ) {
				photo = node( 'img' );
				photo.src = item.image;
				photo.alt = '';
				photo.loading = 'lazy';
			}

			var name = node( 'div' );
			name.appendChild( node( 'strong', '', item.name ) );
			var meta = [];
			if ( item.sku ) {
				meta.push( item.sku );
			}
			if ( item.gtin ) {
				meta.push( item.gtin );
			}
			if ( item.type === 'variable' ) {
				meta.push( labels.variable + ' (' + item.variations + ')' );
			}
			if ( item.status !== 'publish' ) {
				meta.push( labels[ item.status ] || item.status );
			}
			if ( meta.length ) {
				name.appendChild( node( 'small', '', meta.join( ' · ' ) ) );
			}

			var edit = node( 'button', '', labels.edit );
			edit.type = 'button';
			edit.addEventListener( 'click', function () {
				openEditor( item );
			} );

			var row = line( rowCount++ % 2 ? 'wkwcpos-list-item-even' : 'wkwcpos-list-item-odd', [
				[ 'check', box ],
				[ 'photo', photo ],
				[ 'name', name ],
				[ 'price', document.createTextNode( item.price ) ],
				[ 'stock', document.createTextNode( stockText( item ) ) ],
				[ 'switch', switchField( item, 'shop', item.shop, item.status !== 'publish' ? labels.notPublished : '' ) ],
				[ 'switch', switchField( item, 'pos', item.pos, '' ) ],
				[ 'actions', edit ]
			] );
			return row;
		}

		// The POS switch (pos-form-switch-field > pos-switch > input + pos-slider).
		function switchField( item, target, value, disabledReason ) {
			var field = node( 'div', 'pos-form-switch-field' );
			var label = node( 'label', 'pos-switch' );
			var box = input( 'checkbox' );
			box.checked = !! value;
			box.setAttribute( 'aria-label', ( target === 'shop' ? labels.inShop : labels.inPos ) + ': ' + item.name );
			if ( disabledReason ) {
				box.disabled = true;
				label.title = disabledReason;
			}
			label.appendChild( box );
			label.appendChild( node( 'span', 'pos-slider' ) );
			field.appendChild( label );

			box.addEventListener( 'change', function () {
				var wanted = box.checked;
				box.disabled = true;
				setLoading( true );
				core.posPost( config.visibilityUrl, { product_id: item.id, target: target, value: wanted } )
					.then( function ( result ) {
						setLoading( false );
						box.disabled = false;
						if ( ! result || ! result.success ) {
							box.checked = ! wanted;
							setStatus( ( result && result.message ) || labels.saveError );
							return;
						}
						box.checked = !! result.data[ target ];
						setStatus( target === 'pos' ? labels.resync : '' );
					} )
					.catch( function () {
						setLoading( false );
						box.checked = ! wanted;
						box.disabled = false;
						setStatus( labels.saveError );
					} );
			} );
			return field;
		}

		// ---- A popup, with the POS modal markup. Returns { body, footer, close } ----
		function makeBox( title ) {
			var overlay = node( 'div', 'pos-modal-overlay' );
			var box = node( 'div', 'pos-modal-content vfwoo-products-modal' );
			var head = node( 'div', 'pos-modal-header' );
			head.appendChild( node( 'span', 'pos-modal-heading', title ) );
			var x = node( 'button', 'pos-modal-close-btn', '×' );
			x.type = 'button';
			x.setAttribute( 'aria-label', labels.close );
			head.appendChild( x );
			var body = node( 'div', 'pos-modal-body' );
			var footer = node( 'div', 'pos-modal-footer' );
			box.appendChild( head );
			box.appendChild( body );
			box.appendChild( footer );
			overlay.appendChild( box );
			document.body.appendChild( overlay );

			function close() {
				document.removeEventListener( 'keydown', onKey );
				overlay.remove();
			}
			function onKey( event ) {
				if ( event.key === 'Escape' ) {
					close();
				}
			}
			document.addEventListener( 'keydown', onKey );
			x.addEventListener( 'click', close );
			overlay.addEventListener( 'mousedown', function ( event ) {
				if ( event.target === overlay ) {
					close();
				}
			} );
			return { body: body, footer: footer, close: close };
		}

		function input( type, value ) {
			var element = node( 'input' );
			element.type = type;
			element.value = value === null || value === undefined ? '' : value;
			return element;
		}

		// pos-form-field: a label above its control.
		function field( text, element ) {
			var wrap = node( 'div', 'pos-form-field' );
			wrap.appendChild( node( 'label', '', text ) );
			wrap.appendChild( element );
			return wrap;
		}

		// pos-form-checkbox-field: a checkbox with its label.
		function checkField( text, box ) {
			var wrap = node( 'div', 'pos-form-checkbox-field' );
			wrap.appendChild( box );
			wrap.appendChild( node( 'label', '', text ) );
			return wrap;
		}

		function optionsSelect( options, value ) {
			var element = node( 'select' );
			options.forEach( function ( option ) {
				var item = node( 'option', '', option[ 1 ] );
				item.value = option[ 0 ];
				element.appendChild( item );
			} );
			element.value = value;
			return element;
		}

		function grid() {
			return node( 'div', 'vfwoo-form-grid' );
		}

		// ---- Edit popup: title, status, prices, SKU, barcode and stock of a product and of its existing variations ----
		function openEditor( item ) {
			var popup = makeBox( item.name );
			popup.body.appendChild( node( 'p', '', labels.loading ) );

			core.posPost( config.getUrl, { product_id: item.id } )
				.then( function ( result ) {
					popup.body.textContent = '';
					if ( ! result || ! result.success ) {
						popup.body.appendChild( node( 'div', 'no-result', ( result && result.message ) || labels.error ) );
						return;
					}
					buildForm( popup, result.data );
				} )
				.catch( function () {
					popup.body.textContent = '';
					popup.body.appendChild( node( 'div', 'no-result', labels.error ) );
				} );
		}

		// Fields shared by a product and a variation. Returns { element, collect() }; collect() keeps what is not shown.
		function stockFields( data, showPrices, editableStock ) {
			var wrap = grid();
			var inputs = {};
			if ( showPrices ) {
				inputs.regularPrice = input( 'text', data.regularPrice );
				inputs.regularPrice.inputMode = 'decimal';
				inputs.salePrice = input( 'text', data.salePrice );
				inputs.salePrice.inputMode = 'decimal';
				inputs.saleFrom = input( 'date', data.saleFrom );
				inputs.saleTo = input( 'date', data.saleTo );
				wrap.appendChild( field( labels.regularPrice, inputs.regularPrice ) );
				wrap.appendChild( field( labels.salePrice, inputs.salePrice ) );
				wrap.appendChild( field( labels.saleFrom, inputs.saleFrom ) );
				wrap.appendChild( field( labels.saleTo, inputs.saleTo ) );
			}
			inputs.sku = input( 'text', data.sku );
			inputs.gtin = input( 'text', data.gtin );
			wrap.appendChild( field( labels.sku, inputs.sku ) );
			wrap.appendChild( field( labels.gtin, inputs.gtin ) );

			inputs.manageStock = input( 'checkbox' );
			inputs.manageStock.checked = !! data.manageStock;
			inputs.stockQty = input( 'number', data.stockQty );
			inputs.stockQty.step = '1';
			inputs.stockStatus = optionsSelect( [ [ 'instock', labels.inStock ], [ 'outofstock', labels.outOfStock ], [ 'onbackorder', labels.backorder ] ], data.stockStatus );

			wrap.appendChild( checkField( labels.manageStock, inputs.manageStock ) );
			var qty = field( labels.quantity, inputs.stockQty );
			var statusField = field( labels.stockStatus, inputs.stockStatus );
			wrap.appendChild( qty );
			wrap.appendChild( statusField );

			function refreshStock() {
				var managed = inputs.manageStock.checked;
				qty.style.display = managed ? '' : 'none';
				statusField.style.display = managed ? 'none' : '';
			}
			inputs.manageStock.addEventListener( 'change', refreshStock );
			refreshStock();
			if ( ! editableStock ) {
				[ inputs.manageStock, inputs.stockQty, inputs.stockStatus ].forEach( function ( element ) {
					element.disabled = true;
				} );
			}

			return {
				element: wrap,
				collect: function () {
					var out = { id: data.id };
					Object.keys( inputs ).forEach( function ( key ) {
						out[ key ] = key === 'manageStock' ? inputs[ key ].checked : inputs[ key ].value;
					} );
					[ 'regularPrice', 'salePrice', 'saleFrom', 'saleTo' ].forEach( function ( key ) {
						if ( ! showPrices ) {
							out[ key ] = data[ key ];
						}
					} );
					return out;
				}
			};
		}

		// Save and cancel buttons in the modal footer, as the POS does.
		function footerButtons( popup, applyText, onApply, openUrl ) {
			if ( openUrl ) {
				var open = node( 'button', 'pos-btn-cancel', labels.openWp );
				open.type = 'button';
				open.addEventListener( 'click', function () {
					window.open( openUrl, '_blank', 'noopener' );
				} );
				popup.footer.appendChild( open );
			}
			var cancel = node( 'button', 'pos-btn-cancel', labels.cancel );
			cancel.type = 'button';
			cancel.addEventListener( 'click', popup.close );
			var apply = node( 'button', 'pos-btn-apply', applyText );
			apply.type = 'button';
			popup.footer.appendChild( cancel );
			popup.footer.appendChild( apply );
			apply.addEventListener( 'click', function () {
				apply.disabled = true;
				apply.textContent = '';
				apply.appendChild( node( 'span', 'pos-btn-spinner' ) );
				apply.appendChild( document.createTextNode( ' ' + labels.saving ) );
				onApply( function () {
					apply.disabled = false;
					apply.textContent = applyText;
				} );
			} );
		}

		function buildForm( popup, data ) {
			var content = popup.body;
			if ( ! data.editable ) {
				content.appendChild( node( 'p', '', labels.unsupported ) );
				footerButtons( popup, labels.close, popup.close, data.editUrl );
				return;
			}

			var head = grid();
			var nameInput = input( 'text', data.name );
			var statusSelect = optionsSelect( [ [ 'publish', labels.published ], [ 'draft', labels.draft ], [ 'pending', labels.pending ], [ 'private', labels.private ] ], data.status );
			head.appendChild( field( labels.name, nameInput ) );
			head.appendChild( field( labels.statusLabel, statusSelect ) );
			content.appendChild( head );

			var isVariable = data.type === 'variable';
			var main = stockFields( data, ! isVariable, data.stockEditable );
			content.appendChild( main.element );
			if ( isVariable ) {
				content.appendChild( node( 'p', 'vfwoo-note', labels.priceNote ) );
			}
			if ( ! data.stockEditable ) {
				content.appendChild( node( 'p', 'vfwoo-note', labels.stockLocked ) );
			}

			var variations = [];
			if ( isVariable && data.variations.length ) {
				content.appendChild( node( 'div', 'pos-modal-field-label', labels.variations ) );
				content.appendChild( node( 'p', 'vfwoo-note', labels.variationsNote ) );
				data.variations.forEach( function ( variation ) {
					var card = node( 'div', 'vfwoo-variation' );
					card.appendChild( node( 'strong', '', variation.label || '#' + variation.id ) );
					var fields = stockFields( variation, true, data.stockEditable );
					card.appendChild( fields.element );
					content.appendChild( card );
					variations.push( fields );
				} );
			}

			var feedback = node( 'div', 'no-result' );
			feedback.style.display = 'none';
			content.appendChild( feedback );

			footerButtons( popup, labels.save, function ( done ) {
				var fields = main.collect();
				fields.name = nameInput.value;
				fields.status = statusSelect.value;
				fields.variations = variations.map( function ( entry ) {
					return entry.collect();
				} );
				feedback.style.display = 'none';
				core.posPost( config.updateUrl, { product_id: data.id, fields: fields } )
					.then( function ( result ) {
						if ( ! result || ! result.success ) {
							feedback.textContent = ( result && result.message ) || labels.saveError;
							feedback.style.display = '';
							done();
							return;
						}
						popup.close();
						setStatus( labels.saved + ' ' + labels.resync );
						load( true );
					} )
					.catch( function () {
						feedback.textContent = labels.saveError;
						feedback.style.display = '';
						done();
					} );
			}, data.editUrl );
		}

		// ---- Bulk actions on the selected rows ----
		function selectedIds() {
			return Object.keys( selected ).filter( function ( id ) {
				return selected[ id ];
			} ).map( Number );
		}

		function renderBulk() {
			var ids = selectedIds();
			bulkBar.textContent = '';
			bulkBar.style.display = ids.length ? 'flex' : 'none';
			if ( ! ids.length ) {
				return;
			}
			bulkBar.appendChild( node( 'strong', '', labels.selected.replace( '%s', ids.length ) ) );
			[
				[ labels.bulkPosOn, 'pos', true ],
				[ labels.bulkPosOff, 'pos', false ],
				[ labels.bulkShopOn, 'shop', true ],
				[ labels.bulkShopOff, 'shop', false ]
			].forEach( function ( action ) {
				var button = node( 'button', '', action[ 0 ] );
				button.type = 'button';
				button.addEventListener( 'click', function () {
					bulkBar.querySelectorAll( 'button' ).forEach( function ( other ) {
						other.disabled = true;
					} );
					setLoading( true );
					core.posPost( config.bulkUrl, { product_ids: ids, target: action[ 1 ], value: action[ 2 ] } )
						.then( function ( result ) {
							setLoading( false );
							if ( ! result || ! result.success ) {
								setStatus( ( result && result.message ) || labels.saveError );
								renderBulk();
								return;
							}
							var text = labels.bulkDone.replace( '%1$s', result.data.done ).replace( '%2$s', result.data.failed.length );
							setStatus( text + ( action[ 1 ] === 'pos' ? ' ' + labels.resync : '' ) );
							load( true );
						} )
						.catch( function () {
							setLoading( false );
							setStatus( labels.saveError );
							renderBulk();
						} );
				} );
				bulkBar.appendChild( button );
			} );
		}

		// ---- Quick new product ----
		function openCreator( code ) {
			var popup = makeBox( labels.newTitle );
			popup.body.appendChild( node( 'p', 'vfwoo-note', labels.newNote ) );
			var wrap = grid();
			var name = input( 'text', '' );
			var price = input( 'text', '' );
			price.inputMode = 'decimal';
			var gtin = input( 'text', code );
			var sku = input( 'text', '' );
			var qty = input( 'number', '' );
			qty.step = '1';
			var shop = input( 'checkbox' );
			wrap.appendChild( field( labels.name, name ) );
			wrap.appendChild( field( labels.regularPrice, price ) );
			wrap.appendChild( field( labels.quantity, qty ) );
			wrap.appendChild( field( labels.gtin, gtin ) );
			wrap.appendChild( field( labels.sku, sku ) );
			wrap.appendChild( checkField( labels.showInShop, shop ) );
			popup.body.appendChild( wrap );
			var feedback = node( 'div', 'no-result' );
			feedback.style.display = 'none';
			popup.body.appendChild( feedback );
			( code ? price : name ).focus();

			footerButtons( popup, labels.create, function ( done ) {
				feedback.style.display = 'none';
				core.posPost( config.createUrl, { fields: { name: name.value, regularPrice: price.value, stockQty: qty.value, gtin: gtin.value, sku: sku.value, showInShop: shop.checked } } )
					.then( function ( result ) {
						if ( ! result || ! result.success ) {
							feedback.textContent = ( result && result.message ) || labels.saveError;
							feedback.style.display = '';
							done();
							return;
						}
						popup.close();
						searchInput.value = result.data.gtin || result.data.sku || result.data.name;
						state.search = searchInput.value;
						state.page = 1;
						setStatus( labels.created + ' ' + labels.resync );
						load( true );
					} )
					.catch( function () {
						feedback.textContent = labels.saveError;
						feedback.style.display = '';
						done();
					} );
			}, '' );
		}

		load();
	}

	core.addScreen( {
		id: 'products',
		path: '/productos',
		title: labels.menu,
		// A box: lid and body.
		iconPaths: [ 'M21 8 12 3 3 8v8l9 5 9-5z', 'M3 8l9 5 9-5', 'M12 13v8' ],
		build: build
	} );
}() );
