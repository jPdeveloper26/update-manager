=== Update Manager ===
Contributors: CognitoWP
Donate link: https://wpbay.com/store/cognitowp/
Tags: updates, plugins, Disable, version control, update management
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.2.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable WordPress plugin updates at specific versions with notes explaining why updates are frozen.

== Description ==

Plugin Update Manager allows you to Disable specific plugins at their current version, preventing WordPress from showing update notifications or performing automatic updates for those plugins. Each frozen plugin can have a note explaining why it was frozen, making it easy to track compatibility issues or other reasons for holding back updates.

= Features =

* Disable any plugin at its current version
* Add notes explaining why each plugin is frozen
* View all frozen plugins in a centralized dashboard
* See who froze a plugin and when
* Prevent automatic updates for frozen plugins
* Visual indicators in the plugins list showing frozen status
* Easy one-click Disable/Enable functionality
* Full multilingual support

= Use Cases =

* Compatibility issues with newer versions
* Waiting for theme/plugin compatibility updates
* Custom modifications that would be overwritten
* Testing requirements before updating
* Client-specific version requirements

== Installation ==

1. Upload the `plugin-update-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Plugins > Update Manager to manage frozen plugins
4. Use the "Disable Updates" link on any plugin to Disable it at its current version

== Frequently Asked Questions ==

= Will frozen plugins still check for updates? =

Yes, the plugin will still check for available updates but won't display them in the WordPress admin or allow automatic updates.

= Can I Disable the Plugin Update Manager itself? =

No, the plugin cannot Disable itself to prevent potential issues.

= What happens if I deactivate Plugin Update Manager? =

All plugins will return to their normal update behavior. The Disable settings are stored in the database and will be restored if you reactivate the plugin.

= Can I export/import Disable settings? =

This feature is planned for a future version.

= Is this plugin multisite compatible? =

Currently, the plugin works on single WordPress installations. Multisite support is planned for a future release.

== Screenshots ==

1. Plugin Update Manager admin page showing frozen plugins
2. Disable/UnDisable links in the plugins list
3. Disable dialog with version and note fields
4. Frozen plugin indicator in the plugins list
5. Update notice for frozen plugins

== Changelog ==

= 1.0.0 =
* Initial release
* Core Disable/unDisable functionality
* Admin interface for managing frozen plugins
* AJAX-powered Disable/unDisable actions
* Visual indicators for frozen plugins
* Multilingual support ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of Plugin Update Manager.