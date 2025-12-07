/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

import ServerSideRender from '@wordpress/server-side-render';
import { MediaUpload, InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody, ToggleControl, SelectControl, __experimentalNumberControl as NumberControl } from '@wordpress/components';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const defaults = window.dgBlockConfig.dgDefaults;
	
	// Helper to get attribute value with fallback to default
	const getAttrValue = (attrName) => {
		return attributes[attrName] !== undefined ? attributes[attrName] : defaults[attrName];
	};
	
	// TODO: REGRESSION - Past dg behavior allowed for any parameters not explicitly set
	// to change if a global change to defaults was made. With migration to blocks, this is
	// no longer the case as attributes are now fixed on a per-block basis once set.
	// Consider adding a "Reset to Defaults" button to allow users to revert to
	// current default settings if desired.
	const setAttrValue = (attrName, value) => {
		setAttributes({ [attrName]: value });
	};
	
	// Get allowed MIME types from block attribute or defaults
	const mimeTypesStr = getAttrValue('mime_types');
	const allowedTypes = typeof mimeTypesStr === 'string' ? mimeTypesStr.split(',') : [];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __('Gallery Settings', 'document-gallery') } initialOpen={false}>
					<ToggleControl
						label={ __('Limit to Current Post', 'document-gallery') }
						checked={getAttrValue('localpost')}
						onChange={(value) => setAttrValue('localpost', value)}
						help={ __('Only show attachments uploaded to this post.', 'document-gallery') }
					/>
					<NumberControl
						__next40pxDefaultSize
						label={ __('Columns', 'document-gallery') }
						value={getAttrValue('columns')}
						onChange={(value) => setAttrValue('columns', parseInt(value))}
						min={1}
						step={1}
						help={ __('The number of columns to display when not rendering descriptions.', 'document-gallery') }
					/>
					<ToggleControl
						label={ __('Fancy', 'document-gallery') }
						checked={getAttrValue('fancy')}
						onChange={(value) => setAttrValue('fancy', value)}
						help={ __('Use auto-generated document thumbnails.', 'document-gallery') }
					/>
					<SelectControl
						label={ __('Relation', 'document-gallery') }
						value={getAttrValue('relation')}
						onChange={(value) => setAttrValue('relation', value)}
						options={[
							{ label: 'OR', value: 'OR' },
							{ label: 'AND', value: 'AND' }
						]}
						help={ __('Whether matched documents must have all taxa_names (AND) or at least one (OR).', 'document-gallery') }
					/>
					<NumberControl
						__next40pxDefaultSize
						label={ __('Limit', 'document-gallery') }
						value={getAttrValue('limit')}
						onChange={(value) => setAttrValue('limit', parseInt(value))}
						min={-1}
						step={1}
						help={ __('Limit the number of documents included. -1 means no limit.', 'document-gallery') }
					/>
					<ToggleControl
						label={ __('Paginate', 'document-gallery') }
						checked={getAttrValue('paginate')}
						onChange={(value) => setAttrValue('paginate', value)}
						help={ __('When a limit exists, paginate rather than truncating gallery.', 'document-gallery') }
					/>
					<SelectControl
						label={ __('Order By', 'document-gallery') }
						value={getAttrValue('orderby')}
						onChange={(value) => setAttrValue('orderby', value)}
						options={[
							{ label: 'Menu Order', value: 'menu_order' },
							{ label: 'Title', value: 'title' },
							{ label: 'Date', value: 'date' },
							{ label: 'Modified', value: 'modified' },
							{ label: 'Random', value: 'rand' }
						]}
						help={ __('Which field to order documents by.', 'document-gallery') }
					/>
					<SelectControl
						label={ __('Order', 'document-gallery') }
						value={getAttrValue('order')}
						onChange={(value) => setAttrValue('order', value)}
						options={[
							{ label: 'Ascending', value: 'ASC' },
							{ label: 'Descending', value: 'DESC' }
						]}
						help={ __('Ascending or descending sorting of documents.', 'document-gallery') }
					/>
					<ToggleControl
						label={ __('Descriptions', 'document-gallery') }
						checked={getAttrValue('descriptions')}
						onChange={(value) => setAttrValue('descriptions', value)}
						help={ __('Include document descriptions.', 'document-gallery') }
					/>
					<ToggleControl
						label={ __('Open in New Window', 'document-gallery') }
						checked={getAttrValue('new_window')}
						onChange={(value) => setAttrValue('new_window', value)}
						help={ __('Open thumbnail links in new window.', 'document-gallery') }
					/>
					<ToggleControl
						label={ __('Show Attachment Page Link', 'document-gallery') }
						checked={getAttrValue('attachment_pg')}
						onChange={(value) => setAttrValue('attachment_pg', value)}
						help={ __('Link to attachment page rather than to file.', 'document-gallery') }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<MediaUpload
					title={ __('Select Document Gallery Files (Optional)', 'document-gallery') }
					gallery={true}
					allowedTypes={allowedTypes}
					multiple={true}
					value={getAttrValue('ids') ? getAttrValue('ids').split(',').map(id => parseInt(id)) : []}
					render={({open}) => (
						<>
							<Button
								variant='secondary'
								onClick={open}
							>
								{ getAttrValue('ids') 
									? __('Edit selected documents', 'document-gallery')
									: __('Select specific documents (optional)', 'document-gallery') }
							</Button>
							{ getAttrValue('ids') && (
								<Button
									variant='link'
									isDestructive
									onClick={() => setAttributes({ ids: '' })}
									style={{ marginLeft: '8px' }}
								>
									{ __('Clear selection', 'document-gallery') }
								</Button>
							)}
						</>
					)}
					onSelect={ ( media ) => {
						if ( media && media.length > 0 ) {
							const selectedIds = media.map( m => m.id ).join(',');
							setAttributes( { ids: selectedIds } );
						} else {
							setAttributes( { ids: '' } );
						}
					}}
				/>
				<ServerSideRender
					block="document-gallery/document-gallery-block"
					attributes={attributes}
				/>
			</div>
		</>
	);
}
