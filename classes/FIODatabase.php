<?php

namespace Yale\Yes3Fips;

use Exception;
use mysqli;
use Yale\Yes3Fips\Yes3;
use Yale\Yes3Fips\FIPS;
use Yale\Yes3Fips\FIODbConnection;

//FIODbConnection::initializeConnection();

class FIODatabase implements \Yale\Yes3Fips\FIO {

    public function getAddressForApiCall(string $record): array {

        $sql = "
        SELECT 
              fom_addresses.`fips_linkage_id`
            , fom_crosswalk.`record`
            , fom_addresses.`fips_address`
            , fom_addresses.`fips_address_street`
            , fom_addresses.`fips_address_city`
            , fom_addresses.`fips_address_state`
            , fom_addresses.`fips_address_zip`
        FROM fom_addresses
            INNER JOIN fom_crosswalk ON fom_addresses.fips_linkage_id=fom_crosswalk.fips_linkage_id
        WHERE fom_crosswalk.`record`=?
        ";

        $params = [ $record ];

        return self::dbFetchRecord($sql, $params);
    }

    public function getLocationForApiCall(string $record): array {

        $sql = "
        SELECT 
              fom_addresses.`fips_linkage_id`
            , fom_crosswalk.`record`
            , fom_addresses.`fips_longitude`
            , fom_addresses.`fips_latitude`
        FROM fom_addresses
            INNER JOIN fom_crosswalk ON fom_addresses.fips_linkage_id=fom_crosswalk.fips_linkage_id
        WHERE fom_crosswalk.`record`=?
        ";

        $params = [ $record ];

        return self::dbFetchRecord($sql, $params);
    }

    public function makeCsvForApiCall(): string {

        $sql = "
        SELECT 
              fom_addresses.`fips_linkage_id`
            , fom_addresses.`fips_address_street`
            , fom_addresses.`fips_address_city`
            , fom_addresses.`fips_address_state`
            , fom_addresses.`fips_address_zip`
        FROM fom_addresses
            INNER JOIN fom_crosswalk ON fom_addresses.fips_linkage_id=fom_crosswalk.fips_linkage_id
        WHERE `fips_match_status`=?
        ";

        $params = [ self::MATCH_STATUS_NEXT_API_BATCH ];
/*
        $diag = print_r(
            [
                'sql' => $sql,
                'params' => $params,
                'const' => self::MATCH_STATUS_IN_PROCESS
            ]
            , true
            );

        return $diag;
*/
        $yy = self::dbFetchRecords($sql, $params);

        $temp_file_name = tempnam(sys_get_temp_dir(), 'fips') . '.csv';

        //return $temp_file_name;

        $fp = fopen( $temp_file_name, 'w' );

        for($i=0; $i<count($yy); $i++){

            $yy[$i]['fips_address_street'] = Yes3::inoffensiveText($yy[$i]['fips_address_street'], 8096, true);
            $yy[$i]['fips_address_city'] = Yes3::inoffensiveText($yy[$i]['fips_address_city'], 8096, true);
            $yy[$i]['fips_address_state'] = Yes3::inoffensiveText($yy[$i]['fips_address_state'], 8096, true);
            $yy[$i]['fips_address_zip'] = Yes3::inoffensiveText($yy[$i]['fips_address_zip'], 8096, true);

            fputcsv($fp, $yy[$i]);
        }

        fclose($fp);

        //Yes3::logDebugMessage(0, file_get_contents( $temp_file_name ), "makeCsvForApiCall:file" );

        //return print_r($yy, true);
        //return file_get_contents( $temp_file_name );
        return $temp_file_name;
        //return $diag;
    }

    public function saveGeoData(array $geoData): string {

        //return print_r($geoData, true);

        $n = 0;
        $nMatchedExact = 0;
        $nMatchedNonExact = 0;
        $nUnmatched = 0;

        foreach($geoData as $geoDataRecord){

            if ( $geoDataRecord['fips_linkage_id'] ){

                $n++;

                if ($geoDataRecord['fips_match_type']===FIO::MATCH_TYPE_EXACT) $nMatchedExact++;

                elseif ($geoDataRecord['fips_match_type']===FIO::MATCH_TYPE_FUZZY) $nMatchedNonExact++;

                else $nUnmatched++;

                $sql = "UPDATE fom_addresses SET ";

                $params = [];
                $nP = 0;
        
                foreach($geoDataRecord as $colname=>$value){

                    if ( $colname !== 'fips_linkage_id' ){
            
                        if ( $nP ) {
            
                            $sql .= ", ";
                        }
            
                        $params[] = $value;
            
                        $sql .= $colname . " = ?";
            
                        $nP++;
                    }
                }

                $sql .= " WHERE fips_linkage_id = ? LIMIT 1";

                $params[] =  $geoDataRecord['fips_linkage_id'];

                $rc = self::dbQuery($sql, $params, self::QRY_RETURN_RETCODE);

                if ( $rc !== "success" ){

                    return print_r([
                        'message'=>'QUERY ERROR',
                        'error'=>$rc,
                        'sql'=>$sql,
                        'params'=>$params,
                        'geoRecord' => $geoDataRecord
                    ], true);

                    return $rc;
                }
            }
        }
       
        return "API call succeeded.<br>{$n} record(s) processed.<br>{$nMatchedExact} exact match(es).<br>{$nMatchedNonExact} fuzzy match(es).<br>{$nUnmatched} not matched.";
    }

    public function getFIPSrecords(string $filter, string $record, int $limit=5000): array {

        if ( $record ){

            $limit = 1;
        }

        $params = [];

        $sql = "SELECT fom_addresses.*, fom_crosswalk.record
        FROM fom_addresses
          INNER JOIN fom_crosswalk ON fom_addresses.fips_linkage_id=fom_crosswalk.fips_linkage_id";

        if ( $filter==="pending"){

            $sql .= " WHERE IFNULL(`fips_match_status`, '0')='0'";

            //return $sql."\n\n".print_r($params, true);
        }
        else if ( $filter==="nextbatch"){

            $sql .= " WHERE `fips_match_status`=?";
            $params[] = self::MATCH_STATUS_NEXT_API_BATCH;
        }
        else if ( $filter==="inprocess"){

            $sql .= " WHERE `fips_match_status`=?";
            $params[] = self::MATCH_STATUS_IN_PROCESS;
        }
        else if ( $filter==="deferred-pobox"){

            $sql .= " WHERE `fips_match_status`=?";
            $params[] = self::MATCH_STATUS_PO_BOX;
        }
        else if ( $filter==="deferred-later"){

            $sql .= " WHERE `fips_match_status`=?";
            $params[] = self::MATCH_STATUS_DEFERRED;
        }
        else if ( $filter==="inprocess-nomatch"){

            $sql .= " WHERE `fips_match_status`=? AND `fips_match_result`=?";
            $params[] = self::MATCH_STATUS_IN_PROCESS;
            $params[] = self::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="inprocess-fuzzy"){

            $sql .= " WHERE `fips_match_status`=? AND `fips_match_type`=?";
            $params[] = self::MATCH_STATUS_IN_PROCESS;
            $params[] = self::MATCH_TYPE_FUZZY;
        }
        else if ( $filter==="inprocess-tie"){

            $sql .= " WHERE `fips_match_status`=? AND `fips_match_result`=?";
            $params[] = self::MATCH_STATUS_IN_PROCESS;
            $params[] = self::MATCH_RESULT_TIE;
        }
        else if ( $filter==="closed"){

            $sql .= " WHERE `fips_match_status`=?";
            $params[] = self::MATCH_STATUS_CLOSED;
        }
        else if ( $filter==="closed-matched"){

            $sql .= " WHERE `fips_match_status`=? AND `fips_match_result`=?";
            $params[] = self::MATCH_STATUS_CLOSED;
            $params[] = self::MATCH_RESULT_MATCHED;
        }
        else if ( $filter==="closed-unmatched"){

            $sql .= " WHERE `fips_match_status`=? AND `fips_match_result`=?";
            $params[] = self::MATCH_STATUS_CLOSED;
            $params[] = self::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="record"){

            if ( !$record ) {

                return [];
            }

            $sql .= " WHERE `record`=?";

            $params[] = $record;
        }
        else if ( $filter==="all") {

        }
        else if ( $filter==="unmatched"){

            $sql .= " WHERE `fips_match_result`=?";
            $params[] = self::MATCH_RESULT_UNMATCHED;
        }
        else if ( $filter==="matched"){

            $sql .= " WHERE `fips_match_result`=?";
            $params[] = self::MATCH_RESULT_MATCHED;
        }
        else {

            return [];
        }

        Yes3::logDebugMessage(0, $sql, "getFIPSrecords:sql");
        Yes3::logDebugMessage(0, print_r($params, true), "getFIPSrecords:params");

        //return $sql;

        $sql .= " LIMIT ?";

        $params[] = $limit;

        $xx =  self::dbFetchRecords( $sql, $params );

        if ( !is_array($xx) ){

            return [];
        }

        // perform a 'natural sort' on the result
        if ( count($xx)>1 ){

            usort( $xx, function($a, $b){ return strnatcmp($a['record'], $b['record']); });
        }

        return $xx;
    }

    public function assignLinkageIDs(): int {

        return 0;
    }

    public function getStudyIdFromLinkageId($linkageId): string {

        return "";
    }

    public function getLinkageIdFromStudyId($studyId): string {

        return "";
    }

    public static function getFipsComment( $fips_linkage_id ){

        $fips_comment = self::dbFetchValue(
            "SELECT fips_comment FROM fom_addresses WHERE fips_linkage_id=?",
            [$fips_linkage_id]
        );
        
        if ( !$fips_comment ){

            $fips_comment = "";
        }

        return $fips_comment;
    }

    public function saveFIPSrecord(string $record, int $fips_linkage_id, array $x, int $close_editor_on_success, string $username): string {

        // archive on first save

        $rc = $this->archiveFIPSrecord($fips_linkage_id);

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

        $x['fips_comment'] = self::logAction(self::getFipsComment($fips_linkage_id), $username, "edited and saved");

        $x['fips_save_user'] = $username;
        $x['fips_save_timestamp'] = Yes3::isoTimeStampString();

        //$data['fips_complete'] = ( isset($data['fips_match_status']) && $data['fips_match_status'] === FIO::MATCH_STATUS_CLOSED ) ? '2':'1';

        $sql = "UPDATE fom_addresses SET ";

        $params = [];
        $nP = 0;
        foreach($x as $colname=>$value){

            if ( $nP ) {

                $sql .= ", ";
            }

            $params[] = $value;

            $sql .= $colname . " = ?";

            $nP++;
        }

        $sql .= " WHERE fips_linkage_id = ? LIMIT 1";

        $params[] = $fips_linkage_id;

        $return = print_r([
            "sql"=>$sql,
            "params"=>$params
        ], true);

        $rc = self::dbQuery($sql, $params, self::QRY_RETURN_RETCODE);

        if ( $rc !== "success" ){

            return $rc;
        }
        else {

            return ($close_editor_on_success) ? "success-and-close":"success";
        }
    }

    public function archiveFIPSrecord( int $fips_linkage_id ): int {

        // first see if it's already been archived

        if ($fom_archive_timestamp = self::dbFetchValue("SELECT fom_archive_timestamp FROM fom_addresses WHERE fips_linkage_id=?", [$fips_linkage_id])){

            return $fom_archive_timestamp;
        }

        $fom_archive_timestamp = Yes3::isoTimeStampString();

        $x = self::dbFetchRecord("SELECT * FROM fom_addresses WHERE fips_linkage_id=?", [$fips_linkage_id]);

        $params = [ $fom_archive_timestamp, json_encode($x, JSON_PRETTY_PRINT), $fips_linkage_id ];

        $sql = "UPDATE fom_addresses SET fom_archive_timestamp=?, fom_archive=? WHERE fips_linkage_id=?";

        $rc = self::dbQuery($sql, $params, self::QRY_RETURN_ROWS_AFFECTED);

        return $fom_archive_timestamp;
    }
    
    public function restoreFIPSrecord(int $fips_linkage_id, string $username): string {

        $sql = "SELECT fom_archive FROM fom_addresses WHERE fips_linkage_id=?";

        $json = self::dbFetchValue($sql, [ $fips_linkage_id ]);

        if ( !Yes3::is_json_decodable($json) ) {

            return "no archive exists";
            //return $json;
        }

        $x = json_decode($json, true);

        $x['fips_comment'] = self::logAction(self::getFipsComment($fips_linkage_id), $username, "restored");

        $sql = "UPDATE fom_addresses SET ";

        $params = [];
        $nP = 0;
        foreach($x as $colname=>$value){

            if ( !in_array($colname, ['fips_linkage_id', 'fom_archive', 'fom_archive_timestamp']) ){

                if ( $nP ) {

                    $sql .= ", ";
                }

                $params[] = $value;

                $sql .= $colname . " = ?";

                $nP++;
            }
        }

        $sql .= " WHERE fips_linkage_id = ? LIMIT 1";

        $params[] = $fips_linkage_id;

        $rc = self::dbQuery($sql, $params, self::QRY_RETURN_RETCODE);

        if ( $rc !== "success" ){

            return $rc;
        }
        else {

            return "success";
        }
    }

    private static function logAction($log, $username, $action){

        $timestamp = Yes3::isoTimeStampString();

        if ( strlen($log) > 0 ){

            $log .= "\n";
        }
        else {

            $log = "";
        }

        return $log . "[{$timestamp}]({$username}): {$action}.";
    }

    public function updateAPIbatch(): string {

        $batchSize = FIPS::getProjectSetting('api-batch-size');

        $batchOrder = FIPS::getProjectSetting('api-batch-order');

        $current = self::dbFetchValue("SELECT count(*) FROM fom_addresses WHERE `fips_match_status`=?", 
            [self::MATCH_STATUS_NEXT_API_BATCH]);

        $remaining = $batchSize - $current;

        if ( $remaining <= 0 ){

            return "The batch quota is full. You may still manually assign records to the next batch.";
        }

        $orderBy = ( FIPS::getProjectSetting('api-batch-order')==="random" ) ? "RAND()" : "d.`record`";

        $sql = "
        UPDATE fom_addresses a 
        INNER JOIN (
          SELECT c.fips_linkage_id FROM fom_addresses c
            INNER JOIN fom_crosswalk d ON d.fips_linkage_id=c.fips_linkage_id
          WHERE IFNULL(c.fips_match_status, 0) < ?
          ORDER BY {$orderBy}
          LIMIT ?
        ) b
        ON a.fips_linkage_id=b.fips_linkage_id
        SET a.fips_match_status = ?
        ";

        $params = [ self::MATCH_STATUS_NEXT_API_BATCH, $batchSize, self::MATCH_STATUS_NEXT_API_BATCH ];

        $rows_affected = self::dbQuery($sql, $params, self::QRY_RETURN_ROWS_AFFECTED);

        return $rows_affected . " record(s) marked for inclusion in the next API batch.";
    }

    public function getSummary(): array {

        $sql = "
        SELECT COUNT(*) as `summary_n`,
            SUM(IF(IFNULL(`fips_match_status`, ?)=?, 1, 0)) AS `summary_pending`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=?, 1, 0)) AS `summary_apibatch`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=?, 1, 0)) AS `summary_inprocess`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=?, 1, 0)) AS `summary_pobox`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=?, 1, 0)) AS `summary_deferred`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=?, 1, 0)) AS `summary_closed`,           
            SUM(IF(IFNULL(`fips_match_status`, 0)=? AND IFNULL(`fips_match_result`, '')=?, 1, 0)) AS `summary_closed_matched`,
            SUM(IF(IFNULL(`fips_match_status`, 0)=? AND IFNULL(`fips_match_result`, '')<>?, 1, 0)) AS `summary_closed_unmatched` 
        FROM fom_addresses
        ";

        $params = [ 
            FIO::MATCH_STATUS_PENDING,
            FIO::MATCH_STATUS_PENDING,
            FIO::MATCH_STATUS_NEXT_API_BATCH,
            FIO::MATCH_STATUS_IN_PROCESS,
            FIO::MATCH_STATUS_PO_BOX,
            FIO::MATCH_STATUS_DEFERRED,
            FIO::MATCH_STATUS_CLOSED,
            FIO::MATCH_STATUS_CLOSED,
            FIO::MATCH_RESULT_MATCHED,
            FIO::MATCH_STATUS_CLOSED,
            FIO::MATCH_RESULT_MATCHED
        ];

        return self::dbFetchRecord($sql, $params);
    }
 
    public static function dbQuery($sql, $params=[], $returnType=self::QRY_RETURN_RETCODE)
    {        
        $db_conn = FIODbConnection::getConn();
        
        $stmt = $db_conn->prepare($sql);

        if ( !$stmt ){

            throw new Exception("dbQuery statement preparation failed: " .  $sql);
        }

        if ($params) {

            $types = str_repeat('s', count($params)); // all string types: must assume proper formatting for floats, dates etc
            $retcode = $stmt->bind_param($types, ...$params); // '...' unpacks the array into individual args

            if ( !$retcode ){

                throw new Exception("dbQuery prepared statement binding failed: " .  $sql);
            }
        }

        $retcode = $stmt->execute();

        if ( !$retcode ){

            throw new Exception("dbQuery execution failed for : " .  $sql);
        }

        if ($returnType == self::QRY_RETURN_RESULTSET){

            $result = $stmt->get_result();

            if ( $result === false ){

                throw new Exception("dbQuery get_result() failed for : " .  $sql);
            }

            return $result;
        }

        if ($returnType == self::QRY_RETURN_INSERT_ID){

            return (int)$db_conn->insert_id;
        }

        if ($returnType == self::QRY_RETURN_ROWS_AFFECTED){

            return (int)$db_conn->affected_rows;
        }

        if ($returnType == self::QRY_RETURN_RETCODE){

            return "success";
        }

        if ( $db_conn->error ){

            return "MySQL error reported: " . $db_conn->error;
            throw new Exception("dbQuery reports MySQL error: " .  $db_conn->error);
        }

        throw new Exception("dbQuery failed for " .  $sql);
    }
    
    public static function dbFetchRecords($sql, $parameters = [])
    {
        $rows = [];
        $resultSet = self::dbQuery($sql, $parameters, self::QRY_RETURN_RESULTSET);
        if ( $resultSet->num_rows > 0 ) {
            while ($row = $resultSet->fetch_assoc()) {
                $rows[] = $row;
            }
            $resultSet->free_result();
        }
 
       return $rows;
    }

    public static function dbRecordGenerator( $sql, $parameters = [] )
    {
        $resultSet = self::dbQuery($sql, $parameters, self::QRY_RETURN_RESULTSET);
    
        while ($row = $resultSet->fetch_assoc()) {

            yield $row;
        }
        $resultSet->free_result();
    }

    public static function dbFetchRecord($sql, $parameters = []){

        return self::dbQuery(Yes3::sql_limit_1($sql), $parameters, self::QRY_RETURN_RESULTSET)->fetch_assoc();
    }

    public static function dbFetchValue($sql, $parameters = [])
    {
       return self::dbQuery(Yes3::sql_limit_1($sql), $parameters, self::QRY_RETURN_RESULTSET)->fetch_row()[0];
    }
 
} 

?>