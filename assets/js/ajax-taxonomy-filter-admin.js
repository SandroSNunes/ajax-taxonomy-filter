( function( $ ) {

    $( function () {

		/**
		 * Toggle elements.
		 */
        $( '[data-toggle]' ).change( function (e) { 
            $( $( this ).data('toggle') ).toggle();
        });

		/**
		 * When loading the widget, toggle all the checked elements.
		 */
        $( document ).on( 'widget-added widget-updated panelsopen', function( root, element ) {
            $( '[data-toggle]' ).change( function (e) { 
                $( $( this ).data( 'toggle' ) ).toggle();
            });
        });

    });

} )( jQuery );