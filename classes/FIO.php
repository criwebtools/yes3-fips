<?php

namespace Yale\Yes3Fips;
/*
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
*/
interface FIO 
{
    public const MATCH_STATUS_PENDING = '0';
    public const MATCH_STATUS_NEXT_API_BATCH = '1';
    public const MATCH_STATUS_IN_PROCESS = '2';
    public const MATCH_STATUS_CLOSED = '3';
    public const MATCH_STATUS_PO_BOX = '11';
    public const MATCH_STATUS_DEFERRED = '12';

    public const NEVER = 0;
    public const IF_SINGLE_ADDRESS_FIELD = 1;
    public const IF_MULTIPLE_ADDRESS_FIELDS = 2;
    public const ALWAYS = 3;
    public const IF_SOURCE_DATABASE = 4;
    public const IF_SOURCE_REDCAP = 8;

    public const RANDOM = "random";

    public const MATCH_RESULT_MATCHED = 'Match';
    public const MATCH_RESULT_UNMATCHED = 'No_Match';
    public const MATCH_RESULT_TIE = 'Tie';

    public const MATCH_TYPE_EXACT = 'Exact';
    public const MATCH_TYPE_FUZZY = 'Non_Exact';
    public const MATCH_TYPE_LOCATION = 'Location';

    public const FORM_COMPLETE = '2';
    public const FORM_INCOMPLETE = '1';

    public const QRY_RETURN_RESULTSET = 1;
    public const QRY_RETURN_INSERT_ID = 2;
    public const QRY_RETURN_RETCODE = 3;
    public const QRY_RETURN_ROWS_AFFECTED = 4;

    public const DEFAULT_LIST_LIMIT = 2000;
    public const DEFAULT_LIST_ORDER = "random";

    public const DEFAULT_API_BATCH_SIZE = 50;
    public const DEFAULT_API_BATCH_ORDER = "random";

    public const DEFAULT_RESERVATION_BLOCK_SIZE = 100;
    public const DEFAULT_RESERVATION_SELECTION = "random";
    public const RESERVATION_RESERVED = 1;
    public const RESERVATION_RELEASED = 0;

    public const RECORD_KEY_DATA_TYPE_NUMERIC = "numeric";
    public const RECORD_KEY_DATA_TYPE_STRING = "string";
    public const DEFAULT_RECORD_KEY_DATA_TYPE = "string";
    
    public const DEFAULT_ALLOW_RESERVATIONS = "no";
    
    /**
     * benchmark:   Public_AR_ACS2022,  Public_AR_Current,  Public_AR_Census2020
     * vintage:     Current_ACS2022,    Current_Current,    Census2020_Census2020
     */
    public const GEO_LAYERS = '2020 Census Blocks';
    public const GEO_BENCHMARK_PRIMARY = 'Public_AR_Current';
    public const GEO_BENCHMARK_SECONDARY = 'Public_AR_ACS2022';

    public const GEO_BENCHMARK_VINTAGE = [

        'Public_AR_Current' => 'Current_Current',
        'Public_AR_ACS2022' => 'Current_ACS2022',
        'Public_AR_Census2020' => 'Census2020_Census2020'

    ];
    
    public function makeCsvForApiCall(): string;
    
    public function getAddressForApiCall(string $record): array;
    
    public function getLocationForApiCall(string $record): array;

    public function saveGeoData(array $geoData): string;

    public function getFIPSrecords(string $filter, string $record, string $user): array;

    public function assignLinkageIDs(): int;

    public function getStudyIdFromLinkageId(string $linkageId): string;

    public function getLinkageIdFromStudyId(string $studyId): string;
    
    public function saveFIPSrecord(string $record, int $fips_linkage_id, array $x, int $close_editor_on_success, string $username): string;

    public function archiveFIPSrecord( int $fips_linkage_id ): int;
    
    public function restoreFIPSrecord(int $fips_linkage_id, string $username): string;

    public function updateAPIbatch(): string;

    public function reserveBatch(string $user, int $batch_size): string;
    
    public function releaseBatch(string $user): string;

    public function getSummary(string $user): array;
}

?>