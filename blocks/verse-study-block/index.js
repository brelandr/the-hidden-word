( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/verse-study', {
		title: __( 'Verse Study Card', 'hidden-word-bible-lessons' ),
		icon: 'book-alt',
		category: 'widgets',
		description: __(
			'Guided study for one verse: plain words, context, key words, cross-references, and a live-it question.',
			'hidden-word-bible-lessons'
		),
		attributes: {
			bookId: { type: 'string', default: '43' },
			chapter: { type: 'string', default: '3' },
			verse: { type: 'string', default: '16' },
			translation: { type: 'string', default: '' },
			title: { type: 'string', default: 'Verse Study Card' },
			picker: { type: 'boolean', default: true },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'hwbl-verse-study-block-editor' } ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Verse Study settings', 'hidden-word-bible-lessons' ) },
						el( TextControl, {
							label: __( 'Heading', 'hidden-word-bible-lessons' ),
							value: attrs.title,
							onChange: function ( val ) {
								setAttributes( { title: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Book ID', 'hidden-word-bible-lessons' ),
							help: __( '1 = Genesis … 43 = John', 'hidden-word-bible-lessons' ),
							value: attrs.bookId,
							onChange: function ( val ) {
								setAttributes( { bookId: val } );
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
						el( TextControl, {
							label: __( 'Translation slug (optional)', 'hidden-word-bible-lessons' ),
							value: attrs.translation,
							onChange: function ( val ) {
								setAttributes( { translation: val } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show book/chapter/verse picker', 'hidden-word-bible-lessons' ),
							checked: !! attrs.picker,
							onChange: function ( val ) {
								setAttributes( { picker: val } );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'hwbl-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-book-alt' } ),
					el(
						'p',
						{},
						__( 'Hidden Word — Verse Study Card', 'hidden-word-bible-lessons' )
					),
					el(
						'p',
						{ className: 'description' },
						( attrs.picker
							? __( 'Interactive picker', 'hidden-word-bible-lessons' )
							: __( 'Fixed verse', 'hidden-word-bible-lessons' ) ) +
							': book ' +
							attrs.bookId +
							' / ' +
							attrs.chapter +
							':' +
							attrs.verse
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
