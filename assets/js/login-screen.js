( function () {
	'use strict';

	var config = window.vfwooWebkulLogin;
	if ( ! config ) {
		return;
	}

	// This script runs in <head>, before the body exists: classes on <html> avoid a flash of Webkul's texts.
	var root = document.documentElement;
	if ( config.hideVersion ) {
		root.classList.add( 'vfwoo-hide-version' );
	}
	if ( config.footer ) {
		root.classList.add( 'vfwoo-custom-footer' );
	}

	function fill( id, value ) {
		var input = document.getElementById( id );
		if ( input && ! input.value ) {
			input.value = value;
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( config.footer ) {
			var line = document.querySelector( '.pos-footer p' );
			if ( line ) {
				line.textContent = config.footer;
			}
			root.classList.add( 'vfwoo-footer-ready' );
		}
		if ( config.user ) {
			fill( 'username', config.user );
			fill( 'password', config.pass || '' );
		}
	} );
}() );
