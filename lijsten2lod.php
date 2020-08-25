<?php
/**
 * Create a turtle file from the 'lijsten.csv' file
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
$fileToRead = __DIR__ . '/input/lijsten.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/lijsten.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/lijsten/%s';
\EasyRdf\RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
\EasyRdf\RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
\EasyRdf\RdfNamespace::set('skos', 'http://www.w3.org/2004/02/skos/core#');

foreach ($records as $row) {
    # create Concept
    $lijstenUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0])) ;
    $lijsten = $graph->resource($lijstenUri, 'skos:Concept') ;
    $graph->add($lijsten, 'rdfs:label', remove_utf8_bom($row[2]));
    $graph->add($lijsten, 'skos:prefLabel', remove_utf8_bom($row[2]));

    if ($row[1] != 'NULL') {
    $graph->add($lijsten, 'skos:broader', $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[1])));        
    }

    if ($row[3] != 'NULL' and $row[3] != '') {
    $graph->add($lijsten, 'skos:note', remove_utf8_bom($row[3]));        
    }

}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;