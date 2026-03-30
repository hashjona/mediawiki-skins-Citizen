<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\Skins\Citizen\SkinCitizen;

/**
 * Hooks to run relating to the resource loader
 */
class ResourceLoaderHooks {

	/**
	 * Passes config variables to skins.citizen.scripts ResourceLoader module.
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getCitizenResourceLoaderConfig(
		RL\Context $context,
		Config $config
	) {
		$prefsConfig = SkinCitizen::getPreferencesConfig( $config );
		return [
			'wgCitizenPreferencesEnabled' => SkinCitizen::isPreferencesEnabled( $config ),
			'wgCitizenPreferencesConfig' => $prefsConfig,
			'wgCitizenOverflowInheritedClasses' => $config->get( 'CitizenOverflowInheritedClasses' ),
			'wgCitizenOverflowNowrapClasses' => $config->get( 'CitizenOverflowNowrapClasses' ),
		];
	}

	/**
	 * Passes config variables to skins.citizen.preferences ResourceLoader module.
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getCitizenPreferencesResourceLoaderConfig(
		RL\Context $context,
		Config $config
	) {
		return [
			'wgCitizenPreferencesConfig' => SkinCitizen::getPreferencesConfig( $config ),
		];
	}

	/**
	 * Passes config variables to skins.citizen.commandPalette ResourceLoader module.
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getCitizenCommandPaletteResourceLoaderConfig(
		RL\Context $context,
		Config $config
	) {
		$extensionRegistry = ExtensionRegistry::getInstance();

		return [
			'isMediaSearchExtensionEnabled' => $extensionRegistry->isLoaded( 'MediaSearch' ),
			'isSemanticMediaWikiEnabled' => $extensionRegistry->isLoaded( 'SemanticMediaWiki' ),
			'wgSearchSuggestCacheExpiry' => $config->get( MainConfigNames::SearchSuggestCacheExpiry ),
			'wgCitizenSearchNamespaceButtons' => $config->get( 'CitizenSearchNamespaceButtons' )
		];
	}
}
