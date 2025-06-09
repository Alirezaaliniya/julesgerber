jQuery(document).ready(function($) {
    var $viewerTarget = $('#gerber-viewer-target');
    var $fileDataScript = $('#gerber_file_data');

    // Get localized strings, provide default fallbacks if object or string isn't available
    var i18n = {
        loadingViewerText: (typeof gerbernama_preview_obj !== 'undefined' && gerbernama_preview_obj.loading_viewer_text) ? gerbernama_preview_obj.loading_viewer_text : 'Initializing viewer...',
        loadingErrorText: (typeof gerbernama_preview_obj !== 'undefined' && gerbernama_preview_obj.loading_error_text) ? gerbernama_preview_obj.loading_error_text : 'Error loading Gerber files into the viewer.',
        libraryNotLoadedText: (typeof gerbernama_preview_obj !== 'undefined' && gerbernama_preview_obj.library_not_loaded_text) ? gerbernama_preview_obj.library_not_loaded_text : 'Gerber viewer library not loaded. Cannot display preview.',
        noFilesText: (typeof gerbernama_preview_obj !== 'undefined' && gerbernama_preview_obj.no_files_text) ? gerbernama_preview_obj.no_files_text : 'No files to preview.'
    };


    if ($viewerTarget.length && $fileDataScript.length) {
        var filesToLoad = JSON.parse($fileDataScript.html());

        if (filesToLoad && filesToLoad.length > 0) {
            $viewerTarget.html('<p style="text-align: center; padding-top: 10px;">' + i18n.loadingViewerText + '</p>'); // Basic feedback

            // Check if the gerberViewer library is available
            if (typeof gerberViewer === 'function') {

                // Prepare files for the viewer. It typically expects an array of {filename: string, filetext: string}
                // or it can fetch from URLs. Fetching from URLs is simpler here.
                // The tracespace viewer can take an array of URLs directly.
                var urls = filesToLoad.map(function(file) { return file.url; });

                // Initialize the viewer
                var viewer = gerberViewer($viewerTarget[0], {
                    // Example options (refer to tracespace/gerber-viewer docs for all options)
                    width: '100%',
                    height: '100%',
                    pcbBackground: '#222222', // Dark background
                    layerColors: { // Example custom colors
                        // gtl: '#D4AF37', // Top Copper - Gold
                        // gbl: '#C0C0C0', // Bottom Copper - Silver
                        // gto: '#FFFFFF', // Top Silkscreen - White
                        // gts: '#800080', // Top Soldermask - Purple (example)
                    }
                });

                // Load files by URL
                viewer.loadLayers(urls)
                    .then(function() {
                        $viewerTarget.find('p').remove(); // Remove "Initializing..." message
                        console.log('GerberNAMA: Gerber files load attempt completed by viewer.');
                    })
                    .catch(function(error) {
                        console.error('GerberNAMA: Error reported by viewer during loadLayers:', error);
                        var errorMessage = i18n.loadingErrorText;
                        if (error && error.message) {
                            errorMessage += "<br><small>" + String(error.message) + "</small>";
                        } else if (typeof error === 'string') {
                            errorMessage += "<br><small>" + String(error) + "</small>";
                        }
                        $viewerTarget.html('<p class="error" style="text-align: center; padding-top: 10px;">' + errorMessage + '</p>');
                    });

            } else {
                console.error('GerberNAMA: gerberViewer library function not found.');
                $viewerTarget.html('<p class="error" style="text-align: center; padding-top: 10px;">' + i18n.libraryNotLoadedText + '</p>');
            }
        } else {
            $viewerTarget.html('<p style="text-align: center; padding-top: 10px;">' + i18n.noFilesText + '</p>');
        }
    } else {
        if (!$viewerTarget.length) console.log('GerberNAMA: Gerber viewer target div not found.');
        if (!$fileDataScript.length) console.log('GerberNAMA: Gerber file data script tag not found.');
    }
});
