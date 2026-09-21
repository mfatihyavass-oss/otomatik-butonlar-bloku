( function () {
	'use strict';

	function replaceSectionFromResponse( section, url ) {
		if ( section.classList.contains( 'is-loading' ) ) {
			return;
		}

		section.classList.add( 'is-loading' );
		section.setAttribute( 'aria-busy', 'true' );

		fetch( url, {
			credentials: 'same-origin',
			headers: {
				Accept: 'text/html',
			},
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Pagination request failed.' );
				}

				return response.text();
			} )
			.then( function ( html ) {
				var documentParser = new DOMParser();
				var responseDocument = documentParser.parseFromString( html, 'text/html' );
				var nextSection = responseDocument.getElementById( section.id );

				if ( ! nextSection ) {
					throw new Error( 'Pagination section was not found.' );
				}

				section.replaceWith( nextSection );
				window.history.pushState( {}, '', url );

				var heading = nextSection.querySelector( 'h2' );

				if ( heading ) {
					heading.setAttribute( 'tabindex', '-1' );
					heading.focus( { preventScroll: true } );
				}
			} )
			.catch( function () {
				window.location.href = url;
			} )
			.finally( function () {
				var currentSection = document.getElementById( section.id );

				if ( currentSection ) {
					currentSection.classList.remove( 'is-loading' );
					currentSection.setAttribute( 'aria-busy', 'false' );
				}
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		var target = event.target instanceof Element ? event.target : event.target.parentElement;
		var link = target ? target.closest( '.otobuton-post-buttons__pagination a' ) : null;

		if ( ! link ) {
			return;
		}

		event.preventDefault();

		var section = link.closest( '.otobuton-post-buttons' );

		if ( section ) {
			replaceSectionFromResponse( section, link.href );
		}
	} );

	window.addEventListener( 'popstate', function () {
		window.location.reload();
	} );
} )();
