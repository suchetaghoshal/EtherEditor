/**
 * Library to manage etherpad instances
 *
 * @author Mark Holmquist
 * @license GPLv2+
 */

( function( $, mw ) {

	/**
	 * Creates a new manager object
	 *
	 * @param {HTMLDivElement} (can be jQuery-wrapped)
	 */
	function etherManager( mainDiv ) {
		var _this = this;
		_this.$mainDiv = $( mainDiv );
		_this.padTpl = $( '.ethereditor-pad' ).detach();
		_this.pageTpl = $( '.ethereditor-page' ).detach();
		_this.pageList = $( '#ethereditor-page-list' );
		_this.pads = mw.config.get( 'EtherEditorPads' );
		_this.initializeList();
	}

	etherManager.prototype = {
		initializeList: function () {
			var _this = this;
			for ( var px in _this.pads ) {
				var pad = _this.pads[px];
				var $pr = $( '[data-page-title="' + pad.page_title + '"]' );
				if ( $pr.length == 0 ) {
					$pr = _this.addPage( pad.page_title );
				}
				var $prt = $( 'table', $pr );
				_this.addPad( pad, $prt );
			}
		},
		addPage: function ( pagename ) {
			var _this = this;
			var $pr = _this.pageTpl.clone();
			$pr.attr( 'data-page-title', pagename );
			$( '.ethereditor-page-title', $pr ).html( pagename );
			$( '.ethereditor-create-pad', $pr ).click( function () {
				_this.createPad( pagename );
			} );
			_this.pageList.append( $pr );
			return $pr;
		},
		addPad: function ( pad, $prt ) {
			var _this = this;
			var $tc = _this.padTpl.clone();
			$tc.attr( 'data-pad-id', pad.pad_id );
			var $joinPadLink = $( '<a></a>' );
			$joinPadLink.html( pad.ep_pad_id.substr( 19 ) );
			var joinPadUrl = mw.util.wikiGetlink( pad.page_title );
			joinPadUrl += '?action=edit&collaborate=true&padId=' + pad.pad_id;
			$joinPadLink.attr( 'href', joinPadUrl );
			$( '.pad-name', $tc ).html( $joinPadLink );
			$( '.pad-baserev', $tc ).html( pad.base_revision );
			$( '.pad-users', $tc ).html( pad.users_connected );

			var $padCtrls = $( '.pad-ctrls', $tc );

			var $forkbtn = $( '<button></button>' );
			$forkbtn.html( mw.msg( 'ethereditor-fork-button' ) );
			$forkbtn.click( function () {
				var $this = $( this );
				var padid = $this.closest( 'tr' ).attr( 'data-pad-id' );
				var pagetitle = $this.closest( '.ethereditor-page' ).attr( 'data-page-title' );
				_this.forkPad( padid, pagetitle );
				return false;
			} );
			$padCtrls.append( $forkbtn );

			if ( pad.admin_user == mw.user.name() ) {
				var $delbtn = $( '<button></button>' );
				$delbtn.html( mw.msg( 'ethereditor-delete-button' ) );
				$delbtn.click( function () {
					var padid = $( this ).closest( 'tr' ).attr( 'data-pad-id' );
					var pagetitle = $( this ).closest( '.ethereditor-page' ).attr( 'data-page-title' );
					_this.deletePad( padid, pagetitle );
				} );
				$padCtrls.append( $delbtn );
			}

			$prt.append( $tc );
		},
		/**
		 * Deletes a pad's contents. (requires admin, of course)
		 */
		deletePad: function ( dbId, pageTitle ) {
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: {
					format: 'json', action: 'DeleteEtherPad',
					padId: dbId
				},
				success: function( data ) {
					$( '[data-pad-id=' + dbId + ']' ).remove();
					return 0;
				},
				dataType: 'json'
			} );
		},
		forkPad: function ( padid, pagetitle ) {
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'ForkEtherPad', padId: padid },
				success: function( data ) {
					var fep = data.ForkEtherPad;
					var newpad = {
						ep_pad_id: fep.padId,
						pad_id: fep.dbId,
						admin_user: mw.user.name()
					};
					_this.addPad( newpad, $( '[data-page-title="' + pagetitle + '"] table' ) );
				},
				dataType: 'json'
			} );
		},
		createPad: function ( pagetitle ) {
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'CreateNewPadFromPage', pageTitle: pagetitle },
				success: function( data ) {
					var fep = data.CreateNewPadFromPage;
					var newpad = {
						ep_pad_id: fep.padId,
						pad_id: fep.dbId,
						admin_user: mw.user.name()
					};
					_this.addPad( newpad, $( '[data-page-title="' + pagetitle + '"] table' ) );
				},
				dataType: 'json'
			} );
		}
	};

	$.fn.etherManager = function() {
		var $elements = this;
		$.each( $elements, function( i, div ) {
			var manager = new etherManager( div );
		} );
	};

	$( document ).ready( function () {
		$( '#ethereditor' ).etherManager();
	} );

} )( jQuery, mediaWiki );
