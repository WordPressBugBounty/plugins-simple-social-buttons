/**
 * Auto-dismiss SSB purge admin notices after a delay.
 */
( function () {
	'use strict';

	let notices = document.querySelectorAll( '.ssb-purge-notice[data-ssb-purge-autodismiss="1"]' );
	if ( ! notices.length ) {
		return;
	}

	let delayMs = 6000;

	notices.forEach( function ( notice ) {
		setTimeout( function () {
			notice.style.transition = 'opacity 0.4s ease';
			notice.style.opacity = '0';
			setTimeout( function () {
				notice.remove();
			}, 400 );
		}, delayMs );
	} );
} )();
