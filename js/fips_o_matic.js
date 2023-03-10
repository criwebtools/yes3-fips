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
    },

    constants: {
        NEVER: 0,
        IF_SINGLE_ADDRESS_FIELD: 1,
        IF_MULTIPLE_ADDRESS_FIELDS: 2,
        ALWAYS: 3,

        SHOW_API_BUTTON: 1,
        HIDE_API_BUTTON: 2,

        MATCH_STATUS_PENDING:'0',
        MATCH_STATUS_NEXT_API_BATCH: '1',
        MATCH_STATUS_IN_PROCESS: '2',
        MATCH_STATUS_CLOSED: '3'
    }
};

FIPS.hideRefreshElement = function(){

    $('#fips-refresh').hide();
}

FIPS.showRefreshElement = function(){

    $('#fips-refresh').show();
}

FIPS.showListCount = function( n ){

    $('#fips-list-count').html(`n = ${n}`);
}

FIPS.hideListCount = function( n ){

    $('#fips-list-count').html("");
}

FIPS.hideEditorSaveButton = function(showAPIbutton){

    showAPIbutton = showAPIbutton || FIPS.constants.SHOW_API_BUTTON;

    //$('input.yes3-save-button').css('opacity', '0.5');
    $('#fips-editor input.fips-savebutton').css('visibility', 'hidden');

    if ( showAPIbutton===FIPS.constants.SHOW_API_BUTTON ){

        $('#fips-editor input.fips-apibutton').css('visibility', 'visible');
    }
}

FIPS.showEditorSaveButton = function(){

    //$('input.yes3-save-button').css('opacity', '1.0');
    $('#fips-editor input.fips-savebutton').css('visibility', 'visible');
    $('#fips-editor input.fips-apibutton').css('visibility', 'hidden');
}

FIPS.clearEditorChangedStatus = function(){

    $(`.fips-changed`).removeClass('fips-changed');

    FIPS.hideEditorSaveButton( FIPS.constants.HIDE_API_BUTTON );
}

FIPS.hideEditorRestoreButton = function(){

    $('#fips-editor input.fips-restorebutton').css('visibility', 'hidden');
}

FIPS.showEditorRestoreButton = function(){

    $('#fips-editor input.fips-restorebutton').css('visibility', 'visible');
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

FIPS.porpulateTheList = function( response ){

    console.log( 'populateTheList', response );

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
            text: response[i].fips_match_result,
            class: 'fips_match_result fips-disposable'
        }));

        $tr.append( $('<td>', {
            text: response[i].fips_match_type,
            class: 'fips_match_type'
        }));

        $tr.append( $('<td>', {
            html: `<a href='javaScript:FIPS.openEditor("${response[i].record}")'>edit</a>`,
            class: 'fips-edit-link'
        }));

        $tbody.append( $tr );
    }

    if ( response.length ) {

        FIPS.showRefreshElement();

        FIPS.showListCount( response.length );
    }
    else {

        FIPS.hideListCount();
    }
}

/**
 * FIPS Editor
 */

FIPS.populateTheEditor = function(response){

    console.log('FIPS.populatedTheEditor', response);

    const x = response[0];

    const $tbody = $('tbody#fips-editor-tbody');

    // chear any 'changed' markings
    $tbody.find('.fips-changed').removeClass('fips-changed');

    // clean slate, no save
    FIPS.hideEditorSaveButton();

    // more cleanup
    $('input#fips_accept_match').remove();

    for(const field_name in x ){

        const value = x[field_name];

        const $input = $tbody.find(`#${field_name}`);

        if ( $input.length ){

            $input.val( value );
        }

        //console.log('==>', field_name, value, $input);
    }

    if ( x.fips_match_type === 'Non_Exact' && x.fips_match_status !== FIPS.constants.MATCH_STATUS_CLOSED ){

        $('input#fips_match_type')
            .after($('<input>', {
                'type': 'button',
                'value': 'accept as a match',
                'id': 'fips_accept_match'
            })
                .css({'margin-left': '10px'})
                .off('click')
                .on('click', function(){

                    $('select#fips_match_status').val(FIPS.constants.MATCH_STATUS_CLOSED).trigger('propertychange');
                    FIPS.saveEditorRecord(1);
                    //FIPS.closeEditor(0);
                })
            )
        ;
    }

    $tbody.find('input, select, textarea')
        .off('input propertychange paste')
        .on('input propertychange paste', function(){
            if ( !$(this).hasClass('fips-changed') ){
                $(this).addClass('fips-changed');
                FIPS.showEditorSaveButton();
            }
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

    // updates style to mark row being edited, also interim data saves (e.g. from API call)
    FIPS.updateListFromEditor();

    FIPS.hideEditorSaveButton();

    if ( x.fips_history_id ){

        FIPS.showEditorRestoreButton();
    }
    else {

        FIPS.hideEditorRestoreButton();
    }

    let popmsg = '';

    if ( x.fips_match_result ){

        popmsg += x.fips_match_result + "&nbsp;";
    }

    if ( x.fips_match_type ){

        popmsg += x.fips_match_type;
    }

    FIPS.postEditorMessage( popmsg );
}

FIPS.openEditor = function( record ){

    console.log('openEditor', record);

    $('#fips-record').html( record );

    const $tbody = $('tbody#fips-editor-tbody');

    const fields = YES3.moduleProperties.fips_editor_fields;

    const address_field_type = YES3.moduleProperties.settings.address_field_type; // single or multiple

    $tbody
        .empty()
        .append( FIPS.fipsEditorRow('record', 'Study record', 'text', 0, 50, []) )
        .append( FIPS.fipsEditorRow('fips_linkage_id', 'linkage id', 'text', 0, 50, []) )
    ;

    for(let i=0; i<fields.length; i++){

        if ( fields[i].display === FIPS.constants.IF_SINGLE_ADDRESS_FIELD && address_field_type==='multiple' ){

            continue;
        }

        const editable = (
            (fields[i].editable === FIPS.constants.ALWAYS) 
            || ( fields[i].editable === FIPS.constants.IF_SINGLE_ADDRESS_FIELD && address_field_type==='single' )
            || ( fields[i].editable === FIPS.constants.IF_MULTIPLE_ADDRESS_FIELDS && address_field_type==='multiple' )
            ) ? 1 : 0
        ;

        let choices = ( fields[i].choices==undefined ) ? []:fields[i].choices;

        $tbody.append( FIPS.fipsEditorRow(fields[i].field_name, fields[i].label, fields[i].type, editable, fields[i].size, choices) );
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

FIPS.restoreEditorRecord = function(){

    const $tbody = $('tbody#fips-editor-tbody');
    const record = $tbody.find('input#record').val();
    const fips_linkage_id = $tbody.find('input#fips_linkage_id').val();

    FIPS.postEditorMessage("WAIT");

    YES3.ajax(
        'restore-fips-record',
        {
            'fips_linkage_id': fips_linkage_id
        },
        FIPS.restoreEditorRecordConfirmation
    );
}

FIPS.restoreEditorRecordConfirmation = function(response){

    console.log('restoreEditorRecordConfirmation: ', response);

    const $tbodyEd = $('tbody#fips-editor-tbody');

    const record = $tbodyEd.find('input#record').val();

    if ( response === "success" ){

        // post confirmed changes to list, and mark as saved
        FIPS.updateListFromEditor(true);

        // update the summary table
        FIPS.getTheSummary();

        FIPS.loadRecordIntoEditor( record ); // re-load record 
    }
    else {

        FIPS.postEditorMessage("ERROR", true);

        YES3.hello(response);

        // hide the save button, and reset all editor 'changed'
        // flags so that the save button listener will respond
        // to new changes
        FIPS.clearEditorChangedStatus();
    }
}

FIPS.saveEditorRecord = function( close_editor_on_success ){

    close_editor_on_success = close_editor_on_success || 0;

    const $tbody = $('tbody#fips-editor-tbody');
    const record = $tbody.find('input#record').val();
    const fips_linkage_id = $tbody.find('input#fips_linkage_id').val();

    FIPS.postEditorMessage("WAIT");

    x = {};

    $tbody.find('.fips-changed').each(function(){

        x[$(this).prop('id')] = $(this).val();
    });

    console.log('saveEditorRecord', x);

    YES3.ajax(
        'save-fips-record',
        {
            'data': x,
            'record': record,
            'fips_linkage_id': fips_linkage_id,
            'close_editor_on_success': close_editor_on_success

        },
        FIPS.saveEditorRecordConfirmation
    );
}

FIPS.saveEditorRecordConfirmation = function(response){

    console.log('saveEditorRecordConfirmation: ', response);

    const $tbodyEd = $('tbody#fips-editor-tbody');

    const record = $tbodyEd.find('input#record').val();

    if ( response === "success" || response === "success-and-close"){

        // post confirmed changes to list, and mark as saved
        FIPS.updateListFromEditor(true);

        // update the summary table
        FIPS.getTheSummary();

        if ( response === "success-and-close" ) {

            YES3.closePanel('fips-editor');
        }
        else {

            FIPS.loadRecordIntoEditor( record ); // re-load record 
        }
    }
    else {

        FIPS.postEditorMessage("ERROR", true);

        YES3.hello(response);

        // hide the save button, and reset all editor 'changed'
        // flags so that the save button listener will respond
        // to new changes
        FIPS.clearEditorChangedStatus();
    }
}

FIPS.postEditorMessage = function(msg, danger, timeout){

    danger = danger || false;

    let msgClass = ( danger ) ? "yes3-danger":"yes3-warning";

    timeout = timeout || 10000;

    if ( typeof FIPS.messageTimeoutId === 'number' ){

        clearTimeout( FIPS.messageTimeoutId );

        FIPS.messageTimeoutId = undefined;
    }

    $("#fips-editor-message")
        .removeClass("yes3-warning")
        .removeClass("yes3-danger")
        .addClass(msgClass)
        .html( msg )
    ;

    FIPS.messageTimeoutId = setTimeout(function(){

        FIPS.clearEditorMessage();
    }, timeout);
}

FIPS.clearEditorMessage = function(){

    $('#fips-editor-message').html("");
}

FIPS.updateListFromEditor = function(saved){

    saved = saved || false;

    const $tbodyEd = $('tbody#fips-editor-tbody');
    const $tbodyList = $('div#fips-list-container tbody');
    const record = $tbodyEd.find('input#record').val();
    const fips_match_status = $tbodyEd.find('select#fips_match_status').val() || 0;
    const fips_match_result = $tbodyEd.find('input#fips_match_result').val() || '';
    const fips_match_type = $tbodyEd.find('input#fips_match_type').val() || '';
    const $row = $tbodyList.find('tr#fips-list-' + record);

    $row.addClass('fips-changed');

    if ( saved ){

        $row.addClass('fips-saved');
    }

    $row.find('td.fips_match_status_label').html( FIPS.fips_match_status_labels[fips_match_status] );
    $row.find('td.fips_match_result').html( fips_match_result );
    $row.find('td.fips_match_type').html( fips_match_type );
}

FIPS.updateAPIBatch = function(){

    FIPS.postMessage('WAIT', false, true);

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

    FIPS.postMessage('WAIT', false, true);

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

    console.log('callApiFromEditorConfirmation', response);

    const record = $('#fips-editor input#record').val();

    if ( record ){

        FIPS.loadRecordIntoEditor( record );
    }
}

FIPS.callAPI = function(){

    FIPS.postMessage( 'WAIT', false, true );

    YES3.ajax(
        'call-api',
        {},
        FIPS.callAPIConfirmation
    );
}

FIPS.callAPIConfirmation = function( response ){

    console.log('callAPIConfirmation', response);

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

    const copyHeight = $('#fips-copyright').parent().height();

    //const Hf = $('#south').outerHeight();

    //console.log('onResize', Hw, top);

    $listContainer.height( (Hw - top - copyHeight - 80)+'px' );
}

FIPS.postMessage = function( msg, danger, forever ){

    danger = danger || false;

    let msgClass = ( danger ) ? "yes3-danger":"yes3-warning";

    forever = forever || false;

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

    if ( !forever ){

        FIPS.messageTimeoutId = setTimeout(function(){

            FIPS.clearMessage();
        }, 10000);
    }
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
