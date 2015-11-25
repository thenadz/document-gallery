<?php /* Custom templates into the DOM */
	include_once DG_PATH . 'inc/class-gallery.php';
?>
<script type="text/html" id="tmpl-dg-settings">
	<h3><?php _e('Document Gallery Settings', 'document-gallery'); ?></h3>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Link To'); ?></span></td>
			<td><select class="link-to" data-setting="attachment_pg">
				<option value="false" <#
					if ( !wp.media.dgDefaults.attachment_pg ) {
						#>selected="selected"<#
					}
				#>><?php esc_attr_e('Media File'); ?></option>
				<option value="true" <#
					if ( wp.media.dgDefaults.attachment_pg ) {
						#>selected="selected"<#
					}
				#>><?php esc_attr_e('Attachment Page'); ?></option>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Columns'); ?></span></td>
			<td><select class="columns" name="columns" data-setting="columns">
					<option value="-1" <#
						if ( '-1' == wp.media.dgDefaults.columns ) {
							#>selected="selected"<#
						}
					#>>&infin;</option>
				<?php for ( $i = 1; $i <= 9; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( <?php echo $i ?> == wp.media.dgDefaults.columns ) {
								#>selected="selected"<#
						}
					#>><?php echo esc_html( $i ); ?></option>
				<?php endfor; ?>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Open thumbnail links in new window', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="new_window" <#
				if ( wp.media.dgDefaults.new_window ) {
					#>checked="checked"<#
				} #> />
			</td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Include document descriptions', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="descriptions" <#
				if ( wp.media.dgDefaults.descriptions ) {
					#>checked="checked"<#
				} #> />
			</td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Use auto-generated document thumbnails', 'document-gallery'); ?></span></td>
			<td><input type="checkbox" data-setting="fancy" <#
				if ( wp.media.dgDefaults.fancy ) {
					#>checked="checked"<#
				} #> />
			</td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Which field to order documents by', 'document-gallery'); ?></span></td>
			<td><select data-setting="dgorderby">
				<?php foreach ( DG_GallerySanitization::getOrderbyOptions() as $i ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( '<?php echo $i ?>' == wp.media.dgDefaults.dgorderby ) {
							#>selected="selected"<#
						}
					#>><?php echo esc_html( $i ); ?></option>
				<?php endforeach; ?>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Ascending or descending sorting of documents', 'document-gallery'); ?></span></td>
			<td><select data-setting="dgorder">
				<?php foreach ( DG_GallerySanitization::getOrderOptions() as $i ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <#
						if ( '<?php echo $i ?>' == wp.media.dgDefaults.dgorder ) {
							#>selected="selected"<#
						}
					#>><?php echo esc_html( $i ); ?></option>
				<?php endforeach; ?>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Paginate', 'document-gallery'); ?></span></td>
			<td><select data-setting="paginate">
				<option value="false" <#
					if ( !wp.media.dgDefaults.paginate ) {
						#>selected="selected"<#
					}
				#>><?php _e('No', 'document-gallery'); ?></option>
				<option value="true" <#
					if ( wp.media.dgDefaults.paginate ) {
						#>selected="selected"<#
					}
				#>><?php _e('Yes', 'document-gallery'); ?></option>
			</select></td>
		</tr></table>
	</label>

	<label class="setting">
		<table><tr>
			<td><span><?php _e('Limit', 'document-gallery'); ?></span></td>
			<td><input data-setting="limit" type="number" min="-1" step="1" value="{{ wp.media.dgDefaults.limit }}" /></td>
		</tr></table>
	</label>
</script>
<script type="text/html" id="tmpl-editor-dg">
</script>