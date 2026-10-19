(function ($) {

	/**
	 * Copyright 2012, Digital Fusion
	 * Licensed under the MIT license.
	 * http://teamdf.com/jquery-plugins/license/
	 *
	 * @author Sam Sehnert
	 * @desc A small plugin that checks whether elements are within
	 *     the user visible viewport of a web browser.
	 *     only accounts for vertical position, not horizontal.
	 */

	$.fn.visible = function (partial) {

		let $t            = $( this ),
			$w            = $( globalThis ),
			viewTop       = $w.scrollTop(),
			viewBottom    = viewTop + $w.height(),
			_top          = $t.offset().top,
			_bottom       = _top + $t.height(),
			compareTop    = partial === true ? _bottom : _top,
			compareBottom = partial === true ? _top : _bottom;

		return ((compareBottom <= viewBottom) && (compareTop >= viewTop));

	};

})( jQuery );

// IIFE - Immediately Invoked Function Expression
// Read from globalThis — `let ssbPlugin = ssbPlugin || {}` throws TDZ ReferenceError.
globalThis.ssbPlugin = globalThis.ssbPlugin || {};
let ssbPlugin = globalThis.ssbPlugin;

(function ($, globalThis, document) {
	'use strict';

	ssbPlugin.getTrackableNetworkFromElement = function (element) {
		if ( ! element ) {
			return '';
		}

		let trackableNetworks = Array.isArray( SSB?.share_trackable_networks ) ? SSB.share_trackable_networks : [];
		let network           = element.dataset.ssbNetwork;

		return ( network && trackableNetworks.includes( network ) ) ? network : '';
	};

	ssbPlugin.getSharePopupFeatures = function () {
		return SSB?.popup_features
			? SSB.popup_features
			: 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600';
	};

	/**
	 * Bind Snap Creative Kit to custom Snapchat share buttons.
	 *
	 * @param {Document|Element} [root] Optional subtree (e.g. icon-limit popup).
	 */
	ssbPlugin.initSnapchatCreativeKit = function (root) {
		let scope   = root?.getElementsByClassName ? root : document;
		let buttons = scope.getElementsByClassName( 'snapchat-share-button' );

		if ( ! buttons.length ) {
			return;
		}

		let bindButtons = function () {
			if ( globalThis.snap?.creativekit?.initalizeShareButtons ) {
				globalThis.snap.creativekit.initalizeShareButtons( buttons );
				return true;
			}

			return false;
		};

		if ( bindButtons() ) {
			return;
		}

		globalThis.snapKitInit = function () {
			bindButtons();
		};

		ssbPlugin.bindSnapchatShareFallback( buttons );
	};

	/**
	 * Open Snapchat web share when Creative Kit is unavailable.
	 *
	 * @param {HTMLCollection|Array} buttons Snapchat share button elements.
	 */
	ssbPlugin.bindSnapchatShareFallback = function (buttons) {
		Array.prototype.forEach.call(
			buttons,
			function (button) {
				if ( button.dataset.ssbSnapchatFallback === '1' ) {
					return;
				}

				button.dataset.ssbSnapchatFallback = '1';
				button.addEventListener(
					'click',
					function (event) {
						if ( globalThis.snap?.creativekit?.initalizeShareButtons ) {
							return;
						}

						let shareUrl = button.dataset.shareUrl;
						if ( ! shareUrl ) {
							return;
						}

						event.preventDefault();
						globalThis.open( shareUrl, '_blank', ssbPlugin.getSharePopupFeatures() );
					}
				);
			}
		);
	};

	ssbPlugin.initShareActionDelegation = function () {
		document.addEventListener(
			'click',
			function (event) {
				if ( event.defaultPrevented ) {
					return;
				}

				let button = event.target.closest( '[data-ssb-share-action]' );
				if ( ! button ) {
					return;
				}

				let action = button.dataset.ssbShareAction;
				if ( ! action ) {
					return;
				}

				event.preventDefault();

				let url            = button.dataset.href || '';
				let popupFeatures  = ssbPlugin.getSharePopupFeatures();

				switch ( action ) {
				case 'popup':
					globalThis.open( url, '', popupFeatures );
					break;
				case 'blank':
					globalThis.open( url, '_blank' );
					break;
				case 'self':
					globalThis.open( url, '_self' );
					break;
				case 'messenger':
					globalThis.open( url, '_blank', popupFeatures );
					break;
				case 'mailto':
					globalThis.location.href = url;
					break;
				case 'print':
					globalThis.print();
					break;
				case 'pinterest-pin':
					// Prefer Pinterest's pinmarklet picker; fall back to the pin-create URL.
					if ( ! document.querySelector( 'script[src*="pinmarklet.js"]' ) ) {
						let pinScript = document.createElement( 'script' );
						pinScript.setAttribute( 'type', 'text/javascript' );
						pinScript.setAttribute( 'charset', 'UTF-8' );
						pinScript.setAttribute(
							'src',
							'https://assets.pinterest.com/js/pinmarklet.js?r=' + Math.random() * 99999999
						);
						pinScript.onerror = function () {
							if ( url ) {
								globalThis.open( url, '', popupFeatures );
							}
						};
						document.body.appendChild( pinScript );
					} else if ( url ) {
						globalThis.open( url, '', popupFeatures );
					}
					break;
				default:
					break;
				}
			},
			true
		);
	};

	ssbPlugin.trackShareClickFromElement = function (element) {
		if ( ! element ) {
			return;
		}

		let network = ssbPlugin.getTrackableNetworkFromElement( element );
		if ( ! network ) {
			return;
		}

		let postId = Number.parseInt( element.dataset.ssbPostId || '0', 10 );
		if ( ! Number.isFinite( postId ) || postId <= 0 ) {
			return;
		}

		ssbPlugin.trackShareClick( postId, network );
	};

	ssbPlugin.trackShareClick = function (postId, network) {
		if ( ! SSB?.ajax_url || ! SSB?.share_track_nonce ) {
			return;
		}

		let payload = {
			action: 'ssb_track_share_click',
			security: SSB.share_track_nonce,
			post_id: postId,
			network: network
		};

		if ( navigator.sendBeacon && globalThis.URLSearchParams ) {
			let params = new URLSearchParams();
			Object.keys( payload ).forEach(
				function (key) {
					params.append( key, payload[ key ] );
				}
			);
			navigator.sendBeacon(
				SSB.ajax_url,
				new Blob(
					[ params.toString() ],
					{ type: 'application/x-www-form-urlencoded' }
				)
			);
			return;
		}

		$.post( SSB.ajax_url, payload );
	};

	ssbPlugin.initInternalShareTracking = function () {
		document.addEventListener(
			'click',
			function (event) {
				if ( event.defaultPrevented ) {
					return;
				}

				let target = event.target.closest( '.simplesocialbuttons [data-ssb-network]' );
				if ( ! target ) {
					return;
				}

				ssbPlugin.trackShareClickFromElement( target );
			},
			true
		);
	};

	// Listen for the jQuery ready event on the document
	$(
		function () {
			// The DOM is ready!
			if ($( 'div[class*="simplesocialbuttons-float"]' ).length > 0) {
				$( 'body' ).addClass( 'body_has_simplesocialbuttons' );
			}

			function ssbCloseIconLimitPopups() {
				$( '.ssb_icon-limit-popup' ).each(
					function () {
						let $popup = $( this );
						$popup.removeClass( 'ssb_icon-limit-popup--open' ).attr( 'hidden', 'hidden' );
					}
				);
				$( 'body' ).removeClass( 'ssb_icon_limit_popup_open' );
			}

			function ssbOpenIconLimitPopup( $popup ) {
				if ( ! $popup.length ) {
					return;
				}

				if ( ! $popup.parent().is( 'body' ) ) {
					$popup.appendTo( 'body' );
				}

				ssbCloseIconLimitPopups();
				$popup.removeAttr( 'hidden' ).addClass( 'ssb_icon-limit-popup--open' );
				$( 'body' ).addClass( 'ssb_icon_limit_popup_open' );
				if ( ssbPlugin.initSnapchatCreativeKit ) {
					ssbPlugin.initSnapchatCreativeKit( $popup.get( 0 ) );
				}
			}

			$( document ).on(
				'click',
				'.ssb_icon-limit-more',
				function ( e ) {
					e.preventDefault();
					e.stopPropagation();

					let targetId = $( this ).attr( 'data-ssb-popup-target' );
					if ( targetId ) {
						ssbOpenIconLimitPopup( $( '#' + targetId ) );
					}
				}
			);

			$( document ).on(
				'click',
				'.ssb_custom-button:not(.ssb_icon-limit-more)',
				function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					$( this ).siblings( '.ssb_wrapper_mobile' ).first().addClass( 'ssb_wrapper_mobile_open' );
					$( 'body' ).addClass( 'ssb_icon_limit_popup_open' );
				}
			);

			$( document ).on(
				'click',
				'.ssb_icon-limit-popup .ssb_wrapper-closed',
				function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					$( this ).closest( '.ssb_icon-limit-popup' ).removeClass( 'ssb_icon-limit-popup--open' ).attr( 'hidden', 'hidden' );
					if ( ! $( '.ssb_icon-limit-popup--open' ).length ) {
						$( 'body' ).removeClass( 'ssb_icon_limit_popup_open' );
					}
				}
			);

			$( document ).on(
				'click',
				'.ssb_wrapper-closed',
				function ( e ) {
					if ( $( this ).closest( '.ssb_icon-limit-popup' ).length ) {
						return;
					}
					e.preventDefault();
					e.stopPropagation();
					$( this ).closest( '.ssb_wrapper_mobile' ).removeClass( 'ssb_wrapper_mobile_open' );
					if ( ! $( '.ssb_wrapper_mobile.ssb_wrapper_mobile_open' ).length ) {
						$( 'body' ).removeClass( 'ssb_icon_limit_popup_open' );
					}
				}
			);

			$( document ).on(
				'click',
				'.ssb_icon-limit-popup',
				function ( e ) {
					if ( e.target === this ) {
						$( this ).removeClass( 'ssb_icon-limit-popup--open' ).attr( 'hidden', 'hidden' );
						if ( ! $( '.ssb_icon-limit-popup--open' ).length ) {
							$( 'body' ).removeClass( 'ssb_icon_limit_popup_open' );
						}
					}
				}
			);

			$( document ).on(
				'click',
				'.ssb_wrapper_mobile',
				function ( e ) {
					if ( e.target === this ) {
						$( this ).removeClass( 'ssb_wrapper_mobile_open' );
						if ( ! $( '.ssb_wrapper_mobile.ssb_wrapper_mobile_open' ).length && ! $( '.ssb_icon-limit-popup--open' ).length ) {
							$( 'body' ).removeClass( 'ssb_icon_limit_popup_open' );
						}
					}
				}
			);

			$( document ).on(
				'keydown',
				'.ssb_icon-limit-popup .ssb_wrapper-closed',
				function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						e.preventDefault();
						$( this ).trigger( 'click' );
					}
				}
			);

			$( document ).on(
				'keydown',
				function ( e ) {
					if ( e.key === 'Escape' ) {
						ssbCloseIconLimitPopups();
					}
				}
			);
		}
	);

	$( globalThis ).on(
		'load',
		function () {
			let allMods = $( ".simplesocialbuttons_inline" );
			if ( 'IntersectionObserver' in globalThis ) {
				let inlineObserver = new IntersectionObserver(
					function (entries) {
						entries.forEach(
							function (entry) {
								if ( entry.isIntersecting ) {
									entry.target.classList.add( 'simplesocialbuttons-inline-in' );
									inlineObserver.unobserve( entry.target );
								}
							}
						);
					},
					{ rootMargin: '0px 0px 8% 0px', threshold: 0 }
				);

				allMods.each(
					function (i, el) {
						inlineObserver.observe( el );
					}
				);
			} else {
				// Legacy browsers: throttle checks and drop nodes once animated (no per-scroll full scan).
				let pendingInline = [];
				allMods.each(
					function (i, el) {
						let $el = $( el );
						if ($el.visible( true )) {
							$el.addClass( 'simplesocialbuttons-inline-in' );
						} else {
							pendingInline.push( el );
						}
					}
				);

				if (pendingInline.length > 0) {
					let scrollTicking = false;

					function flushInlineVisibility() {
						scrollTicking = false;
						let i = pendingInline.length;
						while ( i-- ) {
							let el = pendingInline[ i ];
							let $el = $( el );
							if ($el.visible( true )) {
								$el.addClass( 'simplesocialbuttons-inline-in' );
								pendingInline.splice( i, 1 );
							}
						}
						if (pendingInline.length === 0) {
							$( globalThis ).off( 'scroll.ssbInlineVisibility' );
						}
					}

					$( globalThis ).on(
						'scroll.ssbInlineVisibility',
						function () {
							if (!scrollTicking) {
								scrollTicking = true;
								globalThis.requestAnimationFrame( flushInlineVisibility );
							}
						}
					);
				}
			}

			let $firstFloatLink = $( 'div[class*="simplesocialbuttons-float"]>a:first-child' );
			if ( $firstFloatLink.length ) {
				let sidebarwidth = $firstFloatLink.outerWidth( true );
				if ( typeof sidebarwidth === 'number' && sidebarwidth > 0 ) {
					$( 'div[class*="simplesocialbuttons-float"]' ).css( 'width', sidebarwidth + 'px' );
				}
			}
			$( '.simplesocialbuttons.ssb_counter-activate:not(.simplesocial-round-txt):not(.simplesocial-round-icon):not(.simplesocial-simple-icons) button:not(.simplesocial-viber-share):not(.simplesocial-whatsapp-share):not(.simplesocial-msng-share):not(.simplesocial-email-share):not(.simplesocial-print-share):not(.simplesocial-copy-link):not(.simplesocial-linkedin-share)' ).each(
				function () {
					let $el = $( this );
					setTimeout(
						function () {
							let $elWidth = $el.children( '.ssb_counter' ).innerWidth();
							$el.css( 'padding-right', $elWidth + 10 );
						},
						100
					);
				}
			);
		}
	);

	function ssbEscapeAttr(value) {
		return String( value )
			.replaceAll( '&', '&amp;' )
			.replaceAll( '"', '&quot;' )
			.replaceAll( '<', '&lt;' );
	}

	function docLoadedFun() {
		let hideLabel = SSB?.i18n?.hide_bar ? SSB.i18n.hide_bar : 'Hide social sharing bar';
		let hideSidebarButton = '<span tabindex="0" role="button" class="ssb-hide-floating-bar" aria-label="' + ssbEscapeAttr( hideLabel ) + '" aria-pressed="false"><svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 370.814 370.814"><path d="M292.92 24.848L268.781 0 77.895 185.401l190.886 185.413 24.139-24.853-165.282-160.56"></path></svg></span>';
		if (document.querySelector( 'div[class*="simplesocialbuttons-float"]' )) {
			document.querySelector( 'div[class*="simplesocialbuttons-float"]' ).insertAdjacentHTML( 'beforeend', hideSidebarButton );
			document.querySelector( '.ssb-hide-floating-bar' ).addEventListener( 'click', toggleSidebarButtons );
		}
	}

	function toggleSidebarButtons() {
		let leftSidebar = document.querySelector( 'div[class*="simplesocialbuttons-float"]' );
		let hideSidebarToggle = document.querySelector( '.ssb-hide-floating-bar' );
		leftSidebar.classList.toggle( 'ssb-hide-float-buttons' );
		if (hideSidebarToggle) {
			let isHidden  = leftSidebar.classList.contains( 'ssb-hide-float-buttons' );
			let showLabel = SSB?.i18n?.show_bar ? SSB.i18n.show_bar : 'Show social sharing bar';
			let hideLabel = SSB?.i18n?.hide_bar ? SSB.i18n.hide_bar : 'Hide social sharing bar';
			hideSidebarToggle.setAttribute( 'aria-label', isHidden ? showLabel : hideLabel );
			hideSidebarToggle.setAttribute( 'aria-pressed', isHidden ? 'true' : 'false' );
		}
	}

	document.addEventListener( 'DOMContentLoaded', docLoadedFun );
	document.addEventListener( 'DOMContentLoaded', ssbPlugin.initInternalShareTracking );
	document.addEventListener( 'DOMContentLoaded', ssbPlugin.initShareActionDelegation );
	document.addEventListener( 'DOMContentLoaded', function () {
		ssbPlugin.initSnapchatCreativeKit();
	} );

})( globalThis.jQuery, globalThis, document );

// function to copy the current link to clipboard
// eslint-disable-next-line no-unused-vars
function ssb_copy_share_link(clickedButton) {
	ssbPlugin?.trackShareClickFromElement?.( clickedButton );

	const url = ( clickedButton.dataset.href || '' ).trim();

	const showCopiedFeedback = () => {
		if (jQuery( clickedButton ).closest( '.simplesocial-simple-round' ).length === 0) {
			jQuery( clickedButton ).attr( 'data-tooltip', 'Copied' );
		} else {
			jQuery( clickedButton ).find( '.ssb_tooltip' ).show();
		}
		clickedButton.classList.add( 'ssb_copy_btn' );
		setTimeout(
			() => {
				jQuery( clickedButton ).removeAttr( 'data-tooltip' );
				clickedButton.classList.remove( 'ssb_copy_btn' );
				jQuery( clickedButton ).find( '.ssb_tooltip' ).hide();
			},
			1500
		);
	};

	const copyWithTextArea = () => {
		const textArea = document.createElement( 'textarea' );
		textArea.value = url;
		document.body.appendChild( textArea );
		textArea.select();
		try {
			if ( document.execCommand( 'copy' ) ) {
				showCopiedFeedback();
			} else {
				console.warn( 'Failed to copy URL using text area selection.' );
			}
		} catch (err) {
			console.error( 'Failed to copy URL:', err );
		} finally {
			textArea.remove();
		}
	};

	if ( navigator.clipboard && typeof navigator.clipboard.writeText === 'function' ) {
		navigator.clipboard.writeText( url ).then( showCopiedFeedback ).catch( copyWithTextArea );
		return;
	}

	copyWithTextArea();
}
