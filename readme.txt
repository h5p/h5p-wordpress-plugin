=== H5P ===
Contributors: icc0rz, fnoks, falcon28
Donate link: http://h5p.org
Tags: h5p, content, interactive, video, interactive content, interactive video, presentation, html5, modern web, education, free, responsive, custom, fill in the blanks, multiple choice, multichoice, drag and drop, shortcode, plugin, admin, images, slideshow, sharing, multisite, mobile, media, javascript, package, export, user results, download, quiz, games, memory game
Requires at least: 3.8.1
Tested up to: 4.2.2
Stable tag: 1.5.4
License: MIT
License URI: http://opensource.org/licenses/MIT

H5P is a WordPress plugin for creating and sharing rich HTML5 content in your browser.

== Description ==

H5P makes it easy to create and share HTML5 content and applications. H5P empowers creatives to create rich
and interactive web experiences in Wordpress more efficiently.

With the H5P plugin you may create interactive videos, interactive presentations, quizzes, image hotspots
and many other types of interactive HTML5 content. Content may be created using the built in authoring
tool or by uploading H5P files containing content others have created.
The content may be inserted into multiple posts and pages using shortcodes, like this `[h5p id="1"]`.

Whenever you get a new H5P library this enables you to start creating rich content of a new type using this library,
i.e. if you download [Interactive Video](http://h5p.org/interactive-video) and upload in your WordPress, you may create
your own interactive videos.

If you think you've found a bug related to this plugin, report it [here](https://github.com/h5p/h5p-wordpress-plugin/issues "GitHub Issue Tracker").
Content bugs are reported to their respective issue trackers.

Content is King!


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
