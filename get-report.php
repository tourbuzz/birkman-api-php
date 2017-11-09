<?php

require 'lib/BirkmanAPI.php';
require 'lib/BirkmanGrid.php';

$args = array_slice($argv, 1);
if (count($args) !== 2)
{
    print "Usage: get-report.php PERSON_ID REPORT_ID" . PHP_EOL;
    print_r($args);
    exit(1);
}
list($userId, $reportId) = $args;

$birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
//$report = $birkman->getReportData($userId, $reportId);
$birkmanData = $birkman->getUserCoreData('xx');
print_r($birkmanData);

$grid = new BirkmanGrid($birkmanData);
$grid->asPNG('./grid.png');
