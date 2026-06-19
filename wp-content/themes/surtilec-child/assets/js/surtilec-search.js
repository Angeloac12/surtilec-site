/**
 * Surtilec — header search autocomplete (no jQuery).
 *
 * Debounced fetch to the custom REST suggest endpoint, renders a keyboard- and
 * screen-reader-accessible dropdown (combobox/listbox) with up to 4 product
 * matches + category shortcuts + a "see all results" footer. Progressive
 * enhancement: with JS off the plain GET form still works.
 */
( function () {
	'use strict';

	var cfg = window.surtilecSearch || {};
	if ( ! cfg.endpoint ) {
		return;
	}

	var input = document.getElementById( 'surtilec-search-field' );
	if ( ! input ) {
		return;
	}
	var form = input.closest( 'form' );
	var wrap = input.closest( '.surtilec-nav-search' ) || form;

	var i18n = cfg.i18n || {};
	var DEBOUNCE = 180;
	var MIN = 2;

	// --- Build the dropdown container ----------------------------------------
	var box = document.createElement( 'div' );
	box.className = 'su-suggest';
	box.id = 'su-suggest-list';
	box.setAttribute( 'role', 'listbox' );
	box.hidden = true;
	wrap.appendChild( box );

	// ARIA combobox wiring on the input.
	input.setAttribute( 'role', 'combobox' );
	input.setAttribute( 'aria-autocomplete', 'list' );
	input.setAttribute( 'aria-expanded', 'false' );
	input.setAttribute( 'aria-controls', box.id );
	input.setAttribute( 'autocomplete', 'off' );

	var items = [];        // selectable DOM rows in display order
	var active = -1;       // index into items
	var timer = null;
	var controller = null; // AbortController for the in-flight request
	var lastQuery = '';

	function close() {
		box.hidden = true;
		box.innerHTML = '';
		items = [];
		active = -1;
		input.setAttribute( 'aria-expanded', 'false' );
		input.removeAttribute( 'aria-activedescendant' );
	}

	function open() {
		if ( box.children.length ) {
			box.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
		}
	}

	function esc( str ) {
		var d = document.createElement( 'div' );
		d.textContent = str == null ? '' : String( str );
		return d.innerHTML;
	}

	// Highlight the matched substring (case-insensitive), HTML-safe.
	function highlight( text, q ) {
		text = String( text || '' );
		var lc = text.toLowerCase();
		var i = lc.indexOf( q.toLowerCase() );
		if ( i < 0 || ! q ) {
			return esc( text );
		}
		return esc( text.slice( 0, i ) ) +
			'<strong>' + esc( text.slice( i, i + q.length ) ) + '</strong>' +
			esc( text.slice( i + q.length ) );
	}

	function searchUrl( q ) {
		var action = ( form && form.getAttribute( 'action' ) ) || '/';
		return action + '?s=' + encodeURIComponent( q ) + '&post_type=product';
	}

	function setActive( idx ) {
		if ( active > -1 && items[ active ] ) {
			items[ active ].setAttribute( 'aria-selected', 'false' );
		}
		active = idx;
		if ( active > -1 && items[ active ] ) {
			var el = items[ active ];
			el.setAttribute( 'aria-selected', 'true' );
			input.setAttribute( 'aria-activedescendant', el.id );
			el.scrollIntoView( { block: 'nearest' } );
		} else {
			input.removeAttribute( 'aria-activedescendant' );
		}
	}

	function render( data, q ) {
		box.innerHTML = '';
		items = [];
		active = -1;

		var products = ( data && data.products ) || [];
		var cats = ( data && data.categories ) || [];

		if ( ! products.length && ! cats.length ) {
			var none = document.createElement( 'div' );
			none.className = 'su-suggest-empty';
			none.textContent = ( i18n.none || 'Sin coincidencias' ) + ' "' + q + '"';
			box.appendChild( none );
			open();
			return;
		}

		var rowId = 0;

		products.forEach( function ( p ) {
			var a = document.createElement( 'a' );
			a.className = 'su-suggest-item su-suggest-product';
			a.href = p.url;
			a.id = 'su-sg-' + ( rowId++ );
			a.setAttribute( 'role', 'option' );
			a.setAttribute( 'aria-selected', 'false' );

			var media = '';
			if ( p.thumb ) {
				media = '<span class="su-suggest-thumb"><img src="' + esc( p.thumb ) +
					'" alt="" loading="lazy" width="40" height="40"></span>';
			} else {
				media = '<span class="su-suggest-thumb su-suggest-thumb--ph" aria-hidden="true"></span>';
			}

			var meta = [];
			if ( p.sku ) { meta.push( esc( p.sku ) ); }
			if ( p.cat ) { meta.push( esc( p.cat ) ); }

			a.innerHTML = media +
				'<span class="su-suggest-text">' +
					'<span class="su-suggest-title">' + highlight( p.title, q ) + '</span>' +
					( meta.length ? '<span class="su-suggest-meta">' + meta.join( ' · ' ) + '</span>' : '' ) +
				'</span>';
			box.appendChild( a );
			items.push( a );
		} );

		cats.forEach( function ( c ) {
			var a = document.createElement( 'a' );
			a.className = 'su-suggest-item su-suggest-cat';
			a.href = c.url;
			a.id = 'su-sg-' + ( rowId++ );
			a.setAttribute( 'role', 'option' );
			a.setAttribute( 'aria-selected', 'false' );
			a.innerHTML = '<span class="su-suggest-cat-label">' +
				( i18n.cat || 'Ver categoría' ) + ': ' + highlight( c.name, q ) +
				'</span><span class="su-suggest-meta">' + c.count + '</span>';
			box.appendChild( a );
			items.push( a );
		} );

		// Footer: see all results.
		var all = document.createElement( 'a' );
		all.className = 'su-suggest-item su-suggest-all';
		all.href = searchUrl( q );
		all.id = 'su-sg-' + ( rowId++ );
		all.setAttribute( 'role', 'option' );
		all.setAttribute( 'aria-selected', 'false' );
		all.textContent = ( i18n.all || 'Ver todos los resultados para' ) + ' "' + q + '"';
		box.appendChild( all );
		items.push( all );

		open();
	}

	function fetchSuggest( q ) {
		if ( controller ) {
			controller.abort();
		}
		controller = ( 'AbortController' in window ) ? new AbortController() : null;
		var opts = controller ? { signal: controller.signal } : {};

		fetch( cfg.endpoint + '?q=' + encodeURIComponent( q ), opts )
			.then( function ( r ) { return r.ok ? r.json() : null; } )
			.then( function ( data ) {
				if ( ! data || input.value.trim() !== q ) {
					return; // stale
				}
				render( data, q );
			} )
			.catch( function () { /* aborted or network — ignore */ } );
	}

	function onInput() {
		var q = input.value.trim();
		if ( timer ) { clearTimeout( timer ); }
		if ( q.length < MIN ) {
			lastQuery = '';
			close();
			return;
		}
		if ( q === lastQuery ) {
			open();
			return;
		}
		lastQuery = q;
		timer = setTimeout( function () { fetchSuggest( q ); }, DEBOUNCE );
	}

	input.addEventListener( 'input', onInput );

	input.addEventListener( 'keydown', function ( e ) {
		if ( box.hidden || ! items.length ) {
			return;
		}
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			setActive( active + 1 >= items.length ? 0 : active + 1 );
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			setActive( active - 1 < 0 ? items.length - 1 : active - 1 );
		} else if ( e.key === 'Enter' ) {
			if ( active > -1 && items[ active ] ) {
				e.preventDefault();
				window.location.href = items[ active ].href;
			}
			// else: let the form submit normally to the results page.
		} else if ( e.key === 'Escape' ) {
			close();
		}
	} );

	input.addEventListener( 'focus', function () {
		if ( input.value.trim().length >= MIN && box.children.length ) {
			open();
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( ! wrap.contains( e.target ) ) {
			close();
		}
	} );
}() );
