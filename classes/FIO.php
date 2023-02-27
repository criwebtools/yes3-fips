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
    public const ALWAYS = 1;
    public const IF_SINGLE_ADDRESS_FIELD = 2;
    public const IF_MULTIPLE_ADDRESS_FIELDS = 4;

    public const MATCH_RESULT_MATCHED = 'Match';
    public const MATCH_RESULT_UNMATCHED = 'No_Match';

    public const FORM_COMPLETE = '2';
    public const FORM_INCOMPLETE = '1';
    
    public function makeCsvForApiCall(string $record): string;

    public function saveGeoData(array $geoData): string;

    public function getFIPSrecords(array $data, int $limit=5000): array;

    public function assignLinkageIDs(): int;

    public function getStudyIdFromLinkageId(string $linkageId): string;

    public function getLinkageIdFromStudyId(string $studyId): string;
    
    public function saveFIPSrecord(array $data, string $username): string;

    public function updateAPIbatch(): string;
}