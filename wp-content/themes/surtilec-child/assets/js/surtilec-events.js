/**
 * Surtilec conversion events.
 *
 * Sends privacy-conscious events to the dataLayer, GA4/Site Kit when gtag is
 * available, and Microsoft Clarity when its API is loaded. No form values or
 * personal information are recorded.
 */
( function () {
	'use strict';

	var trackedForms = new WeakSet();

	function pageType() {
		var body = document.body;
		if ( body.classList.contains( 'single-product' ) ) { return 'product'; }
		if ( body.classList.contains( 'tax-product_cat' ) ) { return 'product_category'; }
		if ( body.classList.contains( 'single-post' ) ) { return 'resource'; }
		if ( body.classList.contains( 'home' ) ) { return 'home'; }
		if ( body.classList.contains( 'woocommerce' ) ) { return 'catalog'; }
		return 'page';
	}

	function channel() {
		var ref = document.referrer || '';
		if ( ! ref ) { return 'direct'; }
		if ( /google\.|bing\.|yahoo\.|duckduckgo\./i.test( ref ) ) { return 'organic'; }
		try {
			return new URL( ref ).hostname === window.location.hostname ? 'internal' : 'referral';
		} catch ( err ) {
			return 'referral';
		}
	}

	function track( name, params ) {
		var payload = Object.assign( {
			page_type: pageType(),
			page_path: window.location.pathname,
			traffic_channel: channel()
		}, params || {} );

		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( Object.assign( { event: name }, payload ) );

		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', name, payload );
		}
		if ( typeof window.clarity === 'function' ) {
			window.clarity( 'event', name );
		}
	}

	window.surtilecTrack = track;

	function linkContext( link ) {
		var productSku = link.getAttribute( 'data-product-sku' ) || '';
		if ( ! productSku && document.body.classList.contains( 'single-product' ) ) {
			var sku = document.querySelector( '.product_meta .sku' );
			productSku = sku ? sku.textContent.trim() : '';
		}
		return productSku ? { product_sku: productSku } : {};
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest ? event.target.closest( 'a' ) : null;
		if ( ! link ) { return; }

		var href = link.getAttribute( 'href' ) || '';
		var context = linkContext( link );
		var explicit = link.getAttribute( 'data-surtilec-event' );

		if ( explicit ) {
			track( explicit, context );
			return;
		}
		if ( link.matches( '.su-suggest-item' ) ) {
			track( 'product_search_result_click', Object.assign( { result_type: link.classList.contains( 'su-suggest-product' ) ? 'product' : 'category' }, context ) );
			return;
		}
		if ( link.matches( '.yith-ywraq-add-to-quote a, .add-request-quote-button, .single_add_to_cart_button' ) ) {
			track( 'quote_add_product', context );
			return;
		}
		if ( /wa\.me\/|api\.whatsapp\.com\/send/i.test( href ) ) {
			track( 'whatsapp_click', Object.assign( { lead_channel: 'whatsapp' }, context ) );
			return;
		}
		if ( /\/cotizar(?:\/|$)/i.test( href ) ) {
			track( 'quote_start', Object.assign( { lead_channel: 'form' }, context ) );
		}
	} );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;
		if ( ! form.matches || ! form.matches( '.surtilec-product-search' ) ) { return; }
		var input = form.querySelector( 'input[name="s"]' );
		track( 'product_search', { search_term_length: input ? input.value.trim().length : 0 } );
	} );

	document.addEventListener( 'focusin', function ( event ) {
		var form = event.target.closest ? event.target.closest( '.wpcf7-form' ) : null;
		if ( ! form || trackedForms.has( form ) ) { return; }
		trackedForms.add( form );
		track( 'quote_form_start', { form_id: form.getAttribute( 'data-wpcf7-id' ) || '' } );
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches && event.target.matches( '.wpcf7-form input[type="file"]' ) ) {
			track( 'bom_upload_start', { file_selected: event.target.files && event.target.files.length ? 1 : 0 } );
		}
	} );

	document.addEventListener( 'wpcf7mailsent', function ( event ) {
		track( 'quote_submit', { form_id: event.detail && event.detail.contactFormId ? event.detail.contactFormId : '' } );
	} );

	document.addEventListener( 'wpcf7invalid', function ( event ) {
		track( 'quote_validation_error', { form_id: event.detail && event.detail.contactFormId ? event.detail.contactFormId : '' } );
	} );
}() );
