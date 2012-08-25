=== Plugin Name ===
Contributors: danrossiter
Tags: documents, gallery
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows the user to easily include documents (ie: PDF, MS Word, MS PowerPoint, MS Excel, and ZIP files) 
directly into their page or post.

== Installation ==

1. Upload `file-gallery` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `shortcode` in any posts or pages you want a document gallery included.

== Screenshots ==

1. This is how the Document Gallery looks with `descriptions=false` (default). Note that the display inherits styling from your active theme.
2. This is how the Document Gallery looks with `descriptions=true`. The descriptions are auto-populated using the description field from when you upload the document.

== A brief Markdown Example ==

In order to include all compatible docuements from a given page or post, you must include 
the following shortcode in the post:

`[document gallery <descriptions=[true or false]> <orderby=[menu_order,date]> <order=[ASC or DEC]>]`

Using just `[document gallery]` is equivalent to `[document gallery descriptions=false orderby=menu_order order=ASC]`.
