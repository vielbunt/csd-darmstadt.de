/**
 * mobile nav for CSD Darmstadt
 *
 * handles the submenu accordion in the overlay. we use capture+bubble
 * so WP's own interactivity API handler always runs first and we just
 * mirror the result into our own vb-open class.
 *
 * also resets submenus when the overlay is reopened and injects a
 * spenden button at the bottom of the overlay.
 */
( function () {
	'use strict';

	var preClickState = new WeakMap();

	function init() {
		var nav = document.querySelector( '.wp-block-navigation__responsive-container' );
		if ( ! nav ) { return; }

		/* capture phase: snapshot state before WP's handler fires */
		nav.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.wp-block-navigation-submenu__toggle' );
			if ( ! btn ) { return; }
			var item    = btn.closest( '.has-child' );
			var submenu = item && item.querySelector( ':scope > .wp-block-navigation__submenu-container' );
			if ( ! item || ! submenu ) { return; }
			preClickState.set( btn, {
				wpOpen: submenu.classList.contains( 'open' ) || item.classList.contains( 'open' )
			} );
		}, true );

		/* bubble phase: WP has already handled the click by now */
		nav.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.wp-block-navigation-submenu__toggle' );
			if ( ! btn ) { return; }

			var pre = preClickState.get( btn );
			preClickState.delete( btn );
			if ( ! pre ) { return; }

			var item    = btn.closest( '.has-child' );
			var submenu = item && item.querySelector( ':scope > .wp-block-navigation__submenu-container' );
			if ( ! item || ! submenu ) { return; }

			var nowWpOpen = submenu.classList.contains( 'open' ) || item.classList.contains( 'open' );

			if ( nowWpOpen !== pre.wpOpen ) {
				if ( nowWpOpen && item.parentElement ) {
					[].forEach.call(
						item.parentElement.querySelectorAll( ':scope > .has-child.vb-open' ),
						function ( s ) { s.classList.remove( 'vb-open' ); }
					);
				}
				item.classList.toggle( 'vb-open', nowWpOpen );
			} else {
				var willOpen = ! item.classList.contains( 'vb-open' );
				if ( item.parentElement ) {
					[].forEach.call(
						item.parentElement.querySelectorAll( ':scope > .has-child.vb-open' ),
						function ( s ) {
							s.classList.remove( 'vb-open' );
							var sb = s.querySelector( ':scope > .wp-block-navigation-submenu__toggle' );
							if ( sb ) { sb.setAttribute( 'aria-expanded', 'false' ); }
						}
					);
				}
				item.classList.toggle( 'vb-open', willOpen );
				btn.setAttribute( 'aria-expanded', willOpen ? 'true' : 'false' );
			}
		}, false );

		/* close all submenus when the overlay is reopened */
		var openBtn = document.querySelector( '.wp-block-navigation__responsive-container-open' );
		if ( openBtn ) {
			openBtn.addEventListener( 'click', function () {
				[].forEach.call( nav.querySelectorAll( '.vb-open' ), function ( el ) {
					el.classList.remove( 'vb-open' );
				} );
			} );
		}

		/* add the spenden button to the bottom of the overlay */
		var content = nav.querySelector( '.wp-block-navigation__responsive-container-content' );
		if ( content && ! nav.querySelector( '.vb-mobile-spenden' ) ) {
			var wrapper = document.createElement( 'div' );
			wrapper.className = 'vb-mobile-spenden';
			var link = document.createElement( 'a' );
			link.href = 'https://donorbox.org/csd-darmstadt-2026';
			link.className = 'vb-mobile-spenden__link';
			link.textContent = 'Spenden';
			wrapper.appendChild( link );
			content.appendChild( wrapper );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
