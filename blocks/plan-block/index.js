( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'hwbl/plan', {
		title: __( 'Reading Plan', 'hidden-word-bible-lessons' ),
		icon: 'calendar-alt',
		category: 'widgets',
		description: __(
			'Show a reading plan or a browsable list of plans by topic.',
			'hidden-word-bible-lessons'
		),
		attributes: {
			planId: { type: 'string', default: '' },
			topic: { type: 'string', default: '' },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'hwbl-plan-block-editor' } ),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Reading Plan settings', 'hidden-word-bible-lessons' ) },
						el( TextControl, {
							label: __( 'Plan ID (optional)', 'hidden-word-bible-lessons' ),
							help: __(
								'Leave empty to show the plan list. Set an ID to show one plan.',
								'hidden-word-bible-lessons'
							),
							value: attrs.planId,
							onChange: function ( val ) {
								setAttributes( { planId: val } );
							},
						} ),
						el( TextControl, {
							label: __( 'Topic filter (list mode)', 'hidden-word-bible-lessons' ),
							help: __(
								'anxiety, grief, marriage, new-believer, gospel, foundations…',
								'hidden-word-bible-lessons'
							),
							value: attrs.topic,
							onChange: function ( val ) {
								setAttributes( { topic: val } );
							},
						} )
					)
				),
				el(
					'p',
					{ className: 'hwbl-plan-block-preview' },
					attrs.planId
						? __( 'Reading Plan #', 'hidden-word-bible-lessons' ) + attrs.planId
						: __( 'Reading Plan list', 'hidden-word-bible-lessons' ) +
								( attrs.topic ? ' (' + attrs.topic + ')' : '' )
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
