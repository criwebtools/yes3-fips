<?php

namespace Yale\Yes3Fips;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "autoload.php";

use Exception;
use REDCap;
use stdClass;

use Yale\Yes3Fips\Yes3;

use Yale\Yes3Fips\FIPS;
use Yale\Yes3Fips\FIO;
use Yale\Yes3Fips\FIOREDCap;
use Yale\Yes3Fips\FIODbConnection;

class Yes3Fips extends \ExternalModules\AbstractExternalModule
{
    // low rent dd for FIPS form
    public $fips_form = [
        'form_name' => 'fips',
        "fips_linkage_id" => "fips_linkage_id",
        "fips_address" => "fips_address",
        "fips_address_street" => "fips_address_street",
        "fips_address_city" => "fips_address_city",
        "fips_address_state" => "fips_address_state",
        "fips_address_updated" => "fips_address_updated",
        "fips_address_zip" => "fips_address_zip",
        "fips_code" => "fips_code",
        "fips_address_timestamp" => "fips_address_timestamp",
        "fips_state" => "fips_state",
        "fips_county" => "fips_county",
        "fips_tract" => "fips_tract",
        "fips_block" => "fips_block",
        "fips_match_result" => "fips_match_result",
        "fips_match_status" => "fips_match_status",
        "fips_match_timestamp" => "fips_match_timestamp",
        "fips_batch_user" => "fips_batch_user",
        "fips_save_timestamp" => "fips_save_timestamp",
        "fips_save_user" => "fips_save_user",
        "fips_comment" => "fips_comment"
    ];

    public $fips_editor_fields = [

        [ "field_name"=>"fips_match_status", "type"=>"select", "label"=>"status", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50, 
            "choices"=>[
                ["value"=>FIO::MATCH_STATUS_PENDING, "label"=>"Pending"],
                ["value"=>FIO::MATCH_STATUS_NEXT_API_BATCH, "label"=>"Next API batch"],
                ["value"=>FIO::MATCH_STATUS_IN_PROCESS, "label"=>"In process"],
                ["value"=>FIO::MATCH_STATUS_CLOSED, "label"=>"Closed"]
            ]
        ],

        [ "field_name"=>"fips_address",  "type"=>"textarea", "label"=>"address", "editable"=>FIO::IF_SINGLE_ADDRESS_FIELD, "display"=>FIO::IF_SINGLE_ADDRESS_FIELD, "size"=>100 ],

        [ "field_name"=>"fips_address_street","type"=>"textarea", "label"=>"Street", "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_city",  "type"=>"text", "label"=>"City",   "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_state", "type"=>"text", "label"=>"State",  "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_address_zip",   "type"=>"text", "label"=>"Zip",    "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>50 ],
    
        [ "field_name"=>"fips_comment",       "type"=>"textarea", "label"=>"comment",  "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>100 ],

        [ "field_name"=>"fips_state",  "type"=>"text", "label"=>"FIPS state code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_county", "type"=>"text", "label"=>"FIPS county code",    "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_tract",  "type"=>"text", "label"=>"FIPS tract code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_block",  "type"=>"text", "label"=>"FIPS block code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_code",   "type"=>"text", "label"=>"15-digit FIPS code",  "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_census_block_group","type"=>"text", "label"=>"12-digit Census block grp", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_match_result", "type"=>"text", "label"=>"Match result", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_match_type", "type"=>"text", "label"=>"Match type", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_address_submitted", "type"=>"textarea", "label"=>"Address submitted for match", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_matched", "type"=>"textarea", "label"=>"Matched address", "editable"=>FIO::NEVER, "size"=>100 ],

        [ "field_name"=>"fips_longitude", "type"=>"text", "label"=>"Longitude", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_latitude", "type"=>"text", "label"=>"Latitude", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_tigerlineid", "type"=>"text", "label"=>"Tiger line id", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_tigerlineside", "type"=>"text", "label"=>"Tiger line side", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_history_id", "type"=>"text", "label"=>"archive id", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        
    ];

    public $settings = [
        'address_field_type' => ""
    ];

    function __construct()
    {
        parent::__construct();

        if ( $this->getProjectId() ){

            $this->settings['address_field_type'] = $this->getProjectSetting('address-field-type');
        }
    }

    function hiMom(){
        print "Hi Mom";
    }

    function redcap_module_link_check_display($project_id, $link){

        $allowed_user = $this->getProjectSetting('allowed-user');

        $this_user = $this->getUser()->getUserName();

        if (is_array($allowed_user) ){

            for($i=0; $i<count($allowed_user); $i++){

                if ($allowed_user[$i]===$this_user){

                    return $link;
                }
            }
        }

        return false;
    }

    function redcap_save_record ( 
        $project_id, 
        $record,
        $instrument, 
        $event_id, 
        $group_id = NULL, 
        $survey_hash = NULL, 
        $response_id = NULL, 
        $repeat_instance = 1 
    ){
        if ( $instrument==="fips" ){

            $match_status = Yes3::getREDCapDatum($project_id, $event_id, $record, 'fips_match_status', $repeat_instance);

            REDCap::saveData(
                $project_id,
                'array',
                [
                    $record => [
                        $event_id => [
                            'fips_save_user' => $this->getUser()->getUsername(),
                            'fips_save_timestamp' => Yes3::isoTimeStampString(),
                            'fips_complete' => ( $match_status && $match_status===FIO::MATCH_STATUS_CLOSED ) ? FIO::FORM_COMPLETE : FIO::FORM_INCOMPLETE            
                        ]
                    ]
                ]
            );
        }
    }

    private function saveAddressData( $xx ){

        $rc = REDCap::saveData(
            $this->getProjectId(),
            'array',
            $xx
        );

        print "<p>".print_r($rc, true)."</p>";
    }

    public function objectProperties()
    {
        $propKeys = [];

        /**
         * A ReflectionObject is apparently required to distinuish the non-private properties of this object
         * https://www.php.net/ReflectionObject
         */
        $publicProps = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC+\ReflectionProperty::IS_PROTECTED);

        foreach( $publicProps as $rflxnProp){
            $propKeys[] = $rflxnProp->name;
        }
         
        $props = [ 'CLASS' => __CLASS__ ];

        foreach ( $propKeys as $propKey ){

            $json = json_encode($this->$propKey);

            /**
             * some properties can't be json-encoded...
             */
            if ( $json===false ){
                $props[$propKey] = "json encoding failed for {$propKey}: " . json_last_error_msg();
            }
            else {
                $props[$propKey] = $this->$propKey;
            }
        }

        if ( !$json = json_encode($props) ){
            return json_encode(['message'=>json_last_error_msg()]);
        }
        
        return $json;
    }

    public function yes3UserRights()
    {
        $isDesigner = ( $this->getUser()->hasDesignRights() ) ? 1:0;

        $user = $this->getUser()->getRights();

        //Yes3::logDebugMessage($this->project_id, print_r($user, true), "user rights");

        /**
         * The rank order of export permission codes
         * 
         * 0 - no access (export code = 0)
         * 1 - de-identified: no identifiers, dates or text fields (export code = 2)
         * 2 - no identifiers (export code = 3)
         * 3 - full access (export code = 1)
         */
        $exportPermRank = [0, 3, 1, 2];

        $formPermString = str_replace("[", "", $user['data_entry']);

        $formPerms = explode("]", $formPermString);
        $formPermissions = [];
        foreach( $formPerms as $formPerm){

            if ( $formPerm ){
                
                $formPermParts = explode(",", $formPerm);
                $formPermissions[ $formPermParts[0] ] = ($isDesigner) ? 1 : (int) $formPermParts[1];
            }
        }

        /**
         * Export permissions differ as of REDCap v12(!)
         */

        $formExportPermissions = [];

        $exporter = $isDesigner;

        if ( $isDesigner ){

            $export_tool = 1;
        }
        else {

            $export_tool = (int)$user['data_export_tool']; // this is always blank in v12, have to use form-specific
            if ( !$export_tool ) $export_tool = 0; // I'm paranoid
        }

        if ( isset($user['data_export_instruments'])) {

            //Yes3::logDebugMessage($this->project_id, print_r($user['data_export_instruments'], true), "user[data_export_instruments]");

            $formExportPermString = str_replace("[", "", $user['data_export_instruments']);

            $formExportPerms = explode("]", $formExportPermString);

            foreach( $formExportPerms as $formExportPerm){

                if ( $formExportPerm ){

                    $formExportPermParts = explode(",", $formExportPerm);

                    $xPerm = (int)$formExportPermParts[1];

                    if ( $exportPermRank[$xPerm] > $exportPermRank[$export_tool] ){

                        $export_tool = $xPerm;
                    }

                    if ( $xPerm > 0 && $exporter === 0 ){

                        $exporter = 1;
                    }
                    
                    $formExportPermissions[ $formExportPermParts[0] ] = $xPerm;
                }
            }
        }
        // pre-v12
        else {

            // create the v12-style form export permission array, with each instrument having the global permission
            foreach ( array_keys($formPermissions) as $instrument){

                $formExportPermissions[$instrument] = $export_tool;
            }
            $exporter = ( $export_tool > 0 ) ? 1 : 0;
        }

        /**
         * set export permission to "none" for any form the user is not allowed to view
         */
        foreach ( $formPermissions as $form_name=>$formperm){

            if ( !$formperm ){

                $formExportPermissions[$form_name] = 0;
            }
        }

        //Yes3::logDebugMessage($this->project_id, print_r($formPermissions, true), "form permissions");
        
        return [

            'username' => $this->getUser()->getUsername(),
            'isDesigner' => ( $this->getUser()->hasDesignRights() ) ? 1:0,
            'isSuper' => ( $this->getUser()->isSuperUser() ) ? 1:0,
            'group_id' => (int)$user['group_id'],
            'dag' => ( $user['group_id'] ) ? REDCap::getGroupNames(true, $user['group_id']) : "",
            'export' => $export_tool,
            'import' => (int)$user['data_import_tool'],
            'api_export' => (int)$user['api_export'],
            'api_import' => (int)$user['api_import'],
            'form_permissions' => $formPermissions,
            'form_export_permissions' => $formExportPermissions,
            'exporter' => $exporter
        ];
    }

    public function getModuleUrl($relative=false)
    {
        // returns a url ending in "/?xxxxxxxx"
        $module_url = trim($this->getUrl(""));

        // strip off any query parm
        if ( strpos($module_url, "?") ) $module_url = substr($module_url, 0, strpos($module_url, "?"));

        // add a terminating slash if not provided
        if ( substr($module_url, -1) !== "/" ) $module_url .= "/";

        if ( $relative ) return substr($module_url, strlen(APP_PATH_WEBROOT_FULL)-1);
        else return $module_url;
    }

    public function getCodeFor( string $libname, bool $includeHtml=false ):string
    {
        $s = "";
        $js = "";
        $css = "";

        $module_url = $this->getModuleUrl(true);

        //die($module_url);
        
        $s .= "\n<!-- Yes3 getCodeFor: {$libname} -->";
        
        $js .= file_get_contents( $this->getModulePath()."js/yes3.js" );   
        $js .= file_get_contents( $this->getModulePath()."js/{$libname}.js" );

        $js .= "\n" . $this->initializeJavascriptModuleObject() . ";";

        $js .= "\nYES3.moduleObject = " . $this->getJavascriptModuleObjectName() . ";";

        $js .= "\nYES3.moduleObjectName = '" . $this->getJavascriptModuleObjectName() . "';";

        $js .= "\nYES3.moduleProperties = " . $this->objectProperties() . ";\n";

        //$js .= "\nYES3.REDCapUserRights = " . json_encode( $this->getUser()->getRights() ) . ";\n";

        $js .= "\nYES3.userRights = " . json_encode( $this->yes3UserRights() ) . ";\n";

        $css .= file_get_contents( $this->getModulePath()."css/yes3.css" );
        $css .= file_get_contents( $this->getModulePath()."css/common.css" );
        $css .= file_get_contents( $this->getModulePath()."css/{$libname}.css" );

        // resolve any CSS url() props
        $css = str_replace("[MODULE_URL]", $module_url, $css);

        if ( $js ) $s .= "\n<script>{$js}</script>";

        if ( $css ) $s .= "\n<style>{$css}</style>";

        if ( $includeHtml ){
            $s .= file_get_contents( $this->getModulePath()."html/yes3.html" );
            $s .= file_get_contents( $this->getModulePath()."html/${libname}.html" );
        }

        print $s;

        return $s;
    }

    private function getFipsRecords( $params ){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        
        return $io->getFIPSrecords($params['filter'], $params['record']);
    }

    private function saveFipsRecord( $params ){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        return $io->saveFIPSrecord(
            $params['record'],
            $params['fips_linkage_id'],
            $params['data'], 
            $params['close_editor_on_success'], 
            $this->getUser()->getUsername()
        );
    }

    private function restoreFipsRecord( $params ){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        return $io->restoreFIPSrecord(
            $params['fips_linkage_id'], 
            $this->getUser()->getUsername()
        );
    }

    private function updateApiBatch(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        else return "EM config error";

        return $io->updateAPIbatch();
    }

    private function getSummary(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        else return "EM config error";

        return $io->getSummary();
    }

    /* -- API -- */

    private function callApi($data){

        $record = $data['record'];

        if ( !$record ){

            $record = '';
        }

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        else return "EM config error";

        $timestamp = Yes3::isoTimeStampString();

        $temp_file_name = $io->makeCsvForApiCall($record);

        //return $temp_file_name;

        //return file_get_contents($temp_file_name);

        $geoData = $this->geocodeAddressFile($temp_file_name);

        if ( !is_array($geoData) ){

            return "No records";
        }

        for($i=0; $i<count($geoData);$i++) {

            if ( $geoData[$i]['fips_linkage_id'] ){

                if ( $geoData[$i]['fips_match_type']==='Exact' ){

                    $geoData[$i]['fips_match_status'] = FIO::MATCH_STATUS_CLOSED;
                 }
                else if ( $geoData[$i]['fips_match_type']==='Non_Exact' ){

                    $geoData[$i]['fips_match_status'] = FIO::MATCH_STATUS_IN_PROCESS;
                }
                else {

                    $geoData[$i]['fips_match_status'] = FIO::MATCH_STATUS_IN_PROCESS;
                 }

                $geoData[$i]['fips_match_user'] = $this->getUser()->getUsername();
                $geoData[$i]['fips_match_timestamp'] = $timestamp;
            }
        }

        //return print_r($geoData, true);

        return $io->saveGeoData( $geoData );
    }

    function prepareAddressElement( $s ){

        return str_replace(["\r", "\t", "\n"], ["", " ", ", "], $s);
    }

    /**
     * okay for either REDcap or database, just needs a stored address csv
     * 
     * @param mixed $addressFilename 
     * @return string|array 
     */
    private function geocodeAddressFile( $addressFilename ){

        $target_url = "https://geocoding.geo.census.gov/geocoder/geographies/addressbatch";

        $addressfile = curl_file_create($addressFilename);

        $post = [
            'location' => 'geographies',
            'benchmark'=>'Public_AR_Census2020',
            'vintage' => 'Census2020_Census2020',
            'layers' => 10,
            'format' => 'json',
            'addressFile' => $addressfile
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        try {

            $result = curl_exec ($ch);

        } catch(exception $e) {

            curl_close($ch);
            return $e->getMessage();
        }

        curl_close ($ch);

        //Yes3::logDebugMessage($this->getProjectId(), $result, "geocodeAddressFile");

        $rows = explode("\n", $result);

        $geoData = [];

        foreach($rows as $row){

            $fipsArray = str_getcsv($row);

            if ( is_array($fipsArray) && count($fipsArray) >= 3 ){

               $geoData[] = FIPS::putGeoRecord($fipsArray);
            }
        }

        return $geoData;
    }

    private function callApiDatabase($record){
    
        return "no db support yet";
    }

    private function getApiBatchSize(){

        if ( !$api_batch_size = $this->getProjectSetting('api-batch-size') ){

            $this->setProjectSetting('api-batch-size', FIO::DEFAULT_API_BATCH_SIZE);
            $api_batch_size = $this->getProjectSetting('api-batch-size');
        }

        return $api_batch_size;
    }

    private function setApiBatchSize($data){

        if ( $data['api_batch_size'] ) {

            $this->setProjectSetting('api-batch-size', $data['api_batch_size']);
        }

        return $this->getProjectSetting('api-batch-size');
    }

    private function clearApiBatch(){

        $event_id = $this->getProjectSetting('fips-event');
        $project_id = $this->getProjectId();

        $sql = "
        SELECT k.`record`
        FROM redcap_data k
            LEFT JOIN redcap_data m ON m.project_id=k.project_id AND m.event_id=k.event_id AND m.`record`=k.`record` AND m.field_name='fips_match_result'
        WHERE k.project_id=? AND k.event_id=? AND k.field_name='fips_match_status' AND k.`value`=? AND m.`value` IS NULL
        ";

        $params = [$project_id, $event_id, FIO::MATCH_STATUS_NEXT_API_BATCH];

        $yy = Yes3::fetchRecords($sql, $params);

        $records = [];

        $n = 0;

        foreach($yy as $y){
            $n++;
            $records[ $y['record'] ][ $event_id ]['fips_match_status'] = FIO::MATCH_STATUS_PENDING;
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
            return "API call succeeded.<br>{$n} record(s) were set from 'next API batch' to 'pending'.";
        }   
    }

    public function getCopyRight(){

        return REDCap::getCopyright() . "<br />Fabulous FIPS-O-Matic &copy; 2023 CRI Web Tools LLC";
    }

    public function testDb(){

        $io = new FIODatabase();
        
        $sql = "select a.fips_linkage_id, a.fips_address_street, a.fips_address_city, a.fips_address_state, a.fips_address_zip
        FROM fom_addresses a
        /*WHERE a.fips_address_state = ?*/
        ";

        $params = [];
        //$params = [ 'MN' ];

        $xx = $io->dbFetchRecords($sql, $params);

        $t = hrtime(true);
        $m = 0;
        foreach( $xx as $x){

            $m++;
        }
        print "<p>dbFetchRecords: " . $m . " row(s) returned. time: " . strval(hrtime(true)-$t) . " nanoseconds</p>";

        $t = hrtime(true);
        $n = 0;
        foreach( $io->dbRecordGenerator( $sql, $params) as $x ){

            $n++;
        }
        print "<p>dbRecordGenerator: " . $n. " row(s) returned. time: " . strval(hrtime(true)-$t) . " nanoseconds</p>";
    }

    /**
     * handler for the EM 'redcap_module_ajax' hook
     * 
     * @param mixed $action 
     * @param mixed $payload 
     * @param mixed $project_id 
     * @param mixed $record 
     * @param mixed $instrument 
     * @param mixed $event_id 
     * @param mixed $repeat_instance 
     * @param mixed $survey_hash 
     * @param mixed $response_id 
     * @param mixed $survey_queue_hash 
     * @param mixed $page 
     * @param mixed $page_full 
     * @param mixed $user_id 
     * @param mixed $group_id 
     * @return mixed 
     * @throws Exception 
     */
    function redcap_module_ajax(
        $action, 
        $payload, 
        $project_id, 
        $record, 
        $instrument, 
        $event_id, 
        $repeat_instance, 
        $survey_hash, 
        $response_id, 
        $survey_queue_hash, 
        $page, 
        $page_full, 
        $user_id, 
        $group_id){

        if ($action==="get-fips-records") {

            return $this->getFipsRecords( $payload );
        }

        else if ($action==="save-fips-record") {

            return $this->saveFipsRecord( $payload );
        }

        else if ($action==="restore-fips-record") {

            return $this->restoreFipsRecord( $payload );
        }

        else if ($action==="clear-api-batch") {

            return $this->clearApiBatch();
        }

        else if ($action==="update-api-batch") {

            return $this->updateApiBatch();
        }

        else if ($action==="get-summary") {

            return $this->getSummary();
        }

        else if ($action==="call-api") {

            return $this->callApi($payload);
        }

        else if ($action==="get-api-batch-size") {

            return $this->getApiBatchSize();
        }

        else if ($action==="set-api-batch-size") {

            return $this->setApiBatchSize($payload);
        }

        else if ($action==="get-copyright") {

            return $this->getCopyright();
        }
                        
        else return "No can do: the action '{$action}' is most abhorrent.";
    }
}