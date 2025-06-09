=== gerbernama ===
Contributors: Alireza aliniya
Tags: gerber, file upload, pcb, gerber viewer
Requires at least: 5.2
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin to receive and preview Gerber files from users via a two-step form. Users can upload individual Gerber files or ZIP archives, which are then processed and made available for preview using a JavaScript-based Gerber viewer.

== Description ==

GerberNAMA provides a user-friendly way to handle Gerber file submissions on your WordPress site.

Features:
* Two-step submission process: 1. Upload files and user info. 2. Preview Gerber files.
* Supports individual Gerber files (.gbr, .gtl, .gbl, .drl, etc.) and ZIP archives.
* Automatic extraction of ZIP archives and filtering of valid Gerber files.
* Secure file storage with unique naming in `wp-content/uploads/gerbers/`.
* Interactive Gerber file preview using a JavaScript viewer (requires manual installation of tracespace/gerber-viewer library).
* AJAX-powered form submission for a smooth user experience.
* Basic styling and internationalization support.

To use the plugin:
1. Place the shortcode `[gerbernama_upload_form]` on a page where you want the upload form to appear.
2. Create a separate page (e.g., named "Gerber Preview" with slug "gerber-preview") and place the `[gerbernama_file_preview]` shortcode on it. This page will be used to display the Gerber previews.
3. **Important:** Download the `gerber-viewer.min.js` from [https://github.com/tracespace/gerber-viewer/releases](https://github.com/tracespace/gerber-viewer/releases) and place it in the plugin's `assets/js/vendor/` directory (`wp-content/plugins/gerbernama/assets/js/vendor/gerber-viewer.min.js`).

== Installation ==

1. Upload the `gerbernama` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Create two pages:
   * One for the upload form, add the shortcode: `[gerbernama_upload_form]`
   * One for the preview, add the shortcode: `[gerbernama_file_preview]` (Ensure its slug matches the one expected by the AJAX redirect, typically 'gerber-preview').
4. **Crucial Step:** Download `gerber-viewer.min.js` from [https://github.com/tracespace/gerber-viewer/releases](https://github.com/tracespace/gerber-viewer/releases) and place it into `wp-content/plugins/gerbernama/assets/js/vendor/gerber-viewer.min.js`. Without this, the preview will not work.

== Frequently Asked Questions ==

= How do I change the preview page URL? =
Currently, the preview page slug is hardcoded in `includes/ajax-handlers.php` to 'gerber-preview'. You may need to modify this if you use a different slug.

= Are the uploaded files secure? =
Files are stored in a subdirectory of `wp-content/uploads` with unique names. An `.htaccess` file is added to this directory to prevent direct listing and script execution.

== Screenshots ==
(Consider adding screenshots if this were a real plugin submission)
1. Upload form.
2. Preview area.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
