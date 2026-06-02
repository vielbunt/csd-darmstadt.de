/**
 * registers our custom blocks and their sidebar UI for the WordPress editor
 * no build step needed, plain ES5
 */
( function ( blocks, element, ssr, i18n, blockEditor, components ) {
	'use strict';

	var el         = element.createElement;
	var Fragment   = element.Fragment;
	var __         = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var MediaUpload       = blockEditor.MediaUpload;
	var MediaUploadCheck  = blockEditor.MediaUploadCheck;
	var PanelBody       = components.PanelBody;
	var Button          = components.Button;
	var TextControl     = components.TextControl;
	var TextareaControl = components.TextareaControl;

	/* helpers */

	/* like el() but spreads an array of children as individual arguments */
	function elMany( type, props, childrenArray ) {
		return el.apply( null, [ type, props ].concat( childrenArray || [] ) );
	}

	function sep() {
		return el( 'hr', { style: { margin: '10px 0', border: 'none', borderTop: '1px solid #e0e0e0' } } );
	}

	/* hero block editor UI */
	function heroEdit( props ) {
		var a = props.attributes;

		function set( key ) {
			return function ( v ) {
				var u = {};
				u[ key ] = v;
				props.setAttributes( u );
			};
		}

		var textPanel = el( PanelBody,
			{ title: __( 'Texte', 'csd-darmstadt' ), initialOpen: true },
			el( TextControl, {
				label: __( 'Kicker (Kleintext oben)', 'csd-darmstadt' ),
				value: a.kicker || '',
				onChange: set( 'kicker' )
			} ),
			el( TextControl, {
				label: __( 'Überschrift', 'csd-darmstadt' ),
				value: a.title || '',
				onChange: set( 'title' )
			} ),
			el( TextareaControl, {
				label: __( 'Lead-Text', 'csd-darmstadt' ),
				value: a.lead || '',
				rows: 3,
				onChange: set( 'lead' )
			} )
		);

		var btnPanel = el( PanelBody,
			{ title: __( 'Buttons', 'csd-darmstadt' ), initialOpen: false },
			el( 'p', { style: { fontWeight: 600, margin: '0 0 4px' } }, __( 'Button 1 (ausgefüllt)', 'csd-darmstadt' ) ),
			el( TextControl, { label: __( 'Beschriftung', 'csd-darmstadt' ), value: a.btn1Label || '', onChange: set( 'btn1Label' ) } ),
			el( TextControl, { label: __( 'URL', 'csd-darmstadt' ),          value: a.btn1Url   || '', onChange: set( 'btn1Url'   ) } ),
			sep(),
			el( 'p', { style: { fontWeight: 600, margin: '0 0 4px' } }, __( 'Button 2 (Rahmen)', 'csd-darmstadt' ) ),
			el( TextControl, { label: __( 'Beschriftung', 'csd-darmstadt' ), value: a.btn2Label || '', onChange: set( 'btn2Label' ) } ),
			el( TextControl, { label: __( 'URL', 'csd-darmstadt' ),          value: a.btn2Url   || '', onChange: set( 'btn2Url'   ) } )
		);

		var bgPanel = el( PanelBody,
			{ title: __( 'Hintergrundbild', 'csd-darmstadt' ), initialOpen: false },
			el( MediaUploadCheck, {},
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: a.bgId,
					onSelect: function ( m ) { props.setAttributes( { bgUrl: m.url, bgId: m.id } ); },
					render: function ( o ) {
						return el( Button, { variant: 'secondary', onClick: o.open },
							a.bgUrl ? __( 'Bild ersetzen', 'csd-darmstadt' ) : __( 'Hintergrundbild wählen', 'csd-darmstadt' )
						);
					}
				} )
			),
			a.bgUrl ? el( Button, {
				variant: 'link', isDestructive: true,
				style: { marginTop: '10px', display: 'block' },
				onClick: function () { props.setAttributes( { bgUrl: '', bgId: 0 } ); }
			}, __( 'Bild entfernen', 'csd-darmstadt' ) ) : null
		);

		return el( Fragment, {},
			elMany( InspectorControls, {}, [ textPanel, btnPanel, bgPanel ] ),
			el( ssr, { block: 'csd/hero', attributes: props.attributes } )
		);
	}

	/* quick access tiles editor UI */

	/* must match csd_default_tiles() in functions.php */
	var DEFAULT_TILES = [
		{ label: 'After Show Party', url: 'https://www.csd-darmstadt.de/after-show-party-centralstation/' },
		{ label: 'Motto 2025',       url: 'https://www.csd-darmstadt.de/motto-2025/' },
		{ label: 'Pride Week 2025',  url: 'https://www.csd-darmstadt.de/csd-pride-week-2025/' },
		{ label: 'Kontakt',          url: 'https://www.csd-darmstadt.de/kontakt/' },
		{ label: 'Fotos CSD 2025',   url: 'https://www.csd-darmstadt.de/2025/08/fotos-vom-csd-darmstadt-2025-in-arbeit/' },
		{ label: 'Videos',           url: 'https://www.csd-darmstadt.de/videos/' },
		{ label: 'Anreise',          url: 'https://www.csd-darmstadt.de/anreise/' },
		{ label: 'Mitmachen!',       url: 'https://www.csd-darmstadt.de/mitmachen/' }
	];

	function quicklinksEdit( props ) {
		var savedTiles = props.attributes.tiles;
		var imgs       = props.attributes.images || {};

		/* use saved attributes if all 8 tiles are saved, otherwise fall back to defaults */
		var tiles = ( savedTiles && savedTiles.length === DEFAULT_TILES.length )
			? savedTiles
			: DEFAULT_TILES;

		function updateTile( i, key, value ) {
			var next = DEFAULT_TILES.map( function ( def, idx ) {
				var cur = ( savedTiles && savedTiles[ idx ] ) ? savedTiles[ idx ] : def;
				return Object.assign( {}, cur );
			} );
			next[ i ][ key ] = value;
			props.setAttributes( { tiles: next } );
		}

		/* heading panel */
		var headingPanel = el( PanelBody,
			{ title: __( 'Überschrift', 'csd-darmstadt' ), initialOpen: false },
			el( TextControl, {
				label: __( 'Überschrift', 'csd-darmstadt' ),
				value: props.attributes.heading || '',
				onChange: function ( v ) { props.setAttributes( { heading: v } ); }
			} )
		);

		/* one collapsible panel per tile */
		var tilePanels = tiles.map( function ( tile, i ) {
			var imgEntry = imgs[ i ] || null;
			return el( PanelBody, {
				key: 'tile-' + i,
				title: ( i + 1 ) + '. ' + ( tile.label || '—' ),
				initialOpen: false
			},
				el( TextControl, {
					label: __( 'Titel', 'csd-darmstadt' ),
					value: tile.label || '',
					onChange: function ( v ) { updateTile( i, 'label', v ); }
				} ),
				el( TextControl, {
					label: __( 'URL', 'csd-darmstadt' ),
					value: tile.url || '',
					onChange: function ( v ) { updateTile( i, 'url', v ); }
				} ),
				sep(),
				el( 'p', { style: { fontWeight: 600, fontSize: '11px', margin: '0 0 6px' } },
					__( 'Hintergrundbild', 'csd-darmstadt' )
				),
				el( MediaUploadCheck, {},
					el( MediaUpload, {
						allowedTypes: [ 'image' ],
						value: imgEntry ? imgEntry.id : 0,
						onSelect: function ( m ) {
							var n = Object.assign( {}, imgs );
							n[ i ] = { url: m.url, id: m.id };
							props.setAttributes( { images: n } );
						},
						render: function ( o ) {
							return el( Button, { variant: 'secondary', onClick: o.open },
								imgEntry ? __( 'Bild ersetzen', 'csd-darmstadt' ) : __( 'Bild wählen', 'csd-darmstadt' )
							);
						}
					} )
				),
				imgEntry ? el( Button, {
					variant: 'link', isDestructive: true,
					style: { marginLeft: '8px' },
					onClick: function () {
						var n = Object.assign( {}, imgs );
						delete n[ i ];
						props.setAttributes( { images: n } );
					}
				}, __( 'Bild entfernen', 'csd-darmstadt' ) ) : null
			);
		} );

		/* spread all panels as indiviual arguments, passing an array directly dosnt work in older React */
		var allPanels = [ headingPanel ].concat( tilePanels );

		return el( Fragment, {},
			elMany( InspectorControls, {}, allPanels ),
			el( ssr, { block: 'csd/quicklinks', attributes: props.attributes } )
		);
	}

	/* block registrations */
	blocks.registerBlockType( 'csd/hero', {
		apiVersion: 3,
		title: __( 'CSD: Hero', 'csd-darmstadt' ),
		category: 'widgets', icon: 'cover-image',
		supports: { html: false, reusable: false },
		attributes: {
			bgUrl:     { type: 'string', default: '' },
			bgId:      { type: 'number', default: 0  },
			kicker:    { type: 'string', default: '' },
			title:     { type: 'string', default: '' },
			lead:      { type: 'string', default: '' },
			btn1Label: { type: 'string', default: '' },
			btn1Url:   { type: 'string', default: '' },
			btn2Label: { type: 'string', default: '' },
			btn2Url:   { type: 'string', default: '' }
		},
		edit: heroEdit,
		save: function () { return null; }
	} );

	blocks.registerBlockType( 'csd/quicklinks', {
		apiVersion: 3,
		title: __( 'CSD: Schnellzugriff', 'csd-darmstadt' ),
		category: 'widgets', icon: 'grid-view',
		supports: { html: false, reusable: false },
		attributes: {
			heading: { type: 'string', default: 'Schnellzugriff' },
			tiles:   { type: 'array',  default: [] },
			images:  { type: 'object', default: {} }
		},
		edit: quicklinksEdit,
		save: function () { return null; }
	} );

	function registerPlain( name, title, icon, attrs ) {
		blocks.registerBlockType( name, {
			apiVersion: 3, title: title, category: 'widgets', icon: icon,
			supports: { html: false, reusable: false }, attributes: attrs || {},
			edit: function ( props ) { return el( ssr, { block: name, attributes: props.attributes } ); },
			save: function () { return null; }
		} );
	}

	registerPlain( 'csd/events',      __( 'CSD: Aktuelles',          'csd-darmstadt' ), 'calendar-alt' );
	registerPlain( 'csd/feed',        __( 'CSD: Alle Ankündigungen', 'csd-darmstadt' ), 'list-view'    );
	registerPlain( 'csd/logo',        __( 'CSD: Logo',               'csd-darmstadt' ), 'flag',         { variant: { type: 'string' } } );
	registerPlain( 'csd/footerlinks', __( 'CSD: Footer-Links',       'csd-darmstadt' ), 'editor-ul'    );

} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender,
     window.wp.i18n, window.wp.blockEditor, window.wp.components );
