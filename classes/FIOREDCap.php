<?php

namespace Yale\Yes3Fips;

use Exception;
use REDCap;
use ExternalModules\ExternalModules;
use Yale\Yes3Fips\Yes3;
use Yale\Yes3Fips\FIPS;

class FIOREDCap implements \Yale\Yes3Fips\FIO {

    public function makeCsvForApiCall(string $record): string {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $params = [ $project_id, $event_id ];

        $extraWhere = "";

        if ( $record ){

            $extraWhere = " AND k.`record`=?";
            $params[] = $record;
        }
        else {

            $extraWhere = " AND ms.`value`=?";
            $params[] = self::MATCH_STATUS_NEXT_API_BATCH;
        }

        $sql = "SELECT k.`value` AS `fips_linkage_id`
            , a2.`value` AS `fips_address_street`
            , a3.`value` AS `fips_address_city`
            , a4.`value` AS `fips_address_state`
            , a5.`value` AS `fips_address_zip`
        FROM redcap_data k
            LEFT JOIN redcap_data ms ON ms.project_id=k.project_id AND ms.event_id=k.event_id AND ms.`record`=k.`record` AND ms.field_name='fips_match_status'
            LEFT JOIN redcap_data a2 ON a2.project_id=k.project_id AND a2.event_id=k.event_id AND a2.`record`=k.`record` AND a2.field_name='fips_address_street'
            LEFT JOIN redcap_data a3 ON a3.project_id=k.project_id AND a3.event_id=k.event_id AND a3.`record`=k.`record` AND a3.field_name='fips_address_city'
            LEFT JOIN redcap_data a4 ON a4.project_id=k.project_id AND a4.event_id=k.event_id AND a4.`record`=k.`record` AND a4.field_name='fips_address_state'
            LEFT JOIN redcap_data a5 ON a5.project_id=k.project_id AND a5.event_id=k.event_id AND a5.`record`=k.`record` AND a5.field_name='fips_address_zip'
        WHERE k.project_id=? AND k.event_id=? AND k.field_name='fips_linkage_id' {$extraWhere}
        ORDER BY 0+k.`value`";

        if ( $record ) {

            $sql .= " LIMIT 1";
        }

        $diag = print_r(
            [
                'sql' => $sql,
                'params' => $params,
                'const' => self::MATCH_STATUS_IN_PROCESS,
                'record' => $record
            ]
            , true
            );

        $yy = Yes3::fetchRecords($sql, $params);

        $temp_file_name = tempnam(sys_get_temp_dir(), 'fips') . '.csv';

        //return $temp_file_name;

        $fp = fopen( $temp_file_name, 'w' );

        for($i=0; $i<count($yy); $i++){

            $yy[$i]['fips_address_street'] = Yes3::inoffensiveText($yy[$i]['fips_address_street'], 8096, true);

            fputcsv($fp, $yy[$i]);
        }

        fclose($fp);

        //return print_r($yy, true);
        //return file_get_contents( $temp_file_name );
        return $temp_file_name;
        //return $diag;
    }

    public function saveGeoData(array $geoData): string {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $records = [];

        $n = 0;
        $nMatchedExact = 0;
        $nMatchedNonExact = 0;
        $nUnmatched = 0;

        foreach($geoData as $geoRecord){

            if ( $geoRecord['fips_linkage_id'] ){

                $record = $this->getRecordFromLinkageId( $project_id, $event_id, $geoRecord['fips_linkage_id']);

                $n++;

                if ($geoRecord['fips_match_type']==='Exact') $nMatchedExact++;

                elseif ($geoRecord['fips_match_type']==='Non_Exact') $nMatchedNonExact++;

                else $nUnmatched++;

                $geoRecord['fips_complete'] = ($geoRecord['fips_match_status'] === FIO::MATCH_STATUS_CLOSED) ? FIO::FORM_COMPLETE : FIO::FORM_INCOMPLETE;

                $records[$record][$event_id] = $geoRecord;
            }
        }

        $rc = REDCap::saveData(
            $project_id,
            'array',
            $records,
            'overwrite'
        );

        if ( $rc['errors'] ){

            return "REDCap reports the following error(s): " . implode(";", $rc['errors'] );
        }
        else {
            return "API call succeeded.<br>{$n} record(s) processed.<br>{$nMatchedExact} exact match(es).<br>{$nMatchedNonExact} fuzzy match(es).<br>{$nUnmatched} not matched.";
            //return print_r($records, true);
            //return print_r($geoData, true);
            //return print_r($rc, true);
        }
    }

    public function getFIPSrecords(string $filter, string $record, int $limit=5000): array {
/*
        $filter = $data['filter'];

        $record = $data['record'];
*/
        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        if ( $record ){

            $limit = 1;
        }

        $sql = "
SELECT d.`record`, d.`value` AS `fips_address_timestamp`
	, IFNULL(f1.`value`, '') AS fips_match_status
	, IFNULL(f2.`value`, '') AS fips_match_result
 	, IFNULL(f3.`value`, '') AS fips_code
	, IFNULL(f4.`value`, '') AS fips_state
	, IFNULL(f5.`value`, '') AS fips_county
	, IFNULL(f6.`value`, '') AS fips_tract
	, IFNULL(f7.`value`, '') AS fips_block
	, IFNULL(a1.`value`, '') AS fips_address
	, IFNULL(a2.`value`, '') AS fips_address_street
	, IFNULL(a3.`value`, '') AS fips_address_city
	, IFNULL(a4.`value`, '') AS fips_address_state
	, IFNULL(a5.`value`, '') AS fips_address_zip
    , IFNULL(a6.`value`, '') AS fips_address_submitted
    , IFNULL(a7.`value`, '') AS fips_address_matched
    , IFNULL(a8.`value`, '') AS fips_match_type
    , IFNULL(a9.`value`, '') AS fips_longitude
    , IFNULL(aa.`value`, '') AS fips_latitude
    , IFNULL(ab.`value`, '') AS fips_tigerlineid
    , IFNULL(ac.`value`, '') AS fips_tigerlineside
    , IFNULL(ad.`value`, '') AS fips_census_block_group

FROM redcap_data d

    LEFT JOIN redcap_data f1 ON f1.project_id=d.project_id AND f1.event_id=d.event_id AND f1.`record`=d.`record` AND f1.field_name='fips_match_status'
    LEFT JOIN redcap_data f2 ON f2.project_id=d.project_id AND f2.event_id=d.event_id AND f2.`record`=d.`record` AND f2.field_name='fips_match_result'
    LEFT JOIN redcap_data f3 ON f3.project_id=d.project_id AND f3.event_id=d.event_id AND f3.`record`=d.`record` AND f3.field_name='fips_code'
    LEFT JOIN redcap_data f4 ON f4.project_id=d.project_id AND f4.event_id=d.event_id AND f4.`record`=d.`record` AND f4.field_name='fips_state'
    LEFT JOIN redcap_data f5 ON f5.project_id=d.project_id AND f5.event_id=d.event_id AND f5.`record`=d.`record` AND f5.field_name='fips_county'
    LEFT JOIN redcap_data f6 ON f6.project_id=d.project_id AND f6.event_id=d.event_id AND f6.`record`=d.`record` AND f6.field_name='fips_tract'
    LEFT JOIN redcap_data f7 ON f7.project_id=d.project_id AND f7.event_id=d.event_id AND f7.`record`=d.`record` AND f7.field_name='fips_block'
    LEFT JOIN redcap_data a1 ON a1.project_id=d.project_id AND a1.event_id=d.event_id AND a1.`record`=d.`record` AND a1.field_name='fips_address'
    LEFT JOIN redcap_data a2 ON a2.project_id=d.project_id AND a2.event_id=d.event_id AND a2.`record`=d.`record` AND a2.field_name='fips_address_street'
    LEFT JOIN redcap_data a3 ON a3.project_id=d.project_id AND a3.event_id=d.event_id AND a3.`record`=d.`record` AND a3.field_name='fips_address_city'
    LEFT JOIN redcap_data a4 ON a4.project_id=d.project_id AND a4.event_id=d.event_id AND a4.`record`=d.`record` AND a4.field_name='fips_address_state'
    LEFT JOIN redcap_data a5 ON a5.project_id=d.project_id AND a5.event_id=d.event_id AND a5.`record`=d.`record` AND a5.field_name='fips_address_zip'

    LEFT JOIN redcap_data a6 ON a6.project_id=d.project_id AND a6.event_id=d.event_id AND a6.`record`=d.`record` AND a6.field_name='fips_address_submitted'
    LEFT JOIN redcap_data a7 ON a7.project_id=d.project_id AND a7.event_id=d.event_id AND a7.`record`=d.`record` AND a7.field_name='fips_address_matched'
    LEFT JOIN redcap_data a8 ON a8.project_id=d.project_id AND a8.event_id=d.event_id AND a8.`record`=d.`record` AND a8.field_name='fips_match_type'
    LEFT JOIN redcap_data a9 ON a9.project_id=d.project_id AND a9.event_id=d.event_id AND a9.`record`=d.`record` AND a9.field_name='fips_longitude'
    LEFT JOIN redcap_data aa ON aa.project_id=d.project_id AND aa.event_id=d.event_id AND aa.`record`=d.`record` AND aa.field_name='fips_latitude'
    LEFT JOIN redcap_data ab ON ab.project_id=d.project_id AND ab.event_id=d.event_id AND ab.`record`=d.`record` AND ab.field_name='fips_tigerlineid'
    LEFT JOIN redcap_data ac ON ac.project_id=d.project_id AND ac.event_id=d.event_id AND ac.`record`=d.`record` AND ac.field_name='fips_tigerlineside'
    LEFT JOIN redcap_data ad ON ad.project_id=d.project_id AND ad.event_id=d.event_id AND ad.`record`=d.`record` AND ad.field_name='fips_census_block_group'

WHERE d.project_id=? AND d.field_name='fips_address_timestamp' AND d.`event_id`=? AND d.`value` IS NOT NULL
        ";

        $params = [
            $project_id, $event_id
        ];

        if ( $filter==="pending"){

            $sql .= " AND IFNULL(f1.`value`, '0')='0'";

            //return $sql."\n\n".print_r($params, true);
        }
        else if ( $filter==="nextbatch"){

            $sql .= " AND f1.`value`=?";
            $params[] = FIO::MATCH_STATUS_NEXT_API_BATCH;
        }
        else if ( $filter==="inprocess"){

            $sql .= " AND f1.`value`=?";
            $params[] = FIO::MATCH_STATUS_IN_PROCESS;
        }
        else if ( $filter==="inprocess-nomatch"){

            $sql .= " AND f1.`value`=? AND f2.`value`=?";
            $params[] = FIO::MATCH_STATUS_IN_PROCESS;
            $params[] = FIO::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="inprocess-fuzzy"){

            $sql .= " AND f1.`value`=? AND a8.`value`=?";
            $params[] = FIO::MATCH_STATUS_IN_PROCESS;
            $params[] = FIO::MATCH_TYPE_FUZZY;
        }
        else if ( $filter==="inprocess-tie"){

            $sql .= " AND f1.`value`=? AND f2.`value`=?";
            $params[] = FIO::MATCH_STATUS_IN_PROCESS;
            $params[] = FIO::MATCH_RESULT_TIE;
        }
        else if ( $filter==="closed"){

            $sql .= " AND f1.`value`=?";
            $params[] = FIO::MATCH_STATUS_CLOSED;
        }
        else if ( $filter==="closed-matched"){

            $sql .= " AND f1.`value`=? AND f2.`value`=?";
            $params[] = FIO::MATCH_STATUS_CLOSED;
            $params[] = FIO::MATCH_RESULT_MATCHED;
        }
        else if ( $filter==="closed-unmatched"){

            $sql .= " AND f1.`value`=? AND f2.`value`=?";
            $params[] = FIO::MATCH_STATUS_CLOSED;
            $params[] = FIO::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="record"){

            if ( !$record ) {

                return [];
            }

            $sql .= " AND d.`record`=?";

            $params[] = $record;
        }
        else if ( $filter==="all") {

        }
        else if ( $filter==="unmatched"){

            $sql .= " AND f2.`value`=?";
            $params[] = FIO::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="matched"){

            $sql .= " AND f2.`value`=?";
            $params() = FIO::MATCH_RESULT_MATCHED; 
        }
        else {

            return [];
        }

        $sql .= " LIMIT ?";

        $params[] = $limit;

        $xx =  Yes3::fetchRecords( $sql, $params );

        // perform a 'natural sort' on the result
        usort( $xx, function($a, $b){ return strnatcmp($a['record'], $b['record']); });

        return $xx;
    }

    public function assignLinkageIDs(): int {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $sql = "SELECT DISTINCT `record` FROM redcap_data WHERE project_id=? AND event_id=? AND `record` NOT IN(
            SELECT `record` FROM redcap_data WHERE project_id=? AND event_id=? AND field_name='fips_linkage_id' AND `value` > 0
        )";
        $params = [ $project_id, $event_id, $project_id, $event_id];

        $n = 0;
        $data = [];
        $u = "";

        foreach(Yes3::recordGenerator($sql, $params) as $x){

            $data[$x['record']][$event_id]['fips_linkage_id'] = $this->genLinkageID($project_id, $event_id);

            $n++;
            $u = 0;
            if ( $n >= 100 ){

                $u += REDCap::saveData($project_id, 'array', $data)['item_count'];

                $data = [];
                $n = 0;
            }
        }

        if ( $data ){

            $u += REDCap::saveData($project_id, 'array', $data)['item_count'];
        }

        return $u;
    }

    public function getStudyIdFromLinkageId($linkageId): string {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $sql = "
        SELECT d.`record` 
        FROM redcap_data d
        WHERE d.project_id=? AND d.field_name='fips_linkage_id' AND d.`event_id`=? AND d.`value`=?
        LIMIT 1
        ";

        $params = [$project_id, $event_id, $linkageId];

        return Yes3::fetchValue($sql, $params);
    }

    public function getLinkageIdFromStudyId($studyId): string {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $sql = "
        SELECT d.`value` 
        FROM redcap_data d
        WHERE d.project_id=? AND d.field_name='fips_linkage_id' AND d.`event_id`=? AND d.`record`=?
        LIMIT 1
        ";

        $params = [$project_id, $event_id, $studyId];

        return Yes3::fetchValue($sql, $params);
    }

    public function saveFIPSrecord(string $record, int $fips_linkage_id, array $x, int $close_editor_on_success, string $username): string {

        $project_id = FIPS::getProjectID();
        $event_id = FIPS::getProjectSetting('fips-event');

        // if the address is in the save set, it must be parseable
        if ( isset($x['fips_address']) && FIPS::getProjectSetting('address-field-type')==="single" ){

            $parsed = FIPS::singleAddressFieldParser(
                    $x['fips_address'], 
                    $x['fips_address_street'],
                    $x['fips_address_city'],
                    $x['fips_address_state'],
                    $x['fips_address_zip']
            );

            if ( !$parsed ) {

                return "Error: Could not parse the address. Make sure that the street, city, state and zip are all present in the address field, and check for typos in the state name or abbreviation." ;
            }
        }

        $x['fips_save_user'] = $username;
        $x['fips_save_timestamp'] = Yes3::isoTimeStampString();

        $x['fips_complete'] = ( isset($x['fips_match_status']) && $x['fips_match_status'] === FIO::MATCH_STATUS_CLOSED ) ? '2':'1';

        $saveArray = [
            $record => [
                $event_id => $x
            ]
        ];

        $rc = REDCap::saveData(

            $project_id,
            'array',
            $saveArray,
            'overwrite'
        );

        if ( $rc['errors'] ){

            return "REDCap reports the following error(s): " . implode(";", $rc['errors'] );
        }
        else {

            return ($close_editor_on_success) ? "success-and-close":"success";
        }
    }

    public function archiveFIPSrecord( int $fips_linkage_id ): int {

        $project_id = FIPS::getProjectID();
        $event_id = FIPS::getProjectSetting('fips-event');
        $id_field_name = REDCap::getRecordIdField();

        $sql = "
        select d.*
        from redcap_data d
          inner join redcap_data k on d.project_id=k.project_id and d.event_id=k.event_id and d.record=k.record
          inner join redcap_metadata m on m.project_id=d.project_id and m.form_name='fips' and d.field_name=m.field_name
        where k.project_id=? and k.event_id=? and k.field_name=? and k.value=?
        order by m.field_order
        ";

        $params = [
            $project_id,
            $event_id,
            'fips_linkage_id',
            $fips_linkage_id
        ];

        $y = Yes3::fetchRecord($sql, $params);

        if ( $fips_archive_record = Yes3::json_encode_pretty( $y ) ){

            
        }

        

        return 0;
    }
    
    public function restoreFIPSrecord(int $fips_linkage_id, string $username): string {

        return "";
    }

    public function updateAPIbatch(): string {

        $project_id = FIPS::getProjectID();

        $event_id = FIPS::getProjectSetting('fips-event');

        $batchSize = FIPS::getProjectSetting('api-batch-size');

        $current = Yes3::fetchValue("SELECT count(*) FROM redcap_data WHERE project_id=? AND `event_id`=? AND field_name='fips_match_status' AND `value`=?", 
            [$project_id, $event_id, FIO::MATCH_STATUS_NEXT_API_BATCH]);

        $remaining = $batchSize - $current;

        if ( $remaining <= 0 ){

            return "The batch quota is full. You may still manually assign records to the next batch.";
        }

        $sql = "
        SELECT d.`record` 
        FROM redcap_data d
            LEFT JOIN redcap_data f1 ON f1.project_id=d.project_id AND f1.event_id=d.event_id AND f1.`record`=d.`record`AND f1.field_name='fips_match_status'
        WHERE d.project_id=? AND d.field_name='fips_address_timestamp' AND d.`event_id`=? AND d.`value` IS NOT NULL
        AND IFNULL(f1.`value`, 0) < ?
        ORDER BY `record`
        LIMIT ?
        ";

        $yy = Yes3::fetchRecords($sql, [$project_id, $event_id,   FIO::MATCH_STATUS_NEXT_API_BATCH, $remaining]);

        $updates = [];

        foreach( $yy as $y ){

            $updates[$y['record']][$event_id]['fips_match_status'] = FIO::MATCH_STATUS_NEXT_API_BATCH;
        }

        $rc = REDCap::saveData(
            $project_id,
            'array',
            $updates
        );

        //return "batch size: {$batchSize}, current: {$current}, remaining: {$remaining}.";
        return $rc['item_count'] . " record(s) marked for inclusion in the next API batch.";
        //return print_r($rc, true);
    }

    private function getRecordFromLinkageId( $project_id, $event_id, $fips_linkage_id) {

        $sql = "
        SELECT d.`record` 
        FROM redcap_data d
        WHERE d.project_id=? AND d.field_name='fips_linkage_id' AND d.`event_id`=? AND d.`value`=?
        LIMIT 1
        ";

        $params = [$project_id, $event_id, $fips_linkage_id];

        return Yes3::fetchValue($sql, $params);
    }

    public function genLinkageID( $project_id, $event_id ): int {

        $linkage_id = 0;
        $n = 0;
        while ( $linkage_id===0 && $n<1000 ){

            $n++;

            $linkage_id = mt_rand(1, 1000000);

            $sql = "SELECT `record` FROM redcap_data WHERE `project_id`=? AND `event_id`=? AND `field_name`='fips_linkage_id' AND `value`=? LIMIT 1";
            $params = [ $project_id, $event_id, $linkage_id ];

            if ( Yes3::fetchValue($sql, $params) ){

                $linkage_id = 0;
            }
        }

        return $linkage_id;
    }

    function importAddressesToFipsForm() {

        $selection_field    = FIPS::getProjectSetting('selection-field-name');
        $address_field      = FIPS::getProjectSetting('address-field-name');
        $street_field       = FIPS::getProjectSetting('street-field-name');
        $city_field         = FIPS::getProjectSetting('city-field-name');
        $state_field        = FIPS::getProjectSetting('state-field-name');
        $zip_field          = FIPS::getProjectSetting('zip-field-name');

        $fips_event         = FIPS::getProjectSetting('fips-event');
        $project_id         = FIPS::getProjectId();

        $import_timestamp = Yes3::isoTimeStampString();

        $sql = "
        SELECT a.`record`, a.`value` AS `fips_address`, k.`value` AS `fips_linkage_id`
        FROM redcap_data a 
        INNER JOIN redcap_data s ON s.project_id=a.project_id AND s.`record`=a.`record` AND s.field_name=?
        LEFT JOIN redcap_data f ON f.project_id=a.project_id AND f.`record`=a.`record` AND f.field_name=?
        LEFT JOIN redcap_data k ON k.project_id=a.project_id AND k.`record`=a.`record` AND k.field_name=?
        WHERE a.project_id=?
          AND a.field_name=?
          AND s.`value`='1'
          AND IFNULL(f.`value`, 0) < ?
        ORDER BY a.`record`
        ";

        $params = [
            $selection_field, 
            'fips_match_status',
            'fips_linkage_id',
            FIPS::getProjectId(), 
            $address_field,
            FIO::MATCH_STATUS_IN_PROCESS
        ];

        $N = 0;
        $Nu = 0;

        foreach(Yes3::recordGenerator($sql, $params) as $x){
      
            $street = "";
            $state = "";
            $city = "";
            $zip = "";

            $record = $x['record'];

            $fips_linkage_id = $x['fips_linkage_id'];

            if ( !$fips_linkage_id ){

                $fips_linkage_id = $this->genLinkageID($project_id, $fips_event);
            }

            FIPS::singleAddressFieldParser($x['fips_address'], $street, $city, $state, $zip);

            $addressData = [

                'fips_linkage_id' => $fips_linkage_id,
                'fips_address' => $x['fips_address'],
                'fips_address_street' => $street,
                'fips_address_city' => $city,
                'fips_address_state' => $state,
                'fips_address_zip' => $zip
            ];

            $y = [
                $record => [

                    $fips_event => $addressData
                ]
            ];

            $rc = REDCap::saveData(
                $project_id,
                'array',
                $y
            );

            $N++;

            $z = [];
            
            if ( $rc['item_count'] ) {

                $Nu++;

                $z = [
                    $record => [
    
                        $fips_event => [
                            'fips_address_timestamp' => $import_timestamp,
                            'fips_address_updated' => '1'
                        ]
                    ]
                ];
            } else {

                $z = [
                    $record => [
    
                        $fips_event => [
                            'fips_address_updated' => '0'
                        ]
                    ]
                ];
            } 

            $rc_ts = REDCap::saveData(
                $project_id,
                'array',
                $z
            ); 
        }

        return "<p>--- FIPS ADDRESS UPDATER ---<p>"
        . "<br>{$N} addresses processed."
        . "<br>{$Nu} FIPS address record(s) updated."
        ;
    }

    public function getSummary(): array {

        $event_id = FIPS::getProjectSetting('fips-event');

        $project_id = FIPS::getProjectId();

        $sql = "
        SELECT COUNT(*) as `summary_n`,
            SUM(IF(IFNULL(s.`value`, ?)=?, 1, 0)) AS `summary_pending`,
            SUM(IF(IFNULL(s.`value`, 0)=?, 1, 0)) AS `summary_apibatch`,
            SUM(IF(IFNULL(s.`value`, 0)=?, 1, 0)) AS `summary_inprocess`,
            SUM(IF(IFNULL(s.`value`, 0)=?, 1, 0)) AS `summary_closed`,           
            SUM(IF(IFNULL(s.`value`, 0)=? AND IFNULL(m.`value`, '')='Match', 1, 0)) AS `summary_closed_matched`,
            SUM(IF(IFNULL(s.`value`, 0)=? AND IFNULL(m.`value`, '')<>'Match', 1, 0)) AS `summary_closed_unmatched`           
        FROM redcap_data d
            LEFT JOIN redcap_data s ON s.project_id=d.project_id AND s.event_id=d.event_id AND s.record=d.record AND s.field_name='fips_match_status'
            LEFT JOIN redcap_data m ON m.project_id=d.project_id AND m.event_id=d.event_id AND m.record=d.record AND m.field_name='fips_match_result'
        WHERE d.project_id=? AND d.field_name='fips_address_timestamp' AND d.`event_id`=? AND d.`value` IS NOT NULL        
        ";
    
        $params = [ 
            FIO::MATCH_STATUS_PENDING,
            FIO::MATCH_STATUS_PENDING,
            FIO::MATCH_STATUS_NEXT_API_BATCH,
            FIO::MATCH_STATUS_IN_PROCESS,
            FIO::MATCH_STATUS_CLOSED,
            FIO::MATCH_STATUS_CLOSED,
            FIO::MATCH_STATUS_CLOSED,
            $project_id,
            $event_id
        ];

        return Yes3::fetchRecord($sql, $params);  
    }
}