( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/lesson-list', {
		title: __( 'Bible Lesson Catalog', 'hidden-word-bible-lessons' ),
		icon: 'list-view',
		category: 'widgets',
		description: __( 'Browse all bundled Bible lessons by book or testament.', 'hidden-word-bible-lessons' ),
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
				useBlockProps( { className: 'hwbl-lesson-list-block-editor' } ),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Catalog Settings', 'hidden-word-bible-lessons' ) },
						el( SelectControl, {
							label: __( 'Group by', 'hidden-word-bible-lessons' ),
							value: attrs.group,
							options: [
								{ label: __( 'Book', 'hidden-word-bible-lessons' ), value: 'book' },
								{ label: __( 'Testament', 'hidden-word-bible-lessons' ), value: 'testament' },
								{ label: __( 'Flat list (paginated)', 'hidden-word-bible-lessons' ), value: 'all' },
							],
							onChange: function ( val ) { setAttributes( { group: val } ); },
						} ),
						el( TextControl, {
							label: __( 'Book ID filter (optional)', 'hidden-word-bible-lessons' ),
							value: attrs.book,
							onChange: function ( val ) { setAttributes( { book: val } ); },
						} ),
						el( SelectControl, {
							label: __( 'Testament filter', 'hidden-word-bible-lessons' ),
							value: attrs.testament,
							options: [
								{ label: __( 'All', 'hidden-word-bible-lessons' ), value: '' },
								{ label: __( 'Old Testament', 'hidden-word-bible-lessons' ), value: 'ot' },
								{ label: __( 'New Testament', 'hidden-word-bible-lessons' ), value: 'nt' },
							],
							onChange: function ( val ) { setAttributes( { testament: val } ); },
						} ),
						el( SelectControl, {
							label: __( 'Show', 'hidden-word-bible-lessons' ),
							value: attrs.show,
							options: [
								{ label: __( 'Reference and title', 'hidden-word-bible-lessons' ), value: 'both' },
								{ label: __( 'Reference only', 'hidden-word-bible-lessons' ), value: 'reference' },
								{ label: __( 'Title only', 'hidden-word-bible-lessons' ), value: 'title' },
							],
							onChange: function ( val ) { setAttributes( { show: val } ); },
						} )
					)
				),
				el( 'div', { className: 'hwbl-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-list-view' } ),
					el( 'p', {}, __( 'Hidden Word Bible Lessons — Lesson Catalog', 'hidden-word-bible-lessons' ) )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
