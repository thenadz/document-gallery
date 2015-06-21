<?php /* Custom templates into the DOM */
	include_once DG_PATH . 'inc/class-gallery.php';
?>
<script type="text/html" id="tmpl-document-gallery-settings">
	<h3><?php _e('Document Gallery Settings', 'document-gallery'); ?></h3>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Link To'); ?></span></td>
			<td><select class="link-to" data-setting="attachment_pg">
				<option value="false" <# if ( !documentGalleryDefaults.attachment_pg ) { #>selected="selected"<# } #>>
					<?php esc_attr_e('Media File'); ?>
				</option>
				<option value="true" <# if ( documentGalleryDefaults.attachment_pg ) { #>selected="selected"<# } #>>
					<?php esc_attr_e('Attachment Page'); ?>
				</option>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Columns'); ?></span></td>
			<td><select class="columns" name="columns" data-setting="columns">
					<option value="-1" <#
						if ( '-1' == documentGalleryDefaults.columns ) { #>selected="selected"<# }
					#>>
						&infin;
					</option>
				<?php for ( $i = 1; $i <= 9; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( <?php echo $i ?> == documentGalleryDefaults.columns ) { #>selected="selected"<# }
					#>>
						<?php echo esc_html( $i ); ?>
					</option>
				<?php endfor; ?>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Open thumbnail links in new window', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="new_window" <# if ( documentGalleryDefaults.new_window ) { #>checked="checked"<# } #>/></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Include document descriptions', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="descriptions" <# if ( documentGalleryDefaults.descriptions ) { #>checked="checked"<# } #>/></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Use auto-generated document thumbnails', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="fancy" <# if ( documentGalleryDefaults.fancy ) { #>checked="checked"<# } #>/></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Which field to order documents by', 'document-gallery'); ?></span></td>
			<td><select class="DGorderby" name="DGorderby" data-setting="DGorderby">
				<?php foreach ( DG_Gallery::getOrderbyOptions() as $i ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( '<?php echo $i ?>' == documentGalleryDefaults.orderby ) { #>selected="selected"<# }
					#>>
						<?php echo esc_html( $i ); ?>
					</option>
				<?php endforeach; ?>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Ascending or descending sorting of documents', 'document-gallery'); ?></span></td>
			<td><select class="order" name="order" data-setting="order">
				<?php foreach ( DG_Gallery::getOrderOptions() as $i ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( '<?php echo $i ?>' == documentGalleryDefaults.order ) { #>selected="selected"<# }
					#>>
						<?php echo esc_html( $i ); ?>
					</option>
				<?php endforeach; ?>
			</select></td>
		</tr></table>
	</label>
</script>