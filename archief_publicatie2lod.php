<?php
/**
 * Create a turtle file from the 'relations/archief_publicatie.csv' file
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
$fileToRead = __DIR__ . '/input/relations/archief_publicatie.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/archief_publicatie.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$archiefBaseUri = 'https://nago.nl/archief/%s';
$pubBaseUri = 'https://nago.nl/publicatie/%s';
$objectBaseUri = 'https://nago.nl/object/%s';
\EasyRdf\RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm/');
\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');
\EasyRdf\RdfNamespace::set('bf', 'http://id.loc.gov/ontologies/bibframe/');
\EasyRdf\RdfNamespace::set('dc', 'http://purl.org/dc/elements/1.1/');

foreach ($records as $row) {
    $archiefUri = sprintf($archiefBaseUri, remove_utf8_bom($row[0]));
    $archief = $graph->resource($archiefUri, 'rico:RecordSet');

    $pubUri = sprintf($pubBaseUri, remove_utf8_bom($row[1]));
    $pub = $graph->resource($pubUri, 'bf:Instance');

    $graph->add($archief, 'dc:relation', $pub);
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;