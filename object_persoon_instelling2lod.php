<?php
/**
 * Create a turtle file from the 'relations/object_persoon_instelling.csv' file
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
$fileToRead = __DIR__ . '/input/relations/object_persoon_instelling.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/object_persoon_instelling.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$archiefBaseUri = 'https://nago.nl/archief/%s';
$pubBaseUri = 'https://nago.nl/publicatie/%s';
$objectBaseUri = 'https://nago.nl/object/%s';
$agentBaseUri = 'https://nago.nl/agent/%s';
\EasyRdf\RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm/');
\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');
\EasyRdf\RdfNamespace::set('bf', 'http://id.loc.gov/ontologies/bibframe/');
\EasyRdf\RdfNamespace::set('dc', 'http://purl.org/dc/elements/1.1/');

foreach ($records as $row) {
    # create RecordSet
    $objectUri = sprintf($objectBaseUri, remove_utf8_bom($row[2]));
    $object = $graph->resource($objectUri, 'crm:E22_Human-Made_Object');
    # create Agent
    $agentUri = sprintf($agentBaseUri, remove_utf8_bom($row[1]));
    $agent = $graph->resource($agentUri, 'crm:Agent');
    # ik gok dat rol 0 dc:subject betekent ... vervaardiging staat elders in de database
    if ($row[0] == 0) { $graph->add($object, 'dc:subject', $agent); } 
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;