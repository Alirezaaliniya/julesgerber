<?php
// Prevent direct access
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles AJAX submission for Step 1: File Upload.
 */
function gerbernama_ajax_handle_step1_submission() {
    // Verify nonce ( FormData should include 'gerbernama_upload_nonce_field' )
    if ( ! isset( $_POST['gerbernama_upload_nonce_field'] ) || ! wp_verify_nonce( $_POST['gerbernama_upload_nonce_field'], 'gerbernama_upload_nonce_action' ) ) {
        wp_send_json_error( array( 'message' => __( 'Nonce verification failed. Please refresh and try again.', 'nm-gb' ) ), 403 ); // Already internationalized
    }

    // Sanitize user inputs
    $first_name = isset( $_POST['gn_first_name'] ) ? sanitize_text_field( $_POST['gn_first_name'] ) : '';
    $last_name = isset( $_POST['gn_last_name'] ) ? sanitize_text_field( $_POST['gn_last_name'] ) : '';

    if ( empty( $first_name ) || empty( $last_name ) ) {
        wp_send_json_error( array( 'message' => __( 'First name and last name are required.', 'nm-gb' ) ), 400 ); // Already internationalized
    }

    if ( ! isset( $_FILES['gn_gerber_files'] ) || empty($_FILES['gn_gerber_files']['name'][0]) ) {
        wp_send_json_error( array( 'message' => __( 'Please upload at least one file.', 'nm-gb' ) ), 400 ); // Already internationalized
    }

    $files = $_FILES['gn_gerber_files'];
    $processed_files = gerbernama_process_uploaded_files( $files, $first_name, $last_name ); // This function is in form-handler.php

    if ( is_wp_error( $processed_files ) ) {
        wp_send_json_error( array( 'message' => $processed_files->get_error_message() ), 400 ); // Error message from WP_Error should be translated at source
    }

    if ( empty( $processed_files ) ) {
        wp_send_json_error( array( 'message' => __( 'No valid Gerber files were found in your upload.', 'nm-gb' ) ), 400 ); // Already internationalized
    }

    // Generate transient key for Step 2
    $transient_key = 'gerbernama_files_' . md5( uniqid( $first_name . $last_name, true ) );
    set_transient( $transient_key, $processed_files, HOUR_IN_SECONDS );

    // Prepare URL for Step 2 (preview page)
    // This assumes a page with slug 'gerber-preview' will be created for the preview shortcode.
    $preview_page_slug = 'gerber-preview'; // Make this configurable if needed
    $preview_url = add_query_arg( array(
        'gn_preview_key' => $transient_key,
        // 'fname' => rawurlencode($first_name), // Optionally pass names if needed by preview page directly
        // 'lname' => rawurlencode($last_name),
    ), get_permalink( get_page_by_path( $preview_page_slug ) ) );

    if ( !get_page_by_path( $preview_page_slug ) ) {
         // Fallback or error if the preview page doesn't exist
         // For now, let's allow it to proceed but the URL might be non-functional
         // Consider creating this page programmatically on activation or providing instructions.
         error_log("GerberNAMA: The preview page with slug '{$preview_page_slug}' does not exist. Preview URL might not work.");
    }


    wp_send_json_success( array(
        'message' => __( 'Files processed successfully! Redirecting to preview...', 'nm-gb' ), // Already internationalized
        'processed_files' => $processed_files, // For debugging or if no redirect
        'transient_key' => $transient_key,     // For debugging
        'preview_url' => $preview_url
    ) );
}
add_action( 'wp_ajax_gerbernama_submit_step1_ajax', 'gerbernama_ajax_handle_step1_submission' );
add_action( 'wp_ajax_nopriv_gerbernama_submit_step1_ajax', 'gerbernama_ajax_handle_step1_submission' ); // For non-logged-in users

?>
