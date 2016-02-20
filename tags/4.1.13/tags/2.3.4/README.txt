=== Document Gallery ===
Contributors: dan.rossiter, demur
Tags: attachments, thumbnail, documents, gallery, MS office, pdf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=EE5LWRLG933EN&lc=US&item_name=Document%20Gallery%20Plugin&item_number=document%2dgallery&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Requires at least: 3.6
Tested up to: 4.0.1
Stable tag: 2.3.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin generates thumbnails for documents and displays them in a
gallery-like format for easy sharing.

== Description ==

This plugin allows the user to effortlessly create a gallery of documents and
other attached media, much like the gallery option already available for image
attachments.

Watch the following video for a brief demonstration of Document Gallery in action:
[youtube https://www.youtube.com/watch?v=pCQsx5z-uOU]

Read more in the **Installation** tab!

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
customize behavior with various attributes, seen below:

`[dg [fancy=<true/false>] [attachment_pg=<true/false>]
[category/custom_taxon_name=<**comma-separated list of taxon values**> [relation=<AND/OR>]]
[descriptions=<true/false>] [ids=<**comma-separated list of ID #s**>]
[images=<true/false>] [localpost=<true/false>] [order=<ASC/DESC>] [orderby=<**see below**>]]`

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
by commas. Note that if a taxon value contains spaces then the entire comma-
delimited list must be quoted.

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

*NOTE: By default, the most universally-supported option for generating thumbnails,
[Google Drive Viewer](https://docs.google.com/viewer) is disabled by default
in order to protect your privacy, since using it requires sending your documents
to Google's servers. If you're not working with confidential documents, you are
encouraged to enable this for optimum performance.*

**Limit Option** *(New in Version 2.3)*

As the name suggests, this value will limit how many results are returned in the gallery.
If set to *-1*, the limit is infinite.

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
taxonomy* option (see above).

When using multiple taxa this option allows you to decide whether the attachments 
returned must match all of the different taxa specified (AND) or a minimum of one 
taxa match (OR).

*NOTE: This has no bearing on the relationship between different terms for a single
taxon (eg: `[dg category=x,y,z relation=AND]` will return any attachments where the
category is x, y, OR z). If you wish to return only attachments with all 3 categories,
you will instead need to use the following syntax: 
`[dg category=x,y,z category_relation=AND]`. This syntax of *taxon*_relation will
work for any taxon, not just "category."*

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

*NOTE: The `dg_doc_icon` has been deprecated with the release and is
scheduled to be removed in a future release. If you are using this
filter, you are encouraged to replace its usages with `dg_icon_template`.*

Each of the following filters provides an bool argument which indicates
whither the gallery being generated will display descriptions, which
allows you to handle galleries with and without descriptions differently.

If you wish to wrap your galleries in some additional content, the 
`dg_gallery_template` is the tool for the job. With it you can include
content prior to or following your document galleries. The filter
exposes 1 special tag which is replaced during gallery generation
with data specific to that gallery. The tag is described below:

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


If you wish to modify the HTML that wraps individual icons, the 
`dg_icon_template` filter is what you will use. The filter is passed
two arguments which may be used to gain additional information about
the document that will be used in generating this icon. The first
argument is a bool value which indicates whether descriptions will
be used along with the icon and the second value is an integer WordPress
attachment ID which may be used to lookup any relevant information
you need specific to that document. The filter exposes 5 special tags
which are replaced during gallery generation with document-specific data.
These tags are as follows:

* **%link%**: The URL that will be loaded when the user clicks the icon.
* **%img%**: The URL pointing the the image that will be displayed.
* **%title%**: The human-readable title of the attachment.
* **%title_attribute%**: The escaped title (above), safe for using HTML tag attributes.
* **%description%**: The attachment description (only present when rendering descriptions).


**Filter Thumbnail Generation Methods**

Document Gallery provides the `dg_thumbers` filter, which allows developers to
add, remove, or even re-order which methods are used to generate a thumbnail
for a given attachment.

The value being filtered is an associative array with keys equal to a regular
expression matching all file extensions supported by the generator and values
equal to [callables](http://www.php.net/manual/en/language.types.callable.php) 
which take an **attachment ID** and a **file page number** as arguments.

The callable given should return false if thumbnail generation fails or
a system path to a **temporary** copy of the generated image if generation
succeeds. The caller will manipulate the file at the returned path so **do not** pass
in a file path to the original copy of anything as it will be destroyed. Also, do not
worry about any image resizing or giving the file a sensible name as the caller
of your method will resize and rename the file before returning.

The following is an example taken from the Document Gallery source (with a few
modifications for ease of readability), where we add thumbnail generation for
all Audio/Video filetypes supported by WordPress:

`function dg_filter_thumbers($thumbers) {
    $av_file_types = array_merge(wp_get_audio_extensions(), wp_get_video_extensions());
    $exts = implode('|', $av_file_types);
    $thumbers[$exts] = 'dg_get_audio_video_thumbnail';
}
add_filter('dg_thumbers', 'dg_filter_thumbers', 10);

function dg_get_audio_video_thumbnail($ID, $pg) {
    include_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment = get_post($ID);
    $doc_path = get_attached_file($ID);

    // get the file metadata
    if (preg_match('#^video/#', get_post_mime_type($attachment))) {
       $metadata = wp_read_video_metadata($doc_path);
    }
    elseif (preg_match('#^audio/#', get_post_mime_type($attachment))) {
       $metadata = wp_read_audio_metadata($doc_path);
    }

    // unsupported mime type || no embedded image present
    if(!isset($metadata) || empty($metadata['image']['data'])) {
       return false;
    }

    $ext = 'jpg';
    switch ($metadata['image']['mime']) {
       case 'image/gif':
          $ext = 'gif';
          break;
       case 'image/png':
          $ext = 'png';
          break;
    }

    $tmp_dir = untrailingslashit(get_temp_dir());
    $temp_file = $tmp_dir . DIRECTORY_SEPARATOR . wp_unique_filename($tmp_dir, md5(time()) . ".$ext");

    if (!$fp = @fopen($temp_file, 'wb')) {
       return false;
    }

    if (!@fwrite($fp, $metadata['image']['data'])) {
       fclose($fp);
       return false;
    }

    fclose($fp);

    return $temp_file;
}`

== Frequently Asked Questions ==


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

= Q: Why isn't Google Drive Viewer enabled by default? =

A: Google Drive Viewer is the most commonly-supported thumbnail generation method,
alongside the Audio/Video generation, but is disabled by default. The reason
for this is that in order to use this method, Document Gallery has to send your
document over to Google's servers, where Google will generate the thumbnail for
you. For most users, this shouldn't be a big deal, but since some users
retain sensitive documents on their site, this was made opt-in to avoid
compromising anyone's security. If you don't have sensitive documents, I
would recommend enabling it, since it's currently the only way to generate a
thumbnail for any of the Microsoft Office files, as well as some less common
file types.

= Q: Ghostscript is installed on my server, but it's not working! =

A: Document Gallery does a pretty good job of detecting where Ghostscript is installed,
but on some installs it may need a little help. To check whether this is the case,
navigate to `Dashboard -> Settings -> Document Gallery` and see if there is a notice
next to the Ghostscript checkbox indicating that your server is not properly configured.
If that notice does exist, the next step is to go to the `Advanced` tab on that same page
and see if the Ghostscript path is set. If it is not, you'll need to manually fill it
with the location for your Ghostscript install (eg: `/usr/local/bin/gs`). Once that
change is saved, the Ghostscript checkbox should be enabled on the first tab.

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

To see a list of features planned for the future as well as to propose your own
ideas for future Document Gallery development, take a look at our
[issue tracker](https://github.com/thenadz/document-gallery/issues).

= 2.3.4 =
* **Bug Fix:** A bug was introduced that broke the `ids` parameter. This is resolved now.

= 2.3.3 =
* **Bug Fix:** Update script was failing following new release. This resolves that issue.

= 2.3.2 =
* **Translation:** Russian and Ukrainian translations have been updated.

= 2.3.1 =
* **Bug Fix:** Resolved a couple of bugs introduced with new `2.3` functionality.

= 2.3 =
* **Enhancement:** Taxonomy support now includes handling for both relationships
  between different taxons and relationships between different terms within a single
  taxon. See installation tab for more details.
* **Enhancement:** You can now limit how many results are displayed in the gallery with
  the *limit* attribute.
* **Enhancement:** The *post_type* and *post_status* used when generating
  a gallery are now configurable. (In most cases, these should be left at their default
  values, however advanced users may find a use case for this functionality.)
* **Enhancement:** Support was added for detecting when your site is running behind a
  firewall or on a local network where Google Drive Viewer will not be able to function.
* **Enhancement:** Handling of custom CSS was improved. Page load speed should be improved
  in some cases.
* **Bug Fix:** When Ghostscript chokes on a PDF, it will no longer print the error message
  in the Document Gallery output (instead it will end up in Document Gallery Logging).

= 2.2.7 =
* **Bug Fix:** There was an issue with a few phrases not being translated in the
  admin dialogs. Dates in the logs were also not being properly translated.

= 2.2.6 =
* **Enhancement:** Improved how Ghostscript executable is detected.

= 2.2.5 =
* **Bug Fix:** Resolves a bug where document descriptions were not being displayed
  correctly.
* **Translation:** Thanks, Marc Liotard and [Traffic Influence](http://www.trafic-influence.com/)
  for updating the French translation to include new phrases throughout the plugin!

= 2.2.4 =
* **Translation:**: Thanks to [mepmepmep](http://wordpress.org/support/profile/mepmepmep)
  who has just updated the Document Gallery Swedish translation!

= 2.2.3 =
* **Enhancement:** This will only be relevant to developers. `%descriptions%` tag
  is now available in the `dg_icon_template` filter.

= 2.2.2 =
* **Bug Fix:** Resolves minor issue in `2.2.1` that resulted in a warning being
  logged while interacting with the new thumbnail management table in the
  Document Gallery settings.

= 2.2.1 =
* **Bug Fix:** PHP installs older than 5.3 were crashing with version 2.2. This release
  patches the issue.

= 2.2 =
* **Note:** This release is the first release where development has been done by
  multiple people. I would like to give a massive thank you to
  [demur](http://wordpress.org/support/profile/demur) who has been an equal
  partner throughout the development of this version. Couldn't have done it without you!
* **Note:** With multiple developing this project, it made sense to setup
  a formal method to track issues and possible future enhancements. With this in mind
  we've begun to maintain an [issue tracker](https://github.com/thenadz/document-gallery/issues).
  Feel free to read through possible future features and even suggest new features
  you would like to see!
* **Enhancement:** You can now view which thumbnails have been generated and manually
  delete individual thumbnails from the Document Gallery settings page, located at
  Dashboard -> Settings -> Document Gallery.
* **Enhancement:* The logging for Document Gallery is now **much** more advanced.
  Logging can be configured and viewed directly from the Document Gallery settings
  page.
* **Enhancement:** Max width and height of generated thumbnails is now configurable.
* **Enhancement:** We had a couple of reports of the Document Gallery options
  being corrupted in some installs, so we added functionality to force validation
  of option structure on save. This will not be of much use to most users, but
  will help us track down some of the more difficult to reproduce bugs.
* **Enhancement:** For developers. New filters have been added to support modifying all
  aspects of HTML generated by the plugin. Look at the Installation tab for documentation
  on these new filters.

= 2.1.1 =
* **Translation:** Thanks to [mepmepmep](http://wordpress.org/support/profile/mepmepmep)
  who has translated Document Gallery into Swedish!
* **Translation:** Thanks to Marc Liotard who has translated Document Gallery into French!
* **Note:** If you would like to help translate Document Gallery into another language,
  get started [here](http://wordpress.org/support/topic/seeking-translators)!
* **Note:** This is an extremely minor release, but big changes are on the
  way in version `2.2`! Stay tuned as it should be going live in the very
  near future. Look for a complete makeover of the admin options including
  lots of new ways to configure DG to best meet your needs!

= 2.1 =
* **Enhancement:** Document Gallery now supports
  [multisite networks](http://codex.wordpress.org/Create_A_Network).

= 2.0.10 =
* **Enhancement:** Ghostscript detection should now work correctly on GoDaddy
  and some other hosts that don't properly setup their executables.
* **Translation:** Thanks *again* to
  [demur](http://wordpress.org/support/profile/demur) who has translated
  Document Gallery into Russian and Ukrainian! If you would like to help
  translate Document Gallery into another language, get started
  [here](http://wordpress.org/support/topic/seeking-translators)!

= 2.0.9 =
* **Bug Fix:** The `order` attribute was documented and implemented as being `ASC`
  or `DEC`, but the latter should actually have been `DESC`. Documentation and 
  implementation for this option has been corrected. Thanks again to
  [demur](http://wordpress.org/support/profile/demur) for catching this!

= 2.0.8 =
* **Enhancement:** Ghostscript will now handle PS and EPS files if enabled.
* **Bug Fix:** There were a couple of issues in how the `ids` attribute was being
  handled. Thanks, [demur](http://wordpress.org/support/profile/demur) for catching
  these!

= 2.0.7 =
* **Bug Fix:** `2.0.6` did not fully resolve the bug described below. This should.

= 2.0.6 =
* **Bug Fix:** If DG failed to automagically detect the location of the
  Ghostscript binary, manually setting the location did not enable using it.
  Thanks for tracking this bug down,
  [Chris](http://wordpress.org/support/profile/fredd500)!
* **Minor:** Included various enhancements in handling thumbnail generation
  for image types.
* **Tested Up To:** Document Gallery has been tested in WP 3.9 (RC 1).

= 2.0.5 =
* **Bug Fix:** Rolling back part of CSS enhancments in 2.0.4 that were causing
  errors on some servers. May revisit at a later date.

= 2.0.4 =
* **Enhancement:** Custom CSS is now loaded faster, meaning faster page loads
  for your users. Tests are showing a speedup of around 30% over where it was
  in the last release.
* **Enhancement:** All of the default icons were sent through
  [Yahoo! Smush.it](http://www.smushit.com/ysmush.it/), giving a few percentage
  points decrease in size. Thanks for the suggestion,
  [wm](http://wordpress.org/support/profile/webbmasterpianise)!

= 2.0.3 =
* **Enhancement:** Now handles custom user CSS more securely.
* **Enhancement:** Now handles calling Ghostscript executable more securely.
* **Enhancement:** Now provides timing information for gallery generation
  when running WordPress in [WP_DEBUG](https://codex.wordpress.org/WP_DEBUG)
  mode. When enabled, DG will log to the PHP error log file.
* **Info:** Did you know that in tests I performed, Ghostscript (GS) performed
  350% faster than using Imagick (IM)? Try testing with 
  [this file](http://www.open-std.org/jtc1/sc22/wg14/www/docs/n1570.pdf),
  which finished almost instantly using GS, but took multiple minutes when
  using IM on my test server (results may vary). See new FAQ tab to find out why.

= 2.0.2 =
* **Bug Fix:** Imagick was actually never working... My bad -- it is now! Thanks to
  [kaldimar](http://wordpress.org/support/profile/kaldimar) for reporting this.
* **Enhancement:** Document Gallery en el Español por Andrew de
  [WebHostingHub](http://www.webhostinghub.com/). (To help translate to another
  language, [see here](http://wordpress.org/support/topic/seeking-translators).)

= 2.0.1 =
* **Bug Fix:** Resolves issue with `2.0` where DG options were not properly
  initialized when the plugin was updated. This caused the settings page to
  behave oddly and many other things throughout to not work as expected when
  you updated through the dashboard. Thanks to jawhite & rigbypa for
  [reporting this](http://wordpress.org/support/topic/errors-after-updating-to-20)!

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
  [Internationalization-enabled](https://codex.wordpress.org/I18n_for_WordPress_Developers).
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