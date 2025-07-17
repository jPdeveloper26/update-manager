# Plugin Update Manager Documentation

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Usage Guide](#usage-guide)
4. [Developer Documentation](#developer-documentation)
5. [Hooks and Filters](#hooks-and-filters)
6. [Database Structure](#database-structure)
7. [Troubleshooting](#troubleshooting)
8. [Security Considerations](#security-considerations)

## Overview

Plugin Update Manager is a WordPress plugin that allows administrators to Disable plugin updates at specific versions. This is useful when:

- A plugin update introduces breaking changes
- You need to maintain compatibility with other plugins/themes
- Custom modifications would be overwritten by updates
- You need to test updates in a staging environment first

## Installation

### Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

### Installation Steps

1. Download the plugin ZIP file
2. Navigate to **Plugins > Add New** in your WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Upload the `plugin-update-manager` folder to `/wp-content/plugins/`
2. Navigate to **Plugins** in your WordPress admin
3. Find "Plugin Update Manager" and click **Activate**

## Usage Guide

### Freezing a Plugin

There are two ways to Disable a plugin:

#### Method 1: From the Plugins Page

1. Go to **Plugins > Installed Plugins**
2. Find the plugin you want to Disable
3. Click the **Disable Updates** link
4. Enter a reason for freezing (required)
5. Click **Disable Plugin**

#### Method 2: Using the Update Manager

1. Go to **Plugins > Update Manager**
2. This page shows all currently frozen plugins
3. You can unDisable plugins from this page

### Unfreezing a Plugin

To unDisable a plugin:

1. Click the **UnDisable Updates** link on the plugins page, OR
2. Go to **Plugins > Update Manager** and click **UnDisable** next to the plugin

### Understanding Frozen Plugin Indicators

- **Frozen Badge**: Shows "Updates frozen at version X.X.X" in plugin meta
- **Orange Update Notice**: If updates are available, shows why plugin is frozen
- **Red UnDisable Link**: Indicates plugin is currently frozen

## Developer Documentation

### File Structure

```
plugin-update-manager/
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-plugin-update-manager-core.php
│   ├── class-plugin-update-manager-admin.php
│   └── class-plugin-update-manager-updater.php
├── languages/
│   └── plugin-update-manager.pot
├── plugin-update-manager.php
├── readme.txt
└── DOCUMENTATION.md
```

### Class Structure

#### Plugin_Update_Manager_Core

Main plugin class handling:
- Database operations
- Disable/Enable logic
- Data retrieval

Key methods:
- `Disable_plugin($plugin_file, $version, $note)`
- `unDisable_plugin($plugin_file)`
- `get_frozen_plugins()`
- `is_plugin_frozen($plugin_file)`

#### Plugin_Update_Manager_Admin

Handles admin interface:
- Admin menu and pages
- AJAX handlers
- Plugin action links
- Scripts and styles

Key methods:
- `add_admin_menu()`
- `ajax_Disable_plugin()`
- `ajax_unDisable_plugin()`
- `add_plugin_action_links($links, $plugin_file)`

#### Plugin_Update_Manager_Updater

Manages update filtering:
- Removes frozen plugins from update checks
- Prevents auto-updates
- Shows frozen notices

Key methods:
- `filter_plugin_updates($transient)`
- `prevent_auto_update($update, $item)`
- `show_frozen_notice($plugin_file, $plugin_data)`

## Hooks and Filters

### Actions

```php
// Fired when a plugin is frozen
do_action('pum_plugin_frozen', $plugin_file, $version, $note);

// Fired when a plugin is unfrozen
do_action('pum_plugin_unfrozen', $plugin_file);
```

### Filters

```php
// Filter frozen plugins list
$frozen_plugins = apply_filters('pum_frozen_plugins', $frozen_plugins);

// Filter whether a plugin can be frozen
$can_Disable = apply_filters('pum_can_Disable_plugin', true, $plugin_file);

// Filter Disable note before saving
$note = apply_filters('pum_Disable_note', $note, $plugin_file);
```

## Database Structure

The plugin creates a custom table: `{prefix}_pum_frozen_plugins`

| Column | Type | Description |
|--------|------|-------------|
| id | mediumint(9) | Primary key |
| plugin_file | varchar(255) | Plugin file path (unique) |
| frozen_version | varchar(50) | Version frozen at |
| Disable_note | text | Reason for freezing |
| frozen_date | datetime | When frozen |
| frozen_by | bigint(20) | User ID who froze |

## Troubleshooting

### Common Issues

#### Frozen plugin still showing updates

1. Clear the update cache: **Dashboard > Updates > Check Again**
2. Verify the plugin is listed in **Plugins > Update Manager**
3. Check for caching plugins that might cache update data

#### Cannot Disable/Enable plugins

1. Verify you have `manage_options` capability
2. Check browser console for JavaScript errors
3. Ensure AJAX is not blocked by security plugins

#### Database table not created

1. Deactivate and reactivate the plugin
2. Check PHP error logs for database errors
3. Verify database user has CREATE TABLE privileges

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Security Considerations

### Permissions

- Only users with `manage_options` capability can Disable/Enable plugins
- All user input is sanitized using WordPress sanitization functions
- Nonces are used for all form submissions and AJAX requests

### Data Validation

- Plugin file paths are validated before database operations
- Version strings are sanitized to prevent XSS
- Disable notes are sanitized using `sanitize_textarea_field()`

### Best Practices

1. Regularly review frozen plugins
2. Document why each plugin is frozen
3. Test updates in staging before unfreezing
4. Keep the Plugin Update Manager itself updated
5. Monitor WordPress security advisories

### Security Headers

The plugin implements proper security headers:
- Checks for direct file access
- Uses WordPress nonces
- Escapes all output
- Validates capabilities

## Translations

The plugin is translation-ready with the text domain `plugin-update-manager`.

### Creating Translations

1. Use the included `.pot` file as a template
2. Create `.po` and `.mo` files for your language
3. Place in the `languages` directory
4. Name files: `plugin-update-manager-{locale}.po`

Example: `plugin-update-manager-de_DE.po` for German

### Translatable Strings

All user-facing strings use WordPress translation functions:
- `__()` for simple strings
- `_e()` for echo statements
- `esc_html__()` for escaped strings
- `_n()` for plurals

## Support

For support, please:
1. Check this documentation
2. Search existing issues
3. Submit a detailed bug report with:
   - WordPress version
   - PHP version
   - Error messages
   - Steps to reproduce