<div id="gerbernama-upload-form-container" style="direction: ltr;">
    <form id="gerbernama-upload-form" method="post" enctype="multipart/form-data">

        <h2><?php _e( 'Submit Your Gerber Files', 'nm-gb' ); ?></h2>

        <p>
            <label for="gn_first_name"><?php _e( 'First Name:', 'nm-gb' ); ?></label><br>
            <input type="text" id="gn_first_name" name="gn_first_name" required>
        </p>

        <p>
            <label for="gn_last_name"><?php _e( 'Last Name:', 'nm-gb' ); ?></label><br>
            <input type="text" id="gn_last_name" name="gn_last_name" required>
        </p>

        <p>
            <label for="gn_gerber_files"><?php _e( 'Upload Gerber Files (ZIP or individual files like .gbr, .gtl, .drl):', 'nm-gb' ); ?></label><br>
            <input type="file" id="gn_gerber_files" name="gn_gerber_files[]" multiple accept=".zip,.gbr,.gtl,.gbl,.drl,.gto,.gts,.gbs,.gbp,.gko,.gm1,.gm2,.gm3,.gml">
            <!-- Note: 'multiple_input="true"' is not a standard HTML attribute. For multiple files, use the 'multiple' attribute. This will be handled by AJAX later. -->
            <!-- For non-AJAX, if you want multiple files with one input, it should be name="gn_gerber_files[]" and the `multiple` attribute. -->
            <!-- We'll refine the file input for AJAX handling. For now, this sets up the basic field. -->
        </p>

        <?php wp_nonce_field( 'gerbernama_upload_nonce_action', 'gerbernama_upload_nonce_field' ); ?>

        <p>
            <input type="submit" name="gn_submit_step1" value="<?php _e( 'Next: Preview Files', 'nm-gb' ); ?>">
        </p>

    </form>
    <div id="gerbernama-form-feedback"></div>
</div>
