#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import re
import os
import time
import datetime
import pywikibot
import urllib.parse

from codecs import open
from sqlalchemy import create_engine, exc, MetaData, Table, orm, func, insert, update
from SPARQLWrapper import SPARQLWrapper, JSON


class DB:
    now = func.current_timestamp()
    engine = create_engine("mysql+pymysql://openchurch:openchurch@127.0.0.1:13306/openchurch")
    con = engine.connect()
    metadata = MetaData(bind=engine)
    session = orm.sessionmaker(bind=engine)()
    places = Table('places', metadata, autoload=True)
    churches = Table('wikidata_churches', metadata, autoload=True)

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
    dateformat = '%Y-%m-%d %H:%M:%S'

    def __init__(self):
        self.filename = os.path.dirname(os.path.abspath(__file__)) + '/wikidata_all.json'
        self.cache_places = {}
        self.cache_churches = {}

    @staticmethod
    def decode(string):
        return urllib.parse.unquote(string.split('/')[-1]).replace('_', ' ')

    @staticmethod
    def ucfirst(myStr):
        if len(myStr) < 1:
            return myStr
        return myStr[0].upper() + myStr[1:]

    def init_caches(self):
        if not len(self.cache_places):
            places = DB.con.execute('SELECT place_id FROM places').fetchall()
            self.cache_places = [wikidata_id for (wikidata_id,) in places]
        print(len(self.cache_places), 'places in cache')
        if not len(self.cache_churches):
            churches = DB.con.execute('SELECT wikidata_church_id, updated_at FROM wikidata_churches').fetchall()
            self.cache_churches = {wikidata_id: date_time for (wikidata_id, date_time) in churches}
        print(len(self.cache_churches), 'churches in cache')

    def fetch(self):
        if os.path.isfile(self.filename) and os.path.getmtime(self.filename) > time.time() - 12 * 3600: # cache JSON for 12 hours
            with open(self.filename, 'r', encoding='utf-8') as content_file:
                print('Loading from file, please wait...')
                self.data = json.loads(content_file.read())
                return

        endpoint = "https://query.wikidata.org/bigdata/namespace/wdq/sparql"
        print('Query running, please wait...')

        sparql = SPARQLWrapper(endpoint)

        query = 'PREFIX schema: <http://schema.org/> SELECT DISTINCT ?churches ?P17 ?P18 ?P31 ?P131 ?P625 ?P708 ?P1644 ?label_fr ?modified WHERE { {?churches (wdt:P31/wdt:P279*) wd:Q16970 .} ?churches schema:dateModified ?modified OPTIONAL {?churches wdt:P17 ?P17 .} OPTIONAL {?churches wdt:P18 ?P18 .} OPTIONAL {?churches wdt:P31 ?P31 .} OPTIONAL {?churches wdt:P131 ?P131 .} OPTIONAL {?churches wdt:P625 ?P625 .} OPTIONAL {?churches wdt:P708 ?P708 .} OPTIONAL {?churches wdt:P1644 ?P1644 .} OPTIONAL {?churches rdfs:label ?label_fr filter (lang(?label_fr) = "fr") .} SERVICE wikibase:label {bd:serviceParam wikibase:language "fr".} }'

        sparql.setQuery(query)
        sparql.setReturnFormat(JSON)
        self.data = sparql.query().convert()

        if len(self.data) > 0:
            json.dump(self.data, open(self.filename, 'wb', encoding='utf-8'))

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

    def update(self, from_file = False):
        if 'results' in self.data.keys() and 'bindings' in self.data['results'].keys():
            t = len(self.data['results']['bindings'])
            print(t, 'elements loaded')
            i = 0
            j = 0
            for item in self.data['results']['bindings']:
                i += 1
                if i % 1000 == 0:
                    DB.session.commit()
                wikidata_id = int(item['churches']['value'].split('/')[-1].replace('Q', ''))
                modified = item['modified']['value'].replace('T', ' ').replace('Z', '')
                #print(wikidata_id, wikidata_id in self.cache_churches, '/'+self.cache_churches[wikidata_id]+'/' if wikidata_id in self.cache_churches else '', '*'+modified+'*')
                if wikidata_id in self.cache_churches and self.cache_churches[wikidata_id].strftime(Query.dateformat) == modified:
                    print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> continue', end='    \r')
                    continue
                print('(%s/%s) Q%s' % (i, t, wikidata_id), '-> update', end='    \r')
                type_ = item['P31']['value'].split('/')[-1] if 'P31' in item.keys() else ''
                if int(type_.replace('Q', '')) not in Query.building_types:
                    continue # ignore item FIXME we may want to delete if from the DB
                place_id = int(item['P131']['value'].split('/')[-1].replace('Q', '')) if 'P131' in item.keys() else '' # FIXME manage multiple places
                if not place_id:
                    print('No location for Q%s                    ' % (wikidata_id,))
                    continue
                country_id = int(item['P17']['value'].split('/')[-1].replace('Q', '')) if 'P17' in item.keys() else ''
                image = Query.decode(item['P18']['value']) if 'P18' in item.keys() else ''
                interieur = Query.decode(item['P5775']['value']) if 'P5775' in item.keys() else ''
                commonscat = Query.decode(item['P373']['value']) if 'P373' in item.keys() else ''
                coordinates = item['P625']['value'].replace('Point(', '').replace(')', '').split(' ') if 'P625' in item.keys() and item['P625']['value'].startswith('Point') else ''
                latitude = coordinates[1] if coordinates else 0
                longitude = coordinates[0] if coordinates else 0
                label_fr = item['label_fr']['value'] if 'label_fr' in item.keys() else item['label_en']['value'] if 'label_en' in item.keys() else ''

                if place_id not in self.cache_places:
                    self.add_location(place_id)

                if country_id and country_id not in self.cache_places:
                    self.add_location(country_id, True)

                church = {
			'place_id': place_id,
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
            print('Finished')

if __name__ == '__main__':
    q = Query()
    q.init_caches()
    q.fetch()
    q.update()

