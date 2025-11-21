# Whats is OpenChurch ?

Openchurch aims to be an open API in order to allow anyone to find data about dioceses, parishes, and churches. As ecclesia reality is complex, openchurch does not serve fully trustable information. Instead, it serves informations who have been entered by multiple agents.  
Currently, it serves [the Rosario app](https://rosario.app/), allowing people to easily join a parish to pray chapelet.

There are 2 main services : `backend` and `python`. The `backend` aims to provide an API, and `python` aims to retrieve data from wikidata, and import it to the backend.  
There is also an `elastisearch` service. Data coming from wikidata is indexed in an elastic instance in order to find it using search. For now, only diocese and parish can be searched.

![Openchurch Architecture](openchurch-architecture.png)

## Development

### Project setup

This project uses Symfony with Api-Platform. Make sure to have docker installed on your machine.

1. Git clone `git@github.com:hozana/openchurch.git`
2. Setup your `/etc/hosts` to include `127.0.0.1 api.openchurch.local`
3. Build the containers: `docker compose build`
4. Run the containers: `docker compose up`
5. You can navigate to https://api.openchurch.local/api/docs to see the doc. You can also go to https://api.openchurch.local/dashboard in order to see the importation of data status.
6. Once data have been imported (or whenever you want) you can run the command `bin/console app:index:communities` from the backend container in order to index all communities to elastic.
7. You are ready to query the API

### Docker
If you need to ssh into a container, run `docker compose exec <service> bash` where `<service>` can be any service located in `compose.yml`.

### Elastic
To index communities (parishes and dioceses stored in the database), run from the `backend container`: `bin/console app:index:communities`

## API documentation

Documentation is available at `/api/docs`:

- https://api.openchurch.local/api/docs

## Synchro Dashboard
A synchro dashboard is available at `/dashboard`:

- https://api.openchurch.local/dashboard

# Data structure
Each Field holder (Place or Community) hold some fields. Each fields is categorized by a name (CF `FieldCommunity.php` or `FieldPlace.php`) and have a value. A field is coming from an agent (the entity who entered the data) and have a reliability indice. The value can be of multiple types (dting, float, int, date, ...)
A Field can also refer to a Community or a Place. Indeed, a parish will have a field named `parentCommunityId` which link it to a `diocese`.

## Synchro with wikidata

### To synchronize the database from Wikidata
From `python`service, load data from Wikidata: `/opt/venv/bin/python /app/synchro.py --entity-only xxx` where xxx can be either `diocese`, `parish` or `church`.

### How does it work?
While the synchro is occuring, data drom wikidata is being fetched by batch. When some data is being imported, an import status is being reported to the `redis` service. Thanks to it, if the process failed during run, it can start again where it stopped. Moreover, if multiple processes are started at the same time, they will handle different batches.

Data is being requested from wikidata thanks to a sparql query. All logic resides in `sripts/synchro.py`. Importing diocese takes several minutes. Parishes takes about 15-30 minutes. Churches is longer (around 2 hours).

When processing a batch, the python script calls `[PUT] /places/upsert` endpoint for churches, or `[PUT] /communities/upsert` for communities (ie diocese/parishes). The wikidataId is being stored, so the data can be either updated or inserted.

## Api usage example
### Query a diocese
`https://api.openchurch.local/api/communities?type=diocese&itemsPerPage=10&page=1&name=aix%20en%20provence`

### Query a parish
`https://api.openchurch.local/api/communities?type=parish&itemsPerPage=10&contactZipcodes[]=40270&contactZipcodes[]=30000`

### Response example
```JSON
{
	"@context": "/api/contexts/Community",
	"@id": "/api/communities",
	"@type": "Collection",
	"totalItems": 2,
	"member": [
		{
			"@id": "/api/communities/0194cc58-9ae2-754e-aaa4-0f98ab46bb18",
			"@type": "Community",
			"id": "0194cc58-9ae2-754e-aaa4-0f98ab46bb18",
			"fields": [
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/636173db41ef2a4797d1",
					"name": "contactCountryCode",
					"value": "fr",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q1363973"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/1005b8c821e43a34a907",
					"name": "name",
					"value": "Archidiocèse d'Aix-en-Provence et Arles",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q1363973"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/0fb42f164359d0774415",
					"name": "type",
					"value": "diocese",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q1363973"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/6c8ccedbe6861c1935d4",
					"name": "wikidataId",
					"value": 1363973,
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q1363973"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/4ca791f1e444898a57ee",
					"name": "wikidataUpdatedAt",
					"value": "2025-03-26T14:01:10+00:00",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q1363973"
				}
			]
		},
		{
			"@id": "/api/communities/0194cc58-8931-753b-b77c-f3a75fe04ba8",
			"@type": "Community",
			"id": "0194cc58-8931-753b-b77c-f3a75fe04ba8",
			"fields": [
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/d9415c4d44f906ff3a69",
					"name": "contactCountryCode",
					"value": "fr",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q866776"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/abb294b073a833cdd952",
					"name": "name",
					"value": "Diocèse d'Aire et Dax",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q866776"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/ea3c0c5515f041c6a3d0",
					"name": "type",
					"value": "diocese",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q866776"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/607b601ece57c173c729",
					"name": "wikidataId",
					"value": 866776,
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q866776"
				},
				{
					"@type": "Field",
					"@id": "/api/.well-known/genid/516c82999a0bbc3e23d0",
					"name": "wikidataUpdatedAt",
					"value": "2025-05-10T08:18:06+00:00",
					"agent": {
						"@type": "Agent",
						"@id": "/api/.well-known/genid/00694474629cbf0d779a",
						"id": "0193e4d8-4f7a-7ae1-aaa2-8145d3caee4a",
						"name": "CLI_PYTHON"
					},
					"reliability": "high",
					"engine": "scraper",
					"source": "Wikidata",
					"explanation": "https://www.wikidata.org/wiki/Q866776"
				}
			]
		}
	],
	"view": {
		"@id": "/api/communities?itemsPerPage=10&name=aix%20en%20provence&type=diocese",
		"@type": "PartialCollectionView"
	},
	"search": {
		"@type": "IriTemplate",
		"template": "/api/communities{?type,messeInfoId,parentWikidataId,name}",
		"variableRepresentation": "BasicRepresentation",
		"mapping": [
			{
				"@type": "IriTemplateMapping",
				"variable": "type",
				"property": "type",
				"required": false
			},
			{
				"@type": "IriTemplateMapping",
				"variable": "messeInfoId",
				"property": "wikidataId",
				"required": false
			},
			{
				"@type": "IriTemplateMapping",
				"variable": "parentWikidataId",
				"property": "parentWikidataId",
				"required": false
			},
			{
				"@type": "IriTemplateMapping",
				"variable": "name",
				"property": "name",
				"required": false
			}
		]
	}
}
```
