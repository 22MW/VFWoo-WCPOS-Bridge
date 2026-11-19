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
		moduleConfig: moduleConfig,
		addScreen: addScreen
	};
}() );
