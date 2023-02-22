<?php

namespace Yale\Yes3Fips;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('MATCH_STATUS_PENDING', '0');
define('MATCH_STATUS_NEXT_API_BATCH', '1');
define('MATCH_STATUS_IN_PROCESS', '2');
define('MATCH_STATUS_CLOSED', '3');
define('DEFAULT_API_BATCH_SIZE', '50');

require "autoload.php";

use Exception;
use PHPSQLParser\builders\TruncateBuilder;
use REDCap;

use Yale\Yes3Fips\FIPS;
use Yale\Yes3Fips\Yes3;

class Yes3Fips extends \ExternalModules\AbstractExternalModule
{
    // low rent dd for FIPS form
    public $fips_form = [
        'form_name' => 'fips',
        "fips_linkage_id" => "fips_linkage_id",
        "fips_address_original" => "fips_address_original",
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

        [ "field_name"=>"fips_address_original",  "type"=>"textarea", "label"=>"Imported address",    "editable"=>0, "size"=>100 ],

        [ "field_name"=>"fips_match_status",      "type"=>"select",   "label"=>"status",              "editable"=>1, "size"=>50, 
            "choices"=>[
                ["value"=>MATCH_STATUS_PENDING, "label"=>"Pending"],
                ["value"=>MATCH_STATUS_NEXT_API_BATCH, "label"=>"Next API batch"],
                ["value"=>MATCH_STATUS_IN_PROCESS, "label"=>"In process"],
                ["value"=>MATCH_STATUS_CLOSED, "label"=>"Closed"]
            ]
        ],

        [ "field_name"=>"fips_address_street",    "type"=>"textarea", "label"=>"Street",              "editable"=>1, "size"=>100 ],
        [ "field_name"=>"fips_address_city",      "type"=>"text",     "label"=>"City",                "editable"=>1, "size"=>100 ],
        [ "field_name"=>"fips_address_state",     "type"=>"text",     "label"=>"State",               "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_address_zip",       "type"=>"text",     "label"=>"Zip",                 "editable"=>1, "size"=>50 ],
    
        [ "field_name"=>"fips_comment",           "type"=>"textarea", "label"=>"comment",             "editable"=>1, "size"=>100 ],

        [ "field_name"=>"fips_state",             "type"=>"text",     "label"=>"FIPS state code",     "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_county",            "type"=>"text",     "label"=>"FIPS county code",    "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_tract",             "type"=>"text",     "label"=>"FIPS tract code",     "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_block",             "type"=>"text",     "label"=>"FIPS block code",     "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_code",              "type"=>"text",     "label"=>"15-digit FIPS code",      "editable"=>1, "size"=>50 ],
        [ "field_name"=>"fips_census_block_group","type"=>"text",     "label"=>"12-digit Census block group",      "editable"=>1, "size"=>50 ],

        [ "field_name"=>"fips_match_result", "type"=>"text", "label"=>"Match result", "editable"=>0, "size"=>50 ],
        [ "field_name"=>"fips_match_type", "type"=>"text", "label"=>"Match type", "editable"=>0, "size"=>50 ],

        [ "field_name"=>"fips_address_submitted", "type"=>"textarea", "label"=>"Address submitted for match", "editable"=>0, "size"=>100 ],
        [ "field_name"=>"fips_address_matched", "type"=>"textarea", "label"=>"Matched address", "editable"=>0, "size"=>100 ],

        [ "field_name"=>"fips_longitude", "type"=>"text", "label"=>"Longitude", "editable"=>0, "size"=>100 ],
        [ "field_name"=>"fips_latitude", "type"=>"text", "label"=>"Latitude", "editable"=>0, "size"=>100 ],
        [ "field_name"=>"fips_tigerlineid", "type"=>"text", "label"=>"Tiger line id", "editable"=>0, "size"=>50 ],
        [ "field_name"=>"fips_tigerlineside", "type"=>"text", "label"=>"Tiger line side", "editable"=>0, "size"=>50 ],
        
    ];

    function hiMom(){
        print "Hi Mom";
    }

    function redcap_module_link_check_display($project_id, $link){

        return $link; // display all links to all project staff
    }

    function getUniqueLinkageIdREDCap(){

        $fips_event         = $this->getProjectSetting('fips-event');
        $project_id         = $this->getProjectId();

        $linkage_id = 0;
        $n = 0;
        while ( $linkage_id===0 && $n<1000 ){

            $n++;

            $linkage_id = mt_rand(1, 1000000);

            $sql = "SELECT `record` FROM redcap_data WHERE `project_id`=? AND `event_id`=? AND `field_name`='fips_linkage_id' AND `value`=? LIMIT 1";
            $params = [ $project_id, $fips_event, $linkage_id ];

            if ( Yes3::fetchValue($sql, $params) ){

                $linkage_id = 0;
            }
        }

        return $linkage_id;
    }

    function importAddressesToFipsForm() {

        $selection_field    = $this->getProjectSetting('selection-field-name');
        $address_field      = $this->getProjectSetting('address-field-name');
        $street_1_field     = $this->getProjectSetting('street-1-field-name');
        $street_2_field     = $this->getProjectSetting('street-2-field-name');
        $city_field         = $this->getProjectSetting('city-field-name');
        $state_field        = $this->getProjectSetting('state-field-name');
        $zip_field          = $this->getProjectSetting('zip-field-name');

        $fips_event         = $this->getProjectSetting('fips-event');
        $project_id         = $this->getProjectId();

        $batch_timestamp = strftime('%F %T');

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
            $this->fips_form['fips_match_status'],
            $this->fips_form['fips_linkage_id'],
            $this->getProjectId(), 
            $address_field,
            MATCH_STATUS_IN_PROCESS
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

                $fips_linkage_id = $this->getUniqueLinkageIdREDCap();
            }

            $this->singleAddressFieldParser($x['fips_address'], $street, $city, $state, $zip);

            $addressData = [

                $this->fips_form['fips_linkage_id'] => $fips_linkage_id,
                $this->fips_form['fips_address_original'] => $x['fips_address'],
                $this->fips_form['fips_address_street'] => $street,
                $this->fips_form['fips_address_city'] => $city,
                $this->fips_form['fips_address_state'] => $state,
                $this->fips_form['fips_address_zip'] => $zip
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
                            $this->fips_form['fips_address_timestamp'] => $batch_timestamp,
                            $this->fips_form['fips_address_updated'] => '1'
                        ]
                    ]
                ];
            } else {

                $z = [
                    $record => [
    
                        $fips_event => [
                            $this->fips_form['fips_address_updated'] => '0'
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

        print "<p>--- FIPS ADDRESS UPDATER ---<p>"
        . "<br>{$N} addresses processed."
        . "<br>{$Nu} FIPS address record(s) updated."
        ;
    
    }

    private function saveAddressData( $xx ){

        $rc = REDCap::saveData(
            $this->getProjectId(),
            'array',
            $xx
        );

        print "<p>".print_r($rc, true)."</p>";
    }

    /**
     * Assumes address string of the form
     * 
     * street
     * street
     * ... 
     * street 
     * city[,] state zip
     * 
     * state MUST be the 2-char abbrev
     * 
     * @param mixed $address 
     * @param mixed $street 
     * @param mixed $city 
     * @param mixed $state 
     * @param mixed $zip 
     * @return void 
     */
    function singleAddressFieldParser($address, &$street, &$city, &$state, &$zip){

        $states = array(
            'AL',
            'AK',
            'AS',
            'AZ',
            'AR',
            'CA',
            'CO',
            'CT',
            'DE',
            'DC',
            'FM',
            'FL',
            'GA',
            'GU',
            'HI',
            'ID',
            'IL',
            'IN',
            'IA',
            'KS',
            'KY',
            'LA',
            'ME',
            'MH',
            'MD',
            'MA',
            'MI',
            'MN',
            'MS',
            'MO',
            'MT',
            'NE',
            'NV',
            'NH',
            'NJ',
            'NM',
            'NY',
            'NC',
            'ND',
            'MP',
            'OH',
            'OK',
            'OR',
            'PW',
            'PA',
            'PR',
            'RI',
            'SC',
            'SD',
            'TN',
            'TX',
            'UT',
            'VT',
            'VI',
            'VA',
            'WA',
            'WV',
            'WI',
            'WY',
            'AE',
            'AA',
            'AP'
        );
    
        $address = trim($address);
    
        /**
         * convert \r\n and \r to \n
         */
        $address = str_replace("\r", "\n", $address);
        $address = str_replace("\n\n", "\n", $address);
        $address = str_replace("\t", " ", $address);
        $address = str_replace(",", ", ", $address);
        $address = str_replace("  ", " ", $address);
    
        $address_lines = explode("\n", $address);
    
        $nLines = count($address_lines);
    
        $street = "";
        $state = "";
        $city = "";
        $zip = "";
    
        $lnum = 0;
    
        foreach ($address_lines as $address_line){
        
            $lnum++;
        
            if ( $lnum < $nLines ){
        
                if ( $address_line ) {
                    if ($street) $street .= "\n";
                    $street .= $address_line;
                }
            }
            else {
        
                for ($s=0; $s<count($states); $s++){
        
                    $i = stripos($address_line." ", " ".$states[$s]." ");
        
                    if ( $i !== false ){
        
                        $city = trim(substr($address_line, 0, $i), " ,\n\r\t\v\x00");
                        $state = $states[$s];
                        $zip = trim(substr($address_line, $i+4),  " ,\n\r\t\v\x00");
                        break;
                    }
                }
            }
        }         
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

    private function getFipsRecords( $data ){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return $this->getFipsRecordsREDCap( $data );
        }
        else if ( $data_source==="database" ){

            return $this->getFipsRecordsDatabase( $data );
        }
        else return [];
    }

    private function getFipsRecordsREDCap( $data, $limit=5000 ){

        $filter = $data['filter'];

        $record = $data['record'];

        $project_id = $this->getProjectId();

        $event_id = $this->getProjectSetting('fips-event');

        $idfield = $this->getRecordIdField();

        $sql = "
SELECT d.`record`, d.`value` AS `fips_address_timestamp`
	, IFNULL(f1.`value`, '') AS fips_match_status
	, IFNULL(f2.`value`, '') AS fips_match_result
 	, IFNULL(f3.`value`, '') AS fips_code
	, IFNULL(f4.`value`, '') AS fips_state
	, IFNULL(f5.`value`, '') AS fips_county
	, IFNULL(f6.`value`, '') AS fips_tract
	, IFNULL(f7.`value`, '') AS fips_block
	, IFNULL(a1.`value`, '') AS fips_address_original
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
    LEFT JOIN redcap_data a1 ON a1.project_id=d.project_id AND a1.event_id=d.event_id AND a1.`record`=d.`record` AND a1.field_name='fips_address_original'
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

            $sql .= " AND IFNULL(f1.`value`, '0')='0' LIMIT {$limit}";

            //return $sql."\n\n".print_r($params, true);
        }
        else if ( $filter==="nextbatch"){

            $sql .= " AND f1.`value`=?";
            $params[] = MATCH_STATUS_NEXT_API_BATCH;
        }
        else if ( $filter==="inprocess"){

            $sql .= " AND f1.`value`=?";
            $params[] = MATCH_STATUS_IN_PROCESS;
        }
        else if ( $filter==="closed"){

            $sql .= " AND f1.`value`=? LIMIT {$limit}";
            $params[] = MATCH_STATUS_CLOSED;
        }
        else if ( $filter==="record"){

            if ( !$record ) {

                return [];
            }

            $sql .= " AND d.`record`=? LIMIT 1";

            $params[] = $record;
        }
        else if ( $filter==="all") {

            $sql .= " LIMIT {$limit}";
        }
        else if ( $filter==="unmatched"){

            $sql .= " AND f2.`value`='U'";
        }
        else if ( $filter==="matched"){

            $sql .= " AND f2.`value`='M'";
        }
        else {

            return [];
        }


        $xx =  Yes3::fetchRecords( $sql, $params );

        // perform a 'natural sort' on the result
        usort( $xx, function($a, $b){ return strnatcmp($a['record'], $b['record']); });

        return $xx;
    }

    private function getFipsRecordsDatabase( $data ){

        return [];
    }


    private function saveFipsRecord( $data ){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return $this->saveFipsRecordREDCap( $data );
        }
        else if ( $data_source==="database" ){

            return $this->saveFipsRecordDatabase( $data );
        }
        else return [];
    }

    private function saveFipsRecordREDCap( $data ){

        $x = $data['data'];

        $record = $data['record'];

        $event_id = $this->getProjectSetting('fips-event');

        $project_id = $this->getProjectId();

        $x = [
            $record => [
                $event_id => $data['data']
            ]
        ];

        return REDCap::saveData(

            $project_id,
            'array',
            $x,
            'overwrite'
        );
    }

    private function saveFipsRecordDatabase( $data ){

        return "no db support yet";
    }

    private function updateApiBatch(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return $this->updateApiBatchREDCap();
        }
        else if ( $data_source==="database" ){

            return $this->updateApiBatchDatabase();
        }
        else return "EM config error";
    }

    private function updateApiBatchREDCap(){

        $batchSize = (int) $this->getProjectSetting('api-batch-size');

        $event_id = $this->getProjectSetting('fips-event');

        $project_id = $this->getProjectId();

        $current = Yes3::fetchValue("SELECT count(*) FROM redcap_data WHERE project_id=? AND `event_id`=? AND field_name='fips_match_status' AND `value`=?", 
            [$project_id, $event_id, MATCH_STATUS_NEXT_API_BATCH]);

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

        $yy = Yes3::fetchRecords($sql, [$project_id, $event_id, MATCH_STATUS_NEXT_API_BATCH, $remaining]);

        $updates = [];

        foreach( $yy as $y ){

            $updates[$y['record']][$event_id]['fips_match_status'] = MATCH_STATUS_NEXT_API_BATCH;
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

    private function updateApiBatchDatabase(){
    
        return "no db support yet";
    }

    private function getSummary(){

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return $this->getSummaryREDCap();
        }
        else if ( $data_source==="database" ){

            return $this->getSummaryDatabase();
        }
        else return "EM config error";
    }

    private function getSummaryREDCap(){

        $event_id = $this->getProjectSetting('fips-event');
        $project_id = $this->getProjectId();

        $sql = "
        SELECT COUNT(*) as `summary_n`,
            SUM(IF(IFNULL(s.`value`, 0)=0, 1, 0)) AS `summary_pending`,
            SUM(IF(IFNULL(s.`value`, 0)=1, 1, 0)) AS `summary_apibatch`,
            SUM(IF(IFNULL(s.`value`, 0)=2, 1, 0)) AS `summary_inprocess`,
            SUM(IF(IFNULL(s.`value`, 0)=3, 1, 0)) AS `summary_closed`,           
            SUM(IF(IFNULL(s.`value`, 0)=3 AND IFNULL(m.`value`, '')='Match', 1, 0)) AS `summary_closed_matched`,
            SUM(IF(IFNULL(s.`value`, 0)=3 AND IFNULL(m.`value`, '')<>'Match', 1, 0)) AS `summary_closed_unmatched`           
        FROM redcap_data d
            LEFT JOIN redcap_data s ON s.project_id=d.project_id AND s.event_id=d.event_id AND s.record=d.record AND s.field_name='fips_match_status'
            LEFT JOIN redcap_data m ON m.project_id=d.project_id AND m.event_id=d.event_id AND m.record=d.record AND m.field_name='fips_match_result'
        WHERE d.project_id=? AND d.field_name='fips_address_timestamp' AND d.`event_id`=? AND d.`value` IS NOT NULL        
        ";
    
        return Yes3::fetchRecord($sql, [$project_id, $event_id]);
    }

    private function getSummaryDatabase(){
    
        return "no db support yet";
    }
    
    /* -- API -- */

    private function getRecordFromLinkageIdREDCap( $fips_linkage_id ){

        $event_id = $this->getProjectSetting('fips-event');

        $project_id = $this->getProjectId();

        $sql = "
        SELECT d.`record` 
        FROM redcap_data d
        WHERE d.project_id=? AND d.field_name='fips_linkage_id' AND d.`event_id`=? AND d.`value`=?
        LIMIT 1
        ";

        $params = [$project_id, $event_id, $fips_linkage_id];

        return Yes3::fetchValue($sql, $params);
    }

    private function callApi($data){

        $record = $data['record'];

        if ( !$record ){

            $record = '';
        }

        $data_source = $this->getProjectSetting('data-source');

        if ( $data_source==="redcap" ){

            return $this->callApiREDCap($record);
        }
        else if ( $data_source==="database" ){

            return $this->callApiDatabase($record);
        }
        else return "EM config error";
    }

    private function callApiREDCap($record){

        $event_id = $this->getProjectSetting('fips-event');

        $project_id = $this->getProjectId();

        $params = [ $project_id, $event_id ];

        $extraWhere = "";

        if ( $record ){

            $extraWhere = "AND k.`record`=?";
            $params[] = $record;
            $limit = 1;
        }
        else {

            $extraWhere = "AND ms.`value`=?";
            $params[] = MATCH_STATUS_NEXT_API_BATCH;

            $limit = $this->getProjectSetting('api-batch-size');
        }

        $sql = "SELECT k.`value` AS `fips_linkage_id`
            , a2.`value` AS `fips_address_street`
            , a3.`value` AS `fips_address_city`
            , a4.`value` AS `fips_address_state`
            , a5.`value` AS `fips_address_zip`
        FROM redcap_data k
            LEFT  JOIN redcap_data ms ON ms.project_id=k.project_id AND ms.event_id=k.event_id AND ms.`record`=k.`record` AND ms.field_name='fips_match_status'
            INNER JOIN redcap_data a2 ON a2.project_id=k.project_id AND a2.event_id=k.event_id AND a2.`record`=k.`record` AND a2.field_name='fips_address_street'
            INNER JOIN redcap_data a3 ON a3.project_id=k.project_id AND a3.event_id=k.event_id AND a3.`record`=k.`record` AND a3.field_name='fips_address_city'
            INNER JOIN redcap_data a4 ON a4.project_id=k.project_id AND a4.event_id=k.event_id AND a4.`record`=k.`record` AND a4.field_name='fips_address_state'
            INNER JOIN redcap_data a5 ON a5.project_id=k.project_id AND a5.event_id=k.event_id AND a5.`record`=k.`record` AND a5.field_name='fips_address_zip'
        WHERE k.project_id=? AND k.event_id=? AND k.field_name='fips_linkage_id' {$extraWhere}
        ORDER BY 0+k.`value`
        LIMIT {$limit}";

        $yy = Yes3::fetchRecords($sql, $params);

        $temp_file_name = tempnam(sys_get_temp_dir(), 'fips') . '.csv';

        //return $temp_file_name;

        $fp = fopen( $temp_file_name, 'w' );

        for($i=0; $i<count($yy); $i++){

            fputcsv($fp, $yy[$i]);
        }

        fclose($fp);

        //return file_get_contents( $temp_file_name );

        $geoData = $this->geocodeAddressFile($temp_file_name);

        $records = [];

        $timestamp = strftime('%F %T');

        $n = 0;
        $nClosed = 0;
        $nMatchedExact = 0;
        $nMatchedNonExact = 0;
        $nUnmatched = 0;

        foreach( $geoData as $geoRecord ){

            $n++;

            if ( $geoRecord['fips_linkage_id'] ){

                $record = $this->getRecordFromLinkageIdREDCap($geoRecord['fips_linkage_id']);

                if ( $geoRecord['fips_match_type']==='Exact' ){

                    $nMatchedExact++;

                    $geoRecord['fips_match_status'] = MATCH_STATUS_CLOSED;
                }
                else if ( $geoRecord['fips_match_type']==='Non_Exact' ){

                    $nMatchedNonExact++;

                    $geoRecord['fips_match_status'] = MATCH_STATUS_IN_PROCESS;
                }
                else {

                    $nUnmatched++;

                    $geoRecord['fips_match_status'] = MATCH_STATUS_IN_PROCESS;
                }

                $geoRecord['fips_match_user'] = $this->getUser()->getUsername();
                $geoRecord['fips_match_timestamp'] = $timestamp;

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

            $this->setProjectSetting('api-batch-size', DEFAULT_API_BATCH_SIZE);
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

        $params = [$project_id, $event_id, MATCH_STATUS_NEXT_API_BATCH];

        $yy = Yes3::fetchRecords($sql, $params);

        $records = [];

        $n = 0;

        foreach($yy as $y){
            $n++;
            $records[ $y['record'] ][ $event_id ]['fips_match_status'] = MATCH_STATUS_PENDING;
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
                        
        else return "No can do: the action '{$action}' is most abhorrent.";
    }
}