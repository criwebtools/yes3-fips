<?php

namespace Yale\Yes3Fips;

use Exception;
use REDCap;
use ExternalModules\ExternalModules;
use Yale\Yes3Fips\Yes3;
use Yale\Yes3Fips\GeoRecord;

class FIPS {

    public const MODULE_DIRECTORY_PREFIX = 'yes3_fips';

    /**
     * Returns a geoDataRecord from a CSV row returned by the batch API
     */
    
    static function getGeoDataRecordFromApiCsvRow( $apiCsvRow ): array {

        $geoRecord = new GeoRecord();

        $k = count( $apiCsvRow );

        if ( $k < 3 ) {

            return [];
        }

        //Yes3::logDebugMessage(0, print_r($apiCsvRow, true), 'getGeoDataRecordFromApiCsvRow' );

        $geoRecord->put_fips_linkage_id( $apiCsvRow[0] );
        $geoRecord->put_fips_address_submitted( $apiCsvRow[1] );
        $geoRecord->put_fips_match_result( $apiCsvRow[2] );

        if ( $k >= 4 ) $geoRecord->put_fips_match_type( $apiCsvRow[3] );
        if ( $k >= 5 ) $geoRecord->put_fips_address_matched( $apiCsvRow[4] );
        if ( $k >= 6 ) {

            $longlat = explode(',', $apiCsvRow[5]);

            $geoRecord->put_fips_longitude( $longlat[0] );
            $geoRecord->put_fips_latitude( $longlat[1] );
        }
        if ( $k >= 7 ) $geoRecord->put_fips_tigerlineid( $apiCsvRow[6] );
        if ( $k >= 8 ) $geoRecord->put_fips_tigerlineside( $apiCsvRow[7] );
        if ( $k >= 9 ) $geoRecord->put_fips_state( $apiCsvRow[8] );
        if ( $k >= 10 ) $geoRecord->put_fips_county( $apiCsvRow[9] );
        if ( $k >= 11 ) $geoRecord->put_fips_tract( $apiCsvRow[10] );
        if ( $k >= 12 ) $geoRecord->put_fips_block( $apiCsvRow[11] );

        return $geoRecord->put_geo_data();
    }

    /**
     * apiObject: the decoded object returned by the single address/location API
     * matched_address_summary: a more detailed summary of the matched address(es), from Yes3Fips->geocodeSingleAddress()
     * same_fips_code: all matched addresses returned the same FIPS code
     * addressMatchesIndex: the index of the address having the best match
     * 
     * @param mixed $apiObject 
     * @param string $matched_address_summary 
     * @param bool $same_fips_code 
     * @param int $addressMatchesIndex 
     * @return array 
     */
    static function getGeoDataRecordFromApiObject( $apiObject, $matched_address_summary="", $same_fips_code=false, $addressMatchesIndex=0 ): array{

        $geoRecord = new GeoRecord();

        $geoRecord->put_fips_linkage_id( $apiObject['input']['linkage']['fips_linkage_id'] );

        if ( $apiObject['input']['address']['address'] ) {
            
            $geoRecord->put_fips_address_submitted( $apiObject['input']['address']['address'] );

            $fips_match_result = FIO::MATCH_RESULT_UNMATCHED;

            if ( $apiObject['addressMatches'] ){

                if ( count($apiObject['addressMatches']) == 1 ){

                    $fips_match_result = FIO::MATCH_RESULT_MATCHED;
                }
                else if ( count($apiObject['addressMatches']) > 1 ){

                    $fips_match_result = FIO::MATCH_RESULT_TIE;
                }
            }

            $geoRecord->put_fips_match_result($fips_match_result);
        }

        if ( $apiObject['addressMatches'] ) {
            
            $geoRecord->put_fips_match_type( $apiObject['addressMatches'][$addressMatchesIndex]['match_type'] );
            $geoRecord->put_fips_address_matched( $matched_address_summary );
        }

        if ( $apiObject['addressMatches'][$addressMatchesIndex]['coordinates'] ) {

            $geoRecord->put_fips_longitude( $apiObject['addressMatches'][$addressMatchesIndex]['coordinates']['x'] );
            $geoRecord->put_fips_latitude( $apiObject['addressMatches'][$addressMatchesIndex]['coordinates']['y'] );
        }

        if ( $apiObject['addressMatches'][$addressMatchesIndex]['tigerLine'] ) {

            $geoRecord->put_fips_tigerlineid( $apiObject['addressMatches'][$addressMatchesIndex]['tigerLine']['tigerLineId'] );
            $geoRecord->put_fips_tigerlineside( $apiObject['addressMatches'][$addressMatchesIndex]['tigerLine']['side'] );
        }

        if ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'] ) {

            $geoRecord->put_fips_state ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'][0]['STATE'] );
            $geoRecord->put_fips_county( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'][0]['COUNTY'] );
            $geoRecord->put_fips_tract ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'][0]['TRACT'] );
            $geoRecord->put_fips_block ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'][0]['BLOCK'] );
            $geoRecord->put_fips_code  ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['2020 Census Blocks'][0]['GEOID'] );
        }
        else if ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'] ) {

            $geoRecord->put_fips_state ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'][0]['STATE'] );
            $geoRecord->put_fips_county( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'][0]['COUNTY'] );
            $geoRecord->put_fips_tract ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'][0]['TRACT'] );
            $geoRecord->put_fips_block ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'][0]['BLOCK'] );
            $geoRecord->put_fips_code  ( $apiObject['addressMatches'][$addressMatchesIndex]['geographies']['Census Blocks'][0]['GEOID'] );
        }
        else if ( $apiObject['geographies']['2020 Census Blocks'] ) {

            $geoRecord->put_fips_state ( $apiObject['geographies']['2020 Census Blocks'][0]['STATE'] );
            $geoRecord->put_fips_county( $apiObject['geographies']['2020 Census Blocks'][0]['COUNTY'] );
            $geoRecord->put_fips_tract ( $apiObject['geographies']['2020 Census Blocks'][0]['TRACT'] );
            $geoRecord->put_fips_block ( $apiObject['geographies']['2020 Census Blocks'][0]['BLOCK'] );
            $geoRecord->put_fips_code  ( $apiObject['geographies']['2020 Census Blocks'][0]['GEOID'] );
        }
        else if ( $apiObject['geographies']['Census Blocks'] ) {

            $geoRecord->put_fips_state ( $apiObject['geographies']['Census Blocks'][0]['STATE'] );
            $geoRecord->put_fips_county( $apiObject['geographies']['Census Blocks'][0]['COUNTY'] );
            $geoRecord->put_fips_tract ( $apiObject['geographies']['Census Blocks'][0]['TRACT'] );
            $geoRecord->put_fips_block ( $apiObject['geographies']['Census Blocks'][0]['BLOCK'] );
            $geoRecord->put_fips_code  ( $apiObject['geographies']['Census Blocks'][0]['GEOID'] );
        }

        if ( $apiObject['input']['location'] ){

            if ( $geoRecord->get_fips_code() ){

                $geoRecord->put_fips_match_result( FIO::MATCH_RESULT_MATCHED );
                $geoRecord->put_fips_match_type( FIO::MATCH_TYPE_LOCATION );
            }
            else {

                $geoRecord->put_fips_match_result( FIO::MATCH_RESULT_UNMATCHED );
                //$geoRecord->put_fips_match_type( FIO::MATCH_TYPE_LOCATION );
            }

            $geoRecord->put_fips_longitude( $apiObject['input']['location']['x'] );
            $geoRecord->put_fips_latitude ( $apiObject['input']['location']['y'] );
        }

        return $geoRecord->put_geo_data();
    }

    static function getCityStateZip( $s, &$city, &$state, &$zip ): bool {

        $states = [
            'AL' => 'ALABAMA',            
            'AK' => 'ALASKA	',            
            'AS' => 'AMERICAN SAMOA',     
            'AZ' => 'ARIZONA',            
            'AR' => 'ARKANSAS',           
            'CA' => 'CALIFORNIA',         
            'CO' => 'COLORADO',           
            'CT' => 'CONNECTICUT',        
            'DE' => 'DELAWARE',           
            'DC' => 'DISTRICT OF COLUMBIA',
            'FL' => 'FLORIDA',           
            'GA' => 'GEORGIA',            
            'GU' => 'GUAM',               
            'HI' => 'HAWAII',             
            'ID' => 'IDAHO',              
            'IL' => 'ILLINOIS',           
            'IN' => 'INDIANA',            
            'IA' => 'IOWA',               
            'KS' => 'KANSAS',             
            'KY' => 'KENTUCKY',           
            'LA' => 'LOUISIANA',          
            'ME' => 'MAINE',              
            'MD' => 'MARYLAND',           
            'MA' => 'MASSACHUSETTS',      
            'MI' => 'MICHIGAN',           
            'MN' => 'MINNESOTA',          
            'MS' => 'MISSISSIPPI',        
            'MO' => 'MISSOURI',           
            'MT' => 'MONTANA',            
            'NE' => 'NEBRASKA',           
            'NV' => 'NEVADA',             
            'NH' => 'NEW HAMPSHIRE',      
            'NJ' => 'NEW JERSEY',         
            'NM' => 'NEW MEXICO',         
            'NY' => 'NEW YORK',           
            'NC' => 'NORTH CAROLINA',     
            'ND' => 'NORTH DAKOTA',       
            'MP' => 'NORTHERN MARIANA IS', 
            'OH' => 'OHIO',               
            'OK' => 'OKLAHOMA',           
            'OR' => 'OREGON',             
            'PA' => 'PENNSYLVANIA',       
            'PR' => 'PUERTO RICO',        
            'RI' => 'RHODE ISLAND',       
            'SC' => 'SOUTH CAROLINA',     
            'SD' => 'SOUTH DAKOTA',       
            'TN' => 'TENNESSEE',          
            'TX' => 'TEXAS',              
            'UT' => 'UTAH',               
            'VT' => 'VERMONT',            
            'VA' => 'VIRGINIA',           
            'VI' => 'VIRGIN ISLANDS',     
            'WA' => 'WASHINGTON',         
            'WV' => 'WEST VIRGINIA',      
            'WI' => 'WISCONSIN',          
            'WY' => 'WYOMING'        
        ];

        $city = "";
        $state = "";
        $zip = "";

        // make sure a clean string
        $s = trim($s, " ,\n\r\t\v\x00");

        foreach($states as $abbr => $name){
        
            $matchtext = [];
                
            // bounded by white space, BOL or EOL
            if ( !$i = preg_match("/(^|\s){$abbr}($|\s)/i", $s, $matchtext) ) {

                $i = preg_match("/(^|\s){$name}($|\s)/i", $s, $matchtext);
            }

            if ( $i ) {

                $j = stripos($s, $matchtext[0]);

                if ( $j !== false ) {

                    $state = $abbr;

                    if ( $j > 0 ){

                        $city = trim(substr($s, 0, $j));
                    }

                    if (  $j + strlen($matchtext[0]) < strlen($s)){

                        $zip = trim(substr($s, $j+strlen($matchtext[0]) ));
                    }

                    return true;
                }
            }
        }

        return false;
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
     * @return bool 
     */
    static function singleAddressFieldParser($address, &$street, &$city, &$state, &$zip): bool {

        $address = trim($address);
    
        $street = "";
        $state = "";
        $city = "";
        $zip = "";  
        
        $gotState = false;

        /**
         * convert \r to \n for exploding.
         */
        $address = trim(str_replace("\r", "\n", $address), " ,\n\r\t\v\x00");

        /**
         * if no line breaks then we will explode on commas and hope for the best
         */
        $xpchar = "\n";

        if ( strpos($address, $xpchar)===false ){

            $xpchar = ",";
        }

        // build the address lines, ignoring blank lines
        
        $parts = explode($xpchar, $address);

        $address_lines = [];

        foreach($parts as $part){

            if ( $part ) {

                // remove whitespace runs and commas
                $address_lines[] = preg_replace('/(\s|,)+/', ' ', trim($part));
            }
        }

        $nLines = count($address_lines);

        // an address must have at least two lines

        if ( $nLines > 1 ) {

            $lastStreetIndex = 0;

            $gotState = self::getCityStateZip($address_lines[$nLines-1], $city, $state, $zip);

            if ( $gotState ){

                if ( !$city ){

                    $city = $address_lines[$nLines-2];
                    $lastStreetIndex = $nLines-3;
                }
                else {

                    $lastStreetIndex = $nLines-2;
                }

                if ( $lastStreetIndex >= 0 ){

                    $street = $address_lines[0];

                    for ($j=1; $j<=$lastStreetIndex; $j++){

                        $street .= "\n" . $address_lines[$j];
                    }
                }
            }
        }
        
        return $gotState;
    }

    static function getProjectId(){

        return ExternalModules::getProjectId();
    }

    static function getProjectSetting($setting){

        return ExternalModules::getProjectSetting(self::MODULE_DIRECTORY_PREFIX, ExternalModules::getProjectId(), $setting);
    }
}