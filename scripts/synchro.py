#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import re
import os
import sys
import time
import datetime
import pywikibot
import urllib.parse

from codecs import open
from dotenv import load_dotenv
from sqlalchemy import create_engine, exc, MetaData, Table, orm, func, insert, update
from SPARQLWrapper import SPARQLWrapper, JSON

endpoint = "https://query.wikidata.org/bigdata/namespace/wdq/sparql"
agent='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
churches_query = '''PREFIX schema: <http://schema.org/>
  SELECT DISTINCT ?churches ?P17 ?P18 ?P31 ?P131 ?P625 ?P708 ?P1644 ?P5607 ?label_fr ?modified WHERE {
  {?churches (wdt:P31/wdt:P279*) wd:Q16970 .}
  ?churches schema:dateModified ?modified
  OPTIONAL {?churches wdt:P17 ?P17 .} # country
  OPTIONAL {?churches wdt:P18 ?P18 .} # image
  OPTIONAL {?churches wdt:P31 ?P31 .} # type
  OPTIONAL {?churches wdt:P131 ?P131 .} # city
  OPTIONAL {?churches wdt:P625 ?P625 .} # coordinates
  OPTIONAL {?churches wdt:P708 ?P708 .} # diocese
  OPTIONAL {?churches wdt:P1644 ?P1644 .} # messes_info
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

class DB:
    now = func.current_timestamp()
    load_dotenv(dotenv_path='../.env')
    host = os.getenv('DB_HOST')
    port = os.getenv('DB_PORT')
    database = os.getenv('MYSQL_DATABASE')
    user = os.getenv('MYSQL_USER')
    password = os.getenv('MYSQL_PASSWORD')
    dsn = 'mysql+pymysql://%s:%s@%s:%s/%s' % (user, password, host, port, database)
    engine = create_engine(dsn)
    con = engine.connect()
    metadata = MetaData(bind=engine)
    session = orm.sessionmaker(bind=engine)()
    places = Table('places', metadata, autoload=True)
    churches = Table('wikidata_churches', metadata, autoload=True)
    dioceses = Table('dioceses', metadata, autoload=True)
    parishes = Table('parishes', metadata, autoload=True)

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
        7100806, # Ordinariate for Eastern Catholic faithful
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


    def init_caches(self):
        if not len(self.cache_places):
            places = DB.con.execute('SELECT place_id FROM places').fetchall()
            self.cache_places = [wikidata_id for (wikidata_id,) in places]
        print(len(self.cache_places), 'places in cache')
        if not len(self.cache_churches):
            churches = DB.con.execute('SELECT wikidata_church_id, updated_at FROM wikidata_churches').fetchall()
            self.cache_churches = {wikidata_id: date_time for (wikidata_id, date_time) in churches}
        print(len(self.cache_churches), 'churches in cache')
        if not len(self.cache_dioceses):
            dioceses = DB.con.execute('SELECT diocese_id, updated_at FROM dioceses').fetchall()
            self.cache_dioceses = {wikidata_id: date_time for (wikidata_id, date_time) in dioceses}
        print(len(self.cache_dioceses), 'dioceses in cache')
        if not len(self.cache_parishes):
            parishes = DB.con.execute('SELECT parish_id, updated_at FROM parishes').fetchall()
            self.cache_parishes = {wikidata_id: date_time for (wikidata_id, date_time) in parishes}
        print(len(self.cache_parishes), 'parishes in cache')

    def fetch(self, filename, query):
        if os.path.isfile(filename) and os.path.getmtime(filename) > time.time() - 12 * 3600: # cache JSON for 12 hours
            with open(filename, 'r', encoding='utf-8') as content_file:
                print('Loading from file, please wait...')
                return json.loads(content_file.read())

        print('Query running, please wait...')
        sparql = SPARQLWrapper(endpoint, agent=agent)
        sparql.setQuery(query)
        sparql.setReturnFormat(JSON)
        data = sparql.query().convert()

        if len(data) > 0:
            json.dump(data, open(filename, 'wb', encoding='utf-8'))

        return data

    def add_location(self, wikidata_id, country = False):
        datapage = pywikibot.ItemPage(pywikibot.Site('fr').data_repository(), 'Q%s' % (wikidata_id,))
        if datapage.exists():
            claims = datapage.claims if datapage.claims else {}
            labels = datapage.labels
            parent_id = int(claims['P131'][0].getTarget().title().replace('Q', '')) if 'P131' in claims else '' # FIXME manage multiple parents
            latitude = claims['P625'][0].getTarget().lat if 'P625' in claims and claims['P625'][0].getTarget() else 0
            longitude = claims['P625'][0].getTarget().lon if 'P625' in claims and claims['P625'][0].getTarget() else 0
            name = labels['fr'] if 'fr' in labels else labels['en'] if 'en' in labels else next(iter(labels.values()))
            name = Query.ucfirst(name)
            place_type = 'country' if country else 'unknown'
            if parent_id and parent_id not in self.cache_places:
                self.add_location(parent_id)
            ins = insert(DB.places)
            ins = ins.values({'place_id': wikidata_id, 'name': name, 'parent_id': parent_id or None, 'type': place_type, 'created_at': DB.now, 'updated_at': DB.now})
            DB.session.execute(ins)
            print('Notice: place Q%s "%s" added' % (wikidata_id, name))
            self.cache_places.append(wikidata_id)
            return wikidata_id
        return False

    def update_churches(self, data):
        if 'results' in data.keys() and 'bindings' in data['results'].keys():
            t = len(data['results']['bindings'])
            print(t, 'churches loaded')
            i = 0
            j = 0
            for item in data['results']['bindings']:
                i += 1
                if i % 1000 == 0:
                    DB.session.commit()
                wikidata_id = int(item['churches']['value'].split('/')[-1].replace('Q', ''))
                modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
                if wikidata_id in self.cache_churches and self.cache_churches[wikidata_id].strftime(Query.dateformat) == modified:
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
                diocese_id = Query.get_wikidata_id(item, 'P708')
                parish_id = Query.get_wikidata_id(item, 'P5607')
                commonscat = Query.get_decoded_value(item, 'P373', '')
                point = Query.get_value(item, 'P625', '')
                coordinates = point.replace('Point(', '').replace(')', '').split(' ') if point.startswith('Point') else ''
                latitude = coordinates[1] if coordinates else 0
                longitude = coordinates[0] if coordinates else 0
                label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''
                messesinfo_id = item['P1644']['value'] if 'P1644' in item.keys() else ''

                if place_id not in self.cache_places:
                    self.add_location(place_id)

                if country_id and country_id not in self.cache_places:
                    self.add_location(country_id, True)

                if diocese_id and diocese_id not in self.cache_dioceses:
                    diocese_id = None

                if parish_id and parish_id not in self.cache_parishes:
                    if messesinfo_id:
                        print('Could not find Parish', parish_id)
                    parish_id = None

                church = {
                  'place_id': place_id,
                  'diocese_id': diocese_id,
                  'parish_id': parish_id,
                  'messesinfo_id': messesinfo_id,
                  'name': Query.ucfirst(label_fr),
                  'latitude': latitude,
                  'longitude': longitude,
                  'address': '',
                  'updated_at': modified,
                }

                if wikidata_id in self.cache_churches:
                    up = update(DB.churches, DB.churches.c.wikidata_church_id==wikidata_id)
                    up = up.values(church)
                    DB.session.execute(up)
                else:
                    church['wikidata_church_id'] = wikidata_id
                    church['created_at'] = DB.now
                    ins = insert(DB.churches)
                    ins = ins.values(church)
                    DB.session.execute(ins)

                self.cache_churches[wikidata_id] = datetime.datetime.strptime(modified, Query.dateformat)
            DB.session.commit()
            # FIXME then do: insert into churches (wikidata_church_id) select wikidata_church_id from wikidata_churches where wikidata_church_id not in (select wikidata_church_id from churches)
            print('\nFinished')

    def update_dioceses(self, data):
        if 'results' in data.keys() and 'bindings' in data['results'].keys():
            t = len(data['results']['bindings'])
            print(t, 'dioceses loaded')
            i = 0
            j = 0
            for item in data['results']['bindings']:
                i += 1
                if i % 1000 == 0:
                    DB.session.commit()
                wikidata_id = int(item['dioceses']['value'].split('/')[-1].replace('Q', ''))
                modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
                if wikidata_id in self.cache_dioceses and self.cache_dioceses[wikidata_id].strftime(Query.dateformat) == modified:
                    print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> continue', end='    \r')
                    continue
                gcatholic_id = Query.get_value(item, 'P8389')
                if not gcatholic_id:
                    continue
                print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> update', end='    \r')
                type_ = Query.get_wikidata_id(item, 'P31')
                if not type_ or int(type_) not in Query.dioceses_types:
                    continue # ignore item FIXME we may want to delete if from the DB
                country_id = Query.get_wikidata_id(item, 'P17')
                website = Query.decode(item['P856']['value']) if 'P856' in item.keys() else ''
                label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

                if country_id and country_id not in self.cache_places:
                    self.add_location(country_id, True)

                diocese = {
                  'name': Query.ucfirst(label_fr),
                  'country_id': country_id,
                  'gcatholic_id': gcatholic_id,
                  'website': website,
                  'updated_at': modified,
                }

                if wikidata_id in self.cache_dioceses:
                    up = update(DB.dioceses, DB.dioceses.c.diocese_id==wikidata_id)
                    up = up.values(diocese)
                    DB.session.execute(up)
                else:
                    diocese['diocese_id'] = wikidata_id
                    diocese['created_at'] = DB.now
                    ins = insert(DB.dioceses)
                    ins = ins.values(diocese)
                    DB.session.execute(ins)

                self.cache_dioceses[wikidata_id] = datetime.datetime.strptime(modified, Query.dateformat)
            DB.session.commit()
            print('\nFinished')

    def update_parishes(self, data):
        if 'results' in data.keys() and 'bindings' in data['results'].keys():
            t = len(data['results']['bindings'])
            print(t, 'parishes loaded')
            i = 0
            j = 0
            for item in data['results']['bindings']:
                i += 1
                if i % 1000 == 0:
                    DB.session.commit()
                wikidata_id = int(item['parishes']['value'].split('/')[-1].replace('Q', ''))
                modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
                if wikidata_id in self.cache_parishes and self.cache_parishes[wikidata_id].strftime(Query.dateformat) == modified:
                    print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> continue', end='    \r')
                    continue
                messesinfo_id = item['P6788']['value'] if 'P6788' in item.keys() else ''
                print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> update', end='    \r')
                #type_ = Query.get_wikidata_id(item, 'P31')
                #if not type_ or int(type_) not in Query.parishes_types:
                #    continue # ignore item FIXME we may want to delete if from the DB
                country_id = Query.get_wikidata_id(item, 'P17')
                zip_code = Query.get_value(item, 'P281', '')
                diocese_id = Query.get_wikidata_id(item, 'P708')
                website = Query.get_decoded_value(item, 'P856', '')
                label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

                if country_id and country_id not in self.cache_places:
                    self.add_location(country_id, True)

                if diocese_id and diocese_id not in self.cache_dioceses:
                    diocese_id = None

                parish = {
                  'name': Query.ucfirst(label_fr),
                  'country_id': country_id,
                  'zip_code': zip_code,
                  'diocese_id': diocese_id,
                  'messesinfo_id': messesinfo_id,
                  'website': website,
                  'updated_at': modified,
                }

                if wikidata_id in self.cache_parishes:
                    up = update(DB.parishes, DB.parishes.c.parish_id==wikidata_id)
                    up = up.values(parish)
                    DB.session.execute(up)
                else:
                    parish['parish_id'] = wikidata_id
                    parish['created_at'] = DB.now
                    ins = insert(DB.parishes)
                    ins = ins.values(parish)
                    DB.session.execute(ins)

                self.cache_parishes[wikidata_id] = datetime.datetime.strptime(modified, Query.dateformat)
            DB.session.commit()
            print('\nFinished')

def percentage(num, total):
    return '%s = %s%%' % (num, (round(100 * num / total, 2)))

if __name__ == '__main__':
    arg = sys.argv[1] if len(sys.argv) > 1 else ''
    if arg == 'stats':
        print('Statistics')

        places = DB.con.execute('SELECT COUNT(*) FROM places').fetchone()[0]
        print('Places:', places)

        churches = DB.con.execute('SELECT COUNT(*) FROM wikidata_churches').fetchone()[0]
        print('Churches:', churches)

        catholic_churches = DB.con.execute('SELECT COUNT(*) FROM wikidata_churches WHERE messesinfo_id != ""').fetchone()[0]
        print('\twith MessesInfoId:', percentage(catholic_churches, churches))

        churches_diocese = DB.con.execute('SELECT COUNT(*) FROM wikidata_churches WHERE diocese_id IS NOT NULL').fetchone()[0]
        print('\twith Diocese:', percentage(churches_diocese, churches))

        churches_parish = DB.con.execute('SELECT COUNT(*) FROM wikidata_churches WHERE parish_id IS NOT NULL').fetchone()[0]
        print('\twith Parish:', percentage(churches_parish, churches))

        churches_diocese_or_parish = DB.con.execute('SELECT COUNT(*) FROM wikidata_churches WHERE parish_id IS NOT NULL OR diocese_id IS NOT NULL').fetchone()[0]
        print('\twith Diocese or Parish:', percentage(churches_diocese_or_parish, churches))

        dioceses = DB.con.execute('SELECT COUNT(*) FROM dioceses').fetchone()[0]
        print('Dioceses:', dioceses)

        catholic_dioceses = DB.con.execute('SELECT COUNT(*) FROM dioceses WHERE gcatholic_id != ""').fetchone()[0]
        print('\twith GCatholicId:', percentage(catholic_dioceses, dioceses))

        parishes = DB.con.execute('SELECT COUNT(*) FROM parishes').fetchone()[0]
        print('Parishes:', parishes)

        catholic_parishes = DB.con.execute('SELECT COUNT(*) FROM parishes WHERE messesinfo_id != ""').fetchone()[0]
        print('\twith MessesInfoId:', percentage(catholic_parishes, parishes))
    else:
        q = Query()
        q.init_caches()
        dioceses = q.fetch('wikidata_dioceses.json', dioceses_query)
        q.update_dioceses(dioceses)
        parishes = q.fetch('wikidata_parishes.json', parishes_query)
        q.update_parishes(parishes)
        churches = q.fetch('wikidata_churches.json', churches_query)
        q.update_churches(churches)

        # Create missing Churches
        DB.con.execute('''
            INSERT INTO churches (`wikidata_church_id`)
            SELECT `wikidata_church_id` FROM wikidata_churches WHERE wikidata_church_id NOT IN
            (SELECT `wikidata_church_id` FROM `churches`);
        ''')
