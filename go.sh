#!/bin/bash

# eerst alle entiteiten
php archief2lod.php
php rubriek2lod.php
php object2lod.php
php publicatie2lod.php

php persoon_instelling2lod.php
php tentoonstelling2lod.php
php lijsten2lod.php

# dan alle relaties
php archief_persoon_instelling2lod.php
php archief_publicatie2lod.php
php archief_relatie_archief2lod.php

php rubriek_publicatie2lod.php
php rubriek_relatie_rubriek2lod.php

php object_afbeelding2lod.php
php object_persoon_instelling2lod.php
php object_publicatie2lod.php
php object_relatie_object2lod.php
php vervaardiging2lod.php

