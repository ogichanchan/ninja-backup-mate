=== Ninja Backup Mate ===
Contributors: ogichanchan
Tags: wordpress, plugin, tool, admin, backup, utility, database, files
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Ninja Backup Mate is a unique, PHP-only WordPress utility designed for simplicity and efficiency in creating website backups. This "ninja style" plugin acts as a mate, providing a fast and focused backup solution.

It allows you to perform a quick backup of your WordPress database and essential custom files, including themes, plugins, and key configuration files (like `wp-config.php`, `.htaccess`). Crucially, this plugin EXCLUDES core WordPress files and your `uploads` directory to ensure the backup process is swift and concentrates only on your unique customizations.

Upon clicking the backup button, a compressed `.zip` file containing your database and custom files will be generated and offered for immediate download.

**Key Features:**
*   **PHP-Only:** No external dependencies beyond the core PHP `ZipArchive` extension.
*   **Focused Backups:** Backs up only your database, themes, plugins, and selected configuration files.
*   **Excludes Core & Uploads:** Skips large and non-essential files to keep backups lean and fast.
*   **One-Click Backup:** Simple and intuitive interface for initiating backups.
*   **Direct Download:** Backup files are immediately offered for download.
*   **Minimalist Design:** Prioritizes efficiency and ease of use.

**Important Notes:**
*   Requires the PHP `ZipArchive` extension to be enabled on your server.
*   Always download and store your backups in a safe, off-site location.
*   While designed for speed, ensure your server has sufficient memory and execution time for the backup process.

This plugin is open source. Report bugs at: https://github.com/ogichanchan/ninja-backup-mate

== Installation ==
1. Upload the `ninja-backup-mate` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the plugin via the 'Ninja Backup' menu item in your WordPress admin sidebar.

== Changelog ==
= 1.0.0 =
* Initial release.