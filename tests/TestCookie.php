<?php

namespace Tests;

use Fize\Codec\Json;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * @notice 该测试仅支持windows环境
 */
class TestCookie extends TestCase
{

    /**
     * @var bool
     */
    protected static $seriver = false;

    /**
     * @var Client
     */
    protected $client;

    /**
     * 构造时启动内置服务器用于测试
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if (!self::$seriver) {
            self::$seriver = true;
            $cmd = 'start cmd /k "cd /d %cd%/../examples &&php -S localhost:8123"';
            $pid = popen($cmd, 'r');
            pclose($pid);
            sleep(3);  //待服务器启动
        }

        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => 'http://localhost:8123'
            ]);
        }
    }

    public function test__construct()
    {
        $response = $this->client->get('cookie___construct.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);

        var_dump($json['data']);

        self::assertEquals('value1', $json['data']['value1']);
        self::assertEquals('value2', $json['data']['value2']);
        self::assertEquals('value3', $json['data']['value3']);
    }

    public function testOnTamper()
    {
        $response = $this->client->get('cookie_onTamper.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);
        self::assertEquals('value2', $json['data']['status']['fz_key1']);
        self::assertEquals('value1', $json['data']['value1']);
        self::assertFalse($json['data']['value2']);
    }

    public function testSet()
    {
        $response = $this->client->get('cookie_set.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);

        var_dump($json['data']);

        self::assertEquals('value1', $json['data']['value1']);
        self::assertEquals('value2', $json['data']['value2']);
        self::assertEquals('value3', $json['data']['value3']);
    }

    public function testGet()
    {
        $response = $this->client->get('cookie_get.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);

        var_dump($json['data']);

        self::assertEquals('value1', $json['data']['value1']);
        self::assertEquals('value2', $json['data']['value2']);
        self::assertEquals('value3', $json['data']['value3']);
    }

    public function testHas()
    {
        $response = $this->client->get('cookie_has.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);

        var_dump($json['data']);

        self::assertTrue($json['data']['value10']);
        self::assertFalse($json['data']['value11']);
        self::assertTrue($json['data']['value20']);
        self::assertFalse($json['data']['value21']);
        self::assertTrue($json['data']['value30']);
        self::assertFalse($json['data']['value31']);
    }

    public function testDelete()
    {
        $response = $this->client->get('cookie_delete.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);
        self::assertEquals('value1', $json['data']['value1']);
        self::assertEquals('value2', $json['data']['value2']);
        self::assertEquals(false, $json['data']['value3']);
    }

    public function testClear()
    {
        $response = $this->client->get('cookie_clear.php');
        $body = $response->getBody();
        $json = Json::decode($body);

        self::assertIsArray($json);
        self::assertEquals(0, $json['errcode']);

        var_dump($json['data']);

        self::assertTrue($json['data']['value10']);
        self::assertFalse($json['data']['value11']);
        self::assertFalse($json['data']['value12']);
        self::assertTrue($json['data']['value20']);
        self::assertFalse($json['data']['value21']);
        self::assertFalse($json['data']['value22']);
        self::assertFalse($json['data']['value30']);
        self::assertTrue($json['data']['value31']);
        self::assertFalse($json['data']['value32']);
    }
}
