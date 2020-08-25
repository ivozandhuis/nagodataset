<?php
/**
 * Create a turtle file from the 'persoon_instelling.csv' file
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
$fileToRead = __DIR__ . '/input/persoon_instelling.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/persoon_instelling.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/agent/%s';
\EasyRdf\RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
\EasyRdf\RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
\EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');
\EasyRdf\RdfNamespace::set('bio', 'http://purl.org/vocab/bio/0.1/');
\EasyRdf\RdfNamespace::set('pnv', 'https://w3id.org/pnv#');

# persoon
foreach ($records as $row) {
    if (($row[11] === '1') and ($row[10] === '1')) { # check "toon_op_site Y/N" and "is_person" Y/N

        # create Agent
        $agentUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0])) ;
        $agent = $graph->resource($agentUri, 'schema:Person') ;

        # create and relate Name
        $nameUri = $agentUri."/name";
        $name = $graph->resource($nameUri, 'pnv:PersonName');
        $graph->add($name, 'pnv:givenName', remove_utf8_bom($row[5]));
        $graph->add($name, 'pnv:surname', remove_utf8_bom($row[4]));
        $infix = '';
        if ($row[13] != '' and $row[13]  != 'NULL') {
            $graph->add($name, 'pnv:surnamePrefix', remove_utf8_bom($row[13]));
            $infix = $row[13] . ' ' ;
        }
        $graph->add($agent, 'pnv:hasName', $name);

        # create and add NameLit
        $nameLit = $row[5] . ' ' . $infix . $row[4] ;
        $graph->add($name, 'pnv:nameLiteral', $nameLit);
        $graph->add($agent, 'schema:name', $nameLit) ;
        $graph->add($agent, 'rdfs:label', $nameLit) ;

        # create  and relate Birth
        $birthUri = $agentUri."/birth";
        $birth = $graph->resource($birthUri, 'schema:Event');
        $graph->add($birth, 'rdf:type', $graph->resource('http://purl.org/vocab/bio/0.1/Birth'));
        $graph->add($birth, 'schema:actor', $agent);
        $graph->add($birth, 'schema:beginDate', remove_utf8_bom($row[7]));
        $graph->add($birth, 'schema:endDate', remove_utf8_bom($row[7]));
        $loc = $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[1])) ;
        $graph->add($birth, 'schema:location', $loc);
        $graph->add($agent, 'bio:birth', $birth);

        # create  and relate Death
        $deathUri = $agentUri."/death";
        $death = $graph->resource($deathUri, 'schema:Event');
        $graph->add($death, 'rdf:type', $graph->resource('http://purl.org/vocab/bio/0.1/Death'));
        $graph->add($death, 'schema:actor', $agent);
        $graph->add($death, 'schema:beginDate', remove_utf8_bom($row[8]));
        $graph->add($death, 'schema:endDate', remove_utf8_bom($row[8]));
        $loc = $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[2])) ;
        $graph->add($death, 'schema:location', $loc);
        $graph->add($agent, 'bio:death', $death);

    }
}

# instelling
foreach ($records as $row) {
    if (($row[11] === '1') and ($row[10] != '1')) { # check "toon_op_site Y/N" and "is_person" Y/N

        # create Agent
        $agentUri = sprintf($nagoBaseUri, remove_utf8_bom($row[0])) ;
        $agent = $graph->resource($agentUri, 'schema:Organization') ;

        # local typing
        $graph->add($agent, 'schema:additionalType', $graph->resource('https://nago.nl/lijsten/'.remove_utf8_bom($row[3]))) ;

        # create and add NameLit
        $graph->add($agent, 'schema:name', remove_utf8_bom($row[4])) ;
        $graph->add($agent, 'rdfs:label', remove_utf8_bom($row[4])) ;

    }
}


print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;