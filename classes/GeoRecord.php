<?php

namespace Yale\Yes3Fips;

use Yale\Yes3Fips\Yes3;

class GeoRecord {

    private $fips_linkage_id = "";
    private $fips_address_submitted = ""; 
    private $fips_match_result = "";
    private $fips_match_type = "";
    private $fips_match_status = "";
    private $fips_match_user = "";
    private $fips_match_timestamp = "";
    private $fips_address_matched = "";
    private $fips_longitude = "";
    private $fips_latitude = "";
    private $fips_tigerlineid = "";
    private $fips_tigerlineside = "";
    private $fips_state = "";             
    private $fips_county = "";            
    private $fips_tract = "";             
    private $fips_block = "";    
    private $fips_code = "";              
    private $fips_census_block_group = "";
    private $fips_state_county = "";
    private $fips_complete = "";

    private function ifBlank( $s ){

        if ( strlen($s) ) return $s;
        return "";
    }

    // 15-digit fips code from constituant parts, triggered when a constituant is put
    
    private function calculate_fips_code(){

        if (        $this->fips_state
                &&  $this->fips_county
                &&  $this->fips_tract
                &&  $this->fips_block    
        ){
            $this->fips_code = 
                str_pad($this->fips_state,  2, '0', STR_PAD_LEFT).
                str_pad($this->fips_county, 3, '0', STR_PAD_LEFT).
                str_pad($this->fips_tract,  6, '0', STR_PAD_LEFT).
                str_pad($this->fips_block,  4, '0', STR_PAD_LEFT)
            ;    
        }
    }

    // 12-digit fips census block group from constituant parts, triggered when a constituant is put
    
    private function calculate_fips_census_block_group(){

        if (        $this->fips_state
                &&  $this->fips_county
                &&  $this->fips_tract
                &&  $this->fips_block    
        ){
            $this->fips_census_block_group = 
                str_pad($this->fips_state,  2, '0', STR_PAD_LEFT).
                str_pad($this->fips_county, 3, '0', STR_PAD_LEFT).
                str_pad($this->fips_tract,  6, '0', STR_PAD_LEFT).
                substr($this->fips_block,  0, 1)
            ;    
        }
    }

    // 5-digit fips state+county from constituant parts, triggered when a constituant is put
    
    private function calculate_fips_state_county(){

        if (        $this->fips_state
                &&  $this->fips_county  
        ){
            $this->fips_state_county = 
                str_pad($this->fips_state,  2, '0', STR_PAD_LEFT).
                str_pad($this->fips_county, 3, '0', STR_PAD_LEFT)
            ;    
        }
    }

    // triggered when the 15-digit fips code is put
    
    private function calculate_fips_code_parts(){

        if ( strlen($this->fips_code) === 15 ){

            $this->fips_state = substr($this->fips_code, 0, 2);
            $this->fips_county = substr($this->fips_code, 2, 3);
            $this->fips_state_county = substr($this->fips_code, 0, 5);
            $this->fips_tract = substr($this->fips_code, 5, 6);
            $this->fips_block = substr($this->fips_code, 11, 4);
            $this->fips_census_block_group = substr($this->fips_code, 0, 12);
        }
    }

    // triggered when the 12-digit census block is put
    
    private function calculate_fips_census_block_group_parts(){

        if ( strlen($this->fips_census_block_group) === 12 ){

            $this->fips_state = substr($this->fips_census_block_group, 0, 2);
            $this->fips_county = substr($this->fips_census_block_group, 2, 3);
            $this->fips_state_county = substr($this->fips_code, 0, 5);
            $this->fips_tract = substr($this->fips_census_block_group, 5, 6);
            $this->fips_block = substr($this->fips_census_block_group, 11, 1);
        }
    }

    private function calculate_fips_match_status(){

        if ( $this->fips_match_type === FIO::MATCH_TYPE_EXACT || $this->fips_match_type === FIO::MATCH_TYPE_LOCATION ){

            $this->fips_match_status = FIO::MATCH_STATUS_CLOSED;
        }
        else if ( $this->fips_match_type === FIO::MATCH_TYPE_FUZZY ){

            $this->fips_match_status = FIO::MATCH_STATUS_IN_PROCESS;
        }
        else {

            $this->fips_match_status = FIO::MATCH_STATUS_IN_PROCESS;
        }
    }

    /**
     * putters
     */

    function put_geo_data(): array {

        if ( !$this->fips_linkage_id ){

            return [ 'fips_linkage_id' => '' ];
        }

        $this->fips_match_user = USERID;

        $this->fips_match_timestamp = Yes3::isoTimeStampString();

        $this->calculate_fips_match_status();

        $this->fips_complete = ( $this->fips_match_status === FIO::MATCH_STATUS_CLOSED ) ? FIO::FORM_COMPLETE : FIO::FORM_INCOMPLETE;

        return [
            'fips_linkage_id'          => $this->fips_linkage_id         ,
            'fips_address_submitted'   => $this->fips_address_submitted  ,
            'fips_match_result'        => $this->fips_match_result       ,
            'fips_match_type'          => $this->fips_match_type         ,
            'fips_match_status'        => $this->fips_match_status       ,
            'fips_match_user'          => $this->fips_match_user         ,
            'fips_match_timestamp'     => $this->fips_match_timestamp    ,
            'fips_address_matched'     => $this->fips_address_matched    ,
            'fips_longitude'           => $this->fips_longitude          ,
            'fips_latitude'            => $this->fips_latitude           ,
            'fips_tigerlineid'         => $this->fips_tigerlineid        ,
            'fips_tigerlineside'       => $this->fips_tigerlineside      ,
            'fips_state'               => $this->fips_state              ,
            'fips_county'              => $this->fips_county             ,
            'fips_tract'               => $this->fips_tract              ,
            'fips_block'               => $this->fips_block              ,
            'fips_code'                => $this->fips_code               ,
            'fips_census_block_group'  => $this->fips_census_block_group ,
            'fips_state_county'        => $this->fips_state_county       ,
            'fips_complete'            => $this->fips_complete
        ];
    }

    function put_fips_complete( $s ){

        $this->fips_complete = strval($s);
    }

    function put_fips_linkage_id( $s ){

        $this->fips_linkage_id = $this->ifBlank($s);
    }

    function put_fips_address_submitted( $s ){

        $this->fips_address_submitted = $this->ifBlank($s);
    }

    function put_fips_match_result( $s ){

        $this->fips_match_result = $this->ifBlank($s);
    }

    function put_fips_match_type( $s ){

        $this->fips_match_type = $this->ifBlank($s);
    }

    function put_fips_match_status( $s ){

        $this->fips_match_status = $this->ifBlank($s);
    }

    function put_fips_match_user( $s ){

        $this->fips_match_user = $this->ifBlank($s);
    }

    function put_fips_match_timestamp( $s ){

        $this->fips_match_timestamp = $this->ifBlank($s);
    }

    function put_fips_address_matched( $s ){

        $this->fips_address_matched = $this->ifBlank($s);
    }

    function put_fips_longitude( $s ){

        $this->fips_longitude = $this->ifBlank($s);
    }

    function put_fips_latitude( $s ){

        $this->fips_latitude = $this->ifBlank($s);
    }

    function put_fips_tigerlineid( $s ){

        $this->fips_tigerlineid = $this->ifBlank($s);
    }

    function put_fips_tigerlineside( $s ){

        $this->fips_tigerlineside = $this->ifBlank($s);
    }

    function put_fips_state( $s ){

        $this->fips_state = $this->ifBlank($s);
        $this->calculate_fips_code();
        $this->calculate_fips_census_block_group();
        $this->calculate_fips_state_county();
    }

    function put_fips_county( $s ){

        $this->fips_county = $this->ifBlank($s);
        $this->calculate_fips_code();
        $this->calculate_fips_census_block_group();
        $this->calculate_fips_state_county();
    }

    function put_fips_tract( $s ){

        $this->fips_tract = $this->ifBlank($s);
        $this->calculate_fips_code();
        $this->calculate_fips_census_block_group();
    }

    function put_fips_block( $s ){

        $this->fips_block = $this->ifBlank($s);
        $this->calculate_fips_code();
        $this->calculate_fips_census_block_group();
    }

    function put_fips_code( $s ){

        $this->fips_code = $this->ifBlank($s);
        $this->calculate_fips_code_parts();
    }

    function put_fips_census_block_group( $s ){

        $this->fips_census_block_group = $this->ifBlank($s);
        $this->calculate_fips_census_block_group_parts();
    }

    /**
     * getters
     */

    function get_fips_complete(){

        return $this->fips_complete;
    }

    function get_fips_linkage_id(){

        return $this->fips_linkage_id;
    }

    function get_fips_address_submitted(){

        return $this->fips_address_submitted;
    }

    function get_fips_match_result(){

        return $this->fips_match_result;
    }

    function get_fips_match_type(){

        return $this->fips_match_type;
    }

    function get_fips_match_status( $s ){

        return $this->fips_match_status;
    }

    function get_fips_match_user( $s ){

        return $this->fips_match_user;
    }

    function get_fips_match_timestamp( $s ){

        return $this->fips_match_timestamp;
    }

    function get_fips_address_matched(){

        return $this->fips_address_matched;
    }

    function get_fips_longitude(){

        return $this->fips_longitude;
    }

    function get_fips_latitude(){

        return $this->fips_latitude;
    }

    function get_fips_tigerlineid(){

        return $this->fips_tigerlineid;
    }

    function get_fips_tigerlineside(){

        return $this->fips_tigerlineside;
    }

    function get_fips_state(){

        return $this->fips_state;
    }

    function get_fips_county(){

        return $this->fips_county;
    }

    function get_fips_state_county(){

        return $this->fips_state_county;
    }

    function get_fips_tract(){

        return $this->fips_tract;
    }

    function get_fips_block(){

        return $this->fips_block;
    }

    function get_fips_code(){

        return $this->fips_code;
    }

    function get_fips_census_block_group(){

        return $this->fips_census_block_group;
    }
}