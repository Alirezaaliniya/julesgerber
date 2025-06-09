<?php
// Prevent direct access
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the Gerber file upload form.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML content for the form.
 */
function gerbernama_render_upload_form_shortcode( $atts ) {
    // Start output buffering
    ob_start();

    // Include the form template
    // We will create this file in the next step.
    if ( file_exists( GERBERNAMA_PLUGIN_DIR . 'templates/upload-form.php' ) ) {
        include GERBERNAMA_PLUGIN_DIR . 'templates/upload-form.php';
    } else {
        echo '<p>' . esc_html__( 'Upload form template not found.', 'nm-gb' ) . '</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'gerbernama_upload_form', 'gerbernama_render_upload_form_shortcode' );

/**
 * Renders the Gerber file preview stage.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML content for the preview.
 */
function gerbernama_render_file_preview_shortcode( $atts ) {
    ob_start();

    $preview_key = isset( $_GET['gn_preview_key'] ) ? sanitize_text_field( $_GET['gn_preview_key'] ) : '';
    $processed_files_data = false;

    if ( ! empty( $preview_key ) ) {
        $processed_files_data = get_transient( $preview_key );
    }

    if ( $processed_files_data && !empty($processed_files_data) ) {
        // Pass file data to the template
        // We need to ensure the file paths/URLs are accessible to the JavaScript viewer.
        // The `gerbernama_process_uploaded_files` function already stores 'name', 'path', and 'url'.
        // We will JSON_encode the file data (specifically URLs and names) to pass to JS.

        // For security, once the transient is used, consider deleting it if it's a one-time view.
        // delete_transient( $preview_key ); // Optional: uncomment if preview link should work once.

        include GERBERNAMA_PLUGIN_DIR . 'templates/preview-gerber.php';
    } else {
        // Transient expired, invalid, or no files
        echo '<div class="gerbernama-preview-container" style="direction: ltr;">';
        echo '<h2>' . esc_html__( 'Gerber File Preview', 'nm-gb' ) . '</h2>';
        echo '<p class="error">' . esc_html__( 'Sorry, the preview link is invalid, has expired, or no files were found for preview. Please try submitting your files again.', 'nm-gb' ) . '</p>';
        // Optional: Link back to the upload form page
        // $upload_page_url = home_url('/your-upload-page-slug/');
        // echo '<p><a href="' . esc_url($upload_page_url) . '">' . esc_html__('Go back to upload form', 'nm-gb') . '</a></p>';
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode( 'gerbernama_file_preview', 'gerbernama_render_file_preview_shortcode' );

?>
