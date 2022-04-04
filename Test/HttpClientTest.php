<?php
declare(strict_types=1);

namespace Test;

use HttpClient\HttpClient;
use HttpClient\HttpMethod;
use Symfony\Component\Process\Process;

/**
 * Class ApiHttpClientTestWs Comment
 * class de test pour ApiHttpCLient ils sont dans un dossier a part
 * car il nécessite un serveur externe pour renvoyer des donées
 * @group wsTest
 */
class HttpClientTest extends TestCase
{

    /** @var \Symfony\Component\Process\Process */
    private static Process $process;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $server = __DIR__ . "/server";
        self::$process = new Process(["php", "-S", "localhost:9874", "-t", $server]);
        self::$process->start();
        usleep(100000);
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$process->stop();
    }

    public function testClient(): void
    {
        $action = $this->getClient();
        $clef1 = $action->addParamRequest("localhost:9874/ressources", [], HttpMethod::GET);
        $clef2 = $action->addParamRequest("localhost:9874/ressourcesbis", [], HttpMethod::GET);
        $action->execAll();
        $rep1 = $action->getResult($clef1);
        $rep2 = $action->getResult($clef2);
        self::assertTrue($rep1->isSuccess());
        self::assertTrue($rep2->isSuccess());
        self::assertIsArray($rep1->getHeaders());
        self::assertIsArray($rep2->getHeaders());
        self::assertIsArray($rep1->getData());
        self::assertIsArray($rep2->getData());
    }

    public function getClient(): HttpClient
    {
        return new HttpClient($this->getLogger());
    }

    public function testClientTimeout(): void
    {
        $client = $this->getClient();
        $client->setTimeout(1);
        $response = $client->curlUnique('localhost:9874/timeout/2');
        self::assertEquals(408, $response->getCode());

        $client->setTimeout(3);
        $response = $client->curlUnique('localhost:9874/timeout/1');
        self::assertNotEquals(408, $response->getCode());
    }
}
