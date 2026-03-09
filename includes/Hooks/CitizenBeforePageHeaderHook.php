<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Hooks;

use MediaWiki\Skins\Citizen\SkinCitizen;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CitizenBeforePageHeader" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CitizenBeforePageHeaderHook {

	/**
	 * Allows extensions to inject HTML before the Citizen page header.
	 *
	 * @param SkinCitizen $skin The active Citizen skin instance
	 * @param string &$html Raw HTML to render before the page header
	 * @return void
	 */
	public function onCitizenBeforePageHeader( SkinCitizen $skin, string &$html ): void;
}
