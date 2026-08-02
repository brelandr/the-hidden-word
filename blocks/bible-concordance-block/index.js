( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/bible-concordance', {
		title: __( 'Bible Concordance', 'hidden-word-bible-lessons' ),
		icon: 'search',
		category: 'widgets',
		description: __(
			'Look up a word or phrase across Scripture using Local Bibles or Biblia.com.',
			'hidden-word-bible-lessons'
		),
		attributes: {
			translation: { type: 'string', default: '' },
			q: { type: 'string', default: '' },
			title: { type: 'string', default: 'Bible Concordance' },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'hwbl-bible-concordance-block-editor' } ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Concordance settings', 'hidden-word-bible-lessons' ) },
						el( TextControl, {
							label: __( 'Heading', 'hidden-word-bible-lessons' ),
							value: attrs.title,
							onChange: function ( val ) {
								setAttributes( { title: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Default translation slug', 'hidden-word-bible-lessons' ),
							help: __( 'e.g. kjv, web, niv (NIV needs Biblia API)', 'hidden-word-bible-lessons' ),
							value: attrs.translation,
							onChange: function ( val ) {
								setAttributes( { translation: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Initial search (optional)', 'hidden-word-bible-lessons' ),
							value: attrs.q,
							onChange: function ( val ) {
								setAttributes( { q: val } );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'hwbl-block-placeholder' },
					el( 'span', { className: 'dashicons dashicons-search' } ),
					el( 'p', {}, __( 'Hidden Word — Bible Concordance', 'hidden-word-bible-lessons' ) ),
					el(
						'p',
						{ className: 'description' },
						( attrs.translation
							? attrs.translation
							: __( 'Default translation', 'hidden-word-bible-lessons' ) ) +
							( attrs.q ? ': “' + attrs.q + '”' : '' )
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
