(function( $ ){

  var padIdTransforms = [
    [/\s+/g, '_'],
    [/:+/g, '_']
  ];

  $.fn.pad = function( options ) {
    var settings = {
      'host'              : 'http://beta.etherpad.org',
      'baseUrl'           : '/p',
      'showControls'      : false,
      'showChat'          : false,
      'showLineNumbers'   : false,
      'userName'          : 'unnamed',
      'useMonospaceFont'  : false,
      'noColors'          : false,
      'hideQRCode'        : false,
      'width'             : 100,
      'height'            : 100,
      'border'            : 0,
      'borderStyle'       : 'solid',
      'toggleTextOn'      : 'Disable Rich-text',
      'toggleTextOff'     : 'Enable Rich-text'
    };

    var $self = this;
    if (!$self.length) return;
    if (!$self.attr('id')) throw new Error('No "id" attribute');

    var useValue = $self.is( 'textarea' );
    var selfId = $self.attr('id');
    var epframeId = 'epframe'+ selfId;
    var $other = $( '#' + epframeId );
    $other.remove();
    // This writes a new frame if required
    if ( !options.getContents ) {
      if ( options ) {
        $self.data( 'ep-options', options );
        $.extend( settings, options );
      }

      var padUrl = settings.host+settings.baseUrl+'/'+settings.padId;

      var setupEp = function () {
        var iFrameLink = '<iframe id="'+epframeId;
            iFrameLink += '" name="'+epframeId;
            iFrameLink += '" src="'+padUrl;
            iFrameLink +=  '?showControls='+settings.showControls;
            iFrameLink +=  '&showChat='+settings.showChat;
            iFrameLink +=  '&showLineNumbers='+settings.showLineNumbers;
            iFrameLink +=  '&useMonospaceFont='+settings.useMonospaceFont;
            iFrameLink +=  '&userName=' + settings.userName;
            iFrameLink +=  '&noColors=' + settings.noColors;
            iFrameLink +=  '&hideQRCode=' + settings.hideQRCode;
            iFrameLink += '" style="border:'+settings.border;
            iFrameLink += '; border-style:'+settings.borderStyle;
  //          iFrameLink += '; width:'+settings.width;
  //          iFrameLink += '; height:'+settings.height;
            iFrameLink += ';" width="'+ '100%';//settings.width;
            iFrameLink += '" height="'+ settings.height;
            iFrameLink += '"></iframe>';


        var $iFrameLink = $(iFrameLink);

        if (useValue) {
          $self.hide().after($iFrameLink);
        }
        else {
          $self.html($iFrameLink);
        }
      };

      setupEp();
    }

    else {
      // there is no need for this, we don't use this feature of the library.
      // since I already monkeyed around with it, I figured it's best to just
      // remove this part.
    }


    return $self;
  };
})( jQuery );
