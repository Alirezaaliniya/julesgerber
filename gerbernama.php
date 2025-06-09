<?php
/**
 * Plugin Name:       gerbernama
 * Plugin URI:        https://nias.ir
 * Description:       A plugin to receive and preview Gerber files from users in a two-step form.
 * Version:           1.0.0
 * Author:            Alireza aliniya
 * Author URI:        https://nias.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nm-gb
 * Domain Path:       /languages
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Package:           PluginPackage
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define global constants for the plugin.
 */
define( 'GERBERNAMA_VERSION', '1.0.0' );
define( 'GERBERNAMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GERBERNAMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GERBERNAMA_UPLOAD_DIR_NAME', 'gerbers' ); // Name of the subdirectory in wp-content/uploads

// Placeholder for future includes
// require_once GERBERNAMA_PLUGIN_DIR . 'includes/class-gerbernama.php';

// Include plugin files
require_once GERBERNAMA_PLUGIN_DIR . 'includes/shortcodes.php';
require_once GERBERNAMA_PLUGIN_DIR . 'includes/form-handler.php';
require_once GERBERNAMA_PLUGIN_DIR . 'includes/ajax-handlers.php';

function gerbernama_load_textdomain() {
    load_plugin_textdomain( 'nm-gb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'gerbernama_load_textdomain' );

function gerbernama_enqueue_scripts() {
    global $post;
    $enqueue_styles = false;

    // Check for upload form shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'gerbernama_upload_form')) {
        $enqueue_styles = true;
        wp_enqueue_script(
            'gerbernama-form-ajax',
            GERBERNAMA_PLUGIN_URL . 'assets/js/form-ajax.js',
            array( 'jquery' ),
            GERBERNAMA_VERSION,
            true
        );
        wp_localize_script(
            'gerbernama-form-ajax',
            'gerbernama_ajax_obj',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'processing_text' => __( 'Processing...', 'nm-gb' ),
                    'redirecting_text' => __( 'Redirecting to preview...', 'nm-gb' ),
                    'error_text' => __( 'An unexpected error occurred. Please try again.', 'nm-gb' ),
                    'processed_files_header_text' => __( 'Processed Files:', 'nm-gb' )
                )
        );
    }

    // Check for preview shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'gerbernama_file_preview')) {
        $enqueue_styles = true;
        $gerber_viewer_lib_path = 'assets/js/vendor/gerber-viewer.min.js';
        if (file_exists(GERBERNAMA_PLUGIN_DIR . $gerber_viewer_lib_path)) {
            wp_enqueue_script(
                'gerber-viewer-lib',
                GERBERNAMA_PLUGIN_URL . $gerber_viewer_lib_path,
                array(),
                null,
                true
            );
            wp_enqueue_script(
                'gerbernama-preview-js',
                GERBERNAMA_PLUGIN_URL . 'assets/js/gerber-preview.js',
                array( 'jquery', 'gerber-viewer-lib' ),
                GERBERNAMA_VERSION,
                true
            );
            wp_localize_script(
               'gerbernama-preview-js',
               'gerbernama_preview_obj',
               array(
                   'loading_viewer_text' => __( 'Initializing viewer...', 'nm-gb' ),
                   'loading_error_text' => __( 'Error loading Gerber files into the viewer.', 'nm-gb' ),
                   'library_not_loaded_text' => __( 'Gerber viewer library not loaded. Cannot display preview.', 'nm-gb' ),
                   'no_files_text' => __( 'No files to preview.', 'nm-gb' )
               )
           );
        } else {
            error_log('GerberNAMA Error: gerber-viewer.min.js not found at ' . GERBERNAMA_PLUGIN_DIR . $gerber_viewer_lib_path);
        }
    }

    // Enqueue main stylesheet if either shortcode is present
    if ($enqueue_styles) {
        wp_enqueue_style(
            'gerbernama-style',
            GERBERNAMA_PLUGIN_URL . 'assets/css/style.css',
            array(),
            GERBERNAMA_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'gerbernama_enqueue_scripts' );

/**
 * The code that runs during plugin activation.
 */
function activate_gerbernama() {
    // Create the uploads directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $gerber_upload_path = $upload_dir['basedir'] . '/' . GERBERNAMA_UPLOAD_DIR_NAME;

    if ( ! file_exists( $gerber_upload_path ) ) {
        wp_mkdir_p( $gerber_upload_path );
    }

    // Add a .htaccess file for security to prevent direct access and script execution if possible
    if ( file_exists( $gerber_upload_path ) ) {
        $htaccess_content = "Options -Indexes
deny from all";
        @file_put_contents( $gerber_upload_path . '/.htaccess', $htaccess_content );
    }
}
register_activation_hook( __FILE__, 'activate_gerbernama' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_gerbernama() {
    // Optional: Code to run on deactivation, like cleaning up settings or transients.
}
register_deactivation_hook( __FILE__, 'deactivate_gerbernama' );

// Main plugin actions
// add_action( 'plugins_loaded', array( 'GerberNAMA_Main', 'get_instance' ) );

?>
