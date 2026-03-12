<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Tests\Integration;

use MediaWiki\Skins\Citizen\SkinCitizen;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RequestContext;

/**
 * @group Citizen
 * @covers \MediaWiki\Skins\Citizen\SkinCitizen
 */
class SkinCitizenTest extends MediaWikiIntegrationTestCase {

	private function createSkinInstance( ?Title $title = null ): SkinCitizen {
		$skin = new SkinCitizen(
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getGenderCache(),
			$this->getServiceContainer()->getUserIdentityLookup(),
			$this->getServiceContainer()->getLanguageConverterFactory(),
			$this->getServiceContainer()->getLanguageNameUtils(),
			$this->getServiceContainer()->getPermissionManager(),
			$this->getServiceContainer()->getUserGroupManager(),
			$this->getServiceContainer()->getUrlUtils(),
			null,
			[
				'name' => 'Citizen',
			]
		);

		$skin->setContext(
			RequestContext::newExtraneousContext( $title ?? Title::makeTitle( NS_MAIN, 'SkinCitizenTest' ) )
		);

		return $skin;
	}

	private function getSkinHtmlClasses(): string {
		return $this->createSkinInstance()->getHtmlElementAttributes()['class'];
	}

	private function getSkinTemplateData(): array {
		return $this->createSkinInstance()->getTemplateData();
	}

	public function testThemeColorMetaTag(): void {
		$this->overrideConfigValues( [
			'CitizenThemeColor' => '#ffaabb',
		] );

		$skin = $this->createSkinInstance();
		$out = $skin->getOutput();
		$skin->initPage( $out );

		$this->assertContains(
			[ 'theme-color', '#ffaabb' ],
			$out->getMetaTags()
		);
	}

	public function testManifestLinkAddedWhenEnabled(): void {
		$this->overrideConfigValues( [
			'CitizenEnableManifest' => true,
		] );

		$skin = $this->createSkinInstance();
		$out = $skin->getOutput();
		$skin->initPage( $out );

		$expected = [
			'rel' => 'manifest',
			'href' => $this->getServiceContainer()->getUrlUtils()->expand(
				wfAppendQuery( wfScript( 'api' ), [ 'action' => 'appmanifest' ] ),
				PROTO_RELATIVE
			),
		];

		$this->assertContains( $expected, $out->getLinkTags() );
	}

	public function testManifestLinkNotAddedWhenDisabled(): void {
		$this->overrideConfigValues( [
			'CitizenEnableManifest' => false,
		] );

		$skin = $this->createSkinInstance();
		$out = $skin->getOutput();
		$skin->initPage( $out );

		$this->assertSame( [], $out->getLinkTags() );
	}

	public function testManifestLinkNotAddedOnPrivateWiki(): void {
		$this->overrideConfigValues( [
			'CitizenEnableManifest' => true,
			'GroupPermissions' => [ '*' => [ 'read' => false ] ],
		] );

		$skin = $this->createSkinInstance();
		$out = $skin->getOutput();
		$skin->initPage( $out );

		$this->assertSame( [], $out->getLinkTags() );
	}

	public function testCjkFontModuleEnabled(): void {
		$this->overrideConfigValues( [
			'CitizenEnableCJKFonts' => true,
		] );

		$skin = $this->createSkinInstance();

		$this->assertContains(
			'skins.citizen.styles.fonts.cjk',
			$skin->getOptions()['styles']
		);
	}

	public function testArFontModuleEnabled(): void {
		$this->overrideConfigValues( [
			'CitizenEnableARFonts' => true,
		] );

		$skin = $this->createSkinInstance();

		$this->assertContains(
			'skins.citizen.styles.fonts.ar',
			$skin->getOptions()['styles']
		);
	}

	public function testSetSkinThemeWithInvalidValue(): void {
		$this->overrideConfigValues( [
			'CitizenThemeDefault' => 'invalid-value',
		] );

		// Should not throw an undefined array key error
		$skin = $this->createSkinInstance();
		$attrs = $skin->getHtmlElementAttributes();
		$this->assertStringNotContainsString( 'skin-theme-clientpref-', $attrs['class'] );
	}

	public function testConfiguredClientPreferenceDefaultsAffectHtmlClasses(): void {
		$this->overrideConfigValues( [
			'CitizenPreferencesDefaults' => [
				'citizen-feature-custom-width' => 'wide',
				'citizen-feature-performance-mode' => '0',
			],
		] );

		$attrs = $this->getSkinHtmlClasses();

		$this->assertStringContainsString(
			'citizen-feature-custom-width-clientpref-wide',
			$attrs
		);
		$this->assertStringContainsString(
			'citizen-feature-performance-mode-clientpref-0',
			$attrs
		);
		$this->assertStringContainsString(
			'citizen-feature-custom-font-size-clientpref-standard',
			$attrs
		);
	}

	public function testInvalidClientPreferenceDefaultsFallBackToBuiltInValues(): void {
		$this->overrideConfigValues( [
			'CitizenPreferencesDefaults' => [
				'citizen-feature-custom-width' => 'invalid',
			],
		] );

		$attrs = $this->getSkinHtmlClasses();

		$this->assertStringContainsString(
			'citizen-feature-custom-width-clientpref-standard',
			$attrs
		);
		$this->assertStringNotContainsString(
			'citizen-feature-custom-width-clientpref-invalid',
			$attrs
		);
	}

	public function testCollapsibleSectionsBodyClass(): void {
		$title = Title::newFromText( 'CollapsibleSectionsTest' );
		RequestContext::resetMain();
		RequestContext::getMain()->setTitle( $title );

		$this->overrideConfigValues( [
			'CitizenEnableCollapsibleSections' => true,
		] );

		$skin = $this->createSkinInstance();

		$this->assertContains(
			'citizen-sections-enabled',
			$skin->getOptions()['bodyClasses']
		);
	}

	public function testMainPageBodyClass(): void {
		$skin = $this->createSkinInstance( Title::newMainPage() );

		$this->assertContains(
			'citizen-mainpage',
			$skin->getOptions()['bodyClasses']
		);
	}

	public function testBeforePageHeaderHtmlDefaultsToEmptyString(): void {
		$skin = $this->createSkinInstance();

		$this->assertSame( '', $skin->getTemplateData()['html-before-page-header'] );
	}

	public function testBeforePageHeaderHookAddsHtml(): void {
		$this->setTemporaryHook(
			'CitizenBeforePageHeader',
			static function ( SkinCitizen $skin, string &$html ): void {
				$html .= '<div class="citizen-before-page-header-test">Injected</div>';
			}
		);

		$skin = $this->createSkinInstance();

		$this->assertSame(
			'<div class="citizen-before-page-header-test">Injected</div>',
			$skin->getTemplateData()['html-before-page-header']
		);
	}

	public function testTemplateDataExposesEnabledPreferencesFlag(): void {
		$this->overrideConfigValues( [
			'CitizenEnablePreferences' => true,
		] );

		$this->assertTrue( $this->getSkinTemplateData()['is-preferences-enabled'] );
	}

	public function testTemplateDataExposesDisabledPreferencesFlag(): void {
		$this->overrideConfigValues( [
			'CitizenEnablePreferences' => false,
		] );

		$this->assertFalse( $this->getSkinTemplateData()['is-preferences-enabled'] );
	}

	public function testTemplateDataExposesConfiguredLogoVisibilityFlags(): void {
		$this->overrideConfigValues( [
			'CitizenLogoVisibleIn' => [ 'home', 'footer' ],
		] );

		$templateData = $this->getSkinTemplateData();

		$this->assertTrue( $templateData['is-logo-visible-in-home'] );
		$this->assertFalse( $templateData['is-logo-visible-in-drawer'] );
		$this->assertTrue( $templateData['is-logo-visible-in-footer'] );
	}

	public function testTemplateDataFallsBackToBuiltInLogoVisibilityDefaults(): void {
		$this->overrideConfigValues( [
			'CitizenLogoVisibleIn' => null,
		] );

		$templateData = $this->getSkinTemplateData();

		$this->assertTrue( $templateData['is-logo-visible-in-home'] );
		$this->assertTrue( $templateData['is-logo-visible-in-drawer'] );
		$this->assertTrue( $templateData['is-logo-visible-in-footer'] );
	}

	public function testTemplateDataExposesConfiguredWordmarkVisibilityFlagsAndWidths(): void {
		$this->overrideConfigValues( [
			'CitizenWordmarkVisibleIn' => [ 'footer' ],
			'CitizenWordmarkWidths' => [
				'drawer' => 'clamp( 12rem, 30vw, 18rem )',
				'footer' => '20rem',
			],
		] );

		$templateData = $this->getSkinTemplateData();

		$this->assertFalse( $templateData['is-wordmark-visible-in-drawer'] );
		$this->assertTrue( $templateData['is-wordmark-visible-in-footer'] );
		$this->assertSame(
			'clamp( 12rem, 30vw, 18rem )',
			$templateData['data-wordmark-drawer']['wordmark-width']
		);
		$this->assertSame(
			'20rem',
			$templateData['data-wordmark-footer']['wordmark-width']
		);
	}

	public function testTemplateDataFallsBackToBuiltInWordmarkVisibilityDefaults(): void {
		$this->overrideConfigValues( [
			'CitizenWordmarkVisibleIn' => null,
		] );

		$templateData = $this->getSkinTemplateData();

		$this->assertTrue( $templateData['is-wordmark-visible-in-drawer'] );
		$this->assertTrue( $templateData['is-wordmark-visible-in-footer'] );
	}

	public function testInvalidWordmarkWidthsAreIgnored(): void {
		$this->overrideConfigValues( [
			'CitizenWordmarkWidths' => [
				'drawer' => 'javascript:alert(1)',
				'footer' => '',
			],
		] );

		$templateData = $this->getSkinTemplateData();

		$this->assertNull( $templateData['data-wordmark-drawer']['wordmark-width'] );
		$this->assertNull( $templateData['data-wordmark-footer']['wordmark-width'] );
	}
}
