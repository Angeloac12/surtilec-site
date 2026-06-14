/**
 * Surtilec — condense the header on scroll (no jQuery).
 * Toggles body.su-scrolled past a small threshold; CSS handles the rest.
 */
( function () {
	'use strict';
	var threshold = 40;
	var ticking = false;

	function update() {
		document.body.classList.toggle( 'su-scrolled', window.scrollY > threshold );
		ticking = false;
	}

	window.addEventListener(
		'scroll',
		function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		},
		{ passive: true }
	);

	update();
}() );
