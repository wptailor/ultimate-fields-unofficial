jQuery(function( $ ) {

	// Don't do anything unless we're on the right page
	if( ! $( '#ultimate-fields, #ultimate-post-type' ).size() && typenow != 'ultimate-post-type' ) {
		return;
	}

	$( document ).on( 'change', '.uf-field[data-id="field_title"] input[type=text]', function() {
		var title = $( this ).val(),
			$idField = $( this ).closest( '.uf-field' ).siblings( '[data-id="field_id"]' ).find( 'input[type=text]' );

		if( ! $idField.val() ) {
			title = title.replace( /[\-_ ]/g, '_' ).replace( /[^_a-z]/ig, '' ).toLowerCase().replace( /^_*?(.+[^_])_*?$/, '$1' );
			$idField.val( title ).trigger( 'keyup' );
		}
	} );

	$( document ).on( 'change', '.uf-field[data-id="title"] input[type=text]', function() {
		var value = $( this ).val(),
			$idField = $( this ).closest( '.uf-field' ).siblings( '[data-id="key"]' ).find( 'input[type=text]' );

		if( ! $idField.val() ) {
			value = value.replace( /[\-_ ]/g, '_' ).replace( /[^_a-z]/ig, '' ).toLowerCase().replace( /^_*?(.+[^_])_*?$/, '$1' );
			$idField.val( value ).trigger( 'keyup' );
		}
	} );

});