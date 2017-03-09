<?php

require 'lib/BirkmanAPI.php';

$args = array_slice($argv, 1);
if (count($args) !== 2)
{
    print "Usage: get-report.php PERSON_ID REPORT_ID" . PHP_EOL;
    print_r($args);
    exit(1);
}
list($userId, $reportId) = $args;

$birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
$report = $birkman->getReportData($userId, $reportId);
print $report;
