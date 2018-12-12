<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiPlaceControllerTest extends WebTestCase
{
    public function testGetPlaces()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://127.0.0.1:1819/api/places.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(3, $data);
    }

    public function testGetEachPlace()
    {
        $client = static::createClient();
        $places = [
            1 => ['name' => 'France', 'parent' => null],
            2 => ['name' => 'Île-de-France', 'parent' => 1],
            3 => ['name' => 'Paris', 'parent' => 2],
        ];
        foreach ($places as $id => $place) {
            $crawler = $client->request('GET', 'http://127.0.0.1:1819/api/places/'.$id.'.json');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $json = $client->getResponse()->getContent();
            $data = json_decode($json, true);
            $this->assertEquals($place['name'], $data['name']);
            $this->assertEquals($place['parent'], $data['parent']['id']);
        }
    }

    public function testPostPlace()
    {
        $client = static::createClient();
        $crawler = $client->request('POST', 'http://127.0.0.1:1819/api/places.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testPutPlace()
    {
        $client = static::createClient();
        $crawler = $client->request('PUT', 'http://127.0.0.1:1819/api/places/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeletePlace()
    {
        $client = static::createClient();
        $crawler = $client->request('DELETE', 'http://127.0.0.1:1819/api/places/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
