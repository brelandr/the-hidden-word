( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'thw/lesson', {
		title: __( 'Bible Lesson', 'the-hidden-word' ),
		icon: 'book-alt',
		category: 'widgets',
		description: __( 'Display a deep-dive Bible lesson with tabs for context, narrative, and memorization.', 'the-hidden-word' ),
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
				useBlockProps( { className: 'thw-lesson-block-editor' } ),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Lesson Settings', 'the-hidden-word' ) },
						el( SelectControl, {
							label: __( 'Lesson', 'the-hidden-word' ),
							value: attrs.lessonId,
							options: [
								{ label: __( 'Current schedule (auto)', 'the-hidden-word' ), value: 'auto' },
							],
							onChange: function ( val ) { setAttributes( { lessonId: val } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show memorization widget', 'the-hidden-word' ),
							checked: attrs.showMemorization,
							onChange: function ( val ) { setAttributes( { showMemorization: val } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Show discussion tab', 'the-hidden-word' ),
							checked: attrs.showDiscussion,
							onChange: function ( val ) { setAttributes( { showDiscussion: val } ); },
						} )
					)
				),
				el( 'div', { className: 'thw-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-book-alt' } ),
					el( 'p', {}, __( 'The Hidden Word — Bible Lesson', 'the-hidden-word' ) ),
					el( 'p', { className: 'description' },
						attrs.lessonId === 'auto'
							? __( 'Displays the current scheduled lesson.', 'the-hidden-word' )
							: __( 'Displays lesson ID: ', 'the-hidden-word' ) + attrs.lessonId
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
