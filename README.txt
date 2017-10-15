=== Document Gallery ===
Contributors: dan.rossiter, demur
Tags: attachments, library, thumbnail, documents, gallery, word, pdf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=EE5LWRLG933EN&lc=US&item_name=Document%20Gallery%20Plugin&item_number=document%2dgallery&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Requires at least: 4.2
Tested up to: 4.8
Stable tag: 4.4.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin generates thumbnails for documents and displays them in a
gallery-like format for easy sharing.

== Description ==

This plugin allows the user to effortlessly create a gallery of documents and
other attached media, much like the gallery option already available for image
attachments.

Watch the following video for a brief demonstration of Document Gallery in action:
[youtube http://www.youtube.com/watch?v=Xb7RVzfeUUg]

Read more in the **Installation** tab!

= Partners =

* **WP Real Media Library**: Document Gallery seamlessly integrates with RML by way of a
  [helper plugin](https://wordpress.org/plugins/dg-real-media-library/).

= Translation =

Document Gallery has to-date been translated into **6** languages, listed below.

* **Finnish** &#8212; *Thanks, [villevetela](https://wordpress.org/support/profile/villevetela)!*
* **French** &#8212; *Thanks, Marc Liotard and [Traffic Influence](http://www.trafic-influence.com/)!*
* **Russian** &#8212; *Thanks, [demur](http://wordpress.org/support/profile/demur)!*
* **Spanish** &#8212; *Thanks, Andrew Kurtis and [WebHostingHub](http://www.webhostinghub.com/)!*
* **Swedish** &#8212; *Thanks, [mepmepmep](http://wordpress.org/support/profile/mepmepmep)!*
* **Ukrainian** &#8212; *Thanks, [demur](http://wordpress.org/support/profile/demur)!*

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
customize behavior with various attributes, some of which are shown below:

`[dg [fancy=<true/false>] [attachment_pg=<true/false>] [descriptions=<true/false>] [order=<ASC/DESC>] [orderby=<**see below**>]]`

Though the shortcode above may seem far from "short," none of the attributes are
required and most users will find that the plugin meets your needs "out of the box"
without any added attributes.

**Default Values**

Default document gallery behavior can be configured in your dashboard under `Settings -> Document Gallery`.

**Attachment Page Option** *(New in Version 1.1)*

This option determines whether each document icon will link to the actual file
or to its attachment page.  If you want the user to be able to click on the
icon and directly receive the option to download then use `attachment_pg=false`
(the default). If you have information on the attachment page that you want the
link to go to, use `attachment_pg=true`.

**Categories/Custom Taxonomy Option** *(New in Version 1.4)*

With the `categories` option you are able to select attachments based on
their assigned category or any other
[custom taxon](http://codex.wordpress.org/Taxonomies). Categories
or any custom taxon can be referenced simply by including `category=category_value`
or `taxon_name=taxon_value`. Multiple values for a single taxon may be separated
by commas. Note that if a taxon value contains spaces then the entire comma-
delimited list must be quoted.

**Columns Option** *(New in Version 3.0)*

The columns option does what it sounds like -- sets how many columns to use in
rendering your gallery. With `columns=-1`, you will get an infinite number of
columns. In other words, only 1 row with all icons.

**Descriptions Option**

If `true`, each document will take its own line with the description displayed
alongside it.

*Note: this will use the `description` field, **not** the `caption`. Be
careful when entering your document data.*

**Fancy Option** *(New in Version 2.0)*

If `true`, we will try to generate a thumbnail for each document in the gallery.
The success in generating thumbs will depend mostly on what your server supports.
To fine-tune how thumbnails are generated, visit `Settings -> Document Gallery`
in your site's dashboard.

**ID Option** *(New in Version 3.0)*

This option indicates from which parent post/page to retrieve attachments.
If not explicitly set, the default will be the post/page where the shortcode
is being used.

If you do not wish to filter by parent, `id=-1` will match all attachments.

**IDs Option** *(New in Version 1.2)*

This is an advanced option intended for experienced WordPress users. If this
option is used, the plugin will ignore attached documents, instead including
all attachments defined by the `ids` attribute (e.g.: `ids=10,2,4,42`).

*Note: If this attribute is used, order defaults to the order of IDs given
rather than menu_order unless order is explicitly stated.*

**Images Option** *(New in Version 1.2)*

This option will tell the plugin to include all images attached to to a page or
post in addition to all documents.

**Include/Exclude Options** *(New in Version 3.0)*

As the name suggests, these options allow for explicitly adding or removing
matched attachments in your gallery. Like with the IDs options above, these
options take a comma-delimited list of attachment IDs.

**Limit Option** *(New in Version 2.3)*

As the name suggests, this value will limit how many results are returned in the gallery.
If set to *-1*, the limit is infinite.

**MIME Types Option** *(New in Version 3.0)*

This is a comma-delimited list of all
[MIME types](http://en.wikipedia.org/wiki/Internet_media_type#List_of_common_media_types)
to be included in the gallery. Most users will not need to modify this value.

One example use-case would be to include images in your gallery (which are
not included by default). To do this, you would simply set
`mime_types=application,video,text,audio,image`, where "image" is the only difference
from the default value. You could also create a gallery which only includes PDFs
by setting `mime_types=application/pdf`.

**New Window Option** *(New in Version 3.2)*

If true, clicking one of the documents in your gallery will open the target link in a new window/tab.

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

**Relation Option** *(New in Version 1.4)*

The relation option should only be used when also using the *category or custom
taxonomy* option (see above).

When using multiple taxa this option allows you to decide whether the attachments
returned must match all of the different taxa specified (AND) or a minimum of one
taxa match (OR).

*NOTE: This has no bearing on the relationship between different terms for a single
taxon (eg: `[dg category=x,y,z relation=AND]` will return any attachments where the
category is x, y, OR z). If you wish to return only attachments with all 3 categories,
you will instead need to use the following syntax: `[dg category=x,y,z category_relation=AND]`.
This syntax of *taxon*_relation will work for any taxon, not just "category."*

= Customize Appearance =

The Default Document gallery will often fit quite well with whatever theme you
are using. But, if you want to change things, Document Gallery makes that easy.
Just navigate to `Settings -> Document Gallery` and put any custom CSS in the
provided text box.

See [`style.css`](http://plugins.svn.wordpress.org/document-gallery/trunk/assets/css/style.css)
for all of the ids and classes being used in a Document Gallery.

**Example**

Say I would like to include a border for the right and bottom of the document
icon, but only when descriptions are shown (to delineate the icon from the
description text). To do this, I would need to use the following CSS:

`.document-icon-wrapper.descriptions .document-icon{
   border-right: 1px solid #37824A;
   border-bottom: 1px solid #37824A;
}`

Now, if I wanted to modify that code to instead add the same border to all of
the document-icons, regardless of whether they have a description or not, I
would just change the first line, removing the descriptions class like so:

`.document-icon-wrapper .document-icon`

= Developers =

For those unfamiliar with content filters, [here is some
documentation](http://codex.wordpress.org/Plugin_API/Filter_Reference) that you
should read before continuing.

**Filter HTML Output**

In Documnet Gallery version 2.2, we've released a more powerful HTML
templating framework, making all generated output filterable, and thus
configurable, by developers wishing to control the gallery output. Three
different filters are provided in order to access the various segments
of a gallery: `dg_gallery_template`, `dg_row_template`, and `dg_icon_template`.
These filtered templates are used when dynamically generating output for each
gallery.

Each of the following filters provides an bool argument which indicates
whither the gallery being generated will display descriptions, which
allows you to handle galleries with and without descriptions differently.

If you wish to wrap your galleries in some additional content,
the `dg_gallery_template` is the tool for the job. With it you can include
content prior to or following your document galleries. The filter
exposes 2 special tags which are replaced during gallery generation
with data specific to that gallery. The tag is described below:

* **%id%**: The id attribute value for this gallery.
* **%class%**: The class attribute value for this gallery.
* **%data%**: The one ore more data-* attributes for the gallery, which are necessary for client-side operations.
* **%rows%**: This tag is replaced by all of the document gallery rows.
  Everything before this string will be rendered before the gallery and
  everything after this string will be rendered following the gallery.


If you wish to modify how gallery rows are generated, `dg_row_template`,
is provided for this purpose. This filter gives you control at the row
level for how a gallery will be generated. The filter exposes 2 special tags
which are replaced during gallery generation with row-specific data.
These tags are as follows:

* **%class%**: The class attribute value for this row.
* **%icons%**: The icon data for this row.


If you wish to modify the HTML that wraps individual icons,
the `dg_icon_template` filter is what you will use. The filter is passed
two arguments which may be used to gain additional information about
the document that will be used in generating this icon. The first
argument is a bool value which indicates whether descriptions will
be used along with the icon and the second value is an integer WordPress
attachment ID which may be used to lookup any relevant information
you need specific to that document. The filter exposes 5 special tags
which are replaced during gallery generation with document-specific data.
These tags are as follows:

* **%date%**: The date that the attachment was added to WordPress.
* **%description%**: The attachment description (only present when rendering descriptions).
* **%extension%**: The document file extension.
* **%img%**: The URL pointing the the image that will be displayed.
* **%link%**: The URL that will be loaded when the user clicks the icon.
* **%path%**: The system path pointing to the document.
* **%size%**: The human-readable file size formatted by
  [size_format](https://codex.wordpress.org/Function_Reference/size_format).
* **%target%**: The target attribute for the anchor tag (e.g.: _blank, _self).
* **%time%**: The time that the attachment was added to WordPress.
* **%title%**: The human-readable title of the attachment.
* **%title_attribute%**: The escaped title (above), safe for using HTML tag attributes.


Below is an example that uses all three above filter methods to convert the output from a div-based
layout to a table-based layout with no icons, displaying the date of upload in the left column and
the title as a hyperlink to the attachment in the right column.

`function dg_gallery_template($gallery, $use_descriptions) {
     if ( ! $use_descriptions ) {
         $gallery = '<table id="%id%" class="%class%" %data%>%rows%</table>';
     }
     return $gallery;
 }
 add_filter( 'dg_gallery_template', 'dg_gallery_template', 10, 2 );

 function dg_row_template($row, $use_descriptions) {
     if ( ! $use_descriptions ) {
         $row = '<tr class="%class%">%icons%</tr>';
     }
     return $row;
 }
 add_filter( 'dg_row_template', 'dg_row_template', 10, 2 );

 function dg_icon_template($icon, $use_descriptions, $id) {
     if ( ! $use_descriptions ) {
         $icon = '<td>%date%</td><td><a href="%link%" target="%target%"><span class="title">%title%</title></td>';
     }
     return $icon;
 }
 add_filter( 'dg_icon_template', 'dg_icon_template', 10, 3 );`

**Filter Thumbnail Generation Methods**

Document Gallery provides the `dg_thumbers` filter, which allows developers to
add, remove, or even re-order which methods are used to generate a thumbnail
for a given attachment.

The value being filtered is an array of `DG_AbstractThumber` objects. You will want to look at this abstract class,
located in the DG source under `inc/thumbers` to see how to correctly implement the abstract methods. You'll notice
that `DG_AbstractThumber::init()` will handle creating a singleton instance of your class (though you can opt not
to use this logic if you prefer). To register your implementation of `DG_AbstractThumber` using `init()`, you would
simply call YourThumberClass::init() and all of the work setting up the `dg_thumbers` filter to include your thumber
will be done for you.

The following is an example taken from the Document Gallery source (with a few
modifications for ease of readability), where we add thumbnail generation for
all Audio/Video filetypes supported by WordPress:

`class ImageThumber extends DG_AbstractThumber {

 	/**
 	 * @return string[] The extensions supported by this thumber.
 	 */
 	protected function getThumberExtensions() {
 		return array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' );
 	}

 	/**
 	 * @param string $ID The attachment ID to retrieve thumbnail from.
 	 * @param int $pg Unused.
 	 *
 	 * @return bool|string  False on failure, URL to thumb on success.
 	 */
 	public function getThumbnail( $ID, $pg = 1 ) {
 		$options = DG_Thumber::getOptions();
 		$ret     = false;

 		if ( $icon = image_downsize( $ID, array( $options['width'], $options['height'] ) ) ) {
 			$ret = $icon[0];
 		}

 		return $ret;
 	}

 	/**
 	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
 	 */
 	public function getPriority() {
 		return 100;
 	}
 }

 // tells DG_AbstractThumber to create an instance of the class and apply to dg_thumbers filter
 ImageThumber::init();`

**Filter Inclusion of Default Document Gallery CSS**

If you wish to completely replace Document Gallery styling with your own CSS, you can prevent any any
CSS being loaded by returning false in `dg_use_default_gallery_style` filter, like so:
`add_filter('dg_use_default_gallery_style', '__return_false');`

*NOTE: By design, this will **NOT** disable inclusion of any custom CSS set at
`Dashboard -> Settings -> Document Gallery`*

**Modify Gallery Query**

If you wish to modify the query used by Document Gallery to retrieve attachments to be included in a gallery then
the `dg_query` action allows you to do exactly that. The signature for the action
is `function dg_query(&$query, $taxa, &$excluded_keys, &$errs)`. Each parameter is described below:

* **&$query**: This is the query to be modified as documented in [WordPress Codex](https://codex.wordpress.org/Class_Reference/WP_Query).
* **$taxa**: The list of key/value pairs for arguments passed into `[dg]` that do not match known keys.
* **&$excluded_keys**: The list of keys to not be processed by DG. If your extension uses a key and is not intended for
  DG to process, then the key should be added to this list.
* **&$errs**: The list of errors. If something goes wrong, this list should be appended with a description of what happened.

== Frequently Asked Questions ==


= Q: I'm using taxonomies, but nothing is showing up in my gallery =

A: Remember that Document Gallery defaults to retrieving just attachments for the current post/page.
If you want a broader scope of attachments, you'll also need tell Document Gallery to search everywhere
like so: `[dg id=-1 category="My Awesome Category"]`.

= Q: Ghostscript is installed on my server, but it's not working! =

A: Document Gallery does a pretty good job of detecting where Ghostscript is installed,
but on some installs it may need a little help. To check whether this is the case,
navigate to `Dashboard -> Settings -> Document Gallery` and see if there is a notice
next to the Ghostscript checkbox indicating that your server is not properly configured.
If that notice does exist, the next step is to go to the `Advanced` tab on that same page
and see if the Ghostscript path is set. If it is not, you'll need to manually fill it
with the location for your Ghostscript install (eg: `/usr/local/bin/gs`). Once that
change is saved, the Ghostscript checkbox should be enabled on the first tab.

= Q: Why are all of my document icons in a single column? =

A: Assuming that you do not have the `columns` attribute set to 1, the likely cause
of this behavior is that descriptions are enabled. To fix this, simply add `descriptions=false`
(eg: `[dg descriptions=false]`).

= Q: Why is [insert thumbnail generation method] enabled on one of my WordPress installs, but not on another one? =

A: Document Gallery works very hard behind the scenes to ensure that it enables
as much as is possible for any given server, but some servers just can't do
some of the things that the plugin supports. Document Gallery detects when a
server can't do something (run Ghostscript, for example) and disables that option.
If you later modify your server to handle one of the thumbnail generation methods,
Document Gallery will notice this and re-enable the option on the settings page,
though you will need to go in and tell Document Gallery that it should use this
newly-enabled method.

= Q: Why is Ghostscript so much faster than Imagick? =

A: This comes down to how the two programs work. Imagick actually delegates
handling of PDFs to Ghostscript behind the scenes, but it doesn't do so
intelligently. Before passing off the PDF, it first reads the entire contents
of the PDF into memory. Since we only need a single page to generate the
thumbnail, this is much more work than is needed. Ghostscript, on the other hand,
can handle reading only one page into memory, thus doing much less work before
returning our thumbnail.

== Screenshots ==

1. Document Gallery integrates directly with the WordPress Media Manager.
2. The common configuration options are directly accessible through the Media Manager
interface, but additional configuration can be manually added to the generated
shortcode.
3. This is an example of "fancy" thumbnails. The images are a copy of the front
page for each document.
4. This is an example of multiple Document Galleries on a single page (using
the `ids` attribute). It also shows how images will appear in a Document
Gallery. Note that the description field supports HTML markup, so the
possibilities are endless!
5. This is how the Document Gallery looks with `descriptions=false` (default).
Note that the display inherits styling from your active theme.

== Changelog ==

= Features For The Future =

To see a list of features planned for the future as well as to propose your own
ideas for future Document Gallery development, take a look at our
[issue tracker](https://github.com/thenadz/document-gallery/issues).

= 4.4.3 =
* **Bug Fix:** The pagination logic was not working correctly, resulting in the gallery not returning to the
  top after changing to a new page in some situations.

= 4.4.2 =
* **Enhancement:** When pagination is enabled for a gallery, navigating to another page will now scroll the page
  back to the top of the gallery.
* **Enhancement:** Added support for `%author%` to `dg_icon template` filter.

= 4.4.1 =
* **Enhancement:** Added support for `%date%` and `%time%` to  `dg_icon_template`.

= 4.4 =
* **Enhancement:** Document Gallery updated to support integration with
  [WP Real Media Library](https://codecanyon.net/item/wordpress-real-media-library-media-categories-folders/13155134).
  Simply install the [RML/DG helper plugin](https://wordpress.org/plugins/dg-real-media-library/) to use your RML
  folders right from Document Gallery!

= 4.3.2 =
* **Bug Fix:** Bug in editing gallery from post editor has been resolved.
* **Note:** Minimum WordPress version has been bumped from 4.1 to 4.2.

= 4.3.1 =
* **Enhancement:** WordPress 4.7 includes native support for PDF thumbnails. Document Gallery was already using some
  of this, but this update ensures that DG takes full advantage of the new core functionality, including some new
  logic to allow older WordPress installs running Document Gallery to take advantage of some of the new goodies.
* **Tested Up To:** Document Gallery has been tested in WP 4.7. Big thanks to
  [Bjarne](https://wordpress.org/support/users/oldrup/) for help with testing.
* **Reminder:** Don't forget that [Thumber.co](https://thumber.co) can integrate with Document Gallery to greatly
  expand supported file types (eg: Word, PowerPoint, Publisher, and Photoshop). Thumber.co offers a free 1-week trial
  if you want to try it before you buy it!

= 4.2.6 =
* **Bug Fix:** Document Gallery was impossible to uninstall on some WordPress systems. This is addressed now.

= 4.2.5 =
* **Bug Fix:** Resolves issue where visual editor gallery preview got stuck loading.

= 4.2.4 =
* **Tested Up To:** Document Gallery has been tested in WP 4.6.
* **Bug Fix:** Resolves bug in visual editor that would result in the gallery preview never loading in some circumstances.

= 4.2.3 =
* **Bug Fix:** Ghostscript thumbnail generation where the attachment name includes non-ASCII characters was failing
  on some server configurations. Kevin Hock identified the bug AND provided the fix. Gold star!

= 4.2.2 =
* **Bug Fix:** Ghostscript-based thumbnail generation was failing in a rare corner case. Thanks
  [sigvevidnes](https://wordpress.org/support/profile/sigvevidnes) for identifying this issue!

= 4.2.1 =
* **Bug Fix:** There was a bug in validating user secrets for [Thumber.co](https://thumber.co) that has now been addressed.

= 4.2 =
* **Enhancement:** Adding support for `tax_name`_include_children attribute, as requested by
  [John](https://wordpress.org/support/topic/add-shortcode-attribute-for-include_children). Thanks for the suggestion!
* **Enhancement:** [Thumber.co](https://thumber.co) authentication secret is no longer output in the options
  dump on the admin settings tab, removing the possibility of this information being inadvertently printed in the
  support forum.

= 4.1.14 =
* **Bug Fix:** The handling of saving [Thumber.co](https://thumber.co) subscriptions was broken for some use cases.
  The logic has been updated to work correctly in all cases.

= 4.1.13 =
* **Enhancement:** HostGator users were noticing an issue where HTTP connection failures would show up while Document
  Gallery was enabled. This resulted in symptoms like not being able to update plugins. This release modifies behavior
  to try and bypass this HostGator shortcoming. Work is still being done working with HostGator to try and identify
  a more complete resolution.

= 4.1.12 =
* **Bug Fix:** Resolves issues where the visual editor gallery preview was missing for a small subset of shortcode types.

= 4.1.11 =
* **Bug Fix:** Resolves bug that resulted in various issues, including Jetpack and some core WordPress functionality
  not working correctly.

= 4.1.10 =
* **Bug Fix:** Resolves drag/drop issues in the meida manager.

= 4.1.9 =
* **Bug Fix:** Addresses issues with the IMagick thumbnail generation.
* **Enhancement:** Handling thumbnail generation for image attachments better (should be faster & more reliable).

= 4.1.8 =
* **Bug Fix:** There was a minor bug in how thumbnails for image attachments were being generated. It has been addressed.

= 4.1.7 =
* **Bug Fix:** There was a minor issue on some admin pages that has been resolved.

= 4.1.6 =
* **Enhancement:** Added warning to plugins page when a PHP version < 5.3 is being used.
* **Bug Fix:** Resolves a "class not found" error.

= 4.1.5 =
* **Bug Fix:** For a subset of the users upgrading from `4.0` to `4.1.x`, the thumbnail images will have been corrupted
  during the upgrade process. This release addresses the problem.

= 4.1.1 & 4.1.2 & 4.1.3 & 4.1.4 =
* **Bug Fix:** Resolves various errors reported following `4.1` release.

= 4.1 =
* **Enhancement:** At long last, support for Microsoft Office files (Word, PowerPoint, Publisher, Visio), as well as a
  boat-load of [other formats](https://www.thumber.co/about#filetypes), has been re-added to Document Gallery by way of
  integration with the [Thumber.co](https://www.thumber.co) service. For a small fee you can generate images for all of your
  attachments using a service designed specifically to work well with Document Gallery. **For a limited time,
  Thumber.co is offering a free 7-day trial of the basic subscription. If you don't like it, all you have to do is
  cancel and you won't pay a penny.**
* **Enhancement:** The pagination footer now includes more than just "prev" and "next", allowing for quicker navigation
  of long multi-page galleries. Additionally, the pagination footer will no longer be included if pagination is enabled,
  but there are less than a page-length's worth of attachments in the gallery.
* **Enhancement:** Massive rewrite of some core logic that had become unmaintainable. This will mean nothing to most
  users, with the noted exception that if you were using the `dg_thumbers` filter you'll need to change some things.
  If this applies to you then you'll want to hold off on upgrading until you've had a chance to rework your usage of the
  filter to map to the new expected values.

= 4.0 =
* **Enhancement:** The WordPress visual editor now displays a full gallery preview.
* **Enhancement:** You can now paginate your galleries. This is especially useful in large multi-hundred item galleries.
  To enable pagination in your galleries, simply use `limit=##`.
* **Enhancement:** All CSS & JavaScript is now served minified to ensure the fastest possible load time for your site.
* **Enhancement:** When using taxonomies to generate your galleries (eg: media categories) you can now use term slug
  instead of the name. *Thanks andremalenfant for suggesting this!*
* **Enhancement:** The structure of the gallery output has been cleaned up, making it easier to style if you chose to
  use custom CSS. *NOTE: This modified structure may break existing custom CSS or PHP filtering, so be sure to check
  this if you're using either of those features.*
* **Bug Fix:** The storage of the DG thumbnail cache was very broken. Due to how the cache was originally designed, it
  ran into issues at large scale and on busy sites, which resulted in difficult to track bugs. The entire storage
  mechanism for the cache has been rewritten from the ground up to address this issue, which will result in faster
  gallery generation and more reliable performance.
* **Bug Fix:** In the thumbnail management tab of the DG settings, sorting by title was broken. This has been fixed.
* **Bug Fix:** `Limit` was not working in cases where the `ids` or `include` attribute were present. This has been fixed.
* **Tested Up To:** Document Gallery has been tested in WP 4.4 beta.

**NOTE: All earlier changes may be found in the CHANGELOG.md file.**