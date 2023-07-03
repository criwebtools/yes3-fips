<?php 

use \Yale\Yes3Fips\FIPS;

use \Yale\Yes3Fips\Yes3;

$module = new Yale\Yes3Fips\Yes3Fips();

//$module->getCodeFor('fips_o_matic', true);

//$module->initializeDBO();

$copy = $module->getCopyright();

//$module->testDb();

//die("have a nice day");
/*
session_start();

$_SESSION['fips_db_conn'] = null;

if ( FIPS::getProjectSetting('data-source')==="database" ){

    $host = ""; $user = ""; $password = ""; $database = "";

    $specfile = FIPS::getProjectSetting('db-spec-file');

    require $specfile; // connection info, hopefully store off webroot

    try {

        $_SESSION['fips_db_conn'] = new mysqli($host, $user, $password, $database);

    } catch( Exception $e ) {

        Yes3::logDebugMessage(0, $e->getMessage(), 'Yes3Fips:exception');
        throw new Exception("Failed to connect to MySQL (" . $e->getMessage());
    }

    if ($_SESSION['fips_db_conn']->connect_error) {

        Yes3::logDebugMessage(0, $_SESSION['fips_db_conn']->connect_error, 'Yes3Fips:connection error');
        throw new Exception("Failed to connect to MySQL: (" . $_SESSION['fips_db_conn']->connect_errno . ") " . $_SESSION['fips_db_conn']->connect_error);
    }
            
    //Yes3::logDebugMessage(0, print_r($_SESSION['fips_db_conn'], true), 'Yes3Fips:DBCONN1');

}

//print_r($_SESSION);

//die();

*/
?>

<!DOCTYPE html>
<html>
<head>

    <title>The Fabulous FIPS-O-Matic</title>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?= $module->getUrl('favicon.ico')?>">

</head>
<body>

<?php $module->getCodeFor('fips_o_matic', true) ?>

<div class='container' id='yes3-container'>

    <div class='row'>

        <div class='col-lg-6 yes3-container'>

            <div class='h2 yes3-flex-vcenter-hleft'>The Fabulous FIPS-O-Matic</div>

        </div>

        <div class='col-lg-6 yes3-container'>

            <div class="yes3-flex-vcenter-hright" id="fips-action-icons">
            </div>

        </div>

    </div>

    <div class='row'>

        <div class='col-lg-8'>

            <!-- table control bar -->
            <div class="yes3-flex-container-left-aligned yes3-controlbar">

                <div class="yes3-flex-vcenter-hleft">
                    <select id="fips-filter">

                        <!--option value="" disabled selected>-- select a filter --</option-->

                        <optgroup label='pre process'>
                            <option value="pending">pending</option>
                            <option value="nextbatch">selected for next API batch</option>
                        </optgroup>

                        <optgroup label='in process'>
                            <option value="inprocess">all in process records</option>
                            <option value="inprocess-nomatch">in process: not matched</option>
                            <option value="inprocess-fuzzy">in process: fuzzy matched</option>
                            <option value="inprocess-tie">in process: tie</option>
                        </optgroup>

                        <optgroup label='closed'>
                            <option value="closed">all closed records</option>
                            <option value="closed-matched">closed: matched</option>
                            <option value="closed-unmatched">closed: not matched</option>
                        </optgroup>

                        <optgroup label='set aside'>
                            <option value="deferred-pobox">PO box</option>
                            <option value="deferred-later">Deferred</option>
                        </optgroup>

                        <optgroup label='other filters'>
                            <option value="record">a single record</option>
                            <option value="all">all records</option>
                            <option value="unmatched">all unmatched records</option>
                            <option value="matched">all matched records</option>
                        </optgroup>

                    </select>
                </div>

                <div class="yes3-flex-vcenter-hleft">
                    <input type='text' id="fips-record" value="" placeholder="record id" onchange="FIPS.getTheList()"/>
                </div>

                <div class="yes3-flex-vcenter-hleft" id="fips-list-count"></div>

                <div class="yes3-flex-vcenter-hleft">
                    <!--input type='button' id="fips-refresh" value="refresh" onclick="FIPS.getTheList()" /-->
                    <a id="fips-refresh" href='javaScript:FIPS.getTheList()'>refresh</a>
                </div>
            </div>

            <!-- table -->
            <div id='fips-list-container' class='fips-scrolling-content-container'>
                <table class='tableFixHead'>
                    <thead>
                        <th class='fips-record'>record</th>
                        <th class='fips-match-status'>status</th>
                        <th class='fips-match-result fips-disposable'>match</th>
                        <th class='fips-match-type'>type</th>
                        <th class='fips-match-edit'>edit</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>

        <div class='col-lg-4' id="fips-status-board">

            <div class="yes3-flex-container-left-aligned">

                <div class="yes3-flex-vcenter-hleft">

                    <table id='session-information'>

                        <thead>
                            <tr><th colspan='2'>Welcome</td></tr>
                        </thead>

                        <tbody>

                            <tr><td>You are</td><td id='session_user'>foo</td></tr>
                            <tr><td>REDCap project</td><td id='session_project'>bar</td></tr>
                            <tr><td>Session start</td><td id='session_start'>deluxe</td></tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="yes3-flex-container-left-aligned yes3-max-headroom when-reservations">

                <div class="yes3-flex-vcenter-hleft">

                    <table id='fips-reservation-table'>

                        <thead>
                            <tr><th colspan='3'>Your Reservations <a href='javaScript:FIPS.getTheSummaries()'>refresh</a></td></tr>
                        </thead>

                        <tbody>

                            <tr><td>N</td><td id='reservation_n'>0</td><td></td></tr>
                            <tr><td>In Process</td><td id='reservation_inprocess'>0</td><td id='reservation_inprocess_pct'>0%</td></tr>
                            <tr><td>Deferred</td><td id='reservation_deferred'>0</td><td id='reservation_deferred_pct'>0%</td></tr>
                            <tr><td>Closed</td><td id='reservation_closed'>0</td><td id='reservation_closed_pct'>0%</td></tr>
                            <tr><td>Closed: Matched</td><td id='reservation_closed_matched'>0</td><td id='reservation_closed_matched_pct'>0%</td></tr>
                            <tr><td>Closed: Unmatched</td><td id='reservation_closed_unmatched'>0</td><td id='reservation_closed_unmatched_pct'>0%</td></tr>

                        </tbody>
                    </table>
                </div>
            </div>


            <div class="yes3-flex-container-left-aligned  yes3-headroom">

                <input type='button' value='get started: make your reservations' class='when-no-reservations' title='Reserve a block of reservations.' onclick='FIPS.reserveBlock()' />
                <input type='button' value='finish up: cancel your reservations' class='when-reservations fips-warningbutton' title='Cancel your reservations (do this when finished for the day).' onclick='FIPS.releaseBlock()' />
            </div>


            <div class="yes3-flex-container-left-aligned yes3-max-headroom">

                <div class="yes3-flex-vcenter-hleft">

                    <table id='fips-summary-table'>

                        <thead>
                            <tr><th colspan='4'>Project Summary <a href='javaScript:FIPS.getTheSummaries()'>refresh</a></td></tr>
                        </thead>

                        <tbody>

                            <tr><td>N</td><td id='summary_n'>0</td><td></td></tr>
                            <tr><td>Pending</td><td id='summary_pending'>0</td><td id='summary_pending_pct'>0%</td></tr>
                            <tr><td>Next API Batch</td><td id='summary_apibatch'>0</td><td id='summary_apibatch_pct'>0%</td></tr>
                            <tr><td>In Process</td><td id='summary_inprocess'>0</td><td id='summary_inprocess_pct'>0%</td></tr>
                            <tr><td>PO Box</td><td id='summary_pobox'>0</td><td id='summary_pobox_pct'>0%</td></tr>
                            <tr><td>Deferred</td><td id='summary_deferred'>0</td><td id='summary_deferred_pct'>0%</td></tr>
                            <tr><td>Closed</td><td id='summary_closed'>0</td><td id='summary_closed_pct'>0%</td></tr>
                            <tr><td>Closed: Matched</td><td id='summary_closed_matched'>0</td><td id='summary_closed_matched_pct'>0%</td></tr>
                            <tr><td>Closed: Unmatched</td><td id='summary_closed_unmatched'>0</td><td id='summary_closed_unmatched_pct'>0%</td></tr>

                        </tbody>
                    </table>
                </div>

                <!--div class="yes3-flex-vtop-hleft">
                    <input type='button' id='fips-summary-refresh' value='refresh' onclick='FIPS.getTheSummary()' />
                </div-->

            </div>

            <div class="yes3-flex-container-left-aligned yes3-max-headroom fips-when-pending">

                <div class='yes3-flex-vcenter'>API batch size</div>

                <input type='text' class='yes3-flex-vcenter' id='fips-api-batch-size' />

                <input type='button' class='fips-no-api-batch' value='update API batch' title='Mark records for inclusion in the API batch.' onclick="FIPS.updateAPIBatch()" />

                <input type='button' class='fips-dangerbutton fips-api-batch' value='call API (batch)' title='Process the API batch.' onclick="FIPS.callAPIBatch()" />

            </div>

            <div class="yes3-flex-container-left-aligned yes3-max-headroom" id="fips-message"></div>

        </div>


    </div>

    <div class='row yes3-headroom'>

        <div class='col-lg-12 yes3-copyright' id='fips-copyright'>
            <?= $copy ?>
        </div>

    </div>

</div>

</body>
</html>