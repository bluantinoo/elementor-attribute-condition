/**
 * Lets archive templates preview a specific term.
 *
 * Elementor Pro reads the term from the preview_id setting for any
 * "taxonomy/<name>" preview type, but its editor only shows the term field
 * for Loop templates. This wraps the function that decides it, and shows the
 * field as a term search when the preview type is a taxonomy archive.
 * If Elementor Pro renames the function, the wrapper does nothing.
 */
( function () {
	'use strict';

	var themeBuilder = window.elementorPro && elementorPro.modules && elementorPro.modules.themeBuilder;
	if ( ! themeBuilder || 'function' !== typeof themeBuilder.updatePreviewIdOptions ) {
		return;
	}

	var original = themeBuilder.updatePreviewIdOptions;

	themeBuilder.updatePreviewIdOptions = function ( render ) {
		original.apply( this, arguments );

		try {
			showTermField( render );
		} catch ( e ) {
			// Elementor Pro changed its internals: keep its own behaviour.
		}
	};

	function showTermField( render ) {
		var previewType = ( elementor.settings.page.model.get( 'preview_type' ) || '' ).split( '/' );
		if ( 'taxonomy' !== previewType[ 0 ] || ! previewType[ 1 ] ) {
			return;
		}

		var pageView = elementor.getPanelView().getCurrentPageView();
		var controlModel = pageView && pageView.collection && pageView.collection.findWhere( { name: 'preview_id' } );
		if ( ! controlModel ) {
			return;
		}

		controlModel.set( {
			autocomplete: {
				object: 'tax',
				query: { taxonomy: previewType[ 1 ] },
			},
		} );

		if ( true === render ) {
			var controlView = pageView.children.findByModel( controlModel );
			if ( controlView ) {
				controlView.render();
				controlView.$el.show();
			}
		}
	}
} )();
