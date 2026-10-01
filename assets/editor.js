/**
 * registers our custom blocks and their sidebar UI for the WordPress editor
 * no build step needed, plain ES5
 *
 * hero + Schnellzugriff do NOT keep their content in block attributes anymore.
 * everything lives in the option "csd_frontpage" (see inc/frontpage.php) and is
 * edited through the core "site" entity. that means:
 *  - changes are saved with the normal "Speichern" button, together with the template
 *  - nothing is lost after a theme update, a template reset or a re-upload
 *  - images are stored as a plain list, so WordPress cant mangle them anymore
 */
( function ( blocks, element, ssr, i18n, blockEditor, components, coreData ) {
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
	var Notice          = components.Notice;
	var Spinner         = components.Spinner;

	var CONFIG   = window.csdFrontpage || { option: 'csd_frontpage', defaults: { hero: {}, heading: 'Schnellzugriff', tiles: [] } };
	var OPTION   = CONFIG.option;
	var DEFAULTS = CONFIG.defaults;
	var TILES    = 8;

	/* helpers */

	/* like el() but spreads an array of children as individual arguments */
	function elMany( type, props, childrenArray ) {
		return el.apply( null, [ type, props ].concat( childrenArray || [] ) );
	}

	function sep() {
		return el( 'hr', { style: { margin: '10px 0', border: 'none', borderTop: '1px solid #e0e0e0' } } );
	}

	/* always returns the full shape, even if the option is still empty */
	function normalize( raw ) {
		var d    = JSON.parse( JSON.stringify( raw || {} ) );
		var hero = d.hero || {};
		[ 'kicker', 'title', 'lead', 'btn1Label', 'btn1Url', 'btn2Label', 'btn2Url', 'bgUrl', 'flagDate' ].forEach( function ( k ) {
			if ( typeof hero[ k ] !== 'string' ) { hero[ k ] = ''; }
		} );
		hero.bgId = parseInt( hero.bgId, 10 ) || 0;

		var ql    = d.quicklinks || {};
		var tiles = Array.isArray( ql.tiles ) ? ql.tiles : [];
		var list  = [];
		for ( var i = 0; i < TILES; i++ ) {
			var t = tiles[ i ] || {};
			list.push( {
				label:  typeof t.label === 'string' ? t.label : '',
				url:    typeof t.url === 'string' ? t.url : '',
				imgId:  parseInt( t.imgId, 10 ) || 0,
				imgUrl: typeof t.imgUrl === 'string' ? t.imgUrl : ''
			} );
		}
		return {
			hero: hero,
			quicklinks: { heading: typeof ql.heading === 'string' ? ql.heading : '', tiles: list }
		};
	}

	/* reads the option from the site entity and returns [ data, update ].
	   update( fn ) gets a fresh copy, fn changes it, then it goes back into the
	   entity. the editor marks it as unsaved and "Speichern" writes it */
	function useFrontpage() {
		var prop    = coreData.useEntityProp( 'root', 'site', OPTION );
		var raw     = prop[ 0 ];
		var setRaw  = prop[ 1 ];
		var data    = raw === undefined ? null : normalize( raw );
		function update( fn ) {
			var next = normalize( raw );
			fn( next );
			setRaw( next );
		}
		return [ data, update ];
	}

	function loadingOrError() {
		return el( 'div', { style: { padding: '0 16px 16px' } }, el( Spinner ) );
	}

	function saveHint() {
		return el( Notice, { status: 'info', isDismissible: false },
			__( 'Änderungen erscheinen sofort in der Vorschau und werden mit „Speichern" oben rechts übernommen. Sie bleiben auch bei Theme-Updates erhalten.', 'csd-darmstadt' )
		);
	}

	/* image picker with a small thumbnail so you can see what is selected */
	function imagePicker( id, url, onPick, onRemove, labelPick ) {
		return el( 'div', {},
			url ? el( 'img', { src: url, alt: '', style: { display: 'block', width: '100%', maxHeight: 120, objectFit: 'cover', marginBottom: 8, borderRadius: 2 } } ) : null,
			el( 'div', { style: { display: 'flex', alignItems: 'center', gap: 8 } },
				el( MediaUploadCheck, {},
					el( MediaUpload, {
						allowedTypes: [ 'image' ],
						value: id || 0,
						onSelect: function ( m ) { onPick( m ); },
						render: function ( o ) {
							return el( Button, { variant: 'secondary', onClick: o.open },
								url ? __( 'Bild ersetzen', 'csd-darmstadt' ) : labelPick );
						}
					} )
				),
				url ? el( Button, { variant: 'link', isDestructive: true, onClick: onRemove },
					__( 'Bild entfernen', 'csd-darmstadt' ) ) : null
			)
		);
	}

	/* hero block editor UI */
	function heroEdit() {
		var fp     = useFrontpage();
		var data   = fp[ 0 ];
		var update = fp[ 1 ];
		var def    = DEFAULTS.hero || {};

		if ( ! data ) {
			return el( Fragment, {},
				el( InspectorControls, {}, loadingOrError() ),
				el( ssr, { block: 'csd/hero', attributes: {}, httpMethod: 'POST' } )
			);
		}
		var h = data.hero;

		function field( Control, key, label, extra ) {
			return el( Control, Object.assign( {
				label: label,
				value: h[ key ],
				placeholder: def[ key ] || '',
				onChange: function ( v ) { update( function ( d ) { d.hero[ key ] = v; } ); }
			}, extra || {} ) );
		}

		var textPanel = el( PanelBody,
			{ title: __( 'Texte', 'csd-darmstadt' ), initialOpen: true },
			saveHint(),
			field( TextControl, 'kicker', __( 'Kicker (Kleintext oben)', 'csd-darmstadt' ) ),
			field( TextControl, 'title', __( 'Überschrift', 'csd-darmstadt' ) ),
			field( TextareaControl, 'lead', __( 'Lead-Text', 'csd-darmstadt' ), { rows: 3 } ),
			el( 'p', { style: { color: '#757575', fontSize: 12 } }, __( 'Leere Felder zeigen den grauen Standardtext.', 'csd-darmstadt' ) )
		);

		var btnPanel = el( PanelBody,
			{ title: __( 'Buttons', 'csd-darmstadt' ), initialOpen: false },
			el( 'p', { style: { fontWeight: 600, margin: '0 0 4px' } }, __( 'Button 1 (ausgefüllt)', 'csd-darmstadt' ) ),
			field( TextControl, 'btn1Label', __( 'Beschriftung', 'csd-darmstadt' ) ),
			field( TextControl, 'btn1Url', __( 'URL', 'csd-darmstadt' ) ),
			sep(),
			el( 'p', { style: { fontWeight: 600, margin: '0 0 4px' } }, __( 'Button 2 (Rahmen)', 'csd-darmstadt' ) ),
			field( TextControl, 'btn2Label', __( 'Beschriftung', 'csd-darmstadt' ) ),
			field( TextControl, 'btn2Url', __( 'URL', 'csd-darmstadt' ) )
		);

		var flagPanel = el( PanelBody,
			{ title: __( 'Grafik', 'csd-darmstadt' ), initialOpen: false },
			el( TextControl, {
				label: __( 'Datum in der Grafik', 'csd-darmstadt' ),
				help: __( 'Format TT.MM.JJJJ, z. B. 21.08.2027. Wird automatisch zweizeilig in Cera Pro gesetzt. Leer lassen für die Grafik ohne Datum.', 'csd-darmstadt' ),
				value: h.flagDate,
				placeholder: '21.08.2027',
				onChange: function ( v ) { update( function ( d ) { d.hero.flagDate = v.replace( /[^0-9.]/g, '' ); } ); }
			} )
		);

		var bgPanel = el( PanelBody,
			{ title: __( 'Hintergrundbild', 'csd-darmstadt' ), initialOpen: false },
			imagePicker( h.bgId, h.bgUrl,
				function ( m ) { update( function ( d ) { d.hero.bgId = m.id; d.hero.bgUrl = m.url; } ); },
				function () { update( function ( d ) { d.hero.bgId = 0; d.hero.bgUrl = ''; } ); },
				__( 'Hintergrundbild wählen', 'csd-darmstadt' )
			)
		);

		return el( Fragment, {},
			elMany( InspectorControls, {}, [ textPanel, flagPanel, btnPanel, bgPanel ] ),
			el( ssr, { block: 'csd/hero', attributes: { preview: data }, httpMethod: 'POST' } )
		);
	}

	/* quick access tiles editor UI */
	function quicklinksEdit() {
		var fp     = useFrontpage();
		var data   = fp[ 0 ];
		var update = fp[ 1 ];

		if ( ! data ) {
			return el( Fragment, {},
				el( InspectorControls, {}, loadingOrError() ),
				el( ssr, { block: 'csd/quicklinks', attributes: {}, httpMethod: 'POST' } )
			);
		}
		var ql = data.quicklinks;

		function setTile( i, patch ) {
			update( function ( d ) { Object.assign( d.quicklinks.tiles[ i ], patch ); } );
		}

		var headingPanel = el( PanelBody,
			{ title: __( 'Überschrift', 'csd-darmstadt' ), initialOpen: false },
			el( TextControl, {
				label: __( 'Überschrift', 'csd-darmstadt' ),
				value: ql.heading,
				placeholder: DEFAULTS.heading || 'Schnellzugriff',
				onChange: function ( v ) { update( function ( d ) { d.quicklinks.heading = v; } ); }
			} )
		);

		/* one collapsible panel per tile */
		var tilePanels = ql.tiles.map( function ( tile, i ) {
			var def = DEFAULTS.tiles[ i ] || { label: '', url: '' };
			return el( PanelBody, {
				key: 'tile-' + i,
				title: ( i + 1 ) + '. ' + ( tile.label || def.label || __( 'Kachel', 'csd-darmstadt' ) ) + ( tile.imgUrl ? ' (Bild)' : '' ),
				initialOpen: false
			},
				el( TextControl, {
					label: __( 'Titel', 'csd-darmstadt' ),
					value: tile.label,
					placeholder: def.label,
					onChange: function ( v ) { setTile( i, { label: v } ); }
				} ),
				el( TextControl, {
					label: __( 'URL', 'csd-darmstadt' ),
					value: tile.url,
					placeholder: def.url,
					onChange: function ( v ) { setTile( i, { url: v } ); }
				} ),
				sep(),
				el( 'p', { style: { fontWeight: 600, fontSize: '11px', margin: '0 0 6px' } },
					__( 'Hintergrundbild', 'csd-darmstadt' )
				),
				imagePicker( tile.imgId, tile.imgUrl,
					function ( m ) { setTile( i, { imgId: m.id, imgUrl: m.url } ); },
					function () { setTile( i, { imgId: 0, imgUrl: '' } ); },
					__( 'Bild wählen', 'csd-darmstadt' )
				)
			);
		} );

		/* spread all panels as indiviual arguments, passing an array directly dosnt work in older React */
		var allPanels = [ el( 'div', { key: 'hint', style: { padding: '0 16px' } }, saveHint() ), headingPanel ].concat( tilePanels );

		return el( Fragment, {},
			elMany( InspectorControls, {}, allPanels ),
			el( ssr, { block: 'csd/quicklinks', attributes: { preview: data }, httpMethod: 'POST' } )
		);
	}

	/* block registrations. the old content attributes are gone on purpose:
	   whatever is still in an old template gets dropped on the next save */
	blocks.registerBlockType( 'csd/hero', {
		apiVersion: 3,
		title: __( 'CSD: Hero', 'csd-darmstadt' ),
		category: 'widgets', icon: 'cover-image',
		supports: { html: false, reusable: false },
		attributes: { preview: { type: 'object' } },
		edit: heroEdit,
		save: function () { return null; }
	} );

	blocks.registerBlockType( 'csd/quicklinks', {
		apiVersion: 3,
		title: __( 'CSD: Schnellzugriff', 'csd-darmstadt' ),
		category: 'widgets', icon: 'grid-view',
		supports: { html: false, reusable: false },
		attributes: { preview: { type: 'object' } },
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
	registerPlain( 'csd/archive',     __( 'CSD: Beitragsübersicht', 'csd-darmstadt' ), 'grid-view' );

} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender,
     window.wp.i18n, window.wp.blockEditor, window.wp.components, window.wp.coreData );
