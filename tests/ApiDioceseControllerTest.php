<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDioceseControllerTest extends WebTestCase
{
    public function testGetDioceses()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/dioceses.json');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(1, $data);
    }

    public function testGetDiocesesWithPagination()
    {
        $client = static::createClient();
        $client->request('GET', 'http://127.0.0.1:1819/api/dioceses.json?page=2');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = $client->getResponse()->getContent();
        $data = json_decode($json, true);
        $this->assertCount(0, $data);
    }

    public function testGetEachDiocese()
    {
        $client = static::createClient();
        $dioceses = [
            1242250 => ['name' => 'Archidiocèse de Paris'],
        ];
        foreach ($dioceses as $id => $diocese) {
            $client->request('GET', 'http://127.0.0.1:1819/api/dioceses/'.$id.'.json');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $json = $client->getResponse()->getContent();
            $data = json_decode($json, true);
            $this->assertEquals($diocese['name'], $data['name']);
        }
    }

    public function testPostDiocese()
    {
        $client = static::createClient();
        $client->request('POST', 'http://127.0.0.1:1819/api/dioceses.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testPutDiocese()
    {
        $client = static::createClient();
        $client->request('PUT', 'http://127.0.0.1:1819/api/dioceses/1242250.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeleteDiocese()
    {
        $client = static::createClient();
        $client->request('DELETE', 'http://127.0.0.1:1819/api/dioceses/1242250.json', []);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }
}
