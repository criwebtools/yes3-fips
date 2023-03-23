<?php 

//exit('this function has been disabled.');

define('LOG_DEBUG_MESSAGES', '1');

$module = new Yale\Yes3Fips\Yes3Fips();

$io = new Yale\Yes3Fips\FIOREDCap();

use Yale\Yes3Fips\Yes3;

use Yale\Yes3Fips\FIPS;

$project_id = FIPS::getProjectID();

$event_id = FIPS::getProjectSetting('fips-event');

$sql1 = "select log_event_table from redcap_projects where project_id=?";

$log_event_table = Yes3::fetchValue($sql1, [ $project_id ]);

$sql = "
select a.pk as `record`, 

  DATE_FORMAT(a.ts, '%Y-%m-%d %H:%i:%s') as fips_address_timestamp,
  
  substring(a.data_values, 
    locate('\'', a.data_values, locate('pt_addr1', a.data_values)) + 1,
    locate('\'', a.data_values, locate('\'', a.data_values, locate('pt_addr1', a.data_values)) + 1) - locate('\'', a.data_values, locate('pt_addr1', a.data_values)) - 1
  ) as `fips_address`

from {$log_event_table} a
  inner join redcap_data d on d.project_id=a.project_id and d.record=a.pk and d.field_name='trk_enrolled' and d.value='1'
  
where a.project_id=?
  and a.data_values like '%pt_addr1%'
  and a.`event` in('INSERT', 'UPDATE')
  and a.pk not like 'TST%'
  
order by a.`pk`, a.`ts`";

//die($sql);

$xx = Yes3::fetchRecords($sql, [ $project_id ]);

$yy = [];

$record = ".";

$n = 0;

foreach($xx as $x){

    if ( $x['record'] !== $record ){

        $n++;

        $street = "";
        $state = "";
        $city = "";
        $zip = "";

        $record = $x['record'];
        $fips_address = $x['fips_address'];
        $fips_address_timestamp = $x['fips_address_timestamp'];
        $linkage_id = $io->genLinkageID($project_id, $event_id);
        FIPS::singleAddressFieldParser($fips_address, $street, $city, $state, $zip);

        print "<p>" 

        . "--- {$n} --- {$record} --- {$linkage_id} --- {$fips_address_timestamp} ---"

        . "<br>" . nl2br($fips_address)

        . "</p>";


        $rc = REDCap::saveData(
            $project_id,
            'array',
            [
                $record => [
                    $event_id => [
                        
                        'fips_linkage_id' => $linkage_id,
                        'fips_address' => $fips_address,
                        'fips_address_street'=>$street,
                        'fips_address_state'=>$state,
                        'fips_address_city'=>$city,
                        'fips_address_zip'=>$zip,
                        'fips_address_timestamp' => $fips_address_timestamp,
                        'fips_match_status' => '0',
                        'fips_comment' => '',
                        'fips_address_updated' => '1',
                        
                        //'fips_linkage_id' => '',
                        //'fips_address' => '',
                        //'fips_address_street'=>'',
                        //'fips_address_state'=>'',
                        //'fips_address_city'=>'',
                        //'fips_address_zip'=>'',
                        //'fips_address_timestamp' => '',
                        //'fips_match_status' => '',
                        //'fips_comment' => '',
                        //'fips_address_updated' => '',                      

                        'fips_match_user'=>'',
                        'fips_match_timestamp'=>'',
                        'fips_address_submitted'=>'',
                        'fips_match_result'=>'',
                        'fips_match_type'=>'',
                        'fips_address_matched'=>'',

                        'fips_state'=>'',
                        'fips_county'=>'',
                        'fips_tract'=>'',
                        'fips_block'=>'',
                        'fips_census_block_group'=>'',
                        'fips_code'=>'',

                        'fips_longitude'=>'',
                        'fips_latitude'=>'',

                        'fips_tigerlineid'=>'',
                        'fips_tigerlineside'=>'',

                        'fips_save_user'=>'',
                        'fips_save_timestamp'=>'',

                        'fips_archive_record'=>'',
                        'fips_archive_timestamp'=>'',

                        'fips_history_id'=>'',

                        'fips_complete'=>''

                    ]
                ]
            ],
            'overwrite',
            'YMD',
            'flat',
            NULL,
            FALSE
        );

        if ( $rc['errors'] ){

            exit( print_r($rc, true) );
        }
        

    }
}

print $n;


?>