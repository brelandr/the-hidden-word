( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/bible-map', {
		title: __( 'Bible Map', 'hidden-word-bible-lessons' ),
		icon: 'location-alt',
		category: 'widgets',
		description: __(
			'Map biblical places for a verse or chapter using OpenBible geocoding data.',
			'hidden-word-bible-lessons'
		),
		attributes: {
			book: { type: 'string', default: '43' },
			chapter: { type: 'string', default: '3' },
			verse: { type: 'string', default: '16' },
			height: { type: 'string', default: '320' },
			scope: { type: 'string', default: 'verse' },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'hwbl-bible-map-block-editor' } ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Bible Map settings', 'hidden-word-bible-lessons' ) },
						el( TextControl, {
							label: __( 'Book ID', 'hidden-word-bible-lessons' ),
							help: __( '1 = Genesis … 43 = John', 'hidden-word-bible-lessons' ),
							value: attrs.book,
							onChange: function ( val ) {
								setAttributes( { book: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Chapter', 'hidden-word-bible-lessons' ),
							value: attrs.chapter,
							onChange: function ( val ) {
								setAttributes( { chapter: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Verse', 'hidden-word-bible-lessons' ),
							value: attrs.verse,
							onChange: function ( val ) {
								setAttributes( { verse: val } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Scope', 'hidden-word-bible-lessons' ),
							value: attrs.scope,
							options: [
								{
									label: __( 'This verse', 'hidden-word-bible-lessons' ),
									value: 'verse',
								},
								{
									label: __( 'This chapter', 'hidden-word-bible-lessons' ),
									value: 'chapter',
								},
							],
							onChange: function ( val ) {
								setAttributes( { scope: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Map height (px)', 'hidden-word-bible-lessons' ),
							value: attrs.height,
							onChange: function ( val ) {
								setAttributes( { height: val } );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'hwbl-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-location-alt' } ),
					el(
						'p',
						{},
						__( 'Hidden Word — Bible Map', 'hidden-word-bible-lessons' )
					),
					el(
						'p',
						{ className: 'description' },
						( attrs.scope === 'chapter'
							? __( 'Chapter map', 'hidden-word-bible-lessons' )
							: __( 'Verse map', 'hidden-word-bible-lessons' ) ) +
							': book ' +
							attrs.book +
							' ch ' +
							attrs.chapter +
							( attrs.scope === 'chapter' ? '' : ':' + attrs.verse )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
