/**
 * Library to create a remote editor (particularly an instance of the modified etherpad editor pad-mediawiki)
 *
 * dependencies: mediawiki.uri
 *
 * @author Mark Holmquist
 * @license GPLv2+
 *
 */

( function( $, mw ) {

	/** Utility function for having non-stupid Dates in the GMT timezone.
	 * Javascript can die in a fire for not having proper timezone handling.
	 */
	function dateUTC( date ) {
		if ( !( date instanceof Date ) ) {
			date = new Date( date );
		}
		var timeoff = date.getTimezoneOffset() * 60 * 1000;
		date = new Date( date.getTime() + timeoff );
		return date;
	}

	var gInitListener = null;

	/**
	 * Listen for the init event
	 */
	var initListener = function ( remEd, event ) {
		if ( event.data == 'ethereditor-init' && event.origin == remEd.padUrl && !remEd.iframeready ) {
			remEd.iframeready = true;
			remEd.initializeFormattingControls();
			if ( remEd.iframetimeout !== null ) {
				clearTimeout( remEd.iframetimeout );
			}
			window.removeEventListener( 'message', gInitListener );
			remEd.updateUserCount();
		}
	};

	/**
	 * Creates a new remote editor object
	 *
	 * @param {HTMLTextAreaElement} (can be jQuery-wrapped)
	 */
	function remoteEditor( textarea ) {
		if ( typeof textarea === 'undefined' ) {
			throw new Error( "need a textarea as argument to remoteEditor" );
		}

		var _this = this;
		_this.uri = new mw.Uri( window.location.href );
		_this.pads = mw.config.get( 'wgEtherEditorOtherPads' );
		_this.$textarea = $( textarea );
		_this.$ctrls = null;
		_this.$pctrls = null;
		_this.$userlist = null;
		_this.$toolbar = null;
		_this.$sharelink = null;
		_this.users = {};
		_this.userName = mw.config.get( 'wgUserName' );
		_this.hostname = _this.getHostName();
		_this.baseUrl = mw.config.get( 'wgEtherEditorPadUrl' );
		_this.padId = mw.config.get( 'wgEtherEditorPadName' );
		_this.padUrl = 'http://' + _this.hostname;
		_this.dbId = mw.config.get( 'wgEtherEditorDbId' );
		_this.sessionId = mw.config.get( 'wgEtherEditorSessionId' );
		_this.iframe = null;
		_this.iframetimeout = null;
		_this.iframeready = false;
		_this.realUserListener = _this.userListener.bind( this );
		gInitListener = initListener.bind( null, this );
		if ( !_this.padId || !_this.sessionId ) {
			return false; // there was an error, clearly, so let's quit
		}
		_this.initializeControls( function () {
			if ( _this.uri.query.collaborate ) {
				if ( _this.uri.query.padId ) {
					var thepad = false;
					for ( var px in _this.pads ) {
						if ( _this.pads[px].pad_id == _this.uri.query.padId ) {
							thepad = _this.pads[px];
							break;
						}
					}
					if ( thepad ) {
						_this.dbId = thepad.pad_id;
						_this.padId = thepad.ep_pad_id;
					}
				}
				_this.authenticateUser( function () {
					$( '#ethereditor-collab-switch' ).prop( 'checked', true );
					_this.enableEther();
				} );
			}
		} );
	};

	remoteEditor.prototype = {
		/**
		 * Get the hostname for the Etherpad Lite instance
		 */
		getHostName: function () {
			var hostname = mw.config.get( 'wgEtherEditorApiHost' );
			if ( !hostname ) {
				hostname = 'localhost';
			}
			var port = mw.config.get( 'wgEtherEditorApiPort' );
			if ( !port ) {
				port = '9001';
			}
			if ( port != '80' ) {
				hostname = hostname + ':' + port;
			}
			return hostname;
		},
		/**
		 * Send a message to the iframe below.
		 */
		sendMessage: function ( msg ) {
			var _this = this;
			if ( _this.iframeready ) {
				_this.iframe.contentWindow.postMessage( msg, _this.padUrl );
			}
		},
		/**
		 * Signal to the iframe that we're ready, and wait for its response
		 */
		signalReady: function () {
			var _this = this;
			window.addEventListener( 'message', gInitListener, false );
			var recurseUntilReady = function () {
				if ( _this.iframeready === false ) {
					_this.iframe.contentWindow.postMessage( 'ethereditor-init', _this.padUrl );
					_this.iframetimeout = setTimeout( function () {
						recurseUntilReady();
					}, 200 );
				}
			};
			recurseUntilReady();
		},
		/**
		 * Listen for user updates
		 */
		userListener: function ( event ) {
			var msg = event.data;
			if ( msg && msg.type && msg.type == 'userinfo' ) {
				for ( var ux in msg.users ) {
					if ( msg.users[ux].name == '' ) {
						msg.users[ux].name = mw.user.name();
					}
					this.userJoinOrUpdate( msg.users[ux] );
				}
			}       
		},
		/**
		 * Enables the collaborative editor, and starts up a bunch of processes
		 */
		enableEther: function () {
			//alert("I am in enableEther");
			var _this = this;
			_this.hasSubmitted = false;
			
			$( 'input[type="submit"]' ).click( function ( e ) {
				if ( ! _this.hasSubmitted ) {
					e.preventDefault();
					e.stopPropagation();
					_this.hasSubmitted = true;
					var __this = this;
					var $form = _this.$textarea.closest( 'form' );
					$.ajax( {
						url: mw.util.wikiScript( 'api' ),
						method: 'GET',
						data: { format: 'json', action: 'GetEtherPadText', padId: _this.dbId },
						success: function( data ) {
							_this.$textarea.html( data.GetEtherPadText.text );
							var origact = $form.attr( 'action' );
							var newact = origact + '&collaborate=true';
							if ( _this.padId ) {
								newact += '&padId=' + _this.dbId;
							}
							$form.attr( 'action', newact );
							$( __this ).click();
							return 0;
						},
						dataType: 'json'
					} );
				    return 0;
				} 
           } ); 
			
			_this.initializePad();
		},
		/**
		 * Authenticate the current user to the current pad.
		 *
		 * @param function cb the callback for after the authentication is done
		 */
		authenticateUser: function ( cb ) {
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'EtherPadAuth', padId: _this.dbId },
				success: function( data ) {
					_this.sessionId = data.EtherPadAuth.sessionId;
					cb();
					return 0;
				},
				dataType: 'json'
			} );
		},
		/**
		 * Add the list of contributors to the edit summary field
		 */
		populateSummary: function () {
			var _this = this;
			var contribs = _this.contribs;
			var $smry = $( '#wpSummary' );
			var contribstr = '';
			var cx = 0;
			while ( 1 ) {
				var contrib = contribs[cx];
				contribstr += contrib.username;
				if ( ++cx < contribs.length ) {
					contribstr += ',';
				} else {
					break;
				}
			}
			var oldsmry = $smry.val();
			var newsmry = mw.msg( 'ethereditor-summary-message', contribstr );
			var sumregex = new RegExp( mw.msg( 'ethereditor-summary-message' ).replace( '$1', '[^\\.]*(,[^\\.]*)*' ), '' );
			if ( oldsmry.match( sumregex ) !== null ) {
				oldsmry = oldsmry.replace( sumregex, newsmry );
			} else {
				oldsmry += newsmry;
			}
			$smry.val( oldsmry );
		},
		/**
		 * Adds some controls to the form specific to the extension.
		*/
		initializeControls: function ( cb ) {
			var _this = this;
			var $toolbar = $( '#wikiEditor-ui-toolbar .tabs' );

			// Check whether WikiEditor needs to be loaded before we can continue.
			// We use this way because checking for the DOM element won't work if
			// the WikiEditor interface isn't done loading yet (happens (frequently))

			var isUsingWikiEditor = mw.config.exists( 'wgWikiEditorEnabledModules' );
			if ( isUsingWikiEditor && $toolbar.length === 0 ) {
				// If we are using WikiEditor, and the toolbar hasn't loaded, use
				// mw.loader.using to jerry-rig a callback for when WE *is* loaded.

				mw.loader.using( 'jquery.wikiEditor.toolbar', function () {
					_this.initializeControls( cb );
				} );

				return;
			}

			cb = typeof cb == 'function' ? cb : function () {};
			_this.$ctrls = $( '<div></div>' );
			_this.$ctrls.attr( 'id', 'ethereditor-ctrls' );
			_this.$ctrls.css( 'float', 'right' );

			if ( !isUsingWikiEditor ) {
				$toolbar = $( '#toolbar' );
				$toolbar.append( _this.$ctrls );
			} else {
				$toolbar.after( _this.$ctrls );
			}
			_this.initializeCollabControls();
			cb();
		},
		/**
		 * Add the hook for updating users.
		 */
		initializeUserList: function () {
			var _this = this;
			_this.$userlist = $( '#ethereditor-userlist' );
			_this.$userlist = $( '<ul></ul>' )
				.addClass( 'user' )
				.addClass( 'dropdown' )
				.addClass( 'hidden' );
			var $ucontain = $( '<span></span>' );
			$ucontain.attr( 'id', 'ethereditor-userlist-contain' );
			var $uimg = $( '<img />' );
			var imgurl = mw.config.get( 'wgExtensionAssetsPath' )
				+ '/EtherEditor/modules/images/userlist.png';
			$uimg.attr( 'src', imgurl );
			$uimg.attr( 'alt', mw.msg( 'ethereditor-user-list' ) );
			$uimg.addClass( 'listicon' );
			$ucontain.append( $uimg );
			_this.$userlist.attr( 'id', 'ethereditor-userlist' );
			$ucontain.append( _this.$userlist );
			_this.$pctrls.append( $ucontain );
			_this.$usercount = $( '<span></span>' )
				.html( 0 )
				.attr( 'id', 'ethereditor-usercount' );
			$ucontain.append( _this.$usercount );
			_this.$usercount.add( $( '.listicon', $ucontain ) ).click( function () {
				if ( _this.$userlist.children().length === 0 ) {
					return 0;
				}
				if ( _this.$userlist.hasClass( 'hidden' ) && _this.$padlist !== null ) {
					_this.$padlist.addClass( 'hidden' );
				}
				_this.$userlist.toggleClass( 'hidden' );
			} );
			if ( _this.isAdmin ) {
				_this.$userlist.removeClass( 'notadmin' );
			} else {
				_this.$userlist.addClass( 'notadmin' );
			}
			window.addEventListener( 'message', this.realUserListener, false );
		},
		/**
		 * Query the API for the current user count, don't mess around with
		 * cross-frame messages.
		 */
		updateUserCount: function () {
			var _this = this,
				oldDbId = _this.dbId;

			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'GetCurrentUsers', padId: _this.dbId },
				success: function( data ) {
					if ( oldDbId === _this.dbId ) {
						for ( var ix = 0; ix < data.GetCurrentUsers.users.length; ix++ ) {
							_this.userJoinOrUpdate( data.GetCurrentUsers.users[ix], true );
						}
						// This only fires when there's a change, so let's update the
						// contribs list.
						_this.initializeContribs();
					}
					return 0;
				},
				dataType: 'json'
			} );
		},
		/**
		 * Given user info, either create or update the relevant entry.
		 */
		userJoinOrUpdate: function ( user, isOnlyUpdate ) {
			var _this = this;
			if ( !user || !user.name || user.name == '' ) {
				setTimeout( function () {
					_this.sendMessage( 'updateusers' );
				}, 400 );
				return;
			}
			var doesexist = _this.users[user.name];
			_this.users[user.name] = true;
			var len = function ( obj ) {
				var c = 0;
				for ( var ix in obj ) {
					c++;
				}
				return c;
			};
			_this.$usercount.html( len( _this.users ) );
			var $user = $( '[data-username="' + user.id + '"]' );
			if ( !doesexist ) {
				var $userli = $( '<li></li>' );
				$user = $( '<div></div>' )
					.addClass( 'session-info' );
				$user.attr( 'data-username', user.name );
				_this.$userlist.append( $userli.append( $user ) );
				$user.append( '<span class="ethereditor-usercolor">&nbsp;</span>');
				$user.append( $( '<span></span>')
					.addClass( 'user-details' )
					.text( user.name ) );

				if ( user.name != mw.user.name() ) {
					var $userctrls = $( '<div class="sesison-button"></div>' );
					var $kick = $( '<button></button>' )
						.addClass( 'blue' )
						.addClass( 'button' );
					$kick.html( mw.msg( 'ethereditor-kick-button' ) );
					$kick.click( ( function ( username ) {
						return function () {
							_this.kickUser( username );
							return false;
						};
					} )( user.name ) );
					$userctrls.append( $kick );
					$user.append( $userctrls );
				}
			}
			if ( !isOnlyUpdate ) {
				// Since a user just joined (or changed), might as well check for new contributors.
				// Yay, no more constantly polling the server!
				_this.initializeContribs();
			}
			$( '.ethereditor-username', $user ).html( user.name );
			$( '.ethereditor-usercolor', $user ).css( 'background-color', user.color || user.colorId || null );
		},
		/**
		 * Add events to each formatting button, see that they work properly.
		 */
		initializeFormattingControls: function () {
			var _this = this;

			var $bolds = $( 'a[rel=bold], #mw-editbutton-bold' );
			$bolds.click( function () {
				_this.sendMessage( 'ethereditor-bold' );
			} );

			var $italics = $( 'a[rel=italic], #mw-editbutton-italic' );
			$italics.click( function () {
				_this.sendMessage( 'ethereditor-italic' );
			} );

			var $ilinks = $( 'a[rel=ilink], #mw-editbutton-link' );
			$ilinks.click( function () {
				_this.sendMessage( 'ethereditor-ilink' );
			} );

			var $files = $( 'a[rel=file]' );
			$files.click( function () {
				_this.sendMessage( 'ethereditor-file' );
			} );

			var $elinks = $( 'a[rel=xlink], #mw-editbutton-extlink' );
			$elinks.click( function () {
				_this.sendMessage( 'ethereditor-elink' );
			} );

			var $refs = $( 'a[rel=reference]' );
			$refs.click( function () {
				_this.sendMessage( 'ethereditor-ref' );
			} );

			var $nowikis = $( 'a[rel=nowiki], #mw-editbutton-nowiki' );
			$nowikis.click( function () {
				_this.sendMessage( 'ethereditor-nowiki' );
			} );

			var $signatures = $( 'a[rel=signature], #mw-editbutton-signature' );
			$signatures.click( function () {
				_this.sendMessage( 'ethereditor-signature' );
			} );

			var $uls = $( 'a[rel=ulist]' );
			$uls.click( function () {
				_this.sendMessage( 'ethereditor-ul' );
			} );

			var $ols = $( 'a[rel=olist]' );
			$ols.click( function () {
				_this.sendMessage( 'ethereditor-ol' );
			} );

			var $tables = $( 'a[rel=table]' );
			$tables.click( function () {
				_this.sendMessage( 'ethereditor-table' );
			} );

			var $inds = $( 'a[rel=indent]' );
			$inds.click( function () {
				_this.sendMessage( 'ethereditor-indent' );
			} );

			var $brs = $( 'a[rel=newline]' );
			$brs.click( function () {
				_this.sendMessage( 'ethereditor-br' );
			} );

			var $bigs = $( 'a[rel=big]' );
			$bigs.click( function () {
				_this.sendMessage( 'ethereditor-big' );
			} );

			var $smalls = $( 'a[rel=small]' );
			$smalls.click( function () {
				_this.sendMessage( 'ethereditor-small' );
			} );

			var $supers = $( 'a[rel=superscript]' );
			$supers.click( function () {
				_this.sendMessage( 'ethereditor-super' );
			} );

			var $subs = $( 'a[rel=subscript]' );
			$subs.click( function () {
				_this.sendMessage( 'ethereditor-sub' );
			} );

			var $redirs = $( 'a[rel=redirect]' );
			$redirs.click( function () {
				_this.sendMessage( 'ethereditor-redir' );
			} );

			var $specs = $( '.section-characters .page span' );
			$specs.click( function () {
				var $this = $( this );
				_this.sendMessage( { type: 'specialchar', char: $this.attr( 'rel' ) } );
			} );

			for ( var i = 2; i < 6; i++ ) {
				var $headings = $( 'a[rel=heading-' + i + ']' );
				$headings.click( ( 
					function( thisi ) {
						return function () {
							_this.sendMessage( { heading: thisi } );
						}
					})( i ) );
			}

			var $hrs = $( '#mw-editbutton-hr' );
			$hrs.click( function () {
				_this.sendMessage( 'ethereditor-hr' );
			} );

			var $headlines = $( '#mw-editbutton-headline' );
			$headlines.click( function () {
				_this.sendMessage( { heading: 2 } );
			} );
		},
		kickUser: function ( username ) {
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: {
					format: 'json', action: 'KickFromPad',
					padId: _this.dbId, user: username
				},
				success: function( data ) {
					$( '[data-username="' + username + '"]' ).remove();
					_this.$usercount.html( $( '.ethereditor-username' ).length );
					return 0;
				},
				dataType: 'json'
			} );
			return false;
		},
		/**
		 * Initialize the various collaboration controls. This includes the pad
		 * list and the "collaborate" switch.
		 */
		initializeCollabControls: function () {
			//alert("I am in initializeCollabControls");
			var _this = this;
			var $turnOnCollab = $( '<input id="ethereditor-collab-switch" type="checkbox" />' );
			$turnOnCollab.click( function (event) {
				
				var $this = $( this );
				if ( $this.is( ':checked' ) ) {
					_this.enableEther();
				} else {
					_this.disableEther();
				}
			} );
			//alert("I am after preventDefault");
			var $collabLabel = $( '<label></label>' );
			$collabLabel.html( mw.msg( 'ethereditor-collaborate-button' ) );
			$collabLabel.append( $turnOnCollab );
			_this.$ctrls.append( $collabLabel );

			_this.$sharelink = $( '<input />' )
				.attr( 'type', 'text' )
				.attr( 'readonly', 'readonly' );
			_this.$ctrls.append( _this.$sharelink );

			_this.$pctrls = $( '<span></span>' );
			_this.$pctrls.attr( 'id', 'ethereditor-pad-ctrls' );
			_this.$ctrls.append( _this.$pctrls );

			var eventHandle = function () {
				this.selectionStart = 0;
				this.selectionEnd = $( this ).val().length;
				return false;
			};

			_this.$sharelink.focus( eventHandle );
			_this.$sharelink.click( eventHandle );
			_this.$sharelink.on( 'keydown', eventHandle );
			_this.initializePadList();
			_this.initializeUserList();
		},
		/**
		 * Initializes an automatic process of constantly checking for, and
		 * adding, new contributors.
		 */
		initializeContribs: function (oldthis) {
			var _this = this;
			var oldDbId = _this.dbId;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'GetContribs', padId: _this.dbId },
				success: function( data ) {
					if ( oldDbId == _this.dbId ) {
						_this.contribs = data.GetContribs.contribs;
						_this.populateSummary();
					} else {
						_this.initializeContribs();
					}
					return 0;
				},
				dataType: 'json'
			} );
		},
		/**
		 * Adds a list of other pads to the page, so you can have multiple.
		*/
		initializePadList: function () {
			//alert("I am in initializePadList dada");
			var _this = this;
			_this.$padlist = $( '#ethereditor-padlist' );
			_this.$padlist = $( '<ul></ul>' )
				.addClass( 'hidden' )
				.addClass( 'session' )
				.addClass( 'dropdown' );
			var $pcontain = $( '<span></span>' );
			$pcontain.attr( 'id', 'ethereditor-padlist-contain' );
			var $pimg = $( '<img />' );
			var imgurl = mw.config.get( 'wgExtensionAssetsPath' )
				+ '/EtherEditor/modules/images/sessionlist.png';
			$pimg.attr( 'src', imgurl );
			$pimg.attr( 'alt', mw.msg( 'ethereditor-pad-list' ) );
			$pimg.addClass( 'listicon' );
			$pcontain.append( $pimg );
			_this.$padlist.attr( 'id', 'ethereditor-padlist' );
			$pcontain.append( _this.$padlist );
			_this.$pctrls.append( $pcontain );
			_this.$padcount = $( '<span></span>' );
			_this.$padcount.attr( 'id', 'ethereditor-padcount' );
			_this.$padcount.html( _this.pads.length );
			$pcontain.append( _this.$padcount );
			_this.$padcount.add( $( '.listicon', $pcontain ) ).click( function () {
				if ( _this.$padlist.hasClass( 'hidden' ) && _this.$userlist !== null ) {
					_this.$userlist.addClass( 'hidden' );
				}
				_this.$padlist.toggleClass( 'hidden' );
			} );
			if ( _this.isAdmin ) {
				//alert("I am adimn");
				_this.$padlist.removeClass( 'notadmin' );
			} else {
				//alert("I am not adimn");
				_this.$padlist.addClass( 'notadmin' );
			}
			//alert("Going to updatePadList :P");
			_this.updatePadList( false );
		},
		/**
		 * Update the list of pads.
		 * @param updateRemote Should we fetch the data from the API?
		 */
		updatePadList: function ( updateRemote ) {
			var count_iframenull = 0;
			var _this = this;
			_this.$padlist.empty();
			_this.$padlist.append( $( '<li></li>' )
				.addClass( 'empty-pad' )
				.hide() );
			var finishUpdate = function () {
				//alert("I am in finishUpdate");
				var isOdd = 1;
				for ( var px in _this.pads ) {
					var pad = _this.pads[px];
					var $padli = $( '<li></li>' );
					var $pad = $( '<div></div>' )
						.addClass( 'session-info' );
					$pad.attr( 'data-padid', pad.pad_id );
					_this.$padlist
						.find( '.empty-pad:first' )
						.before( $padli.append( $pad ) );
					var $padname = $( '<p class="session-details"></span>' );
					var ot = pad.time_created;
					var createtime = new Date(
						parseInt( ot.substr( 0, 4 ), 10 ),
						parseInt( ot.substr( 4, 2 ), 10 ) - 1,
						parseInt( ot.substr( 6, 2 ), 10 ),
						parseInt( ot.substr( 8, 2 ), 10 ),
						parseInt( ot.substr( 10, 2 ), 10 ),
						parseInt( ot.substr( 12, 2 ), 10 )
					);
					var timeago = dateUTC( new Date() ) - createtime;
					var tunits = {
						minutes: 60,
						hours: 60,
						days: 24
					};
					var curoff = 1000;
					timeago -= timeago % curoff;
					var timestring = mw.msg( 'seconds', timeago / curoff )
					for ( var tunit in tunits ) {
						curoff *= tunits[tunit];
						if ( timeago / curoff > 1 ) {
							timeago -= timeago % curoff;
							timestring = mw.msg( tunit, timeago / curoff );
						} else {
							break;
						}
					}
					var msg = mw.msg( 'ethereditor-session-created', mw.user.name(), mw.msg( 'ago', timestring ) );
					$padname.text( msg );
					$pad.append( $padname );

					var $padcount = $( '<p class="connected-users"></p>' );
					$padcount.text( mw.msg( 'ethereditor-connected', pad.users_connected ) );
					if ( pad.users_connected == 0 ) {
						$padli.addClass( 'empty-pad' );
						$padcount.hide();
					} else {
						$padli.removeClass( 'empty-pad' );
						$padcount.show();
					}
					$pad.append( $padcount );

					var $padminctrls = $( '<div class="session-button"></div>' );

					// Only render the "split" button (used to be "fork") if the
					// user is connected to this session, so they know what they're
					// copying.
					if ( pad.pad_id === _this.dbId ) {
						// Also, add a class (for later CSS)
						$padli.addClass( 'current-pad' );
						var $copy = $( '<button></button>' )
							.addClass( 'button' )
							.addClass( 'blue' );
						$copy.text( mw.msg( 'ethereditor-fork-button' ) );
						$copy.click( ( function ( padId ) {
							return function () {
								_this.forkPad( padId );
								return false;
							};
						} )( pad.pad_id ) );
						$padminctrls.append( $copy );
					}

//==========================================
					//alert(pad.admin_user  + "notadmin");
					//pad.admin_user == mw.user.name()
					if ( pad.admin_user == mw.user.name() ) {
						//alert(pad.admin_user + mw.user.name());
						var $delete = $( '<button></button>' )
							.addClass( 'button' )
							.addClass( 'blue' );
						$delete.text( mw.msg( 'ethereditor-delete-button' ) );
						$delete.click( ( function ( padId ) {
							return function () {
								//alert("I am in update and going to delete");
								_this.deletePad( padId );
								return false;
							};
						} )( pad.pad_id ) );
						$padminctrls.append( $delete );
					}

					//alert(" _this.iframe: "  + _this.iframe);
					$padli.append( $padminctrls );

					$pad.click( ( function ( thispad ) {
						count_iframenull = count_iframenull + 1;
						//alert("I am in pad.click function value: " + count_iframenull + " Pad val: " +  _this.dbId  + _this.padId);
						return function () {
							if (( _this.iframe == null) && (count_iframenull == 1) ) {
								//alert("I am in if of enableEther");
								_this.enableEther();
							}
							//alert("I am in pad.click in another line");
							_this.dbId = thispad.pad_id;
							_this.padId = thispad.ep_pad_id;
							_this.authenticateUser( function () {
								w_this.initializePad();
							} );
						};
					}) ( pad ) );
				}
			};
			if ( updateRemote ) {
				//alert("I am in GetOtherEtherpads");
				var pads = _this.pads;
				$.ajax( {
					url: mw.util.wikiScript( 'api' ),
					method: 'GET',
					data: { format: 'json', action: 'GetOtherEtherpads', padId: _this.dbId },
					success: function ( data ) {
						_this.pads = data.GetOtherEtherpads.pads;
						finishUpdate();
					},
					dataType: 'json'
				} );
			} else {
				//alert("I am in else and going to finishUpdate");
				finishUpdate();
			}
		},
		/**
		 * Fork a pad into another.
		 */
		forkPad: function ( padId ) {
			//alert("I am in forkPad");
			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: { format: 'json', action: 'ForkEtherPad', padId: padId },
				success: function( data ) {
					_this.padId = data.ForkEtherPad.padId;
					_this.dbId = data.ForkEtherPad.dbId;
					_this.sessionId = data.ForkEtherPad.sessionId;
					_this.isAdmin = true;
					_this.initializePad();
					_this.updatePadList( true );
					return 0;
				},
				dataType: 'json'
			} );
			return false;
		},
		/**
		 * Initializes the pad.
		*/
		initializePad: function () {
			//alert("I am initializePad");
			
			if ($('#wpSummary').is(":focus") ){
				//disableEther();
				$("#wpSummary").keypress(function(event) {
					if (event.which == 13) {
						$( "#epframewpTextbox1" ).remove();
						$.removeCookie('sessionID', { path: '/' });
				event.preventDefault();
				//alert(wgArticlePath);
				window.open (wgServer + wgScript + "/"+ wgPageName,'_self',false);

				//$("#epframe").remove();  
			}
});
                return 0;
               }
                  
				     
			var _this = this;
			_this.iframetimeout = null;
			_this.iframeready = false;
			$.cookie( 'sessionID', _this.sessionId, { domain: mw.config.get( 'wgEtherEditorApiHost' ), path: '/' } );
			
			_this.$textarea.pad( {
				padId: _this.padId,
				baseUrl: _this.baseUrl,
				hideQRCode: true,
				host: 'http://' + _this.hostname,
				showControls: false,
				showLineNumbers: false,
				showChat: true,
				noColors: false,
				useMonospaceFont: true,
				userName: _this.userName,
				height: _this.$textarea.height(),
				width: _this.$textarea.width(),
				border: 1,
				borderStyle: 'solid grey'
			} );
			_this.updateShareLink();
			_this.iframe = _this.$textarea.next( 'iframe' ).get(0);
			$( 'input[name=dbId]' ).remove();
			_this.$textarea.after( $( '<input type="hidden" name="dbId" value="' + _this.dbId + '" />' ) );
			if ( _this.isAdmin ) {
				$( '#ethereditor-admin-ctrls' ).show();
			}
			_this.signalReady();
			_this.updatePadList( true );
		},
		/**
		 * Update the share link to reflect the current pad's information
		 */
		updateShareLink: function () {
			var _this = this;
			var shareuri = new mw.Uri( window.location.href );
			shareuri.query.collaborate = true;
			shareuri.query.padId = _this.dbId;
			_this.$sharelink.val( shareuri.toString() );
		},
		/**
		 * Disable the collaborative editor
		 */
		disableEther: function () {
			//alert("I am in disableEther");
			this.$textarea.show();
			this.$userlist.empty();
			this.users = {};
			this.iframe = null;
			this.iframetimeout = null;
			this.iframeready = false;
			var epframeid = '#epframe' + this.$textarea.attr( 'id' );
			this.sendMessage( 'removeevents' );
			window.removeEventListener( 'message', this.realUserListener );
			$( epframeid ).remove();
		},
		/**
		 * Deletes the pad's contents. (requires admin, of course)
		 * @param padId optional, the ID of the pad to delete
		 */
		deletePad: function ( padId ) {
			//alert("I am in deletePad");
								$.removeCookie('sessionID', { path: '/' });

			var _this = this;
			$.ajax( {
				url: mw.util.wikiScript( 'api' ),
				method: 'GET',
				data: {
					format: 'json', action: 'DeleteEtherPad',
					padId: padId || _this.dbId
				},
				success: function( data ) {
					$( 'option[value=' + _this.dbId + ']', _this.$select ).remove();
					$.removeCookie('sessionID', { path: '/' });
					var px = -1;
					// The below line will loop until it finds the right pad index.
									//alert(newPad.admin_user + " I am admin ");

					while ( ++px < _this.pads.length && _this.pads[px].pad_id != padId ) { }
					_this.pads.splice( px, 1 );
				//alert(newPad.admin_user + " I am admin ");
					if ( padId == _this.dbId ) {
						var newPad = _this.pads[0];
						_this.dbId = newPad.pad_id;
						_this.padId = newPad.ep_pad_id;
						//for(var i=0;i<wgUserGroups.length;i++){
						
							//alert(newPad.admin_user + " I am admin ");
						_this.isAdmin = mw.user.name() == newPad.admin_user;


					//}
						_this.authenticateUser( function () {
							_this.initializePad();
						} );
					}
					_this.updatePadList( true );
					return 0;
				},
				dataType: 'json'
			} );
		}
	};

	$.fn.remoteEditor = function() {
		var $elements = this;
		$.each( $elements, function( i, textarea ) {
			//alert ("I am starts");
			var editor = new remoteEditor( textarea );
		} );
	};

	$( '#wpTextbox1' ).remoteEditor();

} )( jQuery, mediaWiki );
