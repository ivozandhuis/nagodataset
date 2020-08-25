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
$fileToRead = __DIR__ . '/input/relations/archief_persoon_instelling.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/archief_persoon_instelling.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/archief/%s';
$agentBaseUri = 'https://nago.nl/agent/%s';
\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');

foreach ($records as $row) {
    # create RecordSet
    $archiefUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0]));
    $archief = $graph->resource($archiefUri, 'rico:RecordSet');
    # create Agent
    $agentUri = sprintf($agentBaseUri, remove_utf8_bom($row[2]));
    $agent = $graph->resource($agentUri, 'rico:Agent');
    if ($row[1] == 1) { $graph->add($archief, 'rico:accumulatedBy', $agent); }
    if ($row[1] == 2) { $graph->add($archief, 'rico:heldBy', $agent); }
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;