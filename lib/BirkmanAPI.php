<?php

class BirkmanAPI
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getReportData($forUserId, $reportId)
    {
        $GetAssessmentReportXMLTemplate = 'GetAssessmentReport.xml';
        $xmlFile = __DIR__ . "/birkman-xml-templates/{$GetAssessmentReportXMLTemplate}";
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) throw new Exception("Unable to load XML file: {$xmlFile}");

        // Configure API KEY
        $oa = $xml->children('http://www.openapplications.org/oagis/9');
        $oa->ApplicationArea->Sender->AuthorizationID = $this->apiKey;

        // CONFIGURE user id
        $xml->DataArea->AssessmentReport->DocumentID = $forUserId;

        // CONFIGURE CSV
        $xml->DataArea->AssessmentReport->UserArea->CSVOutput = 1;

        // CONFIGURE REPORT ID
        $xml->DataArea->AssessmentReport->UserArea->PDFFormatID = $reportId;

        // send request
        $xmlAsString = $xml->asXML();

        $xmlStream = fopen('php://memory','r+');
        fwrite($xmlStream, $xmlAsString );
        $dataLength = ftell($xmlStream);
        rewind($xmlStream);

        $ch = curl_init(); 
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.birkman.com/xml-3.0-report.php',
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [ 'Content-Type: text/xml' ],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_INFILE         => $xmlStream,
            CURLOPT_INFILESIZE     => $dataLength,
            CURLOPT_UPLOAD         => 1,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
