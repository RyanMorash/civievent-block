( function ( blocks, blockEditor, components, element, i18n, serverSideRender, apiFetch ) {
	'use strict';

	var el = element.createElement;
	var useEffect = element.useEffect;
	var useState = element.useState;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var Notice = components.Notice;
	var ServerSideRender = serverSideRender.default || serverSideRender;
	var __ = i18n.__;

	function Edit( props ) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var typeState = useState( [
			{ label: __( 'All event types', 'civievent-block' ), value: 0 }
		] );
		var eventTypes = typeState[ 0 ];
		var setEventTypes = typeState[ 1 ];
		var errorState = useState( '' );
		var eventTypesError = errorState[ 0 ];
		var setEventTypesError = errorState[ 1 ];

		useEffect( function () {
			var active = true;

			apiFetch( { path: '/civievent-block/v1/event-types' } )
				.then( function ( options ) {
					if ( active ) {
						setEventTypes( [
							{ label: __( 'All event types', 'civievent-block' ), value: 0 }
						].concat( options ) );
					}
				} )
				.catch( function ( error ) {
					if ( active ) {
						setEventTypesError( error.message || __( 'Event types could not be loaded.', 'civievent-block' ) );
					}
				} );

			return function () {
				active = false;
			};
		}, [] );

		var contentControls = [
			el( SelectControl, {
				label: __( 'Display', 'civievent-block' ),
				value: attributes.displayMode,
				options: [
					{ label: __( 'Event list', 'civievent-block' ), value: 'list' },
					{ label: __( 'Single event', 'civievent-block' ), value: 'single' }
				],
				onChange: function ( value ) {
					setAttributes( { displayMode: value } );
				}
			} ),
			el( TextControl, {
				label: __( 'Heading', 'civievent-block' ),
				help: __( 'Leave empty to hide the block heading.', 'civievent-block' ),
				value: attributes.heading,
				onChange: function ( value ) {
					setAttributes( { heading: value } );
				}
			} ),
			el( SelectControl, {
				label: __( 'Heading level', 'civievent-block' ),
				value: attributes.headingLevel,
				options: [ 2, 3, 4, 5, 6 ].map( function ( level ) {
					return { label: 'H' + level, value: level };
				} ),
				onChange: function ( value ) {
					setAttributes( { headingLevel: parseInt( value, 10 ) } );
				}
			} )
		];

		if ( 'list' === attributes.displayMode ) {
			contentControls.push( el( RangeControl, {
				label: __( 'Number of events', 'civievent-block' ),
				value: attributes.limit,
				min: 1,
				max: 20,
				onChange: function ( value ) {
					setAttributes( { limit: value } );
				}
			} ) );
		} else {
			contentControls.push( el( RangeControl, {
				label: __( 'Events to skip', 'civievent-block' ),
				help: __( 'Use 1 to display the second upcoming event.', 'civievent-block' ),
				value: attributes.offset,
				min: 0,
				max: 50,
				onChange: function ( value ) {
					setAttributes( { offset: value } );
				}
			} ) );
		}

		contentControls.push(
			eventTypesError ? el( TextControl, {
				label: __( 'Event type ID', 'civievent-block' ),
				type: 'number',
				min: 0,
				value: attributes.eventTypeId,
				onChange: function ( value ) {
					setAttributes( { eventTypeId: parseInt( value, 10 ) || 0 } );
				}
			} ) : el( SelectControl, {
				label: __( 'Event type', 'civievent-block' ),
				value: attributes.eventTypeId,
				options: eventTypes,
				onChange: function ( value ) {
					setAttributes( { eventTypeId: parseInt( value, 10 ) || 0 } );
				}
			} ),
			eventTypesError ? el( Notice, {
				status: 'warning',
				isDismissible: false
			}, eventTypesError ) : null,
			el( ToggleControl, {
				label: __( 'Show summaries', 'civievent-block' ),
				checked: attributes.showSummary,
				onChange: function ( value ) {
					setAttributes( { showSummary: value } );
				}
			} ),
			el( ToggleControl, {
				label: __( 'Show event times', 'civievent-block' ),
				checked: attributes.showTime,
				onChange: function ( value ) {
					setAttributes( { showTime: value } );
				}
			} ),
			el( TextControl, {
				label: __( 'No events message', 'civievent-block' ),
				value: attributes.emptyMessage,
				onChange: function ( value ) {
					setAttributes( { emptyMessage: value } );
				}
			} )
		);

		var eventsPanel = el.apply( null, [ PanelBody, {
				title: __( 'Events', 'civievent-block' ),
				initialOpen: true
			} ].concat( contentControls ) );

		var inspector = el( InspectorControls, {},
			eventsPanel,
			el( PanelBody, {
				title: __( 'Location', 'civievent-block' ),
				initialOpen: false
			},
				el( ToggleControl, {
					label: __( 'Show city', 'civievent-block' ),
					checked: attributes.showCity,
					onChange: function ( value ) {
						setAttributes( { showCity: value } );
					}
				} ),
				el( SelectControl, {
					label: __( 'State or province', 'civievent-block' ),
					value: attributes.stateDisplay,
					options: [
						{ label: __( 'Hidden', 'civievent-block' ), value: 'none' },
						{ label: __( 'Abbreviation', 'civievent-block' ), value: 'abbreviate' },
						{ label: __( 'Full name', 'civievent-block' ), value: 'full' }
					],
					onChange: function ( value ) {
						setAttributes( { stateDisplay: value } );
					}
				} ),
				el( ToggleControl, {
					label: __( 'Show country', 'civievent-block' ),
					checked: attributes.showCountry,
					onChange: function ( value ) {
						setAttributes( { showCountry: value } );
					}
				} ),
				el( TextControl, {
					label: __( 'Location separator', 'civievent-block' ),
					value: attributes.locationSeparator,
					onChange: function ( value ) {
						setAttributes( { locationSeparator: value } );
					}
				} )
			),
			el( PanelBody, {
				title: __( 'Links', 'civievent-block' ),
				initialOpen: false
			},
				el( ToggleControl, {
					label: __( 'Show registration links', 'civievent-block' ),
					checked: attributes.showRegistration,
					onChange: function ( value ) {
						setAttributes( { showRegistration: value } );
					}
				} ),
				el( ToggleControl, {
					label: __( 'Show “View all events”', 'civievent-block' ),
					checked: attributes.showViewAll,
					onChange: function ( value ) {
						setAttributes( { showViewAll: value } );
					}
				} )
			)
		);

		return el( element.Fragment, {},
			inspector,
			el( 'div', useBlockProps( { className: 'civievent-block-editor' } ),
				el( ServerSideRender, {
					block: 'civievent-block/events',
					attributes: attributes
				} )
			)
		);
	}

	blocks.registerBlockType( 'civievent-block/events', {
		edit: Edit,
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender,
	window.wp.apiFetch
);
