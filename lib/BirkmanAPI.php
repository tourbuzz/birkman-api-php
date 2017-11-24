<?php

use Amenadiel\JpGraph\Graph;
use Amenadiel\JpGraph\Plot;
use Amenadiel\JpGraph\Themes;

class BirkmanAPI
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getAssessmentCatalog()
    {
        $xmlTemplate = 'GetAssessmentCatalog.xml';
        $xmlFile = __DIR__ . "/birkman-xml-templates/{$xmlTemplate}";
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) throw new Exception("Unable to load XML file: {$xmlFile}");

        // Configure API KEY
        $oa = $xml->children('http://www.openapplications.org/oagis/9');
        $oa->ApplicationArea->Sender->AuthorizationID = $this->apiKey;

        // send request
        $xmlAsString = $xml->asXML();

        $xmlStream = fopen('php://memory','r+');
        fwrite($xmlStream, $xmlAsString );
        $dataLength = ftell($xmlStream);
        rewind($xmlStream);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.birkman.com/xml-3.0-catalog.php',
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [ 'Content-Type: text/xml' ],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_INFILE         => $xmlStream,
            CURLOPT_INFILESIZE     => $dataLength,
            CURLOPT_UPLOAD         => 1,
        ]);

        $response = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false)
        {
            throw new Exception("cURL error {$curlErrNo} reported.");
        }
        if (!($httpCode >= 200 && $httpCode < 300))
        {
            throw new Exception("Birkman returned HTTP code {$httpCode} and body: " . PHP_EOL . $response);
        }

        /*
         *
         *  <ShowAssessmentCatalog xmlns="http://www.hr-xml.org/3" xmlns:oa="http://www.openapplications.org/oagis/9" releaseID="3.0" versionID="1.4" systemEnvironmentCode="Production" languageCode="en-US">
         *    <DataArea>
         *        <oa:Show/>
         *            <AssessmentCatalog>
         *                  <AssessmentPackage>
         *                          <AssessmentFulfillment>
         *                                    <!-- Available report formats for your account are in the UserArea of this section -->
         *                                              <UserArea>
         *                                                          <PDFFormatIDs>
         *                                                                        <PDFFormatID name="A guide for your sales mgr (Insights/Ind.)" languageID="en-US" type="I">2402319</PDFFormatID>
         *
         */
        $assessmentCatalog = simplexml_load_string($response);
        if ($assessmentCatalog === false)
        {
            throw new Exception("Error parsing response string:\n{$response}\n");
        }

        $catalogResponse = [];

        $assessmentCatalog->registerXPathNamespace('birkman', 'http://www.hr-xml.org/3');
		$result = $assessmentCatalog->xpath('//birkman:PDFFormatID');
        foreach ($result as $catalogEntry) {
            $pdfFormatId = (int) $catalogEntry;
            $pdfReportName = (string) $catalogEntry['name'];
            $pdfReportType = (string) $catalogEntry['type'];
            $catalogResponse[$pdfFormatId] = "[{$pdfReportType}] {$pdfReportName}";
        }

        return $catalogResponse;
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
        $curlErrNo = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false)
        {
            throw new Exception("cURL error {$curlErrNo} reported.");
        }
        if (!($httpCode >= 200 && $httpCode < 300))
        {
            throw new Exception("Birkman returned HTTP code {$httpCode} and body: " . PHP_EOL . $response);
        }

        return $response;
    }

    public function getUserCoreData($userId)
    {
        $coreReportId = 2402262;    // not sure if this reportId is the same report for any Birkman account/catalog? pretty random ID number...
        $xml = simplexml_load_string($this->getReportData($userId, $coreReportId));
        //$xml = simplexml_load_file(__DIR__ . '/../grid.out.xml');

        // register namespace
        $xml->registerXPathNamespace('oa', 'http://www.hr-xml.org/3');
		$result = $xml->xpath('//oa:GivenName');

        $birkmanData = [];
        $birkmanData['name'] = $xml->xpath('//oa:GivenName')[0] . ' ' . $xml->DataArea->AssessmentReport->AssessmentSubject->PersonName->FamilyName;
        foreach ($xml->DataArea->AssessmentReport->AssessmentResult->AssessmentDetailedResult as $score) {
            $item = (string) $score->Score->ScoreText;
            $value = (string) $score->Score->ScoreNumeric;

            list($section, $subsection) = explode('_', $item, 2);
            switch ($section) {
            case 'component':
                $birkmanData['components'][$subsection] = $value;
                break;
            case 'grid':
                $birkmanData['grid'][$subsection] = $value;
                break;
            }
        }

      return $birkmanData;
    }

    private function getComponentVerbiage()
    {
        $json = file_get_contents(__DIR__ . '/../lib/component-verbiage.json');
        if ($json === false) {
            throw new Exception("couldn't load component-verbiage.json");
        }
        $components = json_decode($json, true);
        if ($components === NULL) {
            throw new Exception("couldn't parse component-verbiage.json");
        }
        ksort($components);
        return $components;
    }

    public function getComponentPrettyLabels()
    {
        $components = $this->getComponentVerbiage();
        return array_map(function($component) { return $component['label']; }, $components);
    }

    /**
     * User A talks to user B... what's important to know?
     */
    public function getAlastairsComparativeReport($userAData, $userBData)
    {
        $components = $this->getComponentVerbiage();

        // find components with major diff's between a.usual and b.need
        $componentComparison = [];
        foreach ($components as $component => $componentData) {
            $userAUsual = $userAData["components"]["{$component}_usual"];
            $userBNeed = $userBData["components"]["{$component}_need"];
            $componentDiff = abs($userAUsual - $userBNeed);
            $userAUsualRange = $userAUsual >= 50 ? 'high' : 'low';
            $userBNeedRange = $userBNeed >= 50 ? 'high' : 'low';
            $componentComparison[$component] = [
                'component'            => $component,
                'yourUsual'            => $userAUsual,
                'theirNeed'            => $userBNeed,
                'diff'                 => $componentDiff,
                'yourUsualExplanation' => $components[$component]['usual'][$userAUsualRange],
                'theirNeedExplanation' => $components[$component]['need'][$userBNeedRange]
            ];
        }
        // sort by order of descending diff
        uasort($componentComparison, function($a, $b) { return $a['diff'] < $b['diff']; });
        $userAUsuals = array_map(function($component) {
            return $component['yourUsual'];
        }, $componentComparison);
        $userBNeeds = array_map(function($component) use ($componentComparison) {
            return $component['theirNeed'];
        }, $componentComparison);

		// Setup the graph
		$graph = new Graph\Graph(700,700);
		$graph->SetScale("textlin");

		$theme_class = new Themes\UniversalTheme;

		$graph->SetTheme($theme_class);
        $graph->xaxis->SetLabelAngle(90);
        $graph->legend->SetPos(0.5,0.98,'center','bottom');
		$graph->img->SetAntiAliasing(false);
		$graph->title->Set("Alastair's Magic Birkman Graph");
		$graph->SetBox(false);

		$graph->img->SetAntiAliasing();

		$graph->yaxis->HideZeroLabel();
		$graph->yaxis->HideLine(false);
		$graph->yaxis->HideTicks(false,false);

		$graph->xgrid->Show();
		$graph->xgrid->SetLineStyle("solid");
        $componentLabels = array_values(array_map(function($component) { return $component['label']; }, $components));
		$graph->xaxis->SetTickLabels($componentLabels);
		$graph->xgrid->SetColor('#E3E3E3');

		// Create the first line
		$p1 = new Plot\LinePlot(array_values($userAUsuals));
		$graph->Add($p1);
		$p1->SetColor("#6495ED");
        $p1->mark->SetType(MARK_FILLEDCIRCLE, '', 1);
        $p1->mark->SetFillColor('#6495ED');
		$p1->SetLegend("{$userAData['name']} Usual Behavior");

		// Create the second line
		$p2 = new Plot\LinePlot(array_values($userBNeeds));
		$graph->Add($p2);
		$p2->SetColor("#B22222");
        $p2->mark->SetType(MARK_FILLEDCIRCLE, '', 1);
        $p2->mark->SetFillColor('#B22222');
		$p2->SetLegend("{$userBData['name']} Needs");

		$graph->legend->SetFrameWeight(1);

		// Render graph to file
        $tmpfile = tempnam(sys_get_temp_dir(), 'birkman_'); // @todo move to tempalicious so it cleans up for free -- right now this leaves trash
		$graph->Stroke($tmpfile);
        // @todo REMOVE TEMP for testing...
        $ok = copy($tmpfile, __DIR__.'/../alastair-graph.png');
        if (!$ok) throw new Exception("failed to copy");
        // END temp

        return [
            'graphImg' => file_get_contents($tmpfile),
            'criticalComponents' => array_filter($componentComparison, function($component) { return $component['diff'] > 20; })
        ];
    }
}
