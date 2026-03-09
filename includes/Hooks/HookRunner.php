<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Skins\Citizen\SkinCitizen;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 *
 * @internal
 */
class HookRunner implements CitizenBeforePageHeaderHook {

	public function __construct(
		private readonly HookContainer $hookContainer
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onCitizenBeforePageHeader( SkinCitizen $skin, string &$html ): void {
		$this->hookContainer->run(
			'CitizenBeforePageHeader',
			[ $skin, &$html ],
			[ 'abortable' => false ]
		);
	}
}
