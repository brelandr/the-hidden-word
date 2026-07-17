( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/lesson', {
		title: __( 'Bible Lesson', 'hidden-word-bible-lessons' ),
		icon: 'book-alt',
		category: 'widgets',
		description: __( 'Display a deep-dive Bible lesson with tabs for context, narrative, and memorization.', 'hidden-word-bible-lessons' ),
		attributes: {
			lessonId: { type: 'string', default: 'auto' },
			showMemorization: { type: 'boolean', default: true },
			showDiscussion: { type: 'boolean', default: true },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'hwbl-lesson-block-editor' } ),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Lesson Settings', 'hidden-word-bible-lessons' ) },
						el( SelectControl, {
							label: __( 'Lesson', 'hidden-word-bible-lessons' ),
							value: attrs.lessonId,
							options: [
								{ label: __( 'Current schedule (auto)', 'hidden-word-bible-lessons' ), value: 'auto' },
							],
							onChange: function ( val ) { setAttributes( { lessonId: val } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show memorization widget', 'hidden-word-bible-lessons' ),
							checked: attrs.showMemorization,
							onChange: function ( val ) { setAttributes( { showMemorization: val } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show discussion tab', 'hidden-word-bible-lessons' ),
							checked: attrs.showDiscussion,
							onChange: function ( val ) { setAttributes( { showDiscussion: val } ); },
						} )
					)
				),
				el( 'div', { className: 'hwbl-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-book-alt' } ),
					el( 'p', {}, __( 'Hidden Word Bible Lessons — Bible Lesson', 'hidden-word-bible-lessons' ) ),
					el( 'p', { className: 'description' },
						attrs.lessonId === 'auto'
							? __( 'Displays the current scheduled lesson.', 'hidden-word-bible-lessons' )
							: __( 'Displays lesson ID: ', 'hidden-word-bible-lessons' ) + attrs.lessonId
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
