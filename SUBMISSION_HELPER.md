1. Plugin Name: Ninja Backup Mate
2. Short Description: A unique PHP-only WordPress utility. A ninja style backup plugin acting as a mate. Focused on simplicity and efficiency.

3. Detailed Description:
Ninja Backup Mate is a lightweight, PHP-only WordPress plugin designed for fast and efficient backups of your essential WordPress data. True to its "ninja" style, it focuses on backing up what's critical for your site's unique identity while intentionally excluding large, easily recreatable files to keep the process swift.

**Key Features:**
*   **Database Backup:** Securely exports your entire WordPress database into an SQL file.
*   **Selective File Backup:** Creates a ZIP archive of your custom files, including:
    *   All themes (`wp-content/themes`)
    *   All plugins (`wp-content/plugins`)
    *   Must-use plugins (`wp-content/mu-plugins`)
    *   Core configuration files (`wp-config.php`, `.htaccess`, `index.php`, `wp-robots.txt`)
*   **Exclusions for Efficiency:** Deliberately excludes:
    *   Core WordPress files (these can be reinstalled)
    *   The `wp-content/uploads` directory (typically the largest part of a site, often better managed separately or via other means)
    *   Cache directories, `.git`, `.svn`, `node_modules`, and any 'backups' directories to minimize backup size and time.
*   **One-Click Backup:** Initiate a full "ninja" backup with a single click from a dedicated admin page.
*   **Automatic Download:** The generated backup ZIP file is automatically offered for download to your computer.
*   **Temporary File Cleanup:** Ensures all temporary backup files on your server are removed after the download.
*   **PHP-Only & Simple:** Built entirely in PHP without external libraries, ensuring minimal overhead and maximum compatibility. All administration page styling is inline CSS for simplicity.

**How it Works:**
Upon clicking the "Perform Ninja Backup Now!" button, the plugin generates an SQL dump of your database and a ZIP archive of your selected custom files. These are then combined into a single, comprehensive ZIP file (e.g., `ninja-backup-mate-yoursitename-YYYY-MM-DD-HH-MM-SS.zip`) which is immediately pushed to your browser for download. Temporary files are automatically cleaned up.

**Important Notes:**
*   Requires the PHP `ZipArchive` extension to be enabled on your server.
*   While designed for speed, ensure your server has sufficient memory and execution time for the backup process.
*   Always download and store your backups in a safe, off-site location.
*   This plugin is ideal for quickly backing up custom code, settings, and database content, complementing other strategies for media files.

4. GitHub URL: https://github.com/ogichanchan/ninja-backup-mate