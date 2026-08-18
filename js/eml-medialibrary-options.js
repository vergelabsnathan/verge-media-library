  ( function( $ ) {

    var orderValue;



    $( document ).ready( function() {

        orderValue = $('#vergeml_lib_options_media_order').val();
        $('#vergeml_lib_options_media_orderby').trigger( 'change' );
        $('#vergeml_lib_options_grid_show_caption').trigger( 'change' );
        $('#vergeml_lib_options_search_on_enter').trigger( 'change' );
        $('#vergeml_lib_options_search_auto').trigger( 'change' );
    });



    $( document ).on( 'change', '#vergeml_lib_options_media_orderby', function( event ) {

        var isMenuOrder = 'menuOrder' === $( event.target ).val(),
            isTitleOrder = 'title' === $( event.target ).val(),
            value;

        orderValue = isMenuOrder ? $('#vergeml_lib_options_media_order').val() : orderValue;
        value = isMenuOrder ? 'ASC' : orderValue;

        $('#vergeml_lib_options_media_order').prop( 'disabled', isMenuOrder ).val( value );
        $('#vergeml_lib_options_natural_sort').prop( 'hidden', ! isTitleOrder );
    });



    $( document ).on( 'change', '#vergeml_lib_options_grid_show_caption', function( event ) {

        var isChecked = $(this).prop( 'checked' );

        $('#vergeml_lib_options_grid_caption_type').prop( 'hidden', ! isChecked );
    });


    $( document ).on( 'click', 'input[readonly], .disabled .submit input.button', function( event ) {
        event.preventDefault();
    });


    $( document ).on( 'change', '#vergeml_lib_options_search_in input[type=checkbox].search_columns', function( event ) {

        if ( ! $( '#vergeml_lib_options_search_in input.search_columns:checked' ).length ) {
            $( event.target ).prop( 'checked', true );
        }
    });


    $( document ).on( 'change', '#vergeml_lib_options_search_on_enter', function( event ) {

        var isChecked = $(this).prop( 'checked' );

        if ( ! isChecked ) {
            $('#vergeml_lib_options_search_auto').prop( 'checked', true ).trigger( 'change' );
        }
        $('#vergeml_lib_options_search_auto').prop( 'disabled', ! isChecked );
    });

    $( document ).on( 'change', '#vergeml_lib_options_search_auto', function( event ) {

        var isChecked = $(this).prop( 'checked' );

        $('#vergeml_lib_options_search_min_letters').prop( 'hidden', ! isChecked );
    });

})( jQuery );
