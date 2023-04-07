<?php 

define('LOG_DEBUG_MESSAGES', '1');

$module = new Yale\Yes3Fips\Yes3Fips();

$module->getCodeFor('fips_o_matic', true);

$copy = $module->getCopyright();

//$module->testDb();

//die("have a nice day");

?>

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

        <div class='col-lg-7'>

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
            <div id='fips-list-container'>
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

        <div class='col-lg-5'>

            <div class="yes3-flex-container-left-aligned">

                <div class="yes3-flex-vcenter-hleft yes3-headroom">

                    <table id='fips-summary-table'>

                        <thead>
                            <tr><th colspan='4' class='h3'>SUMMARY <a href='javaScript:FIPS.getTheSummary()'>refresh</a></td></tr>
                        </thead>

                        <tbody>

                            <tr><td>N</td><td id='summary_n'>0</td><td></td><td></td></tr>
                            <tr><td>Pending</td><td id='summary_pending'>0</td><td id='summary_pending_pct'>0%</td><td></td></tr>
                            <tr><td>Next API Batch</td><td id='summary_apibatch'>0</td><td id='summary_apibatch_pct'>0%</td><td><a href='javaScript:FIPS.clearApiBatch()' title="Reset all unmatched records marked 'next API batch' to 'pending'." >reset</a></td></tr>
                            <tr><td>In Process</td><td id='summary_inprocess'>0</td><td id='summary_inprocess_pct'>0%</td><td></td></tr>
                            <tr><td>PO Box</td><td id='summary_pobox'>0</td><td id='summary_pobox_pct'>0%</td><td></td></tr>
                            <tr><td>Deferred</td><td id='summary_deferred'>0</td><td id='summary_deferred_pct'>0%</td><td></td></tr>
                            <tr><td>Closed</td><td id='summary_closed'>0</td><td id='summary_closed_pct'>0%</td><td></td></tr>
                            <tr><td>Closed: Matched</td><td id='summary_closed_matched'>0</td><td id='summary_closed_matched_pct'>0%</td><td></td></tr>
                            <tr><td>Closed: Unmatched</td><td id='summary_closed_unmatched'>0</td><td id='summary_closed_unmatched_pct'>0%</td><td></td></tr>

                        </tbody>
                    </table>
                </div>

                <!--div class="yes3-flex-vtop-hleft">
                    <input type='button' id='fips-summary-refresh' value='refresh' onclick='FIPS.getTheSummary()' />
                </div-->

            </div>


            <div class="yes3-flex-container-left-aligned yes3-max-headroom">

                <div class='yes3-flex-vcenter'>API batch size</div>

                <input type='text' class='yes3-flex-vcenter' id='fips-api-batch-size' />

            </div>

            <div class="yes3-flex-container-left-aligned yes3-max-headroom">

                <input type='button' value='update API batch' title='Mark records for inclusion in the API batch.' onclick="FIPS.updateAPIBatch()" />

                <input type='button' class='fips-dangerbutton fips-api-batch' value='call API (batch)' title='Process the API batch.' onclick="FIPS.callAPI()" />

                <!--div class="yes3-flex-vcenter-hright">
                    <input type='button' class='fips-api-batch fips-subtlebutton' value='clear API batch' onclick="FIPS.clearApiBatch()" />
                </div-->

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