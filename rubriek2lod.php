<?php
/**
 * Create a turtle file from the 'rubriek.csv' file
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
$fileToRead = __DIR__ . '/input/rubriek.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/rubriek.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/';
$nagoArchiefBaseUri = $nagoBaseUri.'archief/%s';
$nagoRubriekBaseUri = $nagoBaseUri.'rubriek/%s';

\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');

foreach ($records as $row) {
    if ($row[5]) { # check "publiceren Y/N"

        # create RecordSet
        $rubriekUri = sprintf($nagoRubriekBaseUri, remove_utf8_bom($row[0]));
        $rubriek = $graph->resource($rubriekUri, 'rico:RecordSet');
        $graph->add($rubriek, 'rico:hasRecordSetType', $graph->resource('https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#Series')) ;

        # row[1]: volgorde

        # relate Archief and parent
        $parentUri = sprintf($nagoRubriekBaseUri, remove_utf8_bom($row[2]));
        $parent = $graph->resource($parentUri);
        $graph->add($rubriek, 'rico:isIncludedIn', $parent);
        $graph->add($parent, 'rico:includes', $rubriek);

        $archiefUri = sprintf($nagoArchiefBaseUri, remove_utf8_bom($row[3]));
        $archief = $graph->resource($archiefUri);
        $graph->add($rubriek, 'rico:isIncludedIn', $archief);
        $graph->add($archief, 'rico:includes', $rubriek);

        # relate descriptiveNote
        if ($row[4] != "" and $row[4] != "NULL") { $graph->add($rubriek, 'rico:descriptiveNote', remove_utf8_bom($row[4])); }

        # row[5]: publiceren Y/N

        # create and relate Title
        $titleUri = $rubriekUri."/title";
        $title = $graph->resource($titleUri, 'rico:Title');
        $graph->add($title, 'rico:textualValue', remove_utf8_bom($row[6]));
        $graph->add($title, 'rico:isTitleOf', $rubriek);
        $graph->add($rubriek, 'rico:hasTitle', $title);

    }
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;