#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from codecs import open
import pandas as pd
import urllib.parse
import json
import re
import os
import time
from sqlalchemy import create_engine, exc

from SPARQLWrapper import SPARQLWrapper, JSON
#from geopy.geocoders import Nominatim

#TODO: connect dataframe to sql database

class Query(object):

    def __init__(self):

        self.filename = os.path.dirname(os.path.abspath(__file__)) + '/wikidata_all.json'
        self.get_props()
        self.get_types()

    @staticmethod
    def decode(string):
        try:
            res = urllib.parse.unquote(string.split('/')[-1]).replace('_', ' ')
            return res
        except:
            return None

    def get_types(self):

        types = ['cathédrale',
                 'église',
                 'chapelle',
                 'abbatiale',
                 'Église paroissiale',
                 'abbaye',
                 'prieuré',
                 'collégiale',
                 'pro-cathédrale',
                 'wooden church',
                 'monastère',
                 'couvent',
                 'chartreuse',
                 'co-cathédrale']

        with open('types.json', 'rb', encoding='utf-8') as f:
            all_types = json.load(f)
        types = [all_types[e] for e in types]

        self.types = types

    def get_props(self):

        props = {}
        props['country'] = 17
        props['image'] = 18
        #props['nature'] = 31 # FIXME
        #props['loc_admin'] = 131 # FIXME
        props['coords'] = 625
        #props['diocese'] = 708 # FIXME
        #props['id_messe_info'] = 1644 # FIXME

        self.props = props

    def fetch(self):
        if os.path.isfile(self.filename) and os.path.getmtime(self.filename) > time.time() - 12 * 3600: # cache JSON for 12 hours
            with open(self.filename, 'r', encoding='utf-8') as content_file:
                print('Loading from file, please wait...')
                self.data = json.loads(content_file.read())
                return

        endpoint = "https://query.wikidata.org/bigdata/namespace/wdq/sparql"
        print('Query running, please wait...')

        sparql = SPARQLWrapper(endpoint)

        query = 'PREFIX schema: <http://schema.org/> SELECT DISTINCT ?churches ?commonslink ?P17 ?P18 ?P625 ?label_fr ?description_fr ?link_fr ?modified WHERE { {  ?churches (wdt:P31/wdt:P279*) wd:Q16970 . }  ?churches schema:dateModified ?modified  OPTIONAL {?churches wdt:P17 ?P17 .} OPTIONAL {?churches wdt:P18 ?P18 .} OPTIONAL {?churches wdt:P625 ?P625 .} OPTIONAL { ?churches rdfs:label ?label_fr filter (lang(?label_fr) = "fr") .} OPTIONAL { ?churches schema:description ?description_fr FILTER((LANG(?description_fr)) = "fr") . } OPTIONAL { ?link_de schema:isPartOf [ wikibase:wikiGroup "wikipedia" ] ; schema:inLanguage "fr" ; schema:about ?churches} OPTIONAL { ?churches ^schema:about [ schema:isPartOf <https://commons.wikimedia.org/>; schema:name ?commonslink ] . FILTER( STRSTARTS( ?commonslink, "Category:" )) . } SERVICE wikibase:label { bd:serviceParam wikibase:language "fr". } }' # FIXME remove description & commonslink ; add missing props

        sparql.setQuery(query)
        sparql.setReturnFormat(JSON)
        self.data = sparql.query().convert()

        if len(self.data) > 0:
            json.dump(self.data, open(self.filename, 'wb', encoding='utf-8'))

    def to_df(self):

        df = pd.DataFrame(self.data['results']['bindings'])

        def process(dic):

            try:
                res = dic['value'].split('/')[-1]
                return res
            except TypeError:
                return dic

        def get_coords(l):
            reg = re.compile('-?\d+.*\d+')
            try:
                l = l.split(' ')
                l = [reg.findall(e)[0] for e in l]
                if len(l) != 2:
                    return 0, 0
                else:
                    return l
            except AttributeError:
                return 0, 0

        df = df.applymap(process)

        df['latitude'] = df['P625'].apply(lambda x:get_coords(x)[0])
        df['longitude'] = df['P625'].apply(lambda x:get_coords(x)[1])

        self.props = {'P'+str(v):k for k,v in self.props.items()}
        df = df.rename(columns=self.props)
        #df['image'] = df['image'].apply(Query.decode) # FIXME
        del df['coords'] # FIXME
        del df['commonslink'] # FIXME
        del df['description_fr'] # FIXME
        del df['modified'] # FIXME
        del df['country'] # FIXME
        del df['image'] # FIXME
        #del df['nature'] # FIXME
        #del df['loc_admin'] # FIXME
        #del df['id_messe_info'] # FIXME
        df = df.rename(columns={'churches':'wikidata_church_id'})
        df = df.rename(columns={'label_fr':'name'})
        df['wikidata_church_id'] = df['wikidata_church_id'].apply(lambda x:int(x.replace('Q', '')))
        df['place_id'] = 1 # FIXME
        df['address'] = '' # FIXME
        df['created_at'] = '2018-12-12' # FIXME
        df['updated_at'] = '2018-12-12' # FIXME

        self.df = df.drop_duplicates(subset='wikidata_church_id')

        df.to_csv(self.filename + '.csv', encoding = 'utf-8', index=False)

    def to_sql(self):

        engine = create_engine("mysql+pymysql://openchurch:openchurch@127.0.0.1:13306/openchurch")
        con = engine.connect()
        self.df.to_sql(con=con, name='wikidata_churches', if_exists='append', index=False)
        con.close()

if __name__ == '__main__':
    q = Query()
    q.fetch()
    q.to_df()
    q.to_sql()

