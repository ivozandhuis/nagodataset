<?php
/**
 * Create a turtle file from the 'archief.csv' file
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
$fileToRead = __DIR__ . '/input/archief.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/archief.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/archief/%s';
\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');

foreach ($records as $row) {
    if ($row[16]) { # check "publiceren Y/N"

        # create RecordSet
        $archiefUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0]));
        $archief = $graph->resource($archiefUri, 'rico:RecordSet');
        $graph->add($archief, 'rico:hasRecordSetType', $graph->resource('https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#Fond')) ;

        # create and relate Title
        $titleUri = $archiefUri."/title";
        $title = $graph->resource($titleUri, 'rico:Title');
        $graph->add($title, 'rico:textualValue', remove_utf8_bom($row[1]));
        $graph->add($title, 'rico:isTitleOf', $archief);
        $graph->add($archief, 'rico:hasTitle', $title);

        # create and relate Identifier
        $idUri = $archiefUri."/id";
        $id = $graph->resource($idUri, 'rico:Identifier');
        $graph->add($id, 'rico:textualValue', remove_utf8_bom($row[2]));
        $graph->add($id, 'rico:identifies', $archief);
        $graph->add($archief, 'rico:identifiedBy', $id);

        # create and relate Date
        $begindateUri = $archiefUri."/begindate";
        $begindate = $graph->resource($begindateUri, 'rico:Date');
        $graph->add($begindate, 'rico:normalizedDateValue', remove_utf8_bom($row[3]));
#        $graph->add($begindate, 'rico:textualValue', remove_utf8_bom($row[4])); # boolean?
        $graph->add($begindate, 'rico:isBeginningDateOf', $archief);
        $graph->add($archief, 'rico:hasBeginningDate', $begindate);

        $enddateUri = $archiefUri."/enddate";
        $enddate = $graph->resource($enddateUri, 'rico:Date');
        $graph->add($enddate, 'rico:normalizedDateValue', remove_utf8_bom($row[5]));
#        $graph->add($enddate, 'rico:textualValue', remove_utf8_bom($row[6])); # boolean?
        $graph->add($enddate, 'rico:isEndDateOf', $archief);
        $graph->add($archief, 'rico:hasEndDate', $enddate);    

        # create and relate Instantiation
        $instUri = $archiefUri."/instantiation";
        $inst = $graph->resource($instUri, 'rico:Instantiation');
        $graph->add($inst, 'rico:instantiationExtent', remove_utf8_bom($row[7]));
        $graph->add($inst, 'rico:instantiates', $archief);
        $graph->add($archief, 'rico:hasInstantiation', $inst);

    }
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;