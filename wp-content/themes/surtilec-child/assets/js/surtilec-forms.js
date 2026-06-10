/**
 * Surtilec — CF7 form helpers.
 *
 * 1. Links the Ciudad text input to its <datalist> (CF7 can't add the
 *    `list` attribute on its own).
 * 2. Populates hidden fields (page URL + UTM params) from the current URL,
 *    so the lead notification email captures the source.
 *
 * Loaded only on pages that render a CF7 form (via `wpcf7_enqueue_scripts`).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// 1. Datalist link.
		var ciudad = document.getElementById( 'surtilec-ciudad' );
		if ( ciudad && document.getElementById( 'ciudades' ) ) {
			ciudad.setAttribute( 'list', 'ciudades' );
		}

		// 2. Hidden field capture.
		var params = new URLSearchParams( window.location.search );

		document.querySelectorAll( '.wpcf7-form' ).forEach( function ( form ) {
			var setField = function ( name, value ) {
				var el = form.querySelector( 'input[name="' + name + '"]' );
				if ( el && value ) {
					el.value = value;
				}
			};

			setField( 'page_url', window.location.href );
			setField( 'utm_source', params.get( 'utm_source' ) );
			setField( 'utm_medium', params.get( 'utm_medium' ) );
			setField( 'utm_campaign', params.get( 'utm_campaign' ) );
		} );
	} );
} )();
