# openchurch

[![CircleCI](https://circleci.com/gh/hozana/openchurch.svg?style=svg)](https://circleci.com/gh/hozana/openchurch)

## A few commands

- To generate our schema: `vendor/bin/schema generate-types config/schema.yaml` to have the PHP entities, then `bin/console doctrine:schema:update --force` to update database.
- To test the app: `bin/console server:run` in project root folder
- To test the react client: `npm start` in `openchurch-admin` folder

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
