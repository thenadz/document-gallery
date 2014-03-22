=== Document Gallery ===
Contributors: dan.rossiter
Tags: attachments, thumbnail, documents, gallery, MS office, pdf
Requires at least: 3.6
Tested up to: 3.8.1
Stable tag: 2.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin generates thumbnails for documents and displays them in a
gallery-like format for easy sharing.

== Description ==

This plugin allows the user to effortlessly create a gallery of documents and
other attached media, much like the gallery option already available for image
attachments.

The plugin will, by default, inherit the styling within your active theme, but
with a little CSS knowledge it is possible to customize the appearance to meet
your specific needs.

Read more in the **Installation** tab!

= Developers =

Document Gallery includes features intended to make integration with other plugins
simple. See the bottom of the **Installation** tab for specific documentation on
the various features provided.

*If this plugin has helped you, please take a moment to [rate
it](http://wordpress.org/support/view/plugin-reviews/document-gallery#postform)!*

== Installation ==

1. Upload `document-gallery` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dg]` in any posts or pages you want a document gallery included. See
below for additional display options.

= Document Gallery Options =

In order to include all compatible documents from a given page or post, you
must include the following shortcode in the post: `[dg]`.

In addition to the default behavior, the plugin provides many options to
customize behavior with various attributes, seen below:

`[dg [fancy=true] [attachment_pg=<true/false>]
[category/custom_taxon_name=<**comma-separated list of taxon values**> [relation=<AND/OR>]]
[descriptions=<true/false>] [ids=<**comma-separated list of ID #s**>]
[images=<true/false>] [localpost=<true/false>] [order=<ASC/DEC>] [orderby=<**see below**>]]`

Though the shortcode above may seem far from "short," none of the attributes are
required and most users will find that the plugin meets your needs "out of the box"
without any added attributes.

**Default Values**

By default, document gallery will use `no descriptions`, `orderby menu_order`
, `ASC order`, `no attachment_pg links`, and `no images` from the `local post`
if you do not specify otherwise. These defaults can be configured in your dashboard
under `Settings -> Document Gallery`.

**Attachment Page Option** *(New in Version 1.1)*

This option determines whether each document icon will link to the actual file
or to its attachment page.  If you want the user to be able to click on the
icon and directly rective the option to download then use `attachment_pg=false`
(the default). If you have information on the attachment page that you want the
link to go to, use `attachment_pg=true`.

**Categories/Custom Taxonomy Option** *(New in Version 1.4)*

With the `categories` option you are able to select attachments based on
their assigned category or any other
[custom taxon](http://codex.wordpress.org/Taxonomies). Categories
or any custom taxon can be referenced simply by including `category=category_value`
or `taxon_name=taxon_value`. Multiple values for a single taxon may be separated
by commas.

**Descriptions Option**

If `true`, each document will take its own line with the description displayed
alongside it.

*Note: this will use the `description` field, **not** the `caption`. Be
careful when entering your document data.*

**Fancy** *(New in Version 2.0)*

If `true`, we will try to generate a thumbnail for each document in the gallery.
The success in generating thumbs will depend mostly on what your server supports.
To fine-tune how thumbnails are generated, visit `Settings -> Document Gallery`
in your site's dashboard.

*NOTE: By default, the most universally-supported option for generating thumbnails,
[Google Drive Viewer](https://docs.google.com/viewer) is disabled by default
in order to protect your privacy, since using it requires sending your documents
to Google's servers. If you're not working with confidential documents, you are
encouraged to enable this for optimum performance.*

**Order Option**

This option works alongside the `orderby` option to determine whether the
documents are displayed in ascending or descending order.

**Orderby Option**

* `menu_order` - This is probably the one you want to use. Menu order is
  the order that icons appear when seen in the Insert / Upload Media Gallery
  dialog. To change this order, you simply drag the icons around until they
  are where you want them. In earlier versions of WordPress, menu_order was
  modified by the integer fields in the Insert / Upload Media Gallery dialog.
  These fields no longer exist in recent releases.
* `title` - Order by title.
* `date` - Order by upload date.
* `modified` - Order by last modified date.
* `rand` - Random order.
* `ID` - Order by post id.
* `author` - Order by author.
* `name` - Order by attachment slug.
* `parent` - Order by post/page parent id.
  (Only useful in conjunction with `localpost=false` option.)
* `comment_count` - Order by number of comments (available with WP >= 2.9).
* `none` - No order (available with Version 2.8).
* `post__in` - Preserve post ID order given in the post__in array.

**Images Option** *(New in Version 1.2)*

This option will tell the plugin to include all images attached to to a page or
post in addition to all documents.

**IDs Option** *(New in Version 1.2)*

This is an advanced option intended for experienced WordPress users. If this
option is used, the plugin will ignore attached documents, instead including
all attachments defined by the `ids` attribute (e.g.: `ids=10,2,4,42`).

*Note: If this attribute is used, the `order`, `orderby`, `images` and other
attributes which generally determine which attachments to include or how to
order them will be ignored. Order is defined by the order the ids are
provided.*

**Localpost Option** *(New in Version 1.4)*

By default a document gallery only looks at attachments of the page/post where
the `[dg]` shortcode is used. If you would like to search beyond that local scope,
you must set `localpost=false`.

This option would probably be useful especially when querying with the *category
or taxonomy* option, though it can be used with any options you chose.

**Relation Option** *(New in Version 1.4)*

The relation option should only be used when also using the *category or custom
taxonomy* option (see above). Additionally, this option is only effective in
WordPress installs version 3.1 or higher. Older versions cannot use this value
and will ignore it.

When using multiple taxa, this option allows you to decide whether the attachments
returned must meet all of the taxa_names specified (AND) or a minimum of one
match (OR).

= Customize Appearance =

By default, the document gallery will use the styles within your active theme
to handle most of the appearance, but, with a little CSS knowledge, you can
customize pretty much anything about how it looks. See
[`style.css`](http://plugins.svn.wordpress.org/document-gallery/trunk/assets/css/style.css)
for an idea of what will select different elements within the gallery display.

**Example**

Say I would like to include a border for the right and bottom of the document
icon, but only when descriptions are shown (to delineate the icon from the
description text). To do this, I would need to add the following CSS to my
theme stylesheet:

`.document-icon-wrapper.descriptions .document-icon{
   border-right: 1px solid #37824A;
   border-bottom: 1px solid #37824A;
}`

Now, if I wanted to modify that code to instead add the same border to all of
the document-icons, regardless of whether they have a description or not, I
would just change the first line, removing the descriptions class like so:

`.document-icon-wrapper .document-icon`

*NOTE: Please don't modify the plugin stylesheet directly or your changes will
be lost when a new version is released.*

= Developers =

**Filter .document-icon Content**

For those unfamiliar with content filters, [here is some
documentation](http://codex.wordpress.org/Plugin_API/Filter_Reference) that you
should read before continuing.

Document Gallery implements its own filter, allowing developers to customize
the output generated. Specifically, the `div.document-icon` content, including
the div itself, the URL to the attachment, the attachment icon, and the
attachment title. Hooking into the `dg_doc_icon` filter will allow you to
modify any of this content before it reaches your users.

Any function using this filter will receive two parameters, the content to be
filtered and the ID number of the file represented by the icon in question.
If you are implementing something to override the plugin default functionality,
it may be useful to be able to query various attributes of the attachment with
this value.

One example use for this filter, which I have personally used in a project I
am working on, will add a query parameter to the end of each attachment URL.
This parameter, `rid`, specifies the referring page and allows the page
receiving the URL to dynamically detect which page ID the link came from.

`function dg_doc_icon( $icon, $id ){
   $ptn = '/(.* href=")([^"]+)(".*)/s';

   if( !preg_match( $ptn, $icon, $matches ) || count( $matches ) !== 4 )
      return $icon;

   if( strpos( $matches[2], '?' ) !== false )
      return "{$matches[1]}{$matches[2]}&rid=".get_the_ID().$matches[3];

   return "{$matches[1]}{$matches[2]}?rid=".get_the_ID().$matches[3];
}
add_filter( 'dg_doc_icon', 'dg_doc_icon', null, 2 );`

Obviously this is just one very specific example, but anything that requires
modifying the image tag, the anchor tag, or the title can be handled with this
filter. Note that this function does not use the $id value it receives, which
is perfectly alright.

== Screenshots ==

1. This is an example of "fancy" thumbnails. The images are a copy of the front
page for each document.
2. This is an example of multiple Document Galleries on a single page (using
the `ids` attribute). It also shows how images will appear in a Document
Gallery. Note that the description field supports HTML markup, so the
possibilities are endless!
3. This is how the Document Gallery looks with `descriptions=false` (default).
Note that the display inherits styling from your active theme.

== Changelog ==

= Features For The Future =

* Full integration with the new [Wordpress 3.5 Media
  Manager](http://codex.wordpress.org/Version_3.5#Highlights).
* Option to open music or video files directly from your gallery.
* Option to open documents directly from your gallery (&#224; la [Google Drive
  Viewer](https://drive.google.com/viewer)).
* Support for adding your own filetypes/icons.
* Whatever else **you** would like (post on the [support
  forum](http://wordpress.org/support/plugin/document-gallery) if you have
  ideas)!

= 2.0 =
* **Enhancement:** This release is a **BIG** deal! We are introducing true
  document thumbnails (rather than the boring static images that were the same
  for every document), meaning that you will be able to generate and display
  thumbnails for most of your documents so your users can see a preview of the
  document before downloading. This has been
  [months in development](http://wordpress.org/support/topic/pdf-thumbnails-instead-of-generic-icon)
  and I really hope that you all enjoy it!
* **Enhancement:** Document Gallery now has a settings page where you can
  configure the default options for your galleries and chose how thumbnails are
  generated.
* **Enhancement:** Customizing CSS for your document gallery is now *much easier*.
  If you want to add additional styling, just navigate to `Settings -> Document Gallery`
  in your dashboard and enter valid CSS in the "Custom CSS" textbox. See the changes
  instantly in your galleries!
* **Enhancement:** Entire plugin is now
  [Internalization-enabled](https://codex.wordpress.org/I18n_for_WordPress_Developers).
  This means that we can now support users speaking all languages. If you are
  interested in translating Document Gallery into a language that you speak,
  please [let me know](http://wordpress.org/support/topic/seeking-translators)!
* **Enhancement:** This release saw much of the backend refactored to better
  support future development. Nothing you will notice unless you're digging into
  the code, but it will keep me sane long-term ;)
* **Note:** The thumbnail generation implementation works very hard to support
  all hosting servers (including Unix and Windows systems). That said, I cannot
  test on all hosts out there, so there is the potential for bugs to appear.
  If you notice something that doesn't look right, please don't hesitate to
  [report the issue](http://wordpress.org/support/plugin/document-gallery)
  so that I can resolve it. Thanks!

= 1.4.3 =
* **Bug Fix:** Resolves minor bug introduced in version 1.4.2. Thanks, tkokholm!

= 1.4.2 =
* **Note:** This release includes an increase in the minimum WP version to 3.5.
  If you have not yet upgraded to at least this version, you should consider doing
  so as future releases include a number of *fantastic* new features as well as
  many security improvements. If you chose not to upgrade, you must stay with
  Document Gallery 1.4.1 or lower until you do. Sorry for the inconvenience!
* **Bug Fix:** Resolved icons being displayed differently depending on which
  user was currently logged in. (Thanks to
  [Sean](http://wordpress.org/support/topic/error-after-update-19?replies=12#post-5041251)
  for reporting the issue.)
* **Enhancement:** A number of new icons were added (mainly for the iWork suite
  and source code filetypes) and a number of pre-existing icons were removed if
  they were very similar to another icon.
* **Under The Hood:** Many, many cool things. Stay tuned for a big reveal in the
  coming weeks!
  PS: If you're really curious, there are some clues in the source code ;)

= 1.4.1 =
* **Bug Fix:** This resolves a bug introduced in `1.4`, which caused a warning
  to be thrown when no attributes were used (i.e.: `[dg]`). (Thanks to
  [wtfbingo](http://wordpress.org/support/topic/error-after-update-19) for
  pointing this out!)

= 1.4 =

* **New Feature:** This release features the addition of *category/taxonomy* support,
  [as suggested by Pyo](http://wordpress.org/support/topic/sorting-documents-by-categorytag-or-other-taxonomy).
* **Under The Hood:** The plugin was completely rewritten for this release. Logic
  was cleaned up to make maintenance easier and facilitate some *big* changes
  planned for version 2.0 of Document Gallery.

= 1.3.1 =

* **Bug Fix:** This resolves a bug introduced in version `1.3`. (Thanks to JKChad
  for pointing this out!)

= 1.3 =

* **New Feature:** It is now possible to filter the HTML produced to represent
  each individual icon, making it possible to add extra attributes and other
  modifications on the fly as document icons are generated. This will probably
  only be of use to developers and people who don't mind getting their hands
  dirty. *(See bottom **Installation** tab for more details.)*
* **Enhancement:** There have been a lot of optimizations to the underlying
  plugin code to make it run more efficiently and be easier to read, if you
  are so inclined.
* **Enhancement:** Changed how images, when included within the gallery, are
  generated so that the format of the icon returned now matches the rest of
  the icons.

= 1.2.1 =

* **Bug Fix:** Resolved issue with the `ids` attribute in `1.2` not working.
  Sorry about that!

= 1.2 =

* **New Feature:** Images can now be included alongside documents in a
  document gallery (using `images=true` attribute).
  (Thanks for the suggestion, Luca!)
* **New Feature:** Attachment ids can now be explicitly listed, allowing for
  documents not attached to a post or page to be included in a document
  gallery (e.g.: `ids=2,42,57,1`). Note that no spaces should be included.
* **Enhancement:** The CSS stylesheet has been enhanced for more flexibility
  in sizing icons.

= 1.1 =

* **New Feature:** Included option to link to the attachment page as well as
  to the actual document.
* **Enhancement:** Added documentation for customizing the appearance of the plugin.
* **Enhancement:** Many improvements to the backend, including pretty HTML output
  and best practice implementation in calls to WordPress core functions.

= 1.0.4 =

* **Bug Fix:** Removed extra `div` at bottom when number of documents is
  evenly divisible by 4. (Thanks, joero4ri!)

= 1.0.3 =

* **Bug Fix:** Resolved issue with detecting plugin directory. (Thanks,
  Brigitte!)
* **Enhancement:** Minor improvement to how linking to individual
  documents is handled.

= 1.0.2 =

* **Bug Fix:** Merge for changes in 1.0 did not go through correctly so users
  downloaded the old icon set which broke the plugin. Sorry about that, but
  all is resolved with this release!

= 1.0.1 =

* **Bug Fix:** Resolved issue with long document titles being cut off in some themes.

= 1.0 =

* **New Feature:** Plugin now has **36 icons** representing **72 filetypes**!
* **Enhancement:** Optimized gallery generation (faster!)
* **Enhancement:** Added fallback to WordPress default icons if you happen to
  include one of the few filetypes not yet supported.
* **Enhancement:** Changed shortcode to `[dg]` (`[document gallery]` will still
  work for backward compatibility).
* **Enhancement:** Gave documentation some **much needed** revisions.

= 0.8.5 =

* **Enhancement:** Added support for
  [OpenDocuments](http://en.wikipedia.org/wiki/OpenDocument).

= 0.8 =

* **Release:** First public release of Document Gallery.
* **Feature:** Displays PDF, Word, PowerPoint, Excel, and ZIP documents from a
  given page or post.
* **Feature:** Documents can be ordered by a number of
  different factors.