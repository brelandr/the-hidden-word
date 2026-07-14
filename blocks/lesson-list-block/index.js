( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'thw/lesson-list', {
		title: __( 'Bible Lesson Catalog', 'the-hidden-word' ),
		icon: 'list-view',
		category: 'widgets',
		description: __( 'Browse all bundled Bible lessons by book or testament.', 'the-hidden-word' ),
		attributes: {
			group: { type: 'string', default: 'book' },
			book: { type: 'string', default: '' },
			testament: { type: 'string', default: '' },
			perPage: { type: 'string', default: '50' },
			show: { type: 'string', default: 'both' },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'thw-lesson-list-block-editor' } ),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Catalog Settings', 'the-hidden-word' ) },
						el( SelectControl, {
							label: __( 'Group by', 'the-hidden-word' ),
							value: attrs.group,
							options: [
								{ label: __( 'Book', 'the-hidden-word' ), value: 'book' },
								{ label: __( 'Testament', 'the-hidden-word' ), value: 'testament' },
								{ label: __( 'Flat list (paginated)', 'the-hidden-word' ), value: 'all' },
							],
							onChange: function ( val ) { setAttributes( { group: val } ); },
						} ),
						el( TextControl, {
							label: __( 'Book ID filter (optional)', 'the-hidden-word' ),
							value: attrs.book,
							onChange: function ( val ) { setAttributes( { book: val } ); },
						} ),
						el( SelectControl, {
							label: __( 'Testament filter', 'the-hidden-word' ),
							value: attrs.testament,
							options: [
								{ label: __( 'All', 'the-hidden-word' ), value: '' },
								{ label: __( 'Old Testament', 'the-hidden-word' ), value: 'ot' },
								{ label: __( 'New Testament', 'the-hidden-word' ), value: 'nt' },
							],
							onChange: function ( val ) { setAttributes( { testament: val } ); },
						} ),
						el( SelectControl, {
							label: __( 'Show', 'the-hidden-word' ),
							value: attrs.show,
							options: [
								{ label: __( 'Reference and title', 'the-hidden-word' ), value: 'both' },
								{ label: __( 'Reference only', 'the-hidden-word' ), value: 'reference' },
								{ label: __( 'Title only', 'the-hidden-word' ), value: 'title' },
							],
							onChange: function ( val ) { setAttributes( { show: val } ); },
						} )
					)
				),
				el( 'div', { className: 'thw-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-list-view' } ),
					el( 'p', {}, __( 'The Hidden Word — Lesson Catalog', 'the-hidden-word' ) )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
