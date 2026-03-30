// @vitest-environment jsdom
const mw = require( '../mocks/mw.js' );
globalThis.mw = mw;

// Add MediaWiki's createMwApp to Vue so init.js can call it.
// In production, ResourceLoader patches this onto the Vue object.
const Vue = require( 'vue' );
const mockMount = vi.fn();
const mockProvide = vi.fn();
Vue.createMwApp = vi.fn( () => ( { provide: mockProvide, mount: mockMount } ) );

// Mutable mock — modify the config between tests
const configMock = require( '../mocks/preferencesConfig.js' );

let initApp;

beforeAll( async () => {
	const mod = await import(
		'../../../resources/skins.citizen.preferences/init.js'
	);
	( { initApp } = mod.default || mod );
} );

afterEach( () => {
	vi.restoreAllMocks();
	// Re-attach createMwApp after restoreAllMocks clears the spy
	Vue.createMwApp = vi.fn( () => ( { provide: mockProvide, mount: mockMount } ) );
	mockMount.mockClear();
	mockProvide.mockClear();
	document.body.innerHTML = '';
	// Reset register hook between tests
	mw.hook( 'citizen.preferences.register' )._reset();
} );

describe( 'initApp', () => {
	it( 'should not crash when mount point is missing', () => {
		expect( () => initApp() ).not.toThrow();
	} );

	it( 'should mount Vue app at #citizen-preferences-content', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		initApp();

		expect( Vue.createMwApp ).toHaveBeenCalled();
		expect( mockMount ).toHaveBeenCalledWith(
			document.getElementById( 'citizen-preferences-content' )
		);
	} );

	it( 'should provide config built from server PreferencesConfig', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		initApp();

		expect( mockProvide ).toHaveBeenCalledWith(
			'preferencesConfig',
			expect.objectContaining( {
				sections: expect.objectContaining( {
					appearance: expect.any( Object ),
					behavior: expect.any( Object )
				} ),
				preferences: expect.any( Object )
			} )
		);
	} );

	it( 'should exclude disabled preferences from config', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';
		const original = configMock.wgCitizenPreferencesConfig[ 'citizen-feature-pure-black' ];
		configMock.wgCitizenPreferencesConfig[ 'citizen-feature-pure-black' ] = {
			...original,
			enabled: false
		};

		initApp();

		const configCall = mockProvide.mock.calls.find(
			( args ) => args[ 0 ] === 'preferencesConfig'
		);
		expect( configCall[ 1 ].preferences ).not.toHaveProperty( 'citizen-feature-pure-black' );

		// Restore
		configMock.wgCitizenPreferencesConfig[ 'citizen-feature-pure-black' ] = original;
	} );

	it( 'should provide themeDefault from server config', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		initApp();

		expect( mockProvide ).toHaveBeenCalledWith( 'themeDefault', 'os' );
	} );

	it( 'should fire citizen.preferences.register hook', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		initApp();

		const hook = mw.hook( 'citizen.preferences.register' );
		expect( hook.fire ).toHaveBeenCalledWith( expect.any( Function ) );
	} );

	it( 'should replay register function to late subscribers', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		initApp();

		// Add subscriber AFTER fire() has already been called
		const registerSpy = vi.fn();
		mw.hook( 'citizen.preferences.register' ).add( ( register ) => {
			registerSpy( register );
		} );

		expect( registerSpy ).toHaveBeenCalledWith( expect.any( Function ) );
	} );

	it( 'should warn and skip malformed registrations', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		mw.hook( 'citizen.preferences.register' ).add( ( register ) => {
			register( null );
			register( 'garbage' );
			register( [ 'an', 'array' ] );
		} );

		initApp();

		expect( mw.log.warn ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'should accept registrations via the register function', () => {
		document.body.innerHTML = '<div id="citizen-preferences-content"></div>';

		// Add a listener before init
		const registerSpy = vi.fn();
		mw.hook( 'citizen.preferences.register' ).add( ( register ) => {
			register( {
				sections: { gadget: { label: 'Gadgets' } },
				preferences: {
					'my-gadget': {
						section: 'gadget',
						options: [ '0', '1' ],
						label: 'My Gadget'
					}
				}
			} );
			registerSpy();
		} );

		initApp();

		expect( registerSpy ).toHaveBeenCalled();
		// Verify the config provided to the Vue app includes the registered preference
		const configCall = mockProvide.mock.calls.find(
			( args ) => args[ 0 ] === 'preferencesConfig'
		);
		expect( configCall[ 1 ].preferences ).toHaveProperty( 'my-gadget' );
		expect( configCall[ 1 ].sections ).toHaveProperty( 'gadget' );
	} );
} );
