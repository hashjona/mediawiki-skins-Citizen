---
title: Hooks
description: Hooks provided by Citizen for extensions, gadgets, and user scripts
---

# Hooks

Citizen exposes both JavaScript and PHP hooks for extensions, gadgets, and user scripts. This page is a quick reference.

## JavaScript hooks

Citizen fires several [`mw.hook`](https://doc.wikimedia.org/mediawiki-core/master/js/mw.hook.html) events that scripts can subscribe to.

### Hook reference

| Hook | Parameters | Description |
| :--- | :--- | :--- |
| `citizen.commandPalette.register` | `{ register }` | Register custom modes and commands in the [command palette](/customization/command-palette#extending-the-command-palette). `register( entry )` accepts a mode or command object. |
| `citizen.preferences.register` | `register` | Register custom sections and preferences in the [preferences panel](/customization/preferences#javascript-api). `register( config )` accepts a config object with `sections` and `preferences`. |
| `citizen.preferences.changed` | `featureName, value` | Fired when a user changes a preference. Use this to [react to changes](/customization/preferences#listening-for-changes) in real time. |

### Usage pattern

All Citizen hooks follow the standard `mw.hook` pattern:

```js
mw.hook( 'citizen.commandPalette.register' ).add( function ( data ) {
    data.register( myEntry );
} );
```

```js
mw.hook( 'citizen.preferences.register' ).add( function ( register ) {
    register( myConfig );
} );
```

```js
mw.hook( 'citizen.preferences.changed' ).add( function ( featureName, value ) {
    if ( featureName === 'my-feature' ) {
        // React to the change
    }
} );
```

### Timing

`mw.hook` replays previously fired data to late subscribers. You don't need to worry about load order — your `.add()` callback will receive the data regardless of whether the firing module has loaded yet.

### Deprecated hooks

| Hook | Replacement |
| :--- | :--- |
| `skins.citizen.commandPalette.registerCommand` | [`citizen.commandPalette.register`](/customization/command-palette#migration-from-previous-api) |

The old hook still works but logs a deprecation warning. See the [migration guide](/customization/command-palette#migration-from-previous-api) for details.

## PHP hooks

| Hook | Parameters | Description |
| :--- | :--- | :--- |
| `CitizenBeforePageHeader` | `SkinCitizen $skin, string &$html` | Append raw HTML immediately before the standard Citizen page header rendered by `templates/PageHeader.mustache`. Citizen wraps the injected markup in `.citizen-before-page-header > .citizen-before-page-header__inner` so it follows the active page width and gutters. |

Register the hook in your extension:

```json
{
  "HookHandlers": {
    "Main": {
      "class": "MediaWiki\\Extension\\Example\\HookHandler"
    }
  },
  "Hooks": {
    "CitizenBeforePageHeader": "Main"
  }
}
```

Implement the handler by appending to the provided HTML string:

```php
<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Example;

use MediaWiki\Skins\Citizen\Hooks\CitizenBeforePageHeaderHook;
use MediaWiki\Skins\Citizen\SkinCitizen;

class HookHandler implements CitizenBeforePageHeaderHook {
	public function onCitizenBeforePageHeader( SkinCitizen $skin, string &$html ): void {
		$html .= '<div class="example-banner">Example content</div>';
	}
}
```
