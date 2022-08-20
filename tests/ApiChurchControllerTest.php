<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiChurchControllerTest extends WebTestCase
{
    public function testGetChurches()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/churches.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(2, $data);
    }

    public function testGetChurchesByParish()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/churches.json?parishId=97293132');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(1, $data);
    }

    public function testGetChurchesByDiocese()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/churches.json?dioceseId=1242250');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(2, $data);
    }

    public function testGetChurchesByPlace()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/churches.json?placeId=3');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(2, $data);
    }

    public function testGetChurchesWithPagination()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/churches.json?page=2');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(0, $data);
    }

    public function testGetEachChurch()
    {
        $client = static::createClient();
        $places = [
            1 => ['name' => 'Église Saint-Sulpice de Paris'],
            2 => ['name' => 'cathédrale Notre-Dame-de-Paris'],
        ];
        foreach ($places as $id => $place) {
            $client->request('GET', 'http://127.0.0.1:1819/api/churches/'.$id.'.json');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $json = $client->getResponse()->getContent();
            $data = json_decode($json, true);
            $this->assertEquals($place['name'], $data['wikidataChurch']['name']);
            $this->assertEquals('Paris', $data['wikidataChurch']['place']['name']);
        }
    }

    public function testPostChurch()
    {
        $client = static::createClient();
        $client->request('POST', 'http://127.0.0.1:1819/api/churches.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testPutChurch()
    {
        $client = static::createClient();
        $client->request('PUT', 'http://127.0.0.1:1819/api/churches/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeleteChurch()
    {
        $client = static::createClient();
        $client->request('DELETE', 'http://127.0.0.1:1819/api/churches/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
