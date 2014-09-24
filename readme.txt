=== H5P ===
Contributors: icc0rz, fnoks, falcon28
Donate link: http://h5p.org
Tags: h5p, interactive content, interactive video, presentation, html5, modern web, education
Requires at least: 3.8.1
Tested up to: 4.0
Stable tag: 1.1
License: MIT
License URI: http://opensource.org/licenses/MIT

H5P is a WordPress plugin for creating and sharing rich HTML5 content in your browser.

== Description ==

H5P makes it easy to create and share HTML5 content and applications. H5P empowers creatives to create rich 
and interactive web experiences more efficiently - all you need is a web browser and a web site with an H5P plugin.

After installing this plugin, you may upload .h5p files containing both HTML5 libraries and content. The content
may be inserted into you posts and pages using shortcodes, like this `[h5p id="1"]`.
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
Fixed so that content dependencies cache is rebuilt when library dependencies changes. This also include new export files.
The H5P Editor is now inside an iframe to avoid messing up css styling.
A library administration user interface has been added. This supports content upgrades, deletion of libraries without content and uploading h5p packages without content.
Added own h5p capabilities to roles, matching the defaults used for posts.
Minor bug fixes and improvements.