=== Interactive Content – H5P ===
Contributors: icc0rz, fnoks, falcon28
Donate link: http://h5p.org
Tags: interactive content, content, interactive video, quiz, drag and drop, multiple choice, hot spots, collage, memory game, cloze test, game, free, export, elearning, e-learning, learning, education, xAPI, html5, responsive
Requires at least: 3.8.1
Tested up to: 4.4
Stable tag: 1.5.4
License: MIT
License URI: http://opensource.org/licenses/MIT

Create and add rich content to your website for free. Some examples of what you get with H5P are Interactive Video, Quizzes, Collage and Timeline.

== Description ==

One of the great benefits with using H5P is that it gives you access to lots of different [interactive content types](https://h5p.org/content-types-and-applications "Examples and Downloads").

Another great benefit with H5P is that it allows you to easily share and reuse content. To use content created with H5P, you simply insert a shortcode `[h5p Id="1"]` where you wish for the content to appear. To reuse content, you just download the H5P you would like to edit and make your changes – e.g. translate to a new language or adjust it to a new situation.

H5P is:
* open source
* free to use
* HTML5
* responsive

The H5P community is actively contributing to improve H5P. Updates and new features are continuously made available on the community portal [H5P.org](https://h5p.org "H5P").

View our [setup for WordPress](https://h5p.org/documentation/setup/wordpress "Setup H5P for WordPress") to get information on how to get started with H5P.

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
