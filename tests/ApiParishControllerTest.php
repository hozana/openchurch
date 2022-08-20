<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiParishControllerTest extends WebTestCase
{
    public function testGetParishes()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/parishes.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(1, $data);
    }

    public function testGetParishesWithPagination()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/parishes.json?page=2');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(0, $data);
    }

    public function testGetEachParish()
    {
        $client = static::createClient();
        $parishes = [
            97293132 => ['name' => 'Paroisse Saint-Sulpice'],
        ];
        foreach ($parishes as $id => $parish) {
            $client->request('GET', 'http://127.0.0.1:1819/api/parishes/'.$id.'.json');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $json = $client->getResponse()->getContent();
            $data = json_decode($json, true);
            $this->assertEquals($parish['name'], $data['name']);
        }
    }

    public function testPostParish()
    {
        $client = static::createClient();
        $client->request('POST', 'http://127.0.0.1:1819/api/parishes.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testPutParish()
    {
        $client = static::createClient();
        $client->request('PUT', 'http://127.0.0.1:1819/api/parishes/97293132.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeleteParish()
    {
        $client = static::createClient();
        $client->request('DELETE', 'http://127.0.0.1:1819/api/parishes/97293132.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
