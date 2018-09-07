# openchurch

[![CircleCI](https://circleci.com/gh/hozana/openchurch.svg?style=svg)](https://circleci.com/gh/hozana/openchurch)

## A few commands

### To start

- `cp .env.dist .env && vim .env` to setup your own environment.
- `wget https://raw.githubusercontent.com/wiki/hozana/openchurch/20180806openchurch.sql && mysql -u root -p < openchurch < ./20180806openchurch.sql` to get a database.
- `composer install && yarn install && cd openchurch-admin && yarn install` to install all dependencies.

### Database

To generate our schema we first used `vendor/bin/schema generate-types config/schema.yaml` to have the PHP entities generated from yaml and [schema.org](https://schema.org/Church). But as these generated entities has been modified, we could just remove the [schema-generator](https://api-platform.com/docs/schema-generator/configuration/) and the schema.yaml file.
 
- `bin/console doctrine:schema:update --force` to update database
- or `bin/console doctrine:migrations:diff` to create a migration, 
- and `bin/console doctrine:migrations:migrate` to run the migration.

### To start the API

To test the app: `bin/console server:run` in project root folder and then reach [http://127.0.0.1:8000](http://127.0.0.1:8000). There is also a GraphiQL interface to help you write down your GraphQL requests : [http://localhost:8000/api/graphql](http://localhost:8000/api/graphql).

- To create a oAuth2 client to test the API: `bin/console oauth:client:create client_credentials` (or use the upcoming web interface).
- To fill Elasticsearch: `bin/console fos:elastica:populate`.
- To generate the assets `yarn run dev` (and `yarn run watch` while developing).

### To start the react app

This app is provided by API Platform. It connects to the PHP API and autodiscover it.

```
cd openchurch-admin
npm start
```

Then `http://localhost:3000` should be automatically opened.
In `openchurch-admin/src/App.js` you can define the API's URL : it's the only configuration.

## For Elasticsearch

On a VM:

```
docker pull docker.elastic.co/elasticsearch/elasticsearch:6.3.2
docker run -p 9200:9200 -p 9300:9300 -e "discovery.type=single-node" docker.elastic.co/elasticsearch/elasticsearch:6.3.2
```

## API usage example

### Find a city

`GET http://127.0.0.1:8000/communes?name=rueil`

will filter cities, looking in the name field only (case insensitive):

```
{
	"@context": "\/contexts\/Commune",
	"@id": "\/communes",
	"@type": "hydra:Collection",
	"hydra:member": [
		{
			"@id": "\/communes\/10802",
			"@type": "http:\/\/schema.org\/Thing",
			"id": 10802,
			"codeInsee": "28322",
			"name": "Rueil-la-Gadelière",
			"searchable": "rueil la gadeliere",
			"departement": "\/departements\/29",
			"commonsCategory": "Rueil-la-Gadelière",
			"latitude": 48.714722,
			"longitude": 0.976389
		},
		{
			"@id": "\/communes\/32137",
			"@type": "http:\/\/schema.org\/Thing",
			"id": 32137,
			"codeInsee": "78113",
			"name": "Brueil-en-Vexin",
			"searchable": "brueil en vexin",
			"departement": "\/departements\/79",
			"commonsCategory": "Brueil-en-Vexin",
			"latitude": 49.031387,
			"longitude": 1.819722
		},
		{
			"@id": "\/communes\/36329",
			"@type": "http:\/\/schema.org\/Thing",
			"id": 36329,
			"codeInsee": "92063",
			"name": "Rueil-Malmaison",
			"searchable": "rueil malmaison",
			"departement": "\/departements\/93",
			"commonsCategory": "Rueil-Malmaison",
			"latitude": 48.877777,
			"longitude": 2.188333
		}
	],
	"hydra:totalItems": 3,
	"hydra:view": {
		"@id": "\/communes?name=rueil",
		"@type": "hydra:PartialCollectionView"
	}
}
```

### Find churches for a given city

Once you retrieved a city id, you can use it to filter churches:

`GET http://127.0.0.1:8000/churches?commune=36329`

```
{
	"@context": "\/contexts\/Church",
	"@id": "\/churches",
	"@type": "hydra:Collection",
	"hydra:member": [
		{
			"@id": "\/churches\/2242",
			"@type": "http:\/\/schema.org\/Church",
			"id": 2242,
			"name": "Chapelle Saint-Maximilien-Kolbe de Rueil-Malmaison",
			"alternateName": null,
			"description": null,
			"address": null,
			"commune": "\/communes\/36329",
			"departement": "\/departements\/93",
			"hasMap": null,
			"telephone": null,
			"faxNumber": null,
			"wikidataId": "Q21064760",
			"wikidataDioceseId": "Q872904",
			"merimeeId": "",
			"egliseInfoId": "",
			"wikipediaId": "Chapelle Saint-Maximilien-Kolbe de Rueil-Malmaison",
			"commonsId": "",
			"clochersId": null,
			"patrimoineReligieuxId": "152666",
			"url": null,
			"confessionUrl": null,
			"adorationUrl": null,
			"massUrl": null,
			"geo": "\/geo_coordinates\/2151",
			"photo": "",
			"thumbnail": null,
			"logo": null,
			"isAccessibleForFree": null,
			"publicAccess": null,
			"maximumAttendeeCapacity": null,
			"additionalType": null,
			"event": null,
			"review": null,
			"openingHour": null,
			"openingHoursSpecifications": [],
			"specialOpeningHoursSpecification": null,
			"dateCreated": null,
			"dateModified": null
		},
		{
			"@id": "\/churches\/8316",
			"@type": "http:\/\/schema.org\/Church",
			"id": 8316,
			"name": "Église Notre-Dame-de-la-Compassion de Rueil-Malmaison",
			"alternateName": null,
			"description": null,
			"address": null,
			"commune": "\/communes\/36329",
			"departement": "\/departements\/93",
			"hasMap": null,
			"telephone": null,
			"faxNumber": null,
			"wikidataId": "Q21064763",
			"wikidataDioceseId": "Q872904",
			"merimeeId": "",
			"egliseInfoId": "92\/rueil-malmaison\/notre-dame-de-la-compassion",
			"wikipediaId": "Église Notre-Dame-de-la-Compassion de Rueil-Malmaison",
			"commonsId": "",
			"clochersId": "92\/accueil_92063a",
			"patrimoineReligieuxId": "152176",
			"url": null,
			"confessionUrl": null,
			"adorationUrl": null,
			"massUrl": null,
			"geo": "\/geo_coordinates\/7794",
			"photo": "",
			"thumbnail": null,
			"logo": null,
			"isAccessibleForFree": null,
			"publicAccess": null,
			"maximumAttendeeCapacity": null,
			"additionalType": null,
			"event": null,
			"review": null,
			"openingHour": null,
			"openingHoursSpecifications": [],
			"specialOpeningHoursSpecification": null,
			"dateCreated": null,
			"dateModified": null
		}
		...
	],
	"hydra:totalItems": 7,
	"hydra:view": {
		"@id": "\/churches?commune=36329",
		"@type": "hydra:PartialCollectionView"
	}
}
```

### Update one church field

Even if it's not RESTful, you can do partial PUT with API Platform.

`PUT http://127.0.0.1:8000/churches/17616` with a JSON body like:

```
{
	"confessionUrl": "http://www.rueil.diocese92.fr/sacrements/reconciliation/"
}
```

will update `confessionUrl` field without changing anything else. And the whole object is returned:

```
{
	"@context": "\/contexts\/Church",
	"@id": "\/churches\/17616",
	"@type": "http:\/\/schema.org\/Church",
	"id": 17616,
	"name": "Église Sainte-Thérèse de Rueil-Malmaison",
	"alternateName": null,
	"description": null,
	"address": null,
	"commune": "\/communes\/36329",
	"departement": "\/departements\/93",
	"hasMap": null,
	"telephone": null,
	"faxNumber": null,
	"wikidataId": "Q21064756",
	"wikidataDioceseId": "Q872904",
	"merimeeId": "",
	"egliseInfoId": "92\/rueil-malmaison\/sainte-therese",
	"wikipediaId": "Église Sainte-Thérèse de Rueil-Malmaison",
	"commonsId": "Église Sainte-Thérèse de Rueil-Malmaison",
	"clochersId": "92\/accueil_92063b",
	"patrimoineReligieuxId": "176572",
	"url": null,
	"confessionUrl": "http:\/\/www.rueil.diocese92.fr\/sacrements\/reconciliation\/",
	"adorationUrl": null,
	"massUrl": null,
	"geo": "\/geo_coordinates\/14254",
	"photo": "Rueil-Malmaison SteThérèse.jpg",
	"thumbnail": "5\/5b\/Rueil-Malmaison_SteThérèse.jpg\/160px-Rueil-Malmaison_SteThérèse.jpg",
	"logo": null,
	"isAccessibleForFree": null,
	"publicAccess": null,
	"maximumAttendeeCapacity": null,
	"additionalType": null,
	"event": null,
	"review": null,
	"openingHour": null,
	"openingHoursSpecifications": [],
	"specialOpeningHoursSpecification": null,
	"dateCreated": null,
	"dateModified": null
}
```
