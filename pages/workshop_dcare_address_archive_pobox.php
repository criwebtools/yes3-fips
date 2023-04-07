<?php 

exit('this function has been disabled.');

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

$k = 0;

$fips_address_imported = "";
$fips_address_timestamp_imported = "";

$fips_source_address_history = "";

print "<pre>";

$data = [];

foreach($xx as $x){

    if ( $x['record'] !== $record ){

        if ( $fips_source_address_history ){

            save_fips_source_address_history( $project_id, $record, $event_id, $fips_source_address_history);
        }
       
        $fips_address_imported = $x['fips_address'];

        $fips_address_timestamp_imported = $x['fips_address_timestamp'];

        $record = $x['record'];

        $fips_source_address_history =
        "--- {$fips_address_timestamp_imported} ---"
        . "\n" . $fips_address_imported;

        $k = 0;
    }
    else {

        if ( mb_strcasecmp(normalize_address($x['fips_address']), normalize_address($fips_address_imported)) != 0 ) {

            //print "\n" . '[' . normalize_address($x['fips_address']) . '] [' . normalize_address($fips_address_imported) . ']';

            $k++;

            if ( $k===1 ){
                $n++;
            }

            $fips_source_address_history .= "\n--- {$x['fips_address_timestamp']} ---"
            . "\n" . $x['fips_address'];
        }
    }
}

if ( $fips_source_address_history ){

    save_fips_source_address_history( $project_id, $record, $event_id, $fips_source_address_history);
}


print "\n\n" . $n . " addresses have changed.";

print "</pre>";

function normalize_address( $s ){

    $s = trim(str_replace(["\n", "\r", "\t", ","], [ ' ', ' ', ' ', ' '], $s));
    $s = str_replace('  ', ' ', $s);
    $s = str_replace('  ', ' ', $s);
    $s = str_replace('  ', ' ', $s);
    $s = str_replace('  ', ' ', $s);

    return $s;
}

function save_fips_source_address_history( $project_id, $record, $event_id, $fips_source_address_history){

    $rc = REDCap::saveData(
        $project_id,
        'array',
        [
            $record => [
                $event_id => [                
                    'fips_source_address_history' => $fips_source_address_history,
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

function mb_strcasecmp($str1, $str2, $encoding = null) {
    if (null === $encoding) { $encoding = mb_internal_encoding(); }
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}


?>