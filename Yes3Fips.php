<?php

namespace Yale\Yes3Fips;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "autoload.php";

use Exception;
use REDCap;
use mysqli;

use Yale\Yes3Fips\Yes3;
use Yale\Yes3Fips\FIPS;
use Yale\Yes3Fips\FIO;
use Yale\Yes3Fips\FIOREDCap;
use Yale\Yes3Fips\FIODatabase;

class Yes3Fips extends \ExternalModules\AbstractExternalModule
{
    public $fips_editor_fields = [

        [ "field_name"=>"fips_match_status", "type"=>"select", "label"=>"status", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50, 
            "choices"=>[
                ["value"=>FIO::MATCH_STATUS_PENDING, "label"=>"Pending"],
                ["value"=>FIO::MATCH_STATUS_NEXT_API_BATCH, "label"=>"Next API batch"],
                ["value"=>FIO::MATCH_STATUS_IN_PROCESS, "label"=>"In process"],
                ["value"=>FIO::MATCH_STATUS_CLOSED, "label"=>"Closed"],
                ["value"=>FIO::MATCH_STATUS_PO_BOX, "label"=>"PO Box"],
                ["value"=>FIO::MATCH_STATUS_DEFERRED, "label"=>"Deferred"]
            ]
        ],

        [ "field_name"=>"fips_address",  "type"=>"textarea", "label"=>"address", "editable"=>FIO::IF_SINGLE_ADDRESS_FIELD, "display"=>FIO::IF_SINGLE_ADDRESS_FIELD, "size"=>100 ],
        [ "field_name"=>"fips_source_address_history",  "type"=>"textarea", "label"=>"source address history", "editable"=>FIO::NEVER, "display"=>FIO::IF_SINGLE_ADDRESS_FIELD, "size"=>100 ],

        [ "field_name"=>"fips_address_street","type"=>"textarea", "label"=>"Street", "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_city",  "type"=>"text", "label"=>"City",   "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_state", "type"=>"text", "label"=>"State",  "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_address_zip",   "type"=>"text", "label"=>"Zip",    "editable"=>FIO::IF_MULTIPLE_ADDRESS_FIELDS, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_phone_home",   "type"=>"text", "label"=>"Home phone",    "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>40 ],
        [ "field_name"=>"fips_phone_mobile",   "type"=>"text", "label"=>"Mobile phone",    "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>40 ],

        [ "field_name"=>"fips_address_submitted", "type"=>"textarea", "label"=>"Address submitted for match", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>100 ],
        [ "field_name"=>"fips_address_matched", "type"=>"textarea", "label"=>"Matched address(es)", "editable"=>FIO::NEVER, "size"=>100 ],
    
        [ "field_name"=>"fips_comment",       "type"=>"textarea", "label"=>"comment",  "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>100 ],

        [ "field_name"=>"fips_state",  "type"=>"text", "label"=>"FIPS state code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>25 ],
        [ "field_name"=>"fips_county", "type"=>"text", "label"=>"FIPS county code",    "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>25 ],
        [ "field_name"=>"fips_tract",  "type"=>"text", "label"=>"FIPS tract code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>25 ],
        [ "field_name"=>"fips_block",  "type"=>"text", "label"=>"FIPS block code",     "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>25 ],
        [ "field_name"=>"fips_code",   "type"=>"text", "label"=>"15-digit FIPS code",  "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_census_block_group","type"=>"text", "label"=>"12-digit Census block grp", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_state_county","type"=>"text", "label"=>"5-digit state+county", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>25 ],

        [ "field_name"=>"fips_match_result", "type"=>"text", "label"=>"Match result", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_match_type", "type"=>"text", "label"=>"Match type", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_longitude", "type"=>"text", "label"=>"Longitude", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_latitude", "type"=>"text", "label"=>"Latitude", "editable"=>FIO::ALWAYS, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_tigerlineid", "type"=>"text", "label"=>"Tiger line id", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_tigerlineside", "type"=>"text", "label"=>"Tiger line side", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_match_user", "type"=>"text", "label"=>"Matched by", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_match_timestamp", "type"=>"text", "label"=>"Matched on", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fips_save_user", "type"=>"text", "label"=>"Edited by", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        [ "field_name"=>"fips_save_timestamp", "type"=>"text", "label"=>"Edited on", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],

        [ "field_name"=>"fom_archive_timestamp", "type"=>"text", "label"=>"Archive timestamp", "editable"=>FIO::NEVER, "display"=>FIO::IF_SOURCE_DATABASE, "size"=>50 ],
        [ "field_name"=>"fom_archive", "type"=>"textarea", "label"=>"Archived record", "editable"=>FIO::NEVER, "display"=>FIO::IF_SOURCE_DATABASE, "size"=>100 ]

        //, [ "field_name"=>"fips_history_id", "type"=>"text", "label"=>"archive id", "editable"=>FIO::NEVER, "display"=>FIO::ALWAYS, "size"=>50 ],
        
    ];

    public $settings = [
        'address_field_type' => ""
    ];

    function __construct()
    {
        global $fips_db_conn;

        parent::__construct();

        if ( $this->getProjectId() ){

            $this->settings['address_field_type'] = $this->getProjectSetting('address-field-type');
        }
    }

    /**
     * deprecated; replaced by singleton class FIODbConnection
     * @return bool 
     * @throws Exception 
     */
    function initializeDBO()
    {
        if ( $this->getProjectSetting('data-source')!=="database" ){

            return false;
        }
        
        //global $fips_db_conn;
        Yes3::logDebugMessage(0, 'initializeDBO invoked', 'Yes3Fips');

        //session_start();

        //if ( !isset($GLOBALS['fips_db_conn']) || !is_object($GLOBALS['fips_db_conn']) || !$GLOBALS['fips_db_conn']->host_info) {
        if ( !$GLOBALS['fips_db_conn']) {

            $host = ""; $user = ""; $password = ""; $database = "";

            $specfile = FIPS::getProjectSetting('db-spec-file');

            require $specfile; // connection info, hopefully store off webroot

            try {

                $GLOBALS['fips_db_conn'] = new mysqli($host, $user, $password, $database);

            } catch( Exception $e ) {

                Yes3::logDebugMessage(0, $e->getMessage(), 'Yes3Fips:exception');
                throw new Exception("Failed to connect to MySQL (" . $e->getMessage());
            }

            if ($GLOBALS['fips_db_conn']->connect_errno) {

                Yes3::logDebugMessage(0, $db_conn->connect_error, 'Yes3Fips:connection error');
                throw new Exception("Failed to connect to MySQL: (" . $GLOBALS['fips_db_conn']->connect_errno . ") " . $GLOBALS['fips_db_conn']->connect_error);
            }
                    
            Yes3::logDebugMessage(0, print_r($GLOBALS['fips_db_conn'], true), 'Yes3Fips:DBCONN1');
        }
        else {

            Yes3::logDebugMessage(0, print_r($GLOBALS['fips_db_conn'], true), 'Yes3Fips:DBCONN2');
        }

        //Yes3::logDebugMessage(0, print_r($GLOBALS, true), 'Yes3Fips: globals');
        return true;
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

    /**
     * returns JSON-encoded properties for the instantiated class
     * @return string|false 
     */
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

    /**
     * Returns user permissions as array, including individual form permissions.
     * Attempts to harmonize pre/post v12 permissions.
     * @return array 
     * @throws Exception 
     */

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

    /**
     * Returns the full module URL ( http://redcap.internal/redcap_v13.7.2/modules/mymodule_v0.0.0/ )
     * or relative to webroot ( /redcap_v13.7.2/modules/mymodule_v0.0.0/ )
     * 
     * @param bool $relative 
     * @return string|false 
     * @throws Exception 
     */

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

    public function getProjectProperties(){

        return [
            'project_id' => $this->getProject()->getProjectId(),
            'title' => $this->getProject()->getTitle()
        ];
    }

    public function getEMSettings(){

        return [
            'api_batch_size' => FIPS::getProjectSetting('api-batch-size', FIO::DEFAULT_API_BATCH_SIZE),
            'reservation_block_size' => FIPS::getProjectSetting('reservation-block-size', FIO::DEFAULT_RESERVATION_BLOCK_SIZE),
            'allow_reservations' => FIPS::getProjectSetting('allow-reservations', FIO::DEFAULT_ALLOW_RESERVATIONS),
        ];
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

        $jmo = $this->initializeJavascriptModuleObject();

        //$js .= "\n" . $this->initializeJavascriptModuleObject() . ";";

        $js .= "\nYES3.moduleObject = " . $this->getJavascriptModuleObjectName() . ";";

        $js .= "\nYES3.moduleObjectName = '" . $this->getJavascriptModuleObjectName() . "';";

        $js .= "\nYES3.moduleProperties = " . $this->objectProperties() . ";\n";

        //$js .= "\nYES3.REDCapUserRights = " . json_encode( $this->getUser()->getRights() ) . ";\n";

        $js .= "\nYES3.userRights = " . json_encode( $this->yes3UserRights() ) . ";\n";

        $js .= "\nYES3.Project = " . json_encode( $this->getProjectProperties() ) . ";\n";

        $js .= "\nYES3.EMSettings = " . json_encode( $this->getEMSettings() ) . ";\n";

        $js .= "\nYES3.REDCapURL = '" . APP_PATH_WEBROOT_FULL	 . "';\n";

        // modify the JMO ajax method to dump response to console

        $js = str_replace(".then(response => {", ".then(response => {\nconsole.log(response);\n", $js);

        Yes3::logDebugMessage(0, $jmo, "getCodeFor:JMO");

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
        
        return $io->getFIPSrecords($params['filter'], $params['record'], $this->getUser()->getUserName());
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
            intval($params['fips_linkage_id']),
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
            intval($params['fips_linkage_id']), 
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

        return $io->getSummary( $this->getUser()->getUserName() );
    }

    private function getIoObject(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            return new FIODatabase();
        }
        else throw new Exception("EM config error: could not determine IO class");
    }

    /* -- API -- */

    private function callApiSingle($data){

        $record = $data['record'];
        $fips_linkage_id = $data['fips_linkage_id'];
        $benchmark = $data['benchmark'];
        $searchtype = $data['searchtype'];

        $addressType = $this->getProjectSetting('address-field-type');

        $vintage = FIO::GEO_BENCHMARK_VINTAGE[ $benchmark ];

        if ( !$record ){

            return "callApiSingle: no record specified";
        }

        $io = $this->getIoObject();

        $geoDataRecord = [];

        if ( $searchtype === "address" ){

            $address = $io->getAddressForApiCall( $record ) ;
            $geoDataRecord = $this->geocodeSingleAddress($fips_linkage_id, $addressType, $address, $benchmark, $vintage );
        }
        else {

            $location = $io->getLocationForApiCall( $record ) ;
            $geoDataRecord = $this->geocodeSingleLocation($fips_linkage_id, $location, $benchmark, $vintage );
        }

        return $io->saveGeoData( [ $geoDataRecord ] );
    }

    private function callApiBatch(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            $io = new FIOREDCap();
        }
        else if ( $data_source==="database" ){

            $io = new FIODatabase();
        }
        else return "EM config error";

        $timestamp = Yes3::isoTimeStampString();

        $temp_file_name = $io->makeCsvForApiCall();

        //return $temp_file_name;

        //return file_get_contents($temp_file_name);

        $geoDataRecords = $this->geocodeAddressFile($temp_file_name);

        if ( !is_array($geoDataRecords) ){

            return "No records";
        }

        return $io->saveGeoData( $geoDataRecords );
    }

    function prepareAddressElement( $s ){

        return trim(str_replace(["\r", "\t", "\n"], ["", " ", ", "], $s));
    }

    private function geocodeSingleAddress($fips_linkage_id, $addressType, $address, $benchmark, $vintage ): array{

        if ( $addressType === "single" ){

            $target_url = "https://geocoding.geo.census.gov/geocoder/geographies/onelineaddress";

            $post = [
                'benchmark' => $benchmark,  
                'vintage' => $vintage,
                'layers' => "all", //FIO::GEO_LAYERS,
                'format' => 'json',
                'address' => $this->prepareAddressElement( $address['fips_address'] )
            ];
        }
        else {

            $target_url = "https://geocoding.geo.census.gov/geocoder/geographies/address";

            $post = [
                'benchmark' => $benchmark,  
                'vintage' => $vintage,
                'layers' => "all", //FIO::GEO_LAYERS,
                'format' => 'json',
                'street' => $address['fips_address_street'],
                'city' => $address['fips_address_city'],
                'state' => $address['fips_address_state'],
                'zip' => $address['fips_address_zip']
            ];
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        try {

            $resultJSON = curl_exec ($ch);

        } catch(exception $e) {

            curl_close($ch);
            return $e->getMessage();
        }

        curl_close ($ch);

        if ( !Yes3::is_json_decodable($resultJSON) ){

            return [];
        }

        $result = json_decode($resultJSON, true)['result'];

        // add linkage id to input prop
        $result['input']['linkage'] = ['fips_linkage_id' => $fips_linkage_id];

        Yes3::logDebugMessage($this->getProjectId(), print_r($result, true), "geocodeSingleAddress");

        // we need the single address field to be populated even if components were input
        if ( $result['input']['address']['street'] ) {

            $result['input']['address']['address'] = 
                trim($result['input']['address']['street']) . "\n" .
                trim($result['input']['address']['city'])  . " " .
                trim($result['input']['address']['state'])  . " " .
                trim($result['input']['address']['zip'])
            ;
        }

        $input_address = strtoupper( $result['input']['address']['address'] );

        $matched_address_summary = "";
        $fips_code = "";
        $same_fips_code = true;
        $addressMatchesIndex = 0;

        /**
         * this loop will:
         *  (1) accumulate a "matched address summary" of all matched addresses (address, fips, match result)
         *  (2) add 'match_type' to the addressMatches structure
         *  (3) for a tie, determine if all FIPS codes agree (same_fips_code)
         */
        for( $i=0; $i < count($result['addressMatches']); $i++ ){

            $this_matched_address = strtoupper($result['addressMatches'][$i]['matchedAddress']);

            $this_match_type = $this->compareAddresses($input_address, $this_matched_address);

            $result['addressMatches'][$i]['match_type'] = $this_match_type;

            // structure depends on benchmark apparantly

            // current, acs
            $this_fips_code = $result['addressMatches'][$i]['geographies']['2020 Census Blocks'][0]['GEOID'];

            if ( !$this_fips_code ){

                // 2020 Census
                $this_fips_code = $result['addressMatches'][$i]['geographies']['Census Blocks'][0]['GEOID'];
            }

            $this_matched_address_summary = 
                $this_matched_address
                . "\n" . $this_match_type . ", fips=" . $this_fips_code
            ;

            if ( $this_match_type === FIO::MATCH_TYPE_EXACT ){

                $addressMatchesIndex = $i;
            }

            if ( $matched_address_summary ) {
                
                $matched_address_summary .= "\n\n";

                if ( $this_fips_code !== $fips_code ){

                    $same_fips_code = false;
                }

                $fips_code = $this_fips_code;
            }

            $matched_address_summary .= $this_matched_address_summary;
        }

        $geoDataRecord = FIPS::getGeoDataRecordFromApiObject( $result, $matched_address_summary, $same_fips_code, $addressMatchesIndex);

        Yes3::logDebugMessage($this->getProjectId(), print_r($geoDataRecord, true), "geocodeSingleAddress:geoDataRecord");

        return $geoDataRecord;
    }  
    
    private function compareAddresses( $input_address, $matched_address ){

        $search = [
            " ROAD ",
            " STREET ",
            " AVENUE ",
            " LANE ",
            " DRIVE ",
            " ROUTE ",
            " VALLEY ",
            " TRAIL ",
            " TURNPIKE ",
            " BOULEVARD ",
            " CIRCLE ",
            " HIGHWAY ",
        ];

        $replace = [
            " RD ",
            " ST ",
            " AVE ",
            " LN ",
            " DR ",
            " RTE ",
            " VLY ",
            " TRL ",
            " TPKE ",
            " BLVD ",
            " CIR ",
            " HWY "
        ];

        if ( !$matched_address ) return "";

        $input_street = "";
        $input_city = "";
        $input_city = "";
        $input_zip = "";

        FIPS::singleAddressFieldParser(strtoupper(trim($input_address)), $input_street, $input_city, $input_state, $input_zip);
        
        /**
         * STREET ADDRESS:
         * Whitespace runs are compressed to single spaces to facilitate comparisons (comma is considered a whitespace).
         * Feature names are replaced with Tiger Line abbreviations.
         * A space is prepended & appended (then trimmed out) so that whole-word replacements will always work.
         */
        $input_street = trim(str_replace($search, $replace, " ".preg_replace('/(\s|,)+/', ' ',$input_street)." "));

        $matched_parts = explode(",", $matched_address);

        $matched_street = trim($matched_parts[0]);
        $matched_city   = trim($matched_parts[1]);
        $matched_state  = trim($matched_parts[2]);
        $matched_zip    = trim($matched_parts[3]);

        $match_type = (
            strpos($input_street, $matched_street) !== false &&
            $input_city === $matched_city && 
            $input_state === $matched_state &&
            strpos($input_zip, $matched_zip) === 0
        ) ? FIO::MATCH_TYPE_EXACT : FIO::MATCH_TYPE_FUZZY;

        $msg = 
            "street: [{$input_street}] [{$matched_street}]"
        . "\ncity  : [{$input_city}] [{$matched_city}]"
        . "\nstate : [{$input_state}] [{$matched_state}]"
        . "\nzip   : [{$input_zip}] [{$matched_zip}]"
        . "\nMATCH TYPE = " . $match_type
        ;

        Yes3::logDebugMessage($this->getProjectId(), $msg, "compareAddresses");

        return $match_type;
    }

    private function geocodeSingleLocation($fips_linkage_id,  $location, $benchmark, $vintage ){

        $target_url = "https://geocoding.geo.census.gov/geocoder/geographies/coordinates";

        $post = [
            'benchmark' => $benchmark,  
            'vintage' => $vintage,
            'layers' => FIO::GEO_LAYERS,
            'format' => 'json',
            'x' => $location['fips_longitude'],
            'y' => $location['fips_latitude']
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        try {

            $resultJSON = curl_exec ($ch);

        } catch(exception $e) {

            curl_close($ch);
            return $e->getMessage();
        }

        curl_close ($ch);

        if ( !Yes3::is_json_decodable($resultJSON) ){

            return [];
        }

        $result = json_decode($resultJSON, true)['result'];

        // add linkage id to input prop
        $result['input']['linkage'] = ['fips_linkage_id' => $fips_linkage_id];

        Yes3::logDebugMessage($this->getProjectId(), print_r($result, true), "geocodeSingleLocation");

        // we need the single address field to be populated even if components were input
        if ( $result['input']['address']['street'] ) {

            $result['input']['address']['address'] = 
                trim($result['input']['address']['street']) . "\n" .
                trim($result['input']['address']['city'])  . " " .
                trim($result['input']['address']['state'])  . " " .
                trim($result['input']['address']['zip'])
            ;
        }

        $geoDataRecord = FIPS::getGeoDataRecordFromApiObject( $result );

        Yes3::logDebugMessage($this->getProjectId(), print_r($geoDataRecord, true), "geocodeSingleLocation:geoDataRecord");

        return $geoDataRecord;
    }    

    /**
     * okay for either REDcap or database, just needs a stored address csv
     * 
     * @param mixed $addressFilename 
     * @return string|array 
     */
    private function geocodeAddressFile( $addressFilename ): array {

        $target_url = "https://geocoding.geo.census.gov/geocoder/geographies/addressbatch";

        $addressfile = curl_file_create($addressFilename);
        $benchmark = FIO::GEO_BENCHMARK_PRIMARY;
        $vintage = FIO::GEO_BENCHMARK_VINTAGE[ $benchmark ];

        $post = [
            'benchmark'=> $benchmark,  
            'vintage' => $vintage,
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

            $apiCsvRow = str_getcsv($row);

            if ( is_array($apiCsvRow) && count($apiCsvRow) >= 3 ){

                $geoDataRecord = FIPS::getGeoDataRecordFromApiCsvRow($apiCsvRow);

                if ( $geoDataRecord['fips_linkage_id'] ) {

                    $geoData[] = $geoDataRecord;
                }
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

        return REDCap::getCopyright() . "<br />The Fabulous FIPS-O-Matic &copy; 2023 CRI Web Tools LLC";
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

    private function reserveBatch( $params ){

        $reservation_user = $this->getUser()->getUserName();

        if ( $reservation_user !== $params['user'] ){

            throw new Exception("Suspicious reservation user name: ".$params['user']);
        }

        $io = $this->getIoObject();

        $reservation_block_size = FIPS::getProjectSetting('reservation-block-size', FIO::DEFAULT_RESERVATION_BLOCK_SIZE);

        return $io->reserveBatch($reservation_user, $reservation_block_size);
    }

    private function releaseBatch( $params ){

        $reservation_user = $this->getUser()->getUserName();

        if ( $reservation_user !== $params['user'] ){

            throw new Exception("Suspicious reservation user name: ".$params['user']);
        }

        $io = $this->getIoObject();

        return $io->releaseBatch( $reservation_user );
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

        //include("foo.bar");

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

        else if ($action==="call-api-batch") {

            return $this->callApiBatch($payload);
        }

        else if ($action==="call-api-single") {

            return $this->callApiSingle($payload);
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

        else if ($action==="reserve-batch") {

            return $this->reserveBatch($payload);
        }

        else if ($action==="release-batch") {

            return $this->releaseBatch($payload);
        }

                        
        else return "No can do: the action '{$action}' is most abhorrent.";
    }
}