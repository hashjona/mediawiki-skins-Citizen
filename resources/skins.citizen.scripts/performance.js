/**
 * @param {Object} deps
 * @param {Document} deps.document
 * @param {Object} deps.mw
 * @param {Object} deps.config
 * @return {Object}
 */
function createPerformanceMode( { document, mw, config } ) {
	/**
	 * Auto-detect GPU capability and adjust performance mode accordingly.
	 *
	 * The configured default is respected as the baseline. The probe only
	 * overrides it when GPU acceleration is detected (switching to '0' to
	 * enable visual effects). On no-GPU devices the configured default is
	 * persisted as-is so the probe does not repeat on subsequent loads.
	 *
	 * Skips entirely if the preference is disabled or a user preference
	 * is already stored.
	 *
	 * @return {void}
	 */
	function init() {
		const prefConfig = ( config.wgCitizenPreferencesConfig || {} )[ 'citizen-feature-performance-mode' ];
		if ( !prefConfig || !prefConfig.enabled ) {
			return;
		}

		const allowedOptions = prefConfig.options || [];
		const configDefault = prefConfig.default || '1';
		const prefName = 'citizen-feature-performance-mode-clientpref-';
		const clientPrefs = mw.storage.get( 'mwclientpreferences' );

		if ( clientPrefs && ( clientPrefs.includes( prefName + '0' ) || clientPrefs.includes( prefName + '1' ) ) ) {
			return;
		}

		const canvas = document.createElement( 'canvas' );
		const contextNames = [ 'webgl', 'experimental-webgl', 'webgl2' ];
		const hasGpu = contextNames.some( ( name ) => {
			try {
				const gl = canvas.getContext( name );
				return !!( gl && typeof gl.getParameter === 'function' );
			} catch ( e ) {
				return false;
			}
		} );

		// GPU detected → turn performance mode off ('0') to enable effects.
		// No GPU → keep the configured default as-is.
		const desiredValue = hasGpu ? '0' : configDefault;
		if ( !allowedOptions.includes( desiredValue ) ) {
			return;
		}

		// Swap CSS class only when diverging from the server-rendered default
		if ( desiredValue !== configDefault ) {
			document.documentElement.classList.replace(
				prefName + configDefault,
				prefName + desiredValue
			);
		}

		// Persist the result so the WebGL probe does not repeat
		const entry = prefName + desiredValue;
		mw.storage.set( 'mwclientpreferences', clientPrefs ? `${ clientPrefs },${ entry }` : entry );
	}

	return { init };
}

module.exports = { createPerformanceMode };
