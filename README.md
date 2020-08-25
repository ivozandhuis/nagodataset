# doel
De [NAGO database](http://www.nago.nl) van het [Wim Crouwel Instituut](http://www.wimcrouwelinstituut.nl/) bevat informatie over archieven gevormd door grafisch vormgevers. Het bevat archiefmateriaal, maar ook ontwerpen, die als museale objecten worden beschreven en publicatie die als publicaties worden beschreven.

PHP-scripten in dit repository converteren csv-files uit de nago-database in directory /input naar ttl-file in /output. Gebruikte namespaces:
* RiC-O voor _archief_ en _rubriek_ 
* Linked-Art voor _object_ en _tentoonstelling_
* BIBFRAME voor _publicatie_
* Schema.org, pnv en bio voor _persoon_instelling_
* SKOS voor _lijsten_

# installatie en afhankelijkheden
Het maakt gebruik van twee libraries: een die csv leest en EasyRdf die een graaf opbouwt in het geheugen en kan serializeren naar diverse RDF-varianten.

# toepassing
```
php archief2lod.php
```

# met dank aan ...
De ontwikkeling van deze scipten is mogelijk gemaakt door het [Stimuleringsfonds Creatieve Industrie](https://stimuleringsfonds.nl/). Met dank aan [PHPetra](https://github.com/phpetra) waarbij ik mocht afkijken.

# todo
* Data aanvullen en verbeteren in de database
* Scripten inbouwen in de NAGO-php infrastructuur
* HTML uit de content filteren
* controleren op compleetheid
* data review laten doen
* rdfs:label overal toevoegen

# ideeen
Ontwikkeling van PHP scripten die JSON-LD maken. 
