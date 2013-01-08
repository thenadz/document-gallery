=== Document Gallery ===
Contributors: dan.rossiter
Tags: attachments, icons, documents, gallery
Requires at least: 2.6
Tested up to: 3.5
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows the user to easily create a "gallery" of all non-image attachments on a given post/page,
making them easy to share.

== Description ==

This plugin allows the user to effortlessly include a gallery, much like a photo gallery,
of all your non-image attachments anywhere within your post.

*NOTE: If you find this plugin useful, please take 10 seconds to rate it so others can find it also. Thanks so 
much for your support!*

== Installation ==

1. Upload `document-gallery` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dg]` in any posts or pages you want a document gallery included. See below for additional display options.

= Document Gallery Options =

In order to include all compatible docuements from a given page or post, you must include 
the following shortcode in the post:

`[dg <descriptions=[true/false]> <orderby=[menu_order, title, date, author, rand]> <attachment_pg=[false/true]> <order=[ASC/DEC]>]`

**Orderby Options**

* `menu_order` - This is probably the one you want to use. Order by the integer fields in the Insert / 
Upload Media Gallery dialog. Note that these fields may be blank by default. If this is the case, 
you must populate the fields before this option will work.
* `title` - Alphabetical order based on title.
* `date` - Order by date of document upload.
* `author` - Order by the owner of the upload (username).
* `rand` - Pseudo-random order.

By default, document gallery will use `descriptions=false`, `orderby=menu_order`, `order=ASC`, and 
`attachment_pg=false` if you do not specify otherwise.

= Theme Developers =

If you would like to include Document Gallery functionality in your theme, you simply need to include the following
code wherever you would like it to appear: `<?php echo do_shortcode('[dg]'); ?>`. You can include additional options 
(listed above) as needed.

== Screenshots ==

1. This is how the Document Gallery looks with `descriptions=false` (default). Note that the display inherits styling from your active theme.
2. This is how the Document Gallery looks with `descriptions=true`. The descriptions are auto-populated using the description field from when you upload the document.

== Changelog ==

= Coming Soon! =

* Option to include player for any music files uploaded to page.
* Option to open documents directly within your browser (&#224; la [Google Drive Viewer](https://drive.google.com/viewer)).
* Support for adding your own filetypes/icons.
* Whatever else **you** would like (post on the [support forum](http://wordpress.org/support/plugin/document-gallery) if you have ideas)!

= 1.0.4 =

* **Enhancement:** Added support for choosing whether to link directly to media file or to attachment page.

= 1.0.3 =

* **Bug Fix:** Resolved issue with detecting plugin directory. (Thanks, Brigitte!)
* **Enhancement:** Minor improvement to how linking to individual documents is han= 1.0.2 =

= 1.0.2 =

* **Bug Fix:** Merge for changes in 1.0 did not go through correctly so users downloaded the old icon set which broke the functioning. Sorry about that, but
all is resolved with this release!

= 1.0.1 =

* **Bug Fix:** Resolved issue with long document titles being cut off in some themes.

= 1.0 =

* **Enhancement:** Optimized gallery generation (faster!)
* **Enhancement:** Plugin now has **36 icons** representing **72 filetypes**!
* **Enhancement:** Added fallback to WordPress default icons if you happen to include one of the few filetypes not yet supported.
* **Enhancement:** Changed shortcode to `[dg]` (`[document gallery]` will still work for backward compatibility).
* **Enhancement:** Gave documentation some **much needed** revisions.

= 0.8.5 =

* **Enhancement:** Added support for [OpenDocuments](http://en.wikipedia.org/wiki/OpenDocument).

= 0.8 =

* **Release:** First public release of Document Gallery.
* **Feature:** Displays PDF, Word, PowerPoint, Excel, and ZIP documents from a given page or post.
* **Feature:** Documents can be ordered by a number of different factors.
