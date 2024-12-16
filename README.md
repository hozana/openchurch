1. Attention à la création des communautés / places !
   Les fields et leurs entités doivent être persistés ensemble avant de flush
2. Comme nous n'avons pas encore de moyens de supprimer des entités (depuis endpoint ou admin), la synchro elastic pour la suppression n'a pas encore été implémentée

### Script

Load data from Wikidata: python3 synchro.py
Push data to OpenChurch v2: python3 synchro.py push
Print statistics: python3 synchro.py stats

The local.sqlite serves as a local buffer to store the OpenChurch v2 UUIDs and remember what as already been pushed, what has not and determine what has been updated on Wikidata since the last push.

1st run is very long (several hours) because it loads places (cities, countries) from Wikidata. It is recommended to put the attached SQLite database in the scripts folder to fasten the process. Every data from this file (except OpenChurch UUIDs) is from Wikidata with license is CC0.
local.sqlite.gz
