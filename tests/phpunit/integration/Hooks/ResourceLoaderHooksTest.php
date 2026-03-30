<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Tests\Integration\Hooks;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\Skins\Citizen\Hooks\ResourceLoaderHooks;
use MediaWikiIntegrationTestCase;

/**
 * @group Citizen
 */
class ResourceLoaderHooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers \MediaWiki\Skins\Citizen\Hooks\ResourceLoaderHooks
	 * @return void
	 */
	public function testCitizenResourceLoaderConfig() {
		$this->overrideConfigValues( [
			'CitizenOverflowInheritedClasses' => false,
			'CitizenOverflowNowrapClasses' => false,
		] );

		$rlCtxMock = $this->getMockBuilder( Context::class )->disableOriginalConstructor()->getMock();

		$config = ResourceLoaderHooks::getCitizenResourceLoaderConfig(
			$rlCtxMock,
			$this->getServiceContainer()->getMainConfig()
		);

		$this->assertArrayHasKey( 'wgCitizenPreferencesEnabled', $config );
		$this->assertArraySubmapSame( [
			'wgCitizenOverflowInheritedClasses' => false,
			'wgCitizenOverflowNowrapClasses' => false,
		], $config );
	}

	/**
	 * @covers \MediaWiki\Skins\Citizen\Hooks\ResourceLoaderHooks
	 * @return void
	 */
	public function testCitizenPreferencesResourceLoaderConfig() {
		$rlCtxMock = $this->getMockBuilder( Context::class )->disableOriginalConstructor()->getMock();

		$config = ResourceLoaderHooks::getCitizenPreferencesResourceLoaderConfig(
			$rlCtxMock,
			$this->getServiceContainer()->getMainConfig()
		);

		$this->assertArrayHasKey( 'wgCitizenPreferencesConfig', $config );
		$prefsConfig = $config['wgCitizenPreferencesConfig'];
		$this->assertArrayHasKey( 'skin-theme', $prefsConfig );
		$this->assertSame( 'os', $prefsConfig['skin-theme']['default'] );
		$this->assertTrue( $prefsConfig['skin-theme']['enabled'] );
	}

}
