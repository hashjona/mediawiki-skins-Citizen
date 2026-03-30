// @vitest-environment jsdom
const mw = require( '../mocks/mw.js' );
globalThis.mw = mw;

let buildConfig;

const ALL_ENABLED_SERVER_CONFIG = {
	'skin-theme': { enabled: true, default: 'os', options: [ 'os', 'day', 'night' ] },
	'citizen-feature-custom-font-size': {
		enabled: true, default: 'standard',
		options: [ 'small', 'standard', 'large', 'xlarge' ]
	},
	'citizen-feature-custom-width': {
		enabled: true, default: 'standard',
		options: [ 'standard', 'expanded', 'wide', 'full' ]
	},
	'citizen-feature-pure-black': { enabled: true, default: '0', options: [ '0', '1' ] },
	'citizen-feature-image-dimming': { enabled: true, default: '0', options: [ '0', '1' ] },
	'citizen-feature-autohide-navigation': { enabled: true, default: '1', options: [ '0', '1' ] },
	'citizen-feature-performance-mode': { enabled: true, default: '1', options: [ '0', '1' ] }
};

beforeAll( async () => {
	const mod = await import(
		'../../../resources/skins.citizen.preferences/defaultConfig.js'
	);
	buildConfig = mod.default || mod;
} );

describe( 'buildConfig', () => {
	it( 'should return sections with appearance and behavior', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );

		expect( config.sections ).toHaveProperty( 'appearance' );
		expect( config.sections ).toHaveProperty( 'behavior' );
		expect( config.sections.appearance ).toHaveProperty( 'labelMsg' );
		expect( config.sections.behavior ).toHaveProperty( 'labelMsg' );
	} );

	it( 'should return all 7 built-in preferences when all enabled', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );
		const keys = Object.keys( config.preferences );

		expect( keys ).toHaveLength( 7 );
		expect( keys ).toContain( 'skin-theme' );
		expect( keys ).toContain( 'citizen-feature-custom-font-size' );
		expect( keys ).toContain( 'citizen-feature-custom-width' );
		expect( keys ).toContain( 'citizen-feature-pure-black' );
		expect( keys ).toContain( 'citizen-feature-image-dimming' );
		expect( keys ).toContain( 'citizen-feature-autohide-navigation' );
		expect( keys ).toContain( 'citizen-feature-performance-mode' );
	} );

	it( 'should exclude disabled preferences', () => {
		const serverConfig = {
			...ALL_ENABLED_SERVER_CONFIG,
			'citizen-feature-pure-black': { enabled: false, default: '0', options: [ '0', '1' ] }
		};
		const config = buildConfig( serverConfig );

		expect( config.preferences ).not.toHaveProperty( 'citizen-feature-pure-black' );
		expect( Object.keys( config.preferences ) ).toHaveLength( 6 );
	} );

	it( 'should assign appearance prefs to appearance section', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );

		expect( config.preferences[ 'skin-theme' ].section ).toBe( 'appearance' );
		expect( config.preferences[ 'citizen-feature-custom-font-size' ].section )
			.toBe( 'appearance' );
		expect( config.preferences[ 'citizen-feature-custom-width' ].section )
			.toBe( 'appearance' );
		expect( config.preferences[ 'citizen-feature-pure-black' ].section )
			.toBe( 'appearance' );
	} );

	it( 'should assign behavior prefs to behavior section', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );

		expect( config.preferences[ 'citizen-feature-autohide-navigation' ].section )
			.toBe( 'behavior' );
		expect( config.preferences[ 'citizen-feature-performance-mode' ].section )
			.toBe( 'behavior' );
	} );

	it( 'should use long-form options with labelMsg for skin-theme', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );
		const themeOpts = config.preferences[ 'skin-theme' ].options;

		expect( themeOpts ).toHaveLength( 3 );
		expect( themeOpts[ 0 ] ).toEqual( {
			value: 'os',
			labelMsg: 'citizen-theme-os-label'
		} );
	} );

	it( 'should build options from server-provided options array', () => {
		const serverConfig = {
			...ALL_ENABLED_SERVER_CONFIG,
			'citizen-feature-custom-width': {
				enabled: true, default: 'standard',
				options: [ 'standard', 'wide' ]
			}
		};
		const config = buildConfig( serverConfig );
		const opts = config.preferences[ 'citizen-feature-custom-width' ].options;

		expect( opts ).toHaveLength( 2 );
		expect( opts ).toEqual( [
			{ value: 'standard', labelMsg: 'citizen-feature-custom-width-standard-label' },
			{ value: 'wide', labelMsg: 'citizen-feature-custom-width-wide-label' }
		] );
	} );

	it( 'should include the expanded width option between standard and wide', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );
		const opts = config.preferences[ 'citizen-feature-custom-width' ].options;

		expect( opts ).toEqual( [
			{ value: 'standard', labelMsg: 'citizen-feature-custom-width-standard-label' },
			{ value: 'expanded', labelMsg: 'citizen-feature-custom-width-expanded-label' },
			{ value: 'wide', labelMsg: 'citizen-feature-custom-width-wide-label' },
			{ value: 'full', labelMsg: 'citizen-feature-custom-width-full-label' }
		] );
	} );

	it( 'should include type and visibilityCondition', () => {
		const config = buildConfig( ALL_ENABLED_SERVER_CONFIG );

		expect( config.preferences[ 'skin-theme' ].type ).toBe( 'radio' );
		expect( config.preferences[ 'citizen-feature-pure-black' ].type )
			.toBe( 'switch' );
		expect( config.preferences[ 'citizen-feature-pure-black' ].visibilityCondition )
			.toBe( 'dark-theme' );
	} );

	it( 'should skip unknown preferences from server config', () => {
		const serverConfig = {
			...ALL_ENABLED_SERVER_CONFIG,
			'unknown-gadget-pref': { enabled: true, default: '0', options: [ '0', '1' ] }
		};
		const config = buildConfig( serverConfig );

		expect( config.preferences ).not.toHaveProperty( 'unknown-gadget-pref' );
	} );
} );
