<?php
/**
 * Create a turtle file from the 'tentoonstelling.csv' file
 */

declare(strict_types=1);

use League\Csv\Reader;
use League\Csv\Statement;

require_once __DIR__ . '/vendor/autoload.php';

function remove_utf8_bom($text)
{
    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

# read csv-file
$fileToRead = __DIR__ . '/input/tentoonstelling.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/tentoonstelling.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/tentoonstelling/%s';

\EasyRdf\RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
\EasyRdf\RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
\EasyRdf\RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm/');

foreach ($records as $row) {

    # create HumanMadeObject
    $exhibitUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0]));
    $exhibit = $graph->resource($exhibitUri, 'crm:E7_Activity');
    $graph->add($exhibit, 'crm:P2_has_type', $graph->resource('http://vocab.getty.edu/aat/300054766')) ;

    $graph->add($exhibit, 'rdfs:label', remove_utf8_bom($row[3]));

    # create and relate Title
    $titleUri = $exhibitUri."/title";
    $title = $graph->resource($titleUri, 'crm:E33_E41_Linguistic_Appellation');
    $graph->add($title, 'crm:P190_has_symbolic_content', remove_utf8_bom($row[3]));
    $graph->add($exhibit, 'crm:P1_is_identified_by', $title);

    $timespan = $graph->newBNode('crm:E52_Time-Span');
    $graph->add($timespan, 'crm:P82a_begin_of_the_begin', remove_utf8_bom($row[1]));
    $graph->add($timespan, 'crm:P82b_end_of_the_end', remove_utf8_bom($row[2]));
    $graph->add($exhibit, 'crm:P4_has_time-span', $timespan);
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;