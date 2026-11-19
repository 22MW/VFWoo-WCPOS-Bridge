/**
 * Module "Productos": a light product manager inside the POS.
 * List on the left, the selected product on the right (the Orders layout), edited in place.
 * Built with the shared POS components of core.js (window.vfwooBridge.ui): see _dev/estilos-pos.md.
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
	var ui = core.ui;

	// Shrinks a photo in the browser before it is uploaded (long side 1600 px, JPEG).
	function shrinkImage( file ) {
		return new Promise( function ( resolve ) {
			if ( ! /^image\/(jpeg|png|webp)$/.test( file.type ) ) {
				resolve( file );
				return;
			}
			var image = new window.Image();
			var url = window.URL.createObjectURL( file );
			image.onload = function () {
				var scale = Math.min( 1, 1600 / Math.max( image.width, image.height ) );
				if ( scale === 1 && file.size < 1500000 ) {
					window.URL.revokeObjectURL( url );
					resolve( file );
					return;
				}
				var canvas = document.createElement( 'canvas' );
				canvas.width = Math.round( image.width * scale );
				canvas.height = Math.round( image.height * scale );
				canvas.getContext( '2d' ).drawImage( image, 0, 0, canvas.width, canvas.height );
				canvas.toBlob( function ( blob ) {
					window.URL.revokeObjectURL( url );
					resolve( blob ? new window.File( [ blob ], file.name.replace( /\.\w+$/, '' ) + '.jpg', { type: 'image/jpeg' } ) : file );
				}, 'image/jpeg', 0.85 );
			};
			image.onerror = function () {
				window.URL.revokeObjectURL( url );
				resolve( file );
			};
			image.src = url;
		} );
	}

	function build( root ) {
		var state = { search: '', shop: '', pos: '', stock: '', category: '', incomplete: '', orderby: '', sale: '', brand: '', page: 1 };
		var selected = {};
		var timer = null;
		var requestId = 0;
		var rowCount = 0;
		var activeId = 0;
		var activeRow = null;
		var loader = ui.loader( labels.loading );

		// ---- Layout: the Orders screen (tab bar, list card, detail column) ----
		var screen = ui.screen( root, labels.title );
		var list = screen.list;
		var detail = ui.detail();
		screen.container.appendChild( detail.element );

		// Search and buttons sit in the tab bar, next to the title.
		var toolsBar = node( 'div', 'vfwoo-products-top' );
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
		toolsBar.appendChild( searchInput );
		if ( config.canCreate ) {
			var quick = node( 'button', 'primary', labels.newQuick );
			quick.type = 'button';
			quick.addEventListener( 'click', function () {
				openCreator( '' );
			} );
			toolsBar.appendChild( quick );
		}
		var wp = node( 'button', '', labels.allInWp );
		wp.type = 'button';
		wp.addEventListener( 'click', function () {
			window.open( config.allUrl, '_blank', 'noopener' );
		} );
		toolsBar.appendChild( wp );
		screen.tabs.appendChild( toolsBar );

		// Filters and order.
		var toolbar = node( 'div', 'vfwoo-products-toolbar' );
		function filter( key, options ) {
			var element = ui.select( options, '', 'vfwoo-products-select' );
			element.addEventListener( 'change', function () {
				state[ key ] = element.value;
				state.page = 1;
				load();
			} );
			toolbar.appendChild( element );
		}
		filter( 'shop', [ [ '', labels.shopAll ], [ 'yes', labels.shopYes ], [ 'no', labels.shopNo ] ] );
		filter( 'pos', [ [ '', labels.posAll ], [ 'yes', labels.posYes ], [ 'no', labels.posNo ] ] );
		filter( 'stock', [ [ '', labels.stockAll ], [ 'in', labels.stockIn ], [ 'out', labels.stockOut ] ] );
		filter( 'category', [ [ '', labels.categoryAll ] ].concat( ( config.categories || [] ).map( function ( category ) {
			return [ String( category.id ), category.name ];
		} ) ) );
		if ( config.brands && config.brands.length ) {
			filter( 'brand', [ [ '', labels.brandAll ] ].concat( config.brands.map( function ( brand ) {
				return [ String( brand.id ), brand.name ];
			} ) ) );
		}
		filter( 'sale', [ [ '', labels.saleAll ], [ 'yes', labels.saleYes ], [ 'no', labels.saleNo ] ] );
		filter( 'incomplete', [ [ '', labels.incAll ], [ 'photo', labels.incPhoto ], [ 'price', labels.incPrice ], [ 'sku', labels.incSku ], [ 'gtin', labels.incGtin ], [ 'duplicate', labels.incDuplicate ] ] );
		filter( 'orderby', [ [ '', labels.sortName ], [ 'name_desc', labels.sortNameDesc ], [ 'date_desc', labels.sortNewest ], [ 'sales_desc', labels.sortSold ], [ 'price_asc', labels.sortPriceAsc ], [ 'price_desc', labels.sortPriceDesc ], [ 'stock_asc', labels.sortStockAsc ], [ 'stock_desc', labels.sortStockDesc ] ] );

		var status = ui.noResult( '' );
		status.style.display = 'none';
		var bulkBar = node( 'div', 'vfwoo-products-bulk' );
		bulkBar.style.display = 'none';
		var rows = node( 'div', 'dropdownlist-order vfwoo-products-rows' );
		var pager = node( 'div', 'wkpos-paginition-wrapper' );

		list.appendChild( toolbar );
		list.appendChild( status );
		list.appendChild( bulkBar );
		list.appendChild( rows );
		list.appendChild( pager );

		// A message in the POS "no result" box; empty text hides it.
		function setStatus( text ) {
			status.textContent = text || '';
			status.style.display = text ? '' : 'none';
		}

		function load( keepStatus ) {
			var current = ++requestId;
			loader.show();
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
					loader.hide();
					if ( current !== requestId ) {
						return;
					}
					if ( ! result || ! result.success ) {
						setStatus( ( result && result.message ) || labels.error );
						return;
					}
					renderList( result.data );
				} )
				.catch( function () {
					loader.hide();
					if ( current === requestId ) {
						setStatus( labels.error );
					}
				} );
		}

		function renderList( data ) {
			rows.textContent = '';
			selected = {};
			rowCount = 0;
			activeRow = null;
			renderBulk();
			ui.pager( pager, data, {
				summary: labels.pageOf,
				first: labels.first,
				prev: labels.prev,
				next: labels.next,
				last: labels.last
			}, function ( page ) {
				state.page = page;
				load();
				rows.scrollTop = 0;
			} );

			if ( ! data.items.length ) {
				var empty = ui.noResult( labels.empty );
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

			var all = ui.input( 'checkbox' );
			all.title = labels.selectPage;
			all.setAttribute( 'aria-label', labels.selectPage );
			all.addEventListener( 'change', function () {
				rows.querySelectorAll( 'input[data-id]' ).forEach( function ( box ) {
					box.checked = all.checked;
					selected[ box.getAttribute( 'data-id' ) ] = all.checked;
				} );
				renderBulk();
			} );
			rows.appendChild( ui.listHeader( [
				[ 'check', all ],
				[ 'photo', null ],
				[ 'name', document.createTextNode( labels.product ) ],
				[ 'price', document.createTextNode( labels.price ) ],
				[ 'stock', document.createTextNode( labels.stock ) ],
				[ 'switch', document.createTextNode( labels.inShop ) ],
				[ 'switch', document.createTextNode( labels.inPos ) ]
			] ) );
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
			var box = ui.input( 'checkbox' );
			box.setAttribute( 'data-id', item.id );
			box.setAttribute( 'aria-label', item.name );
			box.addEventListener( 'click', function ( event ) {
				event.stopPropagation();
			} );
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

			var row = ui.line( rowCount++ % 2 ? 'wkwcpos-list-item-even' : 'wkwcpos-list-item-odd', [
				[ 'check', box ],
				[ 'photo', photo ],
				[ 'name', name ],
				[ 'price', document.createTextNode( item.price ) ],
				[ 'stock', document.createTextNode( stockText( item ) ) ],
				[ 'switch', switchFor( item, 'shop', item.shop, item.status !== 'publish' ? labels.notPublished : '' ) ],
				[ 'switch', switchFor( item, 'pos', item.pos, '' ) ]
			] );
			if ( item.id === activeId ) {
				row.className += ' wkwcpos-active-row';
				activeRow = row;
			}
			row.addEventListener( 'click', function () {
				if ( activeRow ) {
					activeRow.className = activeRow.className.replace( ' wkwcpos-active-row', '' );
				}
				row.className += ' wkwcpos-active-row';
				activeRow = row;
				openPanel( item.id );
			} );
			return row;
		}

		function switchFor( item, target, value, disabledReason ) {
			return ui.switchField( {
				checked: value,
				disabled: !! disabledReason,
				title: disabledReason,
				label: ( target === 'shop' ? labels.inShop : labels.inPos ) + ': ' + item.name,
				onChange: function ( wanted, done ) {
					loader.show();
					core.posPost( config.visibilityUrl, { product_id: item.id, target: target, value: wanted } )
						.then( function ( result ) {
							loader.hide();
							if ( ! result || ! result.success ) {
								setStatus( ( result && result.message ) || labels.saveError );
								done( false );
								return;
							}
							setStatus( target === 'pos' ? labels.resync : '' );
							done( true, result.data[ target ] );
						} )
						.catch( function () {
							loader.hide();
							setStatus( labels.saveError );
							done( false );
						} );
				}
			} );
		}

		// ---- The side panel: the selected product, edited in place ----
		function showPrompt() {
			activeId = 0;
			detail.setTitle( labels.title );
			detail.setExtra( [] );
			detail.clear();
			detail.body.appendChild( ui.noResult( labels.selectPrompt, 'no-order-result' ) );
		}

		function openPanel( id ) {
			activeId = id;
			loader.show();
			core.posPost( config.getUrl, { product_id: id } )
				.then( function ( result ) {
					loader.hide();
					if ( id !== activeId ) {
						return;
					}
					detail.clear();
					if ( ! result || ! result.success ) {
						detail.setTitle( labels.title );
						detail.setExtra( [] );
						detail.body.appendChild( ui.noResult( ( result && result.message ) || labels.error ) );
						return;
					}
					buildForm( result.data );
				} )
				.catch( function () {
					loader.hide();
					detail.clear();
					detail.body.appendChild( ui.noResult( labels.error ) );
				} );
		}

		// The form is the POS customer form (ui.formShell); fields go in `body`, the buttons in the fixed footer.
		function formShell() {
			var shell = ui.formShell();
			detail.body.appendChild( shell.element );
			return { body: shell.form, footer: detail.footer };
		}

		function addToForm( shell, element ) {
			shell.body.appendChild( element );
		}

		// Save button (the POS "primary" button) in the actions of the form.
		function applyButton( footer, text, onApply ) {
			var apply = node( 'button', 'primary', text );
			apply.type = 'button';
			footer.appendChild( apply );
			apply.addEventListener( 'click', function () {
				apply.disabled = true;
				apply.textContent = labels.saving;
				onApply( function () {
					apply.disabled = false;
					apply.textContent = text;
				} );
			} );
		}

		function wordpressButton( footer, url ) {
			var open = node( 'button', '', labels.openWp );
			open.type = 'button';
			open.addEventListener( 'click', function () {
				window.open( url, '_blank', 'noopener' );
			} );
			footer.appendChild( open );
		}

		// Photos: main one and gallery. Unlinking a photo does not delete it from the library.
		function photosBlock( data, feedback ) {
			var area = node( 'div', 'vfwoo-photos' );
			var wrap = ui.field( labels.photos, area );
			wrap.className += ' vfwoo-section';
			wrap.appendChild( ui.note( labels.photoNote ) );

			var fileInput = node( 'input' );
			fileInput.type = 'file';
			fileInput.accept = 'image/*';
			fileInput.style.display = 'none';
			wrap.appendChild( fileInput );
			var pendingOp = '';

			function send( formData ) {
				loader.show();
				feedback.style.display = 'none';
				formData.set( 'product_id', data.id );
				return core.posForm( config.imageUrl, formData )
					.then( function ( result ) {
						loader.hide();
						if ( ! result || ! result.success ) {
							feedback.textContent = ( result && result.message ) || labels.photoError;
							feedback.style.display = '';
							return;
						}
						draw( result.data );
						load( true );
					} )
					.catch( function () {
						loader.hide();
						feedback.textContent = labels.photoError;
						feedback.style.display = '';
					} );
			}

			function operation( op, imageId ) {
				var formData = new window.FormData();
				formData.set( 'op', op );
				formData.set( 'image_id', imageId );
				send( formData );
			}

			fileInput.addEventListener( 'change', function () {
				var files = Array.prototype.slice.call( fileInput.files );
				fileInput.value = '';
				var op = pendingOp;
				files.reduce( function ( chain, file, index ) {
					return chain.then( function () {
						return shrinkImage( file ).then( function ( small ) {
							var formData = new window.FormData();
							formData.set( 'op', index === 0 ? op : 'add_gallery' );
							formData.set( 'file', small, small.name );
							return send( formData );
						} );
					} );
				}, Promise.resolve() );
			} );

			function pick( op ) {
				pendingOp = op;
				fileInput.multiple = op === 'add_gallery';
				fileInput.click();
			}

			function button( text, handler ) {
				var element = node( 'button', '', text );
				element.type = 'button';
				element.addEventListener( 'click', handler );
				return element;
			}

			function thumb( image, isMain ) {
				var cell = node( 'div', 'vfwoo-photo' );
				var img = node( 'img' );
				img.src = image.url;
				img.alt = '';
				cell.appendChild( img );
				var actions = node( 'div', 'vfwoo-photo-actions' );
				if ( ! isMain ) {
					actions.appendChild( button( labels.makeMain, function () {
						operation( 'make_main', image.id );
					} ) );
				}
				actions.appendChild( button( labels.removePhoto, function () {
					operation( 'remove', image.id );
				} ) );
				cell.appendChild( actions );
				return cell;
			}

			function draw( images ) {
				area.textContent = '';
				if ( ! config.canUpload ) {
					area.appendChild( ui.noResult( labels.noPhoto ) );
					return;
				}
				if ( images.main ) {
					area.appendChild( thumb( images.main, true ) );
				}
				images.gallery.forEach( function ( image ) {
					area.appendChild( thumb( image, false ) );
				} );
				var add = node( 'div', 'vfwoo-photo-add' );
				if ( ! images.main ) {
					add.appendChild( button( labels.changePhoto, function () {
						pick( 'add_main' );
					} ) );
				}
				add.appendChild( button( labels.addPhoto, function () {
					pick( 'add_gallery' );
				} ) );
				area.appendChild( add );
			}

			draw( data.images );
			return wrap;
		}

		// Categories, tags and brands that already exist: one button each (orange = chosen). Nothing is created.
		// A taxonomy with parents and children is shown as a tree; a flat one, as buttons side by side.
		function termsBlock( data ) {
			var wrap = node( 'div' );
			var groups = [];
			( data.terms || [] ).forEach( function ( group ) {
				var picked = {};
				group.selected.forEach( function ( id ) {
					picked[ id ] = true;
				} );
				var buttons = [];

				function chip( term ) {
					var button = node( 'button', picked[ term.id ] ? 'primary' : '', term.name );
					button.type = 'button';
					button.disabled = ! group.canAssign;
					button.addEventListener( 'click', function () {
						picked[ term.id ] = ! picked[ term.id ];
						button.className = picked[ term.id ] ? 'primary' : '';
					} );
					buttons.push( { name: term.name.toLowerCase(), id: term.id, button: button } );
					return button;
				}

				// Every term is one button. A child shows its path ("Parent › Child") so no tree is needed.
				var listBox = node( 'div', 'vfwoo-terms vfwoo-chips' );
				var names = [];
				group.terms.forEach( function ( term ) {
					names[ term.depth ] = term.name;
					listBox.appendChild( chip( { id: term.id, name: names.slice( 0, term.depth + 1 ).join( ' › ' ) } ) );
				} );

				var inner = node( 'div' );
				if ( group.terms.length > 12 ) {
					var filterInput = ui.input( 'search', '' );
					filterInput.placeholder = labels.filterTerms;
					filterInput.addEventListener( 'input', function () {
						var text = filterInput.value.trim().toLowerCase();
						buttons.forEach( function ( entry ) {
							entry.button.style.display = ! text || entry.name.indexOf( text ) !== -1 || picked[ entry.id ] ? '' : 'none';
						} );
					} );
					inner.appendChild( filterInput );
				}
				inner.appendChild( listBox );
				var box = ui.field( group.label, inner );
				box.className += ' vfwoo-section';
				wrap.appendChild( box );
				groups.push( { taxonomy: group.taxonomy, canAssign: group.canAssign, picked: picked } );
			} );
			return {
				element: wrap,
				collect: function () {
					var out = {};
					groups.forEach( function ( group ) {
						if ( group.canAssign ) {
							out[ group.taxonomy ] = Object.keys( group.picked ).filter( function ( id ) {
								return group.picked[ id ];
							} ).map( Number );
						}
					} );
					return out;
				}
			};
		}

		// Fields shared by a product and a variation. Returns { element, collect() }; collect() keeps what is not shown.
		function stockFields( data, showPrices, editableStock ) {
			var wrap = ui.pair();
			var inputs = {};
			if ( showPrices ) {
				inputs.regularPrice = ui.input( 'text', data.regularPrice );
				inputs.regularPrice.inputMode = 'decimal';
				inputs.salePrice = ui.input( 'text', data.salePrice );
				inputs.salePrice.inputMode = 'decimal';
				inputs.saleFrom = ui.input( 'date', data.saleFrom );
				inputs.saleTo = ui.input( 'date', data.saleTo );
				wrap.appendChild( ui.field( labels.regularPrice, inputs.regularPrice ) );
				wrap.appendChild( ui.field( labels.salePrice, inputs.salePrice ) );
				wrap.appendChild( ui.field( labels.saleFrom, inputs.saleFrom ) );
				wrap.appendChild( ui.field( labels.saleTo, inputs.saleTo ) );
			}
			inputs.sku = ui.input( 'text', data.sku );
			inputs.gtin = ui.input( 'text', data.gtin );
			wrap.appendChild( ui.field( labels.sku, inputs.sku ) );
			wrap.appendChild( ui.field( labels.gtin, inputs.gtin ) );

			inputs.manageStock = ui.input( 'checkbox' );
			inputs.manageStock.checked = !! data.manageStock;
			inputs.stockQty = ui.input( 'number', data.stockQty );
			inputs.stockQty.step = '1';
			inputs.stockStatus = ui.select( [ [ 'instock', labels.inStock ], [ 'outofstock', labels.outOfStock ], [ 'onbackorder', labels.backorder ] ], data.stockStatus );

			wrap.appendChild( ui.checkField( labels.manageStock, inputs.manageStock ) );
			var qty = ui.field( labels.quantity, inputs.stockQty );
			var statusField = ui.field( labels.stockStatus, inputs.stockStatus );
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

		function buildForm( data ) {
			var shell = formShell();
			var extra = [];
			if ( data.sku ) {
				extra.push( data.sku );
			}
			extra.push( data.status === 'publish' ? labels.published : ( labels[ data.status ] || data.status ) );
			detail.setTitle( data.name );
			detail.setExtra( extra );

			if ( ! data.editable ) {
				addToForm( shell, ui.note( labels.unsupported ) );
				wordpressButton( shell.footer, data.editUrl );
				return;
			}

			var feedback = ui.noResult( '' );
			feedback.style.display = 'none';

			var nameInput = ui.input( 'text', data.name );
			var statusSelect = ui.select( [ [ 'publish', labels.published ], [ 'draft', labels.draft ], [ 'pending', labels.pending ], [ 'private', labels.private ] ], data.status );
			addToForm( shell, ui.field( labels.name, nameInput ) );
			addToForm( shell, ui.field( labels.statusLabel, statusSelect ) );
			addToForm( shell, photosBlock( data, feedback ) );

			var isVariable = data.type === 'variable';
			var main = stockFields( data, ! isVariable, data.stockEditable );
			addToForm( shell, main.element );
			if ( isVariable ) {
				addToForm( shell, ui.note( labels.priceNote ) );
			}
			if ( ! data.stockEditable ) {
				addToForm( shell, ui.note( labels.stockLocked ) );
			}

			var variations = [];
			if ( isVariable && data.variations.length ) {
				addToForm( shell, ui.heading( labels.variations ) );
				addToForm( shell, ui.note( labels.variationsNote ) );
				data.variations.forEach( function ( variation ) {
					var card = node( 'div', 'vfwoo-section' );
					card.appendChild( node( 'strong', '', variation.label || '#' + variation.id ) );
					var fields = stockFields( variation, true, data.stockEditable );
					card.appendChild( fields.element );
					addToForm( shell, card );
					variations.push( fields );
				} );
			}

			var classification = termsBlock( data );
			addToForm( shell, classification.element );
			addToForm( shell, feedback );

			wordpressButton( shell.footer, data.editUrl );
			applyButton( shell.footer, labels.save, function ( done ) {
				var fields = main.collect();
				fields.name = nameInput.value;
				fields.status = statusSelect.value;
				fields.variations = variations.map( function ( entry ) {
					return entry.collect();
				} );
				fields.terms = classification.collect();
				feedback.style.display = 'none';
				core.posPost( config.updateUrl, { product_id: data.id, fields: fields } )
					.then( function ( result ) {
						if ( ! result || ! result.success ) {
							feedback.textContent = ( result && result.message ) || labels.saveError;
							feedback.style.display = '';
							done();
							return;
						}
						setStatus( labels.saved + ' ' + labels.resync );
						detail.clear();
						buildForm( result.data );
						load( true );
					} )
					.catch( function () {
						feedback.textContent = labels.saveError;
						feedback.style.display = '';
						done();
					} );
			} );
		}

		// ---- Quick new product, in the same panel ----
		function openCreator( code ) {
			activeId = 0;
			if ( activeRow ) {
				activeRow.className = activeRow.className.replace( ' wkwcpos-active-row', '' );
				activeRow = null;
			}
			detail.setTitle( labels.newTitle );
			detail.setExtra( [] );
			detail.clear();
			var shell = formShell();
			addToForm( shell, ui.note( labels.newNote ) );
			var wrap = ui.pair();
			var name = ui.input( 'text', '' );
			var price = ui.input( 'text', '' );
			price.inputMode = 'decimal';
			var gtin = ui.input( 'text', code );
			var sku = ui.input( 'text', '' );
			var qty = ui.input( 'number', '' );
			qty.step = '1';
			var shop = ui.input( 'checkbox' );
			wrap.appendChild( ui.field( labels.name, name ) );
			wrap.appendChild( ui.field( labels.regularPrice, price ) );
			wrap.appendChild( ui.field( labels.quantity, qty ) );
			wrap.appendChild( ui.field( labels.gtin, gtin ) );
			wrap.appendChild( ui.field( labels.sku, sku ) );
			wrap.appendChild( ui.checkField( labels.showInShop, shop ) );
			addToForm( shell, wrap );
			var feedback = ui.noResult( '' );
			feedback.style.display = 'none';
			addToForm( shell, feedback );
			( code ? price : name ).focus();

			applyButton( shell.footer, labels.create, function ( done ) {
				feedback.style.display = 'none';
				core.posPost( config.createUrl, { fields: { name: name.value, regularPrice: price.value, stockQty: qty.value, gtin: gtin.value, sku: sku.value, showInShop: shop.checked } } )
					.then( function ( result ) {
						if ( ! result || ! result.success ) {
							feedback.textContent = ( result && result.message ) || labels.saveError;
							feedback.style.display = '';
							done();
							return;
						}
						searchInput.value = result.data.gtin || result.data.sku || result.data.name;
						state.search = searchInput.value;
						state.page = 1;
						setStatus( labels.created + ' ' + labels.resync );
						activeId = result.data.id;
						load( true );
						openPanel( result.data.id );
					} )
					.catch( function () {
						feedback.textContent = labels.saveError;
						feedback.style.display = '';
						done();
					} );
			} );
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
					loader.show();
					core.posPost( config.bulkUrl, { product_ids: ids, target: action[ 1 ], value: action[ 2 ] } )
						.then( function ( result ) {
							loader.hide();
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
							loader.hide();
							setStatus( labels.saveError );
							renderBulk();
						} );
				} );
				bulkBar.appendChild( button );
			} );
		}

		showPrompt();
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
