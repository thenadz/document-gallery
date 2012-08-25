=== Plugin Name ===
Contributors: dan.rossiter
Tags: documents, gallery
Requires at least: 2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows the user to easily include documents (ie: PDF, MS Word, MS PowerPoint, MS Excel, and ZIP files) 
directly into their page or post.

== Installation ==

1. Upload `document-gallery` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[document gallery]` in any posts or pages you want a document gallery included. See below for additional display options.

= Document Gallery Options =

In order to include all compatible docuements from a given page or post, you must include 
the following shortcode in the post:

`[document gallery <descriptions=[true or false]> <orderby=[menu_order, date, rand]> <order=[ASC or DEC]>]`

Using just `[document gallery]` is equivalent to `[document gallery descriptions=false orderby=menu_order order=ASC]`.

== Screenshots ==

1. This is how the Document Gallery looks with `descriptions=false` (default). Note that the display inherits styling from your active theme.
2. This is how the Document Gallery looks with `descriptions=true`. The descriptions are auto-populated using the description field from when you upload the document.

== Changelog ==

= 0.8 =
* First public release of Document Gallery.
* Displays PDF, Word, PowerPoint, Excel, and ZIP documents from a given page or post.
* Documents can be ordered by a number of different factors.
