<?php

require 'lib/BirkmanAPI.php';

$birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
$report = $birkman->getAssessmentCatalog();
print_r( $report );
