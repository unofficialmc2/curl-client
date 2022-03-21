<?php
declare(strict_types=1);

namespace HttpClientStub;

use HttpClient\HttpClientInterface;
use HttpClient\HttpMethod;
use HttpClient\HttpResponse;
use RuntimeException;

/**
 * Class HttpClientStub
 */
class HttpClient implements HttpClientInterface
{
    /** @var array<string,HttpResponse> */
    private array $result;
    /** @var array<int,string> */
    private array $clefs;
    /** @var int */
    private int $index = 0;

    /**
     * @param string $url
     * @param array<string,mixed> $headers
     * @param string $methode
     * @param string $data
     * @return string
     */
    public function addParamRequest(string $url, array $headers = [], string $methode = HttpMethod::GET, string $data = ''): string
    {

        if (!isset($this->clefs[$this->index])) {
            throw new RuntimeException("Il n'y a pas assez d'éléments dans les résultats du stub");
        }

        $clef = $this->clefs[$this->index];
        $this->index++;
        return $clef;
    }

    /**
     * @param int|false $code
     * @param array<string, string> $headers
     * @param string $data
     */
    public function addResult($code, array $headers, string $data): void
    {
        $clef = $this->getClef(8);
        $this->clefs[] = $clef;
        $this->result[$clef] = new HttpResponse($code, $headers, $data);
    }

    /**
     * @param int $length
     * @return string
     */
    private function getClef(int $length): string
    {
        /** @noinspection CryptographicallySecureRandomnessInspection */
        $data = openssl_random_pseudo_bytes($length / 2, $strong);
        if (false === $strong || false === $data) {
            throw new RuntimeException("Un problème est survenu lors d'une génération cryptographique.");
        }
        return bin2hex($data);
    }

    /** Stub */
    public function execAll(): void
    {
    }

    /** Stub */
    public function waitResult(): void
    {
    }

    /**
     * @param string $clef
     * @return HttpResponse
     */
    public function getResult(string $clef): HttpResponse
    {
        return $this->result[$clef];
    }

    /** Stub */
    public function resetResult(): void
    {
        $this->result = [];
        $this->clefs = [];
        $this->index = 0;
    }

    /**
     * @param string $url
     * @param array<string, mixed> $headers
     * @param string $methode
     * @param string $data
     * @return HttpResponse
     */
    public function curlUnique(string $url, array $headers = [], string $methode = HttpMethod::GET, string $data = ''): HttpResponse
    {
        if (!isset($this->clefs[$this->index])) {
            throw new RuntimeException("Il n'y a pas assez d'éléments dans les résultats du stub");
        }
        $clef = $this->clefs[$this->index];
        $this->index++;
        return $this->result[$clef];
    }

    /** Stub */
    public function followRedirect(): void
    {
    }
}
