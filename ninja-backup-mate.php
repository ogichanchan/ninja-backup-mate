<?php
/**
 * Plugin Name: Ninja Backup Mate
 * Plugin URI: https://github.com/ogichanchan/ninja-backup-mate
 * Description: A unique PHP-only WordPress utility. A ninja style backup plugin acting as a mate. Focused on simplicity and efficiency.
 * Version: 1.0.0
 * Author: ogichanchan
 * Author URI: https://github.com/ogichanchan
 * License: GPLv2 or later
 * Text Domain: ninja-backup-mate
 */

// Critical rule: No direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Ninja_Backup_Mate
 *
 * Handles all functionality for the Ninja Backup Mate plugin.
 * This class is designed to be self-contained within a single file.
 */
class Ninja_Backup_Mate {

    /**
     * Transient key for storing admin notices.
     *
     * @var string
     */
    const ADMIN_NOTICES_TRANSIENT = 'ninja_backup_mate_admin_notices';

    /**
     * Constructor for the Ninja_Backup_Mate class.
     * Registers all necessary hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_post_ninja_backup_mate_backup', array( $this, 'handle_backup_request' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    }

    /**
     * Adds the plugin's menu item to the WordPress admin panel.
     */
    public function add_admin_menu() {
        add_menu_page(
            esc_html__( 'Ninja Backup Mate', 'ninja-backup-mate' ), // Page title.
            esc_html__( 'Ninja Backup', 'ninja-backup-mate' ),       // Menu title.
            'manage_options',                                       // Capability required.
            'ninja-backup-mate',                                    // Menu slug.
            array( $this, 'render_admin_page' ),                    // Callback function.
            'dashicons-cloud',                                      // Icon URL or Dashicon class.
            80                                                      // Position in the menu.
        );
    }

    /**
     * Renders the main administration page for the plugin.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Ninja Backup Mate', 'ninja-backup-mate' ); ?></h1>
            <p><?php esc_html_e( 'Click the button below to perform a quick "ninja" backup of your WordPress database and essential custom files (themes, plugins, config).', 'ninja-backup-mate' ); ?></p>
            <p><?php esc_html_e( 'This backup EXCLUDES core WordPress files and your uploads directory to keep the backup fast and focused on your customizations.', 'ninja-backup-mate' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="ninja_backup_mate_backup">
                <?php wp_nonce_field( 'ninja_backup_mate_backup_action', 'ninja_backup_mate_nonce' ); ?>
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-cloud"></span>
                        <?php esc_html_e( 'Perform Ninja Backup Now!', 'ninja-backup-mate' ); ?>
                    </button>
                </p>
            </form>

            <div style="margin-top: 30px; padding: 15px; border: 1px solid #ccc; background-color: #f9f9f9; border-radius: 4px;">
                <h2><?php esc_html_e( 'Important Notes:', 'ninja-backup-mate' ); ?></h2>
                <ul>
                    <li><?php esc_html_e( 'The backup file will be generated and offered for download automatically.', 'ninja-backup-mate' ); ?></li>
                    <li><?php esc_html_e( 'Ensure your server has sufficient memory and execution time for large backups, though this "ninja" backup is designed to be fast.', 'ninja-backup-mate' ); ?></li>
                    <li><?php esc_html_e( 'Always download and store your backups in a safe, off-site location.', 'ninja-backup-mate' ); ?></li>
                    <li><?php esc_html_e( 'This plugin requires the PHP ZipArchive extension to be enabled on your server.', 'ninja-backup-mate' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
        $this->inline_css(); // Add inline CSS.
    }

    /**
     * Handles the backup request.
     */
    public function handle_backup_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'ninja-backup-mate' ) );
        }

        if ( ! isset( $_POST['ninja_backup_mate_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ninja_backup_mate_nonce'] ), 'ninja_backup_mate_backup_action' ) ) {
            wp_die( esc_html__( 'Security check failed. Please try again.', 'ninja-backup-mate' ) );
        }

        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->set_admin_notice( esc_html__( 'Error: The PHP ZipArchive extension is not enabled on your server. Please contact your hosting provider to enable it.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        // Increase PHP limits for backup process.
        set_time_limit( 0 );
        if ( function_exists( 'ini_set' ) ) {
            @ini_set( 'memory_limit', WP_MAX_MEMORY_LIMIT );
        }

        $blog_name = sanitize_title( get_bloginfo( 'name' ) );
        if ( empty( $blog_name ) ) {
            $blog_name = 'wordpress'; // Fallback if blog name is empty.
        }
        $backup_filename = 'ninja-backup-mate-' . $blog_name . '-' . gmdate( 'Y-m-d-H-i-s' );
        $temp_dir        = $this->get_temp_dir();

        if ( ! $temp_dir ) {
            $this->set_admin_notice( esc_html__( 'Error: Could not create temporary directory for backup. Please check file permissions.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        $db_backup_file        = trailingslashit( $temp_dir ) . 'database.sql';
        $file_backup_zip_temp  = trailingslashit( $temp_dir ) . 'files.zip';
        $final_backup_zip_path = trailingslashit( $temp_dir ) . $backup_filename . '.zip';

        // 1. Database Backup.
        if ( ! $this->backup_database( $db_backup_file ) ) {
            $this->cleanup_temp_dir( $temp_dir );
            $this->set_admin_notice( esc_html__( 'Error: Database backup failed. Check database permissions or server resources.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        // 2. File Backup.
        if ( ! $this->backup_files( $file_backup_zip_temp ) ) {
            $this->cleanup_temp_dir( $temp_dir );
            $this->set_admin_notice( esc_html__( 'Error: File backup failed. Check file permissions or server resources.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        // 3. Combine both into a final zip and offer for download.
        $zip = new ZipArchive();
        if ( $zip->open( $final_backup_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $this->cleanup_temp_dir( $temp_dir );
            $this->set_admin_notice( esc_html__( 'Error: Could not create final zip archive.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        $zip->addFile( $db_backup_file, 'database.sql' ); // Add DB file to root of final zip.

        // Add contents of the files.zip to the main zip under a 'files/' directory.
        $inner_zip = new ZipArchive();
        if ( $inner_zip->open( $file_backup_zip_temp ) === true ) {
            for ( $i = 0; $i < $inner_zip->numFiles; $i++ ) {
                $entry_name = $inner_zip->getNameIndex( $i );
                if ( ! empty( $entry_name ) ) {
                    $zip->addFromString( 'files/' . $entry_name, $inner_zip->getFromIndex( $i ) );
                }
            }
            $inner_zip->close();
        } else {
            // If inner_zip fails, continue but log/notify.
            $this->set_admin_notice( esc_html__( 'Warning: Could not open temporary files archive for inclusion. File backup might be incomplete.', 'ninja-backup-mate' ), 'warning' );
        }
        $zip->close();

        // Download the file.
        if ( file_exists( $final_backup_zip_path ) ) {
            header( 'Content-Type: application/zip' );
            header( 'Content-Disposition: attachment; filename=' . $backup_filename . '.zip' );
            header( 'Content-Length: ' . filesize( $final_backup_zip_path ) );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );
            ob_clean(); // Clean any previous output buffer.
            flush();    // Flush system output buffer.
            readfile( $final_backup_zip_path );
        } else {
            $this->set_admin_notice( esc_html__( 'Error: Final backup file was not found for download.', 'ninja-backup-mate' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=ninja-backup-mate' ) );
            exit;
        }

        // Cleanup temporary files and directory.
        $this->cleanup_temp_dir( $temp_dir );
        exit; // Important to exit after file download.
    }

    /**
     * Performs the database backup.
     *
     * @param string $output_file The full path to the output SQL file.
     * @return bool True on success, false on failure.
     */
    private function backup_database( $output_file ) {
        global $wpdb;

        $return = '';
        $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

        if ( empty( $tables ) ) {
            return false;
        }

        foreach ( $tables as $table ) {
            $table = $table[0];
            $result = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table ) . '`', ARRAY_A );

            $return .= 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`;';
            $row2 = $wpdb->get_row( 'SHOW CREATE TABLE `' . esc_sql( $table ) . '`', ARRAY_N );
            $return .= "\n\n" . $row2[1] . ";\n\n";

            foreach ( $result as $row ) {
                $return .= 'INSERT INTO `' . esc_sql( $table ) . '` VALUES(';
                $values = array();
                foreach ( $row as $field_value ) {
                    if ( isset( $field_value ) ) { // Check if value is not null.
                        $field_value = addslashes( $field_value );
                        $field_value = preg_replace( "/\n/", "\\n", $field_value );
                        $values[] = "'" . $field_value . "'";
                    } else {
                        $values[] = 'NULL';
                    }
                }
                $return .= implode( ', ', $values ) . ");\n";
            }
            $return .= "\n\n\n";
        }

        // Save the file.
        $handle = @fopen( $output_file, 'w+' );
        if ( $handle === false ) {
            return false;
        }
        fwrite( $handle, $return );
        fclose( $handle );

        return true;
    }

    /**
     * Performs the file backup using ZipArchive.
     *
     * This backup EXCLUDES core WordPress files and the 'uploads' directory
     * within wp-content, focusing on custom themes, plugins, and key config files.
     *
     * @param string $output_file The full path to the output ZIP file.
     * @return bool True on success, false on failure.
     */
    private function backup_files( $output_file ) {
        $zip = new ZipArchive();
        if ( $zip->open( $output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            return false;
        }

        $root_path = ABSPATH; // Root of WordPress installation.

        // Directories to include, relative to ABSPATH.
        $include_dirs = array(
            'wp-content/plugins',
            'wp-content/themes',
            'wp-content/mu-plugins', // If it exists.
        );

        // Specific files to include, relative to ABSPATH.
        $include_files = array(
            'wp-config.php',
            '.htaccess', // If it exists.
            'index.php',
            'wp-robots.txt', // Also good to include.
        );

        // Add specific files.
        foreach ( $include_files as $file ) {
            $full_path = $root_path . $file;
            if ( file_exists( $full_path ) ) {
                $zip->addFile( $full_path, $file );
            }
        }

        // Add directories recursively.
        foreach ( $include_dirs as $dir ) {
            $full_dir_path = $root_path . $dir;
            if ( is_dir( $full_dir_path ) ) {
                $this->add_dir_to_zip( $zip, $full_dir_path, $dir );
            }
        }

        $zip->close();
        return true;
    }

    /**
     * Recursively adds a directory and its contents to a ZipArchive.
     * Skips specific files/directories like 'uploads', 'cache', '.git'.
     *
     * @param ZipArchive $zip               The ZipArchive object.
     * @param string     $source_path       The full path to the directory to add.
     * @param string     $zip_entry_prefix  The base name for files/directories inside the zip.
     */
    private function add_dir_to_zip( $zip, $source_path, $zip_entry_prefix ) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source_path, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $files as $file ) {
            $file_path = $file->getRealPath();

            // Get path relative to the source directory, then prepend the zip_entry_prefix.
            $relative_to_source = ltrim( substr( $file_path, strlen( $source_path ) ), '/' );
            $zip_entry_name     = trailingslashit( $zip_entry_prefix ) . $relative_to_source;

            // Exclude specific patterns (case-insensitive for directory names).
            $exclude_patterns = array(
                '/uploads/',
                '/cache/',
                '/.git/',
                '/node_modules/',
                '/.svn/',
                '/backups/', // Exclude any potential backup directories.
            );

            $should_exclude = false;
            foreach ( $exclude_patterns as $pattern ) {
                if ( preg_match( $pattern, strtolower( $zip_entry_name ) ) ) {
                    $should_exclude = true;
                    break;
                }
            }

            if ( $should_exclude ) {
                continue;
            }

            if ( $file->isFile() ) {
                $zip->addFile( $file_path, $zip_entry_name );
            }
            // For directories, ZipArchive handles adding empty directories when a file is added within them.
            // Explicitly adding empty directories with $zip->addEmptyDir($zip_entry_name) is optional
            // but can be useful to preserve empty directory structures. For a "ninja" backup,
            // focusing on files is usually sufficient.
        }
    }

    /**
     * Creates a temporary directory for backup files within the uploads folder.
     *
     * @return string|false The path to the temporary directory on success, false on failure.
     */
    private function get_temp_dir() {
        $upload_dir = wp_upload_dir();
        $temp_dir = trailingslashit( $upload_dir['basedir'] ) . 'ninja-backup-mate-temp-' . uniqid();
        if ( wp_mkdir_p( $temp_dir ) ) {
            // Add an index.php to prevent directory listing.
            file_put_contents( trailingslashit( $temp_dir ) . 'index.php', '<?php // Silence is golden.' );
            return $temp_dir;
        }
        return false;
    }

    /**
     * Cleans up a temporary directory and its contents.
     *
     * @param string $dir The path to the directory to clean up.
     */
    private function cleanup_temp_dir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $it = new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS );
        $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $files as $file ) {
            if ( $file->isDir() ) {
                @rmdir( $file->getRealPath() );
            } else {
                @unlink( $file->getRealPath() );
            }
        }
        @rmdir( $dir );
    }

    /**
     * Sets an admin notice to be displayed on the next page load using transients.
     *
     * @param string $message The message to display.
     * @param string $type    The type of notice (e.g., 'success', 'error', 'warning', 'info').
     */
    private function set_admin_notice( $message, $type = 'info' ) {
        $notices = get_transient( self::ADMIN_NOTICES_TRANSIENT );
        if ( ! is_array( $notices ) ) {
            $notices = array();
        }
        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );
        set_transient( self::ADMIN_NOTICES_TRANSIENT, $notices, 30 ); // Keep for 30 seconds.
    }

    /**
     * Displays admin notices stored in a transient.
     */
    public function display_admin_notices() {
        $notices = get_transient( self::ADMIN_NOTICES_TRANSIENT );
        if ( ! empty( $notices ) && is_array( $notices ) ) {
            foreach ( $notices as $notice ) {
                $class = 'notice notice-' . sanitize_html_class( $notice['type'] );
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $notice['message'] ) );
            }
            delete_transient( self::ADMIN_NOTICES_TRANSIENT ); // Clear notices after display.
        }
    }

    /**
     * Outputs inline CSS for the admin page.
     * CRITICAL RULE: PHP ONLY - All CSS must be inline.
     */
    private function inline_css() {
        ?>
        <style type="text/css">
            .wrap h1 {
                margin-bottom: 20px;
                font-size: 2em;
                color: #222;
            }
            .wrap p {
                line-height: 1.6;
                margin-bottom: 10px;
            }
            .button-primary {
                background: #0073aa;
                border-color: #006799;
                box-shadow: 0 1px 0 #006799;
                color: #fff;
                text-decoration: none;
                text-shadow: 0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;
                padding: 10px 20px;
                height: auto;
                line-height: 1.5;
                font-size: 1.1em;
                border-radius: 3px;
                cursor: pointer;
            }
            .button-primary:hover,
            .button-primary:focus {
                background: #0085ba;
                border-color: #0073aa;
                color: #fff;
                box-shadow: 0 1px 0 #0073aa;
            }
            .button-primary .dashicons {
                vertical-align: middle;
                margin-right: 5px;
            }
            .submit {
                margin-top: 20px;
            }
            .wrap ul {
                list-style: disc;
                margin-left: 20px;
                padding: 0;
            }
            .wrap ul li {
                margin-bottom: 8px;
            }
        </style>
        <?php
    }
}

// Instantiate the plugin class.
if ( class_exists( 'Ninja_Backup_Mate' ) ) {
    new Ninja_Backup_Mate();
}