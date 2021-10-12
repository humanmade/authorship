# Changelog

## v0.5.0

- Support enqueuing scripts in the page `<head>` by passing `'in-footer' => false` in options array.
- Introduce `get_active_manifest()` function to return the first available manifest in a list.

## v0.4.1

- Fix bug where admin namespace was not loaded.

## v0.4.0

- **Breaking**: Remove undocumented `Asset_Loader\is_development` method.
- **Breaking**: Remove undocumented `Asset_Loader\enqueue_assets` method.
- **New**: Introduce new `Asset_Loader\register_asset()` and `Asset_Loader\enqueue_asset()` public API.
  - Assets should now be registered individually.
  - If a bundle exports both a CSS and JS file, both files should be registered or enqueued individually.
- **Deprecate** `Asset_Loader\autoenqueue()` method. Use the new, singular `enqueue_asset()` instead.
- **Deprecate** `Asset_Loader\autoregister()` method. Use the new, singular `register_asset()` instead.
- **Deprecate** `Asset_Loader\register_assets()` method. Use the new, singular `register_asset()` instead.
- Refactor how SSL warning notice behavior gets triggered during asset registration.
- Change how version strings are determined when registering assets
  - If asset is detected to be using a uniquely hashed filename, no version string is used.
  - If an asset manifest is in use, assets are versioned based on a content hash of that manifest.
  - If no other version information can be determined and the loader is running within[Altis](https://altis-dxp.com), the Altis revision constant is used to version registered assets.

## v0.3.4

- Added `composer/installers` as a dependency to permit custom installation paths when installing this package.

## v0.3.3

- Display admin notification about accepting Webpack's SSL certificate if `https://localhost` scripts encounter errors when loading.
- Derive script & style version string from file hash, not `filemtime`.

## v0.3.2

- Do not require plugin files if plugin is already active elsewhere in the project.

## v0.3.1

- Transfer plugin to `humanmade` GitHub organization

## v0.3.0

- Fix bug when loading plugin assets outside of `wp-content/plugins`
- Permit installation with `composer`.

## v0.2.0

- Initial release: introduce `autoregister()` and `autoenqueue()` public API.
