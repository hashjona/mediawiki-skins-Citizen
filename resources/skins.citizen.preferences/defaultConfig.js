const { PreferencesConfig } = require( './types.js' );

/**
 * UI metadata for built-in preferences.
 * Options and defaults come from the server-provided PreferencesConfig;
 * this map supplies section, type, i18n keys, and visibility conditions.
 */
const PREFERENCE_UI_METADATA = {
	'skin-theme': {
		section: 'appearance',
		type: 'radio',
		columns: 3,
		labelMsg: 'citizen-theme-name',
		descriptionMsg: 'citizen-theme-description',
		visibilityCondition: 'always',
		optionLabels: {
			os: 'citizen-theme-os-label',
			day: 'citizen-theme-day-label',
			night: 'citizen-theme-night-label'
		}
	},
	'citizen-feature-custom-font-size': {
		section: 'appearance',
		type: 'select',
		labelMsg: 'citizen-feature-custom-font-size-name',
		descriptionMsg: 'citizen-feature-custom-font-size-description',
		visibilityCondition: 'always',
		optionLabels: {
			small: 'citizen-feature-custom-font-size-small-label',
			standard: 'citizen-feature-custom-font-size-standard-label',
			large: 'citizen-feature-custom-font-size-large-label',
			xlarge: 'citizen-feature-custom-font-size-xlarge-label'
		}
	},
	'citizen-feature-custom-width': {
		section: 'appearance',
		type: 'select',
		labelMsg: 'citizen-feature-custom-width-name',
		descriptionMsg: 'citizen-feature-custom-width-description',
		visibilityCondition: 'always',
		optionLabels: {
			standard: 'citizen-feature-custom-width-standard-label',
			expanded: 'citizen-feature-custom-width-expanded-label',
			wide: 'citizen-feature-custom-width-wide-label',
			full: 'citizen-feature-custom-width-full-label'
		}
	},
	'citizen-feature-pure-black': {
		section: 'appearance',
		type: 'switch',
		labelMsg: 'citizen-feature-pure-black-name',
		descriptionMsg: 'citizen-feature-pure-black-description',
		visibilityCondition: 'dark-theme'
	},
	'citizen-feature-image-dimming': {
		section: 'appearance',
		type: 'switch',
		labelMsg: 'citizen-feature-image-dimming-name',
		descriptionMsg: 'citizen-feature-image-dimming-description',
		visibilityCondition: 'dark-theme'
	},
	'citizen-feature-autohide-navigation': {
		section: 'behavior',
		type: 'switch',
		labelMsg: 'citizen-feature-autohide-navigation-name',
		descriptionMsg: 'citizen-feature-autohide-navigation-description',
		visibilityCondition: 'tablet-viewport'
	},
	'citizen-feature-performance-mode': {
		section: 'behavior',
		type: 'switch',
		labelMsg: 'citizen-feature-performance-mode-name',
		descriptionMsg: 'citizen-feature-performance-mode-description',
		visibilityCondition: 'always'
	}
};

/**
 * Build the full preferences config from server-provided PreferencesConfig
 * and built-in UI metadata.
 *
 * @param {Object} serverConfig Map of feature name → { enabled, default, options }
 * @return {PreferencesConfig}
 */
function buildConfig( serverConfig ) {
	const preferences = {};

	for ( const [ feature, serverPref ] of Object.entries( serverConfig ) ) {
		if ( !serverPref.enabled ) {
			continue;
		}

		const uiMeta = PREFERENCE_UI_METADATA[ feature ];
		if ( !uiMeta ) {
			// Unknown preference from server config — skip (gadget prefs
			// are registered via the JS API, not here)
			continue;
		}

		const options = ( serverPref.options || [] ).map( ( value ) => {
			const labelMsg = uiMeta.optionLabels && uiMeta.optionLabels[ value ];
			return labelMsg ? { value, labelMsg } : { value };
		} );

		// A preference with fewer than 2 options has nothing to configure
		if ( options.length < 2 ) {
			continue;
		}

		preferences[ feature ] = {
			section: uiMeta.section,
			options,
			type: uiMeta.type,
			labelMsg: uiMeta.labelMsg,
			descriptionMsg: uiMeta.descriptionMsg,
			visibilityCondition: uiMeta.visibilityCondition
		};

		if ( uiMeta.columns ) {
			preferences[ feature ].columns = uiMeta.columns;
		}
	}

	return {
		sections: {
			appearance: { labelMsg: 'citizen-preferences-section-appearance' },
			behavior: { labelMsg: 'citizen-preferences-section-behavior' }
		},
		preferences
	};
}

module.exports = buildConfig;
