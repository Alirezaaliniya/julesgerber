<?php
// Prevent direct access to the template
if ( ! defined( 'WPINC' ) ) {
    die;
}
global $processed_files_data; // Make sure it's accessible if not passed directly via include scope

// We need to prepare a list of file URLs for the Gerber viewer
$gerber_file_urls_for_js = array();
if ( !empty($processed_files_data) ) {
    foreach ($processed_files_data as $file) {
        // Ensure we have a URL and it's a Gerber file (the viewer might also do its own filtering)
        // The viewer typically needs direct URLs to the files.
        if (isset($file['url'])) {
             // The tracespace viewer might expect an array of {filename: string, filetext: string} or URLs.
             // For simplicity with URLs, we might just pass an array of URLs.
             // Or, more robustly, { name: file['name'], url: file['url'] }
            $gerber_file_urls_for_js[] = array('name' => $file['name'], 'url' => $file['url']);
        }
    }
}
?>
<div id="gerbernama-preview-container" style="direction: ltr;">
    <h2><?php _e( 'Gerber File Preview', 'nm-gb' ); ?></h2>

    <?php if ( !empty( $gerber_file_urls_for_js ) ) : ?>
        <p><?php _e( 'Your processed Gerber files are listed below. The viewer will attempt to render them.', 'nm-gb' ); ?></p>

        <!-- Placeholder for Gerber Viewer -->
        <div id="gerber-viewer-target" style="width: 100%; height: 600px; border: 1px solid #ccc; margin-top: 20px; margin-bottom:20px; position: relative;">
            <p style="text-align: center; padding-top: 50px;"><?php _e( 'Loading Gerber Viewer...', 'nm-gb' ); ?></p>
        </div>

        <script type="application/json" id="gerber_file_data">
            <?php echo wp_json_encode( $gerber_file_urls_for_js ); ?>
        </script>

        <!-- We will enqueue the viewer library and our custom preview JS in gerbernama_enqueue_scripts -->

        <h3><?php _e( 'Files to Preview:', 'nm-gb' ); ?></h3>
        <ul>
            <?php foreach ( $processed_files_data as $file ) : ?>
                <li><?php echo esc_html( $file['name'] ); ?>
                    (<?php printf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $file['url'] ), __( 'Download', 'nm-gb' ) ); ?>)
                </li>
            <?php endforeach; ?>
        </ul>

    <?php else : ?>
        <p class="error"><?php _e( 'No valid Gerber files are available for preview from the provided data.', 'nm-gb' ); ?></p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
         <a href="<?php echo esc_url( home_url('/') ); // Or link to the upload form page ?>" class="gerbernama-button-link"><?php _e( 'Submit Another Set of Files', 'nm-gb' ); ?></a>
    </p>
</div>
