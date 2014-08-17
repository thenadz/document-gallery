<?php
defined('WPINC') OR exit;

DG_Admin::init();

class DG_Admin {
   /**
    * @var string The hook for the Document Gallery settings page.
    */
   private static $hook;
   
   /**
    * @var string The current tab being rendered.
    */
   private static $current;
   
   /**
    * @var multitype:string Associative array containing all tab names, keyed by tab slug.
    */
   private static $tabs;
   
   /**
    * Initializes static values for this class.
    */
   public static function init() {
      if (empty(self::$tabs)) {
         self::$tabs = array(
            'General'    => __('General',                'document-gallery'),
            'Thumbnail'  => __('Thumbnail Management',   'document-gallery'),
            'Advanced'   => __('Advanced',               'document-gallery'));
      }
   }
   
   /**
    * Renders Document Gallery options page.
    */
   public static function renderOptions() { ?>
<div class="wrap">
	<h2>Document Gallery Settings</h2>

	<h2 class="nav-tab-wrapper">
<?php foreach (self::$tabs as $tab => $name) {
   $class = ($tab == self::$current) ? ' nav-tab-active' : '';
   echo '<a class="nav-tab '.$tab.'-tab'.$class.'" href="?page=document_gallery&tab='.$tab.'">'.$name.'</a>';
} ?>
</h2>

	<form method="post" action="options.php" id="tab-<?php echo self::$current?>">
		<input type="hidden" name="<?php echo DG_OPTION_NAME; ?>[tab]" value="<?php echo self::$current; ?>" />
<?php
   settings_fields(DG_OPTION_NAME);
   do_settings_sections(DG_OPTION_NAME);
   submit_button();
?>
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
      DG_Admin::$hook = add_options_page(
          __('Document Gallery Settings', 'document-gallery'),
          __('Document Gallery', 'document-gallery'),
          'manage_options', 'document_gallery', array(__CLASS__, 'renderOptions'));
      add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueScriptsAndStyles'));
   }
   
   /**
    * Enqueues styles and scripts for the admin settings page.
    */
   public static function enqueueScriptsAndStyles($hook) {
      if ($hook !== DG_Admin::$hook) return;
      
      wp_enqueue_style('document-gallery-admin', DG_URL . 'assets/css/admin.css', null, DG_VERSION);
      wp_enqueue_script('document-gallery-admin', DG_URL . 'assets/js/admin.js', array('jquery'), DG_VERSION, true);
   }

   /**
    * Registers settings for the Document Gallery options page.
    */
   public static function registerSettings() {
      if (empty($_REQUEST['tab']) || !array_key_exists($_REQUEST['tab'], self::$tabs)) {
         reset(self::$tabs);
         self::$current = key(self::$tabs);
      } else {
         self::$current = $_REQUEST['tab'];
      }

      register_setting(DG_OPTION_NAME, DG_OPTION_NAME, array(__CLASS__, 'validateSettings'));
      
      $funct = 'register' . self::$current . 'Settings';
      DG_Admin::$funct();
   }
   
   /**
    * Registers settings for the general tab.
    */
   private static function registerGeneralSettings() {
      global $dg_options;

      include_once DG_PATH . 'inc/class-gallery.php';
      include_once DG_PATH . 'inc/class-thumber.php';

      $defaults = $dg_options['gallery']['defaults'];
      $thumber_active = $dg_options['thumber']['active'];

      add_settings_section(
        'gallery_defaults', __('Default Settings', 'document-gallery'),
        array(__CLASS__, 'renderDefaultSettingsSection'), 'document_gallery');

      add_settings_section(
        'thumber_active', __('Thumbnail Generation', 'document-gallery'),
        array(__CLASS__, 'renderThumberSection'), 'document_gallery');

      add_settings_section(
          'css', __('Custom CSS', 'document-gallery'),
          array(__CLASS__, 'renderCssSection'), 'document_gallery');

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
            'description' => __('Ascending or descending sorting of documents', 'document-gallery')
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
            'description' => DG_Thumber::isGhostscriptAvailable()
                              ? __('Use <a href="http://www.ghostscript.com/" target="_blank">Ghostscript</a> for faster local PDF processing (compared to Imagick).', 'document-gallery')
                              : __('Your server is not configured to run <a href="http://www.ghostscript.com/" target="_blank">Ghostscript</a>.', 'document-gallery'),
            'disabled'    => !DG_Thumber::isGhostscriptAvailable()
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
            'description' => DG_Thumber::isImagickAvailable()
                              ? __('Use <a href="http://www.php.net/manual/en/book.imagick.php" target="_blank">Imagick</a> to handle lots of filetypes locally.', 'document-gallery')
                              : __('Your server is not configured to run <a href="http://www.php.net/manual/en/book.imagick.php" target="_blank">Imagick</a>.', 'document-gallery'),
            'disabled'    => !DG_Thumber::isImagickAvailable()
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
            'description' => DG_Thumber::isGoogleDriveAvailable()
                              ? __('Use <a href="https://drive.google.com/viewer" target="_blank">Google Drive Viewer</a> to generate thumbnails for MS Office files and many other file types remotely.', 'document-gallery')
                              : __('Your server does not allow remote HTTP access.', 'document-gallery'),
            'disabled'    => !DG_Thumber::isGoogleDriveAvailable()
        ));
   }
   
   /**
    * Registers settings for the thumbnail management tab.
    */
   private static function registerThumbnailSettings() {
      add_settings_section(
          'css', __('Custom CSS', 'document-gallery'),
          array(__CLASS__, 'renderThumbnailSection'), 'document_gallery');
   }
   
   /**
    * Registers settings for the advanced tab.
    */
   private static function registerAdvancedSettings() {
      global $dg_options;
      $gs = $dg_options['thumber']['gs'];
      
      add_settings_section(
         'advanced', __('Advanced Thumbnail Generation', 'document-gallery'),
         array(__CLASS__, 'renderAdvancedSection'), 'document_gallery');

      add_settings_field(
        'advanced_gs', 'Ghostscript Absolute Path',
        array(__CLASS__, 'renderTextField'),
        'document_gallery', 'advanced',
        array (
            'label_for'   => 'label_advanced_gs',
            'name'        => 'gs',
            'value'       => esc_attr($gs),
            'option_name' => DG_OPTION_NAME,
            'description' => $gs
               ? __('Successfully auto-detected the location of Ghostscript.', 'document-gallery')
               : __('Failed to auto-detect the location of Ghostscript.', 'document-gallery')
        ));
   }
   
   /**
    * @return bool Whether to register settings.
    */
   public static function doRegisterSettings() {
      if (!is_multisite()) {
         $script = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : null;
      } else {
         $script = parse_url($_SERVER['REQUEST_URI']);
         $script = basename($script['path']);
      }
      
      return !empty($script) && ('options-general.php' === $script || 'options.php' === $script);
   }

   /**
    * Render the Default Settings section.
    */
   public static function renderDefaultSettingsSection() { ?>
<p><?php _e('The following values will be used by default in the shortcode. You can still manually set each of these values in each individual shortcode.', 'document-gallery'); ?></p>
<?php }

   /**
    * Render the Thumber section.
    */
   public static function renderThumberSection() { ?>
<p><?php _e('Select which tools to use when generating thumbnails.', 'document-gallery'); ?></p>
<?php }

   public static function renderCssSection() {
      global $dg_options; ?>
<p><?php printf(
          __('Enter custom CSS styling for use with document galleries. To see which ids and classes you can style, take a look at <a href="%s" target="_blank">style.css</a>.'),
          DG_URL . 'assets/css/style.css'); ?></p>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<td><textarea name="document_gallery[css]" rows="10" cols="50"
					class="large-text code"><?php echo $dg_options['css']['text']; ?></textarea>
			</td>
		</tr>
	</tbody>
</table>
<?php }

   /**
    * Render the Thumber Advanced section.
    */
   public static function renderAdvancedSection() {
      include_once DG_PATH . 'inc/class-thumber.php';?>
<p><?php _e('Unless you <em>really</em> know what you\'re doing, you should not touch these values.', 'document-gallery'); ?></p>
<?php if (!DG_Thumber::isExecAvailable()) : ?>
<p>
	<em><?php _e('NOTE: <code>exec()</code> is not accessible. Ghostscript will not function.', 'document-gallery'); ?></em>
</p>
<?php endif; ?>
   <?php }

   /**
    * Render the Thumbnail table.
    */
   public static function renderThumbnailSection() {
      include_once DG_PATH . 'inc/class-thumber.php';
      $options = DG_Thumber::getOptions();

      $URL_params = array('page' => 'document_gallery', 'tab' => 'Thumbnail');
      $att_ids = array();
      
      if (isset($_REQUEST['orderby']) && in_array(strtolower($_REQUEST['orderby']), array('title', 'date'))) {
         $orderby = strtolower($_REQUEST['orderby']);
         $URL_params['orderby'] = $orderby;
         
         switch ($orderby)
         {
            case 'date':
               foreach ($options['thumbs'] as $key => $node) {
                  $keyArray[$key] = $node['timestamp'];
                  $options['thumbs'][$key]['thumb_id'] = $att_ids[] = $key;
               }
               break;
               
            case 'title':
               foreach ($options['thumbs'] as $key => $node) {
                  $keyArray[$key] = basename($node['thumb_path']);
                  $options['thumbs'][$key]['thumb_id'] = $att_ids[] = $key;
               }
               break;
         }
         
         $order = strtolower($_REQUEST['order']);
         if (!isset($_REQUEST['order']) || !in_array($order, array('asc', 'desc'))) {
            $order = 'asc';
         }
         $URL_params['order'] = $order;

         if ($order == 'asc') {
            array_multisort($keyArray, SORT_ASC, $options['thumbs']);
         } else {
            array_multisort($keyArray, SORT_DESC, $options['thumbs']);
         }
      } else {
         $orderby = '';
         foreach ($options['thumbs'] as $key => $node) {
            $options['thumbs'][$key]['thumb_id'] = $att_ids[] = $key;
         }
      }

      static $limit_options = array(10, 25, 75);
      if (!isset($_REQUEST['limit']) || !in_array(intval($_REQUEST['limit']), $limit_options)) {
         $limit = $limit_options[0];
      } else {
         $limit = intval($_REQUEST['limit']);
      }
      
      $URL_params['limit'] = $limit;
      $select_limit = '';
      foreach ($limit_options as $l_o) {
         $select_limit .= '<option value="'.$l_o.'"'.selected($limit, $l_o, false).'>'.$l_o.'</option>'.PHP_EOL;
      }
      $thumbs_number = count($options['thumbs']);
      $lastsheet = ceil($thumbs_number/$limit);
      $sheet = isset($_REQUEST['sheet']) ? intval($_REQUEST['sheet']) : 1;
      if ($sheet <= 0 || $sheet > $lastsheet) {
         $sheet = 1;
      }
      
      $offset = ($sheet - 1) * $limit;

      $att_ids = array_slice($att_ids, $offset, $limit);
      $atts = get_posts(
         array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => -1,
            'post__in'    => $att_ids,
            'orderby'     => 'post__in'
      ));
      $titles = array();
      foreach ($atts as $att) {
         $path_parts = pathinfo($att->guid);
         $titles[$att->ID] = $att->post_title.'.'.$path_parts['extension'];
      }
      unset($atts);
      
      $thead = '<tr>'.
            '<th scope="col" class="manage-column column-cb check-column %s">'.
               '<label class="screen-reader-text" for="cb-select-all-%d">'.__('Select All', 'document-gallery').'</label>'.
               '<input id="cb-select-all-%d" type="checkbox">'.
            '</th>'.
            '<th scope="col" class="manage-column column-icon">'.__('Thumbnail', 'document-gallery').'</th>'.
            '<th scope="col" class="manage-column column-title '.(($orderby != 'title')?'sortable desc':'sorted '.$order).'"><a href="?'.http_build_query(array_merge($URL_params, array('orderby'=>'title','order'=>(($orderby != 'title')?'asc':(($order == 'asc')?'desc':'asc'))))).'"><span>'.__('File name', 'document-gallery').'</span><span class="sorting-indicator"></span></th>'.
            '<th scope="col" class="manage-column column-date '.(($orderby != 'date')?'sortable asc':'sorted '.$order).' %s"><a href="?'.http_build_query(array_merge($URL_params, array('orderby'=>'date','order'=>(($orderby != 'date')?'desc':(($order == 'asc')?'desc':'asc'))))).'"><span>'.__('Date', 'document-gallery').'</span><span class="sorting-indicator"></span></th>'.
         '</tr>';

      $pagination = '<div class="alignleft actions bulkactions"><button class="button action deleteSelected">'.__('Delete Selected', 'document-gallery').'</button></div><div class="tablenav-pages">'.
            '<span class="displaying-num">'.
            $thumbs_number.' '._n('item', 'items', $thumbs_number).
            '</span>'.($lastsheet>1?
            '<span class="pagination-links">'.
               '<a class="first-page'.( $sheet==1 ? ' disabled' : '').'" title="'.__('Go to the first page', 'document-gallery').'"'.( $sheet==1 ? '' : ' href="?'.http_build_query($URL_params).'"').'>«</a>'.
               '<a class="prev-page'.( $sheet==1 ? ' disabled' : '').'" title="'.__('Go to the previous page', 'document-gallery').'"'.( $sheet==1 ? '' : ' href="?'.http_build_query(array_merge($URL_params, array('sheet'=>$sheet-1))).'"').'>‹</a>'.
               '<span class="paging-input">'.
                  '<input class="current-page" title="'.__('Current page', 'document-gallery').'" type="text" name="paged" value="'.$sheet.'" size="'.strlen($sheet).'" maxlength="'.strlen($sheet).'"> '.__('of', 'document-gallery').' <span class="total-pages">'.$lastsheet.'</span></span>'.
               '<a class="next-page'.( $sheet==$lastsheet ? ' disabled' : '').'" title="'.__('Go to the next page', 'document-gallery').'"'.( $sheet==$lastsheet ? '' : ' href="?'.http_build_query(array_merge($URL_params, array('sheet'=>$sheet+1))).'"').'>›</a>'.
               '<a class="last-page'.( $sheet==$lastsheet ? ' disabled' : '').'" title="'.__('Go to the last page', 'document-gallery').'"'.( $sheet==$lastsheet ? '' : ' href="?'.http_build_query(array_merge($URL_params, array('sheet'=>$lastsheet))).'"').'>»</a>'.
            '</span>':' <b>|</b> ').
            '<span class="displaying-num"><select dir="rtl" class="limit_per_page">'.$select_limit.'</select> '.__('items per page', 'document-gallery').'</span>'.
         '</div>'.
         '<br class="clear" />';
      ?>

<script type="text/javascript">
         var URL_params = <?php echo json_encode($URL_params); ?>;
      </script>
<div class="thumbs-list-wrapper">
	<div>
		<div class="tablenav top"><?php echo $pagination; ?></div>
		<table id="ThumbsTable" class="wp-list-table widefat fixed media"
			cellpadding="0" cellspacing="0">
			<thead>
               <?php printf($thead, 'topLeft', 1, 1, 'topRight'); ?>
            </thead>
			<tfoot>
               <?php printf($thead, 'bottomLeft', 2, 2, 'bottomRight'); ?>
            </tfoot>
			<tbody><?php
               $WP_date_format = get_option('date_format').' '.get_option('time_format');
               $i = 0;
               foreach ($options['thumbs'] as $v) {
                  if ($i < $offset) { $i++; continue; }
                  if (++$i > $offset + $limit) { break; }
                  
                  $icon = isset($v['thumb_url']) ? $v['thumb_url'] : DG_URL . 'assets/icons/missing.png';
                  $title = isset($titles[$v['thumb_id']]) ? $titles[$v['thumb_id']] : '';
                  $date = date($WP_date_format, $v['timestamp']);
                  
                  echo '<tr><td scope="row" class="check-column"><input type="checkbox" class="cb-ids" name="' . DG_OPTION_NAME . '[ids][]" value="' .
                          $v['thumb_id'].'"></td><td class="column-icon media-icon"><img src="' .
                          $icon.'" />'.'</td><td class="title column-title">' .
                          ( $title ? '<strong><a href="/?attachment_id='.$v['thumb_id'].'" target="_blank" title="'.__('View', 'document-gallery').' "' .
                          $title.'" '.__('attachment page', 'document-gallery').'">'.$title.'</a></strong>' : __('Attachment not found', 'document-gallery') ) .
                          '</td><td class="date column-date">'.$date.'</td></tr>'.PHP_EOL;
               } ?>
            </tbody>
		</table>
		<div class="tablenav bottom"><?php echo $pagination; ?></div>
	</div>
</div>
<?php }

   /**
    * Render a checkbox field.
    * @param array $args
    */
   public static function renderCheckboxField($args) {
      $args['disabled'] = isset($args['disabled']) ? $args['disabled'] : false;
      printf('<input type="checkbox" value="1" name="%1$s[%2$s]" id="%3$s" %4$s %5$s/> %6$s',
          $args['option_name'],
          $args['name'],
          $args['label_for'],
          checked($args['value'], 1, false),
          $args['disabled'] ? 'disabled="disabled"' : '',
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
      $funct = "validate{$values['tab']}Settings";
      unset($values['tab']);
      return DG_Admin::$funct($values);
   }

   /**
    * Validates general settings, sanitizing any invalid options.
    * @param array $values User-submitted new options.
    * @return array Sanitized new options.
    */
   private static function validateGeneralSettings($values) {
      global $dg_options;
      $ret = $dg_options;
      
      include_once DG_PATH . 'inc/class-gallery.php';

      // handle gallery shortcode defaults
      $errs = array();
      $ret['gallery']['defaults'] =
          DG_Gallery::sanitizeDefaults($values['gallery_defaults'], $errs);

      foreach ($errs as $k => $v) {
         add_settings_error(DG_OPTION_NAME, str_replace('_', '-', $k), $v);
      }

      // handle setting the active thumbers
      foreach (array_keys($ret['thumber']['active']) as $k) {
         $ret['thumber']['active'][$k] = isset($values['thumber_active'][$k]);
      }

      // if new thumbers available, clear failed thumbnails for retry
      foreach ($dg_options['thumber']['active'] as $k => $v) {
         if (!$v && $ret['thumber']['active'][$k]) {
            foreach ($dg_options['thumber']['thumbs'] as $k => $v) {
               if (empty($v['thumber'])) {
                  unset($ret['thumber']['thumbs'][$k]);
               }
            }
            break;
         }
      }

      // handle modified CSS
      if (trim($ret['css']['text']) !== trim($values['css'])) {
         $ret['css']['text'] = trim($values['css']);
         $ret['css']['version']++;
         $ret['css']['last-modified'] = gmdate('D, d M Y H:i:s');
         $ret['css']['etag'] = md5($ret['css']['last-modified']);
         
         if (empty($ret['css']['text'])) {
            unset($ret['css']['minified']);
         } else {
            $ret['css']['minified'] =
                    DocumentGallery::compileCustomCss($ret['css']['text']);
         }
      }

      return $ret;
   }
   
   /**
    * Validates thumbnail management settings, sanitizing any invalid options.
    * @param array $values User-submitted new options.
    * @return array Sanitized new options.
    */
   private static function validateThumbnailSettings($values) {
      global $dg_options;
      $ret = $dg_options;
      
      if (isset($values['ids'])) {
         $deleted = array_intersect(array_keys($dg_options['thumber']['thumbs']), $values['ids']);
   
         foreach ($deleted as $k) {
            if (isset($ret['thumber']['thumbs'][$k]['thumber'])) {
               @unlink($ret['thumber']['thumbs'][$k]['thumb_path']);
            }
            
            unset($ret['thumber']['thumbs'][$k]);
         }
      }
      
      return $ret;
   }
   
   /**
    * Validates advanced settings, sanitizing any invalid options.
    * @param array $values User-submitted new options.
    * @return array Sanitized new options.
    */
   private static function validateAdvancedSettings($values) {
      global $dg_options;
      $ret = $dg_options;
      
      // handle setting the Ghostscript path
      if (isset($values['gs']) &&
         0 != strcmp($values['gs'], $ret['thumber']['gs'])) {
         if (false === strpos($values['gs'], ';')) {
            $ret['thumber']['gs'] = $values['gs'];
         } else {
            add_settings_error(DG_OPTION_NAME, 'thumber-gs',
            __('Invalid Ghostscript path given: ', 'document-gallery') . $values['gs']);
         }
      }
      
      return $ret;
   }

   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}