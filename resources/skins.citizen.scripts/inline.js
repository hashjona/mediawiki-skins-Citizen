/*
 * Citizen
 *
 * Inline script used in includes/Hooks/SkinHooks.php
 */

/**
 * Backported from MW 1.42
 * Modified to use localStorage only
 *
 * Validates stored preferences against the server-provided allowlist
 * (window.__citizenPrefsAllowlist) so that disabled or restricted
 * preferences cannot be restored from stale localStorage entries.
 * Invalid entries are stripped from localStorage on the spot.
 */
window.clientPrefs = () => {
	let className = document.documentElement.className;
	// eslint-disable-next-line no-underscore-dangle
	const allowlist = window.__citizenPrefsAllowlist;
	let storage;
	try {
		// mw.storage is not available in this context
		// eslint-disable-next-line mediawiki/no-storage
		storage = localStorage.getItem( 'mwclientpreferences' );
	} catch ( e ) {
		// localStorage is not available, ignore
	}
	if ( !storage ) {
		return;
	}

	const kept = [];
	const entries = storage.split( ',' );
	entries.forEach( ( pref ) => {
		const match = pref.match( /^(.+)-clientpref-(\w+)$/ );
		if ( !match ) {
			return;
		}

		const feature = match[ 1 ];
		const value = match[ 2 ];

		// Validate against the allowlist when present.
		// Disabled prefs have an empty array → all values blocked.
		// Unknown custom prefs (not in allowlist) are allowed through.
		if ( allowlist && feature in allowlist && !allowlist[ feature ].includes( value ) ) {
			return;
		}

		kept.push( pref );

		const pattern = new RegExp(
			'(^| )' + feature.replace( /[^\w-]+/g, '' ) + '-clientpref-\\w+( |$)'
		);
		if ( pattern.test( className ) ) {
			className = className.replace( pattern, '$1' + pref + '$2' );
		} else {
			className += ' ' + pref;
		}
	} );
	document.documentElement.className = className;

	// Clean up localStorage if any entries were removed
	if ( kept.length !== entries.length ) {
		try {
			if ( kept.length > 0 ) {
				// eslint-disable-next-line mediawiki/no-storage
				localStorage.setItem( 'mwclientpreferences', kept.join( ',' ) );
			} else {
				// eslint-disable-next-line mediawiki/no-storage
				localStorage.removeItem( 'mwclientpreferences' );
			}
		} catch ( e ) {
			// localStorage write failed, ignore
		}
	}
};

( () => {
	window.clientPrefs();
} )();
