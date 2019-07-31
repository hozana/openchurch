# openchurch

[![CircleCI](https://circleci.com/gh/hozana/openchurch.svg?style=svg)](https://circleci.com/gh/hozana/openchurch)
[![BrowserStack Status](https://www.browserstack.com/automate/badge.svg?badge_key=ZSt3N2Rtd2hhWWZDcDhVNmNFUjAycVNjSW0rYXJIdWhINmpXODJmYVB2TT0tLWc4WjFweGtmN29Fc3AyNldaRVZycEE9PQ==--1d3f8aa35e09306748448a275969f0d8de70fa6f)](https://www.browserstack.com/automate/public-build/ZSt3N2Rtd2hhWWZDcDhVNmNFUjAycVNjSW0rYXJIdWhINmpXODJmYVB2TT0tLWc4WjFweGtmN29Fc3AyNldaRVZycEE9PQ==--1d3f8aa35e09306748448a275969f0d8de70fa6f)

## They help us

[![](https://marker.io/vendor/img/logo/browserstack-logo.svg "BrowserStack")](https://www.browserstack.com/)

BrowserStack is a useful tool to test our app on different browsers, different OS and different versions. They give free access to their platform to open-source projects. Thanks to them!

## A few commands

### Quick start

To build and start the app:

```
# build docker image
cp .env.dist .env
docker-compose build
docker-compose up

# install
docker exec -it openchurch composer install
docker exec -it openchurch yarn install
docker exec -it openchurch sh -c "cd openchurch-admin && yarn install"
docker exec -it openchurch yarn run dev

# import database
mysql -uopenchurch -popenchurch -h 127.0.0.1 -P 13306 openchurch < data/20180806-openchurch.sql
docker exec -it openchurch bin/console doctrine:schema:update --force

# index data in ES
docker exec -it openchurch bin/console fos:elastica:populate

# run API
docker exec -it openchurch bin/console server:run 0.0.0.0:8000

# run backoffice
docker exec -it openchurch sh -c "cd openchurch-admin && npm start"
```

Check API works on [http://127.0.0.1:8000](http://127.0.0.1:8000). And backoffice on [http://127.0.0.1:3000](http://127.0.0.1:3000).


### Docker

Always rebuild `openchurch` after modification.

Then you should have three instances:

```
docker ps
CONTAINER ID        IMAGE                                                 COMMAND                  CREATED             STATUS              PORTS                                                                NAMES
1651a84b55b9        hozana/openchurch                                     "/data/scripts/docke…"   51 seconds ago      Up 50 seconds       0.0.0.0:3000->3000/tcp, 0.0.0.0:8000->8000/tcp, 0.0.0.0:1819->80/tcp openchurch
7c9484d9ca5f        docker.elastic.co/elasticsearch/elasticsearch:6.3.2   "/usr/local/bin/dock…"   2 minutes ago       Up 51 seconds       0.0.0.0:9200->9200/tcp, 9300/tcp                                     elasticsearch
f368935297ef        mysql:latest                                          "docker-entrypoint.s…"   16 minutes ago      Up 51 seconds       33060/tcp, 0.0.0.0:13306->3306/tcp                                   db
```

If you need to directly hit inside our custom container:

```
docker exec -it container-id /bin/bash
docker exec -it 1651a84b55b9 /bin/bash
```

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

## Deployment

Follow these [guidelines](./docs/setup_openchurch_server.md) to setup prod server. Lost server? [Here](./docs/restore_backup.md) is the process to restore db backup.

## API usage example

TODO
