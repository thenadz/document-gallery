<?php
defined('WPINC') OR exit;

class DG_Admin {

   /**
    * Renders Document Gallery options page.
    */
   public static function renderOptions() { ?>
<div class="wrap">
<h2>Document Gallery Settings</h2>

<form method="post" action="options.php">
   <?php settings_fields(DG_OPTION_NAME); ?>
   <?php do_settings_sections('document_gallery'); ?>
   <?php submit_button(); ?>
</form>

</div>
   <?php }

   /**
    * Adds settings link to main plugin view.
    */
   public static function addSettingsLink($links) {
      $settings = '<a href="options-general.php?page=document_gallery">' .
          __('Settings', 'document-gallery') . '</a>';
      array_unshift($links, $settings);
      return $links;
   }

   /**
    * Adds Document Gallery settings page to admin navigation.
    */
   public static function addAdminPage() {
      $page = add_options_page(
          __('Document Gallery Settings', 'document-gallery'),
          __('Document Gallery', 'document-gallery'),
          'manage_options', 'document_gallery', array(__CLASS__, 'renderOptions'));

      add_action('admin_print_styles-' . $page, array(__CLASS__, 'enqueueAdminStyle'));
   }

   /**
    * Registers stylesheet for admin options page.
    */
   public static function registerAdminStyle() {
      wp_register_style('dg-admin', DG_URL . 'admin/css/style.css', null, DocumentGallery::version());
   }

   /**
    * Registers settings for the Document Gallery options page.
    */
   public static function registerSettings() {
      global $dg_options;

      include_once DG_PATH . 'inc/class-gallery.php';

      $defaults = $dg_options['gallery']['defaults'];
      $thumber_active = $dg_options['thumber']['active'];
      $thumber_gs = $dg_options['thumber']['gs'];

      register_setting(DG_OPTION_NAME, DG_OPTION_NAME, array(__CLASS__, 'validateSettings'));

      add_settings_section(
        'gallery_defaults', __('Default Settings', 'document-gallery'),
        array(__CLASS__, 'renderDefaultSettingsSection'), 'document_gallery');

      add_settings_section(
        'thumber_active', __('Thumbnail Generation', 'document-gallery'),
        array(__CLASS__, 'renderThumberSection'), 'document_gallery');

      add_settings_section(
        'thumber_advanced', __('Advanced Thumbnail Generation', 'document-gallery'),
        array(__CLASS__, 'renderThumberAdvancedSection'), 'document_gallery');

      add_settings_field(
        'gallery_defaults_attachment_pg', 'attachment_pg',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_attachment_pg',
            'name'        => 'gallery_defaults][attachment_pg',
            'value'       => esc_attr($defaults['attachment_pg']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Link to attachment page rather than to file', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_descriptions', 'descriptions',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_descriptions',
            'name'        => 'gallery_defaults][descriptions',
            'value'       => esc_attr($defaults['descriptions']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Include document descriptions', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_fancy', 'fancy',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_fancy',
            'name'        => 'gallery_defaults][fancy',
            'value'       => esc_attr($defaults['fancy']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Use auto-generated document thumbnails', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_images', 'images',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_images',
            'name'        => 'gallery_defaults][images',
            'value'       => esc_attr($defaults['images']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Include image attachments in gallery', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_localpost', 'localpost',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_localpost',
            'name'        => 'gallery_defaults][localpost',
            'value'       => esc_attr($defaults['localpost']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Only look for attachments in post where [dg] is used', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_order', 'order',
        array(__CLASS__, 'renderSelectField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_order',
            'name'        => 'gallery_defaults][order',
            'value'       => esc_attr($defaults['order']),
            'options'     => DG_Gallery::getOrderOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Ascending or decending sorting of documents', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_orderby', 'orderby',
        array(__CLASS__, 'renderSelectField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_orderby',
            'name'        => 'gallery_defaults][orderby',
            'value'       => esc_attr($defaults['orderby']),
            'options'     => DG_Gallery::getOrderbyOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Which field to order documents by', 'document-gallery')
        ));

      add_settings_field(
        'gallery_defaults_relation', 'relation',
        array(__CLASS__, 'renderSelectField'),
        'document_gallery', 'gallery_defaults',
        array (
            'label_for'   => 'label_gallery_defaults_relation',
            'name'        => 'gallery_defaults][relation',
            'value'       => esc_attr($defaults['relation']),
            'options'     => DG_Gallery::getRelationOptions(),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Whether matched documents must have all taxa_names (AND) or at least one (OR)', 'document-gallery')
        ));

      add_settings_field(
        'thumber_active_av', 'Audio/Video',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'thumber_active',
        array (
            'label_for'   => 'label_thumber_active_av',
            'name'        => 'thumber_active][av',
            'value'       => esc_attr($thumber_active['av']),
            'option_name' => DG_OPTION_NAME,
            'description' => esc_html__('Locally generate thumbnails for audio & video files.', 'document-gallery')
        ));

      add_settings_field(
        'thumber_active_gs', 'Ghostscript',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'thumber_active',
        array (
            'label_for'   => 'label_thumber_active_gs',
            'name'        => 'thumber_active][gs',
            'value'       => esc_attr($thumber_active['gs']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Use <a href="http://www.ghostscript.com/" target="_blank">Ghostscript</a>
               for faster local PDF processing (compared to Imagick).', 'document-gallery')
        ));

      add_settings_field(
        'thumber_active_imagick', 'Imagick',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'thumber_active',
        array (
            'label_for'   => 'label_thumber_active_imagick',
            'name'        => 'thumber_active][imagick',
            'value'       => esc_attr($thumber_active['imagick']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Use <a href="http://www.imagemagick.org/" target="_blank">Imagick</a>
               to handle lots of filetypes locally.', 'document-gallery')
        ));

      add_settings_field(
        'thumber_active_google', 'Google Drive Viewer',
        array(__CLASS__, 'renderCheckboxField'),
        'document_gallery', 'thumber_active',
        array (
            'label_for'   => 'label_thumber_active_google',
            'name'        => 'thumber_active][google',
            'value'       => esc_attr($thumber_active['google']),
            'option_name' => DG_OPTION_NAME,
            'description' => __('Use <a href="https://drive.google.com/viewer" target="_blank">Google Drive Viewer</a>
               to generate thumbnails for MS Office files and many other file types remotely.', 'document-gallery')
        ));

      add_settings_field(
        'thumber_advanced_gs', 'Ghostscript Absolute Path',
        array(__CLASS__, 'renderTextField'),
        'document_gallery', 'thumber_advanced',
        array (
            'label_for'   => 'label_thumber_advanced_gs',
            'name'        => 'thumber_advanced][gs',
            'value'       => esc_attr($thumber_gs),
            'option_name' => DG_OPTION_NAME,
            'description' => $thumber_gs
               ? __('Successfully auto-detected the location of Ghostscript.', 'document-gallery')
               : __('Failed to auto-detect the location of Ghostscript.', 'document-gallery')
        ));
   }

   /**
    * Render the Default Settings section.
    */
   public static function renderDefaultSettingsSection() { ?>
      <p><?php _e('The following values will be used by default in the shortcode. You can still
         manually set each of these values in each individual shortcode.', 'document-gallery'); ?></p>
   <?php }

   /**
    * Render the Thumber section.
    */
   public static function renderThumberSection() { ?>
      <p><?php _e('Select which tools to use when generating thumbnails.', 'document-gallery'); ?></p>
   <?php }

   /**
    * Render the Thumber Advanced section.
    */
   public static function renderThumberAdvancedSection() {
      include_once DG_PATH . 'inc/class-thumber.php';?>
      <p><?php _e('Unless you <em>really</em> know what you\'re doing, you should not touch these values.', 'document-gallery'); ?></p>
      <?php if (!DG_Thumber::isExecAvailable()) : ?>
      <p><em><?php _e('NOTE: <code>exec()</code> is not accessible. Ghostscript will not function.', 'document-gallery'); ?></em></p>
      <?php endif; ?>
   <?php }

   /**
    * Render a checkbox field.
    * @param array $args
    */
   public static function renderCheckboxField($args) {
      printf('<input type="checkbox" value="1" name="%1$s[%2$s]" id="%3$s" %4$s/> %5$s',
          $args['option_name'],
          $args['name'],
          $args['label_for'],
          checked($args['value'], 1, false),
          $args['description']);
   }

   /**
    * Render a text field.
    * @param array $args
    */
   public static function renderTextField($args) {
      printf('<input type="text" value="%1$s" name="%2$s[%3$s]" id="%4$s" /> %5$s',
          $args['value'],
          $args['option_name'],
          $args['name'],
          $args['label_for'],
          $args['description']);
   }

   /**
    * Render a select field.
    * @param array $args
    */
   public static function renderSelectField($args) {
      printf('<select name="%1$s[%2$s]" id="%3$s">',
          $args['option_name'],
          $args['name'],
          $args['label_for']);

      foreach ($args['options'] as $val) {
         printf('<option value="%1$s" %2$s>%3$s</option>',
             $val,
             selected($val, $args['value'], false),
             $val,
             $args['description']);
      }

      print '</select> ' . $args['description'];
   }

   /**
    * Validates submitted options, sanitizing any invalid options.
    * @param array $values User-submitted new options.
    * @return array Sanitized new options.
    */
   public static function validateSettings($values) {
      include_once DG_PATH . 'inc/class-gallery.php';

      global $dg_options;
      $ret = $dg_options;

      // handle gallery shortcode defaults
      $errs = array();
      $ret['gallery']['defaults'] =
          DG_Gallery::sanitizeDefaults($values['gallery_defaults'], $errs);

      foreach ($errs as $k => $v) {
         add_settings_error(DG_OPTION_NAME, str_replace('_', '-', $k), $v);
      }

      // handle setting the active thumbers
      foreach ($ret['thumber']['active'] as $k => $v) {
         $ret['thumber']['active'][$k] = isset($values['thumber_active'][$k]);
      }

      // if new thumbers available, clear failed thumbnails for retry
      foreach ($dg_options['thumber']['active'] as $k => $v) {
         if (!$dg_options['thumber']['active'][$k] && $ret['thumber']['active'][$k]) {
            include_once DG_PATH . 'inc/class-thumber.php';
            DG_Thumber::deleteFailedThumbMeta();
            break;
         }
      }

      // handle setting the Ghostscript path
      if (isset($values['thumber_advanced']['gs']) &&
          0 != strcmp($values['thumber_advanced']['gs'], $ret['thumber']['gs'])) {
         if (false === strpos($values['thumber_advanced']['gs'], ';')) {
            $ret['thumber']['gs'] = $values['thumber_advanced']['gs'];
         } else {
            add_settings_error(DG_OPTION_NAME, 'thumber-gs',
                __('Invalid Ghostscript path given: ', 'document-gallery')
                . $values['thumber_advanced']['gs']);
         }
      }

      return $ret;
   }

   /**
    * Enqueues stylesheet for admin options page.
    */
   public static function enqueueAdminStyle() {
      wp_enqueue_style('dg-admin');
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}