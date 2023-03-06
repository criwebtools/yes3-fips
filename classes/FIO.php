<?php

namespace Yale\Yes3Fips;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

interface FIO 
{
    public const MATCH_STATUS_PENDING = '0';
    public const MATCH_STATUS_NEXT_API_BATCH = '1';
    public const MATCH_STATUS_IN_PROCESS = '2';
    public const MATCH_STATUS_CLOSED = '3';

    public const DEFAULT_API_BATCH_SIZE = 50;

    public const NEVER = 0;
    public const IF_SINGLE_ADDRESS_FIELD = 1;
    public const IF_MULTIPLE_ADDRESS_FIELDS = 2;
    public const ALWAYS = 3;

    public const MATCH_RESULT_MATCHED = 'Match';
    public const MATCH_RESULT_UNMATCHED = 'No_Match';
    public const MATCH_RESULT_TIE = 'Tie';

    public const MATCH_TYPE_EXACT = 'Exact';
    public const MATCH_TYPE_FUZZY = 'Non_Exact';

    public const FORM_COMPLETE = '2';
    public const FORM_INCOMPLETE = '1';

    public const QRY_RETURN_RESULTSET = 1;
    public const QRY_RETURN_INSERT_ID = 2;
    public const QRY_RETURN_RETCODE = 3;
    public const QRY_RETURN_ROWS_AFFECTED = 4;
    
    public function makeCsvForApiCall(string $record): string;

    public function saveGeoData(array $geoData): string;

    public function getFIPSrecords(string $filter, string $record, int $limit=5000): array;

    public function assignLinkageIDs(): int;

    public function getStudyIdFromLinkageId(string $linkageId): string;

    public function getLinkageIdFromStudyId(string $studyId): string;
    
    public function saveFIPSrecord(string $record, int $fips_linkage_id, array $x, int $close_editor_on_success, string $username): string;
    
    public function restoreFIPSrecord(int $fips_linkage_id, string $username): string;

    public function updateAPIbatch(): string;

    public function getSummary(): array;
}

?>