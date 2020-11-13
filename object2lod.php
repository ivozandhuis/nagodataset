<?php
/**
 * Create a turtle file from the 'object.csv' file
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
$fileToRead = __DIR__ . '/input/object.csv';
$reader = Reader::createFromPath($fileToRead, 'r');
$stmt = (new Statement())
    ->offset(1)
    //->limit(50)
;
$records = $stmt->process($reader);

# set turtle-file to write to
$fileToWrite = __DIR__ . '/output/object.ttl';
if (file_exists($fileToWrite)) {
    unlink($fileToWrite);
}

# construct and write triples for every row in csv
print 'New graph' . PHP_EOL;
$graph = new \EasyRdf\Graph();
$nagoBaseUri = 'https://nago.nl/';
$nagoArchiefBaseUri = $nagoBaseUri.'archief/%s';
$nagoRubriekBaseUri = $nagoBaseUri.'rubriek/%s';
$nagoObjectBaseUri = $nagoBaseUri.'object/%s';
$nagoLijstBaseUri = $nagoBaseUri.'lijsten/%s';
$nagoObjectBaseUrl = 'http://www.wimcrouwelinstituut.nl/nago/object.php?id=%s';

\EasyRdf\RdfNamespace::set('rico', 'https://www.ica.org/standards/RiC/ontology#');
\EasyRdf\RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm/');
\EasyRdf\RdfNamespace::set('schema', 'http://schema.org/');

foreach ($records as $row) {
    if ($row[20]) { # check "publiceren Y/N"

        # create HumanMadeObject
        $objectUri = sprintf($nagoObjectBaseUri, remove_utf8_bom($row[0]));
        $object = $graph->resource($objectUri, 'crm:E22_Human-Made_Object');

        $objectUrl = sprintf($nagoObjectBaseUrl, remove_utf8_bom($row[0]));
        $x = $graph->resource($objectUrl);
        $graph->add($object, 'schema:url', $x) ;

        # 1"type_titel",
        # 2"soort_object",
        $objectnameUri = sprintf($nagoLijstBaseUri, remove_utf8_bom($row[2]));
        $x = $graph->resource($objectnameUri);
        $graph->add($object, 'crm:P2_has_type', $x) ;

        # relate Dimension
        if ($row[3] != "" and $row[3] != "NULL") { #diepte
            $depthUri = $objectUri."/depth";
            $depth = $graph->resource($depthUri);
            $graph->add($depth, 'crm:P2_has_type', $graph->resource('http://vocab.getty.edu/aat/300072633')) ;
            $graph->add($depth, 'crm:P90_has_value', remove_utf8_bom($row[23]));
            if ($row[3] == '54') { # cm
                $graph->add($depth, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379098')) ;
            }
            if ($row[3] == '53') { # mm
                $graph->add($depth, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379097')) ;
            }
            $graph->add($object, 'crm:P43_has_dimension', $depth);
        }

        if ($row[4] != "" and $row[4] != "NULL") { #hoogte
            $heightUri = $objectUri."/height";
            $height = $graph->resource($heightUri);
            $graph->add($height, 'crm:P2_has_type', $graph->resource('http://vocab.getty.edu/aat/300055644')) ;
            $graph->add($height, 'crm:P90_has_value', remove_utf8_bom($row[21]));
            if ($row[4] == '54') { # cm
                $graph->add($height, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379098')) ;
            }
            if ($row[4] == '53') { # mm
                $graph->add($height, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379097')) ;
            }
            $graph->add($object, 'crm:P43_has_dimension', $height);
        }

        if ($row[5] != "" and $row[5] != "NULL") { #breedte
            $widthUri = $objectUri."/width";
            $width = $graph->resource($widthUri);
            $graph->add($width, 'crm:P2_has_type', $graph->resource('http://vocab.getty.edu/aat/300055647')) ;
            $graph->add($width, 'crm:P90_has_value', remove_utf8_bom($row[22]));
            if ($row[5] == '54') { # cm
                $graph->add($width, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379098')) ;
            }
            if ($row[5] == '53') { # mm
                $graph->add($width, 'crm:P91_has_unit', $graph->resource('http://vocab.getty.edu/aat/300379097')) ;
            }
            $graph->add($object, 'crm:P43_has_dimension', $width);
        }

        # create and relate Title
        $titleUri = $objectUri."/title";
        $title = $graph->resource($titleUri, 'crm:E33_E41_Linguistic_Appellation');
        $graph->add($title, 'crm:P2_has_type', $graph->resource('http://vocab.getty.edu/aat/300404670')) ;
        $graph->add($title, 'crm:P190_has_symbolic_content', remove_utf8_bom($row[6]));
        $graph->add($object, 'crm:P1_is_identified_by', $title);

        # relate Archief and parent
        $archiefUri = sprintf($nagoArchiefBaseUri, remove_utf8_bom($row[7]));
        $archief = $graph->resource($archiefUri);
        $graph->add($object, 'rico:isIncludedIn', $archief);
#        $graph->add($archief, 'rico:includes', $object);

        $parentUri = sprintf($nagoRubriekBaseUri, remove_utf8_bom($row[8]));
        $parent = $graph->resource($parentUri);
        $graph->add($object, 'rico:isIncludedIn', $parent);
#        $graph->add($parent, 'rico:includes', $object);

        # create and relate Identifier
        $idUri = $objectUri."/id";
        $id = $graph->resource($idUri, 'crm:E42_Identifier');
        $graph->add($id, 'crm:P190_has_symbolic_content', remove_utf8_bom($row[9]));
        $graph->add($object, 'crm:P1_is_identified_by', $id);


        # 10"doosnummer",
        # 11"soort_doos",

        # create and relate Production
        $prodUri = $objectUri."/production";
        $prod = $graph->resource($prodUri, 'crm:E12_Production');
        $timespan = $graph->newBNode('crm:E52_Time-Span');
        $graph->add($timespan, 'crm:P82a_begin_of_the_begin', remove_utf8_bom($row[12]));
        $graph->add($timespan, 'crm:P82b_end_of_the_end', remove_utf8_bom($row[14]));
        $graph->add($prod, 'crm:P4_has_time-span', $timespan);
        $graph->add($object, 'crm:P108i_was_produced_by', $prod);

        # 16"aantal",
        # 17"opmerking",
        # 18"opmerkingen_geheim",
        # 19"breedte_temp",

        # 24"staat_toelichting",
        # 25"omvang",
        # 26"omschrijving",
        # 27"is_dossier",
        # 28"toon_op_homepage",
        # 29"app_state",
        # 30"datum_gewijzigd",
        # 31"gewijzigd_door",
        # 32"volgorde"


    }
}

print 'Flush to file' . PHP_EOL;
file_put_contents(
$fileToWrite,
$graph->serialise('turtle'),
FILE_APPEND | LOCK_EX
);

print 'All done' . PHP_EOL;