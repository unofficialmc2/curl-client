<?php

declare(strict_types=1);

namespace HttpClient;

/**
 * Class ApiHttpResponse
 * Reponse retourner Par ApihttpClient
 * @package Api\Utilities
 */
class HttpResponse
{
    /**
     * @var bool
     */
    private bool $success;
    /**
     * @var string
     */
    private string $data;
    /**
     * @var string[]
     */
    private array $headers;
    /**
     * @var false|int
     */
    private $code;

    /**
     * HttpResponse constructor.
     * @param int|false $code
     * @param array<string, string> $headers
     * @param string $data
     */
    public function __construct($code, array $headers = [], string $data = '')
    {
        $this->success = is_int($code) && $code >= 100 && $code < 400;
        $this->data = $data;
        $this->code = $code;
        $this->headers =array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * @param bool $isJson
     * @param bool $associative n'a auccun impact si $isJson = false
     * @return mixed
     * @throws \JsonException
     */
    public function getData(bool $isJson = true, bool $associative = true)
    {
        if ($isJson && !empty($this->data)) {
            return json_decode($this->data, $associative, 512, JSON_THROW_ON_ERROR);
        }
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function isRedirect(): bool
    {
        return ($this->code === 302 || $this->code === 301);
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retourne le code de la réponse
     * @return int
     */
    public function getCode(): int
    {
        if (is_int($this->code)) {
            return $this->code;
        }
        return 500;
    }

    /**
     * @param int $code
     * @return bool
     */
    public function isCode(int $code): bool
    {
        return $this->code === $code;
    }

    /**
     * @param string $header
     * @return string|false
     */
    public function getHeader(string $header)
    {
        return $this->headers[strtolower($header)] ?? false;
    }
}
