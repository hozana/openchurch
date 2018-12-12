<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiChurchControllerTest extends WebTestCase
{
    public function testGetChurches()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://127.0.0.1:1819/api/churches.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(1, $data);
    }

    public function testGetEachChurch()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://127.0.0.1:1819/api/churches/1.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertArrayHasKey('wikidataChurch', $data);
        $this->assertEquals('cathédrale Notre-Dame-de-Paris', $data['wikidataChurch']['name']);
        $this->assertArrayHasKey('place', $data['wikidataChurch']);
        $this->assertEquals('Paris', $data['wikidataChurch']['place']['name']);
    }

    public function testPostChurch()
    {
        $client = static::createClient();
        $crawler = $client->request('POST', 'http://127.0.0.1:1819/api/churches.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testPutChurch()
    {
        $client = static::createClient();
        $crawler = $client->request('PUT', 'http://127.0.0.1:1819/api/churches/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeleteChurch()
    {
        $client = static::createClient();
        $crawler = $client->request('DELETE', 'http://127.0.0.1:1819/api/churches/1.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
