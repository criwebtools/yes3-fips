const FIPS = {

    'fips_match_status_labels': ['Pending', 'Next API Batch', 'In Process', 'Closed'],

    'summary': {
        n:0,
        pending: 0,
        apiBatch: 0,
        inProcess: 0,
        closed: 0,
        closedMatched: 0,
        closedUnmatched: 0
    }
};

FIPS.hideRefreshElement = function(){

    $('#fips-refresh').hide();
}

FIPS.showRefreshElement = function(){

    $('#fips-refresh').show();
}

FIPS.hideEditorSaveButton = function(){

    //$('input.yes3-save-button').css('opacity', '0.5');
    $('#fips-editor input.fips-savebutton').css('visibility', 'hidden');
    $('#fips-editor input.fips-apibutton').css('visibility', 'visible');
}

FIPS.showEditorSaveButton = function(){

    //$('input.yes3-save-button').css('opacity', '1.0');
    $('#fips-editor input.fips-savebutton').css('visibility', 'visible');
    $('#fips-editor input.fips-apibutton').css('visibility', 'hidden');
}

FIPS.setListeners = function(){

    $('select#fips-filter')
    .off('change')
    .on('change', function(){

        FIPS.hideRefreshElement();

        const filter = $(this).val();

        const $recordInput = $('input#fips-record');

        if ( filter === "record" ){

            $recordInput.show().focus();
        }

        else {
            
            $recordInput.hide()

            YES3.ajax(
                'get-fips-records',
                {
                    'filter': filter,
                    'record': ''
                },
                FIPS.populateTheList
            );
        }
    });

    $('input#fips-api-batch-size')
    .off('change')
    .on('change', function(){

        FIPS.setApiBatchSize( $(this).val() );
    });
}

FIPS.getTheSummary = function(){

    YES3.ajax(
        'get-summary',
        {},
        FIPS.populateTheSummary
    );
}

FIPS.populateTheSummary = function(response){

    $('td#summary_n').html(response.summary_n);

    $('td#summary_pending').html(response.summary_pending);
    $('td#summary_pending_pct').html( FIPS.percentOf(response.summary_pending, response.summary_n, 'total') );

    $('td#summary_apibatch').html(response.summary_apibatch);
    $('td#summary_apibatch_pct').html( FIPS.percentOf(response.summary_apibatch, response.summary_n, 'total') );

    $('td#summary_inprocess').html(response.summary_inprocess);
    $('td#summary_inprocess_pct').html( FIPS.percentOf(response.summary_inprocess, response.summary_n, 'total') );

    $('td#summary_closed').html(response.summary_closed);
    $('td#summary_closed_pct').html( FIPS.percentOf(response.summary_closed, response.summary_n, 'total') );

    $('td#summary_closed_matched').html(response.summary_closed_matched);
    $('td#summary_closed_matched_pct').html( FIPS.percentOf(response.summary_closed_matched, response.summary_closed, 'closed') );

    $('td#summary_closed_unmatched').html(response.summary_closed_unmatched);
    $('td#summary_closed_unmatched_pct').html( FIPS.percentOf(response.summary_closed_unmatched, response.summary_closed, 'closed') );

    FIPS.showOrHideApiElements( response.summary_apibatch );

    console.log('populateTheSummary', response);
}

FIPS.showOrHideApiElements = function( n ){

    if ( parseInt(n) > 0 ){

        $('.fips-api-batch').show();
    }
    else {

        $('.fips-api-batch').hide();
    }
}

FIPS.percentOf = function(n, d, ofWhat){

    if ( d <= 0 ) {

        return "";
    }

    return (Math.round(1000*n/d)/10).toFixed(1) + '% of ' + ofWhat;
}

FIPS.getTheListFor = function(filter){

    $('select#fips-filter').val(filter).trigger('change');
}

FIPS.getTheList = function(){

    const filter = $('select#fips-filter').val();

    const record = $('input#fips-record').val();

    FIPS.hideRefreshElement();

    YES3.ajax(
        'get-fips-records',
        {
            'filter': filter,
            'record': record
        },
        FIPS.populateTheList
    );
}

FIPS.populateTheList = function( response ){

    //console.log( 'populateTheList', response );

    const $tbody = $('div#fips-list-container tbody');

    $tbody.empty();

    for( let i=0; i<response.length; i++ ){

        const $tr = $('<tr>', {

            id: 'fips-list-' + response[i].record
        });

        $tr.append( $('<td>', {
            text: response[i].record
        }));

        $tr.append( $('<td>', {
            class: 'fips_match_status_label',
            text: FIPS.fips_match_status_labels[ response[i].fips_match_status || 0 ]
        }));

        $tr.append( $('<td>', {
            text: response[i].fips_match_result
        }));

        $tr.append( $('<td>', {
            text: response[i].fips_match_type
        }));

        $tr.append( $('<td>', {
            html: `<a href='javaScript:FIPS.openEditor("${response[i].record}")'>edit</a>`
        }));

        $tbody.append( $tr );
    }

    if ( response.length ) {

        FIPS.showRefreshElement();
    }
}

/**
 * FIPS Editor
 */

FIPS.populateTheEditor = function(response){

    //console.log('FIPS.populatedTheEditor', response);

    const x = response[0];

    const $tbody = $('tbody#fips-editor-tbody');

    // chear any 'changed' markings
    $tbody.find('.fips-changed').removeClass('fips-changed');

    // clean slate, no save
    FIPS.hideEditorSaveButton();

    for(const field_name in x ){

        const value = x[field_name];

        const $input = $tbody.find(`#${field_name}`);

        if ( $input.length ){

            $input.val( value );
        }

        //console.log('==>', field_name, value, $input);
    }

    $tbody.find('input, select, textarea')
        .off('change')
        .on('change', function(){

            $(this).addClass('fips-changed');
            FIPS.showEditorSaveButton();
        })
    ;

    const $panel = YES3.openPanel('fips-editor', false, 0, 0, 1);

    const $header = $('div#fips-editor-header');

    const $footer = $('div#fips-editor-footer');

    const $formContainer = $('div#fips-editor-container');

    let formHeight = $(window).height() - $header.outerHeight() - $footer.outerHeight() - 4*8 - 80;

    //console.log(`resize: ${canvasHeight} - ${panelTop} - ${headerHeight} - ${footerHeight} = ${formHeight}`);

    if ( formHeight > 1000 ) formHeight = 1000;

    $formContainer.height( formHeight+'px' );

    FIPS.hideEditorSaveButton();

    FIPS.clearEditorMessage();
}

FIPS.openEditor = function( record ){

    console.log('openEditor', record);

    $('#fips-record').html( record );

    const $tbody = $('tbody#fips-editor-tbody');

    const fields = YES3.moduleProperties.fips_editor_fields;

    $tbody
        .empty()
        .append( FIPS.fipsEditorRow('record', 'REDCap record', 'text', 0, 50, []) )
    ;

    for(let i=0; i<fields.length; i++){

        let choices = ( fields[i].choices==undefined ) ? []:fields[i].choices;

        $tbody.append( FIPS.fipsEditorRow(fields[i].field_name, fields[i].label, fields[i].type, fields[i].editable, fields[i].size, choices) );
    }

    FIPS.loadRecordIntoEditor( record );
}

FIPS.loadRecordIntoEditor = function( record ){

    YES3.ajax(
        'get-fips-records',
        {
            'filter': "record",
            'record': record
        },
        FIPS.populateTheEditor
    );
}

FIPS.fipsEditorRow = function(field_name, label, input_type, editable, size, choices, value){

    value = value || '';

    const $tr = $('<tr>', {
        id: 'fips-editor-' + field_name
    });

    if (input_type==='hidden'){

        $tr.addClass('fips-hidden');
    }

    $tr.append( $('<td>', {
        class: 'fips-editor-label',
        html: label
    }));

    const $td = $('<td>', {
        class: 'fips-editor-input',
        id: 'fips-editor-input-' + field_name
    });

    let $input = null;

    if ( input_type==='text' || input_type==='hidden' ) {

        $input = $('<input>', {

            type: 'text',
            id: field_name,
        })
    }
    else if ( input_type==='textarea' ) {

        $tr.addClass('fips-textarea');

        $input = $('<textarea>', {

            id: field_name
        });
    }
    else if ( input_type==='select' ) {

        $input = $('<select>', {

            id: field_name
        });

        for(let i=0; i<choices.length; i++){

            $input.append( $('<option>', {
                value: choices[i].value,
                text: choices[i].label
            }));
        }
    }

    $input.css({'width': size + '%'});

    if ( !editable ){

        $input
        .prop('disabled', true)
        .addClass('fips-disabled')
        ;

    }

    if ( value ){

        $input.val( value );
    }

    $td.append( $input );

    $tr.append( $td );

    return $tr;
}

FIPS.closeEditor = function( save ){

    save = save || false;

    if ( save ){

        FIPS.saveEditorRecord();
    }
    else {

        YES3.closePanel('fips-editor');
    }
}

FIPS.saveEditorRecord = function(){

    const $tbody = $('tbody#fips-editor-tbody');
    const record = $tbody.find('input#record').val();

    FIPS.postEditorMessage("WAIT");

    x = {};

    $tbody.find('.fips-changed').each(function(){

        x[$(this).prop('id')] = $(this).val();
    });

    console.log('saveEditorRecord', x)

    YES3.ajax(
        'save-fips-record',
        {
            'data': x,
            'record': record
        },
        FIPS.saveEditorRecordConfirmation
    );
}

FIPS.saveEditorRecordConfirmation = function(response){

    const $tbodyEd = $('tbody#fips-editor-tbody');

    const record = $tbodyEd.find('input#record').val();

    if ( response.item_count > 0 ){

        FIPS.updateListFromEditor();

        FIPS.loadRecordIntoEditor( record ); // re-load record 

        FIPS.getTheSummary();

        //YES3.closePanel('fips-editor');
    }
    else {

        YES3.hello('Error: The save operation failed. See the console log for more information.')
        console.error( 'saveEditorRecordConfirmation FAIL', response );
    }
}

FIPS.postEditorMessage = function(msg){

    $('#fips-editor-message').html(msg);
}

FIPS.clearEditorMessage = function(){

    $('#fips-editor-message').html("");
}

FIPS.updateListFromEditor = function(){

    const $tbodyEd = $('tbody#fips-editor-tbody');
    const $tbodyList = $('div#fips-list-container tbody');
    const record = $tbodyEd.find('input#record').val();
    const fips_match_status = $tbodyEd.find('select#fips_match_status').val() || 0;
    const $row = $tbodyList.find('tr#fips-list-' + record);

    $row.addClass('fips-changed');

    $row.find('td.fips_match_status_label').html( FIPS.fips_match_status_labels[fips_match_status] );
}

FIPS.updateAPIBatch = function(){

    YES3.ajax(
        'update-api-batch',
        {},
        FIPS.updateAPIBatchConfirmation
    );
}

FIPS.updateAPIBatchConfirmation = function( response ){

    FIPS.postMessage( response );

    FIPS.getTheSummary();

    FIPS.getTheList();

    console.log('updateAPIBatchConfirmation', response);
}

FIPS.clearApiBatch = function(){

    YES3.ajax(
        'clear-api-batch',
        {},
        FIPS.clearApiBatchConfirmation
    );

}

FIPS.clearApiBatchConfirmation = function( response ){

    FIPS.postMessage(response);

    FIPS.getTheSummary();

    FIPS.getTheList();

    console.log('clearApiBatchConfirmation', response);
}

FIPS.callApiFromEditor = function(){

    FIPS.postEditorMessage("WAIT");

    const record = $('#fips-editor input#record').val();

    YES3.ajax(
        'call-api',
        {
            record: record
        },
        FIPS.callApiFromEditorConfirmation
    )
}

FIPS.callApiFromEditorConfirmation = function(response){

    console.log(response);

    const record = $('#fips-editor input#record').val();

    if ( record ){

        FIPS.loadRecordIntoEditor( record );
    }
}

FIPS.callAPI = function(){

    YES3.ajax(
        'call-api',
        {},
        FIPS.callAPIConfirmation
    );
}

FIPS.callAPIConfirmation = function( response ){

    FIPS.postMessage( response );

    FIPS.getTheSummary();

    FIPS.getTheList();

    console.log('callAPIConfirmation', response);
}

FIPS.setApiBatchSize = function(api_batch_size){

    YES3.ajax(
        'set-api-batch-size',
        {
            'api_batch_size': api_batch_size
        },
        FIPS.setApiBatchSizeConfirmation
    );
}

FIPS.setApiBatchSizeConfirmation = function(response){

    console.log('setApiBatchSizeConfirmation', response);
}

FIPS.getApiBatchSize = function(){

    YES3.ajax(
        'get-api-batch-size',
        {},
        FIPS.getApiBatchSizeConfirmation
    );
}

FIPS.getApiBatchSizeConfirmation = function(response){

    $('input#fips-api-batch-size').val(response);
}

$(window).off("resize").on("resize", function(){

    FIPS.onResize();
});

FIPS.onResize = function(){

    const $listContainer = $('div#fips-list-container');

    const Hw = $(window).innerHeight();

    const top = $listContainer.offset().top;

    //const Hf = $('#south').outerHeight();

    //console.log('onResize', Hw, top);

    $listContainer.height( (Hw - top - 80)+'px' );
}

FIPS.postMessage = function( msg, danger, timeout ){

    danger = danger || false;

    let msgClass = ( danger ) ? "yes3-danger":"yes3-warning";

    timeout = timeout || 10000;

    if ( typeof FIPS.messageTimeoutId === 'number' ){

        clearTimeout( FIPS.messageTimeoutId );

        FIPS.messageTimeoutId = undefined;
    }

    $("#fips-message")
        .removeClass("yes3-warning")
        .removeClass("yes3-danger")
        .addClass(msgClass)
        .html( msg )
    ;

    FIPS.messageTimeoutId = setTimeout(function(){

        FIPS.clearMessage();
    }, timeout);
}

FIPS.clearMessage = function(){

    $("#fips-message").html("");
}

FIPS.injectActionIcons = function() {

    let $iconHelp = $("<i>", {"class":"fas fa-question yes3-action-icon", "action":"showPageHelp", "title": "Get some help"});
    let $iconThemeLight = $("<i>", {"class":"fas fa-sun yes3-action-icon yes3-dark-theme-only", "action":"setThemeLight", "title": "Switch to the sunny side"});
    let $iconThemeDark = $("<i>", {"class":"fas fa-moon yes3-action-icon yes3-light-theme-only", "action":"setThemeDark", "title": "Give in to the dark side"});

    $('#fips-action-icons').empty().append($iconThemeDark).append($iconThemeLight);
}

$(function(){

    FIPS.injectActionIcons();

    FIPS.setListeners();

    YES3.setActionIconListeners();

    YES3.setTheme( YES3.getTheme() );

    YES3.displayActionIcons();

    FIPS.getApiBatchSize();

    FIPS.getTheSummary();

    FIPS.getTheListFor('inprocess');

    $(window).trigger('resize');
})
