<?php


namespace Yale\Yes3Fips;

class FIPS {

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
            "fips_census_block_group" => "",
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
}