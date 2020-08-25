<?php
/**
 * Create a turtle file from the 'relations/archief_persoon_instelling.csv' file
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
$fileToRead = __DIR__ . '/input/relations/object_afbeelding.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/object_afbeelding.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/object/%s';
$imgBaseUri = 'http://nago.test.mmvi.nl/visuals/objects/large/%s';
\EasyRdf\RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
\EasyRdf\RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm/');

foreach ($records as $row) {
    # create Object
    $objectUri = sprintf($nagoBaseUri, remove_utf8_bom($row[1]));
    $object = $graph->resource($objectUri, 'crm:E22_Human-Made_Object');
    # create Image
    $imgPath = remove_utf8_bom($row[2]);
    $imgPathEscaped = str_replace(" ","%20",$imgPath);
    $imgUri = sprintf($imgBaseUri, $imgPathEscaped);
    $img = $graph->resource($imgUri, 'crm:E22_Human-Made_Object');
    $graph->add($object, 'foaf:depiction', $img);
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;