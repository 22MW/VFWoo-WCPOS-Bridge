( function ( hooks ) {
	'use strict';

	var variables = window.vfwooWebkulInvoiceVars;
	var element = window.wp && window.wp.element;

	// The editor evaluates the saved template as a template literal and only knows Webkul's variables:
	// an unknown ${vfwoo_*} raises an error and leaves the editor blank. Escaping it makes the editor
	// show the tag as plain text, like the Webkul ones, and it is saved back unchanged.
	// Registered before the editor's own DOMContentLoaded listener, so it runs first.
	document.addEventListener( 'DOMContentLoaded', function () {
		var data = window.wkwcposInvoiceObj;
		if ( data && typeof data.invoice_html === 'string' ) {
			data.invoice_html = data.invoice_html.replace( /\$\{(vfwoo_[a-z_]+)\}/g, function ( match, name ) {
				return '\\${' + name + '}';
			} );
		}
	} );

	if ( ! hooks || ! hooks.addFilter || ! element || ! Array.isArray( variables ) ) {
		return;
	}

	// Adds the VFWoo variables to the "Pre-defined Variables" list of Webkul's template editor.
	hooks.addFilter( 'wkwcpos_add_new_invoice_variables', 'vfwoo-webkul-pos-bridge', function ( extra ) {
		return element.createElement(
			element.Fragment,
			null,
			extra,
			variables.map( function ( variable ) {
				return element.createElement( 'li', { key: variable.tag }, element.createElement( 'strong', null, variable.tag + ' - ' ), variable.label );
			} )
		);
	} );
}( window.wp && window.wp.hooks ? window.wp.hooks : null ) );
