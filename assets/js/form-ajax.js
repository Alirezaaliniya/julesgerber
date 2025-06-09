jQuery(document).ready(function($) {
    $('#gerbernama-upload-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var feedbackDiv = $('#gerbernama-form-feedback');
        var submitButton = form.find('input[type="submit"]');
        feedbackDiv.html('').removeClass('error success');
        submitButton.val(gerbernama_ajax_obj.processing_text || 'Processing...').prop('disabled', true);

        var formData = new FormData(form[0]);
        // Ensure the action is part of formData if not already included by PHP
        formData.append('action', 'gerbernama_submit_step1_ajax');
        // The nonce should already be part of form.serializeArray() if it's in the form,
        // but good to ensure it's picked up by FormData. It is.

        $.ajax({
            url: gerbernama_ajax_obj.ajax_url, // Passed via wp_localize_script
            type: 'POST',
            data: formData,
            processData: false, // Important for FormData
            contentType: false, // Important for FormData
            dataType: 'json', // Expect JSON response from server
            success: function(response) {
                if (response.success) {
                    feedbackDiv.html('<p class="success">' + response.data.message + '</p>');
                    // If preview_url is provided, redirect to Step 2
                    if (response.data.preview_url) {
                        // Optionally display a brief message before redirecting
                        feedbackDiv.append('<p>' + (gerbernama_ajax_obj.redirecting_text || 'Redirecting to preview...') + '</p>');
                        window.location.href = response.data.preview_url;
                    } else if (response.data.processed_files) {
                        // Fallback if no redirect URL, just show files (more for debugging)
                        var fileList = '<ul>';
                        response.data.processed_files.forEach(function(file) {
                            fileList += '<li>' + file.name + '</li>';
                        });
                        fileList += '</ul>';
                        feedbackDiv.append('<h3>' + (gerbernama_ajax_obj.processed_files_header_text || 'Processed Files:') + '</h3>' + fileList);
                        submitButton.val(form.find('input[type="submit"]').prop('defaultValue') || 'Next: Preview Files').prop('disabled', false); // Re-enable
                    }
                } else {
                    feedbackDiv.html('<p class="error">' + response.data.message + '</p>');
                    submitButton.val(form.find('input[type="submit"]').prop('defaultValue') || 'Next: Preview Files').prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var errorMsg = gerbernama_ajax_obj.error_text || 'An unexpected error occurred. Please try again.';
                feedbackDiv.html('<p class="error">' + errorMsg + '<br><small>' + textStatus + ': ' + errorThrown + '</small></p>');
                submitButton.val(form.find('input[type="submit"]').prop('defaultValue') || 'Next: Preview Files').prop('disabled', false);
            }
        });
    });
});
