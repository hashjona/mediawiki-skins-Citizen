<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen;

use BadMethodCallException;
use MediaWiki\Cache\GenderCache;
use MediaWiki\Config\Config;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Skins\Citizen\Components\CitizenComponentBodyContent;
use MediaWiki\Skins\Citizen\Components\CitizenComponentFooter;
use MediaWiki\Skins\Citizen\Components\CitizenComponentMainMenu;
use MediaWiki\Skins\Citizen\Components\CitizenComponentPageFooter;
use MediaWiki\Skins\Citizen\Components\CitizenComponentPageHeading;
use MediaWiki\Skins\Citizen\Components\CitizenComponentPageSidebar;
use MediaWiki\Skins\Citizen\Components\CitizenComponentPageTools;
use MediaWiki\Skins\Citizen\Components\CitizenComponentSiteStats;
use MediaWiki\Skins\Citizen\Components\CitizenComponentStickyHeader;
use MediaWiki\Skins\Citizen\Components\CitizenComponentTableOfContents;
use MediaWiki\Skins\Citizen\Components\CitizenComponentUserInfo;
use MediaWiki\Skins\Citizen\Hooks\HookRunner;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\Utils\UrlUtils;
use MobileContext;
use SkinMustache;
use SkinTemplate;

/**
 * Skin subclass for Citizen
 * @ingroup Skins
 */
class SkinCitizen extends SkinMustache {

	private const CLIENTPREFS_THEME_MAP = [
		'auto' => 'os',
		'light' => 'day',
		'dark' => 'night'
	];

	private const DEFAULT_CLIENT_PREFS = [
		'citizen-feature-autohide-navigation' => '1',
		'citizen-feature-image-dimming' => '0',
		'citizen-feature-pure-black' => '0',
		'citizen-feature-custom-font-size' => 'standard',
		'citizen-feature-custom-width' => 'standard',
		'citizen-feature-performance-mode' => '1',
	];

	private const CLIENT_PREFS_ALLOWED_VALUES = [
		'citizen-feature-autohide-navigation' => [ '0', '1' ],
		'citizen-feature-image-dimming' => [ '0', '1' ],
		'citizen-feature-pure-black' => [ '0', '1' ],
		'citizen-feature-custom-font-size' => [ 'small', 'standard', 'large', 'xlarge' ],
		'citizen-feature-custom-width' => [ 'standard', 'expanded', 'wide', 'full' ],
		'citizen-feature-performance-mode' => [ '0', '1' ],
	];

	private const LOGO_PLACEMENTS = [ 'home', 'drawer', 'footer' ];
	private const WORDMARK_PLACEMENTS = [ 'drawer', 'footer' ];

	private const OPTIONAL_FONT_MODULES = [
		'CitizenEnableCJKFonts' => 'skins.citizen.styles.fonts.cjk',
		'CitizenEnableARFonts' => 'skins.citizen.styles.fonts.ar',
	];

	/** For caching purposes */
	private ?array $languages = null;

	/**
	 * Overrides template, styles and scripts module
	 *
	 * @inheritDoc
	 */
	public function __construct(
		private readonly UserFactory $userFactory,
		private readonly GenderCache $genderCache,
		private readonly UserIdentityLookup $userIdentityLookup,
		private readonly LanguageConverterFactory $languageConverterFactory,
		private readonly LanguageNameUtils $languageNameUtils,
		private readonly PermissionManager $permissionManager,
		private readonly UserGroupManager $userGroupManager,
		private readonly UrlUtils $urlUtils,
		// @phan-suppress-next-line PhanUndeclaredTypeParameter,PhanUndeclaredTypeProperty
		private readonly ?MobileContext $mfContext,
		array $options = []
	) {
		if ( !isset( $options['name'] ) ) {
			$options['name'] = 'citizen';
		}

		// Add skin-specific features that only modify the $options array.
		// OutputPage modifications (HTML classes, metadata) are deferred to
		// initPage() and getHtmlElementAttributes() so that they only run
		// when Citizen is the active rendering skin. Without this separation,
		// Special:Preferences pollutes other skins' OutputPage when it
		// instantiates all registered skins to gather their configuration.
		$this->buildSkinFeatures( $options );
		parent::__construct( $options );
	}

	/**
	 * @inheritDoc
	 */
	public function initPage( OutputPage $out ): void {
		parent::initPage( $out );
		$this->addMetadata( $out, $this->getConfig() );
	}

	/**
	 * @inheritDoc
	 */
	public function getHtmlElementAttributes(): array {
		$attrs = parent::getHtmlElementAttributes();
		$config = $this->getConfig();
		$classes = [];

		// Theme
		$theme = $config->get( 'CitizenThemeDefault' );
		if ( isset( self::CLIENTPREFS_THEME_MAP[$theme] ) ) {
			$classes[] = 'skin-theme-clientpref-' . self::CLIENTPREFS_THEME_MAP[$theme];
		}

		// Default client preferences
		foreach ( $this->getClientPreferenceDefaults( $config ) as $feature => $value ) {
			$classes[] = $feature . '-clientpref-' . $value;
		}

		// Header position
		$headerPosition = $config->get( 'CitizenHeaderPosition' );
		if ( !in_array( $headerPosition, [ 'left', 'right', 'top', 'bottom' ], true ) ) {
			$headerPosition = 'left';
		}
		$classes[] = 'citizen-header-position-' . $headerPosition;

		$attrs['class'] = trim( $attrs['class'] . ' ' . implode( ' ', $classes ) );
		return $attrs;
	}

	/**
	 * Ensure onSkinTemplateNavigation runs after all SkinTemplateNavigation hooks
	 * @see T287622
	 *
	 * @param SkinTemplate $skin
	 * @param array &$content_navigation
	 */
	protected function runOnSkinTemplateNavigationHooks( SkinTemplate $skin, &$content_navigation ): void {
		parent::runOnSkinTemplateNavigationHooks( $skin, $content_navigation );
		Hooks\SkinHooks::onSkinTemplateNavigation( $skin, $content_navigation );
	}

	/**
	 * Calls getLanguages with caching.
	 * From Vector 2022
	 */
	protected function getLanguagesCached(): array {
		if ( $this->languages === null ) {
			$this->languages = $this->getLanguages();
		}
		return $this->languages;
	}

	private function getBeforePageHeaderHtml(): string {
		$html = '';
		( new HookRunner( $this->getHookContainer() ) )->onCitizenBeforePageHeader( $this, $html );

		return $html;
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateData(): array {
		$parentData = parent::getTemplateData();

		$config = $this->getConfig();
		$localizer = $this->getContext();
		$lang = $this->getLanguage();
		$out = $this->getOutput();
		$title = $this->getTitle();
		$user = $this->getUser();

		[ $sidebar, $pageToolsMenu ] = $this->extractPageToolsFromSidebar(
			$parentData['data-portlets-sidebar']
		);

		$components = [
			'data-footer' => new CitizenComponentFooter(
				$localizer,
				$parentData['data-footer']
			),
			'data-main-menu' => new CitizenComponentMainMenu( $sidebar ),
			'data-page-footer' => new CitizenComponentPageFooter(
				$localizer,
				$parentData['data-footer']['data-info']
			),
			'data-page-heading' => new CitizenComponentPageHeading(
				$this->userFactory,
				$this->genderCache,
				$this->userIdentityLookup,
				$this->languageConverterFactory,
				$lang,
				$localizer,
				$out,
				$title,
				$parentData['html-title-heading']
			),
			'data-page-sidebar' => new CitizenComponentPageSidebar(
				$localizer,
				$title,
				$parentData['data-last-modified']
			),
			'data-page-tools' => new CitizenComponentPageTools(
				$config,
				$localizer,
				$title,
				$user,
				$this->permissionManager,
				count( $this->getLanguagesCached() ),
				$pageToolsMenu,
				// These portlets can be unindexed
				$parentData['data-portlets']['data-languages'] ?? [],
				$parentData['data-portlets']['data-variants'] ?? []
			),
			'data-site-stats' => new CitizenComponentSiteStats(
				$config,
				$localizer,
				$lang,
				$this->languageNameUtils
			),
			'data-user-info' => new CitizenComponentUserInfo(
				$this->userGroupManager,
				$lang,
				$localizer,
				$title,
				$user,
				$parentData['data-portlets']['data-user-page']
			),
			'data-sticky-header' => new CitizenComponentStickyHeader(
				$this->isVisualEditorTabPositionFirst( $parentData['data-portlets']['data-views'] )
			),
			'data-body-content' => new CitizenComponentBodyContent(
				$parentData['html-body-content'],
				$this->shouldMakeSections( $config, $title )
			),
			'data-toc' => new CitizenComponentTableOfContents(
				$parentData['data-toc'] ?? [],
				$localizer,
				$config
			),
		];

		foreach ( $components as $key => $component ) {
			$parentData[$key] = $component->getTemplateData();
		}

		// TODO: Pass tagline through the component instead of reaching across template data
		$parentData['data-sticky-header']['html-sticky-header-tagline'] =
			$this->prepareStickyHeaderTagline( $parentData['data-page-heading']['html-tagline'] );

		$this->applyBrandingTemplateData( $parentData, $config );
		$parentData['is-preferences-enabled'] = $config->get( 'CitizenEnablePreferences' );

		$parentData['html-before-page-header'] = $this->getBeforePageHeaderHtml();

		$parentData['toc-enabled'] = !empty( $parentData['data-toc'][ 'array-sections' ] );
		if ( $parentData['toc-enabled'] ) {
			// This body class depends on template data so it can't move to
			// getHtmlElementAttributes(). Safe here because getTemplateData()
			// only runs for the active rendering skin.
			$out->addBodyClasses( 'citizen-toc-enabled' );
		}

		return $parentData;
	}

	/**
	 * Extracts the page tools menu from the sidebar and returns both.
	 * From Vector 2022
	 *
	 * @return array [ $sidebar, $pageToolsMenu ]
	 */
	private function extractPageToolsFromSidebar( array $sidebar ): array {
		$restPortlets = $sidebar[ 'array-portlets-rest' ] ?? [];
		$pageToolsMenu = [];
		$toolboxMenuIndex = array_search(
			CitizenComponentPageTools::TOOLBOX_ID,
			array_column(
				$restPortlets,
				'id'
			)
		);

		if ( $toolboxMenuIndex !== false ) {
			$pageToolsMenu = array_splice( $restPortlets, $toolboxMenuIndex, 1 );
			$sidebar['array-portlets-rest'] = $restPortlets;
		}

		return [ $sidebar, $pageToolsMenu ];
	}

	/**
	 * @return array<string, string>
	 */
	private function getClientPreferenceDefaults( Config $config ): array {
		$defaults = self::DEFAULT_CLIENT_PREFS;
		$configuredDefaults = $config->get( 'CitizenPreferencesDefaults' );

		if ( !is_array( $configuredDefaults ) ) {
			return $defaults;
		}

		foreach ( self::CLIENT_PREFS_ALLOWED_VALUES as $feature => $allowedValues ) {
			$configuredValue = $configuredDefaults[$feature] ?? null;
			if ( is_string( $configuredValue ) && in_array( $configuredValue, $allowedValues, true ) ) {
				$defaults[$feature] = $configuredValue;
			}
		}

		return $defaults;
	}

	/**
	 * @param array<string, mixed> &$templateData
	 */
	private function applyBrandingTemplateData( array &$templateData, Config $config ): void {
		// TODO: Pass the home icon through the component instead of injecting into logos data
		$templateData['data-logos']['icon-home'] = 'home';

		$logoVisibility = $this->getLogoVisibilityMap( $config );
		$wordmarkVisibility = $this->getWordmarkVisibilityMap( $config );
		$wordmarkWidths = $this->getWordmarkWidthMap( $config );

		foreach ( $logoVisibility as $placement => $isVisible ) {
			$templateData["is-logo-visible-in-$placement"] = $isVisible;
		}

		foreach ( self::WORDMARK_PLACEMENTS as $placement ) {
			$templateData["is-wordmark-visible-in-$placement"] = $wordmarkVisibility[$placement];
			$templateData["data-wordmark-$placement"] = $this->getWordmarkTemplateData(
				$templateData['data-logos'],
				$wordmarkWidths[$placement]
			);
		}

		$templateData['has-drawer-siteinfo'] =
			$wordmarkVisibility['drawer'] || !empty( $templateData['data-site-stats'] );
		$templateData['has-footer-sitetitle'] =
			$logoVisibility['footer'] || $wordmarkVisibility['footer'];
	}

	/**
	 * @return array{home: bool, drawer: bool, footer: bool}
	 */
	private function getLogoVisibilityMap( Config $config ): array {
		return $this->getPlacementVisibilityMap( $config, 'CitizenLogoVisibleIn', self::LOGO_PLACEMENTS );
	}

	/**
	 * @return array{drawer: bool, footer: bool}
	 */
	private function getWordmarkVisibilityMap( Config $config ): array {
		return $this->getPlacementVisibilityMap( $config, 'CitizenWordmarkVisibleIn', self::WORDMARK_PLACEMENTS );
	}

	/**
	 * @return array{drawer: ?string, footer: ?string}
	 */
	private function getWordmarkWidthMap( Config $config ): array {
		return $this->getConfiguredCssSizeMap(
			$config->get( 'CitizenWordmarkWidths' ),
			self::WORDMARK_PLACEMENTS
		);
	}

	/**
	 * @param array<string, mixed> $dataLogos
	 * @return array{wordmark: mixed, wordmark-width: ?string}
	 */
	private function getWordmarkTemplateData( array $dataLogos, ?string $width ): array {
		return [
			'wordmark' => $dataLogos['wordmark'] ?? false,
			'wordmark-width' => $width,
		];
	}

	/**
	 * @param string[] $placements
	 * @return array<string, bool>
	 */
	private function getPlacementVisibilityMap(
		Config $config,
		string $configKey,
		array $placements
	): array {
		$visibility = array_fill_keys( $placements, false );
		$visibleIn = $config->get( $configKey );

		if ( !is_array( $visibleIn ) ) {
			$visibleIn = $placements;
		}

		foreach ( $visibleIn as $placement ) {
			if ( is_string( $placement ) && array_key_exists( $placement, $visibility ) ) {
				$visibility[$placement] = true;
			}
		}

		return $visibility;
	}

	/**
	 * @param string[] $placements
	 * @return array<string, ?string>
	 */
	private function getConfiguredCssSizeMap( mixed $configuredValues, array $placements ): array {
		$values = array_fill_keys( $placements, null );

		if ( !is_array( $configuredValues ) ) {
			return $values;
		}

		foreach ( $placements as $placement ) {
			$values[$placement] = $this->sanitizeCssSize( $configuredValues[$placement] ?? null );
		}

		return $values;
	}

	private function sanitizeCssSize( mixed $value ): ?string {
		if ( !is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		if ( $value === '' ) {
			return null;
		}

		if ( !preg_match( '/^[a-zA-Z0-9.%(),+\\-\\/\\s]+$/', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Check whether Visual Editor Tab Position is first
	 * From Vector 2022
	 */
	private function isVisualEditorTabPositionFirst( array $dataViews ): bool {
		foreach ( $dataViews[ 'array-items' ] as $item ) {
			if ( $item[ 'name' ] === 've-edit' ) {
				return true;
			}
			if ( $item[ 'name' ] === 'edit' ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Check if collapsible sections should be made
	 */
	private function shouldMakeSections( Config $config, Title $title ): bool {
		if (
			$config->get( 'CitizenEnableCollapsibleSections' ) === false ||
			!$title->canExist() ||
			$title->isMainPage() ||
			!$title->isContentPage() ||
			$title->getContentModel() !== CONTENT_MODEL_WIKITEXT
		) {
			return false;
		}

		// If MF is installed, check if page is in mobile view and let MF do the formatting
		// @phan-suppress-next-line PhanUndeclaredClassMethod MobileFrontend is an optional dependency
		return $this->mfContext === null || !$this->mfContext->shouldDisplayMobileView();
	}

	/**
	 * Prepare the tagline for the sticky header
	 * Replace <a> elements with <span> elements because
	 * you can't nest <a> elements in <a> elements
	 */
	private static function prepareStickyHeaderTagline( string $tagline ): string {
		return preg_replace( '/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/', '<span>$2</span>', $tagline ) ?? $tagline;
	}

	/**
	 * Set up skin features that modify the constructor $options array.
	 * Only bodyClasses and styles belong here — OutputPage modifications
	 * are handled by initPage() and getHtmlElementAttributes().
	 */
	private function buildSkinFeatures( array &$options ): void {
		$config = $this->getConfig();
		$title = $this->getOutput()->getTitle();

		if ( $title !== null ) {
			if ( $title->isMainPage() ) {
				$options['bodyClasses'][] = 'citizen-mainpage';
			}

			// Collapsible sections
			if (
				$config->get( 'CitizenEnableCollapsibleSections' ) === true &&
				$title->isContentPage()
			) {
				$options['bodyClasses'][] = 'citizen-sections-enabled';
			}
		}

		foreach ( self::OPTIONAL_FONT_MODULES as $configKey => $module ) {
			if ( $config->get( $configKey ) === true ) {
				$options['styles'][] = $module;
			}
		}
	}

	/**
	 * Adds metadata to the output page (theme-color and manifest)
	 */
	private function addMetadata( OutputPage $out, Config $config ): void {
		$out->addMeta( 'theme-color', $config->get( 'CitizenThemeColor' ) );

		if (
			$config->get( 'CitizenEnableManifest' ) !== true ||
			$config->get( MainConfigNames::GroupPermissions )['*']['read'] !== true
		) {
			return;
		}

		try {
			$href = $this->urlUtils->expand( wfAppendQuery( wfScript( 'api' ),
					[ 'action' => 'appmanifest' ] ), PROTO_RELATIVE );
		} catch ( BadMethodCallException ) {
			$href = '';
		}

		$out->addLink( [
			'rel' => 'manifest',
			'href' => $href,
		] );
	}

}
