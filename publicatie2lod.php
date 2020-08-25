<?php
/**
 * Create a turtle file from the 'publicatie.csv' file
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
$fileToRead = __DIR__ . '/input/publicatie.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/publicatie.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/publicatie/%s';
\EasyRdf\RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
\EasyRdf\RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
\EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');
\EasyRdf\RdfNamespace::set('bf', 'http://id.loc.gov/ontologies/bibframe/');

foreach ($records as $row) {
    if (($row[1] === '80') or ($row[1] === '83')) { # check pubType is Book

        # create Book
        $pubUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0])) ;
        $pub = $graph->resource($pubUri, 'bf:Instance') ;
        $graph->add($pub, 'rdfs:label', remove_utf8_bom($row[3]));

        # create and relate Title
        $titleUri = $pubUri."/title";
        $title = $graph->resource($titleUri, 'bf:Title');
        $graph->add($title, 'rdfs:label', remove_utf8_bom($row[3]));
        $graph->add($pub, 'bf:title', $title);

        # create and relate Identifier
        $idUri = $pubUri."/isbn";
        $id = $graph->resource($idUri, 'bf:Isbn');
        $graph->add($id, 'rdf:label', remove_utf8_bom($row[10]));
        $graph->add($pub, 'bf:identifiedBy', $id);

        # create  and relate PubEvent
        $pubeventUri = $pubUri."/publication";
        $pubevent = $graph->resource($pubeventUri, 'bf:Publication');
        $graph->add($pubevent, 'bf:agent', remove_utf8_bom($row[13]));
        $graph->add($pubevent, 'bf:date', remove_utf8_bom($row[7]));
        $loc = $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[2])) ;
        $graph->add($pubevent, 'bf:place', $loc);
        $graph->add($pub, 'bf:provisionActivity', $pubevent);
    }
}

foreach ($records as $row) {
    if (($row[1] === '324') or ($row[1] === '325')) { # check pubType is Article

        # create Article
        $pubUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0])) ;
        $pub = $graph->resource($pubUri, 'bf:Instance') ;
        $graph->add($pub, 'schema:hasNumberOfPages', remove_utf8_bom($row[8]));
        $graph->add($pub, 'schema:description', remove_utf8_bom($row[9]));
        # create and relate Title
        $titleUri = $pubUri."/title";
        $title = $graph->resource($titleUri, 'bf:Title');
        $graph->add($title, 'rdfs:label', remove_utf8_bom($row[3]));
        $graph->add($pub, 'bf:title', $title);

        # create  and relate aflevering
        $afleveringUri = $pubUri."/aflevering";
        $aflevering = $graph->resource($afleveringUri, 'bf:Instance');
        $graph->add($aflevering, 'rdfs:label', remove_utf8_bom($row[6]));
        $graph->add($pub, 'bf:isPartOf', $aflevering);

        # create  and relate jaargang
        $jaargangUri = $pubUri."/jaargang";
        $jaargang = $graph->resource($jaargangUri, 'bf:Instance');
        $graph->add($jaargang, 'rdfs:label', remove_utf8_bom($row[5]));
        $graph->add($jaargang, 'bf:date', remove_utf8_bom($row[7]));
        $graph->add($aflevering, 'bf:isPartOf', $jaargang);

        # create  and relate periodical
        $periodicalUri = $pubUri."/periodical";
        $periodical = $graph->resource($periodicalUri, 'bf:Instance');
        $graph->add($jaargang, 'rdfs:label', remove_utf8_bom($row[5]));
        $graph->add($jaargang, 'bf:isPartOf', $periodical);
        
        # create and relate Title
        $titleUri = $periodicalUri."/title";
        $title = $graph->resource($titleUri, 'bf:Title');
        $graph->add($title, 'rdfs:label', remove_utf8_bom($row[4]));
        $graph->add($periodical, 'bf:title', $title);

        # create and relate Identifier
        $idUri = $periodicalUri."/issn";
        $id = $graph->resource($idUri, 'bf:Issn');
        $graph->add($id, 'rdf:label', remove_utf8_bom($row[10]));
        $graph->add($periodical, 'bf:identifiedBy', $id);

        # create  and relate PubEvent
        $pubeventUri = $periodicalUri."/publication";
        $pubevent = $graph->resource($pubeventUri, 'bf:Publication');
        $graph->add($pubevent, 'bf:agent', remove_utf8_bom($row[13]));
        $loc = $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[2])) ;
        $graph->add($pubevent, 'bf:place', $loc);
        $graph->add($periodical, 'bf:provisionActivity', $pubevent);
    }
}



print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;