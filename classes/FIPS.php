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
    static function singleAddressFieldParser($address, &$street, &$city, &$state, &$zip){

        $states = [
            ['name' => 'ALABAMA',              'abbrev' => 'AL'],
            ['name' => 'ALASKA	',             'abbrev' => 'AK'],
            ['name' => 'AMERICAN SAMOA	',     'abbrev' => 'AS'],
            ['name' => 'ARIZONA',              'abbrev' => 'AZ'],
            ['name' => 'ARKANSAS',             'abbrev' => 'AR'],
            ['name' => 'CALIFORNIA',           'abbrev' => 'CA'],
            ['name' => 'COLORADO',             'abbrev' => 'CO'],
            ['name' => 'CONNECTICUT',          'abbrev' => 'CT'],
            ['name' => 'DELAWARE',             'abbrev' => 'DE'],
            ['name' => 'DISTRICT OF COLUMBIA', 'abbrev' => 'DC'],
            ['name' => 'FLORIDA',              'abbrev' => 'FL'],
            ['name' => 'GEORGIA',              'abbrev' => 'GA'],
            ['name' => 'GUAM',                 'abbrev' => 'GU'],
            ['name' => 'HAWAII',               'abbrev' => 'HI'],
            ['name' => 'IDAHO',                'abbrev' => 'ID'],
            ['name' => 'ILLINOIS',             'abbrev' => 'IL'],
            ['name' => 'INDIANA',              'abbrev' => 'IN'],
            ['name' => 'IOWA',                 'abbrev' => 'IA'],
            ['name' => 'KANSAS',               'abbrev' => 'KS'],
            ['name' => 'KENTUCKY',             'abbrev' => 'KY'],
            ['name' => 'LOUISIANA',            'abbrev' => 'LA'],
            ['name' => 'MAINE',                'abbrev' => 'ME'],
            ['name' => 'MARYLAND',             'abbrev' => 'MD'],
            ['name' => 'MASSACHUSETTS',        'abbrev' => 'MA'],
            ['name' => 'MICHIGAN',             'abbrev' => 'MI'],
            ['name' => 'MINNESOTA',            'abbrev' => 'MN'],
            ['name' => 'MISSISSIPPI',          'abbrev' => 'MS'],
            ['name' => 'MISSOURI',             'abbrev' => 'MO'],
            ['name' => 'MONTANA',              'abbrev' => 'MT'],
            ['name' => 'NEBRASKA',             'abbrev' => 'NE'],
            ['name' => 'NEVADA',               'abbrev' => 'NV'],
            ['name' => 'NEW HAMPSHIRE',        'abbrev' => 'NH'],
            ['name' => 'NEW JERSEY',           'abbrev' => 'NJ'],
            ['name' => 'NEW MEXICO',           'abbrev' => 'NM'],
            ['name' => 'NEW YORK',             'abbrev' => 'NY'],
            ['name' => 'NORTH CAROLINA',       'abbrev' => 'NC'],
            ['name' => 'NORTH DAKOTA',         'abbrev' => 'ND'],
            ['name' => 'NORTHERN MARIANA IS',  'abbrev' => 'MP'],
            ['name' => 'OHIO',                 'abbrev' => 'OH'],
            ['name' => 'OKLAHOMA',             'abbrev' => 'OK'],
            ['name' => 'OREGON',               'abbrev' => 'OR'],
            ['name' => 'PENNSYLVANIA',         'abbrev' => 'PA'],
            ['name' => 'PUERTO RICO',          'abbrev' => 'PR'],
            ['name' => 'RHODE ISLAND',         'abbrev' => 'RI'],
            ['name' => 'SOUTH CAROLINA',       'abbrev' => 'SC'],
            ['name' => 'SOUTH DAKOTA',         'abbrev' => 'SD'],
            ['name' => 'TENNESSEE',            'abbrev' => 'TN'],
            ['name' => 'TEXAS',                'abbrev' => 'TX'],
            ['name' => 'UTAH',                 'abbrev' => 'UT'],
            ['name' => 'VERMONT',              'abbrev' => 'VT'],
            ['name' => 'VIRGINIA',             'abbrev' => 'VA'],
            ['name' => 'VIRGIN ISLANDS',       'abbrev' => 'VI'],
            ['name' => 'WASHINGTON',           'abbrev' => 'WA'],
            ['name' => 'WEST VIRGINIA',        'abbrev' => 'WV'],
            ['name' => 'WISCONSIN',            'abbrev' => 'WI'],
            ['name' => 'WYOMING',              'abbrev' => 'WY']
        ];

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
        
                    $matchtext = [];
                    if ( !$i = preg_match("/[, ]{$states[$s]['abbrev']}[, ]/i", $address_line, $matchtext) ) {

                        $i = preg_match("/[, ]{$states[$s]['name']}[, ]/i", $address_line, $matchtext);
                    }
        
                    if ( $i ){

                        $j = stripos($address_line, $matchtext[0]);
        
                        $city = trim(substr($address_line, 0, $j), " ,\n\r\t\v\x00");
                        $state = $states[$s]['abbrev'];
                        $zip = trim(substr($address_line, $j+strlen($matchtext[0])),  " ,\n\r\t\v\x00");
                        break;
                    }
                }
            }
        }
        
        return ( $street && $city && $state && $zip ) ? true:false;
    }

    static function getProjectId(){

        return ExternalModules::getProjectId();
    }

    static function getProjectSetting($setting){

        return ExternalModules::getProjectSetting(self::MODULE_DIRECTORY_PREFIX, ExternalModules::getProjectId(), $setting);
    }

}