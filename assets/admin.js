/* AI Discovery Manager - Script amministrazione */
( function ( $ ) {
	'use strict';

	var $form = $( '#adm-form' );
	var debounceTimer = null;

	/**
	 * Attiva un tab specifico.
	 *
	 * @param {string} tab Chiave del tab (llms, skills, index).
	 */
	function activateTab( tab ) {
		$( '.adm-tab, .nav-tab' ).removeClass( 'nav-tab-active' );
		$( '.nav-tab[data-tab="' + tab + '"]' ).addClass( 'nav-tab-active' );
		$( '.adm-panel' ).removeClass( 'is-active' );
		$( '#tab-' + tab ).addClass( 'is-active' );
		$( '#adm_active_tab' ).val( tab );
		if ( window.history && window.history.replaceState ) {
			var url = new URL( window.location.href );
			url.searchParams.set( 'tab', tab );
			window.history.replaceState( {}, '', url );
		}
	}

	/**
	 * Determina il tab iniziale (da querystring o default).
	 *
	 * @return {string} Chiave tab.
	 */
	function initialTab() {
		var params = new URLSearchParams( window.location.search );
		var tab = params.get( 'tab' );
		if ( tab && $( '#tab-' + tab ).length ) {
			return tab;
		}
		return 'llms';
	}

	/**
	 * Richiede l'anteprima aggiornata via AJAX.
	 */
	function refreshPreview() {
		$( '.adm-preview-status' ).text( ADM.i18n.loading );

		var data = $form.serializeArray();
		data.push( { name: 'action', value: 'adm_preview' } );
		data.push( { name: 'nonce', value: ADM.nonce } );

		$.post( ADM.ajaxUrl, data )
			.done( function ( response ) {
				if ( response && response.success ) {
					$( '.adm-preview-box[data-preview="llms"]' ).text( response.data.llms );
					$( '.adm-preview-box[data-preview="skills"]' ).text( response.data.skills );
					$( '.adm-preview-box[data-preview="index"]' ).text( response.data.index );
					$( '.adm-preview-status' ).text( '' );
				} else {
					$( '.adm-preview-status' ).text( ADM.i18n.error );
				}
			} )
			.fail( function () {
				$( '.adm-preview-status' ).text( ADM.i18n.error );
			} );
	}

	/**
	 * Anteprima con debounce sull'input.
	 */
	function debouncedPreview() {
		window.clearTimeout( debounceTimer );
		debounceTimer = window.setTimeout( refreshPreview, 500 );
	}

	$( function () {
		// Navigazione tab.
		$( '.nav-tab' ).on( 'click', function ( e ) {
			e.preventDefault();
			activateTab( $( this ).data( 'tab' ) );
		} );

		// Aggiornamento anteprima in tempo reale.
		$form.on( 'input change', '.adm-input', debouncedPreview );

		// Tab iniziale + prima anteprima.
		activateTab( initialTab() );
		refreshPreview();
	} );
} )( jQuery );
