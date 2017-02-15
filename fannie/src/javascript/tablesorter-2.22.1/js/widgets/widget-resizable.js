/*! Widget: resizable - updated 5/17/2015 (v2.22.0) */
;(function ($, window) {
'use strict';
var ts = $.tablesorter || {};

$.extend(ts.css, {
	resizableContainer : 'tablesorter-resizable-container',
	resizableHandle    : 'tablesorter-resizable-handle',
	resizableNoSelect  : 'tablesorter-disableSelection',
	resizableStorage   : 'tablesorter-resizable'
});

// Add extra scroller css
$(function(){
	var s = '<style>' +
		'body.' + ts.css.resizableNoSelect + ' { -ms-user-select: none; -moz-user-select: -moz-none;' +
			'-khtml-user-select: none; -webkit-user-select: none; user-select: none; }' +
		'.' + ts.css.resizableContainer + ' { position: relative; height: 1px; }' +
		// make handle z-index > than stickyHeader z-index, so the handle stays above sticky header
		'.' + ts.css.resizableHandle + ' { position: absolute; display: inline-block; width: 8px; top: 1px;' +
			'cursor: ew-resize; z-index: 3; user-select: none; -moz-user-select: none; }' +
		'</style>';
	$(s).appendTo('body');
});

ts.resizable = {
	init : function( c, wo ) {
		if ( c.$table.hasClass( 'hasResizable' ) ) { return; }
		c.$table.addClass( 'hasResizable' );
		ts.resizableReset( c.table, true ); // set default widths

		// internal variables
		wo.resizable_ = {
			$wrap : c.$table.parent(),
			mouseXPosition : 0,
			$target : null,
			$next : null,
			overflow : c.$table.parent().css('overflow') === 'auto',
			fullWidth : Math.abs(c.$table.parent().width() - c.$table.width()) < 20,
			storedSizes : []
		};

		var noResize, $header, column, storedSizes,
			marginTop = parseInt( c.$table.css( 'margin-top' ), 10 );

		wo.resizable_.storedSizes = storedSizes = ( ( ts.storage && wo.resizable !== false ) ?
			ts.storage( c.table, ts.css.resizableStorage ) :
			[] ) || [];
		ts.resizable.setWidths( c, wo, storedSizes );

		wo.$resizable_container = $( '<div class="' + ts.css.resizableContainer + '">' )
			.css({ top : marginTop })
			.insertBefore( c.$table );
		// add container
		for ( column = 0; column < c.columns; column++ ) {
			$header = c.$headerIndexed[ column ];
			noResize = ts.getData( $header, ts.getColumnData( c.table, c.headers, column ), 'resizable' ) === 'false';
			if ( !noResize ) {
				$( '<div class="' + ts.css.resizableHandle + '">' )
					.appendTo( wo.$resizable_container )
					.attr({
						'data-column' : column,
						'unselectable' : 'on'
					})
					.data( 'header', $header )
					.bind( 'selectstart', false );
			}
		}
		c.$table.one('tablesorter-initialized', function() {
			ts.resizable.setHandlePosition( c, wo );
			ts.resizable.bindings( this.config, this.config.widgetOptions );
		});
	},

	setWidth : function( $el, width ) {
		$el.css({
			'width' : width,
			'min-width' : '',
			'max-width' : ''
		});
	},

	setWidths : function( c, wo, storedSizes ) {
		var column,
			$extra = $( c.namespace + '_extra_headers' ),
			$col = c.$table.children( 'colgroup' ).children( 'col' );
		storedSizes = storedSizes || wo.resizable_.storedSizes || [];
		// process only if table ID or url match
		if ( storedSizes.length ) {
			for ( column = 0; column < c.columns; column++ ) {
				// set saved resizable widths
				c.$headerIndexed[ column ].width( storedSizes[ column ] );
				if ( $extra.length ) {
					// stickyHeaders needs to modify min & max width as well
					ts.resizable.setWidth( $extra.eq( column ).add( $col.eq( column ) ), storedSizes[ column ] );
				}
			}
			if ( $( c.namespace + '_extra_table' ).length && !ts.hasWidget( c.table, 'scroller' ) ) {
				ts.resizable.setWidth( $( c.namespace + '_extra_table' ), c.$table.outerWidth() );
			}
		}
	},

	setHandlePosition : function( c, wo ) {
		var startPosition,
			hasScroller = ts.hasWidget( c.table, 'scroller' ),
			tableHeight = c.$table.height(),
			$handles = wo.$resizable_container.children(),
			handleCenter = Math.floor( $handles.width() / 2 );

		if ( hasScroller ) {
			tableHeight = 0;
			c.$table.closest( '.' + ts.css.scrollerWrap ).children().each(function(){
				var $this = $(this);
				// center table has a max-height set
				tableHeight += $this.filter('[style*="height"]').length ? $this.height() : $this.children('table').height();
			});
		}
		// subtract out table left position from resizable handles. Fixes #864
		startPosition = c.$table.position().left;
		$handles.each( function() {
			var $this = $(this),
				column = parseInt( $this.attr( 'data-column' ), 10 ),
				columns = c.columns - 1,
				$header = $this.data( 'header' );
			if ( !$header ) { return; } // see #859
			if ( !$header.is(':visible') ) {
				$this.hide();
			} else if ( column < columns || column === columns && wo.resizable_addLastColumn ) {
				$this.css({
					display: 'inline-block',
					height : tableHeight,
					left : $header.position().left - startPosition + $header.outerWidth() - handleCenter
				});
			}
		});
	},

	// prevent text selection while dragging resize bar
	toggleTextSelection : function( c, toggle ) {
		var namespace = c.namespace + 'tsresize';
		c.widgetOptions.resizable_.disabled = toggle;
		$( 'body' ).toggleClass( ts.css.resizableNoSelect, toggle );
		if ( toggle ) {
			$( 'body' )
				.attr( 'unselectable', 'on' )
				.bind( 'selectstart' + namespace, false );
		} else {
			$( 'body' )
				.removeAttr( 'unselectable' )
				.unbind( 'selectstart' + namespace );
		}
	},

	bindings : function( c, wo ) {
		var namespace = c.namespace + 'tsresize';
		wo.$resizable_container.children().bind( 'mousedown', function( event ) {
			// save header cell and mouse position
			var column, $this,
				vars = wo.resizable_,
				$extras = $( c.namespace + '_extra_headers' ),
				$header = $( event.target ).data( 'header' );

			column = parseInt( $header.attr( 'data-column' ), 10 );
			vars.$target = $header = $header.add( $extras.filter('[data-column="' + column + '"]') );
			vars.target = column;

			// if table is not as wide as it's parent, then resize the table
			vars.$next = event.shiftKey || wo.resizable_targetLast ?
				$header.parent().children().not( '.resizable-false' ).filter( ':last' ) :
				$header.nextAll( ':not(.resizable-false)' ).eq( 0 );

			column = parseInt( vars.$next.attr( 'data-column' ), 10 );
			vars.$next = vars.$next.add( $extras.filter('[data-column="' + column + '"]') );
			vars.next = column;

			vars.mouseXPosition = event.pageX;
			vars.storedSizes = [];
			for ( column = 0; column < c.columns; column++ ) {
				$this = c.$headerIndexed[ column ];
				vars.storedSizes[ column ] = $this.is(':visible') ? $this.width() : 0;
			}
			ts.resizable.toggleTextSelection( c, true );
		});

		$( document )
			.bind( 'mousemove' + namespace, function( event ) {
				var vars = wo.resizable_;
				// ignore mousemove if no mousedown
				if ( !vars.disabled || vars.mouseXPosition === 0 || !vars.$target ) { return; }
				if ( wo.resizable_throttle ) {
					clearTimeout( vars.timer );
					vars.timer = setTimeout( function() {
						ts.resizable.mouseMove( c, wo, event );
					}, isNaN( wo.resizable_throttle ) ? 5 : wo.resizable_throttle );
				} else {
					ts.resizable.mouseMove( c, wo, event );
				}
			})
			.bind( 'mouseup' + namespace, function() {
				if (!wo.resizable_.disabled) { return; }
				ts.resizable.toggleTextSelection( c, false );
				ts.resizable.stopResize( c, wo );
				ts.resizable.setHandlePosition( c, wo );
			});

		// resizeEnd event triggered by scroller widget
		$( window ).bind( 'resize' + namespace + ' resizeEnd' + namespace, function() {
			ts.resizable.setHandlePosition( c, wo );
		});

		// right click to reset columns to default widths
		c.$table
			.bind( 'columnUpdate' + namespace, function() {
				ts.resizable.setHandlePosition( c, wo );
			})
			.find( 'thead:first' )
			.add( $( c.namespace + '_extra_table' ).find( 'thead:first' ) )
			.bind( 'contextmenu' + namespace, function() {
				// $.isEmptyObject() needs jQuery 1.4+; allow right click if already reset
				var allowClick = wo.resizable_.storedSizes.length === 0;
				ts.resizableReset( c.table );
				ts.resizable.setHandlePosition( c, wo );
				wo.resizable_.storedSizes = [];
				return allowClick;
			});

	},

	mouseMove : function( c, wo, event ) {
		if ( wo.resizable_.mouseXPosition === 0 || !wo.resizable_.$target ) { return; }
		// resize columns
		var vars = wo.resizable_,
			$next = vars.$next,
			leftEdge = event.pageX - vars.mouseXPosition;
		if ( vars.fullWidth ) {
			vars.storedSizes[ vars.target ] += leftEdge;
			vars.storedSizes[ vars.next ] -= leftEdge;
			ts.resizable.setWidths( c, wo );

		} else if ( vars.overflow ) {
			c.$table.add( $( c.namespace + '_extra_table' ) ).width(function(i, w){
				return w + leftEdge;
			});
			if ( !$next.length ) {
				// if expanding right-most column, scroll the wrapper
				vars.$wrap[0].scrollLeft = c.$table.width();
			}
		} else {
			vars.storedSizes[ vars.target ] += leftEdge;
			ts.resizable.setWidths( c, wo );
		}
		vars.mouseXPosition = event.pageX;
	},

	stopResize : function( c, wo ) {
		var $this, column,
			vars = wo.resizable_;
		vars.storedSizes = [];
		if ( ts.storage ) {
			vars.storedSizes = [];
			for ( column = 0; column < c.columns; column++ ) {
				$this = c.$headerIndexed[ column ];
				vars.storedSizes[ column ] = $this.is(':visible') ? $this.width() : 0;
			}
			if ( wo.resizable !== false ) {
				// save all column widths
				ts.storage( c.table, ts.css.resizableStorage, vars.storedSizes );
			}
		}
		vars.mouseXPosition = 0;
		vars.$target = vars.$next = null;
		$(window).trigger('resize'); // will update stickyHeaders, just in case
	}
};

// this widget saves the column widths if
// $.tablesorter.storage function is included
// **************************
ts.addWidget({
	id: "resizable",
	priority: 40,
	options: {
		resizable : true,
		resizable_addLastColumn : false,
		resizable_widths : [],
		resizable_throttle : false, // set to true (5ms) or any number 0-10 range
		resizable_targetLast : false
	},
	init: function(table, thisWidget, c, wo) {
		ts.resizable.init( c, wo );
	},
	remove: function( table, c, wo, refreshing ) {
		if (wo.$resizable_container) {
			var namespace = c.namespace + 'tsresize';
			c.$table.add( $( c.namespace + '_extra_table' ) )
				.removeClass('hasResizable')
				.children( 'thead' ).unbind( 'contextmenu' + namespace );

				wo.$resizable_container.remove();
			ts.resizable.toggleTextSelection( c, false );
			ts.resizableReset( table, refreshing );
			$( document ).unbind( 'mousemove' + namespace + ' mouseup' + namespace );
		}
	}
});

ts.resizableReset = function( table, refreshing ) {
	$( table ).each(function(){
		var index, $t,
			c = this.config,
			wo = c && c.widgetOptions;
		if ( table && c && c.$headerIndexed.length ) {
			for ( index = 0; index < c.columns; index++ ) {
				$t = c.$headerIndexed[ index ];
				if ( wo.resizable_widths && wo.resizable_widths[ index ] ) {
					$t.css( 'width', wo.resizable_widths[ index ] );
				} else if ( !$t.hasClass( 'resizable-false' ) ) {
					// don't clear the width of any column that is not resizable
					$t.css( 'width', '' );
				}
			}
			// reset stickyHeader widths
			$( window ).trigger( 'resize' );
			if ( ts.storage && !refreshing ) {
				ts.storage( this, ts.css.resizableStorage, {} );
			}
		}
	});
};

})( jQuery, window );
