=== Interactive Content – H5P ===
Contributors: icc0rz, fnoks, falcon28
Donate link: http://h5p.org
Tags: editor, video, quiz, slider, education
Requires at least: 3.8.1
Tested up to: 5.8.3
Stable tag: 1.15.4
License: MIT
License URI: http://opensource.org/licenses/MIT

Create and add rich content to your website for free. Some examples of what you get with H5P are Interactive Video, Quizzes, Collage and Timeline.

== Description ==

One of the great benefits with using H5P is that it gives you access to lots of different [interactive content types](https://h5p.org/content-types-and-applications "Examples and Downloads"), such as presentation, interactive video, memory game, quiz, multiple choice, timeline, collage, hotspots, drag and drop, cloze test (fill in the blanks), personality quiz, accordion, flash cards, audio recorder.

Another great benefit with H5P is that it allows you to easily share and reuse content. To use content created with H5P, you simply insert a shortcode `[h5p Id="1"]` where you wish for the content to appear. To reuse content, you just download the H5P you would like to edit and make your changes – e.g. translate to a new language or adjust it to a new situation.

H5P is:

* Open Source
* Free to Use
* HTML5
* Responsive

The H5P community is actively contributing to improve H5P. Updates and new features are continuously made available on the community portal [H5P.org](https://h5p.org "H5P").

View our [setup for WordPress](https://h5p.org/documentation/setup/wordpress "Setup H5P for WordPress") to get information on how to get started with H5P.

= GDPR Compliance =
Information useful to help you achieve GDPR compliance while using this plugin can be found at [H5P.org's GDPR Compliance](https://h5p.org/plugin-gdpr-compliance "GDPR Compliance") page.

== Installation ==

1. Download and extract the package
2. Put the `h5p` folder in your `/wp-content/plugins/` directory
3. Navigate to the WordPress installation in your web browser
4. Login and active the plugin

If you're cloning this plugin from GitHub, remember to get the sub modules as well:
`git submodule update --init --recursive`

== Screenshots ==

1. Simple editor to create interactive H5P content
2. Preview the H5P interactive content
3. Press Add H5P to add interactive H5P content to posts
4. Choose among available interactive H5P content from the list
5. Interactive H5P content added to a post
6. View post with interactive H5P content.

== Changelog ==

= 1.0 =
This is the first release of this plugin.

= 1.1 =
Imported the latest changes and bug fixes from h5p core.
Improved multilingual support. Added missing translations, and it should now be possible to translate the menu item without ruining the H5P forms.
Fixed so that content dependencies cache is rebuilt when library dependencies changes. This also includes new export files.
The H5P Editor is now inside an iframe to avoid messing up css styling.
A library administration user interface has been added. This supports content upgrades, deletion of libraries without content and uploading h5p packages without content.
Added own h5p capabilities to roles, matching the defaults used for posts.
Fixed bugs related to Windows paths and when debugging is enabled with notices.
Other minor bug fixes and improvements.

= 1.2 =
Added user results tracking and views for H5Ps. Can be disabled through settings.
Re-adding capabilities. Now adds to roles which have the default WP capabilities. Should fix issues for users which has changed the default roles.
Updated views for viewing all content and inserting H5P into posts or pages. These are now paginated and can be filtered and sorted as needed.
Added the ability to restrict creation of certain content types through the libraries administration UI.
Implemented function which will fetch meta data updates for content types(libraries) from H5P.org. This can be disabled through the settings interface. Currently it will only display links for tutorials when creating content, but in the future it might fetch information about new versions and upgrades.
Other minor bug fixes and improvements.

= 1.2.1 =
Fixed JavaScript error when the users doesn't have access to the currently selected library. (It has been restricted.)
Made sure the whole copyrights dialog always is visible.
Fixed pagination translation.

= 1.2.2 =
Fixed support for multi-site setups(network).
Fixed issue when repacking h5p files after dependencies have changed, old libraries was still in the pack.
Added missing string to translation.
Fixed code causing PHP notices.
Added H5P_DEV option. Can be set in wp-config to always override libraries when "new" ones are uploaded.

= 1.3.0 =
Added mini tutorial after activating plugin.
Fixed issues with tmp directories on multi-hosted environments.
Added H5P option to the admin bar's new menu.
Improved plugin description and tags.
Fixed issues with uploading and deleting libraries for php builds that doesn't support INPUT_SERVER.
Removed cleanup warnings when there's no dir to clean.
Updated to latest version of core, now supporting xAPI. [!]
H5P now has its own event system better suited for OO.
Libraries are now loaded in the same order as they are required by content types.
Improved the performance of the editor by reduced ajax requests.
Support for external video sources and using video URLs from YouTube. [!]
YouTube integration (also requires updated libraries)
Support for external video files
Other minor bug fixes.
[!] Only work with the latest version of the content types. Find upgrades at http://h5p.org/update-all-content-types

= 1.4.0 =
Added support for external embed.
Updated default sorting on H5P tables, making it easier to find recently modified content or new results.
Added actions which make it easier for other plugins to alter content types.
Added support for inputing target DOM node when initializing H5P, making it possible to "start" content loaded via AJAX.
Removed deprecated version of font-awesome from plugin code.
Other minor fixes and code clean up.

= 1.4.1 =
Fixed some misplaced code that causes the "my results" page to fail.

= 1.5.0 =
Added support for configuring the frame and buttons around H5Ps.
Increased the performance of the upgrade process.
Improved internal URL system, it's no longer required to have allow_url_fopen enabled in PHP.
Made it possible to store the current state of content pr user. This allows logged in users to resume exercises where they left off.
Improved xAPI support.
Italian language support. Thanks to community member yeu for contributing.
Other minor adjustments and fixes.

= 1.5.1 =
Fixed support for hosting services that limit unique key lengths to 767 bytes.
Added support for additional font styling if the content types allows it.
Don't show copyright button if there is no copyright.
Try to generate generic copyrights if the content type doesn't have custom copyrights.
Fixed JS error on results and content list pages that doesn't contain tables.
Fixed wrong sorting on insert H5P media pop-up.
Other minor bug fixes and adjustments.

= 1.5.2 =
Added italian language support to the editor, and update plugin translation. (big thanks to yeu for both of theses!)
Fixed editor list bugs. One where you can't order list items downwards.
Fixed so that libraries are loaded according to weight in the editor.
Improved the external event dispatcher. Now the this arg should be correct.
Keep the contents disable settings when re-saving after global options have changed. (More consistent and reliable)
Fixed issue where the about H5P button disappears from content.
Minor improvement to content output filtering

= 1.5.3 =
Imporved error handling when uploading H5P libraries, courtesy of community user limikael.
Created separate class for querying H5P content in case other plugins wish to use it.
Updated hooks for altering semantics, parameters, scripts and styles. See http://h5p.org/wordpress-customization for more information.
Fixed actor for xAPI events.
Upload is selected by default if there's no content.
Other minor bug fixes and adjustments.

= 1.5.4 =
Added base class for content types.
Added duration, scaled and details about content type to xAPI.
Added html class when content is framed.
Minor improvements for some UI strings to allow translation.
Removed all utf-8 nbsp in the code.
Fixed .off() wasn't working for the event dispatcher.
Added text-align support to wysiwyg.
Improved iframe communication, use parent instead of top.
Minor improvements to fullscreen and embed.
Added Spanish translations.
Added French translations.
Minor bugfixes and improvements to the editor.

= 1.5.5 =
Added auto-install of basic content types.
Added notification when updates are available.
Use different temporary folder to avoid issues on certain hosts.
Made copyright fields translatable.
Added workaround for bug when changing library in Chrome on Windows.
Improved semi-fullscreen for iPad devices.
Avoid warning message when allow_url_fopen=0.
Updated various text strings and translations.
Improved editor design and fixed minor bugs in the editor.
Prepared for copy and paste support in the editor.
Smarter resize script when embedding. Less flickering.

= 1.5.6 =
Improved saving of current content state when leaving the page.
Fixed issues with external embed resizing script.
Improved pasting of text in text editor/WYSIWYG.
Fixed bug in video upload editor widget.

= 1.5.7 =
Handle WP errors when fetching updates.
Altered the "update only" feature to update minor and major versions as well, not just patch versions.
Increase update download timeout to 28 seconds.
Encourage manual update if auto update fails.

= 1.6.0 =
Support for using slug instead of ID when inserting H5P content into posts, articles, etc. Thanks to Mikael Lindqvist for implementing this.
Support for Simplified Chinese, thanks to Wen for contributing.
Use core library when deleting content. (simplifies code)
Improved file handling through core library. (simplifies code)
Support for aggregating JavaScript and CSS through core library. Set H5P_DISABLE_AGGREGATION to disable.
Implemented system for logging H5P events. No UI yet.
Improved communications with H5P.org. Added clearer messages on how to disable.
Added shortcode info to content pages.
Improved AJAX requests and error handling.
Added a simple autoloader to make coding easier.
Added support for tagging H5P content. Makes it easier to organize.
Improved user notification messages.
Fixed broken library delete. (when managing libraries)
Various bug fixes.

= 1.6.1 =
Fixed issue with content list on multi-site installations.

= 1.6.2 =
Fixed broken pagination for H5P content list.
Reset page number when filtering in the H5P content list.
Removed notice when content without stylesheets are aggregated.

= 1.7.0 =
Added custom HTML confirmation dialog.
Added German translation, thanks to herrmayr.
Added version numbers and links to licenses.
Added handling of several digits in the major and minor versions of libraries.
Added highlighting of required fields in the editor.
Enhanced action bar buttons for accessibility.
Improved some error messages that didn't make much sense in the editor.
Corrected typos in Core spec and readme, thanks to Marc Laporte.
Corrected documentation in Core, thanks to Dave Richer.
Fixed pagination widget to handle empty pages.
Bugfix to avoid warnings on some systems when Cron cleans up tmp files.
Joined some CSS files to reduce the number of resources that's loaded in the editor.
Fixed number conversion before comparing versions in Content Upgrade.
Fixed centering of CKEditor dialog in the editor.
Fixed bug when trying to disable external communication.
Fixed so that all the H5P tables are removed on uninstall.
Fixed bug with messages displayed.
Minor text string corrections.
Minor visual enhancements.

= 1.7.1 =
Fixed video widget in editor not printing correct error message.

= 1.7.2 =
Reassign capabilities when enabling multisite. Will revert caps when changing back to single site.
Baked H5PEditor image styles into SCSS.
Editor: Don't display copyright button until an image is added.
Added Turkish translation – Thanks to hakangur at h5p.org for contributing.
Updated French translation – Thanks to Realia at h5p.org.
Added Polish translation for Editor – Thanks to k.kwasniewski at h5p.org and eTechnologie.
Added l10n support for insert video widget – Also, updated texts to make them easier to understand.
Allow hyphens in HTML tags – Thanks to andyrandom at drupal.org.
xAPI – Avoid errors when browser cookies are disabled.
Fixed unnecessary ajax calls.

= 1.7.3 =
Fixed class missing from autoloader.
Added missing variable from uninstall.
Added Bosnian translation, big thanks to sabahuddin on GitHub.
Increase H5P API version to 1.9
Added support for optional select in semantics.
Added option for enabling LRS content types.
Fixed text strings lacking translation support. (editor)
Added support for the same common fields from multiple libraries in the same editor.

= 1.7.4 =
Exec ready callbacks when view is ready

= 1.7.5 =
Fixed backwards-compatible change in editor

= 1.7.6 =
Fixed loading of language files for content types

= 1.7.7 =
Makes it possible for a group semantics to have a sub content id
Adds semi full screen functionality
Increases H5P API version to 1.11

= 1.7.8 =
Visual improvements for the editor
Improvements for the settings controlling the action button toolbar below each content.
Increases H5P API version to 1.12
Fixed untranslatable string, big thanks to Joseph Rezeau for finding and fixing these.

= 1.7.9 =
Fix compability with PHP <5.4
Big thanks to andyrandom at drupal.org for providing the fix.

= 1.7.10 =
Fixed bug where you could not have a custom user table.
Internal changes to improve how files are handled.
Enhanced language code compatibility.
Added custom xAPI verbs for action toolbar buttons. This will make it possible to track the number of downloads.
Added Dutch translation for plugin. Thanks to Qsento!
Updated French translation for plugin. Thanks to Joseph Rezeau!
Added Dutch translation for editor. Thanks to otacke!

= 1.7.11 =
Fixed fieldset overflow bug in Editor.

= 1.7.12 =
Fixed issue when generating export for some sites where the web server would report a false document_root.

= 1.7.13 =
Fixed editor issue related to a problem with fieldsets in IE (not able to expand)

= 1.8.0 =
Added H5P Hub interface for selecting and installing content types
Improved requirements checking
Added support for important description in editor
Minor improvements and bug fixes

= 1.8.1 =
Removed functions dependendant on PHP >5.4.

= 1.8.3 =
Fixed support for combining header styles with other text formating options.
Added .wav files to whitelist.
Added video quality naming option (currently, only used by Interactive Video).
Only allow for a single video source when using YouTube (others doesn't work).
Improved keyboard navigation with H5P Hub.
Updated NL and DE translations.
Other minor bug fixes and improvements.

= 1.8.4 =
Changed H5P's weight in the 'Create New' menu to avoid always being place on top.
Improved SQL mode compatibility.
Improved requirement checks compatibility with different PHP versions.
Added version selector for the different licenses.
Added support for localized licenses.
Minor code improvements in core.
Updated translations.
Minor design layout improvements in editor.
Added external event for when changing and loading library in editor.

= 1.9.0 =
Improved H5P Hub error handling.
Removed warnings in PHP 7.0+ when checking requirements due to bytes conversion.
Added version selector when selecting content license.
Added support for localizing content licenses.
Made the built-in fullscreen button keyboard accessible.
Improved the confirmation dialog positioning.
Improved the handling of temporary files as they're uploaded.
Added vtt and webvtt to the default file upload whitelist.
Improved font-family validation regex pattern. Big thanks to Cornel Les.
Moved the list description text to be consistent with other editor fields.
Improved the YouTube regexp matching pattern. Big thanks to Otacke!
Improved copyright button for media fields.
Improved editor number fields to support feedback ranges.
Improved editor number fields error messages to be consistent with other fields.
Removed update button in H5P Hub when the users doesn't have access to upated libraries.
Other minor editor improvements.
Fixed support for absolute URLs for content types embedded through div.

= 1.9.1 =
Fixed content not loading due to wrong URL for multisites on the same domain. Big thanks to Joachim Happel for contributing the fix.

= 1.9.2 =
Fixed 'div' content not loading on sites residing in sub directories, changes fix for multisites as well.

= 1.9.3 =
Improved the fix introduced in 1.9.2 as it was not tested well enough.

= 1.9.4 =
Something went wrong with the release of 1.9.3.

= 1.10.0 =
Adds more detailed error messages with links for troubleshooting them.
Prevent deleting sub content of linked directories.
New action for adding head tags to embed page.
Fixed PHP warnings when trying to view deleted content.
Updated CKEditor to version 4.7.3.
Added Greek translations (thanks to xarhsdev).
Added Finnish translations (thanks to Janne Särkelä).
Added support for editor iframe reloading.
Allow multiple content to be loaded at the same time.
Refactor of the Content Type Selector(Hub); big UX and performance improvements .
Updated translations.

= 1.10.1 =
Fix issue with editor not saving correctly in Safari 11.
Fix missing translation and some PHP notices.

= 1.10.2 =
Add support for the new Privacy APIs added in the latest WordPress. (retrieving and deleting user data upon request)
Add an opt-in option for statistics for first time users of the plugin.
Update the French translation, big thanks to knowledgeplaces on GitHub!
Restricted some new content type in case an LRS isn't used.
Add support for the latest H5P Core version.
Various minor bug fixes

= 1.10.3 =
Fix compatibility with PHP <5.4

= 1.11.0 =
Added the new metadata system to H5P
Added support for Copy and Paste inside H5PEditor (No cross-site support)
Added support for addons (e.g. Mathdisplay)
Added Russian translation. Big thanks to Александр Шульгин
Added Arabic translation. Big thanks to omniasaid
Updated Turkish translation. Big thanks to hakangur
Upgraded CKEditor inside H5PEditor
Added Bosnian translation to H5PEditor. Big thanks to Sabahuddin
Updated German translation in H5PEditor. Big thanks to Sebastian Rettig
Updated French translations in H5PEditor. Big thanks to knowledgeplaces
Updated Arabic translations in H5PEditor. Big thanks to smartwayme
Updated Polish translations in H5PEditor. Big thanks to Andrzej Pieńkowski
Added brazilian portuguese translation in H5PEditor. Big thanks to Juliano Navroski Junior
Updated Turkish translations in H5PEditor. Big thanks to Adem Özgür
Fix appropriate separator for AJAX URLs in H5PEditor. Big thanks to Miika Langille
Minor bug fixes and improvements to H5PEditor and Core

= 1.11.1 =
Fixed serious bug when loading editor translations for some languages.

= 1.11.2 =
Fix compatibility with PHP <5.4

= 1.11.3 =
Fix "common fields" issue when switching between content types.
Fix resizing bug for iframe embeds.
Fix issue with temporary files.

= 1.12.0 =
Improved API for better support with PressBooks.
Added Copy/Paste support for single libraries in Editor.
Added support for semi-fullscreen in Editor. (used in Branching Scenario)
Added support for Audio Recorder in default audio widget. (used in Memory Game)
Improved group summaries in Editor.
Fix reset of all subContentIds when using Copy/Paste.
Removed caching of pasted content to ensure objects are cloned.
Removed support for base64 "uploads" in Core. (All uploads should be blobs or files)
Other minor Editor & Core improvements.
Updated translations, a big thanks to all contributors.

= 1.13.0 =
Added automated upgrade of content on save.
Improved error handling for content upgrade.
Added support for language switching in editor.
Added a new reuse dialog for download or copy of content in view.

= 1.13.1 =
Fix correct default language not always loading.

= 1.14.0 =
Added fullscreen editing mode
Added offline support for storing and resubmitting answers
Allow setting bitrate for video files
Improved cross origin handling for media files

= 1.14.1 =
Fix invalid finnish language issue in core

= 1.15.0 =
Removed GitHub URL to prevent Update plugins using it and download the plugin without dependencies.
Added capabilities for viewing h5p content. Thanks Otacke.
Fixed missing NOT NULL causing errors on some configurations.
Improved list layout for narrow screens. Thanks Otacke.
Change how focus effect is applied when only using mouse cursor.
Minor accessibility improvements to editor.
Updated language files. Big thanks to all the contributors.

= 1.15.1 =
Fixed PHP version 8 compatibility
Allow for separate accessibility title for content
Updated translations
Improved attribute filtering performance

= 1.15.2 =
Fixed inconsistent variable naming

= 1.15.3 =
Update CKEditor to the latest 4.x version.

= 1.15.4 =
Update CKEditor to 4.17.1