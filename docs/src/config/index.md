---
title: Configuration
description: MediaWiki skin configuration for the Citizen skin.
---

# Configuration

::: tip Citizen works out of the box without any configurations!

The skin configs allow more customization on the specific features in the skin.
:::

## Appearance

### `$wgCitizenHeaderPosition`

Determines where the site header appears on desktop screens.

```php [LocalSettings.php]
$wgCitizenHeaderPosition = 'left';
```

**Values**: `'left'`, `'right'`, `'top'`, `'bottom'`

### `$wgCitizenLogoVisibleIn`

Controls where the icon logo is rendered in the skin chrome.
By default, Citizen shows it in all supported placements.
When set, the array is treated as the exact list of placements where the logo should appear.

```php [LocalSettings.php]
$wgCitizenLogoVisibleIn = [ 'home', 'drawer', 'footer' ];
```

**Values**:

- `'home'`: Show the logo image in the home button
- `'drawer'`: Show the logo image in the drawer header
- `'footer'`: Show the logo image in the footer

When a placement is omitted, only the logo image is hidden there. The wordmark and surrounding content still render normally.

### `$wgCitizenWordmarkVisibleIn`

Controls where the wordmark is rendered in the skin chrome.
By default, Citizen shows it in all supported placements.
When set, the array is treated as the exact list of placements where the wordmark should appear.

```php [LocalSettings.php]
$wgCitizenWordmarkVisibleIn = [ 'drawer', 'footer' ];
```

**Values**:

- `'drawer'`: Show the wordmark in the drawer header
- `'footer'`: Show the wordmark in the footer

When a placement is omitted, only the wordmark is hidden there. The icon logo and surrounding content still render normally.

### `$wgCitizenWordmarkWidths`

Controls the rendered width of image wordmarks in the skin chrome.
Values are CSS widths, and the image height is always `auto` to preserve the aspect ratio.
Citizen does not use `wgLogos.wordmark.width` or `wgLogos.wordmark.height` for rendering.

```php [LocalSettings.php]
$wgCitizenWordmarkWidths = [
    'drawer' => '14rem',
    'footer' => '20rem'
];
```

**Values**:

- `'drawer'`: CSS width for the drawer wordmark image
- `'footer'`: CSS width for the footer wordmark image

This only affects image wordmarks. Text fallbacks still use the normal typography styles.

Example setup:

```php [LocalSettings.php]
$wgCitizenLogoVisibleIn = [ 'home' ];
$wgCitizenWordmarkVisibleIn = [ 'drawer', 'footer' ];
$wgCitizenWordmarkWidths = [
    'drawer' => '14rem',
    'footer' => '20rem'
];
```

### `$wgCitizenEnableCollapsibleSections`

Allows users to collapse and expand sections on pages, making long articles easier to navigate.

```php [LocalSettings.php]
$wgCitizenEnableCollapsibleSections = true;
```

**Values**: `true`, `false`

### `$wgCitizenShowPageTools`

Controls who can see the page tools menu (edit, history, etc.).

```php [LocalSettings.php]
$wgCitizenShowPageTools = true;
```

**Values**:

- `true`: Always visible to everyone
- `'login'`: Visible only to logged-in users
- `'permission'`: Visible only to users with specific permissions

### `$wgCitizenGlobalToolsPortlet`

The ID of the menu where global tools (like user preferences) should appear. Leave this empty to use the default location.

```php [LocalSettings.php]
$wgCitizenGlobalToolsPortlet = '';
```

### `$wgCitizenEnableDrawerSiteStats`

Shows site statistics, such as the total page count, at the header of the side drawer menu.

```php [LocalSettings.php]
$wgCitizenEnableDrawerSiteStats = true;
```

**Values**: `true`, `false`

### `$wgCitizenThemeColor`

Sets the color of the browser address bar on mobile devices to match your brand.

```php [LocalSettings.php]
$wgCitizenThemeColor = '#0d0e12';
```

**Values**: Hex color code

### `$wgCitizenEnableARFonts`

Loads the "Noto Naskh Arabic" font, improving readability for wikis that use Arabic script.

```php [LocalSettings.php]
$wgCitizenEnableARFonts = false;
```

**Values**: `true`, `false`

### `$wgCitizenEnableCJKFonts`

Loads the "Noto Sans CJK" font, improving readability for wikis that use Chinese, Japanese, or Korean characters.

```php [LocalSettings.php]
$wgCitizenEnableCJKFonts = false;
```

**Values**: `true`, `false`

### `$wgCitizenPreferencesConfig`

Unified configuration for Citizen's [preferences panel](/customization/preferences). Each key is a preference name with three fields: `enabled` (whether the UI control is shown), `default` (the default value), and `options` (allowed values).

The defaults are defined in `skin.json` and work out of the box. Use `LocalSettings.php` to override specific settings:

```php [LocalSettings.php]
// Set default theme to dark
$wgCitizenPreferencesConfig['skin-theme']['default'] = 'night';

// Default to expanded width
$wgCitizenPreferencesConfig['citizen-feature-custom-width']['default'] = 'expanded';

// Only allow standard and wide widths
$wgCitizenPreferencesConfig['citizen-feature-custom-width']['options'] = ['standard', 'wide'];

// Hide font size setting (default still applies)
$wgCitizenPreferencesConfig['citizen-feature-custom-font-size']['enabled'] = false;
```

**Fields**:

- `enabled` (bool): Whether the UI control appears in the preferences panel. When `false`, the `default` still applies as a CSS class but users cannot change it. Default: `true`
- `default` (string): The default value for the preference. Must be one of the `options` values
- `options` (array): Allowed values. Remove values from this array to hide them from the UI

**Built-in preferences**:

| Preference | Default | Options |
| :--- | :--- | :--- |
| `skin-theme` | `'os'` | `'os'`, `'day'`, `'night'` |
| `citizen-feature-custom-font-size` | `'standard'` | `'small'`, `'standard'`, `'large'`, `'xlarge'` |
| `citizen-feature-custom-width` | `'standard'` | `'standard'`, `'expanded'`, `'wide'`, `'full'` |
| `citizen-feature-pure-black` | `'0'` | `'0'`, `'1'` |
| `citizen-feature-image-dimming` | `'0'` | `'0'`, `'1'` |
| `citizen-feature-autohide-navigation` | `'1'` | `'0'`, `'1'` |
| `citizen-feature-performance-mode` | `'1'` | `'0'`, `'1'` |

When all preferences have `enabled: false`, the preferences panel is hidden entirely.
Invalid defaults fall back to the first value in `options`.
When the preferences panel is enabled, any saved browser preferences still override these defaults for that user.

### `$wgCitizenOverflowInheritedClasses`

A list of CSS classes that should be preserved when tables or images are wrapped in a scrollable container.

```php [LocalSettings.php]
$wgCitizenOverflowInheritedClasses = [ 'floatleft', 'floatright' ];
```

### `$wgCitizenOverflowNowrapClasses`

A list of CSS classes that prevent an element from being wrapped in a scrollable container. Use this for elements that should always display fully.

```php [LocalSettings.php]
$wgCitizenOverflowNowrapClasses = [
    'noresize',
    'citizen-table-nowrap',
    'cargoDynamicTable',
    'dataTable',
    'smw-datatable',
    'srf-datatable'
];
```

### `$wgCitizenTableOfContentsCollapseAtCount`

The minimum number of headings required before the sticky table of contents automatically collapses its sub-sections to save space.

```php [LocalSettings.php]
$wgCitizenTableOfContentsCollapseAtCount = 28;
```

## Webapp manifest

### `$wgCitizenEnableManifest`

Enables the [web app manifest](https://developer.mozilla.org/en-US/docs/Web/Manifest), allowing users to install your wiki as a standalone app on their device.

```php [LocalSettings.php]
$wgCitizenEnableManifest = true;
```

**Values**: `true`, `false`

### `$wgCitizenManifestOptions`

Customizes the web app manifest settings, such as the app name, colors, and icons.

::: details View default configuration

```php
$wgCitizenManifestOptions = [
    'background_color' => '#0d0e12',
    'description' => '',
    'short_name' => '',
    'theme_color' => "#0d0e12",
    'icons' => [],
];
```

:::
