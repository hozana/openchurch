#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import re
import os
import sys
import time
import requests
import datetime
import pywikibot
import sentry_sdk
import urllib.parse
import urllib3

from codecs import open
from dotenv import load_dotenv
from sqlalchemy import create_engine, exc, MetaData, Table, orm, func, insert, update
from SPARQLWrapper import SPARQLWrapper, JSON
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

load_dotenv(dotenv_path='.env')
sentry_sdk.init(dsn=os.getenv('SENTRY_DSN_SYNCHRO'))

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
    ]
    dateformat = '%Y-%m-%d %H:%M:%S'

    def __init__(self):
        self.cache_places = {}
        self.cache_churches = {}
        self.cache_dioceses = {}
        self.cache_parishes = {}

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

    def fetch(self, type, query):
        if os.path.isfile(type):
            if os.path.getmtime(type) > time.time() - 12 * 3600: # cache JSON for 12 hours
                with open(type, 'r', encoding='utf-8') as content_file:
                    print('Loading from file', type ,'please wait...')
                    return json.loads(content_file.read())
            else:
                os.remove(type)

        print('Query running for', type, ' - please wait...')
        sparql = SPARQLWrapper(endpoint, agent=agent)
        sparql.setReturnFormat(JSON)

        # print('Loaded ' + str(offset + batch_size) + ' entries. Please wait...')
        sparql.setQuery(query)
        data = sparql.query().convert()
        json.dump(data['results']['bindings'], open(type, 'wb', encoding='utf-8'))
        return data['results']['bindings']

    def update_churches(self, data):
        t = len(data)
        print(t, 'churches loaded')
        i = 0
        j = 0
        for item in data:
            i += 1
            if i % DB.commit_frequency == 0:
                DB.session.commit()
            wikidata_id = int(item['churches']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            if wikidata_id in self.cache_churches and self.cache_churches[wikidata_id] == modified:
                print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> continue', end='    \r')
                continue
            print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> update', end='    \r')
            type_ = Query.get_wikidata_id(item, 'P31')
            if not type_ or int(type_) not in Query.building_types:
                continue # ignore item FIXME we may want to delete if from the DB
            place_id = Query.get_wikidata_id(item, 'P131') # FIXME manage multiple places
            if not place_id:
                print('No location for Q%s                    ' % (wikidata_id,))
                continue
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

            church = {
                'place_id': place_id,
                'diocese_id': diocese_id,
                'parish_id': parish_id,
                'country_id': country_id,
                'messesinfo_id': messesinfo_id,
                'gcatholic_id': gcatholic_id,
                'website': website,
                'name': Query.ucfirst(label_fr),
                'latitude': latitude,
                'longitude': longitude,
                'address': '',
                'updated_at': datetime.datetime.strptime(modified, Query.dateformat),
            }

            if wikidata_id in self.cache_churches:
                up = update(DB.churches, DB.churches.c.wikidata_id==wikidata_id)
                up = up.values(church)
                DB.session.execute(up)
            else:
                church['wikidata_id'] = wikidata_id
                church['created_at'] = DB.now
                ins = insert(DB.churches)
                ins = ins.values(church)
                DB.session.execute(ins)

            self.cache_churches[wikidata_id] = modified
        DB.session.commit()
        print('\nFinished')

    def extractDiocesesFromSparqlQuery(self, sparqlData):
        dioceses = {}
        for item in sparqlData:
            wikidata_id = int(item['dioceses']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            gcatholic_id = Query.get_value(item, 'P8389')

            if not gcatholic_id:
                continue
            type_ = Query.get_wikidata_id(item, 'P31')
            if not type_ or int(type_) not in Query.dioceses_types:
                continue # ignore item FIXME we may want to delete if from the DB
            country_id = Query.get_wikidata_id(item, 'P17')
            website = Query.get_decoded_value(item, 'P856', '')
            label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

            # dirty hack so that Annecy appears in France and not in Switzerland
            if wikidata_id == 866863: # Annecy
                country_id = 142 # France

            if country_id != 142:
                # For now, we only care about french data
                continue

            dioceses[wikidata_id] = {
                'wikidataId': wikidata_id,
                'name': Query.ucfirst(label_fr),
                'contactCountryCode': 'fr',
                'website': website,
                'wikidataUpdatedAt': str(datetime.datetime.strptime(modified, Query.dateformat)),
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

            parishes[wikidata_id] = {
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
    
    def extractChurchesFromSparqlQuery(self, sparqlData, client):
        churches = {}
        for item in sparqlData:
            wikidata_id = int(item['churches']['value'].split('/')[-1].replace('Q', ''))
            modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
            type_ = Query.get_wikidata_id(item, 'P31')
            if not type_ or int(type_) not in Query.building_types:
                continue # ignore item FIXME we may want to delete if from the DB
            place_id = Query.get_wikidata_id(item, 'P131') # FIXME manage multiple places
            if not place_id:
                print('No location for Q%s                    ' % (wikidata_id,))
                continue
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

            church = {
                'place_id': place_id,
                'parentWikidataIds': [diocese_id, parish_id],
                'contactCountryCode': 'fr',
                'messesInfoId': messesinfo_id,
                'website': website,
                'contactZipcode': zip_code,
                'name': Query.ucfirst(label_fr),
                'latitude': latitude,
                'longitude': longitude,
                'updated_at': str(datetime.datetime.strptime(modified, Query.dateformat)),
            }
        return churches


    def update_dioceses(self, sparqlData, client):
        wikidataEntities = {'wikidataEntities': []}
        wikidataIdDioceses = self.extractDiocesesFromSparqlQuery(sparqlData)
        for wikidataId in wikidataIdDioceses:
            fields = client.populate_fields('diocese', wikidataIdDioceses[wikidataId], wikidataId)
            wikidataEntities['wikidataEntities'].append(fields)
        if len(wikidataEntities['wikidataEntities']) > 0:
            print(wikidataEntities)
            response = client.upsert_wikidata_entities('/communities/upsert', wikidataEntities)
            print('DIOCESES : ', response)

    def update_parishes(self, sparqlData, client):
        wikidataEntities = {'wikidataEntities': []}
        wikidataIdDioceses = self.extractParishesFromSparqlQuery(sparqlData, client)
        for wikidataId in wikidataIdDioceses:
            fields = client.populate_fields('parish', wikidataIdDioceses[wikidataId], wikidataId)
            wikidataEntities['wikidataEntities'].append(fields)
        print(wikidataEntities)
        response = client.upsert_wikidata_entities('/communities/upsert', wikidataEntities)
        print("PARISHES : ", response)

class UuidDoesNotExistException(Exception):
    pass

class OpenChurchClient(object):
    urllib3.disable_warnings(category=urllib3.exceptions.InsecureRequestWarning)
    hostname = os.getenv('OPENCHURCH_HOST')
    headers = {
        'Authorization': 'Bearer ' + os.getenv('SYNCHRO_SECRET_KEY')
    }

    def create_session(self):
        session = requests.Session()
        
        # Configuration des retries et timeouts
        retry_strategy = Retry(
            total=3,
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
        
        return session

    def post(self, path, fields):
        session = self.create_session()
        response = session.post(self.hostname + path, json=fields, headers=self.headers, verify=False)
        print(fields)
        if response.status_code == 200:
            data = response.json()
            return data['id']
        else:
            print('API error for POST with data', fields)
            print(response.status_code, response.text)
            return None

    def patch(self, path, fields):
        session = self.create_session()
        response = session.patch(self.hostname + path, json=fields, headers=self.headers, verify=False)
        if response.status_code == 200:
            data = response.json()
            return data['id']
        elif response.status_code == 404 and True:
            print(response.status_code, response.text, 'for PATCH', path)
            raise UuidDoesNotExistException
        else:
            print('API error for PATCH with data', fields)
            print(response.status_code, response.text, 'for PATCH', path)
            return None
        
    def upsert_wikidata_entities(self, path, body):
        session = self.create_session()
        response = session.put(self.hostname + path, json=body, headers=self.headers, verify=False)
        if response.status_code == 200:
            data = response.json()
            return data
        elif response.status_code == 404 and True:
            print(response.status_code, response.text, 'for GET', path)
            raise UuidDoesNotExistException
        else:
            print(response.status_code, response.text, 'for PUT', path, body)
            return None

    def populate_fields(self, type, values, wikidata_id):
        values['type'] = type

        fields = []
        for name, value in values.items():
            fields.append({
                'name': name,
                'value': value,
                'reliability': 'high',
                'engine': 'scraper',
                'source': 'Wikidata',
                'explanation': 'https://www.wikidata.org/wiki/Q'+format(wikidata_id),
            })
        return fields

    def push_parishes(self):
        session = self.create_session()
        parishes = DB.con.execute('''SELECT parishes.wikidata_id, parishes.name, parishes.messesinfo_id, parishes.zip_code, parishes.website, iso_code, parishes.openchurch_id, dioceses.openchurch_id as diocese_openchurch_id
            FROM parishes
            LEFT JOIN places ON parishes.country_id = places.wikidata_id
            LEFT JOIN dioceses ON parishes.diocese_id = dioceses.wikidata_id
            WHERE (parishes.pushed_at IS NULL OR parishes.openchurch_id IS NULL) AND parishes.country_id = 142''').fetchall()
        i = 0
        for [wikidata_id, name, messesinfo_id, zip_code, website, iso_code, openchurch_id, diocese_openchurch_id] in parishes:
            i += 1
            print('(%s/%s) - ' % (i, len(parishes)), end='')
            data = {'wikidataId': wikidata_id, 'type': 'parish'}
            if name:
                data['name'] = name
            if messesinfo_id:
                data['messesInfoId'] = messesinfo_id
            if iso_code:
                data['contactCountryCode'] = iso_code.lower()
            if website:
                data['website'] = website
            if website:
                data['contactZipcode'] = zip_code
            if diocese_openchurch_id:
                data['parentCommunityId'] = diocese_openchurch_id
            fields = self.populate_fields(data, wikidata_id)
            if openchurch_id:
                try:
                    self.patch('/communities/' + openchurch_id, fields)
                except UuidDoesNotExistException:
                    openchurch_id = None # force reset local UUID
            else:
                id = self.post('/communities', fields)
            up = update(DB.parishes, DB.parishes.c.wikidata_id==wikidata_id)
            up = up.values({'openchurch_id': id, 'pushed_at': DB.now})
            DB.session.execute(up)
            DB.session.commit()
    
    def get_openchurch_entity(self, wikidata_id):
        openchurchEntities = self.get('/communities?' + 'wikidataId=' + str(wikidata_id) + '&itemsPerPage=1')
        if len(openchurchEntities) > 0:
            return openchurchEntities[0]
        return None

    def push_churches(self):
        session = self.create_session()
        churches = DB.con.execute('''SELECT churches.wikidata_id, churches.name, churches.messesinfo_id, churches.zip_code, churches.website, churches.latitude, churches.longitude,
                iso_code, churches.openchurch_id, parishes.openchurch_id as parish_openchurch_id
            FROM churches
            LEFT JOIN places ON churches.country_id = places.wikidata_id
            LEFT JOIN parishes ON churches.parish_id = parishes.wikidata_id
            WHERE (churches.pushed_at IS NULL OR churches.openchurch_id IS NULL) AND churches.country_id = 142''').fetchall()
        i = 0
        for [wikidata_id, name, messesinfo_id, zip_code, website, latitude, longitude, iso_code, openchurch_id, parish_openchurch_id] in churches:
            i += 1
            print('(%s/%s) - ' % (i, len(churches)), end='')
            data = {'wikidataId': wikidata_id, 'type': 'church'}
            if name:
                data['name'] = name
            if messesinfo_id:
                data['messesInfoId'] = messesinfo_id
            if iso_code:
                data['countryCode'] = iso_code.lower()
            if website:
                data['website'] = website
            if website:
                data['zipcode'] = zip_code
            if latitude and longitude:
                data['latitude'] = float(latitude)
                data['longitude'] = float(longitude)
            if parish_openchurch_id:
                data['parentCommunities'] = [parish_openchurch_id]
            fields = self.populate_fields(data, wikidata_id)
            if openchurch_id:
                try:
                    self.patch('/places/' + openchurch_id, fields)
                except UuidDoesNotExistException:
                    openchurch_id = None # force reset local UUID
            else:
                id = self.post('/places', fields)
            up = update(DB.churches, DB.churches.c.wikidata_id==wikidata_id)
            up = up.values({'openchurch_id': id, 'pushed_at': DB.now})
            DB.session.execute(up)
            DB.session.commit()

def percentage(num, total):
    return '%s = %s%%' % (num, (round(100 * num / total, 2)))

if __name__ == '__main__':
    arg = sys.argv[1] if len(sys.argv) > 1 else ''
    if arg == 'stats':
        print('Statistics')

        places = DB.con.execute('SELECT COUNT(*) FROM places').fetchone()[0]
        print('Places:', places)

        churches = DB.con.execute('SELECT COUNT(*) FROM churches').fetchone()[0]
        print('Churches:', churches)

        catholic_churches = DB.con.execute('SELECT COUNT(*) FROM churches WHERE messesinfo_id != ""').fetchone()[0]
        print('\twith MessesInfoId:', percentage(catholic_churches, churches))

        churches_diocese = DB.con.execute('SELECT COUNT(*) FROM churches WHERE diocese_id IS NOT NULL').fetchone()[0]
        print('\twith Diocese:', percentage(churches_diocese, churches))

        churches_parish = DB.con.execute('SELECT COUNT(*) FROM churches WHERE parish_id IS NOT NULL').fetchone()[0]
        print('\twith Parish:', percentage(churches_parish, churches))

        churches_diocese_or_parish = DB.con.execute('SELECT COUNT(*) FROM churches WHERE parish_id IS NOT NULL OR diocese_id IS NOT NULL').fetchone()[0]
        print('\twith Diocese or Parish:', percentage(churches_diocese_or_parish, churches))

        dioceses = DB.con.execute('SELECT COUNT(*) FROM dioceses').fetchone()[0]
        print('Dioceses:', dioceses)

        catholic_dioceses = DB.con.execute('SELECT COUNT(*) FROM dioceses WHERE gcatholic_id != ""').fetchone()[0]
        print('\twith GCatholicId:', percentage(catholic_dioceses, dioceses))

        parishes = DB.con.execute('SELECT COUNT(*) FROM parishes').fetchone()[0]
        print('Parishes:', parishes)

        catholic_parishes = DB.con.execute('SELECT COUNT(*) FROM parishes WHERE messesinfo_id != ""').fetchone()[0]
        print('\twith MessesInfoId:', percentage(catholic_parishes, parishes))
    elif arg == 'push':
        c = OpenChurchClient()
        dioceses = DB.con.execute('SELECT COUNT(*) FROM dioceses WHERE country_id=142 AND (pushed_at IS NULL OR openchurch_id IS NULL)').fetchone()[0]
        print('Dioceses to push to OpenChurch:', dioceses)
        if dioceses:
            c.push_dioceses()
        parishes = DB.con.execute('SELECT COUNT(*) FROM parishes WHERE country_id=142 AND (pushed_at IS NULL OR openchurch_id IS NULL)').fetchone()[0]
        print('Parishes to push to OpenChurch:', parishes)
        if parishes:
            c.push_parishes()
        churches = DB.con.execute('SELECT COUNT(*) FROM churches WHERE country_id=142 AND (pushed_at IS NULL OR openchurch_id IS NULL)').fetchone()[0]
        print('Churches to push to OpenChurch:', churches)
        if churches:
            c.push_churches()
    elif arg == 'test':
        c = OpenChurchClient()
        c.community_exist('-20', 'diocese')
    else:
        q = Query()
        client = OpenChurchClient()
        batch_size = 50

        dioceses = q.fetch('diocese', dioceses_query)
        batches = Query.split_into_batches(dioceses, batch_size)
        for batch in batches:
            q.update_dioceses(batch, client)

        parishes = q.fetch('parish', parishes_query)
        batches = Query.split_into_batches(parishes, batch_size)
        for batch in batches:
            q.update_parishes(batch, client)

        churches = q.fetch('church', churches_query)
        batches = Query.split_into_batches(churches, batch_size)
        for batch in batches:
            q.update_churches(batch, client)

        # parishes = q.fetch('wikidata_parishes.json', parishes_query)
        # q.update_parishes(parishes)
        # churches = q.fetch('wikidata_churches.json', churches_query)
        # q.update_churches(churches)