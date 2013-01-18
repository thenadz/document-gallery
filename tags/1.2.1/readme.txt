=== Document Gallery ===
Contributors: dan.rossiter
Tags: attachments, icons, documents, gallery, ms office, doc, ppt, xls, docx, pptx, xlsx, pdf, openoffice
Requires at least: 2.6
Tested up to: 3.5
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows the user to easily create a "gallery" of all non-image attachments on a given post/page,
making them easy to share.

== Description ==

This plugin allows the user to effortlessly include a gallery, much like a photo gallery,
of all your non-image attachments anywhere within your post.

The plugin will, by default, inherit the styling within your active theme, but with a 
little CSS knowledge it is easily modified to meet your specific needs.

Read more in the **Installation** tab!

*If this plugin has helped you, please take a moment to 
[rate it](http://wordpress.org/support/view/plugin-reviews/document-gallery#postform)!*

== Installation ==

1. Upload `document-gallery` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dg]` in any posts or pages you want a document gallery included. See below for additional display options.

= Document Gallery Options =

In order to include all compatible docuements from a given page or post, you must include 
the following shortcode in the post:

`[dg descriptions=[true/false] orderby=[menu_order, title, date, author, rand] order=[ASC/DEC] 
attachment_pg=[true/false] images=[true/false] ids=[false/comma-separated list of id #s]]`

**Default Values**

By default, document gallery will use `descriptions=false`, `orderby=menu_order`, `order=ASC`
, `attachment_pg=false`, `images=false`, and `ids=false` if you do not specify otherwise.

**Descriptions Option**

If `true`, each document will take its own line with the description displayed alongside it.

*Note: this will use the `description` field, **not** the `caption`. Be careful when entering 
your document data.*

**Orderby Option**

* `menu_order` - This is probably the one you want to use. Order by the integer fields in the Insert / 
Upload Media Gallery dialog. Note that these fields may be blank by default. If this is the case, 
you must populate the fields before this option will work.
* `title` - Alphabetical order based on title.
* `date` - Order by date of document upload.
* `author` - Order by the owner of the upload (username).
* `rand` - Pseudo-random order.

**Order Option**

This option works alongsied the `orderby` option to determine whether the documents are displayed in
ascending or decending order.

**Attachment Page Option** *(New in Version 1.1)*

This option determines whether each document icon will link to the actual file or to its attachment page. 
If you want the user to be able to click on the icon and directly rective the option to download then 
use `attachment_pg=false` (the default). If you have information on the attachment page that you want the 
link to go to, use `attachment_pg=true`.

**Images** *(New in Version 1.2)*

This option will tell the plugin to pull all images attached to to a page or post in addition to all documents.

**Ids** *(New in Version 1.2)*

This is an advanced option intended for experienced WordPress users. If this option is used, the plugin will 
ignore attached documents, instead including all attachments defined by the ids attribute (e.g.: `ids=10,2,4,42`).

*Note: If this attribute is used, the `order`, `orderby`, and `images` attributes will be ignored. Order is defined
by the order the ids are provided.*

= Customize Appearance =

By default, the document gallery will use the styles within your active theme to handle most of the appearance,
but, with a little CSS knowledge, you can customize pretty much anything about how it looks. See 
[`style.css`](http://plugins.svn.wordpress.org/document-gallery/trunk/style.css) for an idea of what will 
select different elements within the gallery display.

**Example**

Say I would like to include a border for the right and bottom of thedocument icon, but only when descriptions 
are shown (to deliniate the icon from the description text). To do this, I would need to add the following 
CSS to my theme stylesheet:

`.document-icon-wrapper.descriptions .document-icon{
  border-right: 1px solid #37824A;
  border-bottom: 1px solid #37824A;
}`

Now, if I wanted to modify that code to instead add the same border to all of the document-icons, regardless of 
whether they have a description or not, I would just change the first line, removing the descriptions class like so:

`.document-icon-wrapper .document-icon`

*NOTE: Please don't modify the plugin stylesheet directly or your changes will be lost when a new version is released.*

= Theme Developers =

If you would like to include Document Gallery functionality in your theme, you simply need to include the following
code wherever you would like it to appear: `<?php echo do_shortcode('[dg]'); ?>`. You can include additional options 
(listed above) as needed.

== Screenshots ==

1. This is how the Document Gallery looks with `descriptions=false` (default). Note that the display inherits styling from your active theme.
2. This is how the Document Gallery looks with `descriptions=true`. The descriptions are auto-populated using the description field from when you upload the document.

== Changelog ==

= Coming (Relatively) Soon =

* Full integration with the new [Wordpress 3.5 Media Manager](http://codex.wordpress.org/Version_3.5#Highlights).
* Option to include player for any music or video attachments uploaded to page.
* Option to open documents directly within your browser (&#224; la [Google Drive Viewer](https://drive.google.com/viewer)).
* Support for adding your own filetypes/icons.
* Whatever else **you** would like (post on the [support forum](http://wordpress.org/support/plugin/document-gallery) if you have ideas)!

= 1.2.1 =
* **Bug Fix:** Resolved issue with the `ids` attribute in `1.2` not working. Sorry about that!

= 1.2 =

* **New Feature:** Images can now be included alongside documents in a document gallery (using `images=true` attribute).
* **New Feature:** Attachment ids can now be explicitly listed, allowing for documents not attached to a post or page 
to be included in a document gallery (e.g.: `ids=2,42,57,1`). Note that no spaces should be included.
* **Enhancement:** The CSS stylesheet has been enhanced for more flexibility in sizing icons.

= 1.1 =

* **New Feature:** Included option to link to the attachment page as well as to the actual document.
* **Enhancement:** Added documentation for customizing the appearance of the plugin.
* **Enhancement:** Many improvements to the backend, including pretty HTML output and best practice implementation in
calls to WordPress core functions.

= 1.0.4 =

* **Bug Fix:** Removed extra `div` at bottom when number of documents is evenly divisible by 4. (Thanks, joero4ri!)

= 1.0.3 =

* **Bug Fix:** Resolved issue with detecting plugin directory. (Thanks, Brigitte!)
* **Enhancement:** Minor improvement to how linking to individual documents is handled.

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
