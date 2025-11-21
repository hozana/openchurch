#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import os
import time
import requests
import datetime
import sentry_sdk
import urllib.parse
import urllib3
import argparse
import redis

from codecs import open
from dotenv import load_dotenv
from sqlalchemy import create_engine, exc, MetaData, Table, orm, func, insert, update
from SPARQLWrapper import SPARQLWrapper, JSON
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from datetime import datetime, timedelta
from time import sleep

load_dotenv(dotenv_path='.env')
sentry_sdk.init(dsn=os.getenv('SENTRY_DSN_SYNCHRO'))
sleep_time = os.getenv('SLEEP_MS_BETWEEN_REQUESTS', 0)

endpoint = "https://query.wikidata.org/bigdata/namespace/wdq/sparql"
agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
churches_query = '''PREFIX schema: <http://schema.org/>
  SELECT DISTINCT ?churches ?P17 ?P18 ?P31 ?P131 ?P281 ?P625 ?P708 ?P856 ?P1644 ?P2971 ?P5607 ?label_fr ?modified WHERE {
  {?churches (wdt:P31/wdt:P279*) wd:Q16970 .}
  ?churches schema:dateModified ?modified
  OPTIONAL {?churches wdt:P17 ?P17 .} # country
  OPTIONAL {?churches wdt:P18 ?P18 .} # image
  OPTIONAL {?churches wdt:P31 ?P31 .} # type
  OPTIONAL {?churches wdt:P131 ?P131 .} # city
  OPTIONAL {?churches wdt:P281 ?P281 .} # zip_code
  OPTIONAL {?churches wdt:P625 ?P625 .} # coordinates
  OPTIONAL {?churches wdt:P708 ?P708 .} # diocese
  OPTIONAL {?churches wdt:P856 ?P856 .} # website
  OPTIONAL {?churches wdt:P1644 ?P1644 .} # messes_info
  OPTIONAL {?churches wdt:P2971 ?P2971 .} # gcatholic
  OPTIONAL {?churches wdt:P5607 ?P5607 .} # parish
  OPTIONAL {?churches rdfs:label ?label_fr filter (lang(?label_fr) = "fr") .}
  SERVICE wikibase:label {bd:serviceParam wikibase:language "fr".} }'''

dioceses_query = '''PREFIX schema: <http://schema.org/>
  SELECT DISTINCT ?dioceses ?P17 ?P31 ?P856 ?P8389 ?label_fr ?modified WHERE {
  {?dioceses (wdt:P31/wdt:P279*) wd:Q665487 .}
  ?dioceses schema:dateModified ?modified
  OPTIONAL {?dioceses wdt:P17 ?P17 .} # country
  OPTIONAL {?dioceses wdt:P31 ?P31 .} # type
  OPTIONAL {?dioceses wdt:P856 ?P856 .} # website
  {?dioceses wdt:P8389 ?P8389 .} # gcatholic
  OPTIONAL {?dioceses rdfs:label ?label_fr filter (lang(?label_fr) = "fr") .}
  SERVICE wikibase:label {bd:serviceParam wikibase:language "fr".} }'''

parishes_query = '''PREFIX schema: <http://schema.org/>
  SELECT DISTINCT ?parishes ?P17 ?P31 ?P281 ?P708 ?P856 ?P6788 ?label_fr ?modified WHERE {
  {?parishes (wdt:P31/wdt:P279*) wd:Q102496 .}
  ?parishes schema:dateModified ?modified
  OPTIONAL {?parishes wdt:P17 ?P17 .} # country
  OPTIONAL {?parishes wdt:P31 ?P31 .} # type
  OPTIONAL {?parishes wdt:P281 ?P281 .} # zip_code
  OPTIONAL {?parishes wdt:P708 ?P708 .} # diocese
  OPTIONAL {?parishes wdt:P856 ?P856 .} # website
  {?parishes wdt:P6788 ?P6788 .} # messes_info
  OPTIONAL {?parishes rdfs:label ?label_fr filter (lang(?label_fr) = "fr") .}
  SERVICE wikibase:label {bd:serviceParam wikibase:language "fr".} }'''

class Query(object):
    verbosity_level = 0

    building_types = [
        16970, # église
        2977, # cathédrale
        120560, # basilique mineure
        160742, # abbaye
        317557, # église paroissiale
        108325, # chapelle
        163687, # basilique
        44613, # monastère
        1509716, # collégiale
        29553, # sanctuaire
        334383, # abbatiale
        1128397, # couvent
        15823129, # chartreuse
        1649060, # pro-cathédrale
        2577114, # co-cathédrale
        744296, # église en bois
        2750108, # prieuré
        6807904, # temple protestant
        56242215, # cathédrale catholique
        55876909, # église paroissiale catholique
    ]
    dioceses_types = [
        285181, # eparchy
        620225, # apostolic vicariate
        2072238, # archdiocese
        2633744, # territorial prelature
        2288631, # archeparchy
        1531518, # military ordinariate
        1778235, # territorial abbey
        1431554, # apostolic administration
        384003, # apostolic prefecture
        3146899, # diocese of the Catholic Church
        665487, # diocese
        3732788, # apostolic exarchate
        105390172, # archdiocese of the Roman Catholic Church
        105406193, # Roman Catholic archdiocese
        105071180, # Catholic Metropolitan Archdiocese
        105072138, # Catholic archdiocese
        105388829, # Roman Catholic diocese
        105419665, # Roman Catholic archdiocese not metropolitan
        7100806, # Ordinariate for Eastern Catholic faithful
        373074, # suffragan diocese
        2665272, # Ecclesiastical district immediately subject to the Holy See
        104964763, # Metropolitan archdiocese headed by a Cardinal
    ]
    dateformat = '%Y-%m-%d %H:%M:%S'

    def __init__(self, verbosity_level):
        self.cache_places = {}
        self.cache_churches = {}
        self.cache_dioceses = {}
        self.cache_parishes = {}
        self.verbosity_level = verbosity_level

    @staticmethod
    def decode(string):
        return urllib.parse.unquote(string.split('/')[-1]).replace('_', ' ')

    @staticmethod
    def ucfirst(myStr):
        if len(myStr) < 1:
            return myStr
        return myStr[0].upper() + myStr[1:]

    @staticmethod
    def get_decoded_value(item, prop, default = None):
        value = Query.get_value(item, prop, default)
        return Query.decode(value) if value else default

    @staticmethod
    def get_value(item, prop, default = None):
        return item[prop]['value'] if prop in item.keys() else default

    @staticmethod
    def get_wikidata_id(item, prop):
        value = Query.get_value(item, prop)
        wikidata_id = value.split('/')[-1] if value else ''

        return int(wikidata_id.replace('Q', '')) if wikidata_id and wikidata_id.startswith('Q') else None

    @staticmethod
    def split_into_batches(lst, batch_size):
        return [lst[i:i + batch_size] for i in range(0, len(lst), batch_size)]

    def fetch(self, file_name, query):
        offset = 0
        batch_size = 50000

        if os.path.isfile(file_name):
            if os.path.getmtime(file_name) > time.time() - 1 * 3600: # cache JSON for 60 mins
                with open(file_name, 'r', encoding='utf-8') as content_file:
                    print('Loading from file', file_name ,'please wait...')
                    return json.loads(content_file.read())
            else:
                os.remove(file_name)

        print('Query running for', file_name, ' - please wait...')
        sparql = SPARQLWrapper(endpoint, agent=agent)
        sparql.setReturnFormat(JSON)

        with open(file_name, 'w', encoding='utf-8') as f:
            f.write('[')
            first_batch = True
            while True:
                try:
                    print(f'Loading data {offset} to {offset + batch_size}...')
                    sparql.setQuery(query + f' LIMIT {batch_size} OFFSET {offset}')
                    data = sparql.query().convert()

                    results = data['results']['bindings']
                    if not results:  # if no result, we stop the loop
                        break

                    # add data to file
                    for result in results:
                        if not first_batch:
                            f.write(',')
                        json.dump(result, f, ensure_ascii=False)
                        first_batch = False
                    offset += batch_size
                except Exception as e:
                    print(f"Failed to load data from {offset} to {offset + batch_size}: {e}")
                offset += batch_size
            f.write(']')  # Fin du tableau JSON

        print(f'Data written in {file_name}.')
        with open(file_name, 'r', encoding='utf-8') as content_file:
            return json.loads(content_file.read())

    def extractDiocesesFromSparqlQuery(self, sparqlData):
        dioceses = {}
        for item in sparqlData:
            wikidata_id = int(item['dioceses']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            gcatholic_id = Query.get_value(item, 'P8389')
            country_id = Query.get_wikidata_id(item, 'P17')

            # dirty hack so that Annecy appears in France and not in Switzerland
            if wikidata_id == 866863: # Annecy
                country_id = 142 # France

            if country_id != 142:
                # For now, we only care about french data
                continue

            if not gcatholic_id:
                continue
            type_ = Query.get_wikidata_id(item, 'P31')
            if not type_ or int(type_) not in Query.dioceses_types:
                continue # ignore item FIXME we may want to delete if from the DB

            website = Query.get_decoded_value(item, 'P856', '')
            label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

            dioceses[wikidata_id] = {
                'type': 'diocese',
                'wikidataId': wikidata_id,
                'name': Query.ucfirst(label_fr),
                'contactCountryCode': 'fr',
                'website': website,
                'wikidataUpdatedAt': str(datetime.strptime(modified, Query.dateformat)),
            }
        return dioceses
    
    def extractParishesFromSparqlQuery(self, sparqlData):
        parishes = {}
        for item in sparqlData:
            wikidata_id = int(item['parishes']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            messesinfo_id = item['P6788']['value'] if 'P6788' in item.keys() else ''
            #type_ = Query.get_wikidata_id(item, 'P31')
            #if not type_ or int(type_) not in Query.parishes_types:
            #    continue # ignore item FIXME we may want to delete if from the DB
            country_id = Query.get_wikidata_id(item, 'P17')
            zip_code = Query.get_value(item, 'P281', '')
            diocese_id = Query.get_wikidata_id(item, 'P708')
            website = Query.get_decoded_value(item, 'P856', '')
            label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

            if country_id != 142:
                # For now, we only care about french data
                continue

            parishes[wikidata_id] = {
                'type': 'parish',
                'wikidataId': wikidata_id,
                'name': Query.ucfirst(label_fr),
                'contactCountryCode': 'fr',
                'contactZipcode': zip_code,
                'parentWikidataId': diocese_id,
                'messesInfoId': messesinfo_id,
                'website': website,
                'wikidataUpdatedAt': modified,
            }
        return parishes
    
    def church_type_to_string(self, type):
        if type == 16970:
            return 'cathedral'
        elif type == 108325:
            return 'chapel'
        elif type == 317557:
            return 'parishHall'
        elif type == 160742:
            return 'abbey'
        else:
            return 'church'
    
    def extractChurchesFromSparqlQuery(self, sparqlData):
        churches = {}
        for item in sparqlData:
            wikidata_id = int(item['churches']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            type_ = Query.get_wikidata_id(item, 'P31')
            if not type_ or int(type_) not in Query.building_types:
                continue # ignore item FIXME we may want to delete if from the DB
            # place_id = Query.get_wikidata_id(item, 'P131') # FIXME manage multiple places
            # if not place_id:
            #     print('No location for Q%s                    ' % (wikidata_id,))
            #     continue
            country_id = Query.get_wikidata_id(item, 'P17')
            image = Query.get_decoded_value(item, 'P18', '')
            zip_code = Query.get_value(item, 'P281', '')
            diocese_id = Query.get_wikidata_id(item, 'P708')
            parish_id = Query.get_wikidata_id(item, 'P5607')
            point = Query.get_value(item, 'P625', '')
            coordinates = point.replace('Point(', '').replace(')', '').split(' ') if point.startswith('Point') else ''
            latitude = coordinates[1] if coordinates else 0
            longitude = coordinates[0] if coordinates else 0
            website = Query.get_decoded_value(item, 'P856', '')
            label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''
            messesinfo_id = item['P1644']['value'] if 'P1644' in item.keys() else ''
            gcatholic_id = item['P2971']['value'] if 'P2971' in item.keys() else ''

            if country_id != 142:
                # For now, we only care about french data
                continue

            churches[wikidata_id] = {
                'wikidataId': wikidata_id,
                'type': self.church_type_to_string(type_),
                'parentWikidataIds': [parish_id],
                'countryCode': 'fr',
                'messesInfoId': messesinfo_id,
                'website': website,
                'zipcode': zip_code,
                'name': Query.ucfirst(label_fr),
                'latitude': float(latitude),
                'longitude': float(longitude),
                'wikidataUpdatedAt': str(datetime.strptime(modified, Query.dateformat)),
            }
        return churches

    def update_dioceses(self, sparqlData, client):
        wikidataEntities = {'wikidataEntities': []}
        wikidataIdDioceses = self.extractDiocesesFromSparqlQuery(sparqlData)
        for wikidataId in wikidataIdDioceses:
            fields = client.populate_fields(wikidataIdDioceses[wikidataId], wikidataId)
            wikidataEntities['wikidataEntities'].append(fields)
        if len(wikidataEntities['wikidataEntities']) > 0:
            self.print_logs(wikidataEntities['wikidataEntities'], 2)
            response = client.upsert_wikidata_entities('/communities/upsert', wikidataEntities)
            self.print_logs(response, 1)
            return response
        return "skipped"

    def update_parishes(self, sparqlData, client):
        wikidataEntities = {'wikidataEntities': []}
        wikidataIdParishes = self.extractParishesFromSparqlQuery(sparqlData)
        for wikidataId in wikidataIdParishes:
            fields = client.populate_fields(wikidataIdParishes[wikidataId], wikidataId)
            wikidataEntities['wikidataEntities'].append(fields)
        if len(wikidataEntities['wikidataEntities']) > 0:
            self.print_logs(wikidataEntities['wikidataEntities'], 2)
            response = client.upsert_wikidata_entities('/communities/upsert', wikidataEntities)
            self.print_logs(response, 1)
            return response
        return "skipped"

    def update_churches(self, sparqlData, client):
        wikidataEntities = {'wikidataEntities': []}
        wikidataIdChurches = self.extractChurchesFromSparqlQuery(sparqlData)
        for wikidataId in wikidataIdChurches:
            fields = client.populate_fields(wikidataIdChurches[wikidataId], wikidataId)
            wikidataEntities['wikidataEntities'].append(fields)
        if len(wikidataEntities['wikidataEntities']) > 0:
            self.print_logs(wikidataEntities['wikidataEntities'], 2)
            response = client.upsert_wikidata_entities('/places/upsert', wikidataEntities)
            self.print_logs(response, 1)
            return response
        return "skipped"

    def print_logs(self, data, required_level):
        if self.verbosity_level >= required_level:
            print(json.dumps(data, indent=4, ensure_ascii=False))

class UuidDoesNotExistException(Exception):
    pass

class OpenChurchClient(object):
    urllib3.disable_warnings(category=urllib3.exceptions.InsecureRequestWarning)
    hostname = os.getenv('OPENCHURCH_HOST')
    headers = {
        'Authorization': 'Bearer ' + os.getenv('SYNCHRO_SECRET_KEY')
    }
    session = requests.Session()
    # Configure retries and timeouts
    retry_strategy = Retry(
        total=1,
        backoff_factor=1,
        status_forcelist=[429, 500, 502, 503, 504]
    )
    # Configuration de l'adaptateur avec connection pooling
    adapter = HTTPAdapter(
        pool_connections=10,    # Nombre de connexions à garder
        pool_maxsize=10,        # Taille maximale du pool
        max_retries=retry_strategy,
        pool_block=False        # Ne pas bloquer quand le pool est plein
    )
    # Monter l'adaptateur pour HTTP et HTTPS
    session.mount("https://", adapter)
    # Configuration des timeouts
    session.request_timeout = (3.05, 27)
        
    def upsert_wikidata_entities(self, path, body):
        try:
            response = self.session.put(self.hostname + path, json=body, headers=self.headers, verify=False)
            if response.status_code == 200:
                data = response.json()
                return data
            else:
                print(response.status_code, 'for PUT', path)
                return None
        except requests.exceptions.RequestException as e:
            return None

    def populate_fields(self, values, wikidata_id):
        fields = []
        for name, value in values.items():
            if value:
                fields.append({
                    'name': name,
                    'value': value,
                    'reliability': 'high',
                    'engine': 'scraper',
                    'source': 'Wikidata',
                    'explanation': 'https://www.wikidata.org/wiki/Q'+format(wikidata_id),
                })
        return fields
    
class Processor(object):
    client = OpenChurchClient()
    redis_url = os.getenv('REDIS_URL')
    redis_client = redis.from_url(redis_url)

    def __init__(self, verbosity_level, type, batch_size):
        self.q = Query(verbosity_level=verbosity_level)
        self.verbosity_level = verbosity_level
        self.type = type
        self.batch_size = batch_size

    def process_batch(self, data, method, run_id):
        batches = Query.split_into_batches(data, self.batch_size)
        self.redis_client.hset(self.type, "batchCount", len(batches))
        iteration = 1
        for batch in batches:
            can_process = True
            self.redis_client.hset(self.type, "currentBatch", iteration)
            key_batch = get_redis_key(self.type, (iteration - 1) * self.batch_size, (iteration) * self.batch_size)
            value_batch = self.redis_client.hgetall(key_batch)
            if value_batch:
                # A key exist. We chek if we can process it
                decoded_data = {key.decode('utf-8'): value_batch.decode('utf-8') for key, value_batch in value_batch.items()}
                current_run_id = decoded_data.get('runId')
                if current_run_id == run_id:
                    can_process = False # This have already been processed. We skip it
            if can_process:
                self.redis_client.hset(key_batch, "status", "processing")
                self.redis_client.hset(key_batch, "updatedAt", str(datetime.now()))
                self.redis_client.hset(key_batch, "runId", run_id)
                print("Processing batch %s/%s" % (iteration, len(batches)))
                res = getattr(self.q, method)(batch, self.client)
                if res == "skipped":
                    self.redis_client.hset(key_batch, "successCount", "skipped")
                    self.redis_client.hset(key_batch, "failureCount", "skipped")
                    self.redis_client.hset(key_batch, "status", "skipped")
                elif res:
                    success_count = sum(1 for value in res.values() if value in {'Updated', 'Inserted'})
                    self.redis_client.hset(key_batch, "successCount", success_count)
                    self.redis_client.hset(key_batch, "failureCount", len(res) - success_count)
                    self.redis_client.hset(key_batch, "status", "success")
                else:
                    self.redis_client.hset(key_batch, "status", "error")
            else:
                print("Ignore batch %s/%s" % (iteration, len(batches)))
            sleep(int(sleep_time) * 0.001)
            iteration += 1

    def process_entity(self):
        print("starting synchro for", self.type)
        if self.type == "diocese":
            data = self.q.fetch('wikidata_dioceses.json', dioceses_query)
            method = "update_dioceses"
        elif self.type == "parish":
            data = self.q.fetch('wikidata_parishes.json', parishes_query)
            method = "update_parishes"
        elif self.type == "church":
            data = self.q.fetch('wikidata_churches.json', churches_query)
            method = "update_churches"
        else:
            raise("Unknown entity type %s" % self.type)
        
        value_entity = self.redis_client.hgetall(self.type)
        if value_entity:
            decoded_data = {key.decode('utf-8'): value_entity.decode('utf-8') for key, value_entity in value_entity.items()}
            run_id = decoded_data.get('runId')
            if decoded_data.get('status') in {'processing'}:
                self.process_batch(data, method, run_id)
            else:
                self.clean_entity(int(run_id) + 1)
                self.process_batch(data, method, int(run_id) + 1)
        else:
            self.clean_entity(1)
            self.process_batch(data, method, 1)

        self.redis_client.hset(self.type, "status", "success")
        self.redis_client.hset(self.type, "endDate", str(datetime.now()))
        print("ended synchro for", self.type)

    def clean_entity(self, run_id):
        self.redis_client.hset(self.type, "runId", run_id)
        self.redis_client.hset(self.type, "startDate", str(datetime.now()))
        self.redis_client.hset(self.type, "status", "processing")
        self.redis_client.hset(self.type, "batchSize", self.batch_size)
        self.redis_client.hdel(self.type, "endDate")

def percentage(num, total):
    return '%s = %s%%' % (num, (round(100 * num / total, 2)))

def get_redis_key(type, origin, to):
    return '%s_%s-%s' % (type, origin, to)

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument("--entity-only", type=str, required=True, choices=["parish", "diocese", "church"], help="Spécifiez l'entité à traiter : 'diocese', 'parish' ou 'church'")
    parser.add_argument("-v", "--verbose", action="count", default=0, help="Augmente le niveau de verbosité (utilisez -vvv pour plus de détails).")
    args = parser.parse_args()

    processor = Processor(verbosity_level=args.verbose, type=args.entity_only, batch_size=30)
    processor.process_entity()