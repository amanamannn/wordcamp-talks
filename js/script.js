/* global wct_vars */
/*!
 * WordCamp Talks script
 */

( function( $ ) {

	// Cleanup the url
	if ( wct_vars.canonical && window.history.replaceState ) {
			window.history.replaceState( null, null, wct_vars.canonical + window.location.hash );
	}

	// Only use raty if loaded
	if ( typeof wct_vars.raty_loaded !== 'undefined' ) {

		wpis_update_rate_num( 0 );

		$( 'div#rate' ).raty( {
			cancel     : false,
			half       : false,
			halfShow   : true,
			starType   : 'i',
			readOnly   : wct_vars.readonly,
			score      : wct_vars.average_rate,
			targetKeep : false,
			noRatedMsg : wct_vars.not_rated,
			hints      : wct_vars.hints,
			number     : wct_vars.hints_nb,
			click      : function( score ) {
				if ( ! wct_vars.can_rate ) {
					return;
				}
				// Disable the rating stars
				$.fn.raty( 'readOnly', true, '#rate' );
				// Update the score
				wct_post_rating( score );
			}
		} );
	}

	function wct_post_rating( score ) {
		$( '.rating-info' ).html( wct_vars.wait_msg );

		var data = {
			action: 'wct_rate',
			rate: score,
			wpnonce: wct_vars.wpnonce,
			talk:$('#rate').data('talk')
		};

		$.post( wct_vars.ajaxurl, data, function( response ) {
			if( response && response > 0  ){
				$( '.rating-info' ).html( wct_vars.success_msg + ' ' + response ).fadeOut( 2000, function() {
					wpis_update_rate_num( 1 );
					$(this).show();
				} );
			} else {
				$( '.rating-info' ).html( wct_vars.error_msg );
				$.fn.raty( 'readOnly', false, '#rate' );
			}
		});
	}

	function wpis_update_rate_num( rate ) {
		var number = Number( wct_vars.rate_nb ) + rate,
			msg;

		if ( 1 === number ) {
			msg = wct_vars.one_rate;
		} else if( 0 === number ) {
			msg = wct_vars.not_rated;
		} else {
			msg = wct_vars.x_rate.replace( '%', number );
		}

		$( '.rating-info' ).html( '<a>' + msg + '</a>' );
	}

	// Checkbox are radio groups!
	$( '#wordcamp-talks-form ul.category-list' ).on( 'click', ':checkbox', function( event ) {
		$.each( $( event.delegateTarget ).find( ':checked' ), function( cb, checkbox ) {
			if ( $( checkbox ).prop( 'id' ) !== $( event.target ).prop( 'id' ) ) {
				$( checkbox ).prop( 'checked', false );
			}
		} );
	} );

	if ( typeof wct_vars.tagging_loaded !== 'undefined' ) {
		$( '#_wct_the_tags' ).tagging( {
			'tags-input-name'      : 'wct[_the_tags]',
			'edit-on-delete'       : false,
			'tag-char'             : '',
			'forbidden-chars'      : [ '.', '_', '?', '<', '>' ],
			'forbidden-words'      : [ '&lt;', '&gt;' ],
			'no-duplicate-text'    : wct_vars.duplicate_tag,
			'forbidden-chars-text' : wct_vars.forbidden_chars,
			'forbidden-words-text' : wct_vars.forbidden_words
		} );

		// Make sure the title gets the focus
		$( '#_wct_the_title' ).focus();

		// Add most used tags
		$( '#wct_most_used_tags .tag-items a' ).on( 'click', function( event ) {
			event.preventDefault();

			$( '#_wct_the_tags' ).tagging( 'add', $( this ).html() );
		} );

		// Reset tags
		$( '#wordcamp-talks-form' ).on( 'reset', function() {
			$( '#_wct_the_tags' ).tagging( 'reset' );
		} );
	}

	// Set the interval and the namespace event
	if ( typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined' && typeof wct_vars.pulse !== 'undefined' ) {
		wp.heartbeat.interval( wct_vars.pulse );

		$.fn.extend( {
			'heartbeat-send': function() {
				return this.bind( 'heartbeat-send.wc_talks' );
			}
		} );
	}

	// Send the current talk ID being edited
	$( document ).on( 'heartbeat-send.wc_talks', function( e, data ) {
		data.wc_talks_heartbeat_current_talk = wct_vars.talk_id;
	} );

	// Inform the user if data has been returned
	$( document ).on( 'heartbeat-tick', function( e, data ) {

		// Only proceed if an admin took the lead
		if ( ! data.wc_talks_heartbeat_response ) {
			return;
		}

		if ( ! $( '#wordcamp-talks .message' ).length ) {
			$( '#wordcamp-talks' ).prepend(
				'<div class="message info">' +
					'<p>' + wct_vars.warning + '</p>' +
				'</div>'
			);
		} else {
			$( '#wordcamp-talks .message' ).removeClass( 'error' ).addClass( 'info' );
			$( '#wordcamp-talks .message p' ).html( wct_vars.warning );
		}

		$( '#wordcamp-talks .submit input[name="wct[save]"]' ).remove();
	} );

})( jQuery );
