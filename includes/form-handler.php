<?php
// Prevent direct access
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the submission of the Gerber upload form (Step 1).
 */
function gerbernama_handle_step1_submission() {
    if ( wp_doing_ajax() ) {
        return;
    }
    if ( ! isset( $_POST['gn_submit_step1'] ) || ! isset( $_POST['gerbernama_upload_nonce_field'] ) ) {
        return;
    }

    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['gerbernama_upload_nonce_field'], 'gerbernama_upload_nonce_action' ) ) {
        wp_die( __( 'Nonce verification failed. Please try again.', 'nm-gb' ), 'Error', array( 'response' => 403 ) );
    }

    // Sanitize user inputs
    $first_name = isset( $_POST['gn_first_name'] ) ? sanitize_text_field( $_POST['gn_first_name'] ) : '';
    $last_name = isset( $_POST['gn_last_name'] ) ? sanitize_text_field( $_POST['gn_last_name'] ) : '';

    // Basic validation for names (can be expanded)
    if ( empty( $first_name ) || empty( $last_name ) ) {
        // For now, we'll handle errors by dying. Later, this will be part of AJAX response.
        wp_die( __( 'First name and last name are required.', 'nm-gb' ), 'Error', array( 'response' => 400 ) );
    }

    // File handling
    if ( isset( $_FILES['gn_gerber_files'] ) && !empty($_FILES['gn_gerber_files']['name'][0]) ) {
        $files = $_FILES['gn_gerber_files'];
        $processed_files = gerbernama_process_uploaded_files( $files, $first_name, $last_name );

        if ( is_wp_error( $processed_files ) ) {
            wp_die( $processed_files->get_error_message(), __( 'File Upload Error', 'nm-gb' ), array( 'response' => 400 ) );
        }

        if ( empty( $processed_files ) ) {
            wp_die( __( 'No valid Gerber files were found in your upload.', 'nm-gb' ), __( 'File Processing Error', 'nm-gb' ), array( 'response' => 400 ) );
        }

        // Store file paths for the next step (e.g., in a transient or session)
        // For now, let's assume we'll pass them via a query arg for simplicity before AJAX
        // This needs to be made more robust.
        $transient_key = 'gerbernama_files_' . md5( uniqid( rand(), true ) );
        set_transient( $transient_key, $processed_files, HOUR_IN_SECONDS ); // Store for 1 hour

        // Redirect to step 2 (preview page - to be created)
        // For now, let's just output success and the key.
        // Later, this will be a redirect to a page with the preview shortcode.
        $preview_url = add_query_arg( array(
            'gerbernama_preview' => $transient_key,
            // 'first_name' => rawurlencode($first_name), // Optional: pass other data
            // 'last_name' => rawurlencode($last_name),  // Optional: pass other data
        ), home_url('/gerber-preview/') ); // Assuming a page with slug 'gerber-preview' will host step 2

        // wp_redirect( $preview_url );
        // exit;

        // For now, display a success message for testing
        echo '<h2>' . __( 'Files Processed Successfully!', 'nm-gb' ) . '</h2>';
        echo '<p>' . sprintf( __( 'First Name: %s', 'nm-gb' ), esc_html( $first_name ) ) . '</p>';
        echo '<p>' . sprintf( __( 'Last Name: %s', 'nm-gb' ), esc_html( $last_name ) ) . '</p>';
        echo '<h3>' . __( 'Valid Gerber Files:', 'nm-gb' ) . '</h3><ul>';
        foreach ( $processed_files as $file_info ) {
            echo '<li>' . esc_html( $file_info['name'] ) . ' (' . sprintf( __( 'Stored at: %s', 'nm-gb' ), esc_html( str_replace(ABSPATH, '', $file_info['path']) ) ) . ')</li>';
        }
        echo '</ul>';
        echo '<p>' . __( 'Transient key for next step:', 'nm-gb' ) . ' ' . esc_html( $transient_key ) . '</p>';
        echo '<p><a href="' . esc_url($preview_url) . '">' . __( 'Proceed to Preview (manual link for now)', 'nm-gb' ) . '</a></p>';
        // Normally, a wp_redirect would happen above. We stop here for now to see the output.
        exit;


    } else {
        wp_die( __( 'Please upload at least one file.', 'nm-gb' ), 'Error', array( 'response' => 400 ) );
    }
}
add_action( 'init', 'gerbernama_handle_step1_submission' ); // Hook into init to catch POST request early

/**
 * Processes uploaded files: validates, extracts ZIP, filters Gerber files.
 *
 * @param array $files The $_FILES array for the uploaded files.
 * @param string $first_name User's first name.
 * @param string $last_name User's last name.
 * @return array|WP_Error Array of processed file paths and names, or WP_Error on failure.
 */
function gerbernama_process_uploaded_files( $files_input, $first_name, $last_name ) {
    $processed_files_details = array();
    $upload_dir = wp_upload_dir();
    $gerber_base_dir = $upload_dir['basedir'] . '/' . GERBERNAMA_UPLOAD_DIR_NAME;

    // Create a unique subdirectory for this submission
    $user_identifier = sanitize_file_name( strtolower( $first_name . '_' . $last_name ) );
    $unique_submission_id = $user_identifier . '_' . time() . '_' . wp_generate_password(5, false, false);
    $submission_dir = $gerber_base_dir . '/' . $unique_submission_id;

    if ( ! wp_mkdir_p( $submission_dir ) ) {
        return new WP_Error( 'dir_creation_failed', __( 'Could not create directory for uploaded files.', 'nm-gb' ) ); // Already internationalized, but good to confirm.
    }

    $allowed_extensions = array(
        'gbr', 'gtl', 'gbl', 'gbs', 'gto', 'gts', 'gbp', 'gko', // Common Gerber extensions
        'drl', 'txt', // Drill files often use .drl or .txt
        'gm1', 'gm2', 'gm3', 'gml', // Gerber outline/mechanical layers
        // Add more if needed, e.g., 'pho', 'ger'
    );

    $uploaded_files_count = count($files_input['name']);

    for ( $i = 0; $i < $uploaded_files_count; $i++ ) {
        $file_name = sanitize_file_name( $files_input['name'][$i] );
        $file_tmp_name = $files_input['tmp_name'][$i];
        $file_error = $files_input['error'][$i];
        $file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

        if ( $file_error !== UPLOAD_ERR_OK ) {
            // Consider logging this error or adding a more specific user message
            continue; // Skip this file
        }

        if ( $file_ext === 'zip' ) {
            if ( ! class_exists( 'ZipArchive' ) ) {
                // Consider collecting these errors to show to user
                error_log('ZipArchive class not found. Cannot process ZIP files.');
                continue;
            }
            $zip = new ZipArchive;
            if ( $zip->open( $file_tmp_name ) === TRUE ) {
                $temp_extract_path = $submission_dir . '/zip_extract_' . uniqid();
                wp_mkdir_p($temp_extract_path);

                for ( $j = 0; $j < $zip->numFiles; $j++ ) {
                    $entry_name = $zip->getNameIndex( $j );
                    // Skip directories and hidden files within ZIP
                    if ( substr( $entry_name, -1 ) === '/' || strpos($entry_name, '__MACOSX/') === 0 || strpos($entry_name, '.DS_Store') !== false ) {
                        continue;
                    }
                    $entry_ext = strtolower( pathinfo( $entry_name, PATHINFO_EXTENSION ) );
                    if ( in_array( $entry_ext, $allowed_extensions ) ) {
                        $sanitized_entry_name = sanitize_file_name( basename( $entry_name ) );
                        $new_file_path = $submission_dir . '/' . $sanitized_entry_name;

                        // Ensure unique filename in case of duplicates from different zips or direct uploads
                        $counter = 1;
                        while(file_exists($new_file_path)){
                            $new_file_path = $submission_dir . '/' . pathinfo($sanitized_entry_name, PATHINFO_FILENAME) . '_' . $counter . '.' . pathinfo($sanitized_entry_name, PATHINFO_EXTENSION);
                            $counter++;
                        }

                        if ( $zip->extractTo( $temp_extract_path, $entry_name ) ) {
                            $extracted_file_original_path = $temp_extract_path . '/' . $entry_name;
                            if(rename( $extracted_file_original_path, $new_file_path )){
                                $processed_files_details[] = array(
                                    'name' => basename($new_file_path),
                                    'path' => $new_file_path,
                                    'url'  => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path )
                                );
                            } else {
                                // Log error: failed to move file
                                error_log("Failed to move extracted file: $extracted_file_original_path to $new_file_path");
                            }
                        }
                    }
                }
                $zip->close();
                // Clean up temp extraction folder
                // This requires a recursive delete function, which WP doesn't have built-in for non-empty dirs easily.
                // For now, this might leave empty temp_extract_path folders or files if rename failed.
                // A more robust cleanup would be needed for production.
                if(is_dir($temp_extract_path)){
                    // Basic cleanup for files directly under temp_extract_path
                    $cleanup_files = glob($temp_extract_path . '/*');
                    foreach($cleanup_files as $cleanup_file){
                        if(is_file($cleanup_file)) @unlink($cleanup_file);
                        if(is_dir($cleanup_file)) @rmdir($cleanup_file); // only removes empty subdirs
                    }
                    @rmdir($temp_extract_path);
                }

            } else {
                // Log error: failed to open zip
                error_log("Failed to open ZIP file: $file_name");
            }
        } elseif ( in_array( $file_ext, $allowed_extensions ) ) {
            $new_file_path = $submission_dir . '/' . $file_name;

            $counter = 1;
            while(file_exists($new_file_path)){
                $new_file_path = $submission_dir . '/' . pathinfo($file_name, PATHINFO_FILENAME) . '_' . $counter . '.' . $file_ext;
                $counter++;
            }

            if ( move_uploaded_file( $file_tmp_name, $new_file_path ) ) {
                $processed_files_details[] = array(
                    'name' => basename($new_file_path),
                    'path' => $new_file_path,
                    'url'  => str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path )
                );
            } else {
                // Log error: failed to move uploaded file
                error_log("Failed to move uploaded file: $file_name to $new_file_path");
            }
        }
        // Non-Gerber, non-ZIP files are ignored and automatically cleaned up by PHP from tmp.
    }

    // If no valid files were processed after checking all uploads
    if ( empty( $processed_files_details ) ) {
        // Clean up the created submission directory if it's empty
        if (is_dir($submission_dir) && count(scandir($submission_dir)) == 2) { // Only . and ..
           @rmdir($submission_dir);
        }
        return new WP_Error( 'no_valid_files', __( 'No valid Gerber files (e.g., .gbr, .gtl, .drl) or ZIP archives containing them were found in your upload.', 'nm-gb' ) );
    }

    return $processed_files_details;
}

?>
