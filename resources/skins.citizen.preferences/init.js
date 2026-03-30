const Vue = require( 'vue' );
const { reactive } = Vue;
const App = require( './App.vue' );
const buildConfig = require( './defaultConfig.js' );
const serverConfig = require( './config.json' );
const {
	mergeConfigs, normalizeConfig
} = require( './configRegistry.js' );

/**
 * Initialize the preferences panel Vue app.
 *
 * The server-provided PreferencesConfig (enabled, default, options per
 * preference) is merged with built-in UI metadata (labels, sections,
 * types, visibility) to produce the full config for the Vue app.
 *
 * After mounting, fires `mw.hook('citizen.preferences.register')` with a
 * `register( config )` callback that gadgets/extensions can use to add
 * sections and preferences at runtime.
 */
function initApp() {
	const mountPoint = document.getElementById( 'citizen-preferences-content' );
	if ( !mountPoint ) {
		return;
	}

	const prefsConfig = serverConfig.wgCitizenPreferencesConfig || {};
	const defaults = buildConfig( prefsConfig );
	const config = reactive( normalizeConfig( defaults ) );

	/**
	 * Merge a registration object into the live reactive config.
	 *
	 * @param {Object} registration - Same shape as PreferencesConfig
	 */
	function register( registration ) {
		if (
			!registration ||
			typeof registration !== 'object' ||
			Array.isArray( registration )
		) {
			mw.log.warn( 'citizen.preferences.register: expected an object, got ' + typeof registration );
			return;
		}
		const updated = normalizeConfig(
			mergeConfigs( config, registration )
		);
		Object.assign( config.sections, updated.sections );
		Object.assign( config.preferences, updated.preferences );
	}

	const themeDefault = ( prefsConfig[ 'skin-theme' ] || {} ).default || 'os';

	const app = Vue.createMwApp( App );
	app.provide( 'preferencesConfig', config );
	app.provide( 'themeDefault', themeDefault );
	app.mount( mountPoint );

	mw.hook( 'citizen.preferences.register' ).fire( register );
}

// Export for testing.
// Note: initApp() auto-executes at import time below. In tests, the import
// happens in beforeAll when no mount point exists, so it safely no-ops.
// Tests then call initApp() explicitly with their own DOM fixtures.
module.exports = { initApp };

// Auto-execute
initApp();
