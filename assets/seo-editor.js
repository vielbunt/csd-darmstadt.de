/**
 * Schalter "Nicht in Suchmaschinen anzeigen" in der Seitenleiste von Seiten
 * und Beiträgen (gleiche Datei in csd-darmstadt.de und vielbunt.org).
 * Speichert in das Post-Meta _vb_noindex, ausgewertet in inc/seo.php.
 */
( function ( plugins, editor, editPost, data, coreData, components, element ) {
	var el    = element.createElement;
	var Panel = ( editor && editor.PluginDocumentSettingPanel ) || ( editPost && editPost.PluginDocumentSettingPanel );
	if ( ! Panel ) {
		return;
	}

	function NoIndexPanel() {
		var postType = data.useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var prop = coreData.useEntityProp( 'postType', postType || 'post', 'meta' );
		var meta = prop[ 0 ];
		var setMeta = prop[ 1 ];

		if ( [ 'post', 'page' ].indexOf( postType ) < 0 || ! meta ) {
			return null;
		}
		return el( Panel, { name: 'vb-noindex', title: 'Suchmaschinen' },
			el( components.ToggleControl, {
				label: 'Nicht in Suchmaschinen anzeigen',
				help: meta._vb_noindex
					? 'Google & Co. nehmen diese Seite aus den Suchergebnissen. Sie bleibt über Links erreichbar.'
					: 'Für Altlasten wie alte Formulare oder abgelaufene Aktionen, ohne sie zu löschen.',
				checked: !! meta._vb_noindex,
				onChange: function ( value ) {
					setMeta( Object.assign( {}, meta, { _vb_noindex: value } ) );
				}
			} )
		);
	}

	plugins.registerPlugin( 'vb-noindex', { render: NoIndexPanel } );
} )( window.wp.plugins, window.wp.editor, window.wp.editPost, window.wp.data, window.wp.coreData, window.wp.components, window.wp.element );
