<?php


namespace Yale\Yes3Fips;

use Exception;
use REDCap;
use ExternalModules\ExternalModules;
use Yale\Yes3Fips\Yes3;

class FIPS {

    public const MODULE_DIRECTORY_PREFIX = 'yes3_fips';

    static function putGeoRecord( $fipsArray ){

        $k = count( $fipsArray );

        if ( $k < 3 ) {

            return;
        }

        $geoRecord = [
            "fips_linkage_id" => "",
            "fips_address_submitted" => "", 
            "fips_match_result" => "",
            "fips_match_type" => "",
            "fips_address_matched" => "",
            "fips_longitude" => "",
            "fips_latitude" => "",
            "fips_tigerlineid" => "",
            "fips_tigerlineside" => "",
            "fips_state" => "",             
            "fips_county" => "",            
            "fips_tract" => "",             
            "fips_block" => "",    
            "fips_code" => "",              
            "fips_census_block_group" => ""
        ];

        $geoRecord['fips_linkage_id'] = $fipsArray[0];
        $geoRecord['fips_address_submitted'] = $fipsArray[1];
        $geoRecord['fips_match_result'] = $fipsArray[2];

        if ( $k >= 4 ) $geoRecord['fips_match_type'] = $fipsArray[3];
        if ( $k >= 5 ) $geoRecord['fips_address_matched'] = $fipsArray[4];
        if ( $k >= 6 ) {

            $longlat = explode(',', $fipsArray[5]);

            $geoRecord['fips_longitude'] = $longlat[0];
            $geoRecord['fips_latitude'] = $longlat[1];
        }
        if ( $k >= 7 ) $geoRecord['fips_tigerlineid'] = $fipsArray[6];
        if ( $k >= 8 ) $geoRecord['fips_tigerlineside'] = $fipsArray[7];
        if ( $k >= 9 ) $geoRecord['fips_state'] = $fipsArray[8];
        if ( $k >= 10 ) $geoRecord['fips_county'] = $fipsArray[9];
        if ( $k >= 11 ) $geoRecord['fips_tract'] = $fipsArray[10];
        if ( $k >= 12 ) $geoRecord['fips_block'] = $fipsArray[11];

        if ( $k > 12 ){

            for($i=11; $i<$k; $i++){

                $geoRecord['col'.($i+1)] = $fipsArray[$i];
            }
        }

        $geoRecord['fips_code'] = 
            str_pad($geoRecord['fips_state'],  2, '0', STR_PAD_LEFT).
            str_pad($geoRecord['fips_county'], 3, '0', STR_PAD_LEFT).
            str_pad($geoRecord['fips_tract'],  6, '0', STR_PAD_LEFT).
            str_pad($geoRecord['fips_block'],  4, '0', STR_PAD_LEFT)
        ;

        $geoRecord['fips_census_block_group'] = 
            str_pad($geoRecord['fips_state'],  2, '0', STR_PAD_LEFT).
            str_pad($geoRecord['fips_county'], 3, '0', STR_PAD_LEFT).
            str_pad($geoRecord['fips_tract'],  6, '0', STR_PAD_LEFT).
            substr($geoRecord['fips_block'], 0, 1)
        ;

        if ( $geoRecord['fips_code'] === str_repeat('0', strlen($geoRecord['fips_code'])) ) {

            $geoRecord['fips_code'] = '';
        }

        if ( $geoRecord['fips_census_block_group'] === str_repeat('0', strlen($geoRecord['fips_census_block_group'])) ) {

            $geoRecord['fips_census_block_group'] = '';
        }

        return $geoRecord;
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